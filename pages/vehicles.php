<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_vehicles_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Fiches véhicules GTA VI : type, vitesse estimée, utilité et rareté.'
    : 'GTA VI vehicle sheets: type, estimated speed, use and rarity.';
$vehicles = get_vehicles();
$rarity_labels = lang() === 'fr'
    ? ['common' => 'Commun', 'rare' => 'Rare', 'epic' => 'Épique', 'legendary' => 'Légendaire']
    : ['common' => 'Common', 'rare' => 'Rare', 'epic' => 'Epic', 'legendary' => 'Legendary'];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🚗 ViceHub X</span>
    <h1><?= e(t('page_vehicles_title')) ?></h1>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($vehicles as $v): ?>
            <article class="card glass reveal">
                <div class="card__media"><span aria-hidden="true">🏎️</span></div>
                <div class="card__body">
                    <span class="card__cat"><?= e($v['type']) ?></span>
                    <h3 class="card__title"><?= e($v['name']) ?></h3>
                    <table class="data-table" style="font-size:.88rem">
                        <tr><th><?= e(t('veh_speed')) ?></th><td><?= e($v['speed']) ?></td></tr>
                        <tr><th><?= e(t('veh_use')) ?></th><td><?= e($v['use_case']) ?></td></tr>
                        <tr><th><?= e(t('veh_rarity')) ?></th><td><?= e($rarity_labels[$v['rarity']] ?? $v['rarity']) ?></td></tr>
                    </table>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
