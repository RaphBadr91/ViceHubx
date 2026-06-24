<?php
/**
 * ViceHub X — En-tête commun (SEO + navigation immersive).
 * Variables optionnelles à définir avant l'inclusion :
 *   $SEO_TITLE, $SEO_DESC, $SEO_OG_IMAGE, $JSONLD (array), $BODY_CLASS
 */
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$SEO_TITLE    = $SEO_TITLE    ?? (APP_NAME . ' — ' . (lang() === 'fr' ? APP_SLOGAN_FR : APP_SLOGAN_EN));
$SEO_DESC     = $SEO_DESC     ?? (lang() === 'fr'
    ? 'ViceHub X : news, guides, leaks et analyses de trailers GTA VI dans une interface immersive Vice City OS.'
    : 'ViceHub X: GTA VI news, guides, leaks and trailer analysis in an immersive Vice City OS interface.');
$SEO_OG_IMAGE = $SEO_OG_IMAGE ?? (cdn_url('aerial.png') ?: asset('img/og-default.svg'));
$BODY_CLASS   = $BODY_CLASS   ?? '';

// Base absolue du site (pour canonical, og:url, hreflang, JSON-LD)
$__scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_base  = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $__scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$path_only  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonical  = $site_base . $path_only;
$og_image_abs = (function ($img) use ($site_base) {
    return preg_match('#^https?://#', (string) $img) ? $img : $site_base . '/' . ltrim((string) $img, '/');
})($SEO_OG_IMAGE);

$nav = [
    ['label' => lang() === 'fr' ? 'Actus' : 'News', 'children' => [
        ['news.php',        t('nav_news')],
        ['blog.php',        'Blog'],
        ['guides.php',      t('nav_guides')],
        ['leaks-lab.php',   t('nav_leaks')],
        ['trailer-lab.php', t('nav_trailer')],
    ]],
    ['label' => lang() === 'fr' ? 'Univers' : 'Universe', 'children' => [
        ['dossier.php',    lang() === 'fr' ? 'Le Dossier' : 'The Files'],
        ['map.php',        t('nav_map')],
        ['vehicles.php',   t('nav_vehicles')],
        ['characters.php', t('nav_characters')],
    ]],
    ['label' => t('nav_community'), 'children' => [
        ['forum.php',     'Forum'],
        ['quiz.php',      'Quiz'],
        ['community.php', lang() === 'fr' ? 'Sondages & débats' : 'Polls & debates'],
    ]],
    ['label' => t('nav_shop'), 'children' => [
        ['shop.php',  lang() === 'fr' ? 'La Boutique' : 'The Shop'],
        ['deals.php', t('nav_deals')],
    ]],
];

// Construit l'URL du switch de langue en conservant la page courante
$other_lang  = lang() === 'fr' ? 'en' : 'fr';
$current_uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
?>
<!DOCTYPE html>
<html lang="<?= e(lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($SEO_TITLE) ?></title>
    <meta name="description" content="<?= e($SEO_DESC) ?>">
    <?php if (!empty($ROBOTS)): ?><meta name="robots" content="<?= e($ROBOTS) ?>"><?php endif; ?>
    <meta name="theme-color" content="#0a0a16">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <!-- Versions linguistiques (hreflang) -->
    <link rel="alternate" hreflang="fr" href="<?= e($site_base . $path_only . '?lang=fr') ?>">
    <link rel="alternate" hreflang="en" href="<?= e($site_base . $path_only . '?lang=en') ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e($site_base . $path_only) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <meta property="og:title" content="<?= e($SEO_TITLE) ?>">
    <meta property="og:description" content="<?= e($SEO_DESC) ?>">
    <meta property="og:image" content="<?= e($og_image_abs) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:locale" content="<?= lang() === 'fr' ? 'fr_FR' : 'en_US' ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">

    <?php if (!empty($JSONLD)): ?>
    <script type="application/ld+json"><?= json_encode($JSONLD, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
    <!-- Organisation + WebSite (SEO global) -->
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type' => 'Organization',
                '@id'   => $site_base . '/#org',
                'name'  => APP_NAME,
                'url'   => $site_base . '/',
                'logo'  => $og_image_abs,
                'description' => lang() === 'fr'
                    ? 'Média indépendant non officiel dédié à GTA VI et Vice City.'
                    : 'Independent unofficial media about GTA VI and Vice City.',
            ],
            [
                '@type' => 'WebSite',
                '@id'   => $site_base . '/#website',
                'name'  => APP_NAME,
                'url'   => $site_base . '/',
                'inLanguage' => lang(),
                'publisher'  => ['@id' => $site_base . '/#org'],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $site_base . '/pages/news.php?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

    <?php if ($ac = adsense_client()): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e($ac) ?>"
            crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="<?= e($BODY_CLASS) ?>">
<a class="skip-link" href="#main"><?= lang() === 'fr' ? 'Aller au contenu' : 'Skip to content' ?></a>

<!-- Écran de chargement -->
<div class="vh-loader" aria-hidden="true">
    <div class="vh-loader__logo">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span></div>
    <div class="vh-loader__bar"><i></i></div>
    <div class="vh-loader__tag">Loading Vice City&hellip;</div>
</div>

<!-- Calques cinéma -->
<div class="fx-cursor" aria-hidden="true"></div>
<div class="fx-grain" aria-hidden="true"></div>
<div class="fx-scan" aria-hidden="true"></div>
<div class="fx-vignette" aria-hidden="true"></div>

<header class="site-header glass">
    <div class="header-inner">
        <a class="logo" href="<?= e(with_lang(url('index.php'))) ?>">
            Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span>
        </a>

        <button class="nav-toggle" aria-label="Menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav" aria-label="Navigation principale">
            <?php foreach ($nav as $item): ?>
                <?php if (isset($item['children'])): ?>
                    <div class="nav-group">
                        <button class="nav-group__btn" type="button" aria-expanded="false" aria-haspopup="true">
                            <?= e($item['label']) ?> <span class="nav-caret" aria-hidden="true">▾</span>
                        </button>
                        <div class="nav-dropdown">
                            <?php foreach ($item['children'] as [$href, $label]): ?>
                                <a href="<?= e(with_lang(url('pages/' . $href))) ?>"><?= e($label) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="nav-link" href="<?= e(with_lang(url('pages/' . $item['href']))) ?>"><?= e($item['label']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <a class="cart-link" href="<?= e(with_lang(url('pages/cart.php'))) ?>" aria-label="<?= e(t('cart_title')) ?>">
                🛒<?php $cc = cart_count(); if ($cc > 0): ?><span class="cart-badge"><?= $cc ?></span><?php endif; ?>
            </a>
            <a class="lang-switch" href="<?= e($current_uri . '?lang=' . $other_lang) ?>"><?= e(t('lang_switch')) ?></a>
            <?php if (is_logged_in()): ?>
                <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/account.php'))) ?>">👤 <?= e(mb_strimwidth(display_name(), 0, 14, '…')) ?></a>
            <?php else: ?>
                <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? 'Connexion' : 'Login' ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="main" class="<?= $BODY_CLASS === 'is-home' ? '' : 'page-main' ?>">
