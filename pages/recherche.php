<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$q = trim((string) ($_GET['q'] ?? ''));
$articles = $threads = $products = [];
if (mb_strlen($q) >= 2) {
    $like = '%' . $q . '%';
    $a = db()->prepare("SELECT a.title, a.slug, a.excerpt, a.image, c.name AS cat
        FROM articles a LEFT JOIN categories c ON c.id = a.category_id
        WHERE a.status='published' AND a.lang = ? AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.body LIKE ?)
        ORDER BY a.published_at DESC LIMIT 20");
    $a->execute([lang(), $like, $like, $like]);
    $articles = $a->fetchAll();
    $t = db()->prepare('SELECT id, title FROM forum_threads WHERE title LIKE ? ORDER BY last_post_at DESC LIMIT 15');
    $t->execute([$like]);
    $threads = $t->fetchAll();
    $p = db()->prepare("SELECT name, slug, image, category FROM products WHERE active=1 AND (name LIKE ? OR description LIKE ?) ORDER BY sort ASC LIMIT 12");
    $p->execute([$like, $like]);
    $products = $p->fetchAll();
}
$total = count($articles) + count($threads) + count($products);
$SEO_TITLE = ($q !== '' ? ('« ' . $q .' » — ') : '') . ($fr ? 'Recherche' : 'Search') . ' · ' . APP_NAME;
$SEO_DESC  = $fr ? 'Recherchez dans les news, guides, leaks, forum et boutique de ViceHub X.' : 'Search ViceHub X.';
$ROBOTS = 'noindex,follow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🔍 ViceHub X</span>
    <h1><?= $fr ? 'Recherche' : 'Search' ?></h1>
    <form method="get" class="search-page-form" action="<?= e(url('pages/recherche.php')) ?>">
        <?php if (lang() !== 'fr'): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" autofocus placeholder="<?= $fr ? 'News, leaks, véhicules, produits…' : 'News, leaks, vehicles…' ?>">
        <button class="btn btn--primary" type="submit"><?= $fr ? 'Rechercher' : 'Search' ?></button>
    </form>

    <?php if ($q === '' || mb_strlen($q) < 2): ?>
        <p class="muted" style="margin-top:1.4rem"><?= $fr ? 'Tape au moins 2 caractères.' : 'Type at least 2 characters.' ?></p>
    <?php elseif ($total === 0): ?>
        <p class="muted" style="margin-top:1.4rem"><?= $fr ? 'Aucun résultat pour' : 'No results for' ?> « <?= e($q) ?> ».</p>
    <?php else: ?>
        <p class="muted" style="margin:1.2rem 0"><?= $total ?> <?= $fr ? 'résultat(s) pour' : 'result(s) for' ?> « <strong><?= e($q) ?></strong> »</p>

        <?php if ($articles): ?>
        <h2>📰 <?= $fr ? 'Articles' : 'Articles' ?></h2>
        <div class="cards">
            <?php foreach ($articles as $a): ?>
                <article class="card glass reveal">
                    <a href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>" style="text-decoration:none;color:inherit">
                        <?= media_html($a['image'] ?? '', '📰', $a['title']) ?>
                        <div class="card__body"><span class="card__cat"><?= e($a['cat'] ?? '') ?></span><h3 class="card__title"><?= e($a['title']) ?></h3><p class="card__excerpt"><?= e($a['excerpt']) ?></p></div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($threads): ?>
        <h2 style="margin-top:2rem">💬 <?= $fr ? 'Forum' : 'Forum' ?></h2>
        <div class="thread-list">
            <?php foreach ($threads as $t): ?>
                <a class="thread-row glass" href="<?= e(with_lang(url('pages/forum-thread.php?id=' . (int) $t['id']))) ?>"><div class="thread-row__main"><h3><?= e($t['title']) ?></h3></div></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($products): ?>
        <h2 style="margin-top:2rem">🛍️ <?= $fr ? 'Boutique' : 'Shop' ?></h2>
        <div class="shop-grid">
            <?php $ce = ['poster'=>'🖼️','wallpaper'=>'🖥️','game'=>'🎮','console'=>'🕹️','apparel'=>'👕','accessory'=>'🎧','collectible'=>'🏆']; ?>
            <?php foreach ($products as $p): ?>
                <article class="product glass reveal"><a class="product__media" href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>"><span class="card__emoji"><?= $ce[$p['category']] ?? '🛍️' ?></span><?php if (!empty($p['image'])): ?><img class="product__img" src="<?= e(img_src($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.remove()"><?php endif; ?></a><div class="product__body"><h3 class="product__title"><a href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>"><?= e($p['name']) ?></a></h3></div></article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
