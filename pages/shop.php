<?php
require_once dirname(__DIR__) . '/config/config.php';

$cats = product_categories();
$active_cat = isset($_GET['cat']) ? trim((string) $_GET['cat']) : 'all';
if ($active_cat !== 'all' && !isset($cats[$active_cat])) {
    $active_cat = 'all';
}
$products = get_products($active_cat === 'all' ? null : $active_cat);

$SEO_TITLE = ($active_cat === 'all' ? t('page_shop_title') : ($cats[$active_cat] ?? t('page_shop_title'))) . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Boutique ViceHub X : affiches GTA VI générées par IA, jeux, consoles, vêtements et goodies. Sélection officielle de fans.'
    : 'ViceHub X shop: AI-generated GTA VI posters, games, consoles, apparel and goodies. Official fan picks.';

$cat_emoji = ['poster' => '🖼️', 'wallpaper' => '🖥️', 'game' => '🎮', 'console' => '🕹️', 'apparel' => '👕', 'accessory' => '🎧', 'collectible' => '🏆'];

// JSON-LD : liste de produits
$ld_items = [];
foreach ($products as $i => $p) {
    $item = [
        '@type'    => 'Product',
        'name'     => $p['name'],
        'category' => $cats[$p['category']] ?? $p['category'],
    ];
    if (!empty($p['description'])) {
        $item['description'] = $p['description'];
    }
    if (!empty($p['image'])) {
        $item['image'] = url(ltrim($p['image'], '/'));
    }
    if ($p['price'] !== null && $p['price'] !== '') {
        $item['offers'] = [
            '@type'         => 'Offer',
            'price'         => number_format((float) $p['price'], 2, '.', ''),
            'priceCurrency' => active_currency(),
            'availability'  => 'https://schema.org/InStock',
            'url'           => $p['url'],
        ];
    }
    $ld_items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'item' => $item];
}
$JSONLD = [
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => $SEO_TITLE,
    'numberOfItems'   => count($products),
    'itemListElement' => $ld_items,
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🛍️ ViceHub X</span>
    <h1><?= e(t('page_shop_title')) ?></h1>
    <p class="muted" style="max-width:720px"><?= e(t('shop_intro')) ?></p>

    <div class="shop-filters" role="tablist" aria-label="<?= e(t('page_shop_title')) ?>">
        <a class="chip<?= $active_cat === 'all' ? ' chip--on' : '' ?>" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= lang() === 'fr' ? 'Tout' : 'All' ?></a>
        <?php foreach ($cats as $key => $label): ?>
            <a class="chip<?= $active_cat === $key ? ' chip--on' : '' ?>" href="<?= e(with_lang(url('pages/shop.php?cat=' . $key))) ?>">
                <?= ($cat_emoji[$key] ?? '•') . ' ' . e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$products): ?>
        <p class="muted" style="margin-top:2rem"><?= e(t('no_content')) ?></p>
    <?php else: ?>
    <div class="shop-grid">
        <?php foreach ($products as $p): ?>
            <article class="product glass reveal">
                <a class="product__media" href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>">
                    <?php if (!empty($p['badge'])): ?><span class="product__badge"><?= e($p['badge']) ?></span><?php endif; ?>
                    <span class="card__emoji" aria-hidden="true"><?= $cat_emoji[$p['category']] ?? '🛍️' ?></span>
                    <?php if (!empty($p['image'])): ?>
                        <img class="product__img" src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                </a>
                <div class="product__body">
                    <span class="product__cat"><?= e($cats[$p['category']] ?? $p['category']) ?></span>
                    <h3 class="product__title">
                        <a href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>"><?= e($p['name']) ?></a>
                    </h3>
                    <p class="product__desc"><?= e($p['description']) ?></p>
                    <div class="product__foot">
                        <span class="product__price"><?= price_html($p['price'], active_currency()) ?></span>
                        <?= product_buy_button($p) ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p class="muted shop-disclosure">
        <?= lang() === 'fr'
            ? '🔗 Liens partenaires / affiliés. ViceHub X peut percevoir une commission sans surcoût pour vous. Les achats, paiements et livraisons sont gérés par nos partenaires (Amazon, boutique d’impression à la demande).'
            : '🔗 Partner / affiliate links. ViceHub X may earn a commission at no extra cost to you. Purchases, payments and shipping are handled by our partners (Amazon, print-on-demand store).' ?>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
