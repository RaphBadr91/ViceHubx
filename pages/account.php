<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();
$me = current_user();

// Mes articles soumis
$stmt = db()->prepare('SELECT id, title, slug, status, created_at FROM articles WHERE author_id = ? ORDER BY id DESC');
$stmt->execute([(int) $me['id']]);
$mine = $stmt->fetchAll();

$role_labels = ['admin' => 'Administrateur', 'editor' => 'Éditeur', 'contributor' => 'Contributeur', 'member' => 'Membre'];
$status_labels = lang() === 'fr'
    ? ['draft' => 'Brouillon', 'pending' => 'En attente de validation', 'published' => 'Publié']
    : ['draft' => 'Draft', 'pending' => 'Pending review', 'published' => 'Published'];

$SEO_TITLE = (lang() === 'fr' ? 'Mon compte' : 'My account') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow">👤 <?= e($role_labels[$me['role']] ?? 'Membre') ?></span>
            <h1><?= lang() === 'fr' ? 'Bonjour' : 'Hello' ?>, <?= e(display_name($me)) ?> 👋</h1>
        </div>
        <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/logout.php'))) ?>"><?= lang() === 'fr' ? 'Déconnexion' : 'Log out' ?></a>
    </div>

    <?php
    $st = user_xp_stats((int) $me['id']);
    $rk = rank_for_xp($st['xp']);
    $nx = $rk['next'];
    $pct = $nx ? min(100, round(($st['xp'] - $rk['min']) / max(1, $nx[0] - $rk['min']) * 100)) : 100;
    ?>
    <div class="rank-card glass">
        <div class="rank-card__top">
            <span class="rank-card__emoji"><?= $rk['emoji'] ?></span>
            <div>
                <span class="muted" style="font-size:.8rem"><?= lang() === 'fr' ? 'Ton rang' : 'Your rank' ?></span>
                <h2 style="margin:.1rem 0"><?= e($rk['name']) ?></h2>
            </div>
            <span class="rank-card__xp"><?= (int) $st['xp'] ?> XP</span>
        </div>
        <div class="rank-bar"><i style="width:<?= (int) $pct ?>%"></i></div>
        <p class="muted" style="font-size:.82rem;margin:.5rem 0 0">
            <?= (int) $st['posts'] ?> <?= lang() === 'fr' ? 'messages' : 'posts' ?> · <?= (int) $st['threads'] ?> <?= lang() === 'fr' ? 'sujets' : 'topics' ?>
            <?php if ($nx): ?> · <?= lang() === 'fr' ? 'Prochain rang' : 'Next rank' ?> : <?= $nx[2] ?> <?= e($nx[1]) ?> (<?= (int) ($nx[0] - $st['xp']) ?> XP)<?php else: ?> · <?= lang() === 'fr' ? 'Rang maximum atteint ! 🌴' : 'Max rank! 🌴' ?><?php endif; ?>
            · <a class="link-all" href="<?= e(with_lang(url('pages/classement.php'))) ?>"><?= lang() === 'fr' ? 'Voir le classement' : 'Leaderboard' ?> →</a>
        </p>
    </div>

    <div class="account-actions">
        <a class="account-tile glass" href="<?= e(with_lang(url('pages/contribute.php'))) ?>">
            <span class="account-tile__ico">✍️</span>
            <strong><?= lang() === 'fr' ? 'Proposer un article' : 'Submit an article' ?></strong>
            <span class="muted"><?= lang() === 'fr' ? 'Rédigez, l’équipe valide.' : 'Write, we review.' ?></span>
        </a>
        <a class="account-tile glass" href="<?= e(with_lang(url('pages/forum.php'))) ?>">
            <span class="account-tile__ico">💬</span>
            <strong><?= lang() === 'fr' ? 'Forum' : 'Forum' ?></strong>
            <span class="muted"><?= lang() === 'fr' ? 'Discutez avec la communauté.' : 'Join the community.' ?></span>
        </a>
        <?php if (is_admin()): ?>
        <a class="account-tile glass" href="<?= e(url('admin/dashboard.php')) ?>">
            <span class="account-tile__ico">🛠️</span>
            <strong>Administration</strong>
            <span class="muted"><?= lang() === 'fr' ? 'Gérer le site.' : 'Manage the site.' ?></span>
        </a>
        <?php endif; ?>
    </div>

    <h2 style="margin-top:2.4rem"><?= lang() === 'fr' ? 'Mes articles proposés' : 'My submitted articles' ?></h2>
    <?php if (!$mine): ?>
        <p class="muted"><?= lang() === 'fr' ? 'Vous n’avez encore proposé aucun article.' : 'You have not submitted any article yet.' ?></p>
    <?php else: ?>
        <div class="glass" style="border-radius:16px;padding:1rem 1.2rem;overflow-x:auto">
            <table class="data-table">
                <thead><tr><th><?= lang() === 'fr' ? 'Titre' : 'Title' ?></th><th><?= lang() === 'fr' ? 'Statut' : 'Status' ?></th><th><?= lang() === 'fr' ? 'Date' : 'Date' ?></th></tr></thead>
                <tbody>
                <?php foreach ($mine as $a): ?>
                    <tr>
                        <td><?php if ($a['status'] === 'published'): ?><a class="link-all" href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>"><?= e($a['title']) ?></a><?php else: ?><?= e($a['title']) ?><?php endif; ?></td>
                        <td><?= $a['status'] === 'published' ? '🟢' : ($a['status'] === 'pending' ? '🟡' : '⚪') ?> <?= e($status_labels[$a['status']] ?? $a['status']) ?></td>
                        <td class="muted"><?= e(substr((string) $a['created_at'], 0, 10)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
