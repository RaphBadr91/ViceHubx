<?php
$ADMIN_TITLE = 'ViceHub X — Fan-arts';
require __DIR__ . '/../includes/admin_header.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($action === 'approve' && $id) {
            db()->prepare("UPDATE fanarts SET status='approved' WHERE id=?")->execute([$id]);
            $flash = ['ok', 'Fan-art approuvé.'];
        } elseif ($action === 'delete' && $id) {
            db()->prepare('DELETE FROM fanarts WHERE id=?')->execute([$id]);
            $flash = ['ok', 'Fan-art supprimé.'];
        }
    }
}
$pending = (int) db()->query("SELECT COUNT(*) FROM fanarts WHERE status='pending'")->fetchColumn();
$arts = db()->query("SELECT f.*, COALESCE(u.display_name,u.username) author FROM fanarts f LEFT JOIN users u ON u.id=f.user_id ORDER BY (f.status='pending') DESC, f.id DESC")->fetchAll();
?>
<div class="admin-bar"><h1>Fan-arts</h1></div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>
<?php if ($pending): ?><div class="alert alert--ok">🟡 <?= $pending ?> fan-art(s) en attente de validation.</div><?php endif; ?>

<div class="admin-art-grid">
    <?php foreach ($arts as $a): ?>
        <div class="glass" style="border-radius:14px;overflow:hidden">
            <img src="<?= e(img_src($a['image'])) ?>" alt="" style="width:100%;aspect-ratio:16/10;object-fit:cover" onerror="this.style.display='none'">
            <div style="padding:.8rem 1rem">
                <strong><?= e($a['title']) ?></strong><br>
                <span class="muted" style="font-size:.82rem">@<?= e($a['author'] ?? '—') ?> · <?= $a['status'] === 'approved' ? '🟢 publié' : '🟡 en attente' ?></span>
                <div style="display:flex;gap:.6rem;margin-top:.6rem">
                    <?php if ($a['status'] !== 'approved'): ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><button class="link-all" style="background:none;border:0;color:#2bd6ff;cursor:pointer;font-weight:700">✓ Approuver</button></form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('Supprimer ce fan-art ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><button class="link-all" style="background:none;border:0;color:#ff5d5d;cursor:pointer">Supprimer</button></form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<style>.admin-art-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}</style>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
