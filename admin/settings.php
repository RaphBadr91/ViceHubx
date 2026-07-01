<?php
$ADMIN_TITLE = 'ViceHub X — Réglages';
require __DIR__ . '/../includes/admin_header.php';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } elseif (($_POST['action'] ?? '') === 'test_email') {
        // --- Envoi d'un e-mail de test ---
        $dest = filter_input(INPUT_POST, 'test_to', FILTER_VALIDATE_EMAIL) ?: (current_user()['email'] ?? '');
        if (!$dest) {
            $flash = ['err', 'Renseignez une adresse e-mail de test valide.'];
        } else {
            $ok = send_mail(
                $dest,
                'Test ViceHub X ✅',
                '<div style="font-family:sans-serif"><h2>Ça marche ! 🌴</h2>'
                . '<p>Cet e-mail de test confirme que l’envoi automatique est bien configuré'
                . (resend_enabled() ? ' via <strong>Resend</strong>' : ' via le serveur (mail)')
                . '.</p><p>— ViceHub X</p></div>'
            );
            $flash = $ok
                ? ['ok', 'E-mail de test envoyé à ' . $dest . ' ' . (resend_enabled() ? '(via Resend).' : '(via mail système).')]
                : ['err', 'Échec de l’envoi. Vérifiez la clé Resend, le domaine vérifié et l’adresse d’expédition.'];
        }
    } elseif (($_POST['action'] ?? '') === 'change_password') {
        // --- Changement du mot de passe admin ---
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $uid = (int) (current_user()['id'] ?? 0);
        $row = $uid ? db()->prepare('SELECT password_hash FROM users WHERE id = ?') : null;
        if ($row) { $row->execute([$uid]); }
        $hash = $row ? (string) $row->fetchColumn() : '';
        if (!$uid || $hash === '' || !password_verify($cur, $hash)) {
            $flash = ['err', 'Mot de passe actuel incorrect.'];
        } elseif (strlen($new) < 8) {
            $flash = ['err', 'Le nouveau mot de passe doit faire au moins 8 caractères.'];
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_BCRYPT), $uid]);
            $flash = ['ok', '✅ Mot de passe modifié.'];
        }
    } else {
        set_setting('adsense_client', trim((string) ($_POST['adsense_client'] ?? '')));
        set_setting('adsense_slot', trim((string) ($_POST['adsense_slot'] ?? '')));
        set_setting('hero_video', trim((string) ($_POST['hero_video'] ?? '')));
        set_setting('trailer_url', trim((string) ($_POST['trailer_url'] ?? '')));
        set_setting('map_url', trim((string) ($_POST['map_url'] ?? '')));
        // --- Boutique / Stripe ---
        set_setting('stripe_publishable_key', trim((string) ($_POST['stripe_publishable_key'] ?? '')));
        set_setting('shop_currency', strtoupper(trim((string) ($_POST['shop_currency'] ?? 'EUR'))) ?: 'EUR');
        set_setting('shop_currency_en', strtoupper(trim((string) ($_POST['shop_currency_en'] ?? 'USD'))) ?: 'USD');
        // Clés secrètes : remplacées uniquement si un nouveau champ est saisi.
        $sk = trim((string) ($_POST['stripe_secret_key'] ?? ''));
        if ($sk !== '') { set_setting('stripe_secret_key', $sk); }
        $ws = trim((string) ($_POST['stripe_webhook_secret'] ?? ''));
        if ($ws !== '') { set_setting('stripe_webhook_secret', $ws); }
        $rd = trim((string) ($_POST['release_date'] ?? ''));
        if ($rd !== '') {
            // datetime-local -> ISO
            set_setting('release_date', str_replace(' ', 'T', $rd) . ':00');
        }
        // --- E-mail / Resend ---
        set_setting('mail_from', trim((string) ($_POST['mail_from'] ?? '')));
        set_setting('mail_from_name', trim((string) ($_POST['mail_from_name'] ?? '')));
        set_setting('contact_email', trim((string) ($_POST['contact_email'] ?? '')));
        $rk = trim((string) ($_POST['resend_api_key'] ?? ''));
        if ($rk !== '') { set_setting('resend_api_key', $rk); }
        // --- Google / SEO ---
        // On garde uniquement le code de vérification (pas la balise complète).
        $gv = trim((string) ($_POST['google_site_verification'] ?? ''));
        if (preg_match('/content=["\']([^"\']+)["\']/i', $gv, $m)) { $gv = $m[1]; }
        set_setting('google_site_verification', $gv);
        set_setting('analytics_id', trim((string) ($_POST['analytics_id'] ?? '')));
        $flash = ['ok', 'Réglages enregistrés.'];
    }
}

