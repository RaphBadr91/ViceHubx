<?php
require_once dirname(__DIR__) . '/config/config.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
// Consolidation anti-cannibalisation : si ce slug a été redirigé (301) vers un
// article maître, on redirige AVANT tout rendu (concentre l'autorité sur 1 URL).
if ($slug !== '') {
    $__to = redirect_target($slug);
    if ($__to !== '' && $__to !== $slug) {
        header('Location: ' . url('pages/article.php?slug=' . rawurlencode($__to)), true, 301);
        exit;
    }
}
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

// Une URL = UNE langue : l'article impose la langue de la page (nav, pied de page…)
// pour que les versions anglaises soient 100% anglaises (SEO + confort de lecture).
if (in_array($article['lang'], array_keys(available_languages()), true) && $article['lang'] !== lang()) {
    $GLOBALS['LANG_CODE'] = $article['lang'];
    $__lf = ROOT_PATH . '/lang/' . $article['lang'] . '.php';
    if (is_file($__lf)) { $GLOBALS['LANG'] = require $__lf; }
}

// Corps : on n'autorise qu'un HTML simple (sécurité)
$safe_body = strip_tags(
    (string) $article['body'],
    '<p><h2><h3><h4><ul><ol><li><strong><em><blockquote><br><table><thead><tbody><tr><th><td><figure><figcaption>'
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

// Titre & meta SEO : champ dédié (meta_title/meta_description) s'il est renseigné,
// sinon repli intelligent. La marque n'est ajoutée QUE si le titre est court, pour
// ne pas se faire tronquer par Google et garder le mot-clé visible (levier CTR).
$__mt = trim((string) ($article['meta_title'] ?? ''));
$SEO_TITLE = $__mt !== ''
    ? $__mt
    : ($article['title'] . (mb_strlen((string) $article['title']) <= 52 ? ' — ' . APP_NAME : ''));
$__md = trim((string) ($article['meta_description'] ?? ''));
$SEO_DESC = $__md !== '' ? $__md : ($article['excerpt'] ?: APP_NAME);
// Partage réseaux sociaux (WhatsApp, Facebook, Messenger…) : ces plateformes n'affichent
// PAS le WebP en aperçu. On sert donc la PHOTO DE L'ARTICLE via social-img.php, qui la
// convertit à la volée en JPEG aplati (compatible partout). Repli marque si pas d'image.
$SEO_OG_IMAGE = !empty($article['image'])
    ? 'social-img.php?id=' . (int) $article['id']
    : (cdn_url('brand-cover.png') ?: asset('img/og-default.svg'));

// URL absolue de l'article (pour og:type=article + schema enrichi)
$__base  = (defined('BASE_URL') && BASE_URL !== '') ? rtrim(BASE_URL, '/')
    : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$art_url = $__base . '/article/' . rawurlencode($article['slug']); // URL canonique propre (cohérence SEO)
$pub_iso = date('c', strtotime($article['published_at'] ?: $article['created_at']));
$img_abs = preg_match('#^https?://#', (string) $SEO_OG_IMAGE) ? $SEO_OG_IMAGE : $__base . '/' . ltrim((string) $SEO_OG_IMAGE, '/');

// hreflang : relie la VF et la VO anglaise (URL propres /article/<slug>) pour que
// Google serve la bonne version selon la langue du visiteur (portée maximale).
$__artUrl = fn(string $s): string => $__base . '/article/' . rawurlencode($s);
$HREFLANG_ALT = [];
try {
    if ($article['lang'] === 'fr') {
        $HREFLANG_ALT['fr'] = $__artUrl($article['slug']);
        $q = db()->prepare("SELECT slug FROM articles WHERE lang='en' AND source_id=? AND status='published' ORDER BY id ASC LIMIT 1");
        $q->execute([(int) $article['id']]);
        if ($s = $q->fetchColumn()) { $HREFLANG_ALT['en'] = $__artUrl((string) $s); }
    } elseif ($article['lang'] === 'en') {
        $HREFLANG_ALT['en'] = $__artUrl($article['slug']);
        if (!empty($article['source_id'])) {
            $q = db()->prepare("SELECT slug FROM articles WHERE id=? AND status='published' LIMIT 1");
            $q->execute([(int) $article['source_id']]);
            if ($s = $q->fetchColumn()) { $HREFLANG_ALT['fr'] = $__artUrl((string) $s); }
        }
    }
} catch (Throwable $e) {
    $HREFLANG_ALT = [];
}
if (count($HREFLANG_ALT) < 2) { $HREFLANG_ALT = []; } // pas de contrepartie → hreflang par défaut

$OG_TYPE      = 'article';
$OG_PUBLISHED = $pub_iso;
$OG_SECTION   = $article['category_name'] ?? 'News';

// Correspondance slug catégorie -> page réelle (évite les liens & breadcrumb 404 :
// les catégories « leaks » et « trailers » sont servies par leaks-lab.php / trailer-lab.php).
$__catMap  = ['leaks' => 'leaks-lab', 'trailers' => 'trailer-lab'];
$__catSlug = $article['category_slug'] ?? 'news';
$__catPage = $__catMap[$__catSlug] ?? $__catSlug;
$__catRel  = url('pages/' . $__catPage . '.php');
$__catAbs  = preg_match('#^https?://#', (string) $__catRel) ? $__catRel : $__base . '/' . ltrim((string) $__catRel, '/');

$JSONLD = ['@context' => 'https://schema.org', '@graph' => [
    [
        '@type'    => 'NewsArticle',
        '@id'      => $art_url . '#article',
        'headline' => mb_substr($article['title'], 0, 110),
        'description' => $article['excerpt'],
        'inLanguage'  => $article['lang'],
        'datePublished' => $pub_iso,
        'dateModified'  => (($article['updated_at'] ?? '') !== '') ? date('c', strtotime((string) $article['updated_at'])) : $pub_iso,
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
                'item' => $__catAbs],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title']],
        ],
    ],
]];

