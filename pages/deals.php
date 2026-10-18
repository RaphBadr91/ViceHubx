<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_deals_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Le meilleur matériel pour jouer à GTA 6 : consoles, manettes, casques, stockage, setup et goodies Vice City, sélectionnés par ViceHub X.'
    : 'The best gear to play GTA 6: consoles, controllers, headsets, storage, setup and Vice City goodies, hand-picked by ViceHub X.';
$deals = get_deals();
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow"><?= vhx_icon('tag') ?> ViceHub X</span>
    <h1><?= e(t('page_deals_title')) ?></h1>
    <p class="muted" style="max-width:720px">
        <?= lang() === 'fr'
            ? 'Notre sélection du meilleur matériel pour vivre GTA 6 à fond : consoles, manettes, casques, stockage, setup et goodies Vice City.'
            : 'Our hand-picked selection of the best gear to experience GTA 6: consoles, controllers, headsets, storage, setup and Vice City goodies.' ?>
    </p>
    <div class="cards" style="margin-top:1.5rem">
        <?php foreach ($deals as $d): ?>
            <a class="deal glass reveal" href="<?= e(amazon_url($d['url'])) ?>" rel="sponsored nofollow" target="_blank">
                <span class="tag"><?= e($d['platform']) ?><?= !empty($d['badge']) ? ' · ' . e($d['badge']) : '' ?></span>
                <h3><?= e($d['title']) ?></h3>
                <p class="muted"><?= e($d['description']) ?></p>
                <span class="btn btn--ghost" style="align-self:flex-start"><?= lang() === 'fr' ? 'Voir le produit' : 'View product' ?> →</span>
            </a>
        <?php endforeach; ?>
    </div>
    <!-- Mention légale discrète (obligatoire Amazon Associates + loi FR/UE sur la
         transparence des liens affiliés) : volontairement minimale et en bas de page. -->
    <p class="muted" style="max-width:720px;margin-top:2.2rem;font-size:.74rem;opacity:.6">
        <?= lang() === 'fr'
            ? 'En tant que Partenaire Amazon, ViceHub X réalise un bénéfice sur les achats remplissant les conditions requises.'
            : 'As an Amazon Associate, ViceHub X earns from qualifying purchases.' ?>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
