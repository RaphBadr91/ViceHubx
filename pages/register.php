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
        try {
            $id = register_user(
                (string) ($_POST['username'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                trim((string) ($_POST['display_name'] ?? ''))
            );
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            redirect(with_lang(url('pages/account.php')));
        } catch (Throwable $ex) {
            $error = $ex->getMessage();
        }
    }
}
$SEO_TITLE = (lang() === 'fr' ? 'Créer un compte' : 'Sign up') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:480px">
    <span class="eyebrow">👤 ViceHub X</span>
    <h1><?= lang() === 'fr' ? 'Rejoindre la communauté' : 'Join the community' ?></h1>
    <p class="muted"><?= lang() === 'fr' ? 'Créez un compte gratuit pour participer au forum et proposer des articles.' : 'Create a free account to join the forum and submit articles.' ?></p>
    <?php if ($error): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form glass" style="padding:1.6rem;border-radius:18px;max-width:none">
        <?= csrf_field() ?>
        <div><label><?= lang() === 'fr' ? 'Identifiant' : 'Username' ?></label>
            <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username"></div>
        <div><label><?= lang() === 'fr' ? 'Nom affiché (public)' : 'Display name (public)' ?></label>
            <input type="text" name="display_name" maxlength="80" value="<?= e($_POST['display_name'] ?? '') ?>"></div>
        <div><label>E-mail</label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email"></div>
        <div><label><?= lang() === 'fr' ? 'Mot de passe (8 caractères min.)' : 'Password (min. 8 chars)' ?></label>
            <input type="password" name="password" required minlength="8" autocomplete="new-password"></div>
        <button class="btn btn--primary" type="submit" style="justify-content:center"><?= lang() === 'fr' ? 'Créer mon compte' : 'Create account' ?></button>
    </form>
    <p class="muted" style="text-align:center;margin-top:1rem">
        <?= lang() === 'fr' ? 'Déjà inscrit ?' : 'Already a member?' ?>
        <a class="link-all" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? 'Se connecter' : 'Log in' ?></a>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