$mail_from_v = (string) get_setting('mail_from', '');
$mail_name_v = (string) get_setting('mail_from_name', '');
$contact_v   = (string) get_setting('contact_email', '');
$has_resend  = resend_enabled();

$gverify = (string) get_setting('google_site_verification', '');
$ga_id   = (string) get_setting('analytics_id', '');
$adsense = (string) get_setting('adsense_client', '');
$adslot  = (string) get_setting('adsense_slot', '');
$video   = (string) get_setting('hero_video', '');
$trailer = (string) get_setting('trailer_url', '');
$map_url = (string) get_setting('map_url', 'https://map.stateofleonida.net/?map=vi&lat=3904.00&lng=-10452.00');
$stripe_pk  = (string) get_setting('stripe_publishable_key', '');
$shop_cur   = (string) get_setting('shop_currency', 'EUR');
$shop_cur_en = (string) get_setting('shop_currency_en', 'USD');
$has_secret = stripe_secret() !== '';
$has_whsec  = stripe_webhook_secret() !== '';
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

    <hr style="border:none;border-top:1px solid var(--glass-brd);margin:1.4rem 0 .4rem">
    <h2 style="font-size:1.1rem;margin:0 0 .2rem">🔎 Google — Référencement (SEO)</h2>
    <div>
        <label>Google Search Console — code de vérification</label>
        <input type="text" name="google_site_verification" value="<?= e($gverify) ?>" placeholder="collez le code (ou la balise meta entière)">
        <small class="muted">Dans <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Search Console</a> → « Balise HTML » : copiez le <code>content="…"</code> ici (la balise complète marche aussi). Active la vérification du site.</small>
    </div>

    <div>
        <label>Google Analytics 4 — ID de mesure</label>
        <input type="text" name="analytics_id" value="<?= e($ga_id) ?>" placeholder="G-XXXXXXXXXX">
        <small class="muted">Statistiques de visites. Ne se charge <strong>qu'après acceptation des cookies</strong> (conforme RGPD).</small>
    </div>

    <div>
        <label>Google AdSense — identifiant client</label>
        <input type="text" name="adsense_client" value="<?= e($adsense) ?>" placeholder="ca-pub-0000000000000000">
        <small class="muted">Vide = <strong>aucune pub affichée</strong> (rien, pas de zone réservée). Renseignez <code>ca-pub-…</code> + un slot pour activer.</small>
    </div>

    <div>
        <label>Google AdSense — ID d'emplacement (slot)</label>
        <input type="text" name="adsense_slot" value="<?= e($adslot) ?>" placeholder="1234567890">
        <small class="muted">Les pubs n'apparaissent que si le client <em>et</em> le slot sont renseignés.</small>
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

    <div>
        <label>URL de la carte interactive (Map Lab)</label>
        <input type="url" name="map_url" value="<?= e($map_url) ?>" placeholder="https://map.stateofleonida.net/?map=vi">
        <small class="muted">Carte intégrée en iframe sur Map Lab. Vide = masque l’iframe (garde la carte stylisée).</small>
    </div>

    <hr style="border:0;border-top:1px solid var(--glass-brd);margin:.6rem 0">
    <h2 style="font-size:1.1rem;margin:0">💳 Boutique — Paiement Stripe</h2>
    <p class="muted" style="font-size:.85rem;margin:.2rem 0 .4rem">
        Pour vendre vos produits en direct (affiches, goodies). Créez un compte sur
        <a href="https://dashboard.stripe.com" target="_blank" rel="noopener">stripe.com</a>,
        puis collez vos clés ci-dessous. État :
        <strong><?= stripe_enabled() ? '🟢 Paiement activé' : '⚪ Non configuré' ?></strong>
    </p>

    <div>
        <label>Clé publiable Stripe (pk_…)</label>
        <input type="text" name="stripe_publishable_key" value="<?= e($stripe_pk) ?>" placeholder="pk_live_… ou pk_test_…">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div>
            <label>Devise — visiteurs francophones (FR)</label>
            <select name="shop_currency">
                <?php foreach (['EUR', 'USD', 'GBP', 'CAD', 'CHF'] as $cv): ?>
                    <option value="<?= $cv ?>"<?= $shop_cur === $cv ? ' selected' : '' ?>><?= $cv ?></option>
                <?php endforeach; ?>
            </select>
            <small class="muted">Affichée en € par défaut (5&nbsp;€).</small>
        </div>
        <div>
            <label>Devise — visiteurs anglophones (EN)</label>
            <select name="shop_currency_en">
                <?php foreach (['USD', 'EUR', 'GBP', 'CAD', 'CHF'] as $cv): ?>
                    <option value="<?= $cv ?>"<?= $shop_cur_en === $cv ? ' selected' : '' ?>><?= $cv ?></option>
                <?php endforeach; ?>
            </select>
            <small class="muted">Affichée en $ par défaut (5&nbsp;$). La devise suit la langue du site.</small>
        </div>
    </div>
    <div>
        <label>Clé secrète Stripe (sk_…)</label>
        <input type="password" name="stripe_secret_key" autocomplete="off" placeholder="<?= $has_secret ? '•••••••• (déjà enregistrée — laisser vide pour conserver)' : 'sk_live_… ou sk_test_…' ?>">
        <small class="muted">⚠️ Confidentiel. Laissez vide pour conserver la clé actuelle.</small>
    </div>
    <div>
        <label>Secret du webhook (whsec_…)</label>
        <input type="password" name="stripe_webhook_secret" autocomplete="off" placeholder="<?= $has_whsec ? '•••••••• (déjà enregistré — laisser vide pour conserver)' : 'whsec_…' ?>">
        <small class="muted">Endpoint à créer dans Stripe : <code><?= e(url('stripe-webhook.php')) ?></code> · événement <code>checkout.session.completed</code>.</small>
    </div>

    <hr style="border:0;border-top:1px solid var(--glass-brd);margin:.6rem 0">
    <h2 style="font-size:1.1rem;margin:0">📧 E-mails automatiques — Resend</h2>
    <p class="muted" style="font-size:.85rem;margin:.2rem 0 .4rem">
        Pour l’envoi automatique (livraison des wallpapers, contact, notifications).
        Créez un compte sur <a href="https://resend.com" target="_blank" rel="noopener">resend.com</a>,
        vérifiez votre domaine, générez une clé API, puis collez-la ci-dessous. État :
        <strong><?= $has_resend ? '🟢 Resend connecté' : '⚪ Non connecté (repli sur le mail du serveur)' ?></strong>
    </p>

    <div>
        <label>Clé API Resend (re_…)</label>
        <input type="password" name="resend_api_key" autocomplete="off" placeholder="<?= $has_resend ? '•••••••• (déjà enregistrée — laisser vide pour conserver)' : 're_…' ?>">
        <small class="muted">⚠️ Confidentiel. Laissez vide pour conserver la clé actuelle. Vous pouvez aussi définir la variable d’environnement <code>RESEND_API_KEY</code>.</small>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div>
            <label>Adresse d’expéditeur</label>
            <input type="email" name="mail_from" value="<?= e($mail_from_v) ?>" placeholder="no-reply@vicehubx.fr">
            <small class="muted">Doit appartenir à un domaine <strong>vérifié</strong> dans Resend.</small>
        </div>
        <div>
            <label>Nom d’expéditeur</label>
            <input type="text" name="mail_from_name" value="<?= e($mail_name_v) ?>" placeholder="ViceHub X">
        </div>
    </div>
    <div>
        <label>Adresse de réception des messages de contact</label>
        <input type="email" name="contact_email" value="<?= e($contact_v) ?>" placeholder="contact@vicehubx.fr">
        <small class="muted">Les messages du formulaire de contact y sont envoyés. Vide = adresse d’expéditeur.</small>
    </div>

    <button class="btn btn--primary" type="submit">Enregistrer</button>
