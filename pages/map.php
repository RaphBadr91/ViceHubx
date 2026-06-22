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

    <?php $map_url = trim((string) get_setting('map_url', 'https://map.stateofleonida.net/?map=vi&lat=3904.00&lng=-10452.00')); ?>
    <?php if ($map_url !== ''): ?>
    <div class="map-embed-head">
        <span class="badge badge--official"><?= lang() === 'fr' ? 'Carte interactive' : 'Interactive map' ?></span>
        <a class="btn btn--ghost" href="<?= e($map_url) ?>" target="_blank" rel="noopener"><?= lang() === 'fr' ? 'Ouvrir en plein écran' : 'Open fullscreen' ?> ↗</a>
    </div>
    <div class="map-embed glass">
        <iframe src="<?= e($map_url) ?>" title="Carte interactive GTA VI — Leonida"
                loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
        <noscript><p class="muted" style="padding:1rem"><?= lang() === 'fr' ? 'Activez JavaScript pour la carte interactive.' : 'Enable JavaScript for the interactive map.' ?></p></noscript>
    </div>
    <p class="muted" style="font-size:.82rem;margin:.6rem 0 2rem">
        <?= lang() === 'fr'
            ? 'Carte interactive fournie par la communauté (stateofleonida.net). Si elle ne s’affiche pas ici, utilisez « Ouvrir en plein écran ».'
            : 'Community-provided interactive map (stateofleonida.net). If it does not load here, use “Open fullscreen”.' ?>
    </p>
    <h2 style="margin-top:1rem"><?= lang() === 'fr' ? 'Quartiers en bref' : 'Districts at a glance' ?></h2>
    <?php endif; ?>

    <?php $map_img = is_file(ROOT_PATH . '/public/assets/img/scenes/map.png') ? asset('img/scenes/map.png') : ''; ?>
    <div class="map-wrap glass<?= $map_img ? ' has-map' : '' ?>" style="margin-top:1.6rem<?= $map_img ? ";--map:url('" . e($map_img) . "')" : '' ?>">
        <div class="map-canvas" aria-hidden="true"></div>
        <div class="map-grid" aria-hidden="true"></div>
        <div class="map-scan" aria-hidden="true"></div>
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
