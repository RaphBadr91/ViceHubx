<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_map_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Carte interactive stylisée non officielle de Vice City : quartiers et zones cliquables.'
    : 'Stylised unofficial interactive map of Vice City: clickable districts and zones.';
$zones = get_map_zones();
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🗺️ ViceHub X</span>
    <h1><?= e(t('page_map_title')) ?></h1>
    <p class="muted" style="max-width:680px"><?= e(t('page_map_intro')) ?></p>

    <div class="map-wrap glass" style="margin-top:1.6rem">
        <div class="map-grid" aria-hidden="true"></div>
        <div class="map-zones">
            <?php foreach ($zones as $z): ?>
                <button class="zone" aria-expanded="false">
                    <h3>📍 <?= e($z['name']) ?></h3>
                    <p><?= e($z['description']) ?></p>
                    <div class="zone-info"><?= e($z['info']) ?></div>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