// FAQPage : si l'article contient une FAQ, on l'expose en données structurées
// (rich snippets Google + éligibilité AI Overview). Fort levier SEO.
$__faq = faq_from_html((string) $article['body']);
if (count($__faq) >= 2) {
    $JSONLD['@graph'][] = [
        '@type' => 'FAQPage',
        '@id'   => $art_url . '#faq',
        'mainEntity' => array_map(static fn($qa) => [
            '@type' => 'Question',
            'name'  => $qa['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']],
        ], $__faq),
    ];
}

require ROOT_PATH . '/includes/header.php';
?>
<article class="section" style="max-width:820px">
    <?php if (!empty($is_preview)): ?>
        <div style="background:linear-gradient(90deg,#ff9f1c,#ff2e88);color:#0d0018;font-weight:800;padding:.6rem 1rem;border-radius:12px;margin-bottom:1rem;text-align:center">
            👁️ APERÇU ADMIN — article « <?= e($article['status'] === 'pending' ? 'Programmé' : 'Brouillon') ?> », non visible par le public.
            <a href="<?= e(url('admin/articles.php')) ?>" style="color:#0d0018;text-decoration:underline">Retour à la gestion</a>
        </div>
    <?php endif; ?>
    <a class="link-all" href="<?= e(with_lang(url('pages/' . $__catPage . '.php'))) ?>">← <?= e($article['category_name'] ?? t('nav_news')) ?></a>

    <div style="margin:.8rem 0 1rem;display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
        <?= badge_html($article['badge']) ?>
        <span class="muted" style="font-size:.85rem"><?= e(fmt_date($article['published_at'])) ?></span>
    </div>

    <h1 style="font-size:clamp(1.9rem,5vw,3rem);margin:0 0 1rem;text-transform:none"><?= e($article['title']) ?></h1>
    <p class="muted" style="font-size:1.1rem;margin-bottom:1.6rem"><?= e($article['excerpt']) ?></p>

    <?php if (!empty($article['image'])): $__srcCredit = trim((string) ($article['source_credit'] ?? '')); ?>
        <figure style="position:relative;margin:0 0 1.6rem">
            <img src="<?= e(img_src($article['image'])) ?>" alt="<?= e($article['title']) ?>" width="1280" height="720" loading="eager" fetchpriority="high" decoding="async" style="width:100%;height:auto;aspect-ratio:16/9;object-fit:cover;border-radius:18px;display:block">
            <?php if ($__srcCredit !== ''): ?>
                <figcaption style="position:absolute;left:0;right:0;bottom:0;padding:.5rem .9rem;background:linear-gradient(transparent,rgba(0,0,0,.78));color:#fff;font-size:.78rem;border-radius:0 0 18px 18px;text-align:right">Source : <?= e($__srcCredit) ?></figcaption>
            <?php endif; ?>
        </figure>
    <?php endif; ?>

    <div class="article-body"><?= $safe_body ?></div>

    <?php if ($tags): ?>
    <div style="margin-top:2rem;display:flex;gap:.5rem;flex-wrap:wrap">
        <?php foreach ($tags as $tag): ?>
            <span class="badge badge--analysis">#<?= e($tag) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php $__shareUrl = rawurlencode((string) $canonical); $__shareTxt = rawurlencode((string) $article['title']); ?>
    <div class="article-share" style="margin-top:2rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
        <span class="muted" style="font-size:.85rem"><?= lang() === 'fr' ? 'Partager :' : 'Share:' ?></span>
        <a class="btn btn--ghost" style="padding:.35rem .7rem" href="https://twitter.com/intent/tweet?url=<?= $__shareUrl ?>&amp;text=<?= $__shareTxt ?>" target="_blank" rel="noopener" aria-label="X">𝕏</a>
        <a class="btn btn--ghost" style="padding:.35rem .7rem" href="https://www.facebook.com/sharer/sharer.php?u=<?= $__shareUrl ?>" target="_blank" rel="noopener" aria-label="Facebook">Facebook</a>
        <a class="btn btn--ghost" style="padding:.35rem .7rem" href="https://api.whatsapp.com/send?text=<?= $__shareTxt ?>%20<?= $__shareUrl ?>" target="_blank" rel="noopener" aria-label="WhatsApp">WhatsApp</a>
        <a class="btn btn--ghost" style="padding:.35rem .7rem" href="https://www.reddit.com/submit?url=<?= $__shareUrl ?>&amp;title=<?= $__shareTxt ?>" target="_blank" rel="noopener" aria-label="Reddit">Reddit</a>
        <button type="button" class="btn btn--ghost" style="padding:.35rem .7rem" onclick="vhxShare(this)" data-url="<?= e($canonical) ?>" data-title="<?= e($article['title']) ?>">🔗 <?= lang() === 'fr' ? 'Copier' : 'Copy' ?></button>
    </div>
    <script>function vhxShare(b){var u=b.getAttribute('data-url'),t=b.getAttribute('data-title');if(navigator.share){navigator.share({title:t,url:u}).catch(function(){});}else if(navigator.clipboard){navigator.clipboard.writeText(u);var o=b.innerHTML;b.textContent='✅';setTimeout(function(){b.innerHTML=o;},1500);}}</script>

    <?= article_shop_cta('inline') ?>

    <p class="muted" style="margin-top:2rem;font-size:.82rem;border-top:1px solid var(--glass-brd);padding-top:1rem">
        <?= e(t('legal_disclaimer')) ?>
    </p>
</article>

<?php if ($related): ?>
<section class="section">
    <div class="section-head"><h2><?= lang() === 'fr' ? 'À lire aussi' : 'Read next' ?></h2></div>
    <div class="cards">
        <?php $relScenes = ['downtown.png', 'night.png', 'beachlife.png', 'marina.png', 'artdeco.png', 'ocean-drive.png']; ?>
        <?php foreach ($related as $ri => $r):
            // Vraie image de l'article ; à défaut, une belle scène Vice City (jamais de simple emoji).
            $rimg = !empty($r['image']) ? $r['image'] : '/public/assets/img/scenes/' . $relScenes[$ri % count($relScenes)];
        ?>
            <a class="card glass reveal" href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($r['slug'])))) ?>">
                <?= media_html($rimg, '🌆', (string) $r['title']) ?>
                <div class="card__body">
                    <span class="card__cat"><?= e($r['category_name'] ?? 'News') ?></span>
                    <h3 class="card__title"><?= e($r['title']) ?></h3>
                    <p class="card__excerpt"><?= e(clean_ai_markers((string) $r['excerpt'])) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
