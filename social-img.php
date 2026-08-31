<?php
/**
 * ViceHub X — Sert l'illustration d'un article en JPEG public (pour Instagram).
 * L'API Instagram Content Publishing n'accepte QUE le JPEG : on convertit à la
 * volée l'image de l'article (WebP/PNG local ou URL CDN) en JPEG.
 *   Usage : /social-img.php?id=<article_id>
 */
require_once __DIR__ . '/config/config.php';
while (ob_get_level() > 0) { ob_end_clean(); } // binaire : pas de temporisation

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit; }

try {
    $st = db()->prepare('SELECT image FROM articles WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $img = (string) ($st->fetchColumn() ?: '');
} catch (Throwable $e) {
    http_response_code(500); exit;
}
if ($img === '') { http_response_code(404); exit; }

// GD requis pour convertir en JPEG. S'il manque (rare sur O2Switch), on renvoie
// l'image d'origine telle quelle plutôt qu'un 500 (les crawlers RS/og:image l'exigent).
if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
    if (preg_match('#^https?://#i', $img)) { header('Location: ' . $img, true, 302); }
    else { header('Location: /' . ltrim($img, '/'), true, 302); }
    exit;
}

// Récupère le binaire : fichier local, sinon URL distante (CDN).
$data = false;
if (preg_match('#^https?://#i', $img)) {
    $ch = curl_init($img);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => true]);
    $data = curl_exec($ch);
    curl_close($ch);
} else {
    $p = ROOT_PATH . '/' . ltrim($img, '/');
    if (is_file($p)) { $data = file_get_contents($p); }
}
if ($data === false || $data === '') { http_response_code(404); exit; }

$im = @imagecreatefromstring($data);
if (!$im) { http_response_code(415); exit; }

// Aplati sur fond sombre (au cas où PNG transparent) puis encode en JPEG.
$w = imagesx($im); $h = imagesy($im);
$out = imagecreatetruecolor($w, $h);
imagefill($out, 0, 0, imagecolorallocate($out, 12, 12, 20));
imagecopy($out, $im, 0, 0, 0, 0, $w, $h);

header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');
imagejpeg($out, null, 88);
imagedestroy($im);
imagedestroy($out);
