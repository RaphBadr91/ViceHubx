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

// Recherche : par ID exact, identifiant, nom affiché ou e-mail (insensible casse).
$q = trim((string) ($_GET['q'] ?? ''));
$total = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$LIMIT = 500;
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = db()->prepare(
        'SELECT id, username, display_name, email, role, created_at FROM users
         WHERE id = ? OR username LIKE ? OR display_name LIKE ? OR email LIKE ?
         ORDER BY id ASC LIMIT ' . $LIMIT
    );
    $stmt->execute([ctype_digit($q) ? (int) $q : 0, $like, $like, $like]);
    $users = $stmt->fetchAll();
} else {
    $users = db()->query('SELECT id, username, display_name, email, role, created_at FROM users ORDER BY id ASC LIMIT ' . $LIMIT)->fetchAll();
}
$roles = ['admin' => 'Administrateur', 'editor' => 'Éditeur', 'contributor' => 'Contributeur', 'member' => 'Membre'];
?>
<div class="admin-bar"><h1>Membres <span class="muted" style="font-size:1rem;font-weight:400">· <?= $total ?></span></h1></div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<p class="muted" style="font-size:.9rem;max-width:760px">Gérez les accès : passez un membre en <strong>contributeur</strong> (articles auto-publiés) ou <strong>éditeur/admin</strong> (accès back-office).</p>

<!-- 🔍 Barre de recherche (e-mail, identifiant, nom affiché ou ID) -->
<form method="get" class="glass" style="border-radius:14px;padding:.8rem 1rem;margin:1rem 0;display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="🔍 Rechercher par e-mail, identifiant, nom ou ID…" autofocus
           style="flex:1;min-width:240px;padding:.6rem .8rem;border-radius:10px;background:rgba(255,255,255,.05);color:#fff;border:1px solid var(--glass-brd)">
    <button class="btn btn--primary" type="submit">Rechercher</button>
    <?php if ($q !== ''): ?>
        <a class="btn btn--ghost" href="<?= e(url('admin/users.php')) ?>">✖ Réinitialiser</a>
    <?php endif; ?>
</form>

<?php if ($q !== ''): ?>
    <p class="muted" style="font-size:.88rem;margin:.2rem 0 .8rem">
        <strong><?= count($users) ?></strong> résultat<?= count($users) > 1 ? 's' : '' ?> pour « <?= e($q) ?> »<?= count($users) >= $LIMIT ? ' (affichage limité à ' . $LIMIT . ')' : '' ?>.
    </p>
<?php endif; ?>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem;overflow-x:auto">
    <table class="data-table">
        <thead><tr><th>#</th><th>Identifiant</th><th>Nom affiché</th><th>E-mail</th><th>Rôle</th><th>Inscrit</th><th></th></tr></thead>
        <tbody>
        <?php if (!$users): ?>
            <tr><td colspan="7" class="muted" style="text-align:center;padding:1.5rem">Aucun membre trouvé<?= $q !== '' ? ' pour « ' . e($q) . ' »' : '' ?>.</td></tr>
        <?php endif; ?>
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
