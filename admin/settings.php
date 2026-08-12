<?php
$ADMIN_TITLE = 'ViceHub X — Réglages';
require __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/printful.php';

$flash = null;
$pf_products = null; // liste des produits Printful (bouton « Lister mes produits »)
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
    } elseif (($_POST['action'] ?? '') === 'test_printful') {
        // --- Test de connexion Printful ---
        $r = printful_test();
        $flash = ($r['ok'] ?? false)
            ? ['ok', '✅ Printful connecté' . (!empty($r['name']) ? ' — boutique « ' . $r['name'] . ' »' : '') . '.']
            : ['err', 'Échec Printful : ' . ($r['error'] ?? 'clé invalide.')];
    } elseif (($_POST['action'] ?? '') === 'list_printful') {
        // --- Liste des produits Printful + leurs sync_variant_id ---
        try {
            $pf_products = printful_sync_products();
            if (!$pf_products) {
                $flash = ['ok', 'Aucun produit trouvé dans votre boutique Printful. Créez d’abord vos produits sur printful.com.'];
            }
        } catch (Throwable $ex) {
            $flash = ['err', 'Impossible de lister les produits Printful : ' . $ex->getMessage()];
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
        // --- Impression à la demande (Printful) ---
        $pfk = trim((string) ($_POST['printful_api_key'] ?? ''));
        if ($pfk !== '') { set_setting('printful_api_key', $pfk); }
        set_setting('printful_store_id', trim((string) ($_POST['printful_store_id'] ?? '')));
        set_setting('printful_enabled', isset($_POST['printful_enabled']) ? '1' : '0');
        set_setting('printful_auto_confirm', isset($_POST['printful_auto_confirm']) ? '1' : '0');
        $rd = trim((string) ($_POST['release_date'] ?? ''));
        if ($rd !== '') {
            // datetime-local -> ISO
            set_setting('release_date', str_replace(' ', 'T', $rd) . ':00');
        }
        // --- Image de la page événement GTA 6 (upload prioritaire, sinon URL) ---
        $evUp = handle_image_upload('event_image_file');
        if ($evUp) {
            set_setting('event_image', $evUp);
        } else {
            set_setting('event_image', trim((string) ($_POST['event_image'] ?? '')));
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
        $bv = trim((string) ($_POST['bing_site_verification'] ?? ''));
        if (preg_match('/content=["\']([^"\']+)["\']/i', $bv, $mb)) { $bv = $mb[1]; }
        set_setting('bing_site_verification', $bv);
        set_setting('analytics_id', trim((string) ($_POST['analytics_id'] ?? '')));
        set_setting('amazon_tag', trim((string) ($_POST['amazon_tag'] ?? '')));
        // --- Profils sociaux publics (sameAs = entité claire pour l'IA Google) ---
        foreach (['instagram', 'tiktok', 'youtube', 'x', 'facebook', 'discord'] as $__sp) {
            set_setting('profile_' . $__sp, trim((string) ($_POST['profile_' . $__sp] ?? '')));
        }
        // --- Forum : vie automatique (rythme des interactions) ---
        $gmin = max(1, min(48, (int) ($_POST['forum_gap_min_h'] ?? 2)));
        $gmax = max($gmin, min(72, (int) ($_POST['forum_gap_max_h'] ?? 12)));
        set_setting('forum_gap_min_h', (string) $gmin);
        set_setting('forum_gap_max_h', (string) $gmax);
        set_setting('forum_new_chance', (string) max(0, min(100, (int) ($_POST['forum_new_chance'] ?? 6))));
        $flash = ['ok', 'Réglages enregistrés.'];
    }
}

$mail_from_v = (string) get_setting('mail_from', '');
$mail_name_v = (string) get_setting('mail_from_name', '');
$contact_v   = (string) get_setting('contact_email', '');
$has_resend  = resend_enabled();

$gverify = (string) get_setting('google_site_verification', '');
$bverify = (string) get_setting('bing_site_verification', '');
$ga_id   = (string) get_setting('analytics_id', '');
$amztag  = (string) get_setting('amazon_tag', '');
$fgmin   = (int) get_setting('forum_gap_min_h', '2');
$fgmax   = (int) get_setting('forum_gap_max_h', '12');
$fnew    = (int) get_setting('forum_new_chance', '6');
$fkey    = (string) get_setting('forum_tick_key', '');
if ($fkey === '') { $fkey = bin2hex(random_bytes(16)); set_setting('forum_tick_key', $fkey); }
$fcron   = rtrim(site_base_url(), '/') . '/forum-tick.php?key=' . $fkey;
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
$pf         = printful_status();
$has_pfkey  = $pf['key'];
$pf_store   = (string) get_setting('printful_store_id', '');
$release = (string) release_date();
// ISO -> valeur datetime-local (YYYY-MM-DDTHH:MM)
$release_input = substr(str_replace(' ', 'T', $release), 0, 16);
?>
<div class="admin-bar">
    <h1>Réglages</h1>
    <a class="btn btn--ghost" href="<?= e(url('index.php')) ?>" target="_blank">Voir le site ↗</a>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form glass" style="max-width:680px;padding:1.6rem;border-radius:18px">
    <?= csrf_field() ?>

    <div>
        <label>Date de sortie (compte à rebours)</label>
        <input type="datetime-local" name="release_date" value="<?= e($release_input) ?>">
        <small class="muted">Par défaut : 19 novembre 2026.</small>
    </div>

    <hr style="border:none;border-top:1px solid var(--glass-brd);margin:1.4rem 0 .4rem">
    <h2 style="font-size:1.1rem;margin:0 0 .2rem">🎬 Page événement GTA 6 <span class="muted" style="font-size:.85rem">(/gta6-extended-look)</span></h2>
    <div>
        <label>Image de l'événement — URL à coller</label>
        <input type="text" name="event_image" value="<?= e((string) get_setting('event_image', '')) ?>" placeholder="https://… (image du gameplay Netflix) ou /public/assets/img/scenes/city.png">
        <small class="muted">Clic droit sur une image → « Copier l'adresse de l'image » → colle ici. Affichée en haut de la page événement + en aperçu sur les réseaux. Vide = image par défaut. <strong>Utilise une image officielle/libre et cite la source.</strong></small>
    </div>
    <div>
        <label>… ou uploader une image (JPG / PNG / WebP, max 3 Mo)</label>
        <input type="file" name="event_image_file" accept="image/jpeg,image/png,image/webp">
        <small class="muted">Si tu uploades, elle remplace l'URL ci-dessus.</small>
    </div>

    <hr style="border:none;border-top:1px solid var(--glass-brd);margin:1.4rem 0 .4rem">
    <h2 style="font-size:1.1rem;margin:0 0 .2rem">🔎 Google — Référencement (SEO)</h2>
    <div>
        <label>Google Search Console — code de vérification</label>
        <input type="text" name="google_site_verification" value="<?= e($gverify) ?>" placeholder="collez le code (ou la balise meta entière)">
        <small class="muted">Dans <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Search Console</a> → « Balise HTML » : copiez le <code>content="…"</code> ici (la balise complète marche aussi). Active la vérification du site.</small>
    </div>

    <div>
        <label>Bing Webmaster — code de vérification <span class="muted">(optionnel)</span></label>
        <input type="text" name="bing_site_verification" value="<?= e($bverify) ?>" placeholder="collez le code (ou la balise meta entière)">
        <small class="muted">Dans <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a> → « Balise meta » : copiez le <code>content="…"</code>. Bing alimente aussi ChatGPT Search, DuckDuckGo et Ecosia.</small>
    </div>

    <div>
        <label>Google Analytics 4 — ID de mesure</label>
        <input type="text" name="analytics_id" value="<?= e($ga_id) ?>" placeholder="G-XXXXXXXXXX">
        <small class="muted">Statistiques de visites. Ne se charge <strong>qu'après acceptation des cookies</strong> (conforme RGPD).</small>
    </div>

    <hr style="border:none;border-top:1px solid var(--glass-brd);margin:1.4rem 0 .4rem">
    <h2 style="font-size:1.1rem;margin:0 0 .2rem">🛒 Amazon Partenaires (affiliation)</h2>
    <div>
        <label>Tag d'affiliation Amazon</label>
        <input type="text" name="amazon_tag" value="<?= e($amztag) ?>" placeholder="ex. vicehubx-21">
        <small class="muted">Ton identifiant <a href="https://partenaires.amazon.fr" target="_blank" rel="noopener">Amazon Partenaires</a>. Il est <strong>ajouté automatiquement à tous tes liens Amazon</strong> (boutique + articles) → chaque vente te rapporte une commission. Laisse vide si pas encore inscrit.</small>
    </div>

    <hr style="border:none;border-top:1px solid var(--glass-brd);margin:1.4rem 0 .4rem">
    <h2 style="font-size:1.1rem;margin:0 0 .2rem">🔗 Réseaux sociaux (profils publics)</h2>
    <p class="muted" style="font-size:.85rem;margin:0 0 .6rem">
        <strong>Très important pour être reconnu par Google (et cité dans l'Aperçu IA).</strong>
        Colle l'adresse <strong>complète</strong> de tes profils officiels. Ils alimentent le
        <code>sameAs</code> de ton entité (Google comprend alors que « ViceHub X » = ce site + ces comptes,
        et te distingue des sites au nom proche) et remplacent les icônes du pied de page.
    </p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div><label>📸 Instagram</label><input type="url" name="profile_instagram" value="<?= e((string) get_setting('profile_instagram', '')) ?>" placeholder="https://www.instagram.com/vicehubx"></div>
        <div><label>♪ TikTok</label><input type="url" name="profile_tiktok" value="<?= e((string) get_setting('profile_tiktok', '')) ?>" placeholder="https://www.tiktok.com/@vicehubx"></div>
        <div><label>▶ YouTube</label><input type="url" name="profile_youtube" value="<?= e((string) get_setting('profile_youtube', '')) ?>" placeholder="https://www.youtube.com/@vicehubx"></div>
        <div><label>𝕏 X / Twitter</label><input type="url" name="profile_x" value="<?= e((string) get_setting('profile_x', '')) ?>" placeholder="https://x.com/vicehubx"></div>
        <div><label>f Facebook</label><input type="url" name="profile_facebook" value="<?= e((string) get_setting('profile_facebook', '')) ?>" placeholder="https://www.facebook.com/vicehubx"></div>
        <div><label>💬 Discord</label><input type="url" name="profile_discord" value="<?= e((string) get_setting('profile_discord', '')) ?>" placeholder="https://discord.gg/…"></div>
    </div>

    <hr style="border:none;border-top:1px solid var(--glass-brd);margin:1.4rem 0 .4rem">
    <h2 style="font-size:1.1rem;margin:0 0 .2rem">💬 Forum — vie automatique (100 membres)</h2>
    <p class="muted" style="font-size:.85rem;margin:0 0 .6rem">Les membres IA se répondent entre eux à un rythme humain (une interaction toutes les X à Y heures, aléatoire, jamais en rafale). Branche le lien ci-dessous sur un cron (toutes les 20-30 min).</p>
    <div style="display:flex;gap:1rem;flex-wrap:wrap">
        <div style="flex:1;min-width:130px"><label>Intervalle min (h)</label><input type="number" name="forum_gap_min_h" value="<?= (int) $fgmin ?>" min="1" max="48"></div>
        <div style="flex:1;min-width:130px"><label>Intervalle max (h)</label><input type="number" name="forum_gap_max_h" value="<?= (int) $fgmax ?>" min="1" max="72"></div>
        <div style="flex:1;min-width:130px"><label>Nouveau sujet (%)</label><input type="number" name="forum_new_chance" value="<?= (int) $fnew ?>" min="0" max="100"></div>
    </div>
    <div>
        <label>Lien cron du forum (à brancher toutes les 20-30 min)</label>
        <input type="text" readonly value="<?= e($fcron) ?>" onclick="this.select()" style="font-family:monospace;font-size:.8rem">
        <small class="muted">Cron cPanel : <code>curl -s "<?= e($fcron) ?>" &gt;/dev/null 2>&1</code>. Le verrou interne décide quand poster (respecte l'intervalle). Défaut : 1 interaction toutes les 2 à 12 h.</small>
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
    <h2 style="font-size:1.1rem;margin:0">🖨️ Impression à la demande — Printful</h2>
    <p class="muted" style="font-size:.85rem;margin:.2rem 0 .4rem">
        Vendez votre <strong>propre merch</strong> (t-shirts, mugs, posters, casquettes…) sans stock :
        Printful imprime + expédie automatiquement à chaque commande payée. Créez un compte sur
        <a href="https://www.printful.com" target="_blank" rel="noopener">printful.com</a>,
        créez vos produits (avec vos visuels), générez une clé API, collez-la ci-dessous, puis
        cochez « Traitement automatique ». État :
        <strong><?php
            if (!$has_pfkey) {
                echo '⚪ Dormant (clé non renseignée)';
            } elseif (!$pf['enabled']) {
                echo '🟡 Clé OK — traitement désactivé';
            } else {
                echo $pf['auto_confirm'] ? '🟢 Actif — confirmation auto (impression immédiate)' : '🟢 Actif — commandes en brouillon';
            }
        ?></strong>
    </p>
    <div>
        <label>Clé API Printful</label>
        <input type="password" name="printful_api_key" autocomplete="off" placeholder="<?= $has_pfkey ? '•••••••• (déjà enregistrée — laisser vide pour conserver)' : 'collez votre jeton API Printful' ?>">
        <small class="muted">⚠️ Confidentiel. Dans Printful : <em>Settings → API → Add API key</em>. Laissez vide pour conserver la clé actuelle.</small>
    </div>
    <div>
        <label>ID de boutique Printful <span class="muted">(optionnel)</span></label>
        <input type="text" name="printful_store_id" value="<?= e($pf_store) ?>" placeholder="ex. 12345678 (si votre jeton gère plusieurs boutiques)">
        <small class="muted">Nécessaire uniquement si votre jeton a accès à plusieurs boutiques. Sinon laissez vide.</small>
    </div>
    <label style="display:flex;gap:.5rem;align-items:flex-start;margin:.2rem 0">
        <input type="checkbox" name="printful_enabled" value="1"<?= $pf['enabled'] ? ' checked' : '' ?> style="margin-top:.25rem">
        <span>Traitement automatique <strong>activé</strong><br><small class="muted">Décoché = tout reste dormant (aucune commande envoyée à Printful), même avec une clé.</small></span>
    </label>
    <label style="display:flex;gap:.5rem;align-items:flex-start;margin:.2rem 0">
        <input type="checkbox" name="printful_auto_confirm" value="1"<?= $pf['auto_confirm'] ? ' checked' : '' ?> style="margin-top:.25rem">
        <span>Confirmer automatiquement (impression immédiate)<br><small class="muted">Décoché (<strong>recommandé au début</strong>) = les commandes arrivent en <em>brouillon</em> dans Printful ; vous vérifiez puis confirmez à la main. Coché = Printful imprime + débite tout de suite, sans intervention.</small></span>
    </label>

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

<div class="glass" style="max-width:680px;padding:1.2rem 1.6rem;border-radius:18px;margin-top:1.2rem">
    <h2 style="font-size:1.05rem;margin:0 0 .2rem">🖨️ Printful — outils</h2>
    <p class="muted" style="font-size:.84rem;margin:.1rem 0 .8rem">
        <?= $has_pfkey ? 'Vérifiez la connexion, puis listez vos produits pour récupérer leurs <strong>ID de variante</strong> à coller dans chaque fiche produit.' : '<strong>Renseignez d’abord la clé API ci-dessus et enregistrez</strong>, puis revenez ici.' ?>
    </p>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap">
        <form method="post" style="margin:0">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test_printful">
            <button class="btn btn--ghost" type="submit"<?= $has_pfkey ? '' : ' disabled' ?>>Tester la connexion →</button>
        </form>
        <form method="post" style="margin:0">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="list_printful">
            <button class="btn btn--primary" type="submit"<?= $has_pfkey ? '' : ' disabled' ?>>Lister mes produits Printful →</button>
        </form>
    </div>

    <?php if ($pf_products): ?>
        <div style="margin-top:1rem;overflow-x:auto">
            <p class="muted" style="font-size:.84rem;margin:0 0 .5rem">Copiez l’<strong>ID de variante</strong> et collez-le dans le champ « Variante Printful » de la fiche produit correspondante (Admin → Boutique).</p>
            <table style="width:100%;border-collapse:collapse;font-size:.86rem">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid var(--glass-brd)">
                        <th style="padding:.4rem .5rem">Produit</th>
                        <th style="padding:.4rem .5rem">Variante</th>
                        <th style="padding:.4rem .5rem">Prix</th>
                        <th style="padding:.4rem .5rem">ID de variante (à copier)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pf_products as $prod): ?>
                    <?php if (empty($prod['variants'])): ?>
                        <tr style="border-bottom:1px solid var(--glass-brd)">
                            <td style="padding:.4rem .5rem"><?= e($prod['name']) ?></td>
                            <td style="padding:.4rem .5rem" colspan="3"><span class="muted">— aucune variante synchronisée —</span></td>
                        </tr>
                    <?php else: foreach ($prod['variants'] as $i => $v): ?>
                        <tr style="border-bottom:1px solid var(--glass-brd)">
                            <td style="padding:.4rem .5rem"><?= $i === 0 ? e($prod['name']) : '' ?></td>
                            <td style="padding:.4rem .5rem"><?= e($v['name']) ?></td>
                            <td style="padding:.4rem .5rem"><?= e($v['retail_price'] !== '' ? $v['retail_price'] . ' ' . $v['currency'] : '—') ?></td>
                            <td style="padding:.4rem .5rem"><input type="text" readonly value="<?= e($v['sync_variant_id']) ?>" onclick="this.select()" style="font-family:monospace;width:100%;min-width:130px"></td>
                        </tr>
                    <?php endforeach; endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
