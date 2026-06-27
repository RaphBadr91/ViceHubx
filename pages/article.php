<?php
require_once dirname(__DIR__) . '/config/config.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$article = $slug !== '' ? get_article_by_slug($slug) : null;

// Article introuvable ou non publié → 404 propre
if (!$article || $article['status'] !== 'published') {
    http_response_code(404);
    $SEO_TITLE = '404 — ' . APP_NAME;
    require ROOT_PATH . '/includes/header.php';
    echo '<section class="section" style="text-align:center"><h1>404</h1><p class="muted">'
        . e(lang() === 'fr' ? 'Article introuvable.' : 'Article not found.')
        . '</p><a class="btn btn--ghost" href="' . e(with_lang(url('pages/news.php'))) . '">'
        . e(t('nav_news')) . '</a></section>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}

// Corps : on n'autorise qu'un HTML simple (sécurité)
$safe_body = strip_tags(
    (string) $article['body'],
    '<p><h2><h3><h4><ul><ol><li><strong><em><blockquote><br>'
);
// Maillage interne automatique (liens vers pages piliers) — sûr, après strip_tags
$safe_body = internal_autolink($safe_body);
// Encart Boutique (CTA wallpaper) inséré au cœur de l'article pour inciter à l'achat
$safe_body = inject_after_paragraph($safe_body, 2, article_shop_cta('full'));

// Tags
$tstmt = db()->prepare(
    'SELECT t.name FROM tags t JOIN article_tags at ON at.tag_id = t.id WHERE at.article_id = ?'
);
$tstmt->execute([$article['id']]);
$tags = $tstmt->fetchAll(PDO::FETCH_COLUMN);

// Articles liés (même catégorie)
$related = get_articles(['category' => $article['category_slug'] ?? 'news', 'lang' => $article['lang'], 'limit' => 4]);
$related = array_values(array_filter($related, fn($r) => $r['id'] !== $article['id']));
$related = array_slice($related, 0, 3);

$SEO_TITLE    = $article['title'] . ' — ' . APP_NAME;
$SEO_DESC     = $article['excerpt'] ?: APP_NAME;
$SEO_OG_IMAGE = !empty($article['image']) ? img_src($article['image']) : asset('img/og-default.svg');

$JSONLD = [
    '@context' => 'https://schema.org',
    '@type'    => 'NewsArticle',
    'headline' => $article['title'],
    'description' => $article['excerpt'],
    'inLanguage' => $article['lang'],
    'datePublished' => date('c', strtotime($article['published_at'] ?: $article['created_at'])),
    'author'    => ['@type' => 'Organization', 'name' => 'ViceHub X'],
    'publisher' => ['@type' => 'Organization', 'name' => 'ViceHub X'],
];

require ROOT_PATH . '/includes/header.php';
?>
<article class="section" style="max-width:820px">
    <a class="link-all" href="<?= e(with_lang(url('pages/' . ($article['category_slug'] ?? 'news') . '.php'))) ?>">← <?= e($article['category_name'] ?? t('nav_news')) ?></a>

    <div style="margin:.8rem 0 1rem;display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
        <?= badge_html($article['badge']) ?>
        <span class="muted" style="font-size:.85rem"><?= e(fmt_date($article['published_at'])) ?></span>
    </div>

    <h1 style="font-size:clamp(1.9rem,5vw,3rem);margin:0 0 1rem;text-transform:none"><?= e($article['title']) ?></h1>
    <p class="muted" style="font-size:1.1rem;margin-bottom:1.6rem"><?= e($article['excerpt']) ?></p>

    <?php if (!empty($article['image'])): ?>
        <img src="<?= e(img_src($article['image'])) ?>" alt="<?= e($article['title']) ?>" loading="eager" style="width:100%;border-radius:18px;margin-bottom:1.6rem">
    <?php endif; ?>

    <div class="article-body"><?= $safe_body ?></div>

    <?php if ($tags): ?>
    <div style="margin-top:2rem;display:flex;gap:.5rem;flex-wrap:wrap">
        <?php foreach ($tags as $tag): ?>
            <span class="badge badge--analysis">#<?= e($tag) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?= article_shop_cta('inline') ?>

    <p class="muted" style="margin-top:2rem;font-size:.82rem;border-top:1px solid var(--glass-brd);padding-top:1rem">
        <?= e(t('legal_disclaimer')) ?>
    </p>
</article>

<?php if ($related): ?>
<section class="section">
    <div class="section-head"><h2><?= lang() === 'fr' ? 'À lire aussi' : 'Read next' ?></h2></div>
    <div class="cards">
        <?php foreach ($related as $r): ?>
            <a class="card glass reveal" href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($r['slug'])))) ?>">
                <div class="card__media"><span aria-hidden="true">🌆</span></div>
                <div class="card__body">
                    <span class="card__cat"><?= e($r['category_name'] ?? 'News') ?></span>
                    <h3 class="card__title"><?= e($r['title']) ?></h3>
                    <p class="card__excerpt"><?= e($r['excerpt']) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
