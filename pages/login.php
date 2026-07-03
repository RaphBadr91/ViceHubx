<?php
require_once dirname(__DIR__) . '/config/config.php';
if (is_logged_in()) {
    redirect(with_lang(url('pages/account.php')));
}
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Session expirée, réessayez.';
    } elseif (!throttle_ok('member_login')) {
        $error = lang() === 'fr' ? 'Trop de tentatives. Réessaie dans quelques minutes.' : 'Too many attempts. Try again in a few minutes.';
    } else {
        $user = login_attempt((string) ($_POST['login'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($user) {
            throttle_clear('member_login');
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            if (!empty($_POST['remember'])) { set_remember_cookie((int) $user['id']); }
            redirect(with_lang(url(in_array($user['role'], ['admin', 'editor'], true) ? 'admin/dashboard.php' : 'pages/account.php')));
        }
        throttle_hit('member_login');
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
        <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;color:var(--muted);cursor:pointer">
            <input type="checkbox" name="remember" value="1" checked style="width:auto"> <?= lang() === 'fr' ? 'Rester connecté (30 jours)' : 'Stay logged in (30 days)' ?>
        </label>
        <button class="btn btn--primary" type="submit" style="justify-content:center"><?= lang() === 'fr' ? 'Se connecter' : 'Sign in' ?></button>
    </form>
    <p class="muted" style="text-align:center;margin-top:.8rem">
        <a class="link-all" href="<?= e(with_lang(url('pages/mot-de-passe-oublie.php'))) ?>"><?= lang() === 'fr' ? 'Mot de passe oublié ?' : 'Forgot password?' ?></a>
    </p>
    <p class="muted" style="text-align:center;margin-top:.4rem">
        <?= lang() === 'fr' ? 'Pas encore de compte ?' : 'No account yet?' ?>
        <a class="link-all" href="<?= e(with_lang(url('pages/register.php'))) ?>"><?= lang() === 'fr' ? 'Créer un compte' : 'Sign up' ?></a>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
