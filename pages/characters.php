<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = (lang() === 'fr' ? 'Personnages GTA 6 : Jason, Lucia et le casting' : 'GTA 6 Characters: Jason, Lucia & full cast') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Fiches personnages GTA VI : rôles, descriptions, théories et liens.'
    : 'GTA VI character sheets: roles, descriptions, theories and links.';
$chars = get_characters();
$SEO_OG_IMAGE = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20261022_195950_543258b2-c15d-4a7e-a82e-1dc0ca9afb8f_min.webp';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow"><?= vhx_icon('user') ?> ViceHub X</span>
    <h1><?= e(t('page_characters_title')) ?></h1>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($chars as $c): ?>
            <article class="card glass reveal">
                <?= media_html(character_image($c['name']), '🕶️', (string) $c['name']) ?>
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
