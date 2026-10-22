<?php
/**
 * Guide d'achat ciblé — « Quelle manette pour GTA 6 ? » (PS5 / Xbox).
 * Answer-first + recommandations produits (affiliation) + FAQ + schema. Bilingue.
 * Spoke de /deals.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta-6-meilleure-manette';
$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$modDate = date('Y-m-d');

$SEO_TITLE = $fr ? 'Quelle manette pour GTA 6 ? (PS5 & Xbox, 2026)' : 'Best Controller for GTA 6? (PS5 & Xbox, 2026)';
$SEO_DESC  = $fr
    ? 'Quelle manette choisir pour jouer à GTA 6 ? DualSense, DualSense Edge, manette Xbox ou Elite : notre guide pour bien viser, conduire et profiter de Vice City.'
    : 'Which controller should you pick for GTA 6? DualSense, DualSense Edge, Xbox or Elite: our guide to aim, drive and enjoy Vice City at its best.';
$SEO_OG_IMAGE = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20261022_174632_a8fbe5b9-11b1-4fe0-9869-5a68cec846a2_min.webp';

try { $picks = db()->query("SELECT * FROM affiliate_links WHERE active=1 AND badge IN ('Manette','Pro') ORDER BY sort ASC, id ASC LIMIT 6")->fetchAll(); }
catch (Throwable $e) { $picks = []; }

$faq = $fr ? [
    ['Quelle manette est la meilleure pour GTA 6 ?', 'Sur PS5, la DualSense (retour haptique + gâchettes adaptatives) est idéale pour sentir la conduite et les armes ; la DualSense Edge ajoute des palettes arrière pour les joueurs exigeants. Sur Xbox, la manette sans fil standard suffit, l\'Elite Series 2 pour le haut de gamme.'],
    ['Faut-il une manette pro pour GTA 6 ?', 'Pas indispensable, mais une manette pro (DualSense Edge, Xbox Elite 2) offre des palettes et une meilleure précision, utiles pour GTA 6 Online.'],
    ['Peut-on jouer à GTA 6 avec une manette Xbox sur PC ?', 'Oui : la manette Xbox sans fil est compatible PC (Bluetooth/USB), un bon choix pour la future version PC de GTA 6.'],
    ['Combien de manettes prévoir ?', 'Une deuxième manette (et une station de charge) évite la batterie à plat en pleine session et permet de jouer à plusieurs sur les mini-jeux.'],
] : [
    ['Which controller is best for GTA 6?', 'On PS5, the DualSense (haptic feedback + adaptive triggers) is ideal to feel driving and weapons; the DualSense Edge adds back paddles for demanding players. On Xbox, the standard wireless controller is plenty, the Elite Series 2 for premium.'],
    ['Do I need a pro controller for GTA 6?', 'Not essential, but a pro controller (DualSense Edge, Xbox Elite 2) offers paddles and better precision, handy for GTA 6 Online.'],
    ['Can I play GTA 6 with an Xbox controller on PC?', 'Yes: the Xbox wireless controller works on PC (Bluetooth/USB), a good pick for the future PC version of GTA 6.'],
    ['How many controllers should I get?', 'A second controller (and a charging dock) avoids a dead battery mid-session and lets you play local mini-games with friends.'],
];

$JSONLD = ['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'FAQPage', '@id' => $self . '#faq', 'mainEntity' => array_map(static fn ($qa) => [
        '@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
    ], $faq)],
    ['@type' => 'Article', '@id' => $self . '#article', 'headline' => $SEO_TITLE, 'description' => $SEO_DESC,
     'inLanguage' => lang(), 'datePublished' => '2026-10-21', 'dateModified' => $modDate,
     'author' => ['@id' => $base . '/#org'], 'publisher' => ['@id' => $base . '/#org'],
     'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self]],
]];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:860px">
    <?= breadcrumb_bar([[$fr ? 'Accueil' : 'Home', with_lang(url('index.php'))], [$fr ? 'Matériel GTA 6' : 'GTA 6 gear', with_lang(url('pages/deals.php'))], [$fr ? 'Quelle manette' : 'Best controller', '']]) ?>
    <span class="eyebrow"><?= vhx_icon('controller') ?> GTA 6 · <?= $fr ? 'Guide manette' : 'Controller guide' ?></span>
    <h1><?= $fr ? 'Quelle manette pour GTA 6 ?' : 'Best Controller for GTA 6?' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>
    <img src="<?= e($SEO_OG_IMAGE) ?>" alt="<?= e($SEO_TITLE) ?>" width="1200" height="675" loading="eager" fetchpriority="high" decoding="async" style="width:100%;height:auto;aspect-ratio:16/9;object-fit:cover;border-radius:16px;margin:1rem 0 .4rem;display:block">

    <div class="lore-block glass" style="margin:1.1rem 0 1.4rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.12rem;margin:0"><strong><?= $fr
            ? 'En bref : sur PS5, la DualSense est le meilleur choix pour GTA 6 (retour haptique + gâchettes adaptatives) ; la DualSense Edge pour les palettes. Sur Xbox, la manette sans fil standard, ou l\'Elite Series 2 en haut de gamme.'
            : 'In short: on PS5, the DualSense is the best pick for GTA 6 (haptics + adaptive triggers); the DualSense Edge for paddles. On Xbox, the standard wireless controller, or the Elite Series 2 for premium.' ?></strong></p>
    </div>

    <h2><?= $fr ? 'PS5 : DualSense ou DualSense Edge ?' : 'PS5: DualSense or DualSense Edge?' ?></h2>
    <p class="muted"><?= $fr
        ? 'La DualSense exploite à fond GTA 6 : tu sens la route sous les roues et la résistance des gâchettes à l\'accélération ou au tir. La DualSense Edge ajoute des palettes arrière et des profils personnalisés, un vrai plus pour viser vite en multijoueur.'
        : 'The DualSense makes the most of GTA 6: you feel the road under the tyres and trigger resistance when accelerating or shooting. The DualSense Edge adds back paddles and custom profiles, a real plus for fast aiming in multiplayer.' ?></p>

    <h2><?= $fr ? 'Xbox : standard ou Elite Series 2 ?' : 'Xbox: standard or Elite Series 2?' ?></h2>
    <p class="muted"><?= $fr
        ? 'La manette Xbox sans fil est fiable, confortable et compatible PC — parfaite pour GTA 6, y compris sur la future version PC. L\'Elite Series 2 vise les joueurs exigeants : palettes, gâchettes réglables, finitions premium.'
        : 'The Xbox wireless controller is reliable, comfortable and PC-compatible — perfect for GTA 6, including the future PC version. The Elite Series 2 targets demanding players: paddles, adjustable triggers, premium finish.' ?></p>

    <?php if ($picks): ?>
        <h2><?= $fr ? 'Nos recommandations manettes' : 'Our controller picks' ?></h2>
        <div class="cards" style="margin:1rem 0 1.4rem">
            <?php foreach ($picks as $d): ?>
                <a class="deal glass reveal" href="<?= e(amazon_url((string) $d['url'])) ?>" rel="sponsored nofollow" target="_blank">
                    <?= deal_visual($d) ?>
                    <div class="deal-body">
                        <span class="tag"><?= e((string) $d['platform']) ?><?= !empty($d['badge']) ? ' · ' . e((string) $d['badge']) : '' ?></span>
                        <h3><?= e((string) $d['title']) ?></h3>
                        <p class="muted"><?= e((string) $d['description']) ?></p>
                        <span class="btn btn--primary" style="align-self:flex-start"><?= $fr ? 'Voir le produit' : 'View product' ?> →</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="muted"><a class="btn btn--ghost" href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= $fr ? 'Tout le matériel pour GTA 6' : 'All the GTA 6 gear' ?> →</a></p>
    <?php endif; ?>

    <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem">
            <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
            <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
        </div>
    <?php endforeach; ?>

    <p class="muted" style="margin-top:1.4rem">
        <?= $fr ? 'À lire aussi :' : 'See also:' ?>
        <a href="<?= e(with_lang(url('pages/gta-6-meilleur-ssd.php'))) ?>"><?= $fr ? 'quel SSD ?' : 'which SSD?' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-date-de-sortie.php'))) ?>"><?= $fr ? 'date de sortie' : 'release date' ?></a> ·
        <a href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= $fr ? 'matériel GTA 6' : 'GTA 6 gear' ?></a>
    </p>

    <p class="muted" style="max-width:760px;margin-top:2rem;font-size:.74rem;opacity:.6">
        <?= $fr ? 'En tant que Partenaire Amazon, ViceHub X réalise un bénéfice sur les achats remplissant les conditions requises.' : 'As an Amazon Associate, ViceHub X earns from qualifying purchases.' ?>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
