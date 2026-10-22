<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = (lang() === 'fr' ? 'Véhicules GTA 6 : voitures, motos et bateaux' : 'GTA 6 Vehicles: cars, bikes & boats') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Fiches véhicules GTA VI : type, vitesse estimée, utilité et rareté.'
    : 'GTA VI vehicle sheets: type, estimated speed, use and rarity.';
$vehicles = get_vehicles();
$rarity_labels = lang() === 'fr'
    ? ['common' => 'Commun', 'rare' => 'Rare', 'epic' => 'Épique', 'legendary' => 'Légendaire']
    : ['common' => 'Common', 'rare' => 'Rare', 'epic' => 'Epic', 'legendary' => 'Legendary'];
$SEO_OG_IMAGE = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20261022_195953_5c52e992-bf66-4413-b7f2-27c48cd71828_min.webp';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow"><?= vhx_icon('car') ?> ViceHub X</span>
    <h1><?= e(t('page_vehicles_title')) ?></h1>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($vehicles as $v): ?>
            <article class="card glass reveal">
                <?= media_html($v['image'] ?? '', '🏎️') ?>
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
