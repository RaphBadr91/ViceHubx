<?php
/**
 * ViceHub X — Connexion admin (sessions + bcrypt + CSRF).
 */
require_once dirname(__DIR__) . '/config/config.php';

// Déjà connecté ? -> dashboard
if (is_logged_in()) {
    redirect(url('admin/dashboard.php'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Session expirée, réessayez.';
    } elseif (!throttle_ok('admin_login')) {
        $error = 'Trop de tentatives. Réessaie dans quelques minutes.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            throttle_clear('admin_login');
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            if (!empty($_POST['remember'])) { set_remember_cookie((int) $user['id']); }
            redirect(url('admin/dashboard.php'));
        }
        throttle_hit('admin_login');
        $error = t('admin_bad_login');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e(t('admin_login')) ?> — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<div class="admin-login-wrap">
    <div class="admin-card glass">
        <a class="logo" href="<?= e(url('index.php')) ?>" style="display:block;text-align:center;margin-bottom:1.2rem">
            Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span>
        </a>
        <h1 style="text-align:center;font-size:1.3rem"><?= e(t('admin_login')) ?></h1>
        <?php if ($error): ?>
            <div class="alert alert--err"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" class="form" style="max-width:none">
            <?= csrf_field() ?>
            <div>
                <label><?= e(t('admin_user')) ?></label>
                <input type="text" name="username" required autofocus autocomplete="username">
            </div>
            <div>
                <label><?= e(t('admin_pass')) ?></label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;color:var(--muted);cursor:pointer">
                <input type="checkbox" name="remember" value="1" checked style="width:auto"> Rester connecté (30 jours)
            </label>
            <button class="btn btn--primary" type="submit" style="justify-content:center"><?= e(t('admin_signin')) ?></button>
        </form>
    </div>
</div>
</body>
</html>
