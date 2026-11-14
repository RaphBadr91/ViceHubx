<?php
/**
 * ViceHub X — ads.txt dynamique (Google AdSense).
 * Généré depuis le réglage admin « adsense_client » (ca-pub-…). Évite les 404
 * répétées du robot AdSense et autorise la diffusion des annonces.
 * Si aucun compte AdSense n'est renseigné, renvoie une 404 (comportement correct).
 */
require_once __DIR__ . '/config/config.php';

$client = trim((string) get_setting('adsense_client', ''));
$pub    = preg_replace('/^ca-/', '', $client);

if (!preg_match('/^pub-\d{10,20}$/', (string) $pub)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=86400');
echo 'google.com, ' . $pub . ', DIRECT, f08c47fec0942fa0' . "\n";
