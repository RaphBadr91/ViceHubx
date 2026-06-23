<?php
require_once dirname(__DIR__) . '/config/config.php';
$posts = get_articles(['category' => 'blog', 'lang' => lang(), 'limit' => 30]);
$SEO_TITLE = 'Blog GTA6 — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Le blog ViceHub X : dossiers, analyses et actus autour de GTA VI et Vice City.'
    : 'The ViceHub X blog: features, analysis and news about GTA VI and Vice City.';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">📝 ViceHub X</span>
    <h1><?= lang() === 'fr' ? 'Le Blog' : 'The Blog' ?></h1>
    <p class="muted" style="max-width:680px"><?= lang() === 'fr'
        ? 'Dossiers, analyses et coulisses autour de GTA VI. Tout notre contenu éditorial au même endroit.'
        : 'Features, analysis and behind-the-scenes around GTA VI.' ?></p>

    <?php if (!$posts): ?>
        <p class="muted" style="margin-top:1.5rem"><?= e(t('no_content')) ?></p>
    <?php else: ?>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($posts as $a): ?>
            <article class="card glass reveal">
                <a href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>" style="text-decoration:none;color:inherit">
                    <?= media_html($a['image'] ?? '', '📝') ?>
                    <div class="card__body">
                        <span class="card__cat">Blog</span>
                        <h3 class="card__title"><?= e($a['title']) ?></h3>
                        <p class="card__excerpt"><?= e($a['excerpt']) ?></p>
                        <span class="link-all"><?= e(t('read_more')) ?> →</span>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
