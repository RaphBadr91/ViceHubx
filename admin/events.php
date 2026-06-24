<?php
$ADMIN_TITLE = 'ViceHub X — Événements';
require __DIR__ . '/../includes/admin_header.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'delete' && $id) {
            db()->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
            $flash = ['ok', 'Événement supprimé.'];
        } elseif ($action === 'save') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $desc  = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 300);
            $icon  = mb_substr(trim((string) ($_POST['icon'] ?? '📌')), 0, 12);
            $date  = str_replace('T', ' ', trim((string) ($_POST['event_date'] ?? ''))) . ':00';
            $link  = trim((string) ($_POST['link'] ?? ''));
            if ($title !== '' && strlen($date) >= 16) {
                if ($id) {
                    db()->prepare('UPDATE events SET title=?, description=?, icon=?, event_date=?, link=? WHERE id=?')
                        ->execute([$title, $desc, $icon, $date, $link, $id]);
                    $flash = ['ok', 'Événement mis à jour.'];
                } else {
                    db()->prepare('INSERT INTO events (title, description, icon, event_date, link) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$title, $desc, $icon, $date, $link]);
                    $flash = ['ok', 'Événement créé.'];
                }
            } else {
                $flash = ['err', 'Titre et date sont obligatoires.'];
            }
        }
    }
}
$events = db()->query('SELECT * FROM events ORDER BY event_date ASC')->fetchAll();
$edit = null;
if (($eid = (int) ($_GET['edit'] ?? 0))) {
    $s = db()->prepare('SELECT * FROM events WHERE id = ?');
    $s->execute([$eid]);
    $edit = $s->fetch() ?: null;
}
?>
<div class="admin-bar"><h1>Événements</h1></div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<form method="post" class="form glass" style="max-width:680px;padding:1.4rem;border-radius:16px;margin-bottom:1.6rem">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <h2 style="font-size:1.05rem;margin:0"><?= $edit ? 'Modifier l’événement' : 'Nouvel événement' ?></h2>
    <div style="display:grid;grid-template-columns:60px 1fr;gap:1rem">
        <div><label>Icône</label><input type="text" name="icon" maxlength="12" value="<?= e($edit['icon'] ?? '📌') ?>"></div>
        <div><label>Titre *</label><input type="text" name="title" required maxlength="160" value="<?= e($edit['title'] ?? '') ?>"></div>
    </div>
    <div><label>Description</label><input type="text" name="description" maxlength="300" value="<?= e($edit['description'] ?? '') ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div><label>Date & heure *</label><input type="datetime-local" name="event_date" required value="<?= e($edit ? substr(str_replace(' ', 'T', $edit['event_date']), 0, 16) : '') ?>"></div>
        <div><label>Lien (optionnel)</label><input type="text" name="link" maxlength="300" placeholder="/pages/forum.php" value="<?= e($edit['link'] ?? '') ?>"></div>
    </div>
    <button class="btn btn--primary" type="submit"><?= $edit ? 'Enregistrer' : 'Créer' ?></button>
    <?php if ($edit): ?><a class="btn btn--ghost" href="<?= e(url('admin/events.php')) ?>">Annuler</a><?php endif; ?>
</form>

<div class="glass" style="border-radius:16px;padding:1rem 1.2rem;overflow-x:auto">
    <table class="data-table">
        <thead><tr><th></th><th>Titre</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($events as $ev): ?>
            <tr>
                <td style="font-size:1.3rem"><?= e($ev['icon']) ?></td>
                <td><?= e($ev['title']) ?></td>
                <td class="muted" style="white-space:nowrap"><?= e(substr((string) $ev['event_date'], 0, 16)) ?></td>
                <td style="white-space:nowrap">
                    <a class="link-all" href="<?= e(url('admin/events.php?edit=' . (int) $ev['id'])) ?>">Modifier</a> ·
                    <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $ev['id'] ?>"><button class="link-all" style="background:none;border:0;color:#ff5d5d;cursor:pointer">Supprimer</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
