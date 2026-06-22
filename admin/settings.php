<?php
$ADMIN_TITLE = 'ViceHub X — Réglages';
require __DIR__ . '/../includes/admin_header.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        set_setting('adsense_client', trim((string) ($_POST['adsense_client'] ?? '')));
        set_setting('hero_video', trim((string) ($_POST['hero_video'] ?? '')));
        set_setting('trailer_url', trim((string) ($_POST['trailer_url'] ?? '')));
        $rd = trim((string) ($_POST['release_date'] ?? ''));
        if ($rd !== '') {
            // datetime-local -> ISO
            set_setting('release_date', str_replace(' ', 'T', $rd) . ':00');
        }
        $flash = ['ok', 'Réglages enregistrés.'];
    }
}

$adsense = (string) get_setting('adsense_client', '');
$video   = (string) get_setting('hero_video', '');
$trailer = (string) get_setting('trailer_url', '');
$release = (string) release_date();
// ISO -> valeur datetime-local (YYYY-MM-DDTHH:MM)
$release_input = substr(str_replace(' ', 'T', $release), 0, 16);
?>
<div class="admin-bar">
    <h1>Réglages</h1>
    <a class="btn btn--ghost" href="<?= e(url('index.php')) ?>" target="_blank">Voir le site ↗</a>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<form method="post" class="form glass" style="max-width:680px;padding:1.6rem;border-radius:18px">
    <?= csrf_field() ?>

    <div>
        <label>Date de sortie (compte à rebours)</label>
        <input type="datetime-local" name="release_date" value="<?= e($release_input) ?>">
        <small class="muted">Par défaut : 19 novembre 2026.</small>
    </div>

    <div>
        <label>Google AdSense — identifiant client</label>
        <input type="text" name="adsense_client" value="<?= e($adsense) ?>" placeholder="ca-pub-0000000000000000">
        <small class="muted">Laissez vide pour masquer les pubs (zones « réservées » en dev). Renseignez votre <code>ca-pub-…</code> pour activer.</small>
    </div>

    <div>
        <label>Vidéo de fond du hero (URL .mp4 / .webm)</label>
        <input type="url" name="hero_video" value="<?= e($video) ?>" placeholder="https://… ou /public/assets/video/hero.mp4">
        <small class="muted">Laissez vide pour garder l’animation synthwave (route néon). Si renseigné, la vidéo remplace le canvas.</small>
    </div>

    <div>
        <label>URL de la bande-annonce</label>
        <input type="url" name="trailer_url" value="<?= e($trailer) ?>" placeholder="https://www.youtube.com/watch?v=…">
        <small class="muted">Lien ouvert par le bloc « Bande-annonce ». Vide = renvoie vers Trailer Lab.</small>
    </div>

    <button class="btn btn--primary" type="submit">Enregistrer</button>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
