<?php
/**
 * ViceHub X — Fichier de vérification IndexNow.
 * IndexNow exige que la clé soit servie à https://host/<clé>.txt.
 * Cette clé est générée/stockée côté serveur (settings) — jamais en dur.
 * Servi via .htaccess : ^([a-f0-9]{32})\.txt$ → indexnow-key.php?k=$1
 */
require_once __DIR__ . '/config/config.php';

$k = preg_replace('/[^a-f0-9]/', '', (string) ($_GET['k'] ?? ''));
header('Content-Type: text/plain; charset=UTF-8');

if ($k !== '' && function_exists('indexnow_key') && hash_equals(indexnow_key(), $k)) {
    echo indexnow_key();
} else {
    http_response_code(404);
}
