<?php
/**
 * ViceHub X — Gestionnaire des produits Amazon (page « Bons plans »).
 * Ajouter / éditer / coller le lien produit exact / réordonner / supprimer,
 * sans toucher au SQL. Le tag d'affiliation est ajouté automatiquement au rendu.
 */
$ADMIN_TITLE = 'ViceHub X — Deals Amazon';
require __DIR__ . '/../includes/admin_header.php';

// Colonnes qui peuvent manquer sur une ancienne base : on les ajoute au besoin.
try { db()->exec('ALTER TABLE affiliate_links ADD COLUMN IF NOT EXISTS sort INT NOT NULL DEFAULT 0'); } catch (Throwable $e) {}
try { db()->exec('ALTER TABLE affiliate_links ADD COLUMN IF NOT EXISTS image VARCHAR(400) DEFAULT NULL'); } catch (Throwable $e) {}

/** Nettoie une URL Amazon collée : retire un éventuel tag d'affiliation étranger
 *  (le nôtre est ajouté au rendu) et quelques paramètres de suivi inutiles. */
function deals_clean_url(string $url): string
{
    $url = trim($url);
    if ($url === '') { return ''; }
    // Retire ?tag=... ou &tag=... (ne JAMAIS garder le tag d'un autre affilié).
    $url = preg_replace('/([?&])tag=[^&#]*/i', '$1', $url) ?? $url;
    // Nettoie les séparateurs orphelins laissés par la suppression.
    $url = preg_replace('/[?&]+(#|$)/', '$1', $url) ?? $url;
    $url = str_replace(['?&', '&&'], ['?', '&'], $url);
    return rtrim($url, '?&');
}

$flash = null;
$act = $_POST['action'] ?? '';
if ($act !== '' && !verify_csrf()) { $flash = ['err', 'Jeton CSRF invalide.']; $act = ''; }

if ($act === 'add') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $url   = deals_clean_url((string) ($_POST['url'] ?? ''));
    if ($title === '' || $url === '') {
        $flash = ['err', 'Titre et lien obligatoires.'];
    } else {
        $st = db()->prepare('INSERT INTO affiliate_links (title, description, url, platform, badge, image, active, sort) VALUES (?,?,?,?,?,?,1,?)');
        $st->execute([
            mb_substr($title, 0, 160),
            mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 300),
            mb_substr($url, 0, 400),
            mb_substr(trim((string) ($_POST['platform'] ?? 'Amazon')) ?: 'Amazon', 0, 60),
            mb_substr(trim((string) ($_POST['badge'] ?? '')), 0, 40),
            mb_substr(trim((string) ($_POST['image'] ?? '')), 0, 400) ?: null,
            (int) ($_POST['sort'] ?? 100),
        ]);
        $flash = ['ok', '✅ Produit ajouté.'];
    }
}
if ($act === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $st = db()->prepare('UPDATE affiliate_links SET title=?, description=?, url=?, platform=?, badge=?, image=?, sort=?, active=? WHERE id=?');
        $st->execute([
            mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 160),
            mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 300),
            mb_substr(deals_clean_url((string) ($_POST['url'] ?? '')), 0, 400),
            mb_substr(trim((string) ($_POST['platform'] ?? 'Amazon')) ?: 'Amazon', 0, 60),
            mb_substr(trim((string) ($_POST['badge'] ?? '')), 0, 40),
            mb_substr(trim((string) ($_POST['image'] ?? '')), 0, 400) ?: null,
            (int) ($_POST['sort'] ?? 100),
            !empty($_POST['active']) ? 1 : 0,
            $id,
        ]);
        $flash = ['ok', '💾 Produit mis à jour.'];
    }
}
if ($act === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) { db()->prepare('DELETE FROM affiliate_links WHERE id=?')->execute([$id]); $flash = ['ok', '🗑️ Produit supprimé.']; }
}

$tag  = trim((string) get_setting('amazon_tag', ''));
try { $rows = db()->query('SELECT * FROM affiliate_links ORDER BY sort ASC, id ASC')->fetchAll(); }
catch (Throwable $e) { $rows = db()->query('SELECT * FROM affiliate_links ORDER BY id ASC')->fetchAll(); }
$activeCount = 0; foreach ($rows as $r) { if ((int) $r['active'] === 1) { $activeCount++; } }
?>
<div class="admin-bar"><h1>🛒 Deals Amazon</h1>
    <a class="btn btn--ghost" href="<?= e(url('pages/deals.php')) ?>" target="_blank">Voir la page ↗</a>
