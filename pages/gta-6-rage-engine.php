<?php
/**
 * Pilier d'AUTORITÉ (informationnel) — « Quel moteur pour GTA 6 ? RAGE Engine ».
 * Cible un cluster de requêtes où l'on a déjà des impressions : "what engine is
 * gta 6 using", "gta 6 rage engine", "gta 6 engine", "gta 6 graphics engine".
 * Bilingue FR/EN. Spoke du hub gta6.php.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta-6-rage-engine';
$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$modDate = date('Y-m-d');

$SEO_TITLE = $fr ? 'Quel moteur pour GTA 6 ? Le RAGE Engine expliqué (2026)' : 'What Engine Does GTA 6 Use? RAGE Engine Explained (2026)';
$SEO_DESC  = $fr
    ? 'GTA 6 tourne sur le RAGE Engine de Rockstar. On explique le moteur graphique : ray tracing, physique, performances sur PS5 et Xbox, et pourquoi il repousse les limites.'
    : 'GTA 6 runs on Rockstar\'s RAGE Engine. We break down the graphics engine: ray tracing, physics, performance on PS5 and Xbox, and why it pushes the limits.';
$SEO_OG_IMAGE = cdn_url('downtown.png') ?: ($base . '/public/assets/img/brand/og-share.jpg');

$faq = $fr ? [
    ['Quel moteur utilise GTA 6 ?', 'GTA 6 utilise le RAGE (Rockstar Advanced Game Engine), le moteur maison de Rockstar, dans une version fortement améliorée par rapport à GTA V et Red Dead Redemption 2.'],
    ['Le RAGE Engine gère-t-il le ray tracing ?', 'Oui. GTA 6 pousse l\'éclairage et les reflets en temps réel bien plus loin que GTA V, avec une illumination globale plus réaliste, visible sur l\'eau, les vitrines et les carrosseries.'],
    ['Quelles performances sur PS5 et Xbox ?', 'Le jeu vise la 4K sur PS5 et Xbox Series X (la Series S en retrait). Un mode performance (fluidité accrue) est probable, comme sur les précédents jeux Rockstar.'],
    ['GTA 6 utilise-t-il l\'Unreal Engine ?', 'Non. Comme tous les grands jeux Rockstar, GTA 6 s\'appuie sur le moteur propriétaire RAGE, pas sur l\'Unreal Engine.'],
] : [
    ['What engine does GTA 6 use?', 'GTA 6 uses RAGE (Rockstar Advanced Game Engine), Rockstar\'s in-house engine, in a heavily upgraded version compared to GTA V and Red Dead Redemption 2.'],
    ['Does the RAGE Engine handle ray tracing?', 'Yes. GTA 6 pushes real-time lighting and reflections far beyond GTA V, with more realistic global illumination, visible on water, shop windows and car bodies.'],
    ['What performance on PS5 and Xbox?', 'The game targets 4K on PS5 and Xbox Series X (Series S lower). A performance mode (higher smoothness) is likely, as with previous Rockstar games.'],
    ['Does GTA 6 use Unreal Engine?', 'No. Like all major Rockstar games, GTA 6 is built on the proprietary RAGE engine, not Unreal Engine.'],
];
$JSONLD = ['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'FAQPage', '@id' => $self . '#faq', 'mainEntity' => array_map(static fn ($qa) => ['@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]]], $faq)],
    ['@type' => 'Article', '@id' => $self . '#article', 'headline' => $SEO_TITLE, 'description' => $SEO_DESC, 'inLanguage' => lang(), 'datePublished' => '2026-10-22', 'dateModified' => $modDate, 'author' => ['@id' => $base . '/#org'], 'publisher' => ['@id' => $base . '/#org'], 'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self]],
]];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:860px">
    <span class="eyebrow"><?= vhx_icon('flask') ?> GTA 6 · <?= $fr ? 'Moteur graphique' : 'Graphics engine' ?></span>
    <h1><?= $fr ? 'Quel moteur pour GTA 6 ? Le RAGE Engine' : 'What Engine Does GTA 6 Use? RAGE Engine' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>
    <div class="lore-block glass" style="margin:1.1rem 0 1.4rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.12rem;margin:0"><strong><?= $fr
            ? 'Réponse courte : GTA 6 tourne sur le RAGE Engine (Rockstar Advanced Game Engine), le moteur maison de Rockstar, dans une version bien plus avancée que GTA V et RDR2 — ray tracing, physique et illumination globale poussés pour la nouvelle génération.'
            : 'Short answer: GTA 6 runs on the RAGE Engine (Rockstar Advanced Game Engine), Rockstar\'s in-house engine, in a far more advanced version than GTA V and RDR2 — ray tracing, physics and global illumination pushed for the new generation.' ?></strong></p>
    </div>
    <h2><?= $fr ? 'RAGE : le moteur maison de Rockstar' : 'RAGE: Rockstar\'s in-house engine' ?></h2>
    <p class="muted"><?= $fr
        ? 'Le RAGE (Rockstar Advanced Game Engine) propulse les jeux Rockstar depuis GTA IV. Chaque jeu l\'améliore : GTA 6 représente son plus grand bond technique, avec une ville de Leonida dense, une météo dynamique et une foule crédible.'
        : 'RAGE (Rockstar Advanced Game Engine) has powered Rockstar\'s games since GTA IV. Each game improves it: GTA 6 is its biggest technical leap yet, with a dense Leonida, dynamic weather and believable crowds.' ?></p>
    <h2><?= $fr ? 'Ray tracing & illumination nouvelle génération' : 'Next-gen ray tracing & lighting' ?></h2>
    <p class="muted"><?= $fr
        ? 'Là où GTA V proposait un ray tracing partiel, GTA 6 généralise les reflets en temps réel : sur l\'eau de la baie, les vitrines du centre-ville, le tarmac. Les néons de Vice City gagnent énormément en réalisme la nuit.'
        : 'Where GTA V offered partial ray tracing, GTA 6 generalizes real-time reflections: on the bay\'s water, downtown windows, the tarmac. Vice City\'s neon gains huge realism at night.' ?></p>
    <h2><?= $fr ? 'Physique & performances PS5 / Xbox' : 'Physics & PS5 / Xbox performance' ?></h2>
    <p class="muted"><?= $fr
        ? 'Conduite, dégâts, tissus, eau : la physique du RAGE est l\'une des plus poussées du jeu vidéo. Côté performances, GTA 6 vise la 4K sur PS5 et Xbox Series X, avec un probable mode performance pour plus de fluidité.'
        : 'Driving, damage, cloth, water: RAGE\'s physics is among the most advanced in gaming. On performance, GTA 6 targets 4K on PS5 and Xbox Series X, with a likely performance mode for more smoothness.' ?>
        <a href="<?= e(with_lang(url('pages/gta6-pc.php'))) ?>"><?= $fr ? 'Et la version PC ?' : 'What about PC?' ?></a></p>

    <?= render_deal_picks(['Console', 'Premium', 'Écran'], 3) ?>

    <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem"><h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3><p class="muted" style="margin:0"><?= e($qa[1]) ?></p></div>
    <?php endforeach; ?>
    <p class="muted" style="margin-top:1.4rem"><?= $fr ? 'À lire aussi :' : 'See also:' ?>
        <a href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'dossier GTA 6' : 'GTA 6 hub' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta6-vs-gta5.php'))) ?>">GTA 6 vs GTA 5</a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-date-de-sortie.php'))) ?>"><?= $fr ? 'date de sortie' : 'release date' ?></a></p>
    <p class="muted" style="margin-top:1.6rem;font-size:.85rem"><?= $fr ? 'ViceHub X est un média indépendant non officiel. GTA, Grand Theft Auto et Vice City sont des marques de Rockstar Games / Take-Two.' : 'ViceHub X is an independent, unofficial media. GTA, Grand Theft Auto and Vice City are trademarks of Rockstar Games / Take-Two.' ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
