<?php
/**
 * ViceHub X — Page pilier SEO « GTA 6 : tout savoir ».
 * Contenu evergreen à fort trafic (date, prix, éditions, map, persos, PC)
 * + balisage FAQPage (rich snippets Google) + maillage interne + CTA boutique.
 */
require_once dirname(__DIR__) . '/config/config.php';

$fr = lang() === 'fr';
$SEO_TITLE = ($fr ? 'GTA 6 : tout savoir (date, prix, éditions, map)' : 'GTA 6: everything you need to know') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'GTA 6 : date de sortie, prix et éditions (Standard & Ultimate), plateformes, carte de Leonida, Jason & Lucia, version PC et précommande. Le guide complet, mis à jour.'
    : 'GTA 6: release date, price and editions (Standard & Ultimate), platforms, the Leonida map, Jason & Lucia, PC version and pre-order. The complete, updated guide.';
$BODY_CLASS = 'is-pillar';

$days_left = max(0, (int) floor((strtotime(release_date()) - time()) / 86400));

/* ----------------------------------------------------------------------------
 * FAQ — sert à la fois à l'affichage et au balisage schema.org (FAQPage).
 * -------------------------------------------------------------------------- */
$faq = $fr ? [
    ['Quelle est la date de sortie de GTA 6 ?',
     'GTA 6 sort le 19 novembre 2026 sur PlayStation 5 et Xbox Series X|S. La date a été confirmée par Rockstar Games après un premier report depuis l’automne 2025.'],
    ['Combien coûte GTA 6 et quelles sont les éditions ?',
     'Il existe deux éditions : l’édition Standard (79,99 $ / 79,99 €, en version numérique ou en boîte avec code de téléchargement) et l’édition Ultimate (99,99 $ / 99,99 €, uniquement numérique) qui ajoute des véhicules et armes exclusifs, des packs cosmétiques, des boutiques et un garage dédiés, une mission annexe, le Vintage Vice City Pack et un mois de GTA+ sur les boutiques PlayStation et Xbox. Tarifs indicatifs, susceptibles de varier selon la région.'],
    ['Sur quelles plateformes sort GTA 6 ?',
     'Au lancement, GTA 6 est prévu sur PlayStation 5 et Xbox Series X|S uniquement. Aucune version PC n’a été confirmée officiellement à ce jour — historiquement, les opus GTA arrivent sur PC quelques mois après les consoles.'],
    ['Où se déroule GTA 6 ?',
     'Le jeu se déroule dans l’État fictif de Leonida, inspiré de la Floride, dont la ville principale est Vice City. On y retrouve des plages, des marais, des quartiers Art déco et une vie nocturne néon emblématique de la saga.'],
    ['Qui sont les personnages principaux de GTA 6 ?',
     'GTA 6 met en scène un duo jouable : Jason et Lucia. Lucia est la première protagoniste féminine d’un GTA principal. Leur histoire, façon Bonnie & Clyde moderne, sert de fil rouge à l’aventure.'],
    ['Peut-on précommander GTA 6 et quels sont les bonus ?',
     'Oui. La précommande donne accès au Vintage Vice City Pack (véhicule, garage et cosmétiques pour Jason et Lucia), inclus d’office dans l’édition Ultimate. Les bonus et dates limites peuvent varier selon la boutique.'],
    ['GTA 6 aura-t-il un mode en ligne (GTA Online) ?',
     'Rockstar a confirmé travailler sur l’avenir du multijoueur de la saga. Les détails du mode en ligne de GTA 6 seront communiqués plus tard ; il n’est pas inclus au lancement solo décrit ci-dessus.'],
    ['Y a-t-il une édition collector physique de GTA 6 ?',
     'Non. À ce jour, aucune édition collector physique n’a été annoncée. L’édition Standard existe en boîte, mais celle-ci contient un code de téléchargement plutôt qu’un disque dans certaines régions.'],
] : [
    ['When is GTA 6 coming out?',
     'GTA 6 releases on November 19, 2026 for PlayStation 5 and Xbox Series X|S. Rockstar Games confirmed the date after an earlier delay from fall 2025.'],
    ['How much does GTA 6 cost and what are the editions?',
     'There are two editions: Standard ($79.99 / €79.99, digital or boxed with a download code) and Ultimate ($99.99 / €99.99, digital only), which adds exclusive vehicles and weapons, cosmetic packs, dedicated shops and a garage, a side mission, the Vintage Vice City Pack and one month of GTA+ on the PlayStation and Xbox stores. Prices are indicative and may vary by region.'],
    ['Which platforms is GTA 6 on?',
     'At launch, GTA 6 is planned for PlayStation 5 and Xbox Series X|S only. No PC version has been officially confirmed yet — historically, GTA titles reach PC a few months after consoles.'],
    ['Where does GTA 6 take place?',
     'The game is set in the fictional state of Leonida, inspired by Florida, whose main city is Vice City: beaches, swamps, Art Deco districts and the neon nightlife the series is known for.'],
    ['Who are the main characters in GTA 6?',
     'GTA 6 features a playable duo: Jason and Lucia. Lucia is the first female protagonist in a mainline GTA. Their modern Bonnie & Clyde story drives the adventure.'],
    ['Can I pre-order GTA 6 and what are the bonuses?',
     'Yes. Pre-ordering grants the Vintage Vice City Pack (a vehicle, garage and cosmetics for Jason and Lucia), included by default in the Ultimate edition. Bonuses and deadlines may vary by store.'],
    ['Will GTA 6 have an online mode (GTA Online)?',
     'Rockstar has confirmed it is working on the future of the series’ multiplayer. Details about GTA 6’s online mode will come later; it is not part of the single-player launch described above.'],
    ['Is there a physical collector’s edition of GTA 6?',
     'No. As of now, no physical collector’s edition has been announced. The Standard edition exists as a box, but in some regions it contains a download code rather than a disc.'],
];