</div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>" style="margin:1rem 0"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="glass" style="padding:.9rem 1.1rem;border-radius:12px;margin:.6rem 0;font-size:.9rem;border:1px solid <?= $tag === '' ? 'rgba(255,93,93,.4)' : 'rgba(57,255,170,.3)' ?>">
    <?php if ($tag === ''): ?>
        🔴 <strong>Tag Amazon manquant.</strong> Les liens fonctionnent mais ne te rapportent <strong>aucune commission</strong>. Renseigne-le dans <a href="<?= e(url('admin/settings.php')) ?>">Réglages → Tag Amazon</a>.
    <?php else: ?>
        ✅ Tag Amazon : <strong><?= e($tag) ?></strong> — ajouté automatiquement à chaque lien. <?= $activeCount ?> produit(s) actif(s).
    <?php endif; ?>
    <div class="muted" style="margin-top:.4rem">💡 Pour un <strong>lien produit unique</strong> : ouvre le produit sur Amazon → copie l'URL de la page → colle-la dans le champ « Lien » (le tag d'un autre affilié est automatiquement retiré).</div>
</div>

<!-- Ajouter -->
<div class="glass" style="padding:1.2rem;border-radius:14px;margin:1rem 0">
    <h2 style="margin-top:0;font-size:1.05rem">➕ Ajouter un produit</h2>
    <form method="post" style="display:grid;gap:.6rem;max-width:760px">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <input type="text" name="title" placeholder="Titre (ex. Manette DualSense PS5)" required>
        <input type="text" name="url" placeholder="Lien Amazon du produit (https://www.amazon.fr/dp/…)" required>
        <input type="text" name="image" placeholder="Photo du produit (URL d'image) — optionnel, sinon vignette auto">
        <input type="text" name="description" placeholder="Description courte (pourquoi c'est bien pour GTA 6)">
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
            <input type="text" name="badge" placeholder="Catégorie (ex. Console)" style="flex:1;min-width:140px">
            <input type="number" name="sort" value="100" title="Ordre d'affichage" style="width:110px">
        </div>
        <div><button class="btn btn--primary" type="submit">➕ Ajouter</button></div>
    </form>
</div>

<!-- Liste / édition -->
<h2 style="font-size:1.05rem;margin:1.4rem 0 .6rem"><?= count($rows) ?> produit(s)</h2>
<?php foreach ($rows as $r): ?>
    <div class="glass" style="padding:1rem 1.1rem;border-radius:12px;margin:.6rem 0;<?= (int) $r['active'] === 1 ? '' : 'opacity:.55' ?>">
        <form method="post" style="display:grid;gap:.55rem">
            <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <input type="text" name="title" value="<?= e($r['title']) ?>" required>
            <input type="text" name="url" value="<?= e($r['url']) ?>" required>
            <input type="text" name="image" value="<?= e((string) ($r['image'] ?? '')) ?>" placeholder="Photo du produit (URL d'image) — sinon vignette auto">
            <input type="text" name="description" value="<?= e((string) $r['description']) ?>">
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
                <input type="text" name="platform" value="<?= e((string) $r['platform']) ?>" placeholder="Amazon" style="width:120px">
                <input type="text" name="badge" value="<?= e((string) $r['badge']) ?>" placeholder="Catégorie" style="flex:1;min-width:120px">
                <input type="number" name="sort" value="<?= (int) ($r['sort'] ?? 100) ?>" style="width:90px" title="Ordre">
                <label style="display:flex;gap:.4rem;align-items:center;cursor:pointer;font-size:.9rem"><input type="checkbox" name="active" value="1" <?= (int) $r['active'] === 1 ? 'checked' : '' ?>> Actif</label>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <button class="btn btn--primary" type="submit">💾 Enregistrer</button>
                <a class="btn btn--ghost" href="<?= e(amazon_url((string) $r['url'])) ?>" target="_blank" rel="nofollow noopener">Tester ↗</a>
            </div>
        </form>
        <form method="post" onsubmit="return confirm('Supprimer ce produit ?')" style="margin:.4rem 0 0">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn--ghost" type="submit" style="color:#ff7a7a">🗑️ Supprimer</button>
        </form>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
