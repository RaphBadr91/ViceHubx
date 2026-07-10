<?php
require __DIR__ . '/../includes/admin_header.php';
require_once ROOT_PATH . '/includes/ai.php';

$flash = null;

/** Badge de statut coloré (Programmé = orange, Publié = vert, Brouillon = gris, Erreur = rouge). */
function vhx_status_badge(string $status): string
{
    $map = [
        'published' => ['🟢 Publié',    '#0e3b23', '#3ee07f'],
        'pending'   => ['🟠 Programmé', '#4a2e05', '#ffb02e'],
        'draft'     => ['⚪ Brouillon',  '#2a2a33', '#c9c9d6'],
        'error'     => ['🔴 Erreur',    '#3d1414', '#ff6b6b'],
    ];
    [$label, $bg, $fg] = $map[$status] ?? $map['error'];
    return '<span style="display:inline-block;padding:.28rem .6rem;border-radius:99px;font-size:.78rem;font-weight:800;'
        . 'background:' . $bg . ';color:' . $fg . ';white-space:nowrap">' . e($label) . '</span>';
}

// Actions (POST + CSRF)
$act = $_POST['action'] ?? '';
if (in_array($act, ['delete', 'publish', 'reschedule', 'autoplan'], true)) {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } elseif ($act === 'delete') {
        db()->prepare('DELETE FROM articles WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        $flash = ['ok', 'Article supprimé.'];
    } elseif ($act === 'publish') {
        db()->prepare("UPDATE articles SET status='published', published_at=NOW() WHERE id = ?")->execute([(int) ($_POST['id'] ?? 0)]);
        $flash = ['ok', '🟢 Article publié maintenant.'];
    } elseif ($act === 'reschedule') {
        $id  = (int) ($_POST['id'] ?? 0);
        $raw = trim((string) ($_POST['when'] ?? ''));
        $ts  = $raw !== '' ? strtotime($raw) : false;
        if ($ts === false) {
            $flash = ['err', 'Date invalide.'];
        } else {
            db()->prepare("UPDATE articles SET status='pending', published_at=? WHERE id=?")
               ->execute([date('Y-m-d H:i:s', $ts), $id]);
            $flash = ['ok', '🟠 Article reprogrammé pour le ' . date('d/m/Y à H:i', $ts) . '.'];
        }
    } elseif ($act === 'autoplan') {
        ai_schedule_pending(max(1, (int) get_setting('ai_auto_interval', '6')));
        $flash = ['ok', '🗓️ Articles programmés planifiés automatiquement.'];
    }
}

// Prochaine publication programmée (pour le résumé en tête).
$nextPlanned = db()->query(
    "SELECT MIN(published_at) FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> '' AND published_at > NOW()"
)->fetchColumn();
$scheduledCount = (int) db()->query(
    "SELECT COUNT(*) FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> ''"
)->fetchColumn();
$pendingAll = (int) db()->query("SELECT COUNT(*) FROM articles WHERE status='pending'")->fetchColumn();

// Programmés (à venir) d'abord, du plus proche au plus lointain ; puis brouillons ; puis publiés récents.
$articles = db()->query(
    "SELECT a.id, a.title, a.slug, a.lang, a.status, a.published_at, a.created_at,
            (a.image_prompt IS NOT NULL AND a.image_prompt <> '') AS is_ai,
            c.name cat, COALESCE(u.display_name, u.username) author
     FROM articles a
     LEFT JOIN categories c ON c.id=a.category_id
     LEFT JOIN users u ON u.id=a.author_id
     ORDER BY CASE a.status WHEN 'pending' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END,
              CASE WHEN a.status='pending' THEN a.published_at END ASC,
              a.published_at DESC, a.id DESC"
)->fetchAll();

$btn = 'display:inline-flex;align-items:center;gap:.25rem;background:rgba(255,255,255,.06);border:1px solid var(--glass-brd);'
     . 'border-radius:8px;padding:.32rem .6rem;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none;line-height:1';
?>
<div class="admin-bar">
    <h1><?= e(t('admin_articles')) ?></h1>
    <a class="btn btn--primary" href="<?= e(url('admin/article-create.php')) ?>">+ <?= e(t('admin_new')) ?></a>
