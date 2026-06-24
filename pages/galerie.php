<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$flash = null;

// Soumission d'un fan-art (membre connecté)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) {
        redirect(with_lang(url('pages/login.php')));
    }
    if (!verify_csrf()) {
        $flash = ['err', 'Session expirée, réessayez.'];
    } else {
        try {
            $title = trim((string) ($_POST['title'] ?? ''));
            if (mb_strlen($title) < 3) {
                throw new RuntimeException('Donne un titre à ton œuvre (3 caractères min).');
            }
            $img = handle_image_upload('image');
            if (!$img) {
                throw new RuntimeException('Ajoute une image (JPG / PNG / WebP, max 3 Mo).');
            }
            // Staff/contributeur = publication directe ; membres = modération
            $status = in_array(user_role(), ['admin', 'editor', 'contributor'], true) ? 'approved' : 'pending';
            db()->prepare('INSERT INTO fanarts (user_id, title, image, status) VALUES (?, ?, ?, ?)')
                ->execute([(int) current_user()['id'], $title, $img, $status]);
            $flash = ['ok', $status === 'approved'
                ? 'Fan-art publié ! Merci pour ta création. 🎨'
                : 'Merci ! Ton fan-art sera affiché après validation par l’équipe.'];
        } catch (Throwable $ex) {
            $flash = ['err', $ex->getMessage()];
        }
    }
}

$arts = get_fanarts(true);
$SEO_TITLE = ($fr ? 'Galerie de fan-arts GTA VI' : 'GTA VI fan-art gallery') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'La galerie de fan-arts de la communauté GTA VI : créations, captures et œuvres inspirées de Vice City. Partage les tiennes !'
    : 'The GTA VI community fan-art gallery: creations and artwork inspired by Vice City.';
$SEO_OG_IMAGE = cdn_url('nightlife.png');
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow">🎨 ViceHub X</span>
            <h1><?= $fr ? 'Galerie de fan-arts' : 'Fan-art gallery' ?></h1>
        </div>
        <a class="link-all" href="#partager"><?= $fr ? 'Partager mon œuvre' : 'Share yours' ?> →</a>
    </div>
    <p class="muted" style="max-width:720px"><?= $fr
        ? 'Les créations de la communauté inspirées de Vice City. Poste les tiennes : captures, montages, illustrations.'
        : 'Community creations inspired by Vice City. Post yours.' ?></p>

    <?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

    <?php if ($arts): ?>
    <div class="art-grid">
        <?php foreach ($arts as $a): ?>
            <figure class="art-card glass reveal" id="item-fanart<?= (int) $a['id'] ?>">
                <img src="<?= e(img_src($a['image'])) ?>" alt="<?= e($a['title']) ?>" loading="lazy" onerror="this.closest('.art-card').style.display='none'">
                <figcaption>
                    <span class="art-title"><?= e($a['title']) ?></span>
                    <span class="art-foot">
                        <?php if (!empty($a['username'])): ?><a class="muted" href="<?= e(with_lang(url('pages/profil.php?u=' . urlencode($a['username'])))) ?>">@<?= e($a['author']) ?></a><?php else: ?><span class="muted">@<?= e($a['author'] ?: 'anonyme') ?></span><?php endif; ?>
                        <?php $lc = like_count('fanart', (int) $a['id']); $liked = user_liked('fanart', (int) $a['id'], is_logged_in() ? (int) current_user()['id'] : null); ?>
                        <?php if (is_logged_in()): ?>
                            <form method="post" action="<?= e(url('like.php')) ?>" class="like-form">
                                <?= csrf_field() ?><input type="hidden" name="kind" value="fanart"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="return" value="<?= e(url('pages/galerie.php')) ?>">
                                <button class="like-btn<?= $liked ? ' like-btn--on' : '' ?>" type="submit">💜 <span><?= $lc ?></span></button>
                            </form>
                        <?php else: ?><span class="like-btn like-btn--static">💜 <?= $lc ?></span><?php endif; ?>
                    </span>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="muted"><?= $fr ? 'Aucune œuvre pour l’instant. Sois le premier à partager !' : 'No artwork yet. Be the first!' ?></p>
    <?php endif; ?>

    <div id="partager" class="art-upload glass">
        <h2><?= $fr ? 'Partager un fan-art' : 'Share a fan-art' ?></h2>
        <?php if (is_logged_in()): ?>
            <form method="post" enctype="multipart/form-data" class="form" style="max-width:none">
                <?= csrf_field() ?>
                <div><label><?= $fr ? 'Titre' : 'Title' ?></label><input type="text" name="title" required maxlength="160"></div>
                <div><label><?= $fr ? 'Image (JPG / PNG / WebP, max 3 Mo)' : 'Image' ?></label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>
                <button class="btn btn--primary" type="submit"><?= $fr ? 'Envoyer' : 'Submit' ?></button>
                <p class="muted" style="font-size:.8rem"><?= $fr ? 'Ton œuvre sera visible après validation (ou immédiatement si tu es contributeur).' : 'Your artwork appears after moderation.' ?></p>
            </form>
        <?php else: ?>
            <p class="muted"><?= $fr ? 'Connecte-toi pour partager tes créations.' : 'Log in to share your art.' ?></p>
            <a class="btn btn--primary" href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= $fr ? 'Se connecter' : 'Log in' ?></a>
        <?php endif; ?>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
