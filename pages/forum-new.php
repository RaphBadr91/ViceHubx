<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();
$slug = trim((string) ($_GET['cat'] ?? ''));
$cat = $slug !== '' ? get_forum_category($slug) : null;
if (!$cat) {
    redirect(with_lang(url('pages/forum.php')));
}
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Session expirée.'];
    } else {
        try {
            $tid = create_thread((int) $cat['id'], (int) current_user()['id'], (string) ($_POST['title'] ?? ''), (string) ($_POST['body'] ?? ''));
            redirect(with_lang(url('pages/forum-thread.php?id=' . $tid)));
        } catch (Throwable $ex) {
            $flash = ['err', $ex->getMessage()];
        }
    }
}
$SEO_TITLE = (lang() === 'fr' ? 'Nouveau sujet' : 'New topic') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:760px">
    <p class="breadcrumb muted">
        <a href="<?= e(with_lang(url('pages/forum.php'))) ?>">Forum</a> &rsaquo;
        <a href="<?= e(with_lang(url('pages/forum-category.php?cat=' . urlencode($cat['slug'])))) ?>"><?= e($cat['name']) ?></a>
    </p>
    <h1><?= lang() === 'fr' ? 'Nouveau sujet' : 'New topic' ?></h1>
    <?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>
    <form method="post" class="form glass" style="padding:1.6rem;border-radius:18px;max-width:none">
        <?= csrf_field() ?>
        <div><label><?= lang() === 'fr' ? 'Titre du sujet' : 'Topic title' ?> *</label>
            <input type="text" name="title" required maxlength="200" value="<?= e($_POST['title'] ?? '') ?>"></div>
        <div><label><?= lang() === 'fr' ? 'Votre message' : 'Your message' ?> *</label>
            <textarea name="body" required style="min-height:180px"><?= e($_POST['body'] ?? '') ?></textarea></div>
        <button class="btn btn--primary" type="submit"><?= lang() === 'fr' ? 'Créer le sujet' : 'Create topic' ?></button>
    </form>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
