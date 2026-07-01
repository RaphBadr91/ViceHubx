<?php
/**
 * ViceHub X — Playlist JSON de la WebRadio auto-hébergée.
 * Liste les fichiers audio déposés dans /public/assets/radio/ (voir LISEZ-MOI.txt).
 * Le lecteur (vicefm.js) les enchaîne en continu comme une vraie station.
 */
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

$dir   = ROOT_PATH . '/public/assets/radio';
$tracks = [];

foreach (glob($dir . '/*.{mp3,MP3,ogg,OGG,m4a,M4A}', GLOB_BRACE) ?: [] as $file) {
    $base  = basename($file);
    $title = preg_replace('/\.(mp3|ogg|m4a)$/i', '', $base);
    $title = preg_replace('/^\s*\d+\s*[-_.]\s*/', '', (string) $title); // retire un numéro de piste en tête
    $title = trim(preg_replace('/[_-]+/', ' ', (string) $title));
    $tracks[] = [
        'src'   => BASE_URL . '/public/assets/radio/' . rawurlencode($base),
        'title' => $title !== '' ? $title : $base,
    ];
}

echo json_encode(['tracks' => $tracks], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
