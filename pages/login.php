<?php
require_once dirname(__DIR__) . '/config/config.php';
if (is_logged_in()) {
    redirect(with_lang(url('pages/account.php')));
}
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Session expirée, réessayez.';
    } else {
        $user = login_attempt((string) ($_POST['login'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            redirect(with_lang(url(in_array($user['role'], ['admin', 'editor'], true) ? 'admin/dashboard.php' : 'pages/account.php')));
        }
        $error = lang() === 'fr' ? 'Identifiants invalides.' : 'Invalid credentials.';
    }
}
$SEO_TITLE = (lang() === 'fr' ? 'Connexion' : 'Login') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:460px">
    <span class="eyebrow">👤 ViceHub X</span>
    <h1><?= lang() === 'fr' ? 'Connexion' : 'Login' ?></h1>
    <?php if ($error): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form glass" style="padding:1.6rem;border-radius:18px;max-width:none">
        <?= csrf_field() ?>
        <div><label><?= lang() === 'fr' ? 'Identifiant ou e-mail' : 'Username or email' ?></label>
            <input type="text" name="login" required autofocus autocomplete="username"></div>
        <div><label><?= lang() === 'fr' ? 'Mot de passe' : 'Password' ?></label>
            <input type="password" name="password" required autocomplete="current-password"></div>
        <button class="btn btn--primary" type="submit" style="justify-content:center"><?= lang() === 'fr' ? 'Se connecter' : 'Sign in' ?></button>
    </form>
    <p class="muted" style="text-align:center;margin-top:1rem">
        <?= lang() === 'fr' ? 'Pas encore de compte ?' : 'No account yet?' ?>
        <a class="link-all" href="<?= e(with_lang(url('pages/register.php'))) ?>"><?= lang() === 'fr' ? 'Créer un compte' : 'Sign up' ?></a>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
