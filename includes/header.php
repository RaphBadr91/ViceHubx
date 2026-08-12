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
// Image de partage social (WhatsApp/Facebook/X). Chaque page peut définir la sienne
// (photo de l'article, du produit…) ; sinon on utilise la carte 1200×630 dédiée.
$OG_IMG_SIZED = !isset($SEO_OG_IMAGE); // dimensions connues (1200×630) seulement pour la carte par défaut
$SEO_OG_IMAGE = $SEO_OG_IMAGE ?? (
    is_file(ROOT_PATH . '/public/assets/img/brand/og-share.jpg')
        ? asset('img/brand/og-share.jpg')
        : (cdn_url('brand-cover.png') ?: cdn_url('aerial.png') ?: asset('img/og-default.svg'))
);
$BODY_CLASS   = $BODY_CLASS   ?? '';

// Base absolue du site (pour canonical, og:url, hreflang, JSON-LD)
$__scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_base  = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $__scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$path_only  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
// Canonical : on PRÉSERVE les paramètres identifiants (slug, id, cat, theme, u…)
// et on retire seulement le bruit (tracking, langue) — sinon tous les articles
// ?slug=… partageraient la même URL canonique (catastrophe d'indexation).
$__noise = ['lang', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
    'gclid', 'fbclid', 'gbraid', 'wbraid', 'msclkid', 'ref', 'mc_cid', 'mc_eid', 'igshid'];
