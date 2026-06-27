<?php
/**
 * ViceHub X — Sitemap dynamique (pages + articles + produits).
 * Génère des URL absolues pour un référencement optimal.
 */
require_once __DIR__ . '/config/config.php';

// Base absolue : BASE_URL si défini, sinon schéma + hôte de la requête.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . $host;

$abs = static function (string $path) use ($base): string {
    return $base . '/' . ltrim($path, '/');
};

$urls = [
    ['index.php', 'daily', '1.0'],
    ['pages/gta6.php', 'weekly', '0.9'],
    ['pages/news.php', 'daily', '0.9'],
    ['pages/recherche.php', 'monthly', '0.4'],
    ['pages/blog.php', 'daily', '0.8'],
    ['pages/guides.php', 'weekly', '0.8'],
    ['pages/leaks-lab.php', 'daily', '0.8'],
    ['pages/trailer-lab.php', 'weekly', '0.7'],
    ['pages/map.php', 'monthly', '0.7'],
    ['pages/dossier.php', 'weekly', '0.8'],
    ['pages/quiz.php', 'monthly', '0.6'],
    ['pages/cheats.php', 'monthly', '0.6'],
    ['pages/vehicles.php', 'monthly', '0.6'],
    ['pages/characters.php', 'monthly', '0.6'],
    ['pages/community.php', 'daily', '0.6'],
    ['pages/forum.php', 'daily', '0.7'],
    ['pages/classement.php', 'weekly', '0.5'],
    ['pages/galerie.php', 'weekly', '0.6'],
    ['pages/evenements.php', 'weekly', '0.6'],
    ['pages/shop.php', 'weekly', '0.8'],
    ['pages/deals.php', 'weekly', '0.6'],
    ['pages/presse.php', 'monthly', '0.5'],
    ['pages/contact.php', 'yearly', '0.3'],
    ['pages/legal.php', 'yearly', '0.2'],
];

// Articles publiés
try {
    $arts = db()->query("SELECT slug, published_at FROM articles WHERE status = 'published' ORDER BY published_at DESC")->fetchAll();
} catch (Throwable $e) {
    $arts = [];
}
// Produits actifs
try {
    $prods = db()->query("SELECT slug FROM products WHERE active = 1 ORDER BY sort ASC")->fetchAll();
} catch (Throwable $e) {
    $prods = [];
}
// Forum : catégories + sujets
try {
    $fcats = db()->query("SELECT slug FROM forum_categories ORDER BY sort ASC")->fetchAll(PDO::FETCH_COLUMN);
    $fthreads = db()->query("SELECT id FROM forum_threads ORDER BY last_post_at DESC LIMIT 500")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $fcats = $fthreads = [];
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as [$path, $freq, $prio]) {
    echo '  <url><loc>' . e($abs($path)) . '</loc><changefreq>' . $freq . '</changefreq><priority>' . $prio . "</priority></url>\n";
}
foreach ($arts as $a) {
    $lastmod = !empty($a['published_at']) ? '<lastmod>' . substr($a['published_at'], 0, 10) . '</lastmod>' : '';
    echo '  <url><loc>' . e($abs('pages/article.php?slug=' . urlencode($a['slug']))) . '</loc>' . $lastmod . "<changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
}
foreach ($prods as $p) {
    echo '  <url><loc>' . e($abs('pages/product.php?slug=' . urlencode($p['slug']))) . "</loc><changefreq>weekly</changefreq><priority>0.6</priority></url>\n";
}
// Profils publics des membres actifs
try {
    $members = db()->query("SELECT DISTINCT u.username FROM users u JOIN forum_posts p ON p.user_id = u.id ORDER BY u.username LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $members = [];
}
foreach ($members as $mu) {
    echo '  <url><loc>' . e($abs('pages/profil.php?u=' . urlencode($mu))) . "</loc><changefreq>weekly</changefreq><priority>0.4</priority></url>\n";
}
foreach ($fcats as $slug) {
    echo '  <url><loc>' . e($abs('pages/forum-category.php?cat=' . urlencode($slug))) . "</loc><changefreq>daily</changefreq><priority>0.5</priority></url>\n";
}
foreach ($fthreads as $tid) {
    echo '  <url><loc>' . e($abs('pages/forum-thread.php?id=' . (int) $tid)) . "</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>\n";
}

echo '</urlset>' . "\n";
