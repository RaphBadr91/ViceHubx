<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_legal_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Mentions légales de ViceHub X, média indépendant non officiel.'
    : 'Legal notice for ViceHub X, an independent unofficial media outlet.';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:820px">
    <span class="eyebrow">⚖️ ViceHub X</span>
    <h1><?= e(t('page_legal_title')) ?></h1>

    <div class="glass" style="border-radius:16px;padding:1.4rem 1.6rem;margin:1.2rem 0">
        <p class="disclaimer" style="font-weight:600;color:var(--gold)"><?= e(t('legal_disclaimer')) ?></p>
    </div>

    <?php if (lang() === 'fr'): ?>
        <h2>Éditeur</h2>
        <p class="muted">ViceHub X est un média communautaire indépendant. Toutes les marques citées (Grand Theft Auto, Rockstar Games, Take-Two Interactive) appartiennent à leurs propriétaires respectifs. ViceHub X n’est ni affilié, ni partenaire, ni sponsorisé par ces entités.</p>
        <h2>Contenu</h2>
        <p class="muted">Les leaks, rumeurs et analyses publiés sont fournis à titre informatif et de divertissement. Ils ne constituent pas une information officielle.</p>
        <h2>Affiliation</h2>
        <p class="muted">Certaines pages contiennent des liens partenaires. ViceHub X peut percevoir une commission, sans surcoût pour l’utilisateur.</p>
        <h2>Données</h2>
        <p class="muted">Les inscriptions à la newsletter et les commentaires sont stockés de manière sécurisée et ne sont jamais revendus.</p>
    <?php else: ?>
        <h2>Publisher</h2>
        <p class="muted">ViceHub X is an independent community media outlet. All trademarks mentioned (Grand Theft Auto, Rockstar Games, Take-Two Interactive) belong to their respective owners. ViceHub X is neither affiliated with, partnered with, nor sponsored by these entities.</p>
        <h2>Content</h2>
        <p class="muted">Leaks, rumors and analysis are provided for informational and entertainment purposes only. They are not official information.</p>
        <h2>Affiliation</h2>
        <p class="muted">Some pages contain partner links. ViceHub X may earn a commission at no extra cost to the user.</p>
        <h2>Data</h2>
        <p class="muted">Newsletter sign-ups and comments are stored securely and never resold.</p>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
