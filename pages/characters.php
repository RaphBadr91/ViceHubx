<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_characters_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Fiches personnages GTA VI : rôles, descriptions, théories et liens.'
    : 'GTA VI character sheets: roles, descriptions, theories and links.';
$chars = get_characters();
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🎭 ViceHub X</span>
    <h1><?= e(t('page_characters_title')) ?></h1>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($chars as $c): ?>
            <article class="card glass reveal">
                <div class="card__media"><span aria-hidden="true">🕶️</span></div>
                <div class="card__body">
                    <span class="card__cat"><?= e(t('char_role')) ?> · <?= e($c['role']) ?></span>
                    <h3 class="card__title"><?= e($c['name']) ?></h3>
                    <p class="card__excerpt"><?= e($c['description']) ?></p>
                    <p class="muted" style="font-size:.85rem"><strong><?= e(t('char_theories')) ?> :</strong> <?= e($c['theories']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
