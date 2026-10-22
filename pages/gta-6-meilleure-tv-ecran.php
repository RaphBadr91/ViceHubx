<?php
/** Guide d'achat — « Quelle TV / quel écran pour GTA 6 ? ». Bilingue. Spoke de /deals. */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta-6-meilleure-tv-ecran';
$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$modDate = date('Y-m-d');

$SEO_TITLE = $fr ? 'Quelle TV / écran pour GTA 6 ? (4K 120Hz, 2026)' : 'Best TV / Screen for GTA 6? (4K 120Hz, 2026)';
$SEO_DESC  = $fr
    ? 'Quelle TV ou quel écran pour GTA 6 ? OLED, 4K, 120Hz, HDMI 2.1 : notre guide pour voir Leonida en pleine résolution et fluidité sur PS5 et Xbox.'
    : 'Which TV or screen for GTA 6? OLED, 4K, 120Hz, HDMI 2.1: our guide to see Leonida at full resolution and smoothness on PS5 and Xbox.';
$SEO_OG_IMAGE = cdn_url('downtown.png') ?: ($base . '/public/assets/img/brand/og-share.jpg');

$faq = $fr ? [
    ['Quelle TV choisir pour GTA 6 ?', 'Idéalement une TV 4K avec HDMI 2.1 et 120Hz (OLED pour des noirs parfaits, comme la LG OLED C4). GTA 6 sur PS5/Xbox profitera de la haute résolution et de la fluidité.'],
    ['Faut-il du 120Hz pour GTA 6 ?', 'Ce n\'est pas obligatoire, mais le 120Hz (via HDMI 2.1) rend la conduite et l\'action beaucoup plus fluides si le jeu propose un mode performance.'],
    ['Un écran PC convient-il pour GTA 6 sur console ?', 'Oui, un moniteur 4K 120/144Hz avec HDMI 2.1 fonctionne parfaitement avec PS5/Xbox, et prépare aussi la future version PC.'],
] : [
    ['Which TV should I pick for GTA 6?', 'Ideally a 4K TV with HDMI 2.1 and 120Hz (OLED for perfect blacks, like the LG OLED C4). GTA 6 on PS5/Xbox will benefit from high resolution and smoothness.'],
    ['Do I need 120Hz for GTA 6?', 'Not mandatory, but 120Hz (via HDMI 2.1) makes driving and action far smoother if the game offers a performance mode.'],
    ['Does a PC monitor work for GTA 6 on console?', 'Yes, a 4K 120/144Hz monitor with HDMI 2.1 works perfectly with PS5/Xbox, and also preps the future PC version.'],
];
$JSONLD = ['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'FAQPage', '@id' => $self . '#faq', 'mainEntity' => array_map(static fn ($qa) => ['@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]]], $faq)],
    ['@type' => 'Article', '@id' => $self . '#article', 'headline' => $SEO_TITLE, 'description' => $SEO_DESC, 'inLanguage' => lang(), 'datePublished' => '2026-10-22', 'dateModified' => $modDate, 'author' => ['@id' => $base . '/#org'], 'publisher' => ['@id' => $base . '/#org'], 'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self]],
]];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:860px">
    <span class="eyebrow"><?= vhx_icon('monitor') ?> GTA 6 · <?= $fr ? 'Guide TV & écran' : 'TV & screen guide' ?></span>
    <h1><?= $fr ? 'Quelle TV / écran pour GTA 6 ?' : 'Best TV / Screen for GTA 6?' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>
    <div class="lore-block glass" style="margin:1.1rem 0 1.4rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.12rem;margin:0"><strong><?= $fr
            ? 'En bref : pour GTA 6, vise une TV 4K OLED avec HDMI 2.1 et 120Hz (ex. LG OLED C4) afin de profiter de la résolution et de la fluidité sur PS5/Xbox. Un écran PC 4K 120/144Hz fait aussi très bien le job.'
            : 'In short: for GTA 6, aim for a 4K OLED TV with HDMI 2.1 and 120Hz (e.g. LG OLED C4) to enjoy resolution and smoothness on PS5/Xbox. A 4K 120/144Hz PC monitor does the job great too.' ?></strong></p>
    </div>
    <h2><?= $fr ? 'Ce qui compte vraiment' : 'What really matters' ?></h2>
    <p class="muted"><?= $fr
        ? 'Trois critères : la résolution (4K), la fréquence (120Hz via HDMI 2.1 pour la fluidité) et le contraste (OLED pour les néons de Vice City qui claquent dans la nuit). Le reste (taille, marque) dépend de ton budget et de ta pièce.'
        : 'Three criteria: resolution (4K), refresh rate (120Hz via HDMI 2.1 for smoothness) and contrast (OLED for Vice City\'s neon popping at night). The rest (size, brand) depends on your budget and room.' ?></p>
    <?= render_deal_picks(['Écran'], 6) ?>
    <p class="muted"><a class="btn btn--ghost" href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= $fr ? 'Tout le matériel pour GTA 6' : 'All the GTA 6 gear' ?> →</a></p>
    <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem"><h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3><p class="muted" style="margin:0"><?= e($qa[1]) ?></p></div>
    <?php endforeach; ?>
    <p class="muted" style="margin-top:1.4rem"><?= $fr ? 'À lire aussi :' : 'See also:' ?>
        <a href="<?= e(with_lang(url('pages/gta-6-meilleur-casque.php'))) ?>"><?= $fr ? 'quel casque ?' : 'which headset?' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-meilleur-ssd.php'))) ?>"><?= $fr ? 'quel SSD ?' : 'which SSD?' ?></a> ·
        <a href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= $fr ? 'matériel GTA 6' : 'GTA 6 gear' ?></a></p>
    <p class="muted" style="max-width:760px;margin-top:2rem;font-size:.74rem;opacity:.6"><?= $fr ? 'En tant que Partenaire Amazon, ViceHub X réalise un bénéfice sur les achats remplissant les conditions requises.' : 'As an Amazon Associate, ViceHub X earns from qualifying purchases.' ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
