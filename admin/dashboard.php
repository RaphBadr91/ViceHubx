<?php
require __DIR__ . '/../includes/admin_header.php';

$counts = [
    'articles'    => (int) db()->query("SELECT COUNT(*) c FROM articles")->fetch()['c'],
    'published'   => (int) db()->query("SELECT COUNT(*) c FROM articles WHERE status='published'")->fetch()['c'],
    'comments'    => (int) db()->query("SELECT COUNT(*) c FROM comments WHERE status='pending'")->fetch()['c'],
    'subscribers' => (int) db()->query("SELECT COUNT(*) c FROM newsletter_subscribers")->fetch()['c'],
];
$recent = db()->query(
    "SELECT a.id, a.title, a.status, a.published_at, c.name cat
     FROM articles a LEFT JOIN categories c ON c.id=a.category_id
     ORDER BY a.id DESC LIMIT 6"
)->fetchAll();
?>
<div class="admin-bar">
    <h1><?= e(t('admin_dashboard')) ?></h1>
    <a class="btn btn--primary" href="<?= e(url('admin/article-create.php')) ?>">+ <?= e(t('admin_new')) ?></a>
</div>

<div class="stat-grid">
    <div class="stat glass"><div class="num"><?= $counts['articles'] ?></div><div class="lbl"><?= e(t('admin_articles')) ?></div></div>
    <div class="stat glass"><div class="num"><?= $counts['published'] ?></div><div class="lbl">Published</div></div>
    <div class="stat glass"><div class="num"><?= $counts['comments'] ?></div><div class="lbl">Comments pending</div></div>
    <div class="stat glass"><div class="num"><?= $counts['subscribers'] ?></div><div class="lbl">Newsletter</div></div>
</div>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem">
    <table class="data-table">
        <thead><tr><th>#</th><th><?= e(t('admin_articles')) ?></th><th>Catégorie</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?= (int) $r['id'] ?></td>
                <td><?= e($r['title']) ?></td>
                <td class="muted"><?= e($r['cat'] ?? '—') ?></td>
                <td><?= $r['status'] === 'published' ? '🟢' : '⚪' ?> <?= e($r['status']) ?></td>
                <td><a class="link-all" href="<?= e(url('admin/article-edit.php?id=' . (int) $r['id'])) ?>"><?= e(t('admin_edit')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
