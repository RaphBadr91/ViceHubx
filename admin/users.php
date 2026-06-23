<?php
$ADMIN_TITLE = 'ViceHub X — Membres';
require __DIR__ . '/../includes/admin_header.php';

$me = current_user();
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'role' && $id) {
            $role = in_array($_POST['role'] ?? '', ['admin', 'editor', 'contributor', 'member'], true) ? $_POST['role'] : 'member';
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
            $flash = ['ok', 'Rôle mis à jour.'];
        } elseif ($action === 'delete' && $id && $id !== (int) $me['id']) {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            $flash = ['ok', 'Compte supprimé.'];
        }
    }
}

$users = db()->query('SELECT id, username, display_name, email, role, created_at FROM users ORDER BY id ASC')->fetchAll();
$roles = ['admin' => 'Administrateur', 'editor' => 'Éditeur', 'contributor' => 'Contributeur', 'member' => 'Membre'];
?>
<div class="admin-bar"><h1>Membres</h1></div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<p class="muted" style="font-size:.9rem;max-width:760px">Gérez les accès : passez un membre en <strong>contributeur</strong> (articles auto-publiés) ou <strong>éditeur/admin</strong> (accès back-office).</p>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem;overflow-x:auto">
    <table class="data-table">
        <thead><tr><th>#</th><th>Identifiant</th><th>Nom affiché</th><th>E-mail</th><th>Rôle</th><th>Inscrit</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><?= e($u['username']) ?></td>
                <td class="muted"><?= e($u['display_name'] ?? '—') ?></td>
                <td class="muted"><?= e($u['email'] ?? '—') ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="role">
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <select name="role" onchange="this.form.submit()">
                            <?php foreach ($roles as $rk => $rl): ?>
                                <option value="<?= e($rk) ?>"<?= $u['role'] === $rk ? ' selected' : '' ?>><?= e($rl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="muted"><?= e(substr((string) $u['created_at'], 0, 10)) ?></td>
                <td>
                    <?php if ((int) $u['id'] !== (int) $me['id']): ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce compte ?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button class="link-all" style="background:none;border:0;color:#ff5d5d;cursor:pointer">Supprimer</button>
                        </form>
                    <?php else: ?><span class="muted">vous</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
