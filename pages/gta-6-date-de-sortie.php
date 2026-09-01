<?php
/**
 * Pilier SEO à intention UNIQUE — « date de sortie GTA 6 / GTA 6 release date ».
 * Answer-first + compte à rebours (fraîcheur) + FAQPage schema. Bilingue FR/EN.
 * Spoke du hub gta6.php (liens réciproques → anti-cannibalisation).
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta-6-date-de-sortie';

// Chaque langue est auto-canonique + hreflang réciproques (les 2 langues s'indexent).
$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$pubDate = '2026-08-31';
$modDate = '2026-08-31'; // ← bumper à chaque mise à jour (signal de fraîcheur)

$deadline = release_date(); // 2026-11-19 (compte à rebours partagé, JS app.js)

$SEO_TITLE = $fr
    ? 'Date de sortie GTA 6 : quand sort le jeu ? (19 nov. 2026)'
    : 'GTA 6 Release Date: When Does It Come Out? (Nov 19, 2026)';
$SEO_DESC = $fr
    ? 'GTA 6 sort le 19 novembre 2026 sur PS5 et Xbox Series X|S. Compte à rebours en direct, ce qui est confirmé par Rockstar, la version PC et les rumeurs de report : tout est ici.'
    : 'GTA 6 launches November 19, 2026 on PS5 and Xbox Series X|S. Live countdown, everything Rockstar has confirmed, the PC version and delay rumors — it\'s all here.';
$SEO_OG_IMAGE = cdn_url('downtown.png') ?: ($base . '/public/assets/img/brand/og-share.jpg');

// FAQ ciblée « date » (vraies requêtes) — reprise dans le schema ET affichée.
$faq = $fr ? [
    ['Quelle est la date de sortie de GTA 6 ?', 'GTA 6 sort le 19 novembre 2026 sur PS5 et Xbox Series X|S, comme confirmé officiellement par Rockstar Games.'],
    ['GTA 6 va-t-il être repoussé ?', 'Rien n\'indique un report. Rockstar a confirmé la date du 19 novembre 2026 et a diffusé un long extrait de gameplay (« An Extended Look ») le 27 août 2026, signe d\'un développement en phase finale. Un report reste toujours possible tant que le jeu n\'est pas « gold », mais aucun n\'est annoncé à ce jour.'],
    ['Dans combien de temps sort GTA 6 ?', 'Le jeu sort le 19 novembre 2026. Le compte à rebours en haut de cette page affiche le temps restant exact, jour par jour, mis à jour en direct.'],
    ['GTA 6 sort-il aussi sur PC le 19 novembre 2026 ?', 'Non. Le 19 novembre 2026 concerne uniquement la PS5 et la Xbox Series X|S. La version PC n\'a pas de date officielle : d\'après l\'historique de Rockstar (GTA V, RDR2), elle est attendue autour de 2027.'],
    ['GTA 6 sort-il sur PS4 et Xbox One ?', 'Non. GTA 6 est prévu uniquement sur consoles nouvelle génération : PS5 et Xbox Series X|S. Aucune version PS4 ou Xbox One n\'est annoncée.'],
] : [
    ['What is the GTA 6 release date?', 'GTA 6 launches on November 19, 2026 on PS5 and Xbox Series X|S, as officially confirmed by Rockstar Games.'],
    ['Will GTA 6 be delayed?', 'There is no sign of a delay. Rockstar confirmed the November 19, 2026 date and released a long gameplay preview ("An Extended Look") on August 27, 2026 — a sign the game is in its final stretch. A delay is always possible until the game goes gold, but none has been announced.'],
    ['How long until GTA 6 comes out?', 'The game launches on November 19, 2026. The countdown at the top of this page shows the exact time remaining, day by day, updated live.'],
    ['Does GTA 6 come out on PC on November 19, 2026?', 'No. November 19, 2026 is for PS5 and Xbox Series X|S only. The PC version has no official date: based on Rockstar\'s history (GTA V, RDR2), it is expected around 2027.'],
    ['Is GTA 6 coming to PS4 and Xbox One?', 'No. GTA 6 is planned for new-generation consoles only: PS5 and Xbox Series X|S. No PS4 or Xbox One version has been announced.'],
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
            'datePublished' => $pubDate,
            'dateModified'  => $modDate,
            'author'    => ['@id' => $base . '/#org'],
            'publisher' => ['@id' => $base . '/#org'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self],
        ],
    ],
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow"><?= vhx_icon('calendar') ?> GTA 6 · <?= $fr ? 'Date de sortie' : 'Release date' ?></span>
    <h1><?= $fr ? 'Date de sortie de GTA 6' : 'GTA 6 Release Date' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>

    <!-- Compte à rebours (fraîcheur + engagement) — JS partagé app.js -->
    <div class="countdown" data-deadline="<?= e($deadline) ?>" role="timer" aria-label="<?= e(t('cd_title')) ?>" style="margin:1.2rem 0">
        <span class="cd-title"><?= e(t('cd_title')) ?></span>
        <div class="cd-tiles">
            <div class="cd-tile"><b data-cd="d">--</b><i><?= e(t('cd_days')) ?></i></div>
            <div class="cd-sep">:</div>
            <div class="cd-tile"><b data-cd="h">--</b><i><?= e(t('cd_hours')) ?></i></div>
            <div class="cd-sep">:</div>
            <div class="cd-tile"><b data-cd="m">--</b><i><?= e(t('cd_min')) ?></i></div>
            <div class="cd-sep">:</div>
            <div class="cd-tile"><b data-cd="s">--</b><i><?= e(t('cd_sec')) ?></i></div>
        </div>
        <span class="cd-done"><?= e(t('cd_released')) ?></span>
    </div>

    <!-- Answer box : réponse directe (vise le featured snippet / AI Overview) -->
    <div class="lore-block glass" style="margin:1rem 0 1.6rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.15rem;margin:0"><strong><?= $fr
            ? 'En résumé : GTA 6 sort le 19 novembre 2026 sur PS5 et Xbox Series X|S. C\'est la date officielle confirmée par Rockstar Games. La version PC, elle, n\'a pas encore de date (attendue autour de 2027).'
            : 'Short answer: GTA 6 launches November 19, 2026 on PS5 and Xbox Series X|S. That\'s the official date confirmed by Rockstar Games. The PC version has no date yet (expected around 2027).' ?></strong></p>
    </div>

    <h2><?= $fr ? 'Ce qui est confirmé' : 'What\'s confirmed' ?></h2>
    <ul>
        <li><?= $fr ? '<strong>Date de sortie :</strong> 19 novembre 2026.' : '<strong>Release date:</strong> November 19, 2026.' ?></li>
        <li><?= $fr ? '<strong>Plateformes :</strong> PS5 et Xbox Series X|S (nouvelle génération uniquement).' : '<strong>Platforms:</strong> PS5 and Xbox Series X|S (new-gen only).' ?></li>
        <li><?= $fr ? '<strong>Personnages :</strong> Jason Duval et Lucia Caminos, à Leonida (Vice City).' : '<strong>Characters:</strong> Jason Duval and Lucia Caminos, in Leonida (Vice City).' ?></li>
        <li><?= $fr ? '<strong>Prix &amp; éditions :</strong> Standard à 79,99 € et Ultimate à 99,99 €.' : '<strong>Price &amp; editions:</strong> Standard €79.99 and Ultimate €99.99.' ?> <a href="<?= e(with_lang(url('pages/gta-6-prix-editions.php'))) ?>"><?= $fr ? 'Voir le détail des éditions' : 'See edition details' ?></a>.</li>
        <li><?= $fr ? '<strong>Version PC :</strong> non annoncée à ce jour.' : '<strong>PC version:</strong> not announced yet.' ?> <a href="<?= e(with_lang(url('pages/gta6-pc.php'))) ?>"><?= $fr ? 'Date PC attendue' : 'Expected PC date' ?></a>.</li>
    </ul>

    <h2><?= $fr ? 'Chronologie des annonces GTA 6' : 'GTA 6 announcement timeline' ?></h2>
    <div class="lore-block glass">
        <ul style="margin:0">
            <li><strong>4 <?= $fr ? 'décembre' : 'December' ?> 2023</strong> — <?= $fr ? 'Première bande-annonce de GTA 6 (record de vues sur YouTube).' : 'First GTA 6 trailer (a YouTube views record).' ?></li>
            <li><strong>2025</strong> — <?= $fr ? '2ᵉ bande-annonce.' : '2nd trailer.' ?> <a href="<?= e(with_lang(url('pages/gta6-trailer-2-analyse.php'))) ?>"><?= $fr ? 'Notre analyse image par image' : 'Our frame-by-frame breakdown' ?></a>.</li>
            <li><strong>27 <?= $fr ? 'août' : 'August' ?> 2026</strong> — <?= $fr ? '« An Extended Look » : long extrait de gameplay diffusé.' : '"An Extended Look": long gameplay preview released.' ?> <a href="<?= e(with_lang(url('pages/gta6-extended-look.php'))) ?>"><?= $fr ? 'Le récap' : 'The recap' ?></a>.</li>
            <li><strong>19 <?= $fr ? 'novembre' : 'November' ?> 2026</strong> — <?= $fr ? 'Sortie sur PS5 &amp; Xbox Series X|S.' : 'Launch on PS5 &amp; Xbox Series X|S.' ?></li>
        </ul>
    </div>

    <h2><?= $fr ? 'GTA 6 peut-il encore être repoussé ?' : 'Could GTA 6 still be delayed?' ?></h2>
    <p class="muted"><?= $fr
        ? 'C\'est la question que tout le monde se pose. Les faits vont dans le bon sens : la date du 19 novembre 2026 est réaffirmée par Rockstar, et la diffusion d\'un long extrait de gameplay fin août 2026 est le genre de démonstration que l\'on fait quand la sortie approche vraiment. Historiquement, Rockstar a déjà repoussé des jeux (GTA V et RDR2 ont chacun glissé de quelques mois), donc le risque n\'est jamais nul tant que le jeu n\'est pas déclaré « gold ». À ce jour, aucun report n\'est annoncé.'
        : 'That\'s the question on everyone\'s mind. The facts point the right way: Rockstar keeps reaffirming November 19, 2026, and releasing a long gameplay preview in late August 2026 is the kind of showcase you do when launch is genuinely close. Historically, Rockstar has delayed games before (both GTA V and RDR2 slipped by a few months), so the risk is never zero until the game goes gold. As of today, no delay has been announced.' ?></p>
    <blockquote class="lore-block glass" style="border-left:3px solid var(--pink,#ff2e88)"><?= $fr
        ? '✅ <strong>Confirmé :</strong> 19 novembre 2026 (PS5 / Xbox Series X|S). &nbsp; ❔ <strong>Rumeur :</strong> tout report ou date PC précise. On met cette page à jour dès qu\'une info officielle tombe.'
        : '✅ <strong>Confirmed:</strong> November 19, 2026 (PS5 / Xbox Series X|S). &nbsp; ❔ <strong>Rumor:</strong> any delay or precise PC date. We update this page the moment official info drops.' ?></blockquote>

    <h2><?= $fr ? 'Ne rien rater d\'ici la sortie' : 'Don\'t miss anything until launch' ?></h2>
    <p class="muted"><?= $fr
        ? 'On suit chaque annonce officielle en temps réel.'
        : 'We track every official announcement in real time.' ?>
        <a href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= $fr ? 'Toute l\'actu GTA 6' : 'All GTA 6 news' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'Le dossier GTA 6 complet' : 'The full GTA 6 hub' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-prix-editions.php'))) ?>"><?= $fr ? 'Prix &amp; éditions' : 'Price &amp; editions' ?></a>.
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
