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
    ['pages/gta6-pc.php', 'daily', '0.9'],
    ['pages/gta6-vs-gta5.php', 'monthly', '0.8'],
    ['pages/news.php', 'daily', '0.9'],
    ['pages/recherche.php', 'monthly', '0.4'],
    ['pages/blog.php', 'daily', '0.8'],
    ['pages/guides.php', 'weekly', '0.8'],
    ['pages/leaks-lab.php', 'daily', '0.8'],
    ['pages/trailer-lab.php', 'weekly', '0.7'],
    ['pages/map.php', 'monthly', '0.7'],
    ['pages/dossier.php', 'weekly', '0.8'],
    ['pages/quiz.php', 'monthly', '0.6'],
    ['pages/vice-persona.php', 'weekly', '0.8'],
    ['pages/cheats.php', 'monthly', '0.6'],
    ['pages/vehicles.php', 'monthly', '0.6'],
    ['pages/characters.php', 'monthly', '0.6'],
    ['pages/community.php', 'daily', '0.6'],
    ['pages/forum.php', 'daily', '0.7'],
    ['pages/classement.php', 'weekly', '0.5'],
    ['pages/galerie.php', 'weekly', '0.6'],
    ['pages/evenements.php', 'weekly', '0.6'],
    ['pages/shop.php', 'weekly', '0.8'],
    ['pages/fonds-ecran-gta6.php', 'weekly', '0.8'],
    ['pages/deals.php', 'weekly', '0.6'],
    ['pages/a-propos.php', 'monthly', '0.6'],
    ['pages/presse.php', 'monthly', '0.5'],
    ['pages/contact.php', 'yearly', '0.3'],
    ['pages/legal.php', 'yearly', '0.2'],
    ['pages/confidentialite.php', 'yearly', '0.2'],
    ['pages/cgu.php', 'yearly', '0.2'],
];

// Articles publiés (+ langue et article source, pour les alternances hreflang FR/EN)
try {
    $arts = db()->query("SELECT id, slug, published_at, lang, source_id FROM articles WHERE status = 'published' ORDER BY published_at DESC")->fetchAll();
} catch (Throwable $e) {
    $arts = [];
}
// Slug de la VO anglaise indexée par article FR source (pour relier les paires).
$enBySource = [];
foreach ($arts as $a) {
    if (($a['lang'] ?? 'fr') === 'en' && !empty($a['source_id'])) {
        $enBySource[(int) $a['source_id']] = (string) $a['slug'];
    }
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
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

// URL propres (sans /pages/ ni .php) — cohérent avec .htaccess et url().
$clean = static function (string $p): string {
    if ($p === 'index.php') { return ''; }
    return (string) preg_replace('#^pages/([a-z0-9-]+)\.php$#', '$1', $p);
};

foreach ($urls as [$path, $freq, $prio]) {
    echo '  <url><loc>' . e($abs($clean($path))) . '</loc><changefreq>' . $freq . '</changefreq><priority>' . $prio . "</priority></url>\n";
}
$artUrl = static function (string $slug) use ($abs): string {
    return $abs('article/' . rawurlencode($slug));
};
foreach ($arts as $a) {
    $lastmod = !empty($a['published_at']) ? '<lastmod>' . substr($a['published_at'], 0, 10) . '</lastmod>' : '';

    // Détermine la paire FR/EN de cet article pour les annotations hreflang.
    $frSlug = $enSlug = null;
    if (($a['lang'] ?? 'fr') === 'en') {
        $enSlug = (string) $a['slug'];
        if (!empty($a['source_id'])) {
            foreach ($arts as $b) {           // retrouve le slug FR source
                if ((int) $b['id'] === (int) $a['source_id']) { $frSlug = (string) $b['slug']; break; }
            }
        }
    } else {
        $frSlug = (string) $a['slug'];
        if (isset($enBySource[(int) $a['id']])) { $enSlug = $enBySource[(int) $a['id']]; }
    }

    // Annotations d'alternance de langue (uniquement si une traduction existe).
    $alts = '';
    if ($frSlug !== null && $enSlug !== null) {
        $alts .= '<xhtml:link rel="alternate" hreflang="fr" href="' . e($artUrl($frSlug)) . '"/>'
              .  '<xhtml:link rel="alternate" hreflang="en" href="' . e($artUrl($enSlug)) . '"/>'
              .  '<xhtml:link rel="alternate" hreflang="x-default" href="' . e($artUrl($enSlug)) . '"/>';
    }
    echo '  <url><loc>' . e($artUrl((string) $a['slug'])) . '</loc>' . $lastmod . $alts . "<changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
}
foreach ($prods as $p) {
    echo '  <url><loc>' . e($abs('produit/' . rawurlencode($p['slug']))) . "</loc><changefreq>weekly</changefreq><priority>0.6</priority></url>\n";
}
// Profils membres et sujets forum : contenu mince → volontairement EXCLUS du
// sitemap pour concentrer le budget de crawl sur les articles et les piliers
// (les profils passent aussi en noindex, cf. pages/profil.php).
foreach ($fcats as $slug) {
    echo '  <url><loc>' . e($abs('categorie/' . rawurlencode($slug))) . "</loc><changefreq>daily</changefreq><priority>0.5</priority></url>\n";
}

echo '</urlset>' . "\n";
