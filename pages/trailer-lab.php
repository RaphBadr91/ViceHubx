<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_trailer_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Analyse image par image des trailers GTA VI : timecodes, indices et importance.'
    : 'Frame-by-frame GTA VI trailer analysis: timecodes, clues and importance.';
$rows = get_trailer_analyses();
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🎬 ViceHub X</span>
    <h1><?= e(t('page_trailer_title')) ?></h1>
    <p class="muted" style="max-width:680px"><?= e(t('page_trailer_intro')) ?></p>

    <?php
    $official_trailers = [
        ['VQRLujxTm3c', lang() === 'fr' ? 'Bande-annonce officielle' : 'Official trailer'],
        ['EiQEBYDox_k', lang() === 'fr' ? 'Révélation de la jaquette officielle' : 'Official Cover Art Reveal Trailer'],
    ];
    ?>
    <h2 style="margin-top:2rem"><?= lang() === 'fr' ? 'Bandes-annonces officielles' : 'Official trailers' ?></h2>
    <div class="trailers-official">
        <?php foreach ($official_trailers as [$vid, $label]): ?>
            <figure class="vtrailer glass reveal">
                <div class="video-embed">
                    <iframe src="https://www.youtube-nocookie.com/embed/<?= e($vid) ?>?rel=0"
                            title="<?= e($label) ?>" loading="lazy" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
                <figcaption>
                    <span class="badge badge--official"><?= lang() === 'fr' ? 'Officiel' : 'Official' ?></span>
                    <?= e($label) ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>

    <h2 style="margin-top:2.4rem"><?= lang() === 'fr' ? 'Analyses image par image' : 'Frame-by-frame analysis' ?></h2>
    <div style="margin-top:1rem">
        <?php foreach ($rows as $row): ?>
            <div class="trailer-row glass reveal">
                <span class="timecode"><?= e($row['timecode']) ?></span>
                <div>
                    <strong><?= e($row['description']) ?></strong><br>
                    <span class="muted"><?= e(t('clue')) ?> : <?= e($row['clue']) ?></span>
                </div>
                <span class="impo" title="<?= e(t('importance')) ?> : <?= e(importance_label((int) $row['importance'])) ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="<?= $i <= (int) $row['importance'] ? 'on' : '' ?>"></i>
                    <?php endfor; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
