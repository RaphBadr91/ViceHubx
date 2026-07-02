<?php
require_once dirname(__DIR__) . '/config/config.php';
if (is_logged_in()) {
    redirect(with_lang(url('pages/account.php')));
}
$sent = false;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Session expirée, réessayez.';
    } elseif (!throttle_ok('pwreset', 5, 15)) {
        $error = lang() === 'fr' ? 'Trop de demandes. Réessaie dans quelques minutes.' : 'Too many requests. Try again in a few minutes.';
    } else {
        try {
            request_password_reset((string) ($_POST['email'] ?? ''));
            throttle_hit('pwreset');
        } catch (Throwable $e) { /* on n'expose rien */ }
        $sent = true; // message identique que l'e-mail existe ou non (anti-énumération)
    }
}
$SEO_TITLE = (lang() === 'fr' ? 'Mot de passe oublié' : 'Forgot password') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:460px">
    <span class="eyebrow">🔑 ViceHub X</span>
    <h1><?= lang() === 'fr' ? 'Mot de passe oublié' : 'Forgot password' ?></h1>
    <?php if ($sent): ?>
        <div class="alert alert--ok"><?= lang() === 'fr'
            ? 'Si un compte est associé à cette adresse, un e-mail contenant un lien de réinitialisation vient d\'être envoyé. Pense à vérifier tes spams. Le lien est valable 1 heure.'
            : 'If an account exists for this address, a reset link has been sent. Check your spam. The link is valid for 1 hour.' ?></div>
        <p class="muted" style="text-align:center;margin-top:1rem">
            <a class="link-all" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? '← Retour à la connexion' : '← Back to login' ?></a>
        </p>
    <?php else: ?>
        <p class="muted"><?= lang() === 'fr' ? 'Saisis ton e-mail : on t\'envoie un lien pour choisir un nouveau mot de passe.' : 'Enter your email: we\'ll send you a link to set a new password.' ?></p>
        <?php if ($error): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form glass" style="padding:1.6rem;border-radius:18px;max-width:none">
            <?= csrf_field() ?>
            <div><label>E-mail</label>
                <input type="email" name="email" required autofocus autocomplete="email"></div>
            <button class="btn btn--primary" type="submit" style="justify-content:center"><?= lang() === 'fr' ? 'Envoyer le lien' : 'Send the link' ?></button>
        </form>
        <p class="muted" style="text-align:center;margin-top:1rem">
            <a class="link-all" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? '← Retour à la connexion' : '← Back to login' ?></a>
        </p>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
