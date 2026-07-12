<?php
$ADMIN_TITLE = 'ViceHub X — Boutique';
require __DIR__ . '/../includes/admin_header.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'delete' && $id) {
            db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            $flash = ['ok', 'Produit supprimé.'];
        } elseif ($action === 'toggle' && $id) {
            db()->prepare('UPDATE products SET active = 1 - active WHERE id = ?')->execute([$id]);
            $flash = ['ok', 'Visibilité mise à jour.'];
        } elseif ($action === 'feature' && $id) {
            db()->prepare('UPDATE products SET featured = 1 - featured WHERE id = ?')->execute([$id]);
            $flash = ['ok', 'Mise en avant mise à jour.'];
        } elseif ($action === 'cta' && $id) {
            db()->prepare('UPDATE products SET cta = 1 - cta WHERE id = ?')->execute([$id]);
            $flash = ['ok', 'Produit propulsé (CTA) mis à jour.'];
        } elseif ($action === 'cta_count') {
            $n = max(1, min(50, (int) ($_POST['cta_count'] ?? 6)));
            set_setting('cta_count', (string) $n);
            $flash = ['ok', "Rotation CTA réglée sur $n produit(s)."];
        }
    }
}

// Auto-réparation : normalise en minuscules les slugs legacy en majuscules
// (URL propres + évite la page 404 « noire » due au routage. BINARY = compare
// octet à octet, sinon la collation insensible à la casse ne trouverait rien).
try { db()->exec('UPDATE products SET slug = LOWER(slug) WHERE BINARY slug <> LOWER(slug)'); } catch (Throwable $e) {}

$products = db()->query('SELECT * FROM products ORDER BY sort ASC, id ASC')->fetchAll();
$cats = product_categories();
$cta_count = max(1, min(50, (int) get_setting('cta_count', '6')));
$cta_flagged = (int) db()->query("SELECT COUNT(*) c FROM products WHERE active=1 AND cta=1")->fetch()['c'];
?>
<div class="admin-bar">
    <h1>Boutique</h1>
    <a class="btn btn--primary" href="<?= e(url('admin/product-create.php')) ?>">+ Nouveau produit</a>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<p class="muted" style="font-size:.9rem;max-width:760px">
    🔗 La boutique fonctionne par <strong>liens partenaires</strong> (Amazon, impression à la demande).
    Aucune gestion de stock ni d'expédition : tout est géré par le marchand. Renseignez vos liens d'affiliation
    dans chaque produit pour percevoir les commissions automatiquement.
</p>

<div class="glass" style="border-radius:14px;padding:1rem 1.2rem;margin-bottom:1.2rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
    <form method="post" style="display:flex;gap:.7rem;align-items:flex-end;margin:0">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="cta_count">
        <div style="margin:0">
            <label style="font-weight:700">🚀 Produits propulsés en rotation</label>
            <input type="number" name="cta_count" value="<?= $cta_count ?>" min="1" max="50" style="width:90px">
        </div>
        <button class="btn btn--primary" type="submit">Enregistrer</button>
    </form>
    <p class="muted" style="font-size:.84rem;max-width:420px;margin:0">
        Ce nombre de produits est mis en avant <strong>au hasard</strong> dans les encarts des articles
        (rotation quotidienne, pour l'immersion). Candidats : les produits cochés 🚀 ci-dessous
        (<strong><?= $cta_flagged ?></strong> coché<?= $cta_flagged > 1 ? 's' : '' ?>) — si aucun n'est coché, tout le catalogue vendable est utilisé.
    </p>
</div>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem;overflow-x:auto">
    <table class="data-table">
        <thead><tr><th>#</th><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Marchand</th><th>Vedette</th><th title="Propulsé en encart CTA dans les articles">CTA 🚀</th><th>Visible</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= (int) $p['id'] ?></td>
                <td><?= e($p['name']) ?></td>
                <td class="muted"><?= e($cats[$p['category']] ?? $p['category']) ?></td>
                <td><?= $p['price'] !== null ? e(price_html($p['price'], $p['currency'])) : '—' ?></td>
                <td class="muted"><?= e($p['merchant'] ?? '—') ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="feature">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="link-all" style="background:none;border:0;cursor:pointer"><?= $p['featured'] ? '⭐' : '☆' ?></button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cta">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="link-all" style="background:none;border:0;cursor:pointer" title="<?= !empty($p['cta']) ? 'Retirer du CTA' : 'Propulser en CTA' ?>"><?= !empty($p['cta']) ? '🚀' : '·' ?></button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="link-all" style="background:none;border:0;cursor:pointer"><?= $p['active'] ? '🟢' : '⚪' ?></button>
                    </form>
                </td>
                <td style="white-space:nowrap">
                    <a class="link-all" href="<?= e(url('admin/product-edit.php?id=' . (int) $p['id'])) ?>"><?= e(t('admin_edit')) ?></a>
                    &nbsp;·&nbsp;
                    <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce produit ?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="link-all" style="background:none;border:0;color:#ff5d5d;cursor:pointer"><?= e(t('admin_delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
