<?php
require __DIR__ . '/../includes/admin_header.php';

$flash = null;
$cats = get_categories();
$badge_keys = array_keys(badges());
article_ensure_seo_cols();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        try {
            $title  = trim((string) ($_POST['title'] ?? ''));
            $body   = (string) ($_POST['body'] ?? '');
            if ($title === '') {
                throw new RuntimeException('Le titre est obligatoire.');
            }
            $slug   = unique_slug(trim((string) ($_POST['slug'] ?? '')) ?: $title, 'articles');
            $lang   = in_array($_POST['lang'] ?? 'fr', ['fr', 'en'], true) ? $_POST['lang'] : 'fr';
            $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
            $badge  = in_array($_POST['badge'] ?? '', $badge_keys, true) ? $_POST['badge'] : null;
            $catId  = (int) ($_POST['category_id'] ?? 0) ?: null;
            $excerpt = mb_substr(trim((string) ($_POST['excerpt'] ?? '')), 0, 400);
            $metaTitle = mb_substr(trim((string) ($_POST['meta_title'] ?? '')), 0, 90) ?: null;
            $metaDesc  = mb_substr(trim((string) ($_POST['meta_description'] ?? '')), 0, 200) ?: null;
            $image  = handle_image_upload('image');
            $pub    = $status === 'published' ? date('Y-m-d H:i:s') : null;

            $stmt = db()->prepare(
                'INSERT INTO articles (category_id, lang, title, slug, excerpt, meta_title, meta_description, body, badge, image, status, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$catId, $lang, $title, $slug, $excerpt, $metaTitle, $metaDesc, $body, $badge, $image, $status, $pub]);
            redirect(url('admin/articles.php'));
        } catch (Throwable $ex) {
            $flash = ['err', $ex->getMessage()];
        }
    }
}
?>
<div class="admin-bar">
    <h1><?= e(t('admin_new')) ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('admin/articles.php')) ?>">← <?= e(t('admin_articles')) ?></a>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form glass" style="max-width:760px;padding:1.6rem;border-radius:18px">
    <?= csrf_field() ?>
    <div><label>Titre *</label><input type="text" name="title" required maxlength="200" value="<?= e($_POST['title'] ?? '') ?>"></div>
    <div><label>Slug (auto si vide)</label><input type="text" name="slug" maxlength="220" value="<?= e($_POST['slug'] ?? '') ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
        <div><label>Catégorie</label>
            <select name="category_id">
                <option value="">—</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Langue</label>
            <select name="lang"><option value="fr">FR</option><option value="en">EN</option></select>
        </div>
        <div><label>Badge</label>
            <select name="badge">
                <option value="">—</option>
                <?php foreach ($badge_keys as $k): ?>
                    <option value="<?= e($k) ?>"><?= e(badges()[$k]['fr']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div><label>Extrait</label><textarea name="excerpt" maxlength="400" style="min-height:70px"><?= e($_POST['excerpt'] ?? '') ?></textarea></div>
    <fieldset style="border:1px solid var(--glass-brd);border-radius:12px;padding:.8rem 1rem;margin:.4rem 0">
        <legend style="padding:0 .4rem;font-size:.85rem">🔎 SEO (facultatif — sinon repli auto sur titre/extrait)</legend>
        <div><label>Titre SEO <small class="muted">(≤ 60 car., mot-clé en tête)</small></label><input type="text" name="meta_title" maxlength="90" value="<?= e($_POST['meta_title'] ?? '') ?>" placeholder="GTA 6 sur PC : date de sortie et tout ce qu'on sait (2026)"></div>
        <div><label>Meta description <small class="muted">(150-160 car.)</small></label><textarea name="meta_description" maxlength="200" style="min-height:56px" placeholder="Mot-clé en tête, un chiffre/une date, un appel à l'action."><?= e($_POST['meta_description'] ?? '') ?></textarea></div>
    </fieldset>
    <div><label>Contenu (HTML simple autorisé)</label><textarea name="body" style="min-height:200px"><?= e($_POST['body'] ?? '') ?></textarea></div>
    <div><label>Image (JPG / PNG / WebP, max 3 Mo)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
    <div><label>Statut</label>
        <select name="status"><option value="draft">draft</option><option value="published">published</option></select>
    </div>
    <button class="btn btn--primary" type="submit"><?= e(t('admin_save')) ?></button>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
