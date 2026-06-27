<?php
/**
 * ViceHub X — Page SEO « Fonds d'écran GTA 6 / Vice City ».
 * Landing evergreen ciblant « fond d'écran GTA 6 / Vice City / 4K / téléphone »,
 * vitrine des thèmes + packs, FAQ schema, et tunnel vers la boutique (conversion).
 */
require_once dirname(__DIR__) . '/config/config.php';

$fr = lang() === 'fr';
$SEO_TITLE = ($fr ? 'Fonds d’écran GTA 6 / Vice City HD (PC, téléphone, 4K)' : 'GTA 6 / Vice City HD wallpapers (PC, phone, 4K)') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'Fonds d’écran GTA 6 et Vice City en HD : voitures, ville néon, plages, nuit. Téléchargement immédiat, sans filigrane, pour PC et téléphone. Packs à prix réduit.'
    : 'GTA 6 and Vice City HD wallpapers: cars, neon city, beaches, night. Instant download, watermark-free, for PC and phone. Discounted bundles.';
$BODY_CLASS = 'is-pillar';

$themes = wallpaper_themes();
unset($themes['pack']); // les packs ont leur propre section
$packs = get_products('wallpaper', null, 'pack');
$cur   = active_currency();

$theme_copy = $fr ? [
    'voiture' => 'Supercars, muscle cars et décapotables sur Ocean Drive.',
    'avion'   => 'Survols aériens, jets et hydravions au-dessus de Leonida.',
    'ville'   => 'Skylines néon, gratte-ciels et avenues de Vice City.',
    'nuit'    => 'Ambiances nocturnes, pluie néon et vie de nuit.',
    'fille'   => 'Portraits glamour façon affiche, esprit Vice City.',
] : [
    'voiture' => 'Supercars, muscle cars and convertibles on Ocean Drive.',
    'avion'   => 'Aerial flyovers, jets and seaplanes over Leonida.',
    'ville'   => 'Neon skylines, skyscrapers and Vice City avenues.',
    'nuit'    => 'Night moods, neon rain and nightlife.',
    'fille'   => 'Glamour, poster-style portraits, Vice City spirit.',
];

$faq = $fr ? [
    ['Les fonds d’écran sont-ils en HD / 4K ?',
     'Oui, chaque fond d’écran est livré en haute définition, parfait pour un écran PC, un fond de bureau ou un smartphone. Tu reçois le fichier propre, sans filigrane.'],
    ['Dans quels formats sont livrés les fonds d’écran ?',
     'Après l’achat, tu reçois automatiquement chaque visuel par e-mail en PNG, JPEG et PDF — prêts à utiliser sur n’importe quel appareil.'],
    ['Comment fonctionne un pack de fonds d’écran ?',
     'Un pack regroupe plusieurs fonds d’écran d’un même thème (ou tout le catalogue) à prix très réduit. Tu paies une fois et tu reçois tous les fichiers du pack d’un coup, par e-mail.'],
    ['Les fonds d’écran sont-ils officiels ?',
     'Non. ViceHub X est un média de fans indépendant, sans lien avec Rockstar Games. Nos visuels sont des créations originales inspirées de l’univers Vice City, faites par et pour les fans.'],
    ['Puis-je les utiliser sur téléphone et sur PC ?',
     'Oui. Le format vertical convient au téléphone et le format paysage au bureau PC ; tu peux recadrer librement selon ton écran.'],
] : [
    ['Are the wallpapers HD / 4K?',
     'Yes, every wallpaper is delivered in high definition, perfect for a PC screen, desktop background or smartphone. You get the clean file, watermark-free.'],
    ['What formats are the wallpapers delivered in?',
     'After purchase, you automatically receive each visual by email in PNG, JPEG and PDF — ready to use on any device.'],
    ['How does a wallpaper bundle work?',
     'A bundle groups several wallpapers from one theme (or the whole catalog) at a deep discount. You pay once and receive all the bundle files at once, by email.'],
    ['Are the wallpapers official?',
     'No. ViceHub X is an independent fan media, not affiliated with Rockstar Games. Our visuals are original creations inspired by the Vice City universe, by and for fans.'],
    ['Can I use them on phone and PC?',
     'Yes. The vertical format suits phones and the landscape format suits PC desktops; you can crop freely to fit your screen.'],
];

$JSONLD = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(static fn($qa) => [
        '@type' => 'Question', 'name' => $qa[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
    ], $faq),
];

