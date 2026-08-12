<?php
/**
 * Pilier SEO — « GTA 6 PC Release Date » (cluster enterré pos 52-80, fort volume).
 * Page evergreen, answer-first, FAQPage schema. Bilingue FR/EN.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta6-pc';

$SEO_TITLE = $fr
    ? 'Date de sortie GTA 6 sur PC : tout ce qu\'on sait (2026)'
    : 'GTA 6 PC Release Date: When Is It Coming? (2026)';
$SEO_DESC = $fr
    ? 'GTA 6 sort d\'abord sur PS5/Xbox le 19 nov 2026 — voici la date PC attendue, pourquoi Rockstar retarde la version PC, et tout ce qui est confirmé en 2026.'
    : 'GTA 6 hits PC after PS5/Xbox — here\'s the expected GTA 6 PC release date, why Rockstar delays PC, and everything confirmed as of 2026.';
$SEO_OG_IMAGE = cdn_url('downtown.png');

// FAQ (les vraies requêtes GSC) — reprises dans le schema ET affichées.
$faq = $fr ? [
    ['GTA 6 sortira-t-il sur PC ?', 'Oui, une version PC de GTA 6 est quasi certaine : tous les précédents GTA sont sortis sur PC. Mais à ce jour, Rockstar n\'a annoncé officiellement que les versions PS5 et Xbox Series X|S (19 novembre 2026). Aucune date PC officielle n\'a été communiquée.'],
    ['Quand GTA 6 sortira-t-il sur PC ?', 'Aucune date officielle. En se basant sur GTA V (sorti sur console en 2013, sur PC en 2015), la version PC de GTA 6 est généralement attendue autour de 2027, soit environ 6 à 18 mois après la sortie console du 19 novembre 2026. Tout le reste relève de la rumeur.'],
    ['« GTA 6 PC février 2027 » est-il confirmé ?', 'Non. Aucune date de février 2027 (ni aucune autre) n\'a été confirmée par Rockstar pour la version PC. Ce sont des estimations et des rumeurs de la communauté, à prendre avec prudence.'],
    ['GTA 6 sera-t-il sur Steam et Epic ?', 'Non confirmé. GTA V et les précédents titres sont finalement arrivés sur Steam, l\'Epic Games Store et le Rockstar Games Launcher. Il est probable que GTA 6 suive le même chemin, mais rien n\'est officiel.'],
] : [
    ['Is GTA 6 coming to PC?', 'Yes, a GTA 6 PC version is almost certain — every previous GTA has come to PC. But so far Rockstar has only officially announced the PS5 and Xbox Series X|S versions (November 19, 2026). No official PC date has been given.'],
    ['When will GTA 6 come to PC?', 'There is no official date. Based on GTA V (console 2013, PC 2015), the GTA 6 PC version is generally expected around 2027 — roughly 6 to 18 months after the November 19, 2026 console launch. Everything else is rumor.'],
    ['Is "GTA 6 PC February 2027" confirmed?', 'No. No February 2027 date (or any other) has been confirmed by Rockstar for the PC version. These are community estimates and rumors — treat them with caution.'],
    ['Will GTA 6 be on Steam and Epic?', 'Not confirmed. GTA V and earlier titles eventually reached Steam, the Epic Games Store and the Rockstar Games Launcher. GTA 6 will likely follow, but nothing is official yet.'],
];

$JSONLD = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type' => 'FAQPage',
            '@id'   => $self . '#faq',
            'mainEntity' => array_map(static fn ($qa) => [
                '@type' => 'Question', 'name' => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
            ], $faq),
        ],
        [
            '@type' => 'Article',
            '@id'   => $self . '#article',
            'headline' => $SEO_TITLE,
            'description' => $SEO_DESC,
            'inLanguage' => lang(),
            'author'    => ['@id' => $base . '/#org'],
            'publisher' => ['@id' => $base . '/#org'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self],
        ],
    ],
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow">🖥️ GTA 6 · PC</span>
    <h1><?= $fr ? 'Date de sortie de GTA 6 sur PC' : 'GTA 6 PC Release Date' ?></h1>

    <!-- Answer box : réponse directe en tête (vise le featured snippet / AI Overview) -->
    <div class="lore-block glass" style="margin:1rem 0 1.6rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.15rem;margin:0"><strong><?= $fr
            ? 'En résumé : GTA 6 sort le 19 novembre 2026 sur PS5 et Xbox Series X|S. Aucune date PC officielle n\'a été annoncée. En se basant sur GTA V, la version PC est attendue autour de 2027 — soit ~6 à 18 mois après la sortie console.'
            : 'Short answer: GTA 6 launches November 19, 2026 on PS5 and Xbox Series X|S. No official PC date has been announced. Based on GTA V, the PC version is expected around 2027 — roughly 6 to 18 months after the console launch.' ?></strong></p>
    </div>

    <h2><?= $fr ? 'Ce qui est confirmé' : 'What\'s confirmed' ?></h2>
    <ul>
        <li><?= $fr ? '<strong>Sortie console :</strong> 19 novembre 2026, sur PS5 et Xbox Series X|S.' : '<strong>Console release:</strong> November 19, 2026, on PS5 and Xbox Series X|S.' ?></li>
        <li><?= $fr ? '<strong>Éditions :</strong> Standard (79,99 $) et Ultimate (99,99 $).' : '<strong>Editions:</strong> Standard ($79.99) and Ultimate ($99.99).' ?></li>
        <li><?= $fr ? '<strong>Version PC :</strong> non annoncée officiellement à ce jour.' : '<strong>PC version:</strong> not officially announced as of today.' ?></li>
    </ul>

    <h2><?= $fr ? 'Pourquoi Rockstar sort GTA 6 sur console d\'abord' : 'Why Rockstar releases GTA 6 on console first' ?></h2>
    <p class="muted"><?= $fr
        ? 'Rockstar a pour habitude de lancer ses jeux sur console, puis de porter une version PC optimisée plusieurs mois plus tard. GTA V est sorti sur PS3/Xbox 360 en septembre 2013, puis sur PC en avril 2015 (~18 mois après). Red Dead Redemption 2 : console en octobre 2018, PC en novembre 2019 (~13 mois). Ce décalage permet d\'optimiser le portage PC et d\'étaler les ventes.'
        : 'Rockstar typically launches on console first, then ships an optimized PC version months later. GTA V released on PS3/Xbox 360 in September 2013 and on PC in April 2015 (~18 months later). Red Dead Redemption 2: console in October 2018, PC in November 2019 (~13 months). This gap lets Rockstar optimize the PC port and stagger sales.' ?></p>

    <h2><?= $fr ? 'Chronologie console → PC (estimation)' : 'Console → PC timeline (estimate)' ?></h2>
    <div class="lore-block glass">
        <ul style="margin:0">
            <li><strong>19/11/2026</strong> — <?= $fr ? 'Sortie GTA 6 sur PS5 & Xbox Series X|S (confirmé).' : 'GTA 6 launches on PS5 & Xbox Series X|S (confirmed).' ?></li>
            <li><strong>2027</strong> — <?= $fr ? 'Fenêtre attendue pour la version PC (non confirmé, basé sur l\'historique Rockstar).' : 'Expected window for the PC version (unconfirmed, based on Rockstar\'s history).' ?></li>
        </ul>
    </div>

    <h2><?= $fr ? 'Comment ne rien rater de l\'annonce PC' : 'How to catch the PC announcement' ?></h2>
    <p class="muted"><?= $fr
        ? 'Dès que Rockstar officialise la version PC, on met cette page à jour et on publie une actu dédiée.'
        : 'The moment Rockstar makes the PC version official, we update this page and publish a dedicated news post.' ?>
        <a href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= $fr ? 'Voir toutes les actus GTA 6' : 'See all GTA 6 news' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'Le dossier GTA 6 complet' : 'The full GTA 6 hub' ?></a>.
    </p>

    <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem">
            <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
            <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
        </div>
    <?php endforeach; ?>

    <p class="muted" style="margin-top:1.6rem;font-size:.85rem"><?= $fr
        ? 'ViceHub X est un média indépendant non officiel. GTA, Grand Theft Auto et Vice City sont des marques de Rockstar Games / Take-Two. Page mise à jour régulièrement.'
        : 'ViceHub X is an independent, unofficial media. GTA, Grand Theft Auto and Vice City are trademarks of Rockstar Games / Take-Two. Page updated regularly.' ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
