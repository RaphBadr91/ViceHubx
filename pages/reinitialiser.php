<?php
require_once dirname(__DIR__) . '/config/config.php';
if (is_logged_in()) {
    redirect(with_lang(url('pages/account.php')));
}

// Jeton opaque unique (compat : accepte aussi l'ancien paramètre ?token=).
$token = (string) ($_GET['t'] ?? ($_POST['t'] ?? ($_GET['token'] ?? ($_POST['token'] ?? ''))));
$valid = verify_password_reset($token) !== null;
$done  = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Session expirée, réessayez.';
    } elseif (!$valid) {
        $error = lang() === 'fr' ? 'Lien invalide ou expiré. Refais une demande.' : 'Invalid or expired link. Please request a new one.';
    } elseif (strlen((string) ($_POST['password'] ?? '')) < 8) {
        $error = lang() === 'fr' ? 'Le mot de passe doit faire au moins 8 caractères.' : 'Password must be at least 8 characters.';
    } elseif (complete_password_reset($token, (string) $_POST['password'])) {
        $done = true;
    } else {
        $error = lang() === 'fr' ? 'Impossible de réinitialiser. Le lien a peut-être expiré.' : 'Could not reset. The link may have expired.';
    }
}

$SEO_TITLE = (lang() === 'fr' ? 'Nouveau mot de passe' : 'New password') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:460px">
    <span class="eyebrow">🔒 ViceHub X</span>
    <h1><?= lang() === 'fr' ? 'Nouveau mot de passe' : 'New password' ?></h1>

    <?php if ($done): ?>
        <div class="alert alert--ok"><?= lang() === 'fr' ? '✅ Mot de passe modifié ! Tu peux te connecter.' : '✅ Password updated! You can log in.' ?></div>
        <p style="text-align:center;margin-top:1rem"><a class="btn btn--primary" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? 'Se connecter' : 'Log in' ?></a></p>
    <?php elseif (!$valid): ?>
        <div class="alert alert--err"><?= lang() === 'fr'
            ? 'Lien invalide ou expiré. Les liens sont valables 1 heure.'
            : 'Invalid or expired link. Links are valid for 1 hour.' ?></div>
        <p class="muted" style="text-align:center;margin-top:1rem">
            <a class="link-all" href="<?= e(with_lang(url('pages/mot-de-passe-oublie.php'))) ?>"><?= lang() === 'fr' ? 'Refaire une demande' : 'Request a new link' ?></a>
        </p>
    <?php else: ?>
        <p class="muted"><?= lang() === 'fr' ? 'Choisis ton nouveau mot de passe.' : 'Choose your new password.' ?></p>
        <?php if ($error): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form glass" style="padding:1.6rem;border-radius:18px;max-width:none">
            <?= csrf_field() ?>
            <input type="hidden" name="t" value="<?= e($token) ?>">
            <div><label><?= lang() === 'fr' ? 'Nouveau mot de passe (8 caractères min.)' : 'New password (min. 8 chars)' ?></label>
                <input type="password" name="password" required minlength="8" autofocus autocomplete="new-password"></div>
            <button class="btn btn--primary" type="submit" style="justify-content:center"><?= lang() === 'fr' ? 'Enregistrer' : 'Save' ?></button>
        </form>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