</div>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php if ($scheduledCount > 0): ?>
    <div class="glass" style="border-radius:14px;padding:.9rem 1.1rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between">
        <div>
            <strong>🟠 <?= $scheduledCount ?> article(s) programmé(s)</strong>
            <?php if ($nextPlanned): ?><span class="muted"> · prochain le <strong><?= e(date('d/m/Y à H:i', strtotime((string) $nextPlanned))) ?></strong></span><?php endif; ?>
            <span class="muted"> · 1 publié toutes les <?= (int) get_setting('ai_auto_interval', '6') ?> h</span>
        </div>
        <form method="post" style="margin:0">
            <?= csrf_field() ?><input type="hidden" name="action" value="autoplan">
            <button class="btn btn--ghost" type="submit" title="Répartir automatiquement les dates de publication des articles programmés">🗓️ Planifier automatiquement</button>
        </form>
    </div>
<?php endif; ?>
<?php if ($pendingAll > $scheduledCount): ?>
    <div class="alert alert--ok">🟡 <?= $pendingAll - $scheduledCount ?> contribution(s) en attente de validation (à publier à la main).</div>
<?php endif; ?>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem;overflow-x:auto">
    <table class="data-table">
        <thead><tr><th>#</th><th>Titre</th><th>Statut</th><th>Date de publication</th><th>Cat.</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($articles as $a):
            $st       = (string) $a['status'];
            $isSched  = $st === 'pending';
            $hasDate  = !empty($a['published_at']);
            $ts       = $hasDate ? strtotime((string) $a['published_at']) : 0;
            $preview  = url('pages/article.php?slug=' . urlencode((string) $a['slug']));
            $dtValue  = $hasDate ? date('Y-m-d\TH:i', $ts) : date('Y-m-d\TH:i', time() + 3600);
        ?>
            <tr>
                <td class="muted"><?= (int) $a['id'] ?></td>
                <td>
                    <?= e($a['title']) ?>
                    <?php if ((int) $a['is_ai'] === 1): ?><span class="muted" style="font-size:.72rem"> · 🤖 IA</span><?php endif; ?>
                    <div class="muted" style="font-size:.74rem"><?= e($a['author'] ?? '—') ?> · <?= e(strtoupper($a['lang'])) ?></div>
                </td>
                <td><?= vhx_status_badge($st) ?></td>
                <td style="white-space:nowrap;font-size:.85rem">
                    <?php if ($st === 'published' && $hasDate): ?>
                        <span style="color:#3ee07f">Publié le <?= e(date('d/m/Y', $ts)) ?></span><br><span class="muted"><?= e(date('H:i', $ts)) ?></span>
                    <?php elseif ($isSched && $hasDate): ?>
                        <span style="color:#ffb02e">Prévu le <?= e(date('d/m/Y', $ts)) ?></span><br><span class="muted"><?= e(date('H:i', $ts)) ?></span>
                    <?php elseif ($isSched): ?>
                        <span style="color:#ffb02e">À planifier</span>
                    <?php else: ?>
                        <span class="muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="muted"><?= e($a['cat'] ?? '—') ?></td>
                <td>
                    <div style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center">
                        <a style="<?= $btn ?>" href="<?= e($preview) ?>" target="_blank" rel="noopener" title="Lire l'article<?= $st !== 'published' ? ' en aperçu' : '' ?>">👁️ Lire</a>

                        <?php if ($st !== 'published'): ?>
                            <form method="post" style="margin:0" onsubmit="return confirm('Publier cet article maintenant ?')">
                                <?= csrf_field() ?><input type="hidden" name="action" value="publish"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button type="submit" style="<?= $btn ?>;color:#3ee07f">✅ Publier maintenant</button>
                            </form>

                            <details style="margin:0;position:relative">
                                <summary style="<?= $btn ?>;list-style:none;color:#ffb02e">🗓️ <?= $isSched ? 'Reprogrammer' : 'Programmer' ?></summary>
                                <form method="post" class="glass" style="position:absolute;z-index:20;top:2rem;right:0;padding:.7rem;border-radius:10px;min-width:230px;display:flex;flex-direction:column;gap:.5rem">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="reschedule"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <label style="font-size:.78rem" class="muted">Date &amp; heure de publication</label>
                                    <input type="datetime-local" name="when" value="<?= e($dtValue) ?>" required style="width:100%">
                                    <button class="btn btn--primary" type="submit" style="padding:.4rem .7rem">Enregistrer la date</button>
                                </form>
                            </details>
                        <?php endif; ?>

                        <a style="<?= $btn ?>" href="<?= e(url('admin/article-edit.php?id=' . (int) $a['id'])) ?>">✏️ Modifier</a>

                        <form method="post" style="margin:0" onsubmit="return confirm('Supprimer définitivement cet article ?')">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button type="submit" style="<?= $btn ?>;color:#ff6b6b">🗑️ Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
