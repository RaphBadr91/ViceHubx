<?php
require_once dirname(__DIR__) . '/config/config.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$product = $slug !== '' ? get_product_by_slug($slug) : null;

if (!$product) {
    http_response_code(404);
    $SEO_TITLE = '404 — ' . APP_NAME;
    require ROOT_PATH . '/includes/header.php';
    echo '<section class="section" style="text-align:center"><h1>404</h1><p class="muted">'
        . e(lang() === 'fr' ? 'Produit introuvable.' : 'Product not found.')
        . '</p><a class="btn btn--ghost" href="' . e(with_lang(url('pages/shop.php'))) . '">'
        . e(t('nav_shop')) . '</a></section>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}

$cats = product_categories();
$cat_emoji = ['poster' => '🖼️', 'game' => '🎮', 'console' => '🕹️', 'apparel' => '👕', 'accessory' => '🎧', 'collectible' => '🏆'];
$related = array_values(array_filter(
    get_products($product['category']),
    fn($p) => $p['id'] !== $product['id']
));
$related = array_slice($related, 0, 3);

$SEO_TITLE    = $product['name'] . ' — ' . t('page_shop_title') . ' ' . APP_NAME;
$SEO_DESC     = $product['description'] ?: $product['name'];
$SEO_OG_IMAGE = !empty($product['image']) ? url(ltrim($product['image'], '/')) : asset('img/og-default.svg');

$JSONLD = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $product['name'],
    'description' => $product['description'] ?: $product['name'],
    'category'    => $cats[$product['category']] ?? $product['category'],
    'brand'       => ['@type' => 'Brand', 'name' => APP_NAME],
];
if (!empty($product['image'])) {
    $JSONLD['image'] = $SEO_OG_IMAGE;
}
if ($product['price'] !== null && $product['price'] !== '') {
    $JSONLD['offers'] = [
        '@type'         => 'Offer',
        'price'         => number_format((float) $product['price'], 2, '.', ''),
        'priceCurrency' => $product['currency'] ?: 'EUR',
        'availability'  => 'https://schema.org/InStock',
        'url'           => $product['url'],
        'seller'        => ['@type' => 'Organization', 'name' => $product['merchant'] ?: APP_NAME],
    ];
}

require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <p class="breadcrumb muted">
        <a href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= e(t('nav_shop')) ?></a>
        &rsaquo; <a href="<?= e(with_lang(url('pages/shop.php?cat=' . $product['category']))) ?>"><?= e($cats[$product['category']] ?? $product['category']) ?></a>
        &rsaquo; <span><?= e($product['name']) ?></span>
    </p>

    <div class="product-detail">
        <div class="product-detail__media glass">
            <?php if (!empty($product['badge'])): ?><span class="product__badge"><?= e($product['badge']) ?></span><?php endif; ?>
            <span class="card__emoji" aria-hidden="true" style="font-size:4rem"><?= $cat_emoji[$product['category']] ?? '🛍️' ?></span>
            <?php if (!empty($product['image'])): ?>
                <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" loading="eager" onerror="this.remove()">
            <?php endif; ?>
        </div>
        <div class="product-detail__info">
            <span class="product__cat"><?= e($cats[$product['category']] ?? $product['category']) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <?php if ($product['price'] !== null && $product['price'] !== ''): ?>
                <p class="product-detail__price"><?= price_html($product['price'], $product['currency']) ?></p>
            <?php endif; ?>
            <p class="product-detail__desc"><?= e($product['description']) ?></p>
            <a class="btn btn--primary btn--lg" href="<?= e($product['url']) ?>" target="_blank" rel="sponsored nofollow noopener">
                <?= e(t('shop_buy')) ?><?php if (!empty($product['merchant'])): ?> · <?= e($product['merchant']) ?><?php endif; ?> ↗
            </a>
            <p class="muted" style="font-size:.82rem;margin-top:1rem">
                <?= lang() === 'fr'
                    ? 'Lien partenaire. Achat, paiement et livraison gérés par le marchand. ViceHub X peut percevoir une commission.'
                    : 'Partner link. Purchase, payment and shipping handled by the merchant. ViceHub X may earn a commission.' ?>
            </p>
        </div>
    </div>

    <?php if ($related): ?>
    <div class="section-head" style="margin-top:3rem">
        <h2><?= lang() === 'fr' ? 'Vous aimerez aussi' : 'You may also like' ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <div class="shop-grid">
        <?php foreach ($related as $p): ?>
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
                    <div class="product__foot">
                        <span class="product__price"><?= price_html($p['price'], $p['currency']) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
