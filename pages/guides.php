<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_guides_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Guides GTA VI : prise en main, argent, conduite et astuces pour progresser vite.'
    : 'GTA VI guides: getting started, money, driving and tips to progress fast.';
$articles = get_articles(['category' => 'guides', 'lang' => lang()]);
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">📘 ViceHub X</span>
    <h1><?= e(t('page_guides_title')) ?></h1>
    <?php if ($articles): ?>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($articles as $a): ?>
            <article class="card glass reveal">
                <div class="card__media"><span aria-hidden="true">🧭</span></div>
                <div class="card__body">
                    <span class="card__cat"><?= e(t('nav_guides')) ?></span>
                    <h3 class="card__title"><a href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>"><?= e($a['title']) ?></a></h3>
                    <p class="card__excerpt"><?= e($a['excerpt']) ?></p>
                    <span class="card__date"><?= e(fmt_date($a['published_at'])) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="muted"><?= e(t('no_content')) ?></p>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
