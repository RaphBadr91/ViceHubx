<?php
/**
 * ViceHub X — robots.txt dynamique (Sitemap en URL absolue, requis par Google).
 * Servi à la place de robots.txt via une règle de réécriture (.htaccess).
 */
require_once __DIR__ . '/config/config.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . $host;

header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /config/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /scripts/\n";
echo "Disallow: /uploads/\n";
echo "Disallow: /checkout.php\n";
echo "Disallow: /stripe-webhook.php\n";
echo "Disallow: /pages/cart.php\n";
echo "Disallow: /pages/checkout-success.php\n";
echo "Disallow: /pages/checkout-cancel.php\n";
// Note : on NE bloque PAS ?lang= (les hreflang pointent vers ces URL ; le
// canonical retire déjà le paramètre de langue pour éviter les doublons).
echo "\n";
echo "Sitemap: {$base}/sitemap.xml\n";
