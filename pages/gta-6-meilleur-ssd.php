<?php
/**
 * Guide d'achat ciblé — « Quel SSD pour GTA 6 ? » (PS5 / Xbox).
 * Intention commerciale + info : on est déjà en page 1 sur « storage ». Answer-first,
 * recommandations produits (affiliation), FAQ, schema. Bilingue FR/EN. Spoke de /deals.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta-6-meilleur-ssd';
$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$modDate = date('Y-m-d');

$SEO_TITLE = $fr ? 'Quel SSD pour GTA 6 sur PS5 & Xbox ? (guide 2026)' : 'Best SSD for GTA 6 on PS5 & Xbox? (2026 Guide)';
$SEO_DESC  = $fr
    ? 'GTA 6 sera énorme : quel SSD choisir pour PS5 (NVMe) ou Xbox (carte d\'extension), combien de Go prévoir et comment libérer de l\'espace avant le 19 novembre 2026.'
    : 'GTA 6 will be huge: which SSD to pick for PS5 (NVMe) or Xbox (expansion card), how many GB to plan, and how to free up space before November 19, 2026.';
$SEO_OG_IMAGE = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20261022_174632_cc580d8b-71e3-46f4-a6ad-91ff683065f6_min.webp';

try { $picks = db()->query("SELECT * FROM affiliate_links WHERE active=1 AND badge='Stockage' ORDER BY sort ASC, id ASC LIMIT 6")->fetchAll(); }
catch (Throwable $e) { $picks = []; }

$faq = $fr ? [
    ['Combien d\'espace faut-il pour GTA 6 ?', 'Rockstar n\'a pas communiqué la taille finale, mais au vu de GTA V (~90-100 Go avec les mises à jour) et de l\'ampleur de GTA 6, prévois large : un SSD avec au moins 1 To d\'espace libre est l\'idéal, patch day-one compris.'],
    ['Quel SSD choisir pour la PS5 ?', 'Un SSD NVMe M.2 PCIe 4.0 compatible PS5, avec dissipateur thermique, d\'au moins 1 To. Le WD_BLACK SN850P et le Samsung 990 Pro sont des valeurs sûres.'],
    ['Comment ajouter du stockage sur Xbox Series X|S ?', 'Via une carte d\'extension de stockage officielle (Seagate ou WD) qui se branche à l\'arrière : elle double ton espace en un clic, sans démontage.'],
    ['Peut-on installer GTA 6 sur un disque dur externe ?', 'Sur PS5/Xbox, un SSD externe USB sert surtout à stocker/archiver les jeux ; pour JOUER à GTA 6 (jeu next-gen), il faut l\'installer sur le SSD interne ou une extension NVMe/carte officielle.'],
] : [
    ['How much space does GTA 6 need?', 'Rockstar hasn\'t shared the final size, but given GTA V (~90-100 GB with updates) and GTA 6\'s scale, plan big: an SSD with at least 1 TB free is ideal, day-one patch included.'],
    ['Which SSD should I get for PS5?', 'An NVMe M.2 PCIe 4.0 SSD compatible with PS5, with a heatsink, at least 1 TB. The WD_BLACK SN850P and Samsung 990 Pro are safe bets.'],
    ['How do I add storage on Xbox Series X|S?', 'Use an official storage expansion card (Seagate or WD) that plugs into the back: it doubles your space in one click, no disassembly.'],
    ['Can I install GTA 6 on an external hard drive?', 'On PS5/Xbox, an external USB SSD is mainly for storing/archiving games; to PLAY GTA 6 (a next-gen game) you must install it on the internal SSD or an NVMe/official expansion.'],
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
    <?= breadcrumb_bar([[$fr ? 'Accueil' : 'Home', with_lang(url('index.php'))], [$fr ? 'Matériel GTA 6' : 'GTA 6 gear', with_lang(url('pages/deals.php'))], [$fr ? 'Quel SSD pour GTA 6' : 'Best SSD for GTA 6', '']]) ?>
    <span class="eyebrow"><?= vhx_icon('folder') ?> GTA 6 · <?= $fr ? 'Guide stockage' : 'Storage guide' ?></span>
    <h1><?= $fr ? 'Quel SSD pour GTA 6 ? (PS5 &amp; Xbox)' : 'Best SSD for GTA 6? (PS5 &amp; Xbox)' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>
    <img src="<?= e($SEO_OG_IMAGE) ?>" alt="<?= e($SEO_TITLE) ?>" width="1200" height="675" loading="eager" fetchpriority="high" decoding="async" style="width:100%;height:auto;aspect-ratio:16/9;object-fit:cover;border-radius:16px;margin:1rem 0 .4rem;display:block">

    <div class="lore-block glass" style="margin:1.1rem 0 1.4rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.12rem;margin:0"><strong><?= $fr
            ? 'En bref : GTA 6 sera très volumineux. Pour la PS5, prends un SSD NVMe M.2 PCIe 4.0 avec dissipateur (≥ 1 To) comme le WD_BLACK SN850P ; pour la Xbox Series X|S, une carte d\'extension officielle. Libère de l\'espace dès maintenant pour le patch day-one du 19 novembre 2026.'
            : 'In short: GTA 6 will be very large. For PS5, get an NVMe M.2 PCIe 4.0 SSD with a heatsink (≥ 1 TB) like the WD_BLACK SN850P; for Xbox Series X|S, an official expansion card. Free up space now for the day-one patch on November 19, 2026.' ?></strong></p>
    </div>

    <h2><?= $fr ? 'Pourquoi il te faut de l\'espace pour GTA 6' : 'Why you\'ll need space for GTA 6' ?></h2>
    <p class="muted"><?= $fr
        ? 'GTA V pèse aujourd\'hui près de 100 Go avec ses mises à jour. GTA 6, bien plus ambitieux (Leonida, villes denses, physique next-gen), sera lourd dès l\'installation, sans compter le patch day-one quasi certain. Mieux vaut avoir de la marge pour éviter de devoir désinstaller d\'autres jeux le soir de la sortie.'
        : 'GTA V is now close to 100 GB with updates. GTA 6, far more ambitious (Leonida, dense cities, next-gen physics), will be heavy from install, plus a near-certain day-one patch. Better to have headroom so you don\'t have to uninstall other games on launch night.' ?>
        <a href="<?= e(with_lang(url('article/gta-6-storage-requirements-space-needed-on-ps5-xbox-series-x-s'))) ?>"><?= $fr ? 'Détail de l\'espace nécessaire' : 'Full storage breakdown' ?></a>.</p>

    <h2><?= $fr ? 'PS5 : le bon SSD NVMe' : 'PS5: the right NVMe SSD' ?></h2>
    <p class="muted"><?= $fr
        ? 'La PS5 accepte un SSD interne M.2 NVMe PCIe 4.0. Vérifie trois choses : la vitesse (≥ 5 500 Mo/s recommandé par Sony), la présence d\'un dissipateur thermique, et la capacité (1 à 2 To idéalement).'
        : 'The PS5 accepts an internal M.2 NVMe PCIe 4.0 SSD. Check three things: speed (Sony recommends ≥ 5,500 MB/s), a heatsink, and capacity (1 to 2 TB ideally).' ?></p>

    <h2><?= $fr ? 'Xbox Series X|S : la carte d\'extension' : 'Xbox Series X|S: the expansion card' ?></h2>
    <p class="muted"><?= $fr
        ? 'Le plus simple sur Xbox : une carte d\'extension de stockage officielle qui se branche à l\'arrière de la console. Aucune manipulation interne, performances identiques au SSD intégré.'
        : 'Easiest on Xbox: an official storage expansion card that plugs into the back of the console. No internal work, same performance as the built-in SSD.' ?></p>

    <?php if ($picks): ?>
        <h2><?= $fr ? 'Nos recommandations stockage' : 'Our storage picks' ?></h2>
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
        <a href="<?= e(with_lang(url('pages/gta-6-date-de-sortie.php'))) ?>"><?= $fr ? 'date de sortie' : 'release date' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-meilleure-manette.php'))) ?>"><?= $fr ? 'quelle manette ?' : 'which controller?' ?></a> ·
        <a href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= $fr ? 'matériel GTA 6' : 'GTA 6 gear' ?></a>
    </p>

    <p class="muted" style="max-width:760px;margin-top:2rem;font-size:.74rem;opacity:.6">
        <?= $fr ? 'En tant que Partenaire Amazon, ViceHub X réalise un bénéfice sur les achats remplissant les conditions requises.' : 'As an Amazon Associate, ViceHub X earns from qualifying purchases.' ?>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
