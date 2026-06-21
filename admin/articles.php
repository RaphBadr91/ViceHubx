<?php
require __DIR__ . '/../includes/admin_header.php';

$flash = null;

// Suppression (POST + CSRF)
if (($_POST['action'] ?? '') === 'delete') {
    if (verify_csrf()) {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
        $flash = ['ok', 'Article supprimé.'];
    } else {
        $flash = ['err', 'Jeton CSRF invalide.'];
    }
}

$articles = db()->query(
    "SELECT a.id, a.title, a.lang, a.status, a.badge, a.published_at, c.name cat
     FROM articles a LEFT JOIN categories c ON c.id=a.category_id
     ORDER BY a.id DESC"
)->fetchAll();
?>
<div class="admin-bar">
    <h1><?= e(t('admin_articles')) ?></h1>
    <a class="btn btn--primary" href="<?= e(url('admin/article-create.php')) ?>">+ <?= e(t('admin_new')) ?></a>
</div>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
<?php endif; ?>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem">
    <table class="data-table">
        <thead><tr><th>#</th><th>Titre</th><th>Cat.</th><th>Lang</th><th>Badge</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
            <tr>
                <td><?= (int) $a['id'] ?></td>
                <td><?= e($a['title']) ?></td>
                <td class="muted"><?= e($a['cat'] ?? '—') ?></td>
                <td><?= e(strtoupper($a['lang'])) ?></td>
                <td><?= badge_html($a['badge']) ?: '—' ?></td>
                <td><?= $a['status'] === 'published' ? '🟢' : '⚪' ?> <?= e($a['status']) ?></td>
                <td style="white-space:nowrap">
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