$JSONLD = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(static function ($qa) {
        return [
            '@type'          => 'Question',
            'name'           => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
        ];
    }, $faq),
];

require ROOT_PATH . '/includes/header.php';
?>
<article class="section pillar">
    <span class="eyebrow">🌴 <?= e($fr ? 'Le guide complet' : 'The complete guide') ?></span>
    <h1><?= e($fr ? 'GTA 6 : tout ce qu’il faut savoir' : 'GTA 6: everything you need to know') ?></h1>
    <p class="lede" style="max-width:70ch;font-size:1.1rem;color:var(--muted,#b9b3c9);margin:.6rem 0 0">
        <?= $fr
            ? 'Date de sortie, prix et éditions, plateformes, carte de Leonida, Jason &amp; Lucia, version PC et précommande : on a réuni tout ce que l’on sait de <strong>Grand Theft Auto VI</strong>, vérifié et mis à jour. Bienvenue à Vice City.'
            : 'Release date, price and editions, platforms, the Leonida map, Jason &amp; Lucia, PC version and pre-order: everything we know about <strong>Grand Theft Auto VI</strong>, verified and updated. Welcome to Vice City.' ?>
    </p>

    <!-- Faits rapides -->
    <div class="refband" style="margin:1.6rem 0 .5rem">
        <div class="ref glass"><div class="big"><?= $days_left ?></div><small><?= e($fr ? 'jours avant la sortie' : 'days until launch') ?></small></div>
        <div class="ref glass"><div class="big">19/11/26</div><small><?= e($fr ? 'Date de sortie' : 'Release date') ?></small></div>
        <div class="ref glass"><div class="big">PS5 · Xbox</div><small><?= e($fr ? 'Plateformes au lancement' : 'Launch platforms') ?></small></div>
        <div class="ref glass"><div class="big">2</div><small><?= e($fr ? 'Héros : Jason & Lucia' : 'Heroes: Jason & Lucia') ?></small></div>
    </div>

    <!-- Date de sortie -->
    <h2 id="date">📅 <?= e($fr ? 'Date de sortie de GTA 6' : 'GTA 6 release date') ?></h2>
    <p><?= $fr
        ? 'Rockstar Games a fixé la sortie de <strong>GTA 6</strong> au <strong>19 novembre 2026</strong>, sur PlayStation 5 et Xbox Series X|S. Après un premier trailer dévoilé fin 2023 et un second en 2025, le studio a confirmé cette fenêtre d’automne — l’une des sorties les plus attendues de l’histoire du jeu vidéo. Notre compte à rebours en page d’accueil suit le décompte en temps réel.'
        : 'Rockstar Games set <strong>GTA 6</strong> for <strong>November 19, 2026</strong>, on PlayStation 5 and Xbox Series X|S. After a first trailer in late 2023 and a second in 2025, the studio confirmed this fall window — one of the most anticipated launches in gaming history. Our homepage countdown tracks it live.' ?></p>

    <!-- Prix & éditions -->
    <h2 id="editions">💸 <?= e($fr ? 'Prix & éditions : Standard ou Ultimate ?' : 'Price & editions: Standard or Ultimate?') ?></h2>
    <p><?= $fr
        ? 'GTA 6 se décline en <strong>deux éditions</strong> seulement. Voici le comparatif clair pour choisir.'
        : 'GTA 6 comes in <strong>two editions</strong> only. Here is the clear comparison to help you choose.' ?></p>

    <div class="edi-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;margin:1.1rem 0">
        <div class="glass" style="padding:1.4rem;border-radius:16px">
            <span class="eyebrow"><?= e($fr ? 'L’essentiel' : 'The essentials') ?></span>
            <h3 style="margin:.2rem 0 .1rem">Édition Standard</h3>
            <p style="font-size:1.6rem;font-weight:800;margin:.2rem 0">79,99 $ <span style="font-size:1rem;color:var(--muted,#b9b3c9)">/ 79,99 €</span></p>
            <ul style="line-height:1.9;margin:.6rem 0 0;padding-left:1.1rem">
                <li><?= e($fr ? 'Le jeu complet GTA 6' : 'The full GTA 6 game') ?></li>
                <li><?= e($fr ? 'Version numérique ou boîte (code)' : 'Digital or boxed (code)') ?></li>
                <li><?= e($fr ? 'Bonus de précommande Vintage Vice City' : 'Vintage Vice City pre-order bonus') ?></li>
            </ul>
        </div>
        <div class="glass" style="padding:1.4rem;border-radius:16px;border:1px solid rgba(255,46,136,.45);box-shadow:0 0 30px rgba(255,46,136,.15)">
            <span class="eyebrow" style="color:#ff2e88"><?= e($fr ? 'Le maximum' : 'The max') ?> ★</span>
            <h3 style="margin:.2rem 0 .1rem">Édition Ultimate</h3>
            <p style="font-size:1.6rem;font-weight:800;margin:.2rem 0">99,99 $ <span style="font-size:1rem;color:var(--muted,#b9b3c9)">/ 99,99 €</span></p>
            <ul style="line-height:1.9;margin:.6rem 0 0;padding-left:1.1rem">
                <li><?= e($fr ? 'Tout le contenu de l’édition Standard' : 'Everything in Standard') ?></li>
                <li><?= e($fr ? 'Véhicules & armes exclusifs' : 'Exclusive vehicles & weapons') ?></li>
                <li><?= e($fr ? 'Packs cosmétiques, boutiques & garage dédiés' : 'Cosmetic packs, dedicated shops & garage') ?></li>
                <li><?= e($fr ? 'Mission annexe + Vintage Vice City Pack' : 'Side mission + Vintage Vice City Pack') ?></li>
                <li><?= e($fr ? '1 mois de GTA+ (PlayStation / Xbox)' : '1 month of GTA+ (PlayStation / Xbox)') ?></li>
                <li><em><?= e($fr ? 'Uniquement en numérique' : 'Digital only') ?></em></li>
            </ul>
        </div>
    </div>
    <p class="muted" style="font-size:.85rem"><?= e($fr
        ? 'Tarifs indicatifs susceptibles de varier selon la région et la boutique. ViceHub X est un média de fans indépendant et non officiel : nous ne vendons pas le jeu.'
        : 'Indicative prices, may vary by region and store. ViceHub X is an independent, unofficial fan media: we do not sell the game.') ?></p>

    <!-- Précommande -->
    <h2 id="precommande">🎁 <?= e($fr ? 'Précommande & bonus' : 'Pre-order & bonuses') ?></h2>
    <p><?= $fr
        ? 'La précommande débloque le <strong>Vintage Vice City Pack</strong> : un véhicule, un garage et des cosmétiques pour Jason et Lucia, pensés dans l’esprit rétro de la ville. Ce pack est <strong>inclus d’office dans l’édition Ultimate</strong>. Les bonus exacts et les dates limites dépendent de la boutique (PlayStation Store, Xbox, Rockstar Store) — vérifie toujours la fiche officielle avant d’acheter.'
        : 'Pre-ordering unlocks the <strong>Vintage Vice City Pack</strong>: a vehicle, a garage and cosmetics for Jason and Lucia, in the city’s retro spirit. This pack is <strong>included by default in the Ultimate edition</strong>. Exact bonuses and deadlines depend on the store (PlayStation Store, Xbox, Rockstar Store) — always check the official listing before buying.' ?></p>

    <!-- Histoire -->
    <h2 id="histoire">🎭 <?= e($fr ? 'L’histoire : Jason & Lucia' : 'The story: Jason & Lucia') ?></h2>
    <p><?= $fr
        ? 'Pour la première fois dans un GTA principal, on incarne un <strong>duo</strong> : Jason et Lucia. Lucia est la <strong>première protagoniste féminine</strong> de la série. Leur relation, façon Bonnie &amp; Clyde des temps modernes, mêle braquages, loyauté et survie dans une Leonida impitoyable. On décortique chaque indice du trailer dans notre '
        : 'For the first time in a mainline GTA, you play a <strong>duo</strong>: Jason and Lucia. Lucia is the series’ <strong>first female protagonist</strong>. Their modern Bonnie &amp; Clyde bond blends heists, loyalty and survival across a ruthless Leonida. We break down every trailer clue in our ' ?>
        <a href="<?= e(with_lang(url('pages/characters.php'))) ?>"><?= e($fr ? 'fiche personnages' : 'characters page') ?></a>
        <?= $fr ? ' et notre ' : ' and our ' ?>
        <a href="<?= e(with_lang(url('pages/trailer-lab.php'))) ?>">Trailer Lab</a>.</p>

    <!-- Carte -->
    <h2 id="map">🗺️ <?= e($fr ? 'La carte : l’État de Leonida' : 'The map: the state of Leonida') ?></h2>
    <p><?= $fr
        ? 'GTA 6 nous emmène dans l’<strong>État de Leonida</strong>, vaste terrain de jeu inspiré de la Floride : Vice City et sa vie nocturne néon, des plages dorées, des marais, des Everglades et des petites villes rétro. Explore la carte interactive et les quartiers connus dans notre '
        : 'GTA 6 takes us to the <strong>state of Leonida</strong>, a vast playground inspired by Florida: Vice City and its neon nightlife, golden beaches, swamps, Everglades and retro small towns. Explore the interactive map and known districts in our ' ?>
        <a href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= e($fr ? 'Map Lab' : 'Map Lab') ?></a>
        <?= $fr ? ' et le ' : ' and the ' ?>
        <a href="<?= e(with_lang(url('pages/dossier.php'))) ?>"><?= e($fr ? 'Dossier' : 'Files') ?></a>.</p>

    <!-- Version PC -->
    <h2 id="pc">🖥️ <?= e($fr ? 'Version PC : ce que l’on sait' : 'PC version: what we know') ?></h2>
    <p><?= $fr
        ? 'À ce jour, <strong>aucune version PC n’est confirmée officiellement</strong> au lancement : GTA 6 sort d’abord sur PS5 et Xbox Series X|S. Si l’on se fie aux habitudes de Rockstar (GTA V, RDR2), une édition PC enrichie arrive généralement <strong>quelques mois à un an plus tard</strong>. Dès qu’une annonce officielle tombe, on la relaie immédiatement dans nos '
        : 'As of now, <strong>no PC version is officially confirmed</strong> at launch: GTA 6 ships first on PS5 and Xbox Series X|S. Based on Rockstar’s habits (GTA V, RDR2), an enhanced PC edition usually arrives <strong>several months to a year later</strong>. The moment an official announcement drops, we relay it in our ' ?>
        <a href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= e($fr ? 'actualités' : 'news') ?></a>.</p>

    <?= article_shop_cta('full') ?>

    <!-- FAQ visible (+ schema FAQPage) -->
    <h2 id="faq">❓ <?= e($fr ? 'FAQ — Questions fréquentes sur GTA 6' : 'FAQ — Frequently asked questions about GTA 6') ?></h2>
    <div class="faq-list" style="margin:1rem 0">
        <?php foreach ($faq as $qa): ?>
            <details class="glass" style="padding:1rem 1.2rem;border-radius:14px;margin:.6rem 0">
                <summary style="cursor:pointer;font-weight:700;font-size:1.05rem"><?= e($qa[0]) ?></summary>
                <p style="margin:.7rem 0 0;line-height:1.7;color:var(--muted,#cfc9dd)"><?= e($qa[1]) ?></p>
            </details>
        <?php endforeach; ?>
    </div>

    <!-- Maillage interne -->
    <h2 id="aller-plus-loin">🔗 <?= e($fr ? 'Pour aller plus loin' : 'Go further') ?></h2>
    <div class="os-grid" style="margin-top:1rem">
        <a class="os-card glass" href="<?= e(with_lang(url('pages/news.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">📰 <?= e($fr ? 'Toutes les actus GTA 6' : 'All GTA 6 news') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/guides.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">📘 <?= e($fr ? 'Guides & astuces' : 'Guides & tips') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/leaks-lab.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🔬 <?= e($fr ? 'Leaks & fiabilité' : 'Leaks & reliability') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/gta6-vs-gta5.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">⚔️ <?= e($fr ? 'GTA 6 vs GTA 5' : 'GTA 6 vs GTA 5') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/vehicles.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🚗 <?= e($fr ? 'Véhicules' : 'Vehicles') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/forum.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">💬 <?= e($fr ? 'Le Forum' : 'The Forum') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/fonds-ecran-gta6.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🖥️ <?= e($fr ? 'Fonds d’écran HD' : 'HD wallpapers') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🛍️ <?= e($fr ? 'La Boutique' : 'The Shop') ?></span></span><span class="os-card__arrow">→</span></a>
    </div>

    <p class="muted" style="font-size:.8rem;margin-top:1.6rem">
        <?= e($fr
            ? 'ViceHub X est un site de fans indépendant, sans lien avec Rockstar Games ni Take-Two Interactive. « Grand Theft Auto » et « GTA » sont des marques de leurs détenteurs respectifs. Informations vérifiées à partir des communications officielles ; susceptibles d’évoluer.'
            : 'ViceHub X is an independent fan site, not affiliated with Rockstar Games or Take-Two Interactive. “Grand Theft Auto” and “GTA” are trademarks of their respective owners. Information verified from official communications; subject to change.') ?>
    </p>
</article>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
