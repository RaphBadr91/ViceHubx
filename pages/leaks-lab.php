<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_leaks_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Leaks GTA VI classés par fiabilité : confirmé, probable, rumeur, analyse ou faux.'
    : 'GTA VI leaks ranked by reliability: confirmed, likely, rumor, analysis or fake.';
$leaks = get_articles(['category' => 'leaks', 'lang' => lang()]);
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🔬 ViceHub X</span>
    <h1><?= e(t('page_leaks_title')) ?></h1>
    <p class="muted" style="max-width:680px"><?= e(t('page_leaks_intro')) ?></p>

    <!-- Légende des badges de fiabilité -->
    <div class="glass reveal" style="border-radius:16px;padding:1rem 1.2rem;margin:1.4rem 0;display:flex;gap:.6rem;flex-wrap:wrap">
        <?php foreach (array_keys(badges()) as $k): ?>
            <?= badge_html($k) ?>
        <?php endforeach; ?>
    </div>

    <?php if ($leaks): ?>
    <div class="cards">
        <?php foreach ($leaks as $a): ?>
            <article class="card glass reveal">
                <?= media_html($a['image'] ?? '', '🕵️', $a['title']) ?>
                <div class="card__body">
                    <?= badge_html($a['badge']) ?>
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
