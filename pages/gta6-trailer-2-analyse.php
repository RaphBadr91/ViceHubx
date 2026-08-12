<?php
/**
 * Asset SEO/backlinks — « GTA 6 Trailer 2 : analyse détaillée ».
 * Requête ciblée : "gta 6 trailer 2" (pos ~31). Answer-first + FAQPage.
 * Contenu : faits CONFIRMÉS solides + observations clairement étiquetées.
 * ⚠️ Pour renforcer l'asset (aimant à liens), complète les blocs marqués
 *    « <!-- DATA: … --> » avec tes comptages réels après visionnage image par image.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta6-trailer-2-analyse';

$SEO_TITLE = $fr
    ? 'GTA 6 Trailer 2 : analyse détaillée, tout ce qu\'on a repéré (2026)'
    : 'GTA 6 Trailer 2: Full Breakdown & Every Detail Spotted (2026)';
$SEO_DESC = $fr
    ? 'Analyse complète du trailer 2 de GTA 6 : personnages, lieux de Leonida, véhicules et indices confirmés — et ce qui reste une rumeur. Le décryptage image par image.'
    : 'A full breakdown of the GTA 6 Trailer 2: characters, Leonida locations, vehicles and confirmed clues — plus what\'s still rumor. The frame-by-frame analysis.';
$SEO_OG_IMAGE = cdn_url('downtown.png');

$faq = $fr ? [
    ['Quand est sorti le trailer 2 de GTA 6 ?', 'Le premier trailer de GTA 6 est sorti en décembre 2023 et le deuxième en 2025. Ils présentent Vice City et l\'État de Leonida, ainsi que les deux protagonistes Jason Duval et Lucia Caminos.'],
    ['Qui sont les personnages du trailer 2 de GTA 6 ?', 'Le duo principal est Jason Duval et Lucia Caminos — Lucia étant la première protagoniste féminine de la série principale. D\'autres personnages apparaissent mais ne sont pas officiellement nommés.'],
    ['Où se déroule GTA 6 d\'après le trailer ?', 'Dans l\'État fictif de Leonida (inspiré de la Floride), avec le retour de Vice City. Les trailers montrent plages, quartiers néon, marécages et zones urbaines denses.'],
    ['Le trailer 2 confirme-t-il la date de sortie ?', 'Oui : GTA 6 est annoncé pour le 19 novembre 2026 sur PS5 et Xbox Series X|S. La version PC n\'a pas de date officielle.'],
] : [
    ['When did the GTA 6 Trailer 2 release?', 'GTA 6\'s first trailer released in December 2023 and the second in 2025. They showcase Vice City and the state of Leonida, plus the two protagonists Jason Duval and Lucia Caminos.'],
    ['Who are the characters in GTA 6 Trailer 2?', 'The main duo is Jason Duval and Lucia Caminos — Lucia being the first female protagonist in the main series. Other characters appear but are not officially named.'],
    ['Where is GTA 6 set based on the trailer?', 'In the fictional state of Leonida (inspired by Florida), with the return of Vice City. The trailers show beaches, neon districts, swamps and dense urban areas.'],
    ['Does Trailer 2 confirm the release date?', 'Yes: GTA 6 is announced for November 19, 2026 on PS5 and Xbox Series X|S. The PC version has no official date.'],
];

$JSONLD = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        ['@type' => 'FAQPage', '@id' => $self . '#faq',
         'mainEntity' => array_map(static fn ($qa) => [
             '@type' => 'Question', 'name' => $qa[0],
             'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
         ], $faq)],
        ['@type' => 'Article', '@id' => $self . '#article',
         'headline' => $SEO_TITLE, 'description' => $SEO_DESC, 'inLanguage' => lang(),
         'author' => ['@id' => $base . '/#org'], 'publisher' => ['@id' => $base . '/#org'],
         'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self]],
    ],
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow">🎬 GTA 6 · Trailer 2</span>
    <h1><?= $fr ? 'GTA 6 Trailer 2 : analyse détaillée' : 'GTA 6 Trailer 2: Full Breakdown' ?></h1>

    <div class="lore-block glass" style="margin:1rem 0 1.6rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.15rem;margin:0"><strong><?= $fr
            ? 'En bref : le trailer 2 de GTA 6 (2025) confirme le retour de Vice City dans l\'État de Leonida, le duo Jason Duval & Lucia Caminos, et une sortie le 19 novembre 2026 sur PS5 et Xbox Series X|S. Voici le décryptage — faits confirmés d\'un côté, indices et rumeurs de l\'autre.'
            : 'In short: the GTA 6 Trailer 2 (2025) confirms Vice City\'s return in the state of Leonida, the Jason Duval & Lucia Caminos duo, and a November 19, 2026 launch on PS5 and Xbox Series X|S. Here\'s the breakdown — confirmed facts on one side, clues and rumors on the other.' ?></strong></p>
    </div>

    <h2>✅ <?= $fr ? 'Ce que le trailer CONFIRME' : 'What the trailer CONFIRMS' ?></h2>
    <ul>
        <li><strong>Vice City & Leonida</strong> — <?= $fr ? 'retour de Vice City dans un État fictif inspiré de la Floride.' : 'Vice City returns within a fictional Florida-inspired state.' ?></li>
        <li><strong>Jason Duval & Lucia Caminos</strong> — <?= $fr ? 'le duo de protagonistes ; Lucia est la 1re héroïne de la série principale.' : 'the protagonist duo; Lucia is the main series\' first female lead.' ?></li>
        <li><strong><?= $fr ? 'Date de sortie' : 'Release date' ?></strong> — 19/11/2026 (PS5, Xbox Series X|S).</li>
        <li><strong><?= $fr ? 'Ambiance' : 'Atmosphere' ?></strong> — <?= $fr ? 'plages, néons, marécages, foules denses, cycle jour/nuit, météo dynamique.' : 'beaches, neon, swamps, dense crowds, day/night cycle, dynamic weather.' ?></li>
        <li><strong><?= $fr ? 'Moteur' : 'Engine' ?></strong> — RAGE (<?= $fr ? 'éclairage et densité nouvelle génération.' : 'next-gen lighting and density.' ?>)</li>
    </ul>

    <h2>🔍 <?= $fr ? 'Détails repérés (à confirmer)' : 'Details spotted (to be confirmed)' ?></h2>
    <p class="muted"><?= $fr
        ? 'Ces éléments sont observés dans le trailer et discutés par la communauté, mais ne sont pas détaillés officiellement par Rockstar. À prendre comme des indices, pas des certitudes.'
        : 'These elements are observed in the trailer and discussed by the community, but not officially detailed by Rockstar. Treat them as clues, not certainties.' ?></p>
    <!-- DATA: remplace/complète cette liste avec tes comptages réels après visionnage image par image (véhicules distincts, PNJ nommés, lieux identifiables). C'est ce qui rend l'article citable par la presse. -->
    <div class="lore-block glass">
        <div class="lore-grid">
            <div><h3>🚗 <?= $fr ? 'Véhicules' : 'Vehicles' ?></h3><p class="muted"><?= $fr ? 'Voitures rétro, sportives, motos, hélicoptères et bateaux visibles. Roster complet non confirmé.' : 'Retro cars, sports cars, bikes, helicopters and boats visible. Full roster unconfirmed.' ?></p></div>
            <div><h3>📍 <?= $fr ? 'Lieux' : 'Locations' ?></h3><p class="muted"><?= $fr ? 'Front de mer type Ocean Drive, quartiers résidentiels, zones humides, centre-ville.' : 'Ocean Drive-style waterfront, residential districts, wetlands, downtown.' ?></p></div>
            <div><h3>🎵 <?= $fr ? 'Musique & radios' : 'Music & radio' ?></h3><p class="muted"><?= $fr ? 'Bande-son synthwave/latino ; radios (dont V-Rock) attendues, playlist non détaillée.' : 'Synthwave/Latin soundtrack; radio stations (incl. V-Rock) expected, playlist undetailed.' ?></p></div>
            <div><h3>🐊 <?= $fr ? 'Détails d\'ambiance' : 'Flavor details' ?></h3><p class="muted"><?= $fr ? 'Faune (alligators), culture réseaux sociaux, humour satirique typique de la série.' : 'Wildlife (alligators), social-media culture, the series\' signature satire.' ?></p></div>
        </div>
    </div>

    <h2>❓ <?= $fr ? 'Ce que le trailer ne dit PAS encore' : 'What the trailer does NOT reveal yet' ?></h2>
    <ul>
        <li><?= $fr ? 'La taille exacte de la carte et le nombre de quartiers.' : 'The exact map size and number of districts.' ?></li>
        <li><?= $fr ? 'Le contenu et la date du mode en ligne.' : 'The online mode\'s content and timing.' ?></li>
        <li><?= $fr ? 'La date PC (attendue ~2027, non officielle — voir notre' : 'The PC date (expected ~2027, unofficial — see our' ?> <a href="<?= e(with_lang(url('pages/gta6-pc.php'))) ?>"><?= $fr ? 'pilier GTA 6 PC' : 'GTA 6 PC hub' ?></a>).</li>
    </ul>

    <h2>❓ <?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem">
            <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
            <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
        </div>
    <?php endforeach; ?>

    <p style="margin-top:2rem">
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= $fr ? 'Toutes les actus GTA 6' : 'All GTA 6 news' ?></a>
        <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'Le dossier complet' : 'The full hub' ?></a>
    </p>
    <p class="muted" style="margin-top:1rem;font-size:.85rem"><?= $fr
        ? 'ViceHub X est un média indépendant non officiel. GTA, Grand Theft Auto et Vice City sont des marques de Rockstar Games / Take-Two.'
        : 'ViceHub X is an independent, unofficial media. GTA, Grand Theft Auto and Vice City are trademarks of Rockstar Games / Take-Two.' ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
