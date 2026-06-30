<?php
/**
 * ViceHub X — Génère des versions WebP LÉGÈRES (redimensionnées + compressées)
 * de toutes les images locales. Le site sert alors automatiquement ces WebP
 * (via webp_variant() + les balises <picture>), ce qui rend tout TRÈS fluide :
 * une scène passe de plusieurs Mo (PNG) à ~100-200 Ko (WebP).
 *
 * Le PNG/JPEG d'origine reste comme repli pour les très vieux navigateurs.
 * Idempotent : ne régénère que les WebP manquants ou périmés.
 *
 * À ouvrir UNE fois : https://vicehubx.com/optimize-images.php → puis SUPPRIMER.
 * (Fonctionne aussi en CLI : php optimize-images.php)
 */
require_once __DIR__ . '/config/config.php';

@set_time_limit(0);
@ignore_user_abort(true);
@ini_set('memory_limit', '512M');

$isCli = (PHP_SAPI === 'cli');

// Dossier => [largeur max, qualité WebP] — légers pour un chargement très rapide.
$jobs = [
    'public/assets/img/scenes' => [1200, 76],
    'public/assets/img/shop'   => [900, 80],
    'public/assets/img/brand'  => [1000, 86],
    'public/assets/img'        => [1280, 80], // images à la racine (poster, social…)
];

$hasWebp = function_exists('imagewebp') && function_exists('imagecreatefromstring');

/** Convertit une image en WebP redimensionné. Retourne [statut, octetsSource, octetsWebp]. */
function to_webp(string $src, string $dst, int $maxW, int $q): array
{
    $srcBytes = (int) (@filesize($src) ?: 0);
    $data = @file_get_contents($src);
    if ($data === false) { return ['lecture impossible', $srcBytes, 0]; }
    $im = @imagecreatefromstring($data);
    if (!$im) { return ['décodage impossible', $srcBytes, 0]; }

    $w = imagesx($im); $h = imagesy($im);
    if ($w > $maxW) {
        $im2 = imagescale($im, $maxW); // garde le ratio
        if ($im2) { imagedestroy($im); $im = $im2; }
    }
    // Préserve la transparence éventuelle (PNG → WebP alpha).
    @imagepalettetotruecolor($im);
    @imagealphablending($im, false);
    @imagesavealpha($im, true);

    $ok = @imagewebp($im, $dst, $q);
    imagedestroy($im);
    if (!$ok || !is_file($dst) || filesize($dst) < 300) { return ['encodage échoué', $srcBytes, 0]; }
    return ['ok', $srcBytes, (int) filesize($dst)];
}

function vh_fetch(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120, CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_USERAGENT => 'ViceHubX/1.0']);
        $d = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($d !== false && $code < 400 && strlen($d) > 500) ? $d : null;
    }
    $d = @file_get_contents($url);
    return ($d !== false && strlen($d) > 500) ? $d : null;
}

/** Dossier local cible selon le préfixe du nom de fichier. */
function dir_for(string $base): string
{
    if (preg_match('/^brand-/', $base)) { return 'public/assets/img/brand'; }
    if (preg_match('/^(poster-|tshirt|hoodie|cap|mug|mousepad|console|game-case|shop-)/', $base)) { return 'public/assets/img/shop'; }
    return 'public/assets/img/scenes';
}

$made = 0; $skip = 0; $fail = 0; $srcTotal = 0; $webpTotal = 0; $dl = 0; $log = [];

/* --- Étape 0 : s'assurer que TOUTES les images originales sont en local ---
   (sinon on ne peut pas générer leur WebP). On télécharge ce qui manque depuis
   le CDN : entrées du cdn_map + images réellement utilisées en base. */
