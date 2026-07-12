<?php
$ADMIN_TITLE = 'ViceHub X — Nouveau produit';
require __DIR__ . '/../includes/admin_header.php';

$flash = null;
$cats = product_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        try {
            $name = trim((string) ($_POST['name'] ?? ''));
            $url  = trim((string) ($_POST['url'] ?? ''));
            $sale_type = in_array($_POST['sale_type'] ?? 'external', ['external', 'stripe'], true) ? $_POST['sale_type'] : 'external';
            $stripe_price_id = trim((string) ($_POST['stripe_price_id'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Le nom est obligatoire.');
            }
            if ($sale_type === 'external' && $url === '') {
                throw new RuntimeException('Le lien d’achat (revendeur / affiliation) est obligatoire pour un produit externe.');
            }
            // Slug rendu UNIQUE (jamais d'erreur « Duplicate entry » : -2, -3… au besoin).
            $slug     = unique_slug(trim((string) ($_POST['slug'] ?? '')) ?: $name, 'products');
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
            $image    = handle_image_upload('image') ?: trim((string) ($_POST['image_url'] ?? ''));

            $stmt = db()->prepare(
                'INSERT INTO products (name, slug, description, category, price, currency, image, sale_type, url, stripe_price_id, merchant, badge, featured, active, sort, lang)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $slug, $desc, $category, $price, $currency, $image, $sale_type, $url ?: null, $stripe_price_id ?: null, $merchant ?: null, $badge ?: null, $featured, $active, $sort, $lang]);
            redirect(url('admin/products.php'));
        } catch (Throwable $ex) {
            $flash = ['err', $ex->getMessage()];
        }
    }
}
?>
<div class="admin-bar">
    <h1>Nouveau produit</h1>
    <a class="btn btn--ghost" href="<?= e(url('admin/products.php')) ?>">← Boutique</a>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form glass" style="max-width:760px;padding:1.6rem;border-radius:18px">
    <?= csrf_field() ?>
    <div><label>Nom *</label><input type="text" name="name" required maxlength="160" value="<?= e($_POST['name'] ?? '') ?>"></div>
    <div><label>Slug (auto si vide)</label><input type="text" name="slug" maxlength="180" value="<?= e($_POST['slug'] ?? '') ?>"></div>
    <div><label>Description</label><textarea name="description" maxlength="400" style="min-height:80px"><?= e($_POST['description'] ?? '') ?></textarea></div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
        <div><label>Catégorie</label>
            <select name="category">
                <?php foreach ($cats as $k => $label): ?>
                    <option value="<?= e($k) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Prix</label><input type="text" name="price" placeholder="24.90" value="<?= e($_POST['price'] ?? '') ?>"></div>
        <div><label>Devise</label>
            <select name="currency"><option value="EUR">EUR €</option><option value="USD">USD $</option><option value="GBP">GBP £</option></select>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div><label>Type de vente</label>
            <select name="sale_type">
                <option value="stripe"<?= ($_POST['sale_type'] ?? '') === 'stripe' ? ' selected' : '' ?>>Stripe (vente directe)</option>
                <option value="external"<?= ($_POST['sale_type'] ?? 'external') === 'external' ? ' selected' : '' ?>>Revendeur / affilié (lien externe)</option>
            </select>
        </div>
        <div><label>ID prix Stripe (optionnel)</label><input type="text" name="stripe_price_id" maxlength="120" placeholder="price_… (sinon prix ci-dessus)" value="<?= e($_POST['stripe_price_id'] ?? '') ?>"></div>
    </div>
    <div><label>Lien d’achat (si revendeur / affilié)</label><input type="url" name="url" maxlength="500" placeholder="https://www.amazon.fr/...?tag=votre-tag" value="<?= e($_POST['url'] ?? '') ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div><label>Marchand</label><input type="text" name="merchant" maxlength="60" placeholder="Amazon, ViceHub Store…" value="<?= e($_POST['merchant'] ?? '') ?>"></div>
        <div><label>Badge</label><input type="text" name="badge" maxlength="40" placeholder="Best-seller, Édition IA…" value="<?= e($_POST['badge'] ?? '') ?>"></div>
    </div>
    <div><label>Image (upload JPG / PNG / WebP, max 3 Mo)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
    <div><label>… ou chemin / URL d’image</label><input type="text" name="image_url" placeholder="/public/assets/img/shop/poster-synthwave.png" value="<?= e($_POST['image_url'] ?? '') ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr auto auto;gap:1rem;align-items:end">
        <div><label>Langue</label><select name="lang"><option value="fr">FR</option><option value="en">EN</option></select></div>
        <div><label>Ordre (tri)</label><input type="number" name="sort" value="<?= e($_POST['sort'] ?? '0') ?>"></div>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="checkbox" name="featured"> Vedette</label>
        <label style="display:flex;gap:.4rem;align-items:center"><input type="checkbox" name="active" checked> Visible</label>
    </div>
    <button class="btn btn--primary" type="submit"><?= e(t('admin_save')) ?></button>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