parse_str((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY), $__q);
foreach ($__noise as $__k) { unset($__q[$__k]); }
$__qs       = $__q ? '?' . http_build_query($__q) : '';
$canon_path = $path_only . $__qs;
$canonical  = $site_base . $canon_path;
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
        ['gta6-pc.php',     lang() === 'fr' ? '🖥️ GTA 6 sur PC' : '🖥️ GTA 6 on PC'],
        ['gta6-vs-gta5.php', lang() === 'fr' ? '⚔️ GTA 6 vs GTA 5' : '⚔️ GTA 6 vs GTA 5'],
        ['dossier.php',     lang() === 'fr' ? 'Le Dossier' : 'The Files'],
        ['map.php',         t('nav_map')],
        ['vehicles.php',    t('nav_vehicles')],
        ['characters.php',  t('nav_characters')],
        ['bawsaq.php',      '📈 BAWSAQ'],
        ['evenements.php',  lang() === 'fr' ? 'Événements' : 'Events'],
    ]],
    ['label' => t('nav_community'), 'children' => [
        ['vice-persona.php', lang() === 'fr' ? '🌴 Ton perso Vice City' : '🌴 Your Vice City persona'],
        ['forum.php',      'Forum'],
        ['classement.php', lang() === 'fr' ? 'Classement' : 'Leaderboard'],
        ['galerie.php',    lang() === 'fr' ? 'Fan-arts' : 'Fan-arts'],
        ['quiz.php',       'Quiz'],
        ['cheats.php',     lang() === 'fr' ? 'Codes de triche' : 'Cheat codes'],
        ['community.php',  lang() === 'fr' ? 'Sondages & débats' : 'Polls & debates'],
    ]],
    ['label' => t('nav_shop'), 'children' => [
        ['shop.php',  lang() === 'fr' ? 'La Boutique' : 'The Shop'],
        ['fonds-ecran-gta6.php', lang() === 'fr' ? '🖥️ Fonds d’écran' : '🖥️ Wallpapers'],
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
    <?php // Par défaut : indexable + grande vignette (éligibilité Google Discover / Top Stories).
          //           Les pages privées gardent leur override $ROBOTS (noindex…). ?>
    <meta name="robots" content="<?= e($ROBOTS ?: 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1') ?>">
    <meta name="theme-color" content="#0a0a16">
    <link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= e(asset('img/icon-192.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(cdn_url('brand-profile.png') ?: asset('img/apple-touch-icon.png')) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="ViceHub X">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <!-- Versions linguistiques (hreflang) — UNIQUEMENT des paires RÉELLES (ex. article
         FR ↔ VO anglaise), chacune auto-canonique. On n'émet plus d'alternates ?lang=
         factices : leur cible se canonicalisait ailleurs → Google ignorait tout le bloc.
         x-default = anglais en priorité (marché dominant), repli français. -->
    <?php if (!empty($HREFLANG_ALT) && is_array($HREFLANG_ALT)): ?>
        <?php foreach ($HREFLANG_ALT as $hl => $hurl): ?>
    <link rel="alternate" hreflang="<?= e($hl) ?>" href="<?= e($hurl) ?>">
        <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e($HREFLANG_ALT['en'] ?? $HREFLANG_ALT['fr'] ?? reset($HREFLANG_ALT)) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="<?= e($OG_TYPE ?? 'website') ?>">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <?php if (!empty($OG_PUBLISHED)): ?><meta property="article:published_time" content="<?= e($OG_PUBLISHED) ?>"><?php endif; ?>
    <?php if (!empty($OG_SECTION)): ?><meta property="article:section" content="<?= e($OG_SECTION) ?>"><?php endif; ?>
    <meta property="og:title" content="<?= e($SEO_TITLE) ?>">
    <meta property="og:description" content="<?= e($SEO_DESC) ?>">
    <meta property="og:image" content="<?= e($og_image_abs) ?>">
    <meta property="og:image:secure_url" content="<?= e($og_image_abs) ?>">
    <?php if ($OG_IMG_SIZED): ?>
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>
    <meta property="og:image:alt" content="<?= e($SEO_TITLE) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:locale" content="<?= lang() === 'fr' ? 'fr_FR' : 'en_US' ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($SEO_TITLE) ?>">
    <meta name="twitter:description" content="<?= e($SEO_DESC) ?>">
    <meta name="twitter:image" content="<?= e($og_image_abs) ?>">

    <?php if ($gsv = trim((string) get_setting('google_site_verification', ''))): ?>
    <meta name="google-site-verification" content="<?= e($gsv) ?>">
    <?php endif; ?>
    <?php if ($bsv = trim((string) get_setting('bing_site_verification', ''))): ?>
    <meta name="msvalidate.01" content="<?= e($bsv) ?>">
    <?php endif; ?>
    <?php if ($ga = trim((string) get_setting('analytics_id', ''))): ?>
    <!-- Google Analytics 4 — chargé uniquement APRÈS acceptation des cookies (RGPD) -->
    <script>(function(){try{if(localStorage.getItem('vhx_cookie')!=='accept')return;
    var id=<?= json_encode($ga) ?>;var s=document.createElement('script');s.async=true;
    s.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(id);document.head.appendChild(s);
    window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}window.gtag=gtag;
    gtag('js',new Date());gtag('config',id,{anonymize_ip:true});}catch(e){}})();</script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://d8j0ntlcm91z4.cloudfront.net" crossorigin>
    <link rel="dns-prefetch" href="https://d8j0ntlcm91z4.cloudfront.net">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">

    <?php if (!empty($JSONLD)): ?>
    <script type="application/ld+json"><?= json_encode($JSONLD, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
    <!-- Organisation + WebSite (SEO global + entité claire pour l'IA de Google) -->
    <?php
    // Entité « ViceHub X » : nom exact + variantes + profils sociaux officiels
    // (sameAs). C'est ce qui permet à Google de nous distinguer d'un homonyme
    // et de nous citer comme source dans l'Aperçu IA.
    $__org = [
        '@type'         => 'Organization',
        '@id'           => $site_base . '/#org',
        'name'          => APP_NAME,
        'alternateName' => ['ViceHubX', 'Vice Hub X', 'ViceHub X GTA6'],
        'url'           => $site_base . '/',
        'logo'          => $og_image_abs,
        'foundingDate'  => '2026',
        'knowsAbout'    => ['Grand Theft Auto VI', 'GTA 6', 'Vice City', 'Leonida', 'Rockstar Games'],
        'description'   => lang() === 'fr'
            ? 'ViceHub X est un média indépendant non officiel dédié à GTA VI (Grand Theft Auto VI) et Vice City : actualités, leaks, guides et communauté de fans.'
            : 'ViceHub X is an independent, unofficial media dedicated to GTA VI (Grand Theft Auto VI) and Vice City: news, leaks, guides and a fan community.',
        'disambiguatingDescription' => lang() === 'fr'
            ? 'Média officiel « ViceHub X » (vicehubx.com), à ne pas confondre avec d\'autres sites au nom proche. Non affilié à Rockstar Games ni Take-Two.'
            : 'Official "ViceHub X" media (vicehubx.com), not to be confused with other similarly named sites. Not affiliated with Rockstar Games or Take-Two.',
    ];
    $__sameAs = array_values(array_map(static fn ($p) => $p['url'], social_profiles()));
    if ($__sameAs) {
        $__org['sameAs'] = $__sameAs;
    }
    ?>
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => [
            $__org,
            [
                '@type' => 'WebSite',
                '@id'   => $site_base . '/#website',
                'name'  => APP_NAME,
                'alternateName' => 'ViceHubX',
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
<script>(function(){var b=document.querySelector('[data-loader-pct]'),id;if(b){var n=0;id=setInterval(function(){n+=Math.random()*12+7;if(n>=98){n=98;clearInterval(id);}b.textContent=Math.floor(n);},80);}
var done=false;function hide(){if(done)return;done=true;if(id)clearInterval(id);if(b)b.textContent=100;var l=document.querySelector('.vh-loader');if(l)l.classList.add('hidden');}
/* Le loader part dès que la page est INTERACTIVE (DOM prêt), SANS attendre la vidéo
   (qui se charge en arrière-plan et se révèle quand elle joue). */
if(document.readyState!=='loading'){setTimeout(hide,450);}
else{document.addEventListener('DOMContentLoaded',function(){setTimeout(hide,450);});}
window.addEventListener('load',hide);
setTimeout(hide,2200);})();</script>

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

            <!-- Extras réservés au menu mobile (langue + compte) : sur ordinateur
                 ils restent dans la barre du haut ; sur mobile ils passent ici pour
                 que la barre ne déborde jamais. -->
            <div class="nav-mobile-extra">
                <div class="nav-mobile-lang" role="group" aria-label="<?= lang() === 'fr' ? 'Langue' : 'Language' ?>">
                    <?php foreach (available_languages() as $lc => $llabel): ?>
                        <a href="<?= e(lang_url($lc)) ?>"<?= lang() === $lc ? ' class="is-current"' : '' ?>><?= e($llabel) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php if (is_logged_in()): ?>
                    <a class="nav-mobile-account" href="<?= e(with_lang(url('pages/account.php'))) ?>">👤 <?= e(mb_strimwidth(display_name(), 0, 18, '…')) ?></a>
                <?php else: ?>
                    <a class="nav-mobile-account" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? '👤 Connexion' : '👤 Login' ?></a>
                <?php endif; ?>
            </div>
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