require ROOT_PATH . '/includes/header.php';
?>
<article class="section pillar">
    <span class="eyebrow">🖥️ <?= e($fr ? 'Téléchargement immédiat · sans filigrane' : 'Instant download · watermark-free') ?></span>
    <h1><?= e($fr ? 'Fonds d’écran GTA 6 &amp; Vice City' : 'GTA 6 &amp; Vice City wallpapers') ?></h1>
    <p class="lede" style="max-width:70ch;font-size:1.1rem;color:var(--muted,#b9b3c9);margin:.6rem 0 0">
        <?= $fr
            ? 'Habille ton écran avec l’univers néon de <strong>Vice City</strong> : supercars, skylines, plages et nuits électriques. Des <strong>fonds d’écran HD</strong> pour PC et téléphone, livrés instantanément par e-mail, sans filigrane. Et des <strong>packs</strong> à prix cassé pour tout avoir.'
            : 'Dress up your screen with the neon world of <strong>Vice City</strong>: supercars, skylines, beaches and electric nights. <strong>HD wallpapers</strong> for PC and phone, delivered instantly by email, watermark-free. Plus <strong>bundles</strong> at a deep discount.' ?>
    </p>

    <div class="hero__actions" style="margin:1.2rem 0">
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/shop.php?cat=wallpaper'))) ?>"><?= e($fr ? 'Voir tous les fonds d’écran' : 'Browse all wallpapers') ?> →</a>
        <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/shop.php?cat=wallpaper&theme=pack'))) ?>"><?= e($fr ? 'Les packs 📦' : 'The bundles 📦') ?></a>
    </div>

    <!-- Thèmes -->
    <h2 id="themes">🎨 <?= e($fr ? 'Les thèmes' : 'The themes') ?></h2>
    <div class="shop-teaser-grid" style="margin-top:1rem">
        <?php foreach ($themes as $tkey => $tlabel):
            $sample = get_products('wallpaper', 1, $tkey);
            $img = $sample ? img_src($sample[0]['image']) : ''; ?>
            <a class="product glass reveal" href="<?= e(with_lang(url('pages/shop.php?cat=wallpaper&theme=' . $tkey))) ?>">
                <span class="product__media">
                    <?php if ($img): ?><img class="product__img" src="<?= e($img) ?>" alt="<?= e(($fr ? 'Fond d’écran ' : 'Wallpaper ') . $tlabel) ?> GTA 6 Vice City" loading="lazy" onerror="this.remove()"><?php endif; ?>
                </span>
                <div class="product__body">
                    <h3 class="product__title"><?= e($tlabel) ?></h3>
                    <p class="card__excerpt"><?= e($theme_copy[$tkey] ?? '') ?></p>
                    <span class="product__cat"><?= e($fr ? 'Voir le thème' : 'View theme') ?> →</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Packs -->
    <?php if ($packs): ?>
    <h2 id="packs">📦 <?= e($fr ? 'Les packs (économise gros)' : 'The bundles (save big)') ?></h2>
    <p><?= e($fr
        ? 'Un seul achat, tous les fichiers livrés ensemble. Le meilleur rapport qualité-prix pour habiller tous tes écrans.'
        : 'One purchase, all files delivered together. The best value to dress up all your screens.') ?></p>
    <div class="shop-teaser-grid" style="margin-top:1rem">
        <?php foreach ($packs as $p): ?>
            <article class="product glass reveal">
                <a class="product__media" href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>">
                    <?php if (!empty($p['badge'])): ?><span class="product__badge"><?= e($p['badge']) ?></span><?php endif; ?>
                    <?php if (!empty($p['image'])): ?><img class="product__img" src="<?= e(img_src($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.remove()"><?php endif; ?>
                </a>
                <div class="product__body">
                    <h3 class="product__title"><a href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>"><?= e($p['name']) ?></a></h3>
                    <div class="product__foot">
                        <span class="product__price"><?= price_html($p['price'], $cur) ?></span>
                        <?= product_buy_button($p) ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Pourquoi -->
    <h2 id="pourquoi">✨ <?= e($fr ? 'Pourquoi nos fonds d’écran' : 'Why our wallpapers') ?></h2>
    <div class="refband" style="margin:1rem 0">
        <div class="ref glass"><div class="big">HD</div><small><?= e($fr ? 'Haute définition' : 'High definition') ?></small></div>
        <div class="ref glass"><div class="big">0</div><small><?= e($fr ? 'Filigrane après achat' : 'Watermark after purchase') ?></small></div>
        <div class="ref glass"><div class="big">⚡</div><small><?= e($fr ? 'Livraison e-mail immédiate' : 'Instant email delivery') ?></small></div>
        <div class="ref glass"><div class="big">PNG·JPG·PDF</div><small><?= e($fr ? '3 formats' : '3 formats') ?></small></div>
    </div>

    <!-- FAQ -->
    <h2 id="faq">❓ <?= e($fr ? 'Questions fréquentes' : 'FAQ') ?></h2>
    <div class="faq-list" style="margin:1rem 0">
        <?php foreach ($faq as $qa): ?>
            <details class="glass" style="padding:1rem 1.2rem;border-radius:14px;margin:.6rem 0">
                <summary style="cursor:pointer;font-weight:700;font-size:1.05rem"><?= e($qa[0]) ?></summary>
                <p style="margin:.7rem 0 0;line-height:1.7;color:var(--muted,#cfc9dd)"><?= e($qa[1]) ?></p>
            </details>
        <?php endforeach; ?>
    </div>

    <!-- Maillage -->
    <h2 id="liens">🔗 <?= e($fr ? 'À explorer aussi' : 'Explore too') ?></h2>
    <div class="os-grid" style="margin-top:1rem">
        <a class="os-card glass" href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🎮 <?= e($fr ? 'GTA 6 : tout savoir' : 'GTA 6: everything') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🛍️ <?= e($fr ? 'La Boutique' : 'The Shop') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/galerie.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">🖼️ <?= e($fr ? 'Galerie fan-arts' : 'Fan-art gallery') ?></span></span><span class="os-card__arrow">→</span></a>
        <a class="os-card glass" href="<?= e(with_lang(url('pages/dossier.php'))) ?>"><span class="os-card__txt"><span class="os-card__name">📂 <?= e($fr ? 'Le Dossier Vice City' : 'The Vice City Files') ?></span></span><span class="os-card__arrow">→</span></a>
    </div>

    <p class="muted" style="font-size:.8rem;margin-top:1.6rem">
        <?= e($fr
            ? 'ViceHub X est un site de fans indépendant, sans lien avec Rockstar Games ni Take-Two Interactive. Visuels originaux inspirés de l’univers Vice City.'
            : 'ViceHub X is an independent fan site, not affiliated with Rockstar Games or Take-Two Interactive. Original visuals inspired by the Vice City universe.') ?>
    </p>
</article>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
