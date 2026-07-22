<?php
$ADMIN_TITLE = 'ViceHub X — Modifier produit';
require __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/printful.php';
printful_ensure_schema();

$cats = product_categories();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) {
    echo '<div class="alert alert--err">Produit introuvable.</div>';
    require __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        try {
            $name = trim((string) ($_POST['name'] ?? ''));
            $url  = trim((string) ($_POST['url'] ?? ''));
            $sale_type = in_array($_POST['sale_type'] ?? 'external', ['external', 'stripe'], true) ? $_POST['sale_type'] : 'external';
            $stripe_price_id = trim((string) ($_POST['stripe_price_id'] ?? ''));
            $printful_variant = trim((string) ($_POST['printful_variant_id'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Le nom est obligatoire.');
            }
            if ($sale_type === 'external' && $url === '') {
                throw new RuntimeException('Le lien d’achat (revendeur / affiliation) est obligatoire pour un produit externe.');
            }
            // Slug rendu UNIQUE (en ignorant le produit courant : -2, -3… au besoin).
            $slug     = unique_slug(trim((string) ($_POST['slug'] ?? '')) ?: $name, 'products', 'slug', $id);
            $category = isset($cats[$_POST['category'] ?? '']) ? $_POST['category'] : 'accessory';
            $desc     = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 400);
            $price    = ($_POST['price'] ?? '') !== '' ? (float) str_replace(',', '.', $_POST['price']) : null;
            $currency = trim((string) ($_POST['currency'] ?? 'EUR')) ?: 'EUR';
            $merchant = trim((string) ($_POST['merchant'] ?? ''));
            $badge    = trim((string) ($_POST['badge'] ?? ''));
            $featured = isset($_POST['featured']) ? 1 : 0;
            $active   = isset($_POST['active']) ? 1 : 0;
            $sort     = (int) ($_POST['sort'] ?? 0);
            $lang     = in_array($_POST['lang'] ?? 'fr', ['fr', 'en'], true) ? $_POST['lang'] : 'fr';
            $newImg   = handle_image_upload('image') ?: trim((string) ($_POST['image_url'] ?? ''));
            $image    = $newImg !== '' ? $newImg : $p['image'];

            $stmt = db()->prepare(
                'UPDATE products SET name=?, slug=?, description=?, category=?, price=?, currency=?, image=?, sale_type=?, url=?, stripe_price_id=?, printful_variant_id=?, merchant=?, badge=?, featured=?, active=?, sort=?, lang=? WHERE id=?'
            );
            $stmt->execute([$name, $slug, $desc, $category, $price, $currency, $image, $sale_type, $url ?: null, $stripe_price_id ?: null, $printful_variant ?: null, $merchant ?: null, $badge ?: null, $featured, $active, $sort, $lang, $id]);
            redirect(url('admin/products.php'));
        } catch (Throwable $ex) {
            $flash = ['err', $ex->getMessage()];
        }
    }
    // Recharge les valeurs saisies en cas d'erreur
    $p = array_merge($p, $_POST);
}
?>
<div class="admin-bar">
    <h1>Modifier : <?= e($p['name']) ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('admin/products.php')) ?>">← Boutique</a>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form glass" style="max-width:760px;padding:1.6rem;border-radius:18px">
    <?= csrf_field() ?>
    <div><label>Nom *</label><input type="text" name="name" required maxlength="160" value="<?= e($p['name']) ?>"></div>
    <div><label>Slug</label><input type="text" name="slug" maxlength="180" value="<?= e($p['slug']) ?>"></div>
    <div><label>Description</label><textarea name="description" maxlength="400" style="min-height:80px"><?= e($p['description']) ?></textarea></div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
        <div><label>Catégorie</label>
            <select name="category">
                <?php foreach ($cats as $k => $label): ?>
                    <option value="<?= e($k) ?>"<?= $p['category'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Prix</label><input type="text" name="price" value="<?= e($p['price'] !== null ? $p['price'] : '') ?>"></div>
        <div><label>Devise</label>
            <select name="currency">
                <?php foreach (['EUR' => 'EUR €', 'USD' => 'USD $', 'GBP' => 'GBP £'] as $cv => $cl): ?>
                    <option value="<?= e($cv) ?>"<?= ($p['currency'] ?? 'EUR') === $cv ? ' selected' : '' ?>><?= e($cl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div><label>Type de vente</label>
            <select name="sale_type">
                <option value="stripe"<?= ($p['sale_type'] ?? 'external') === 'stripe' ? ' selected' : '' ?>>Stripe (vente directe)</option>
                <option value="external"<?= ($p['sale_type'] ?? 'external') === 'external' ? ' selected' : '' ?>>Revendeur / affilié (lien externe)</option>
            </select>
        </div>
        <div><label>ID prix Stripe (optionnel)</label><input type="text" name="stripe_price_id" maxlength="120" placeholder="price_…" value="<?= e($p['stripe_price_id'] ?? '') ?>"></div>
    </div>
    <div><label>🖨️ Variante Printful — impression à la demande <span class="muted">(optionnel)</span></label>
        <input type="text" name="printful_variant_id" maxlength="64" placeholder="sync_variant_id (ex. 4503821567)" value="<?= e($p['printful_variant_id'] ?? '') ?>">
        <small class="muted">ID de variante synchronisée Printful. À la commande payée, Printful imprime + expédie automatiquement. Vide = pas d'impression à la demande.</small>
    </div>
    <div><label>Lien d’achat (si revendeur / affilié)</label><input type="url" name="url" maxlength="500" value="<?= e($p['url'] ?? '') ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div><label>Marchand</label><input type="text" name="merchant" maxlength="60" value="<?= e($p['merchant'] ?? '') ?>"></div>
        <div><label>Badge</label><input type="text" name="badge" maxlength="40" value="<?= e($p['badge'] ?? '') ?>"></div>
    </div>
    <?php if (!empty($p['image'])): ?>
        <div><label>Image actuelle</label><div><img src="<?= e($p['image']) ?>" alt="" style="max-height:90px;border-radius:10px" onerror="this.style.display='none'"></div></div>
    <?php endif; ?>
    <div><label>Remplacer l’image (upload)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
    <div><label>… ou chemin / URL d’image</label><input type="text" name="image_url" placeholder="/public/assets/img/shop/…" value=""></div>
    <div style="display:grid;grid-template-columns:1fr 1fr auto auto;gap:1rem;align-items:end">
        <div><label>Langue</label>
            <select name="lang"><option value="fr"<?= ($p['lang'] ?? 'fr') === 'fr' ? ' selected' : '' ?>>FR</option><option value="en"<?= ($p['lang'] ?? '') === 'en' ? ' selected' : '' ?>>EN</option></select>
        </div>
        <div><label>Ordre (tri)</label><input type="number" name="sort" value="<?= e($p['sort'] ?? 0) ?>"></div>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="checkbox" name="featured"<?= !empty($p['featured']) ? ' checked' : '' ?>> Vedette</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="checkbox" name="active"<?= !empty($p['active']) ? ' checked' : '' ?>> Visible</label>
    </div>
    <button class="btn btn--primary" type="submit"><?= e(t('admin_save')) ?></button>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
