<?php
/**
 * ViceHub X — Page SEO comparative « GTA 6 vs GTA 5 ».
 * Requête evergreen à très fort volume. Comparatif factuel + maillage interne + CTA.
 */
require_once dirname(__DIR__) . '/config/config.php';

$fr = lang() === 'fr';
$SEO_TITLE = ($fr ? 'GTA 6 vs GTA 5 : toutes les différences' : 'GTA 6 vs GTA 5: every difference') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'GTA 6 vs GTA 5 : carte, protagonistes, graphismes, ville, sortie et prix. Le comparatif complet entre Grand Theft Auto VI (Leonida) et GTA V (Los Santos).'
    : 'GTA 6 vs GTA 5: map, protagonists, graphics, city, release and price. The full comparison between Grand Theft Auto VI (Leonida) and GTA V (Los Santos).';
$BODY_CLASS = 'is-pillar';

/* Lignes de comparaison : [critère, GTA 5, GTA 6] */
$rows = $fr ? [
    ['Sortie', '17 septembre 2013', '19 novembre 2026'],
    ['Lieu', 'Los Santos & Blaine County (État de San Andreas, façon Californie)', 'Vice City & l’État de Leonida (façon Floride)'],
    ['Protagonistes', '3 — Michael, Franklin et Trevor', '2 — Jason et Lucia (1ʳᵉ héroïne jouable de la saga)'],
    ['Ambiance', 'Satire de la Californie et d’Hollywood', 'Néon, vie nocturne et chaleur tropicale rétro'],
    ['Taille de la carte', 'Vaste, terre + océan', 'Annoncée plus grande et plus vivante, PNJ et faune réactifs'],
    ['Moteur & rendu', 'RAGE (2013), sublimé sur PS5/PC', 'RAGE nouvelle génération : densité, éclairage et eau next-gen'],
    ['Plateformes', 'PS3/360 → PS4/One → PS5/Series/PC', 'PS5 et Xbox Series X|S au lancement (PC non confirmé)'],
    ['Mode en ligne', 'GTA Online, soutenu pendant 10+ ans', 'Multijoueur en préparation, détaillé plus tard'],
    ['Prix de lancement', '59,99 $ à l’époque', 'Standard 79,99 $ · Ultimate 99,99 $'],
] : [
    ['Release', 'September 17, 2013', 'November 19, 2026'],
    ['Setting', 'Los Santos & Blaine County (state of San Andreas, California-like)', 'Vice City & the state of Leonida (Florida-like)'],
    ['Protagonists', '3 — Michael, Franklin and Trevor', '2 — Jason and Lucia (series’ first playable heroine)'],
    ['Vibe', 'Satire of California and Hollywood', 'Neon, nightlife and retro tropical heat'],
    ['Map size', 'Large, land + ocean', 'Announced bigger and more alive, reactive NPCs and wildlife'],
    ['Engine & visuals', 'RAGE (2013), enhanced on PS5/PC', 'Next-gen RAGE: density, lighting and next-gen water'],
    ['Platforms', 'PS3/360 → PS4/One → PS5/Series/PC', 'PS5 and Xbox Series X|S at launch (PC not confirmed)'],
    ['Online mode', 'GTA Online, supported 10+ years', 'Multiplayer in the works, detailed later'],
    ['Launch price', '$59.99 back then', 'Standard $79.99 · Ultimate $99.99'],
];

