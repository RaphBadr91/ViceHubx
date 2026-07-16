<?php
/**
 * ViceHub X — Politique de confidentialité / Privacy Policy (bilingue).
 * URL propre : /confidentialite
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$SEO_TITLE = ($fr ? 'Politique de confidentialité' : 'Privacy Policy') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'Politique de confidentialité de ViceHub X : données collectées, cookies, RGPD et vos droits.'
    : 'ViceHub X Privacy Policy: data collected, cookies, GDPR and your rights.';
require ROOT_PATH . '/includes/header.php';
$updated = '16/07/2026';
?>
<section class="section" style="max-width:820px">
    <span class="eyebrow">🔒 ViceHub X</span>
    <h1><?= $fr ? 'Politique de confidentialité' : 'Privacy Policy' ?></h1>
    <p class="muted" style="font-size:.9rem"><?= $fr ? 'Dernière mise à jour :' : 'Last updated:' ?> <?= e($updated) ?></p>

    <?php if ($fr): ?>
        <p>ViceHub X (« nous ») est un média communautaire indépendant et non officiel dédié à GTA VI et Vice City. Cette politique explique quelles données nous collectons, pourquoi, et comment exercer vos droits.</p>

        <h2>1. Données que nous collectons</h2>
        <ul>
            <li><strong>Compte membre / forum</strong> : identifiant, adresse e-mail, contenus que vous publiez.</li>
            <li><strong>Newsletter</strong> : adresse e-mail (si vous vous inscrivez).</li>
            <li><strong>Commandes boutique</strong> : e-mail et informations de livraison, traités via notre prestataire de paiement <strong>Stripe</strong> (nous ne stockons pas vos données de carte).</li>
            <li><strong>Mesure d'audience</strong> : données de navigation anonymisées (IP tronquée) via Google Analytics, uniquement après votre consentement aux cookies.</li>
            <li><strong>Données techniques</strong> : cookies de session nécessaires au fonctionnement du site.</li>
        </ul>

        <h2>2. Pourquoi nous les utilisons</h2>
        <p>Pour fournir et sécuriser le site, gérer votre compte et vos commandes, vous envoyer la newsletter (si vous y êtes inscrit), et améliorer nos contenus. Nous ne <strong>vendons jamais</strong> vos données.</p>

        <h2>3. Partage avec des tiers</h2>
        <p>Nous partageons des données uniquement avec les prestataires nécessaires : Stripe (paiement), notre service d'e-mail, Google Analytics (audience). Nous publions nos propres contenus sur Facebook, Instagram et TikTok via leurs API officielles ; aucune donnée personnelle de nos visiteurs n'est transmise à ces plateformes à cette occasion.</p>

        <h2>4. Cookies</h2>
        <p>Les cookies de mesure d'audience ne sont déposés qu'après votre acceptation via le bandeau de consentement. Vous pouvez refuser sans que cela n'empêche la consultation du site.</p>

        <h2>5. Conservation</h2>
        <p>Nous conservons vos données le temps nécessaire à la finalité concernée (compte actif, obligations légales pour les commandes). Vous pouvez demander leur suppression à tout moment.</p>

        <h2>6. Vos droits (RGPD)</h2>
        <p>Vous disposez d'un droit d'accès, de rectification, d'effacement, de limitation, d'opposition et de portabilité de vos données. Pour les exercer, contactez-nous via notre <a class="link-all" href="<?= e(with_lang(url('pages/contact.php'))) ?>">page Contact</a>.</p>

        <h2>7. Sécurité & mineurs</h2>
        <p>Les données sont stockées de façon sécurisée. Le site n'est pas destiné aux enfants de moins de 13 ans ; nous ne collectons pas sciemment leurs données.</p>

        <h2>8. Contact</h2>
        <p>Pour toute question relative à cette politique, écrivez-nous via la <a class="link-all" href="<?= e(with_lang(url('pages/contact.php'))) ?>">page Contact</a>.</p>
    <?php else: ?>
        <p>ViceHub X ("we") is an independent, unofficial community media outlet dedicated to GTA VI and Vice City. This policy explains what data we collect, why, and how to exercise your rights.</p>

        <h2>1. Data we collect</h2>
        <ul>
            <li><strong>Member / forum account</strong>: username, email address, content you post.</li>
            <li><strong>Newsletter</strong>: email address (if you subscribe).</li>
            <li><strong>Shop orders</strong>: email and shipping details, processed via our payment provider <strong>Stripe</strong> (we never store your card data).</li>
            <li><strong>Analytics</strong>: anonymized browsing data (truncated IP) via Google Analytics, only after your cookie consent.</li>
            <li><strong>Technical data</strong>: session cookies required for the site to function.</li>
        </ul>

        <h2>2. Why we use it</h2>
        <p>To provide and secure the site, manage your account and orders, send the newsletter (if subscribed), and improve our content. We <strong>never sell</strong> your data.</p>

        <h2>3. Sharing with third parties</h2>
        <p>We share data only with the providers we need: Stripe (payments), our email service, Google Analytics (audience). We publish our own content to Facebook, Instagram and TikTok via their official APIs; no personal data of our visitors is sent to those platforms in the process.</p>

        <h2>4. Cookies</h2>
        <p>Analytics cookies are only set after you accept them via the consent banner. You may decline without losing access to the site.</p>

        <h2>5. Retention</h2>
        <p>We keep your data only as long as necessary for the relevant purpose (active account, legal obligations for orders). You may request deletion at any time.</p>

        <h2>6. Your rights (GDPR)</h2>
        <p>You have the right to access, rectify, erase, restrict, object to and port your data. To exercise them, contact us via our <a class="link-all" href="<?= e(with_lang(url('pages/contact.php'))) ?>">Contact page</a>.</p>

        <h2>7. Security & minors</h2>
        <p>Data is stored securely. The site is not intended for children under 13; we do not knowingly collect their data.</p>

        <h2>8. Contact</h2>
        <p>For any question about this policy, reach us via the <a class="link-all" href="<?= e(with_lang(url('pages/contact.php'))) ?>">Contact page</a>.</p>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
