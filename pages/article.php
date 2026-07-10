<?php
require_once dirname(__DIR__) . '/config/config.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$article = $slug !== '' ? get_article_by_slug($slug) : null;

// Aperçu admin : un administrateur connecté peut LIRE un article non publié
// (brouillon / programmé) avant de le mettre en ligne.
$is_preview = $article && $article['status'] !== 'published'
    && function_exists('is_admin') && is_logged_in() && is_admin();
if ($is_preview) {
    $ROBOTS = 'noindex, nofollow';
}

// Article introuvable ou non publié → 404 propre (sauf aperçu admin)
if (!$article || ($article['status'] !== 'published' && !$is_preview)) {
    http_response_code(404);
    $SEO_TITLE = '404 — ' . APP_NAME;
    $ROBOTS = 'noindex, follow';
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
// Sécurité : retire tout marqueur IA résiduel (===FIN===…) — invisible pour le lecteur.
$safe_body = clean_ai_markers($safe_body);
$article['excerpt'] = clean_ai_markers((string) ($article['excerpt'] ?? ''));
// Maillage interne automatique (liens vers pages piliers) — sûr, après strip_tags
$safe_body = internal_autolink($safe_body);
// PUBS INTERNES (3 à 6) réparties dans l'article : Boutique / Forum / Blog,
// toujours en rapport avec le sujet pour rester cohérent et monétiser sans gêner.
$safe_body = inject_internal_ads($safe_body, $article);

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
$SEO_OG_IMAGE = !empty($article['image']) ? img_src($article['image']) : (cdn_url('brand-cover.png') ?: asset('img/og-default.svg'));

// URL absolue de l'article (pour og:type=article + schema enrichi)
$__base  = (defined('BASE_URL') && BASE_URL !== '') ? rtrim(BASE_URL, '/')
    : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$art_url = $__base . '/pages/article.php?slug=' . urlencode($article['slug']);
$pub_iso = date('c', strtotime($article['published_at'] ?: $article['created_at']));
$img_abs = preg_match('#^https?://#', (string) $SEO_OG_IMAGE) ? $SEO_OG_IMAGE : $__base . '/' . ltrim((string) $SEO_OG_IMAGE, '/');

$OG_TYPE      = 'article';
$OG_PUBLISHED = $pub_iso;
$OG_SECTION   = $article['category_name'] ?? 'News';

$JSONLD = ['@context' => 'https://schema.org', '@graph' => [
    [
        '@type'    => 'NewsArticle',
        '@id'      => $art_url . '#article',
        'headline' => mb_substr($article['title'], 0, 110),
        'description' => $article['excerpt'],
        'inLanguage'  => $article['lang'],
        'datePublished' => $pub_iso,
        'dateModified'  => $pub_iso,
        'articleSection' => $article['category_name'] ?? 'News',
        'image'     => [$img_abs],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $art_url],
        'author'    => ['@type' => 'Organization', 'name' => 'ViceHub X', 'url' => $__base . '/'],
        'publisher' => ['@type' => 'Organization', 'name' => 'ViceHub X',
            'logo' => ['@type' => 'ImageObject', 'url' => cdn_url('brand-logo.png') ?: $img_abs]],
    ],
    [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $__base . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $article['category_name'] ?? 'News',
                'item' => $__base . '/pages/' . ($article['category_slug'] ?? 'news') . '.php'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title']],
        ],
    ],
]];

require ROOT_PATH . '/includes/header.php';
?>
<article class="section" style="max-width:820px">
    <?php if (!empty($is_preview)): ?>
        <div style="background:linear-gradient(90deg,#ff9f1c,#ff2e88);color:#0d0018;font-weight:800;padding:.6rem 1rem;border-radius:12px;margin-bottom:1rem;text-align:center">
            👁️ APERÇU ADMIN — article « <?= e($article['status'] === 'pending' ? 'Programmé' : 'Brouillon') ?> », non visible par le public.
            <a href="<?= e(url('admin/articles.php')) ?>" style="color:#0d0018;text-decoration:underline">Retour à la gestion</a>
        </div>
    <?php endif; ?>
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
