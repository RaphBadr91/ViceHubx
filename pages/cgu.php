<?php
/**
 * ViceHub X — Conditions Générales d'Utilisation / Terms of Service (bilingue).
 * URL propre : /cgu
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$SEO_TITLE = ($fr ? "Conditions d'utilisation" : 'Terms of Service') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? "Conditions générales d'utilisation du site ViceHub X, média indépendant non officiel."
    : 'Terms of Service for ViceHub X, an independent unofficial media outlet.';
require ROOT_PATH . '/includes/header.php';
$updated = '16/07/2026';
?>
<section class="section" style="max-width:820px">
    <span class="eyebrow">📜 ViceHub X</span>
    <h1><?= $fr ? "Conditions Générales d'Utilisation" : 'Terms of Service' ?></h1>
    <p class="muted" style="font-size:.9rem"><?= $fr ? 'Dernière mise à jour :' : 'Last updated:' ?> <?= e($updated) ?></p>

    <?php if ($fr): ?>
        <h2>1. Objet</h2>
        <p>Les présentes conditions régissent l'utilisation du site ViceHub X, média communautaire indépendant dédié à GTA VI et Vice City. En utilisant le site, vous les acceptez.</p>

        <h2>2. Indépendance</h2>
        <p>ViceHub X est <strong>indépendant et non officiel</strong>. Les marques citées (Grand Theft Auto, Rockstar Games, Take-Two Interactive) appartiennent à leurs propriétaires respectifs. Nous ne sommes ni affiliés, ni partenaires, ni sponsorisés par ces entités.</p>

        <h2>3. Contenu</h2>
        <p>Les articles, leaks, rumeurs et analyses sont fournis à titre <strong>informatif et de divertissement</strong> et ne constituent pas une information officielle. Nous nous efforçons de distinguer clairement les faits confirmés des rumeurs.</p>

        <h2>4. Compte & comportement</h2>
        <p>Vous êtes responsable de votre compte et des contenus que vous publiez (forum, commentaires). Sont interdits : propos haineux, illégaux, spam, contrefaçon, ou toute atteinte aux droits d'autrui. Nous pouvons modérer ou supprimer un contenu ou un compte ne respectant pas ces règles.</p>

        <h2>5. Réseaux sociaux</h2>
        <p>Nous publions nos propres contenus (articles, vidéos) sur nos comptes Facebook, Instagram et TikTok via leurs API officielles, dans le respect des conditions de ces plateformes.</p>

        <h2>6. Boutique & liens partenaires</h2>
        <p>Certaines pages contiennent des liens d'affiliation ; nous pouvons percevoir une commission sans surcoût pour vous. Les achats sont traités de façon sécurisée via Stripe et/ou nos marchands partenaires.</p>

        <h2>7. Propriété intellectuelle</h2>
        <p>Les éléments propres à ViceHub X (nom, logo, textes originaux) sont protégés. Toute reproduction non autorisée est interdite.</p>

        <h2>8. Responsabilité</h2>
        <p>Le site est fourni « en l'état ». Nous ne garantissons pas l'exactitude des rumeurs ni la disponibilité continue du service, et déclinons toute responsabilité pour les dommages indirects.</p>

        <h2>9. Modifications</h2>
        <p>Nous pouvons mettre à jour ces conditions à tout moment. La date de mise à jour figure en haut de page.</p>

        <h2>10. Contact</h2>
        <p>Pour toute question, contactez-nous via la <a class="link-all" href="<?= e(with_lang(url('pages/contact.php'))) ?>">page Contact</a>. Droit applicable : France.</p>
    <?php else: ?>
        <h2>1. Purpose</h2>
        <p>These terms govern the use of the ViceHub X website, an independent community media outlet dedicated to GTA VI and Vice City. By using the site, you accept them.</p>

        <h2>2. Independence</h2>
        <p>ViceHub X is <strong>independent and unofficial</strong>. Trademarks mentioned (Grand Theft Auto, Rockstar Games, Take-Two Interactive) belong to their respective owners. We are not affiliated with, partnered with, or sponsored by these entities.</p>

        <h2>3. Content</h2>
        <p>Articles, leaks, rumors and analysis are provided for <strong>informational and entertainment purposes</strong> and are not official information. We strive to clearly separate confirmed facts from rumors.</p>

        <h2>4. Account & conduct</h2>
        <p>You are responsible for your account and the content you post (forum, comments). Prohibited: hateful or illegal content, spam, counterfeiting, or any infringement of others' rights. We may moderate or remove content or accounts that break these rules.</p>

        <h2>5. Social media</h2>
        <p>We publish our own content (articles, videos) to our Facebook, Instagram and TikTok accounts via their official APIs, in compliance with those platforms' terms.</p>

        <h2>6. Shop & partner links</h2>
        <p>Some pages contain affiliate links; we may earn a commission at no extra cost to you. Purchases are processed securely via Stripe and/or our partner merchants.</p>

        <h2>7. Intellectual property</h2>
        <p>Elements owned by ViceHub X (name, logo, original texts) are protected. Unauthorized reproduction is prohibited.</p>

        <h2>8. Liability</h2>
        <p>The site is provided "as is". We do not guarantee the accuracy of rumors or continuous availability, and disclaim liability for indirect damages.</p>

        <h2>9. Changes</h2>
        <p>We may update these terms at any time. The update date is shown at the top of the page.</p>

        <h2>10. Contact</h2>
        <p>For any question, contact us via the <a class="link-all" href="<?= e(with_lang(url('pages/contact.php'))) ?>">Contact page</a>. Governing law: France.</p>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
