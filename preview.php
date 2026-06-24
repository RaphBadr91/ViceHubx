<?php
/**
 * ViceHub X — Aperçu filigrané des wallpapers.
 * Génère (et met en cache) une version filigranée « ViceHub X » de l'image
 * privée stockée dans /storage/wallpapers. Le fichier propre n'est livré
 * qu'après paiement (voir download.php).
 *   Usage : /preview.php?p=wall-skyline
 */
require_once __DIR__ . '/config/config.php';

$p = preg_replace('/[^a-z0-9_-]/i', '', (string) ($_GET['p'] ?? ''));
if ($p === '') {
    http_response_code(404);
    exit;
}

// Fichier propre (téléchargé depuis le CDN au besoin)
$src = wallpaper_path($p) ?? '';

// Négociation de contenu : WebP si le navigateur le supporte (plus léger), sinon JPEG.
$accept  = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
$useWebp = function_exists('imagewebp') && str_contains($accept, 'image/webp');
$ext     = $useWebp ? 'webp' : 'jpg';
$ctype   = $useWebp ? 'image/webp' : 'image/jpeg';

$cacheDir = ROOT_PATH . '/public/assets/img/shop/cache/';
$cache = $cacheDir . $p . '.' . $ext;

// Cache valide → on le sert directement
if ($src !== '' && is_file($cache) && filemtime($cache) >= filemtime($src)) {
    header('Content-Type: ' . $ctype);
    header('Cache-Control: public, max-age=86400');
    header('Vary: Accept');
    readfile($cache);
    exit;
}

// Charge l'image source (ou un fond dégradé de repli si absente)
$im = null;
if ($src !== '') {
    $data = @file_get_contents($src);
    if ($data !== false) {
        $im = @imagecreatefromstring($data);
    }
}
if (!$im) {
    // Repli : dégradé néon + texte (l'aperçu n'est jamais cassé)
    $w = 1280; $h = 720;
    $im = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) {
        $r = (int) (20 + 120 * ($y / $h));
        $b = (int) (60 + 150 * (1 - $y / $h));
        $col = imagecolorallocate($im, $r, 20, $b);
        imageline($im, 0, $y, $w, $y, $col);
    }
}

// Redimensionne l'aperçu (max 1280 de large) — qualité réduite côté preview
$w = imagesx($im); $h = imagesy($im);
if ($w > 1280) {
    $im2 = imagescale($im, 1280);
    if ($im2) { imagedestroy($im); $im = $im2; $w = imagesx($im); $h = imagesy($im); }
}

// Filigrane « VICEHUB X » répété en diagonale
imagealphablending($im, true);
$mark = imagecolorallocatealpha($im, 255, 255, 255, 92);   // blanc ~28 % opacité
$shadow = imagecolorallocatealpha($im, 0, 0, 0, 100);
$text = 'VICEHUB X';
$fw = imagefontwidth(5) * strlen($text);
for ($y = 10, $row = 0; $y < $h; $y += 64, $row++) {
    $offset = ($row % 2) ? 90 : 0;
    for ($x = -$fw + $offset; $x < $w; $x += 190) {
        imagestring($im, 5, $x + 1, $y + 1, $text, $shadow);
        imagestring($im, 5, $x, $y, $text, $mark);
    }
}

// Sauvegarde en cache (si possible) puis sortie
header('Content-Type: ' . $ctype);
header('Cache-Control: public, max-age=86400');
header('Vary: Accept');
$emit = static function ($img, ?string $file) use ($useWebp) {
    if ($useWebp) {
        imagewebp($img, $file, 82);
    } else {
        imagejpeg($img, $file, 82);
    }
};
if (is_dir($cacheDir) && is_writable($cacheDir) && $src !== '') {
    $emit($im, $cache);
    readfile($cache);
} else {
    $emit($im, null);
}
imagedestroy($im);
