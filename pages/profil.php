<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$uname = trim((string) ($_GET['u'] ?? ''));
$u = $uname !== '' ? get_user_by_username($uname) : null;

if (!$u) {
    http_response_code(404);
    $SEO_TITLE = '404 — ' . APP_NAME;
    require ROOT_PATH . '/includes/header.php';
    echo '<section class="section"><h1>404</h1><p class="muted">' . ($fr ? 'Membre introuvable.' : 'Member not found.')
        . '</p><a class="btn btn--ghost" href="' . e(with_lang(url('pages/classement.php'))) . '">' . ($fr ? 'Classement' : 'Leaderboard') . '</a></section>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}

$uid = (int) $u['id'];
// Profils membres : contenu mince → noindex (on concentre le crawl/qualité sur
// les articles et piliers). Les liens restent suivis (follow).
$ROBOTS = 'noindex,follow';
$stats = user_xp_stats($uid);
$rk = rank_for_xp($stats['xp']);
$nx = $rk['next'];
$pct = $nx ? min(100, round(($stats['xp'] - $rk['min']) / max(1, $nx[0] - $rk['min']) * 100)) : 100;
$trophies = user_achievements($uid);
$arts = user_fanarts($uid, 8);
$posts = user_recent_posts($uid, 6);
$name = $u['display_name'] ?: $u['username'];
$role_labels = ['admin' => 'Administrateur', 'editor' => 'Éditeur', 'contributor' => 'Contributeur', 'member' => 'Membre'];

$SEO_TITLE = $name . ' — ' . ($fr ? 'Profil membre' : 'Member profile') . ' · ' . APP_NAME;
$SEO_DESC  = $name . ($fr ? ' sur ViceHub X : rang ' . $rk['name'] . ', ' . $stats['xp'] . ' XP, fan de GTA VI.' : ' on ViceHub X.');
$JSONLD = ['@context' => 'https://schema.org', '@type' => 'ProfilePage', 'mainEntity' => ['@type' => 'Person', 'name' => $name, 'description' => $rk['name']]];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <div class="profile-head glass">
        <div class="profile-avatar"><?= $rk['emoji'] ?></div>
        <div class="profile-id">
            <span class="muted"><?= e($role_labels[$u['role']] ?? 'Membre') ?> · <?= $fr ? 'membre depuis' : 'member since' ?> <?= e(substr((string) $u['created_at'], 0, 7)) ?></span>
            <h1><?= e($name) ?></h1>
            <span class="rank-chip"><?= $rk['emoji'] ?> <?= e($rk['name']) ?> · <?= (int) $stats['xp'] ?> XP</span>
        </div>
        <div class="profile-counts">
            <div><b><?= (int) $stats['posts'] ?></b><small><?= $fr ? 'messages' : 'posts' ?></small></div>
            <div><b><?= (int) $stats['threads'] ?></b><small><?= $fr ? 'sujets' : 'topics' ?></small></div>
            <div><b><?= count($arts) ?></b><small>fan-arts</small></div>
        </div>
    </div>
    <div class="rank-bar" style="margin-top:1rem"><i style="width:<?= (int) $pct ?>%"></i></div>
    <?php if ($nx): ?><p class="muted" style="font-size:.82rem;margin:.4rem 0 0"><?= $fr ? 'Prochain rang' : 'Next rank' ?> : <?= $nx[2] ?> <?= e($nx[1]) ?> (<?= (int) ($nx[0] - $stats['xp']) ?> XP)</p><?php endif; ?>
    <?php if (is_logged_in() && (int) current_user()['id'] !== $uid && $u['password_hash'] ?? true): ?>
        <a class="btn btn--primary" style="margin-top:1rem" href="<?= e(with_lang(url('pages/messages.php?u=' . urlencode($u['username'])))) ?>">💌 <?= $fr ? 'Envoyer un message' : 'Send a message' ?></a>
    <?php endif; ?>

    <h2 style="margin-top:2rem">🏅 <?= $fr ? 'Trophées' : 'Achievements' ?></h2>
    <div class="trophy-grid">
        <?php foreach ($trophies as $ac): ?>
            <div class="trophy<?= $ac[3] ? ' trophy--on' : '' ?>" title="<?= e($ac[2]) ?>">
                <span class="trophy__ico"><?= $ac[3] ? $ac[0] : '🔒' ?></span>
                <span class="trophy__name"><?= e($ac[1]) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($arts): ?>
    <h2 style="margin-top:2rem">🎨 <?= $fr ? 'Ses fan-arts' : 'Fan-arts' ?></h2>
    <div class="art-grid">
        <?php foreach ($arts as $a): ?>
            <figure class="art-card glass"><img src="<?= e(img_src($a['image'])) ?>" alt="<?= e($a['title']) ?>" loading="lazy" onerror="this.closest('.art-card').style.display='none'"><figcaption><span class="art-title"><?= e($a['title']) ?></span></figcaption></figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($posts): ?>
    <h2 style="margin-top:2rem">💬 <?= $fr ? 'Derniers messages' : 'Recent posts' ?></h2>
    <div class="posts">
        <?php foreach ($posts as $p): ?>
            <a class="post glass" style="display:block;text-decoration:none;color:inherit" href="<?= e(with_lang(url('pages/forum-thread.php?id=' . (int) $p['tid']))) ?>">
                <div class="muted" style="font-size:.8rem"><?= e($p['title']) ?> · <?= e(substr((string) $p['created_at'], 0, 16)) ?></div>
                <div><?= e(mb_strimwidth(strip_tags($p['body']), 0, 160, '…')) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
