<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
// Statistiques vivantes
$nArticles = (int) db()->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
$nMembers  = (int) db()->query("SELECT COUNT(*) FROM users WHERE role IN ('member','contributor')")->fetchColumn();
$nPosts    = (int) db()->query('SELECT COUNT(*) FROM forum_posts')->fetchColumn();
$nProducts = (int) db()->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn();
$press_email = (string) (get_setting('contact_email', '') ?: 'presse@vicehubx.fr');

$SEO_TITLE = ($fr ? 'Presse & Partenariats' : 'Press & Partnerships') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'Kit média ViceHub X : audience, opportunités de partenariat, sponsoring et contact presse pour le média GTA VI n°1.'
    : 'ViceHub X media kit: audience, partnership opportunities, sponsoring and press contact.';
$SEO_OG_IMAGE = cdn_url('downtown.png');
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow">🤝 ViceHub X</span>
    <h1><?= $fr ? 'Presse & Partenariats' : 'Press & Partnerships' ?></h1>
    <p class="muted" style="max-width:720px"><?= $fr
        ? 'ViceHub X est un média indépendant 100% dédié à GTA VI et Vice City, avec une communauté active et une expérience immersive premium. Travaillons ensemble.'
        : 'ViceHub X is an independent media fully dedicated to GTA VI and Vice City, with an active community and a premium immersive experience.' ?></p>

    <div class="refband" style="margin-top:1.6rem">
        <div class="ref glass"><div class="big"><?= $nArticles ?>+</div><small><?= $fr ? 'articles publiés' : 'published articles' ?></small></div>
        <div class="ref glass"><div class="big"><?= $nMembers ?>+</div><small><?= $fr ? 'membres' : 'members' ?></small></div>
        <div class="ref glass"><div class="big"><?= $nPosts ?>+</div><small><?= $fr ? 'messages forum' : 'forum posts' ?></small></div>
        <div class="ref glass"><div class="big">4</div><small><?= $fr ? 'langues' : 'languages' ?></small></div>
    </div>

    <div class="lore-block glass" style="margin-top:1.8rem">
        <h2>📣 <?= $fr ? 'Opportunités de partenariat' : 'Partnership opportunities' ?></h2>
        <div class="lore-grid">
            <div><h3>📰 <?= $fr ? 'Articles sponsorisés' : 'Sponsored articles' ?></h3><p class="muted"><?= $fr ? 'Contenu éditorial natif, transparent et de qualité.' : 'Native, transparent editorial content.' ?></p></div>
            <div><h3>🛍️ <?= $fr ? 'Placement boutique' : 'Shop placement' ?></h3><p class="muted"><?= $fr ? 'Vos produits gaming mis en avant auprès des fans.' : 'Your gaming products featured to fans.' ?></p></div>
            <div><h3>🎯 <?= $fr ? 'Affiliation & deals' : 'Affiliate & deals' ?></h3><p class="muted"><?= $fr ? 'Campagnes affiliées (Amazon, éditeurs, hardware).' : 'Affiliate campaigns (Amazon, publishers, hardware).' ?></p></div>
            <div><h3>📺 <?= $fr ? 'Display & sponsoring' : 'Display & sponsoring' ?></h3><p class="muted"><?= $fr ? 'Bannières, habillages et événements communautaires.' : 'Banners, takeovers and community events.' ?></p></div>
        </div>
    </div>

    <div class="lore-block glass" style="margin-top:1.4rem">
        <h2>🎨 <?= $fr ? 'Kit média' : 'Media kit' ?></h2>
        <p class="muted"><?= $fr ? 'Nom officiel, logo et univers visuel disponibles sur demande.' : 'Official name, logo and brand assets available on request.' ?></p>
        <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-top:.6rem">
            <span class="logo" style="font-size:1.6rem">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span></span>
            <a class="btn btn--ghost" href="<?= e(asset('img/icon-512.png')) ?>" download>⬇️ <?= $fr ? 'Logo (PNG)' : 'Logo (PNG)' ?></a>
        </div>
    </div>

    <div class="banner glass" style="text-align:center;margin-top:1.6rem">
        <h2><?= $fr ? 'Discutons de votre projet' : 'Let’s talk' ?></h2>
        <p class="muted"><?= $fr ? 'Contact presse & partenariats :' : 'Press & partnerships contact:' ?></p>
        <a class="btn btn--primary" href="mailto:<?= e($press_email) ?>?subject=Partenariat%20ViceHub%20X"><?= e($press_email) ?></a>
        <p class="muted" style="font-size:.82rem;margin-top:.8rem"><?= $fr ? 'Ou via la page' : 'Or via the' ?> <a class="link-all" href="<?= e(with_lang(url('pages/contact.php'))) ?>"><?= $fr ? 'Contact' : 'Contact page' ?></a>.</p>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
