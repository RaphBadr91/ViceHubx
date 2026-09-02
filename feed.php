<?php
/**
 * ViceHub X — Flux RSS 2.0 des derniers articles publiés.
 * Sert /feed.xml (FR) et /feed.xml?lang=en (EN) via .htaccess.
 * Permet la syndication (agrégateurs, lecteurs) et l'auto-publication sur les
 * réseaux via Zapier/IFTTT/Make (RSS → X, Facebook, Telegram…).
 */
require_once __DIR__ . '/config/config.php';

$base = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : 'https://vicehubx.com';
$lng  = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'fr';

try {
    $q = db()->prepare(
        "SELECT title, slug, excerpt, image, published_at
         FROM articles WHERE status='published' AND lang=?
         ORDER BY published_at DESC LIMIT 30"
    );
    $q->execute([$lng]);
    $arts = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $arts = [];
}

header('Content-Type: application/rss+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>' . "\n";
echo '<title>' . e(APP_NAME . ' — GTA 6 ' . ($lng === 'en' ? 'News' : 'Actualités')) . '</title>' . "\n";
echo '<link>' . e($base . '/') . '</link>' . "\n";
echo '<description>' . e($lng === 'en'
    ? 'The latest GTA 6 news, leaks and guides from ViceHub X.'
    : 'Les dernières actualités, leaks et guides GTA 6 de ViceHub X.') . '</description>' . "\n";
echo '<language>' . ($lng === 'en' ? 'en' : 'fr') . '</language>' . "\n";
echo '<atom:link href="' . e($base . '/feed.xml' . ($lng === 'en' ? '?lang=en' : '')) . '" rel="self" type="application/rss+xml"/>' . "\n";

foreach ($arts as $a) {
    $url  = $base . '/article/' . rawurlencode((string) $a['slug']);
    $date = date(DATE_RSS, strtotime((string) ($a['published_at'] ?: 'now')) ?: time());
    echo '<item>';
    echo '<title>' . e((string) $a['title']) . '</title>';
    echo '<link>' . e($url) . '</link>';
    echo '<guid isPermaLink="true">' . e($url) . '</guid>';
    echo '<pubDate>' . e($date) . '</pubDate>';
    echo '<description>' . e((string) ($a['excerpt'] ?? '')) . '</description>';
    if (!empty($a['image'])) {
        $img = preg_match('#^https?://#', (string) $a['image'])
            ? (string) $a['image']
            : $base . '/' . ltrim((string) $a['image'], '/');
        echo '<enclosure url="' . e($img) . '" type="image/jpeg"/>';
    }
    echo '</item>' . "\n";
}
echo '</channel></rss>' . "\n";
