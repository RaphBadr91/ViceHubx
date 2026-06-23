<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();
$me = current_user();
$cats = get_categories();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Session expirée, réessayez.'];
    } else {
        try {
            $title = trim((string) ($_POST['title'] ?? ''));
            $body  = trim((string) ($_POST['body'] ?? ''));
            if (mb_strlen($title) < 6) {
                throw new RuntimeException('Le titre doit faire au moins 6 caractères.');
            }
            if (mb_strlen($body) < 40) {
                throw new RuntimeException('Le contenu doit faire au moins 40 caractères.');
            }
            $slug = slugify($title) . '-' . base_convert((string) time(), 10, 36);
            $catId = (int) ($_POST['category_id'] ?? 0) ?: null;
            $excerpt = mb_substr(trim((string) ($_POST['excerpt'] ?? '')), 0, 400);
            // Contributeurs & staff = publication directe ; membres = file de modération
            $status = in_array(user_role(), ['admin', 'editor', 'contributor'], true) ? 'published' : 'pending';
            $pub = $status === 'published' ? date('Y-m-d H:i:s') : null;
            $image = handle_image_upload('image');

            $stmt = db()->prepare(
                'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, author_id, status, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$catId, lang(), $title, $slug, $excerpt, $body, $image, (int) $me['id'], $status, $pub]);
            $flash = ['ok', $status === 'published'
                ? 'Article publié ! Merci pour votre contribution.'
                : 'Merci ! Votre article a été soumis et sera publié après validation par l’équipe.'];
            $_POST = [];
        } catch (Throwable $ex) {
            $flash = ['err', $ex->getMessage()];
        }
    }
}

$SEO_TITLE = (lang() === 'fr' ? 'Proposer un article' : 'Submit an article') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:760px">
    <span class="eyebrow">✍️ ViceHub X</span>
    <h1><?= lang() === 'fr' ? 'Proposer un article' : 'Submit an article' ?></h1>
    <p class="muted"><?= lang() === 'fr'
        ? 'Partagez une actu, un guide ou une analyse. Votre proposition sera relue par l’équipe avant publication.'
        : 'Share news, a guide or analysis. Your submission will be reviewed before publishing.' ?></p>

    <?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form glass" style="padding:1.6rem;border-radius:18px;max-width:none">
        <?= csrf_field() ?>
        <div><label><?= lang() === 'fr' ? 'Titre' : 'Title' ?> *</label>
            <input type="text" name="title" required maxlength="200" value="<?= e($_POST['title'] ?? '') ?>"></div>
        <div><label><?= lang() === 'fr' ? 'Catégorie' : 'Category' ?></label>
            <select name="category_id">
                <option value="">—</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label><?= lang() === 'fr' ? 'Accroche (résumé)' : 'Excerpt' ?></label>
            <textarea name="excerpt" maxlength="400" style="min-height:70px"><?= e($_POST['excerpt'] ?? '') ?></textarea></div>
        <div><label><?= lang() === 'fr' ? 'Contenu' : 'Content' ?> * <span class="muted">(<?= lang() === 'fr' ? 'HTML simple autorisé' : 'basic HTML allowed' ?>)</span></label>
            <textarea name="body" required style="min-height:240px"><?= e($_POST['body'] ?? '') ?></textarea></div>
        <div><label><?= lang() === 'fr' ? 'Image (JPG/PNG/WebP, max 3 Mo)' : 'Image' ?></label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
        <button class="btn btn--primary" type="submit"><?= lang() === 'fr' ? 'Soumettre' : 'Submit' ?></button>
    </form>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
