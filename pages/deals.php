<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_deals_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Deals Gaming : offres manettes, casques, consoles et VPN sélectionnées par ViceHub X.'
    : 'Gaming Deals: controllers, headsets, consoles and VPN offers curated by ViceHub X.';
$deals = get_deals();
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow"><?= vhx_icon('tag') ?> ViceHub X</span>
    <h1><?= e(t('page_deals_title')) ?></h1>
    <p class="muted" style="max-width:680px">
        <?= lang() === 'fr'
            ? 'Liens partenaires / affiliés. ViceHub X peut percevoir une commission sans surcoût pour vous.'
            : 'Partner / affiliate links. ViceHub X may earn a commission at no extra cost to you.' ?>
    </p>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($deals as $d): ?>
            <a class="deal glass reveal" href="<?= e($d['url']) ?>" rel="sponsored nofollow" target="_blank">
                <span class="tag"><?= e($d['platform']) ?> · <?= e($d['badge']) ?></span>
                <h3><?= e($d['title']) ?></h3>
                <p class="muted"><?= e($d['description']) ?></p>
                <span class="btn btn--ghost" style="align-self:flex-start"><?= e(t('read_more')) ?> →</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
