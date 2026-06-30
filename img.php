<?php
/**
 * ViceHub X — Redimensionneur d'images à la volée (WebP léger + cache + auto-réparation).
 *
 *   /img.php?f=<chemin ou nom de fichier>&w=<largeur>
 *
 * - Sert une version WebP (ou JPEG selon le navigateur) redimensionnée et compressée
 *   → cartes news/boutique/univers ultra-légères, chargement TRÈS rapide.
 * - Met le résultat en cache sur disque (servi instantanément ensuite, cache navigateur 1 an).
 * - Si l'image n'existe pas en local, va la chercher AU CDN (auto-réparation) → plus
 *   aucune image manquante.
 * - Ne casse jamais : repli sur un dégradé néon si la source est introuvable.
 */
require_once __DIR__ . '/config/config.php';

$f = (string) ($_GET['f'] ?? '');
$w = (int) ($_GET['w'] ?? 1000);
$w = max(160, min(1600, $w));

// Nettoyage anti-traversée.
$f = str_replace(["\0", '..'], '', $f);
$f = ltrim($f, '/');

function img_blank(): void
{
    header('Content-Type: image/gif');
    header('Cache-Control: public, max-age=600');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
    exit;
}

if ($f === '' || !preg_match('/\.(png|jpe?g|webp|gif)$/i', $f)) {
    img_blank();
}

$root = ROOT_PATH;

/* --- Résolution de la source : local, dossiers connus, sinon CDN --------- */
$srcFile = null;
$srcData = null;

$rp = realpath($root . '/' . $f);
if ($rp && is_file($rp) && (str_starts_with($rp, $root . '/public/') || str_starts_with($rp, $root . '/storage/'))) {
    $srcFile = $rp;
} else {
    $base = basename($f);
    foreach (['public/assets/img/scenes', 'public/assets/img/shop', 'public/assets/img/brand', 'public/assets/img'] as $d) {
        if (is_file($root . '/' . $d . '/' . $base)) { $srcFile = $root . '/' . $d . '/' . $base; break; }
    }
    if ($srcFile === null) {
        $url = cdn_url($base);
        if ($url !== '') {
            $tmp = @file_get_contents($url);
            if ($tmp !== false && strlen($tmp) > 200) { $srcData = $tmp; }
        }
    }
}

/* --- Négociation de format (WebP si supporté) --------------------------- */
$accept  = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
$useWebp = function_exists('imagewebp') && (str_contains($accept, 'image/webp') || $accept === '');
$ctype   = $useWebp ? 'image/webp' : 'image/jpeg';

/* --- Cache disque ------------------------------------------------------- */
$cacheDir = $root . '/storage/cache/img';
if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }
$stamp = $srcFile ? (string) @filemtime($srcFile) : 'cdn';
$key   = md5(($srcFile ?: ('cdn:' . $f)) . '|' . $w . '|' . $stamp . '|' . ($useWebp ? 'webp' : 'jpg'));
$cache = $cacheDir . '/' . $key . '.' . ($useWebp ? 'webp' : 'jpg');

if (is_file($cache) && filesize($cache) > 200) {
    header('Content-Type: ' . $ctype);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Vary: Accept');
    readfile($cache);
    exit;
}

/* --- Chargement + redimensionnement ------------------------------------ */
if ($srcFile !== null) { $srcData = @file_get_contents($srcFile); }
$im = ($srcData !== false && $srcData !== null) ? @imagecreatefromstring($srcData) : null;

if (!$im) {
    // Repli : dégradé néon (jamais d'image cassée).
    $h  = (int) round($w * 9 / 16);
    $im = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) {
        $col = imagecolorallocate($im, (int) (24 + 90 * $y / $h), 16, (int) (70 + 120 * (1 - $y / $h)));
        imageline($im, 0, $y, $w, $y, $col);
    }
}

$ow = imagesx($im);
if ($ow > $w) {
    $im2 = imagescale($im, $w);
    if ($im2) { imagedestroy($im); $im = $im2; }
}
@imagepalettetotruecolor($im);
@imagealphablending($im, false);
@imagesavealpha($im, true);

header('Content-Type: ' . $ctype);
header('Cache-Control: public, max-age=31536000, immutable');
header('Vary: Accept');

$wrote = false;
if (is_dir($cacheDir) && is_writable($cacheDir)) {
    $wrote = $useWebp ? @imagewebp($im, $cache, 78) : @imagejpeg($im, $cache, 82);
}
if ($wrote && is_file($cache)) {
    readfile($cache);
} else {
    if ($useWebp) { imagewebp($im, null, 78); } else { imagejpeg($im, null, 82); }
}
imagedestroy($im);
