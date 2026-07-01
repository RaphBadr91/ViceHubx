<?php
/**
 * ViceHub X — Compresse toutes les images en WebP LÉGER (statique), pour un
 * chargement TRÈS rapide. Fonctionne PAR LOTS et se relance tout seul jusqu'à
 * la fin (indispensable sur mutualisé : évite le time-out sur 100+ images).
 *
 *   1. Télécharge en local les images originales manquantes (depuis le CDN).
 *   2. Génère un WebP redimensionné + compressé pour chacune (scène ~3 Mo → ~90 Ko).
 *
 * Le site sert alors ces WebP statiques directement (rapide, en parallèle, sans PHP).
 * Idempotent : ne refait que ce qui manque. Laisse la page se recharger seule
 * jusqu'au message « TERMINÉ ». À ouvrir : https://vicehubx.com/optimize-images.php
 */
require_once __DIR__ . '/config/config.php';

@set_time_limit(0);
@ignore_user_abort(true);
@ini_set('memory_limit', '512M');

$isCli = (PHP_SAPI === 'cli');

// Budget par passage (le script se relance ensuite pour finir le reste).
$DL_BUDGET   = 12;  // téléchargements par passage (réseau, lent)
$CONV_BUDGET = 40;  // conversions WebP par passage (GD, rapide)

// Dossier => [largeur max, qualité WebP] — légers pour un chargement très rapide.
$jobs = [
    'public/assets/img/scenes' => [1200, 76],
    'public/assets/img/shop'   => [900, 80],
    'public/assets/img/brand'  => [1000, 86],
    'public/assets/img'        => [1280, 80],
];

$hasWebp = function_exists('imagewebp') && function_exists('imagecreatefromstring');

function to_webp(string $src, string $dst, int $maxW, int $q): array
{
    $srcBytes = (int) (@filesize($src) ?: 0);
    $data = @file_get_contents($src);
    if ($data === false) { return ['lecture impossible', $srcBytes, 0]; }
    $im = @imagecreatefromstring($data);
    if (!$im) { return ['décodage impossible', $srcBytes, 0]; }
    $w = imagesx($im);
    if ($w > $maxW) { $im2 = imagescale($im, $maxW); if ($im2) { imagedestroy($im); $im = $im2; } }
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
            CURLOPT_TIMEOUT => 90, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_USERAGENT => 'ViceHubX/1.0']);
        $d = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($d !== false && $code < 400 && strlen($d) > 500) ? $d : null;
    }
    $d = @file_get_contents($url);
    return ($d !== false && strlen($d) > 500) ? $d : null;
}

function dir_for(string $base): string
{
    if (preg_match('/^brand-/', $base)) { return 'public/assets/img/brand'; }
    if (preg_match('/^(poster-|tshirt|hoodie|cap|mug|mousepad|console|game-case|shop-)/', $base)) { return 'public/assets/img/shop'; }
    return 'public/assets/img/scenes';
}

$made = 0; $skip = 0; $fail = 0; $srcTotal = 0; $webpTotal = 0; $dl = 0; $log = [];

/* --- Liste des originaux voulus (cdn_map + images en base) -------------- */
$want = [];
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
} catch (Throwable $e) { /* base indispo */ }

/* --- Étape 1 : télécharge les originaux manquants (par lot) -------------- */
$dlLeft = 0;
foreach ($want as $rel => $url) {
    $dest = ROOT_PATH . '/' . $rel;
    if (is_file($dest) && filesize($dest) > 1000) { continue; }
    if ($dl >= $DL_BUDGET) { $dlLeft++; continue; }
    @mkdir(dirname($dest), 0755, true);
    $data = vh_fetch($url);
    if ($data !== null && @file_put_contents($dest, $data) !== false) {
        $dl++; $log[] = 'DL   ' . $rel;
    } else {
        $fail++; $log[] = 'DL ÉCHEC ' . $rel;
    }
}

/* --- Étape 2 : génère les WebP manquants (par lot) ---------------------- */
$convLeft = 0;
if (!$hasWebp) {
    $log[] = '✗ GD/imagewebp indisponible sur ce serveur.';
} else {
    foreach ($jobs as $dir => [$maxW, $q]) {
        $abs = ROOT_PATH . '/' . $dir;
        if (!is_dir($abs)) { continue; }
        foreach (glob($abs . '/*.{png,jpg,jpeg,PNG,JPG,JPEG}', GLOB_BRACE) ?: [] as $src) {
            $dst = preg_replace('/\.(png|jpe?g)$/i', '.webp', $src);
            if ($dst === $src) { continue; }
            if (is_file($dst) && filemtime($dst) >= filemtime($src) && filesize($dst) > 300) { $skip++; continue; }
            if ($made >= $CONV_BUDGET) { $convLeft++; continue; }
            [$status, $sb, $wb] = to_webp($src, $dst, $maxW, $q);
            if ($status === 'ok') {
                $made++; $srcTotal += $sb; $webpTotal += $wb;
                $log[] = 'OK   ' . str_replace(ROOT_PATH . '/', '', $dst) . '  (' . round($sb / 1024) . ' → ' . round($wb / 1024) . ' Ko)';
            } else {
                $fail++; $log[] = 'ÉCHEC ' . basename($src) . ' — ' . $status;
            }
        }
    }
}

$remaining = $dlLeft + $convLeft;      // éléments restants après ce passage
$done      = ($remaining === 0);
$savedMo   = $srcTotal > 0 ? round(($srcTotal - $webpTotal) / 1048576, 1) : 0;
$summary   = "Ce passage — téléchargés : {$dl} · WebP générés : {$made} · déjà à jour : {$skip} · échecs : {$fail}"
           . ($made > 0 ? " · gagné : {$savedMo} Mo" : '') . ($remaining ? " · RESTE ~{$remaining} à traiter…" : '');

if ($isCli) {
    // En CLI : boucle jusqu'à la fin automatiquement.
    echo $summary . "\n" . implode("\n", $log) . "\n";
    if (!$done) { echo "→ relance…\n"; }
    exit($done ? 0 : 7);
}
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<?php if (!$done): ?><meta http-equiv="refresh" content="1"><?php endif; ?>
<title>ViceHub X — Optimisation WebP</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(680px,94vw)">
    <h1 style="text-align:center;font-size:1.2rem">Optimisation WebP ⚡</h1>
    <div class="alert alert--<?= ($done && $fail === 0) ? 'ok' : ($done ? 'err' : 'ok') ?>" style="margin:1rem 0">
        <?= $done ? '✅ TERMINÉ — toutes les images sont compressées en WebP. Recharge le site (Ctrl+Maj+N).' : '⏳ Traitement en cours… la page se recharge toute seule. NE FERME PAS.' ?>
    </div>
    <p class="muted" style="font-size:.9rem"><?= e($summary) ?></p>
    <?php if ($done): ?>
        <p class="muted">✓ Le site sert désormais des WebP légers partout (news, boutique, véhicules, univers). C'est instantané.</p>
        <a class="btn btn--primary" href="<?= e(url('pages/news.php')) ?>" style="justify-content:center;width:100%">Voir les actus →</a>
        <p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Sécurité : <strong>supprime <code>optimize-images.php</code></strong> maintenant.</p>
    <?php else: ?>
        <div class="vh-loader__bar" style="margin:1rem 0"><i style="animation:none;width:60%"></i></div>
    <?php endif; ?>
    <pre style="max-height:300px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.72rem;line-height:1.5"><?= e(implode("\n", $log)) ?></pre>
</div></div></body></html>
