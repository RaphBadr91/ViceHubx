<?php
/**
 * ViceHub X — Sitemap Google News.
 * Uniquement les articles publiés dans les 48 dernières heures (règle Google News),
 * avec le namespace news: requis. Prérequis à l'éligibilité Top Stories / onglet
 * Actualités — surface clé pendant la vague GTA VI. Servi via /news-sitemap.xml (.htaccess).
 */
require_once __DIR__ . '/config/config.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$abs    = static fn (string $p): string => $base . '/' . ltrim($p, '/');

try {
    $arts = db()->query(
        "SELECT title, slug, published_at, lang, image
         FROM articles
         WHERE status = 'published' AND published_at >= (NOW() - INTERVAL 48 HOUR)
         ORDER BY published_at DESC LIMIT 1000"
    )->fetchAll();
} catch (Throwable $e) {
    $arts = [];
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

foreach ($arts as $a) {
    $loc  = $abs('article/' . rawurlencode((string) $a['slug']));
    $lang = (($a['lang'] ?? 'fr') === 'en') ? 'en' : 'fr';
    $date = date('c', strtotime((string) $a['published_at']) ?: time()); // ISO 8601
    // Image de l'article : requise pour l'aperçu Top Stories / onglet Actualités.
    $img = trim((string) ($a['image'] ?? ''));
    $imgTag = '';
    if ($img !== '') {
        $imgAbs = preg_match('#^https?://#i', $img) ? $img : $abs(ltrim($img, '/'));
        $imgTag = '<image:image><image:loc>' . e($imgAbs) . '</image:loc></image:image>';
    }
    echo '  <url><loc>' . e($loc) . '</loc>'
        . '<news:news>'
        . '<news:publication><news:name>ViceHub X</news:name><news:language>' . $lang . '</news:language></news:publication>'
        . '<news:publication_date>' . e($date) . '</news:publication_date>'
        . '<news:title>' . e((string) $a['title']) . '</news:title>'
        . '</news:news>'
        . $imgTag
        . "</url>\n";
}

echo '</urlset>' . "\n";
