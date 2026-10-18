<?php
/**
 * Guide d'achat « matériel pour GTA 6 » — page monétisée ET référencée.
 * Contenu unique + rayons (titres mots-clés) + FAQ + schema ItemList/FAQPage,
 * pour viser les requêtes « [produit] pour GTA 6 » (où un média GTA 6 peut
 * ranker, contrairement au terme produit seul). Bilingue FR/EN.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/deals';

$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$modDate = date('Y-m-d');

$SEO_TITLE = $fr
    ? 'Matériel GTA 6 : le meilleur setup PS5 & Xbox (guide 2026)'
    : 'GTA 6 Gear: The Best PS5 & Xbox Setup (2026 Buying Guide)';
$SEO_DESC = $fr
    ? 'Le meilleur matériel pour jouer à GTA 6 : console PS5/Xbox, manette, casque, SSD (combien d\'espace ?), volant, écran 4K 120Hz et goodies Vice City. Notre guide d\'achat.'
    : 'The best gear to play GTA 6: PS5/Xbox console, controller, headset, SSD (how much space?), racing wheel, 4K 120Hz screen and Vice City goodies. Our buying guide.';
$SEO_OG_IMAGE = cdn_url('downtown.png') ?: ($base . '/public/assets/img/brand/og-share.jpg');

$deals = get_deals();

// Rayons : chaque section = un intitulé mot-clé « pour GTA 6 » + les badges inclus.
$sections = $fr ? [
    ['Consoles pour GTA 6',             '🎮', ['Console','Premium','Petit prix'],  'Pour jouer à GTA 6 le jour J en 4K, il te faut une PS5 ou une Xbox Series X|S. Voici les modèles à viser selon ton budget.'],
    ['Manettes PS5 & Xbox',             '🎮', ['Manette','Pro'],                   'Une bonne manette change tout dans GTA 6, entre fusillades et poursuites. Standard, pro ou de rechange.'],
    ['Volants & conduite',              '🏎️', ['Conduite'],                        'GTA 6, c\'est avant tout des voitures : un volant force feedback rend chaque course à Vice City bien plus immersive.'],
    ['Casques & audio',                 '🎧', ['Audio'],                           'La bande-son synthwave de GTA 6 mérite un bon casque. Son 3D, sans fil ou réduction de bruit.'],
    ['Stockage : quel SSD pour GTA 6 ?','💾', ['Stockage'],                        'GTA 6 sera très lourd (jeu + patch day-one). Un SSD NVMe pour PS5 ou une carte d\'extension Xbox évite de tout désinstaller.'],
    ['Écrans & TV 4K 120Hz',            '🖥️', ['Écran'],                           'Pour voir Leonida en pleine résolution et en fluidité, une TV OLED ou un écran 4K 120Hz HDMI 2.1 fait la différence.'],
    ['Streaming & création',            '🎥', ['Streaming'],                       'Tu veux streamer tes parties de GTA 6 ? Voici le matériel des créateurs : capture, webcam, Stream Deck.'],
    ['VR & périphériques PC',           '🕶️', ['VR','PC'],                         'Pour les geeks : VR, clavier mécanique et souris gaming, en attendant GTA 6 sur PC.'],
    ['Setup, confort & accessoires',    '🪑', ['Setup','Confort','Accessoire'],    'Le confort qui te fait tenir des heures sur GTA 6, et les accessoires PS5 indispensables.'],
    ['Déco Vice City',                  '💡', ['Déco'],                            'Recrée l\'ambiance néon de Vice City dans ta chambre ou ton setup.'],
    ['Jeux, cartes & abonnements',      '🎁', ['Jeu','Carte cadeau','Abonnement'], 'En attendant GTA 6, des jeux Rockstar et de quoi recharger PSN/Xbox pour précommander.'],
    ['Goodies & collector',             '👕', ['Goodies','Collector'],             'Affiche ta passion GTA / Vice City : vêtements, figurines, posters et objets de collection.'],
] : [
    ['Consoles for GTA 6',              '🎮', ['Console','Premium','Petit prix'],  'To play GTA 6 at launch in 4K you need a PS5 or Xbox Series X|S. Here are the models to aim for by budget.'],
    ['PS5 & Xbox controllers',          '🎮', ['Manette','Pro'],                   'A good controller changes everything in GTA 6, from shootouts to chases. Standard, pro or spare.'],
    ['Racing wheels & driving',         '🏎️', ['Conduite'],                        'GTA 6 is all about cars: a force-feedback wheel makes every drive through Vice City far more immersive.'],
    ['Headsets & audio',                '🎧', ['Audio'],                           'GTA 6\'s synthwave soundtrack deserves a great headset. 3D audio, wireless or noise-cancelling.'],
    ['Storage: which SSD for GTA 6?',   '💾', ['Stockage'],                        'GTA 6 will be huge (game + day-one patch). A PS5 NVMe SSD or Xbox expansion card saves you from uninstalling everything.'],
    ['Screens & 4K 120Hz TVs',          '🖥️', ['Écran'],                           'To see Leonida at full resolution and smoothness, an OLED TV or 4K 120Hz HDMI 2.1 screen makes the difference.'],
    ['Streaming & creation',            '🎥', ['Streaming'],                       'Want to stream your GTA 6 sessions? Here is the creators\' kit: capture, webcam, Stream Deck.'],
    ['VR & PC peripherals',             '🕶️', ['VR','PC'],                         'For the geeks: VR, mechanical keyboard and gaming mouse, while waiting for GTA 6 on PC.'],
    ['Setup, comfort & accessories',    '🪑', ['Setup','Confort','Accessoire'],    'The comfort that keeps you going for hours on GTA 6, plus the must-have PS5 accessories.'],
    ['Vice City decor',                 '💡', ['Déco'],                            'Recreate Vice City\'s neon vibe in your room or setup.'],
    ['Games, cards & subscriptions',    '🎁', ['Jeu','Carte cadeau','Abonnement'], 'While waiting for GTA 6, Rockstar games and ways to top up PSN/Xbox to pre-order.'],
    ['Goodies & collectibles',          '👕', ['Goodies','Collector'],             'Show your GTA / Vice City passion: apparel, figures, posters and collectibles.'],
];

// Range chaque produit dans son rayon (selon badge) ; le reste va en « Autres ».
$bySection = array_fill(0, count($sections), []);
$used = [];
foreach ($sections as $i => $s) {
    foreach ($deals as $k => $d) {
        if (in_array((string) ($d['badge'] ?? ''), $s[2], true)) { $bySection[$i][] = $d; $used[$k] = true; }
    }
}
$others = [];
foreach ($deals as $k => $d) { if (empty($used[$k])) { $others[] = $d; } }

$faq = $fr ? [
    ['Quelle console choisir pour GTA 6 ?', 'GTA 6 sort sur PS5 et Xbox Series X|S. La PS5 (ou PS5 Pro pour le meilleur rendu) et la Xbox Series X offrent la 4K ; la Series S est l\'option la moins chère.'],
    ['Combien d\'espace de stockage faut-il pour GTA 6 ?', 'Le jeu sera très volumineux, avec un patch day-one en plus. Prévois un SSD NVMe pour PS5 (type WD_BLACK SN850P) ou une carte d\'extension pour Xbox afin de ne pas manquer de place.'],
    ['Quel est le meilleur matériel pour GTA 6 ?', 'Une console next-gen, une manette fiable, un bon casque, de l\'espace de stockage et, pour les fans de conduite, un volant force feedback. Notre guide ci-dessus regroupe le tout par rayon.'],
] : [
    ['Which console should I pick for GTA 6?', 'GTA 6 launches on PS5 and Xbox Series X|S. The PS5 (or PS5 Pro for the best visuals) and Xbox Series X deliver 4K; the Series S is the cheapest option.'],
    ['How much storage does GTA 6 need?', 'The game will be large, with a day-one patch on top. Get a PS5 NVMe SSD (like the WD_BLACK SN850P) or an Xbox expansion card so you don\'t run out of space.'],
    ['What is the best gear for GTA 6?', 'A next-gen console, a reliable controller, a good headset, storage space and — for driving fans — a force-feedback wheel. Our guide above groups everything by category.'],
];

// Schema : ItemList (produits recommandés) + FAQPage.
$itemList = [];
$pos = 1;
foreach ($deals as $d) {
    $itemList[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => (string) $d['title'], 'url' => $self . '#deals'];
}
$JSONLD = [
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'ItemList', '@id' => $self . '#list', 'name' => $SEO_TITLE,
         'numberOfItems' => count($itemList), 'itemListElement' => $itemList],
        ['@type' => 'FAQPage', '@id' => $self . '#faq',
         'mainEntity' => array_map(static fn ($qa) => [
            '@type' => 'Question', 'name' => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
         ], $faq)],
    ],
];

require ROOT_PATH . '/includes/header.php';

// Rend un bloc de cartes produits.
$renderCards = static function (array $items) use ($fr): void {
    echo '<div class="cards" style="margin:1rem 0 1.8rem">';
    foreach ($items as $d) {
        echo '<a class="deal glass reveal" href="' . e(amazon_url((string) $d['url'])) . '" rel="sponsored nofollow" target="_blank">';
        echo '<span class="tag">' . e((string) $d['platform']) . (!empty($d['badge']) ? ' · ' . e((string) $d['badge']) : '') . '</span>';
        echo '<h3 style="font-size:1.05rem">' . e((string) $d['title']) . '</h3>';
        echo '<p class="muted">' . e((string) $d['description']) . '</p>';
        echo '<span class="btn btn--ghost" style="align-self:flex-start">' . ($fr ? 'Voir le produit' : 'View product') . ' →</span>';
        echo '</a>';
    }
    echo '</div>';
};
?>
<section class="section">
    <span class="eyebrow"><?= vhx_icon('tag') ?> <?= $fr ? 'Guide d\'achat' : 'Buying guide' ?></span>
    <h1><?= $fr ? 'Matériel pour GTA 6 : le meilleur setup PS5 &amp; Xbox' : 'GTA 6 Gear: The Best PS5 &amp; Xbox Setup' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>

    <div class="lore-block glass" style="margin:1.1rem 0 1.4rem;border-left:3px solid var(--blue)">
        <p style="margin:0"><?= $fr
            ? 'GTA 6 sort le <strong>19 novembre 2026</strong> sur PS5 et Xbox Series X|S. Pour en profiter à fond dès le jour J, on a réuni ici le <strong>meilleur matériel</strong>, rayon par rayon : console, manette, casque, <strong>stockage</strong> (le jeu sera énorme), volant pour la conduite, écran 4K et goodies Vice City. Notre sélection pour les joueurs.'
            : 'GTA 6 launches <strong>November 19, 2026</strong> on PS5 and Xbox Series X|S. To make the most of it from day one, we\'ve gathered the <strong>best gear</strong> here, category by category: console, controller, headset, <strong>storage</strong> (the game will be huge), racing wheel, 4K screen and Vice City goodies. Our pick for players.' ?></p>
    </div>

    <div id="deals">
    <?php foreach ($sections as $i => $s): if (empty($bySection[$i])) continue; ?>
        <h2 style="margin-top:1.4rem"><?= e($s[1]) ?> <?= e($s[0]) ?></h2>
        <p class="muted" style="margin:.2rem 0 .4rem;max-width:760px"><?= e($s[3]) ?></p>
        <?php $renderCards($bySection[$i]); ?>
    <?php endforeach; ?>

    <?php if ($others): ?>
        <h2 style="margin-top:1.4rem">➕ <?= $fr ? 'Autres' : 'More' ?></h2>
        <?php $renderCards($others); ?>
    <?php endif; ?>
    </div>

    <h2 style="margin-top:1.6rem"><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem">
            <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
            <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
        </div>
    <?php endforeach; ?>

    <p class="muted" style="margin-top:1.4rem">
        <?= $fr ? 'À lire aussi :' : 'See also:' ?>
        <a href="<?= e(with_lang(url('pages/gta-6-date-de-sortie.php'))) ?>"><?= $fr ? 'date de sortie' : 'release date' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-prix-editions.php'))) ?>"><?= $fr ? 'prix &amp; éditions' : 'price &amp; editions' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-launch-live.php'))) ?>"><?= $fr ? 'GTA 6 est-il sorti ?' : 'is GTA 6 out yet?' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'dossier GTA 6' : 'GTA 6 hub' ?></a>
    </p>

    <p class="muted" style="max-width:760px;margin-top:2rem;font-size:.74rem;opacity:.6">
        <?= $fr
            ? 'En tant que Partenaire Amazon, ViceHub X réalise un bénéfice sur les achats remplissant les conditions requises.'
            : 'As an Amazon Associate, ViceHub X earns from qualifying purchases.' ?>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
