<?php
require_once dirname(__DIR__) . '/config/config.php';
$slug = trim((string) ($_GET['cat'] ?? ''));
$cat = $slug !== '' ? get_forum_category($slug) : null;
if (!$cat) {
    http_response_code(404);
    $SEO_TITLE = '404 — ' . APP_NAME;
    require ROOT_PATH . '/includes/header.php';
    echo '<section class="section"><h1>404</h1><p class="muted">' . (lang() === 'fr' ? 'Catégorie introuvable.' : 'Category not found.')
        . '</p><a class="btn btn--ghost" href="' . e(with_lang(url('pages/forum.php'))) . '">Forum</a></section>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}
$threads = get_threads((int) $cat['id']);
$SEO_TITLE = $cat['name'] . ' — ' . (lang() === 'fr' ? 'Forum' : 'Forum') . ' ' . APP_NAME;
$SEO_DESC  = $cat['description'] ?: $cat['name'];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <p class="breadcrumb muted"><a href="<?= e(with_lang(url('pages/forum.php'))) ?>">Forum</a> &rsaquo; <span><?= e($cat['name']) ?></span></p>
    <div class="section-head">
        <div><h1><?= e($cat['icon'] ?: '💬') ?> <?= e($cat['name']) ?></h1></div>
        <?php if (is_logged_in()): ?>
            <a class="btn btn--primary" href="<?= e(with_lang(url('pages/forum-new.php?cat=' . urlencode($cat['slug'])))) ?>">+ <?= lang() === 'fr' ? 'Nouveau sujet' : 'New topic' ?></a>
        <?php else: ?>
            <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? 'Connectez-vous pour participer' : 'Log in to post' ?></a>
        <?php endif; ?>
    </div>

    <?php if (!$threads): ?>
        <p class="muted"><?= lang() === 'fr' ? 'Aucun sujet pour le moment. Lancez la discussion !' : 'No topics yet. Start one!' ?></p>
    <?php else: ?>
        <div class="thread-list">
            <?php foreach ($threads as $t): ?>
                <a class="thread-row glass" href="<?= e(with_lang(url('pages/forum-thread.php?id=' . (int) $t['id']))) ?>">
                    <div class="thread-row__main">
                        <h3><?= $t['pinned'] ? '📌 ' : '' ?><?= e($t['title']) ?></h3>
                        <span class="muted"><?= lang() === 'fr' ? 'par' : 'by' ?> <?= e($t['display_name'] ?: $t['username'] ?: 'Anonyme') ?></span>
                    </div>
                    <div class="thread-row__stats">
                        <span><strong><?= (int) $t['reply_count'] ?></strong> <?= lang() === 'fr' ? 'msg' : 'posts' ?></span>
                        <span class="muted"><?= e(substr((string) $t['last_post_at'], 0, 16)) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