require ROOT_PATH . '/includes/header.php';
?>
<article class="section pillar">
    <span class="eyebrow"><?= vhx_icon('versus') ?> <?= e($fr ? 'Le comparatif' : 'The comparison') ?></span>
    <h1>GTA 6 vs GTA 5</h1>
    <p class="lede" style="max-width:70ch;font-size:1.1rem;color:var(--muted,#b9b3c9);margin:.6rem 0 0">
        <?= $fr
            ? 'Treize ans séparent les deux jeux. Carte, héros, ville, graphismes, prix : voici <strong>toutes les différences</strong> entre <strong>Grand Theft Auto VI</strong> et <strong>GTA V</strong>, point par point.'
            : 'Thirteen years apart. Map, heroes, city, graphics, price: here are <strong>all the differences</strong> between <strong>Grand Theft Auto VI</strong> and <strong>GTA V</strong>, point by point.' ?>
    </p>

    <!-- Verdict rapide -->
    <div class="refband" style="margin:1.6rem 0 .5rem">
        <div class="ref glass"><div class="big">2013<br>↓<br>2026</div><small><?= e($fr ? '13 ans d’écart' : '13 years apart') ?></small></div>
        <div class="ref glass"><div class="big">3 → 2</div><small><?= e($fr ? 'Protagonistes' : 'Protagonists') ?></small></div>
        <div class="ref glass"><div class="big">🌴</div><small><?= e($fr ? 'Los Santos → Vice City' : 'Los Santos → Vice City') ?></small></div>
        <div class="ref glass"><div class="big">Next-gen</div><small><?= e($fr ? 'Rendu RAGE 2026' : 'RAGE 2026 visuals') ?></small></div>
    </div>

    <!-- Tableau comparatif -->
    <h2 id="tableau">📊 <?= e($fr ? 'Le comparatif point par point' : 'The point-by-point comparison') ?></h2>
    <div class="cmp-grid" style="display:grid;gap:.5rem;margin:1.1rem 0">
        <div class="cmp-head glass" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;padding:.7rem 1rem;font-weight:800">
            <span></span><span>GTA 5</span><span style="color:#ff2e88">GTA 6</span>
        </div>
        <?php foreach ($rows as $r): ?>
            <div class="cmp-row glass" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;padding:.8rem 1rem;align-items:start">
                <span style="font-weight:700"><?= e($r[0]) ?></span>
                <span style="color:var(--muted,#cfc9dd)"><?= e($r[1]) ?></span>
                <span><?= e($r[2]) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Ce qui change vraiment -->
    <h2 id="nouveautes">✨ <?= e($fr ? 'Ce qui change vraiment avec GTA 6' : 'What truly changes with GTA 6') ?></h2>
    <p><?= $fr
        ? 'Au-delà des chiffres, GTA 6 marque un saut générationnel : un <strong>duo jouable</strong> (et la première héroïne de la série), un retour très attendu à <strong>Vice City</strong>, un monde plus dense et réactif, et un rendu next-gen pensé d’emblée pour PS5 et Xbox Series. Là où GTA V brillait par sa satire de la Californie, GTA 6 mise sur la chaleur néon de la Floride et une narration à deux voix façon Bonnie &amp; Clyde.'
        : 'Beyond the numbers, GTA 6 is a generational leap: a <strong>playable duo</strong> (and the series’ first heroine), a long-awaited return to <strong>Vice City</strong>, a denser, more reactive world, and next-gen visuals built for PS5 and Xbox Series from the ground up. Where GTA V shone with California satire, GTA 6 leans into Florida’s neon heat and a two-voice, Bonnie &amp; Clyde-style story.' ?></p>

    <!-- Verdict -->
    <h2 id="verdict">🏁 <?= e($fr ? 'Verdict' : 'Verdict') ?></h2>
    <p><?= $fr
        ? 'GTA V reste un monument toujours jouable et magnifique sur PS5/PC, parfait pour patienter. Mais <strong>GTA 6 vise plus haut sur tous les plans</strong> : ville, ampleur, immersion et technique. Pour tout savoir avant la sortie, consulte notre guide complet.'
        : 'GTA V remains a still-stunning monument on PS5/PC, perfect to pass the time. But <strong>GTA 6 aims higher on every front</strong>: city, scale, immersion and tech. For everything before launch, read our complete guide.' ?></p>

    <p style="margin:1rem 0">
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= e($fr ? 'GTA 6 : tout savoir' : 'GTA 6: everything') ?> →</a>
    </p>

    <?= article_shop_cta('full') ?>

    <!-- Maillage interne -->
    <h2 id="aller-plus-loin">🔗 <?= e($fr ? 'Pour aller plus loin' : 'Go further') ?></h2>
    <div class="os-grid" style="margin-top:1rem">
        <a class="os-card glass" href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🎮 <?= e($fr ? 'GTA 6 : tout savoir' : 'GTA 6: everything') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/map.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🗺️ <?= e($fr ? 'La carte de Leonida' : 'The Leonida map') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/characters.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🎭 <?= e($fr ? 'Jason & Lucia' : 'Jason & Lucia') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/news.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">📰 <?= e($fr ? 'Actus GTA 6' : 'GTA 6 news') ?></span></span><span class="os-card__arrow">→</span></a>
    </div>

    <p class="muted" style="font-size:.8rem;margin-top:1.6rem">
        <?= e($fr
            ? 'ViceHub X est un site de fans indépendant, sans lien avec Rockstar Games ni Take-Two Interactive. Informations vérifiées à partir des communications officielles ; susceptibles d’évoluer.'
            : 'ViceHub X is an independent fan site, not affiliated with Rockstar Games or Take-Two Interactive. Information verified from official communications; subject to change.') ?>
    </p>
</article>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
