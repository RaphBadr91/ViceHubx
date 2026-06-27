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
$SEO_OG_IMAGE = $SEO_OG_IMAGE ?? (cdn_url('brand-cover.png') ?: cdn_url('aerial.png') ?: asset('img/og-default.svg'));
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
        ['gta6.php',        lang() === 'fr' ? '🎮 GTA 6 : tout savoir' : '🎮 GTA 6: everything'],
        ['gta6-vs-gta5.php', lang() === 'fr' ? '⚔️ GTA 6 vs GTA 5' : '⚔️ GTA 6 vs GTA 5'],
        ['dossier.php',     lang() === 'fr' ? 'Le Dossier' : 'The Files'],
        ['map.php',         t('nav_map')],
        ['vehicles.php',    t('nav_vehicles')],
        ['characters.php',  t('nav_characters')],
        ['bawsaq.php',      '📈 BAWSAQ'],
        ['evenements.php',  lang() === 'fr' ? 'Événements' : 'Events'],
    ]],
    ['label' => t('nav_community'), 'children' => [
        ['forum.php',      'Forum'],
        ['classement.php', lang() === 'fr' ? 'Classement' : 'Leaderboard'],
        ['galerie.php',    lang() === 'fr' ? 'Fan-arts' : 'Fan-arts'],
        ['quiz.php',       'Quiz'],
        ['cheats.php',     lang() === 'fr' ? 'Codes de triche' : 'Cheat codes'],
        ['community.php',  lang() === 'fr' ? 'Sondages & débats' : 'Polls & debates'],
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
    <link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= e(asset('img/icon-192.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(cdn_url('brand-profile.png') ?: asset('img/apple-touch-icon.png')) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="ViceHub X">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <!-- Versions linguistiques (hreflang) -->
    <?php foreach (array_keys(available_languages()) as $hl): ?>
    <link rel="alternate" hreflang="<?= e($hl) ?>" href="<?= e($site_base . $path_only . ($hl === 'fr' ? '' : '?lang=' . $hl)) ?>">
    <?php endforeach; ?>
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
    <link rel="preconnect" href="https://d8j0ntlcm91z4.cloudfront.net" crossorigin>
    <link rel="dns-prefetch" href="https://d8j0ntlcm91z4.cloudfront.net">
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
                    'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $site_base . '/pages/recherche.php?q={search_term_string}'],
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
<?php
$__tips = lang() === 'fr' ? [
    'Vice City n’a jamais dormi… et toi non plus ce soir.',
    'Astuce : tape « vicecity » au clavier pour un petit bonus 🌴',
    'Le soleil de Leonida se couche en néon rose et cyan.',
    'Garde un œil sur ton niveau de recherche ⭐ en bas de l’écran.',
    'Branche Vice FM 📻 et roule au rythme de la synthwave.',
    'Jason & Lucia : deux destins, une seule cavale.',
    'Le plus gros open-world de l’histoire de Rockstar t’attend.',
    'Conseil de pro : la pluie change tout sur la route.',
    'Les meilleurs leaks sont vérifiés avant d’être publiés ici.',
    'Bienvenue dans le QG des passionnés de GTA VI.',
] : [
    'Vice City never sleeps… and neither will you tonight.',
    'Tip: type “vicecity” on your keyboard for a little bonus 🌴',
    'Leonida’s sun sets in pink and cyan neon.',
    'Keep an eye on your wanted level ⭐ at the bottom of the screen.',
    'Tune in to Vice FM 📻 and cruise to the synthwave.',
    'Jason & Lucia: two fates, one getaway.',
    'The biggest open world in Rockstar history awaits.',
    'Pro tip: rain changes everything on the road.',
    'The best leaks are verified before they’re posted here.',
    'Welcome to the home of GTA VI fans.',
];
$__tip = $__tips[array_rand($__tips)];
?>
<div class="vh-loader" aria-hidden="true">
    <div class="vh-loader__scene" aria-hidden="true">
        <span class="vh-loader__sun"></span>
        <span class="vh-loader__sea"></span>
        <span class="vh-loader__skyline"></span>
        <span class="vh-loader__palm vh-loader__palm--l">🌴</span>
        <span class="vh-loader__palm vh-loader__palm--r">🌴</span>
    </div>
    <div class="vh-loader__logo">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span></div>
    <div class="vh-loader__sub">VICE CITY · LEONIDA</div>
    <div class="vh-loader__bar"><i></i></div>
    <div class="vh-loader__pct"><b data-loader-pct>0</b>% — <?= lang() === 'fr' ? 'Chargement de Vice City' : 'Loading Vice City' ?>&hellip;</div>
    <div class="vh-loader__tip">💡 <?= e($__tip) ?></div>
</div>
<script>(function(){var b=document.querySelector('[data-loader-pct]');if(!b)return;var n=0,id=setInterval(function(){n+=Math.random()*9+4;if(n>=98){n=98;clearInterval(id);}b.textContent=Math.floor(n);},95);window.addEventListener('load',function(){clearInterval(id);b.textContent=100;});})();</script>

<!-- Calques cinéma -->
<div class="fx-cursor" aria-hidden="true"></div>
<div class="fx-grain" aria-hidden="true"></div>
<div class="fx-scan" aria-hidden="true"></div>
<div class="fx-vignette" aria-hidden="true"></div>

<header class="site-header glass" id="top">
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
            <a class="cart-link" href="<?= e(with_lang(url('pages/recherche.php'))) ?>" aria-label="<?= lang() === 'fr' ? 'Rechercher' : 'Search' ?>">🔍</a>
            <a class="cart-link" href="<?= e(with_lang(url('pages/cart.php'))) ?>" aria-label="<?= e(t('cart_title')) ?>">
                🛒<?php $cc = cart_count(); if ($cc > 0): ?><span class="cart-badge"><?= $cc ?></span><?php endif; ?>
            </a>
            <?php if (is_logged_in()): $unread = unread_count((int) current_user()['id']); $umsg = unread_messages_count((int) current_user()['id']); ?>
            <a class="cart-link notif-link" href="<?= e(with_lang(url('pages/messages.php'))) ?>" aria-label="Messages">
                💌<?php if ($umsg > 0): ?><span class="cart-badge"><?= $umsg > 9 ? '9+' : $umsg ?></span><?php endif; ?>
            </a>
            <a class="cart-link notif-link" href="<?= e(with_lang(url('pages/notifications.php'))) ?>" aria-label="Notifications">
                🔔<?php if ($unread > 0): ?><span class="cart-badge"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
            </a>
            <?php endif; ?>
            <select class="lang-switch" onchange="if(this.value)location.href=this.value" aria-label="Langue / Language">
                <?php foreach (available_languages() as $lc => $llabel): ?>
                    <option value="<?= e(lang_url($lc)) ?>"<?= lang() === $lc ? ' selected' : '' ?>><?= e($llabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (is_logged_in()): ?>
                <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/account.php'))) ?>">👤 <?= e(mb_strimwidth(display_name(), 0, 14, '…')) ?></a>
            <?php else: ?>
                <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? 'Connexion' : 'Login' ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="main" class="<?= $BODY_CLASS === 'is-home' ? '' : 'page-main' ?>">
