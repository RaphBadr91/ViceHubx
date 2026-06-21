<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_community_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Communauté ViceHub X : sondages, votes et commentaires modérés.'
    : 'ViceHub X community: polls, votes and moderated comments.';

$flash = null;

// Sondage actif
$poll = db()->prepare('SELECT * FROM polls WHERE active = 1 AND lang = ? ORDER BY id DESC LIMIT 1');
$poll->execute([lang()]);
$poll = $poll->fetch();
$options = [];
if ($poll) {
    $stmt = db()->prepare(
        'SELECT o.id, o.label, COUNT(v.id) AS votes
         FROM poll_options o LEFT JOIN poll_votes v ON v.option_id = o.id
         WHERE o.poll_id = ? GROUP BY o.id, o.label ORDER BY o.id'
    );
    $stmt->execute([$poll['id']]);
    $options = $stmt->fetchAll();
}

// Article support pour les commentaires (le plus récent)
$thread = db()->query("SELECT id, title FROM articles WHERE status='published' ORDER BY published_at DESC LIMIT 1")->fetch();

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'vote' && $poll) {
        $voted_key = 'voted_poll_' . $poll['id'];
        $option_id = (int) ($_POST['option_id'] ?? 0);
        $valid = in_array($option_id, array_map(fn($o) => (int) $o['id'], $options), true);
        if ($valid && empty($_SESSION[$voted_key])) {
            $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . CSRF_KEY);
            $ins = db()->prepare('INSERT INTO poll_votes (poll_id, option_id, ip_hash) VALUES (?, ?, ?)');
            $ins->execute([$poll['id'], $option_id, $ipHash]);
            $_SESSION[$voted_key] = true;
            $flash = ['ok', t('poll_thanks')];
            redirect(with_lang(url('pages/community.php'))); // PRG
        }
    } elseif ($action === 'comment' && $thread) {
        $name = trim((string) ($_POST['name'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($name !== '' && $body !== '') {
            $ins = db()->prepare(
                'INSERT INTO comments (article_id, author_name, body, status) VALUES (?, ?, ?, "pending")'
            );
            $ins->execute([$thread['id'], mb_substr($name, 0, 80), mb_substr($body, 0, 2000)]);
            $flash = ['ok', t('comment_ok')];
        }
    }
}

// Commentaires approuvés récents
$comments = db()->query(
    "SELECT c.author_name, c.body, c.created_at, a.title
     FROM comments c JOIN articles a ON a.id = c.article_id
     WHERE c.status = 'approved' ORDER BY c.created_at DESC LIMIT 12"
)->fetchAll();

$total_votes = array_sum(array_map(fn($o) => (int) $o['votes'], $options)) ?: 0;
$has_voted = $poll && !empty($_SESSION['voted_poll_' . $poll['id']]);
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">💬 ViceHub X</span>
    <h1><?= e(t('page_community_title')) ?></h1>

    <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
    <?php endif; ?>

    <div class="cards" style="grid-template-columns:1fr 1fr">
        <!-- Sondage -->
        <?php if ($poll): ?>
        <div class="card glass" style="padding:1.4rem">
            <h2 style="font-size:1.2rem;margin-top:0">🗳️ <?= e($poll['question']) ?></h2>
            <?php if ($has_voted): ?>
                <?php foreach ($options as $o):
                    $pct = $total_votes ? round(100 * (int) $o['votes'] / $total_votes) : 0; ?>
                    <div style="margin:.6rem 0">
                        <div style="display:flex;justify-content:space-between">
                            <span><?= e($o['label']) ?></span><span class="muted"><?= $pct ?>%</span>
                        </div>
                        <div style="height:8px;border-radius:8px;background:rgba(255,255,255,.1);overflow:hidden">
                            <div style="height:100%;width:<?= $pct ?>%;background:var(--grad-neon)"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <p class="muted" style="font-size:.8rem"><?= $total_votes ?> votes</p>
            <?php else: ?>
                <form method="post" class="form" style="gap:.6rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="vote">
                    <?php foreach ($options as $o): ?>
                        <label style="display:flex;gap:.5rem;align-items:center;color:var(--ink)">
                            <input type="radio" name="option_id" value="<?= (int) $o['id'] ?>" required style="width:auto">
                            <?= e($o['label']) ?>
                        </label>
                    <?php endforeach; ?>
                    <button class="btn btn--blue" type="submit"><?= e(t('poll_vote')) ?></button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Formulaire commentaire -->
        <div class="card glass" style="padding:1.4rem">
            <h2 style="font-size:1.2rem;margin-top:0">✍️ <?= e(t('comments')) ?></h2>
            <form method="post" class="form" style="gap:.7rem">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="comment">
                <input type="text" name="name" maxlength="80" required placeholder="<?= e(t('comment_name')) ?>">
                <textarea name="body" maxlength="2000" required placeholder="<?= e(t('comment_ph')) ?>"></textarea>
                <button class="btn btn--primary" type="submit"><?= e(t('comment_btn')) ?></button>
            </form>
        </div>
    </div>

    <!-- Derniers commentaires -->
    <h2 style="margin-top:2.4rem">🧵 <?= e(t('comments')) ?></h2>
    <?php if ($comments): ?>
        <?php foreach ($comments as $c): ?>
            <div class="glass reveal" style="border-radius:14px;padding:1rem 1.2rem;margin-bottom:.8rem">
                <strong><?= e($c['author_name']) ?></strong>
                <span class="muted" style="font-size:.8rem"> · <?= e(fmt_date($c['created_at'])) ?></span>
                <p style="margin:.4rem 0 0"><?= e($c['body']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="muted"><?= e(t('no_content')) ?></p>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
