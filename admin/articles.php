<?php
require __DIR__ . '/../includes/admin_header.php';

$flash = null;

// Actions (POST + CSRF)
$act = $_POST['action'] ?? '';
if ($act === 'delete' || $act === 'publish') {
    if (verify_csrf()) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($act === 'delete') {
            db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
            $flash = ['ok', 'Article supprimé.'];
        } else {
            db()->prepare("UPDATE articles SET status='published', published_at=COALESCE(published_at, NOW()) WHERE id = ?")->execute([$id]);
            $flash = ['ok', 'Article publié.'];
        }
    } else {
        $flash = ['err', 'Jeton CSRF invalide.'];
    }
}

// Les contributions en attente d'abord
$pending = db()->query("SELECT COUNT(*) FROM articles WHERE status='pending'")->fetchColumn();
$articles = db()->query(
    "SELECT a.id, a.title, a.lang, a.status, a.badge, a.published_at,
            c.name cat, COALESCE(u.display_name, u.username) author
     FROM articles a
     LEFT JOIN categories c ON c.id=a.category_id
     LEFT JOIN users u ON u.id=a.author_id
     ORDER BY (a.status='pending') DESC, a.id DESC"
)->fetchAll();
?>
<div class="admin-bar">
    <h1><?= e(t('admin_articles')) ?></h1>
    <a class="btn btn--primary" href="<?= e(url('admin/article-create.php')) ?>">+ <?= e(t('admin_new')) ?></a>
</div>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
<?php endif; ?>
<?php if ($pending): ?>
    <div class="alert alert--ok">🟡 <?= (int) $pending ?> contribution(s) en attente de validation.</div>
<?php endif; ?>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem;overflow-x:auto">
    <table class="data-table">
        <thead><tr><th>#</th><th>Titre</th><th>Auteur</th><th>Cat.</th><th>Lang</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
            <tr>
                <td><?= (int) $a['id'] ?></td>
                <td><?= e($a['title']) ?></td>
                <td class="muted"><?= e($a['author'] ?? '—') ?></td>
                <td class="muted"><?= e($a['cat'] ?? '—') ?></td>
                <td><?= e(strtoupper($a['lang'])) ?></td>
                <td><?= $a['status'] === 'published' ? '🟢' : ($a['status'] === 'pending' ? '🟡' : '⚪') ?> <?= e($a['status']) ?></td>
                <td style="white-space:nowrap">
                    <?php if ($a['status'] !== 'published'): ?>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="publish">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button class="link-all" style="background:none;border:0;color:#2bd6ff;cursor:pointer;font-weight:700">✓ Publier</button>
                        </form>
                        &nbsp;·&nbsp;
                    <?php endif; ?>
                    <a class="link-all" href="<?= e(url('admin/article-edit.php?id=' . (int) $a['id'])) ?>"><?= e(t('admin_edit')) ?></a>
                    &nbsp;·&nbsp;
                    <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cet article ?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button class="link-all" style="background:none;border:0;color:#ff5d5d;cursor:pointer"><?= e(t('admin_delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
