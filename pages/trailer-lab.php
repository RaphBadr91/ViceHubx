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

    <div style="margin-top:1.6rem">
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
