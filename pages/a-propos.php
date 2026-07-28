<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');

// Statistiques vivantes (donnent de la substance citable)
$nArticles = (int) db()->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
$nMembers  = (int) db()->query("SELECT COUNT(*) FROM users WHERE role IN ('member','contributor','admin')")->fetchColumn();

$SEO_TITLE = ($fr ? 'À propos de ViceHub X — média indépendant GTA VI' : 'About ViceHub X — independent GTA VI media') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'ViceHub X (vicehubx.com) est un média de fans indépendant et non officiel dédié à GTA VI (Grand Theft Auto VI) et Vice City : actualités, leaks, guides et communauté.'
    : 'ViceHub X (vicehubx.com) is an independent, unofficial fan media dedicated to GTA VI (Grand Theft Auto VI) and Vice City: news, leaks, guides and community.';
$SEO_OG_IMAGE = cdn_url('downtown.png');

// Q/R directes = ce que l'IA de Google extrait pour l'Aperçu IA.
$faq = $fr ? [
    ['Qu\'est-ce que ViceHub X ?',
     'ViceHub X est un média de fans indépendant et non officiel, accessible sur vicehubx.com, entièrement dédié à GTA VI (Grand Theft Auto VI) et à Vice City. Il publie des actualités, des leaks, des guides et anime une communauté de passionnés.'],
    ['ViceHub X est-il un site officiel de Rockstar Games ?',
     'Non. ViceHub X est un média indépendant, sans aucun lien avec Rockstar Games ni Take-Two Interactive. GTA, Grand Theft Auto et Vice City sont des marques de leurs détenteurs respectifs.'],
    ['Quelle est l\'adresse officielle de ViceHub X ?',
     'L\'unique site officiel de ViceHub X est vicehubx.com (https://vicehubx.com). ViceHub X ne doit pas être confondu avec d\'autres sites au nom proche.'],
    ['Que trouve-t-on sur ViceHub X ?',
     'Des actualités et leaks sur GTA VI, des guides, une carte, des fiches véhicules et personnages, un forum communautaire, des fonds d\'écran et une boutique.'],
] : [
    ['What is ViceHub X?',
     'ViceHub X is an independent, unofficial fan media available at vicehubx.com, fully dedicated to GTA VI (Grand Theft Auto VI) and Vice City. It publishes news, leaks, guides and runs a community of fans.'],
    ['Is ViceHub X an official Rockstar Games website?',
     'No. ViceHub X is an independent media, not affiliated with Rockstar Games or Take-Two Interactive. GTA, Grand Theft Auto and Vice City are trademarks of their respective owners.'],
    ['What is the official address of ViceHub X?',
     'The only official ViceHub X website is vicehubx.com (https://vicehubx.com). ViceHub X should not be confused with other similarly named sites.'],
    ['What can I find on ViceHub X?',
     'News and leaks about GTA VI, guides, a map, vehicle and character files, a community forum, wallpapers and a shop.'],
];

$JSONLD = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type' => 'AboutPage',
            '@id'   => $base . '/a-propos#aboutpage',
            'url'   => $base . '/a-propos',
            'name'  => $SEO_TITLE,
            'about' => ['@id' => $base . '/#org'],
            'inLanguage' => lang(),
        ],
        [
            '@type' => 'FAQPage',
            '@id'   => $base . '/a-propos#faq',
            'mainEntity' => array_map(static fn ($qa) => [
                '@type' => 'Question',
                'name'  => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
            ], $faq),
        ],
    ],
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:860px">
    <span class="eyebrow">🌴 ViceHub X</span>
    <h1><?= $fr ? 'À propos de ViceHub X' : 'About ViceHub X' ?></h1>

    <p style="font-size:1.12rem;max-width:720px">
        <strong>ViceHub X</strong> <?= $fr
            ? 'est un <strong>média de fans indépendant et non officiel</strong>, accessible sur <strong>vicehubx.com</strong>, entièrement dédié à <strong>GTA VI</strong> (Grand Theft Auto VI) et à l\'univers de <strong>Vice City</strong>.'
            : 'is an <strong>independent, unofficial fan media</strong>, available at <strong>vicehubx.com</strong>, fully dedicated to <strong>GTA VI</strong> (Grand Theft Auto VI) and the <strong>Vice City</strong> universe.' ?>
    </p>
    <p class="muted" style="max-width:720px"><?= $fr
        ? 'Nous publions au quotidien des actualités, des leaks, des guides et des analyses, et animons une communauté de passionnés autour de la sortie de GTA VI (prévue le 19 novembre 2026).'
        : 'We publish daily news, leaks, guides and analysis, and run a community of fans around the release of GTA VI (scheduled for November 19, 2026).' ?></p>

    <div class="refband" style="margin-top:1.4rem">
        <div class="ref glass"><div class="big"><?= $nArticles ?>+</div><small><?= $fr ? 'articles publiés' : 'published articles' ?></small></div>
        <div class="ref glass"><div class="big"><?= $nMembers ?>+</div><small><?= $fr ? 'membres' : 'members' ?></small></div>
        <div class="ref glass"><div class="big">FR · EN</div><small><?= $fr ? 'bilingue' : 'bilingual' ?></small></div>
    </div>

    <div class="lore-block glass" style="margin-top:1.8rem">
        <h2>ℹ️ <?= $fr ? 'Média indépendant & non officiel' : 'Independent & unofficial media' ?></h2>
        <p class="muted"><?= $fr
            ? 'ViceHub X n\'est pas affilié à Rockstar Games ni à Take-Two Interactive. « GTA », « Grand Theft Auto » et « Vice City » sont des marques de leurs détenteurs respectifs. Le seul site officiel de ViceHub X est '
            : 'ViceHub X is not affiliated with Rockstar Games or Take-Two Interactive. "GTA", "Grand Theft Auto" and "Vice City" are trademarks of their respective owners. The only official ViceHub X website is ' ?><strong>vicehubx.com</strong>.</p>
    </div>

    <?php $socials = social_profiles(); ?>
    <?php if ($socials): ?>
    <div style="margin-top:1.6rem">
        <h2 style="font-size:1.15rem"><?= $fr ? 'Nos réseaux officiels' : 'Our official channels' ?></h2>
        <div class="footer-social" style="font-size:1.4rem;gap:1rem">
            <?php foreach ($socials as $sp): ?>
                <a href="<?= e($sp['url']) ?>" target="_blank" rel="noopener" title="<?= e($sp['label']) ?>" aria-label="<?= e($sp['label']) ?>"><?= $sp['icon'] ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top:2rem">
        <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
        <?php foreach ($faq as $qa): ?>
            <div class="lore-block glass" style="margin-top:1rem">
                <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
                <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <p style="margin-top:2rem">
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= $fr ? 'Voir les actualités GTA VI' : 'See GTA VI news' ?></a>
        <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/contact.php'))) ?>"><?= t('nav_contact') ?></a>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