</form>

<form method="post" class="form glass" style="max-width:680px;padding:1.2rem 1.6rem;border-radius:18px;margin-top:1.2rem">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="change_password">
    <h2 style="font-size:1.05rem;margin:0 0 .2rem">🔒 Changer mon mot de passe</h2>
    <p class="muted" style="margin:.1rem 0 .6rem;font-size:.85rem">Important pour la mise en ligne : remplacez le mot de passe par défaut.</p>
    <div>
        <label>Mot de passe actuel</label>
        <input type="password" name="current_password" required autocomplete="current-password">
    </div>
    <div>
        <label>Nouveau mot de passe (8 caractères min.)</label>
        <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
    </div>
    <button class="btn btn--primary" type="submit">Mettre à jour le mot de passe</button>
</form>

<form method="post" class="form glass" style="max-width:680px;padding:1.2rem 1.6rem;border-radius:18px;margin-top:1.2rem">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="test_email">
    <h2 style="font-size:1.05rem;margin:0 0 .2rem">✉️ Tester l’envoi</h2>
    <p class="muted" style="font-size:.84rem;margin:.1rem 0 .6rem">Envoie un e-mail de test pour vérifier la configuration.</p>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1;min-width:240px;margin:0">
            <label>Adresse de test</label>
            <input type="email" name="test_to" value="<?= e($admin_user['email'] ?? '') ?>" placeholder="vous@exemple.com">
        </div>
        <button class="btn btn--ghost" type="submit" style="white-space:nowrap">Envoyer un test →</button>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
