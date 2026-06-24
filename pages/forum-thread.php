<?php
require_once dirname(__DIR__) . '/config/config.php';
$id = (int) ($_GET['id'] ?? 0);
$thread = $id ? get_thread($id) : null;
if (!$thread) {
    http_response_code(404);
    $SEO_TITLE = '404 — ' . APP_NAME;
    require ROOT_PATH . '/includes/header.php';
    echo '<section class="section"><h1>404</h1><p class="muted">' . (lang() === 'fr' ? 'Sujet introuvable.' : 'Topic not found.')
        . '</p><a class="btn btn--ghost" href="' . e(with_lang(url('pages/forum.php'))) . '">Forum</a></section>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Session expirée.'];
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'reply' && is_logged_in() && !$thread['locked']) {
            try {
                add_post($id, (int) current_user()['id'], (string) ($_POST['body'] ?? ''));
                redirect(with_lang(url('pages/forum-thread.php?id=' . $id)) . '#bas');
            } catch (Throwable $ex) {
                $flash = ['err', $ex->getMessage()];
            }
        } elseif ($action === 'delete_post' && is_admin()) {
            db()->prepare('DELETE FROM forum_posts WHERE id = ?')->execute([(int) ($_POST['post_id'] ?? 0)]);
            redirect(with_lang(url('pages/forum-thread.php?id=' . $id)));
        } elseif ($action === 'delete_thread' && is_admin()) {
            db()->prepare('DELETE FROM forum_threads WHERE id = ?')->execute([$id]);
            redirect(with_lang(url('pages/forum-category.php?cat=' . urlencode($thread['cat_slug']))));
        } elseif ($action === 'toggle_lock' && is_admin()) {
            db()->prepare('UPDATE forum_threads SET locked = 1 - locked WHERE id = ?')->execute([$id]);
            redirect(with_lang(url('pages/forum-thread.php?id=' . $id)));
        }
    }
}

$posts = get_thread_posts($id);
$SEO_TITLE = $thread['title'] . ' — ' . (lang() === 'fr' ? 'Forum' : 'Forum') . ' ' . APP_NAME;
$SEO_DESC  = mb_substr(strip_tags($posts[0]['body'] ?? $thread['title']), 0, 160);
$role_badge = ['admin' => '👑', 'editor' => '📝', 'contributor' => '✍️'];

// JSON-LD : DiscussionForumPosting
$JSONLD = [
    '@context'      => 'https://schema.org',
    '@type'         => 'DiscussionForumPosting',
    'headline'      => $thread['title'],
    'datePublished' => str_replace(' ', 'T', (string) $thread['created_at']),
    'author'        => ['@type' => 'Person', 'name' => $thread['display_name'] ?: $thread['username'] ?: 'Anonyme'],
    'interactionStatistic' => [
        '@type'                => 'InteractionCounter',
        'interactionType'      => 'https://schema.org/CommentAction',
        'userInteractionCount' => max(0, count($posts) - 1),
    ],
    'text' => mb_substr(strip_tags($posts[0]['body'] ?? ''), 0, 280),
];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:840px">
    <p class="breadcrumb muted">
        <a href="<?= e(with_lang(url('pages/forum.php'))) ?>">Forum</a> &rsaquo;
        <a href="<?= e(with_lang(url('pages/forum-category.php?cat=' . urlencode($thread['cat_slug'])))) ?>"><?= e($thread['cat_name']) ?></a>
    </p>
    <div class="section-head">
        <h1><?= $thread['pinned'] ? '📌 ' : '' ?><?= e($thread['title']) ?><?= $thread['locked'] ? ' 🔒' : '' ?></h1>
        <?php if (is_admin()): ?>
            <div style="display:flex;gap:.5rem">
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_lock"><button class="btn btn--ghost" style="font-size:.8rem"><?= $thread['locked'] ? '🔓' : '🔒' ?></button></form>
                <form method="post" onsubmit="return confirm('Supprimer ce sujet ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_thread"><button class="btn btn--ghost" style="font-size:.8rem;color:#ff5d5d">🗑️</button></form>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

    <div class="posts">
        <?php foreach ($posts as $p): ?>
            <article class="post glass">
                <div class="post__head">
                    <span class="post__author"><?= e($role_badge[$p['role']] ?? '🙂') ?> <?= e($p['display_name'] ?: $p['username'] ?: 'Anonyme') ?> <?= rank_chip_html($p['user_id'] ? (int) $p['user_id'] : null) ?></span>
                    <span class="muted post__date"><?= e(substr((string) $p['created_at'], 0, 16)) ?></span>
                </div>
                <div class="post__body"><?= nl2br(e($p['body'])) ?></div>
                <?php if (is_admin()): ?>
                    <form method="post" class="post__mod" onsubmit="return confirm('Supprimer ce message ?')">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete_post"><input type="hidden" name="post_id" value="<?= (int) $p['id'] ?>">
                        <button>🗑️ <?= lang() === 'fr' ? 'Supprimer' : 'Delete' ?></button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <div id="bas"></div>
    <?php if ($thread['locked']): ?>
        <p class="muted" style="margin-top:1.4rem">🔒 <?= lang() === 'fr' ? 'Ce sujet est verrouillé.' : 'This topic is locked.' ?></p>
    <?php elseif (is_logged_in()): ?>
        <form method="post" class="form glass" style="margin-top:1.4rem;padding:1.2rem;border-radius:16px;max-width:none">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reply">
            <label><?= lang() === 'fr' ? 'Répondre' : 'Reply' ?></label>
            <textarea name="body" required style="min-height:120px" placeholder="<?= lang() === 'fr' ? 'Votre message…' : 'Your message…' ?>"></textarea>
            <button class="btn btn--primary" type="submit"><?= lang() === 'fr' ? 'Publier' : 'Post' ?></button>
        </form>
    <?php else: ?>
        <p class="muted" style="margin-top:1.4rem">
            <a class="btn btn--primary" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? 'Connectez-vous pour répondre' : 'Log in to reply' ?></a>
        </p>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
