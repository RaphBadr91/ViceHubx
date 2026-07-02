<?php
/**
 * ViceHub X — Rapatrie EN LOCAL toutes les images des ARTICLES.
 *
 * Pour chaque article :
 *   1. Si le champ `image` est un simple nom de fichier (ex. « night.png ») ou une
 *      URL CDN, on le NORMALISE en chemin local /public/assets/img/<dossier>/<fichier>.
 *   2. On TÉLÉCHARGE le fichier image depuis le CDN vers l'hébergement (s'il manque).
 *   3. Les articles sans image sont illustrés avec la banque de scènes.
 *
 * Résultat : toutes les illustrations d'articles sont servies en LOCAL (rapide,
 * permanent), exactement comme en local. Idempotent : relançable sans risque.
 *
 * À ouvrir UNE fois : https://vicehubx.com/fetch-article-images.php → puis SUPPRIMER.
 * (Fonctionne aussi en CLI : php fetch-article-images.php)
 */
require_once __DIR__ . '/config/config.php';
setup_guard(); // 🔒 accès réservé (admin connecté ou VICEHUB_SETUP=1)
require_once ROOT_PATH . '/includes/ai.php'; // ai_pick_image()

@set_time_limit(0);
@ignore_user_abort(true);

$isCli = (PHP_SAPI === 'cli');

function vh_fetch(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120, CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_USERAGENT => 'ViceHubX-Media/1.0',
        ]);
        $data = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($data !== false && $code < 400 && strlen($data) > 500) ? $data : null;
    }
    $data = @file_get_contents($url);
    return ($data !== false && strlen($data) > 500) ? $data : null;
}

/** Dossier local cible selon le préfixe du nom de fichier (même logique que fetch-media). */
function art_dir_for(string $base): string
{
    if (preg_match('/^brand-/', $base)) {
        return 'public/assets/img/brand';
    }
    if (preg_match('/^(poster-|tshirt|hoodie|cap|mug|mousepad|console|game-case|shop-)/', $base)) {
        return 'public/assets/img/shop';
    }
    return 'public/assets/img/scenes'; // scènes + véhicules (veh-*)
}

$ok = 0; $skip = 0; $fail = 0; $norm = 0; $illus = 0; $log = [];

try {
    $arts = db()->query('SELECT id, title, image FROM articles')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $arts = [];
    $log[] = 'Base indisponible : ' . $e->getMessage();
}
$upd = db()->prepare('UPDATE articles SET image = ? WHERE id = ?');

foreach ($arts as $a) {
    $id  = (int) $a['id'];
    $img = trim((string) $a['image']);

    // 1) Sans image → on en pioche une dans la banque de scènes.
    if ($img === '') {
        $base  = ai_pick_image('', (string) $a['title']);
        $img   = '/' . art_dir_for($base) . '/' . $base;
        $illus++;
    }

    // 2) Détermine le nom de fichier, le chemin local cible et l'URL source.
    if (preg_match('#^https?://#i', $img)) {
        $base   = basename((string) (parse_url($img, PHP_URL_PATH) ?: $img));
        $rel    = art_dir_for($base) . '/' . $base;
        $srcUrl = $img;                 // déjà une URL → on télécharge celle-ci
    } else {
        $base = basename($img);
        $relLtrim = ltrim($img, '/');
        // Déjà un chemin /public/... → on le garde ; sinon nom de fichier nu → on range.
        $rel    = (str_starts_with($relLtrim, 'public/')) ? $relLtrim : art_dir_for($base) . '/' . $base;
        $srcUrl = cdn_url($base);       // URL CDN à partir du nom de fichier
    }

    // 3) Normalise le champ `image` en chemin local si nécessaire.
    $localPath = '/' . $rel;
    if ($img !== $localPath) {
        $upd->execute([$localPath, $id]);
        $norm++;
    }

    // 4) Télécharge le fichier s'il manque en local.
    $dest = ROOT_PATH . '/' . $rel;
    if (is_file($dest) && filesize($dest) > 1000) { $skip++; continue; }
    if ($srcUrl === '') { $fail++; $log[] = "SANS SOURCE  $base (absent du CDN)"; continue; }
    @mkdir(dirname($dest), 0755, true);
    $data = vh_fetch($srcUrl);
    if ($data !== null && @file_put_contents($dest, $data) !== false) {
        $ok++; $log[] = "OK   $rel (" . round(strlen($data) / 1024) . " Ko)";
    } else {
        $fail++; $log[] = "ÉCHEC $rel";
    }
}

$summary = "Articles : " . count($arts) . " · téléchargées : {$ok} · déjà en local : {$skip} · "
         . "chemins normalisés : {$norm} · illustrés : {$illus} · échecs : {$fail}";

if ($isCli) {
    echo $summary . "\n" . implode("\n", $log) . "\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Images des articles</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(640px,94vw)">
    <h1 style="text-align:center;font-size:1.2rem">Images des articles 🖼️</h1>
    <div class="alert alert--<?= $fail === 0 ? 'ok' : 'err' ?>" style="margin:1rem 0"><?= e($summary) ?></div>
    <p class="muted">✓ Toutes les illustrations d'articles sont désormais <strong>servies en local</strong> (chemins corrigés + fichiers rapatriés). Recharge le site (Ctrl+Maj+R).</p>
    <?php if ($fail > 0): ?>
        <p class="muted">⚠️ Quelques fichiers ont échoué (CDN momentanément indispo ou image absente du CDN). Tu peux <strong>relancer cette page</strong> : elle ne reprend que ce qui manque.</p>
        <form method="post"><button class="btn btn--ghost" type="submit" style="width:100%;justify-content:center">Relancer</button></form>
    <?php endif; ?>
    <pre style="max-height:300px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.72rem;line-height:1.5"><?= e(implode("\n", $log)) ?></pre>
    <a class="btn btn--primary" href="<?= e(url('pages/news.php')) ?>" style="justify-content:center;width:100%">Voir les actus →</a>
    <p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Sécurité : <strong>supprime <code>fetch-article-images.php</code></strong> une fois terminé.</p>
</div></div></body></html>
