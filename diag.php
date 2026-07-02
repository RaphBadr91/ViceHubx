<?php
/**
 * ViceHub X — Diagnostic serveur (lecture seule). À ouvrir, copier le résultat,
 * puis SUPPRIMER. Aide à voir pourquoi la vidéo / les images ne s'affichent pas.
 */
require_once __DIR__ . '/config/config.php';
setup_guard(); // 🔒 accès réservé (admin connecté ou VICEHUB_SETUP=1)
header('Content-Type: text/plain; charset=UTF-8');

function human($b) { return $b > 1048576 ? round($b / 1048576, 2) . ' Mo' : round($b / 1024) . ' Ko'; }
$R = ROOT_PATH;

echo "=== ViceHub X — DIAGNOSTIC ===\n";
echo "PHP        : " . PHP_VERSION . "\n";
echo "ROOT_PATH  : $R\n";
echo "BASE_URL   : " . (BASE_URL ?: '(vide)') . "\n";
echo "cURL dispo : " . (function_exists('curl_init') ? 'oui' : 'NON') . "\n\n";

/* --- HERO VIDEO --------------------------------------------------------- */
echo "--- HERO VIDEO ---\n";
$heroLocal = $R . '/public/assets/video/hero.mp4';
$exists = is_file($heroLocal);
echo "Fichier local public/assets/video/hero.mp4 : " . ($exists ? 'PRÉSENT (' . human(filesize($heroLocal)) . ')' : 'ABSENT') . "\n";
// reproduit la logique de index.php
$hv = trim((string) get_setting('hero_video', ''));
if ($hv === '' && $exists) { $hv = asset('video/hero.mp4'); }
if ($hv === '') { $hv = cdn_url('hero.mp4'); }
echo "URL vidéo calculée par le site        : " . ($hv ?: '(vide → canvas)') . "\n";
echo "Réglage admin 'hero_video'            : " . (get_setting('hero_video', '') ?: '(vide)') . "\n";
echo "URL CDN de la vidéo (cdn_map)         : " . (cdn_url('hero.mp4') ?: '(absente)') . "\n\n";

/* --- TEST CDN DEPUIS LE SERVEUR ----------------------------------------- */
echo "--- ACCÈS CDN DEPUIS LE SERVEUR ---\n";
$cdnHero = cdn_url('hero.mp4');
if ($cdnHero && function_exists('curl_init')) {
    $ch = curl_init($cdnHero);
    curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $len  = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    curl_close($ch);
    echo "HEAD vidéo CDN : HTTP $code · type $type · taille " . ($len > 0 ? human($len) : '?') . "\n";
} else {
    echo "(test impossible : pas d'URL CDN ou pas de cURL)\n";
}
echo "\n";

/* --- IMAGES LOCALES ----------------------------------------------------- */
echo "--- IMAGES LOCALES (après fetch-media) ---\n";
foreach (['public/assets/img/scenes', 'public/assets/img/shop', 'public/assets/img/brand', 'storage/wallpapers'] as $d) {
    $full = $R . '/' . $d;
    $n = is_dir($full) ? count(glob($full . '/*.{png,jpg,jpeg,webp}', GLOB_BRACE)) : 0;
    echo str_pad($d, 32) . " : $n fichier(s)\n";
}
echo "img_src(scenes/night.png) => " . img_src('/public/assets/img/scenes/night.png') . "\n\n";

/* --- ARTICLES ----------------------------------------------------------- */
echo "--- ARTICLES ---\n";
try {
    $r = db()->query("SELECT COUNT(*) t, SUM(image IS NULL OR image='') vide, SUM(image IS NOT NULL AND image<>'') ok FROM articles WHERE status='published'")->fetch();
    echo "Publiés : {$r['t']} · avec image : {$r['ok']} · sans image : {$r['vide']}\n";
    $ex = db()->query("SELECT title, image FROM articles WHERE status='published' ORDER BY id DESC LIMIT 3")->fetchAll();
    foreach ($ex as $a) { echo "  · " . mb_substr($a['title'], 0, 40) . " => " . ($a['image'] ?: '(aucune)') . "\n"; }
} catch (Throwable $e) { echo "Base : " . $e->getMessage() . "\n"; }

echo "\n--- CONTENU (vérif que tout est là) ---\n";
try {
    $c = db()->query("SELECT (SELECT COUNT(*) FROM users) u,(SELECT COUNT(*) FROM forum_posts) p,(SELECT COUNT(*) FROM products WHERE active=1) pr")->fetch();
    echo "Membres : {$c['u']} · messages forum : {$c['p']} · produits : {$c['pr']}\n";
} catch (Throwable $e) { echo $e->getMessage() . "\n"; }

echo "\n=== FIN — copie ce texte et envoie-le, puis SUPPRIME diag.php ===\n";