$want = []; // chemin local relatif => URL CDN
$map = is_file(ROOT_PATH . '/config/cdn_map.php') ? (require ROOT_PATH . '/config/cdn_map.php') : [];
foreach ($map as $key => $url) {
    if ($key === 'hero.mp4' || !is_string($url) || $url === '' || !preg_match('/\.(png|jpe?g)$/i', $key)) { continue; }
    $want[dir_for($key) . '/' . $key] = $url;
}
try {
    $rows = db()->query(
        "SELECT image FROM products WHERE image LIKE '/public/%'
         UNION SELECT image FROM vehicles WHERE image LIKE '/public/%'
         UNION SELECT image FROM articles WHERE image LIKE '/public/%'"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $img) {
        $rel = ltrim((string) $img, '/');
        if (!preg_match('/\.(png|jpe?g)$/i', $rel)) { continue; }
        $url = cdn_url(basename($rel));
        if ($url !== '') { $want[$rel] = $url; }
    }
} catch (Throwable $e) { /* base indispo : on continue avec le cdn_map */ }

foreach ($want as $rel => $url) {
    $dest = ROOT_PATH . '/' . $rel;
    if (is_file($dest) && filesize($dest) > 1000) { continue; }
    @mkdir(dirname($dest), 0755, true);
    $data = vh_fetch($url);
    if ($data !== null && @file_put_contents($dest, $data) !== false) { $dl++; }
}

if (!$hasWebp) {
    $log[] = '✗ GD/imagewebp indisponible sur ce serveur (impossible de générer du WebP).';
} else {
    foreach ($jobs as $dir => [$maxW, $q]) {
        $abs = ROOT_PATH . '/' . $dir;
        if (!is_dir($abs)) { continue; }
        foreach (glob($abs . '/*.{png,jpg,jpeg,PNG,JPG,JPEG}', GLOB_BRACE) ?: [] as $src) {
            $dst = preg_replace('/\.(png|jpe?g)$/i', '.webp', $src);
            if ($dst === $src) { continue; }
            // Déjà fait et à jour ? on saute.
            if (is_file($dst) && filemtime($dst) >= filemtime($src) && filesize($dst) > 300) { $skip++; continue; }
            [$status, $sb, $wb] = to_webp($src, $dst, $maxW, $q);
            if ($status === 'ok') {
                $made++; $srcTotal += $sb; $webpTotal += $wb;
                $log[] = 'OK   ' . str_replace(ROOT_PATH . '/', '', $dst)
                       . '  (' . round($sb / 1024) . ' Ko → ' . round($wb / 1024) . ' Ko)';
            } else {
                $fail++; $log[] = 'ÉCHEC ' . basename($src) . ' — ' . $status;
            }
        }
    }
}

$savedMo = $srcTotal > 0 ? round(($srcTotal - $webpTotal) / 1048576, 1) : 0;
$summary = "Originaux rapatriés : {$dl} · WebP générés : {$made} · déjà à jour : {$skip} · échecs : {$fail}"
         . ($made > 0 ? " · poids économisé : {$savedMo} Mo" : '');

if ($isCli) {
    echo $summary . "\n" . implode("\n", $log) . "\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Optimisation WebP</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(660px,94vw)">
    <h1 style="text-align:center;font-size:1.2rem">Optimisation WebP ⚡</h1>
    <div class="alert alert--<?= ($hasWebp && $fail === 0) ? 'ok' : 'err' ?>" style="margin:1rem 0"><?= e($summary) ?></div>
    <?php if ($made > 0): ?>
        <p class="muted">✓ Le site sert désormais des images <strong>WebP légères</strong> partout (cartes news, boutique, univers). Recharge en <strong>navigation privée</strong> (Ctrl+Maj+N) : tout devient bien plus fluide.</p>
    <?php endif; ?>
    <?php if (!$hasWebp): ?>
        <p class="muted">⚠️ L'extension GD avec support WebP n'est pas active. Contacte le support O2Switch pour activer <code>imagewebp</code> (souvent déjà dispo en PHP 8).</p>
    <?php endif; ?>
    <?php if ($fail > 0): ?>
        <form method="post"><button class="btn btn--ghost" type="submit" style="width:100%;justify-content:center">Relancer</button></form>
    <?php endif; ?>
    <pre style="max-height:320px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.72rem;line-height:1.5"><?= e(implode("\n", $log)) ?></pre>
    <a class="btn btn--primary" href="<?= e(url('pages/news.php')) ?>" style="justify-content:center;width:100%">Voir les actus →</a>
    <p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Sécurité : <strong>supprime <code>optimize-images.php</code></strong> une fois terminé.</p>
</div></div></body></html>
