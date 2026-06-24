<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = t('page_contact_title') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Contactez l’équipe ViceHub X : presse, partenariats, signalements.'
    : 'Contact the ViceHub X team: press, partnerships, reports.';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $name  = trim((string) ($_POST['name'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $msg   = trim((string) ($_POST['message'] ?? ''));
    if ($name && $email && $msg) {
        // Destinataire : réglage contact_email, sinon l'adresse d'expédition configurée.
        $to = (string) (get_setting('contact_email', '') ?: mail_from_address());
        $body = '<div style="font-family:sans-serif">'
            . '<h2>Nouveau message — ViceHub X</h2>'
            . '<p><strong>De :</strong> ' . e($name) . ' &lt;' . e($email) . '&gt;</p>'
            . '<p style="white-space:pre-wrap">' . nl2br(e($msg)) . '</p></div>';
        // On envoie si une voie e-mail est disponible (Resend ou mail système).
        @send_mail($to, 'Contact ViceHub X — ' . $name, $body);
        $flash = ['ok', t('contact_ok')];
    } else {
        $flash = ['err', lang() === 'fr' ? 'Veuillez remplir tous les champs.' : 'Please fill in all fields.'];
    }
}
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">✉️ ViceHub X</span>
    <h1><?= e(t('page_contact_title')) ?></h1>
    <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
    <?php endif; ?>
    <form method="post" class="form glass" style="padding:1.6rem;border-radius:18px;margin-top:1.2rem">
        <?= csrf_field() ?>
        <div><label><?= e(t('contact_name')) ?></label><input type="text" name="name" required maxlength="120"></div>
        <div><label><?= e(t('contact_email')) ?></label><input type="email" name="email" required></div>
        <div><label><?= e(t('contact_msg')) ?></label><textarea name="message" required maxlength="3000"></textarea></div>
        <button class="btn btn--primary" type="submit"><?= e(t('contact_btn')) ?></button>
    </form>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
