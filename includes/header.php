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
$SEO_OG_IMAGE = $SEO_OG_IMAGE ?? asset('img/og-default.svg');
$BODY_CLASS   = $BODY_CLASS   ?? '';

$nav = [
    ['news.php',       t('nav_news')],
    ['guides.php',     t('nav_guides')],
    ['leaks-lab.php',  t('nav_leaks')],
    ['trailer-lab.php',t('nav_trailer')],
    ['map.php',        t('nav_map')],
    ['vehicles.php',   t('nav_vehicles')],
    ['characters.php', t('nav_characters')],
    ['community.php',  t('nav_community')],
    ['deals.php',      t('nav_deals')],
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
    <meta name="theme-color" content="#0a0a16">
    <link rel="canonical" href="<?= e($current_uri) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <meta property="og:title" content="<?= e($SEO_TITLE) ?>">
    <meta property="og:description" content="<?= e($SEO_DESC) ?>">
    <meta property="og:image" content="<?= e($SEO_OG_IMAGE) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">

    <?php if (!empty($JSONLD)): ?>
    <script type="application/ld+json"><?= json_encode($JSONLD, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>

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
            <?php foreach ($nav as [$href, $label]): ?>
                <a href="<?= e(with_lang(url('pages/' . $href))) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <a class="lang-switch" href="<?= e($current_uri . '?lang=' . $other_lang) ?>"><?= e(t('lang_switch')) ?></a>
            <a class="btn btn--ghost" href="<?= e(with_lang(url('admin/login.php'))) ?>"><?= e(t('nav_admin')) ?></a>
        </div>
    </div>
</header>

<main id="main" class="<?= $BODY_CLASS === 'is-home' ? '' : 'page-main' ?>">
