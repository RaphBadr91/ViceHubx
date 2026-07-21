<?php
/**
 * ViceHub X — Réseaux sociaux : auto-publication Facebook + Instagram.
 * Connexion des jetons Meta, activation, test, file d'attente et suivi.
 */
$ADMIN_TITLE = 'ViceHub X — Réseaux sociaux';
require __DIR__ . '/../includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/social.php';

$flash = null;
$act = $_POST['action'] ?? '';
if ($act !== '' && !verify_csrf()) { $flash = ['err', 'Jeton CSRF invalide.']; $act = ''; }

// --- Enregistrement des réglages / connexion ---
if ($act === 'save') {
    foreach (['fb_page_id', 'ig_user_id', 'site_public_url'] as $k) {
        if (isset($_POST[$k])) { set_setting($k, trim((string) $_POST[$k])); }
    }
    $tok = trim((string) ($_POST['fb_page_token'] ?? ''));
    if ($tok !== '') { set_setting('fb_page_token', $tok); } // vide = on conserve
    if (isset($_POST['social_daily_max'])) {
        set_setting('social_daily_max', (string) max(1, min(50, (int) $_POST['social_daily_max'])));
    }
    set_setting('social_fb_enabled', !empty($_POST['social_fb_enabled']) ? '1' : '0');
    set_setting('social_ig_enabled', !empty($_POST['social_ig_enabled']) ? '1' : '0');
    set_setting('social_auto', !empty($_POST['social_auto']) ? '1' : '0');
    // (Ré)initialise le repère d'auto-publication : on ne poste que ce qui vient APRÈS.
    if (!empty($_POST['social_auto']) && (int) get_setting('social_since_id', '0') === 0) {
        $max = (int) db()->query('SELECT COALESCE(MAX(id),0) FROM articles')->fetchColumn();
        set_setting('social_since_id', (string) $max);
    }
    $flash = ['ok', '💾 Réglages réseaux sociaux enregistrés.'];
}
// --- Tests ---
if ($act === 'test_fb') { @set_time_limit(0); try { $r = social_test('facebook'); $flash = [$r['ok'] ? 'ok' : 'err', $r['msg']]; } catch (Throwable $e) { $flash = ['err', 'Erreur Facebook : ' . $e->getMessage()]; } }
if ($act === 'test_ig') { @set_time_limit(0); try { $r = social_test('instagram'); $flash = [$r['ok'] ? 'ok' : 'err', $r['msg']]; } catch (Throwable $e) { $flash = ['err', 'Erreur Instagram : ' . $e->getMessage()]; } }
// --- Traiter la file maintenant ---
// ⚠️ Publier plusieurs articles en direct peut dépasser le délai du serveur
// (page blanche/timeout). On renvoie donc la page D'ABORD, puis on publie en
// arrière-plan (comme le heartbeat) — aucun timeout possible.
if ($act === 'run') {
    // Traitement DIRECT (synchrone) avec petit budget : fiable sur LiteSpeed
    // (le mode « arrière-plan » se faisait tuer et laissait le verrou coincé).
    // On débloque le verrou (action manuelle) puis on poste un petit lot ; le
    // reste part via le cron / les clics suivants.
    set_setting('social_lock', '0');
    @set_time_limit(0);
    try {
        $r = social_drain(15);
        $flash = ['ok', "✅ File traitée : {$r['posted']} publication(s) postée(s), {$r['failed']} échec(s). Reclique pour poster le lot suivant (ou laisse le cron faire)."];
    } catch (Throwable $e) {
        $flash = ['err', 'Erreur pendant le traitement : ' . $e->getMessage()];
    }
}
// --- Poster les N derniers articles maintenant (rattrapage) ---
if ($act === 'post_recent') {
    try {
        $n = max(1, min(20, (int) ($_POST['n'] ?? 3)));
        $q = 0;
        // Langue par réseau : FR → Facebook, EN → Instagram.
        if (social_fb_ready()) {
            foreach (db()->query("SELECT id FROM articles WHERE status='published' AND lang='fr' ORDER BY id DESC LIMIT {$n}")->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $q += social_enqueue((int) $id, ['facebook']);
            }
        }
        if (social_ig_ready()) {
            foreach (db()->query("SELECT id FROM articles WHERE status='published' AND lang='en' ORDER BY id DESC LIMIT {$n}")->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $q += social_enqueue((int) $id, ['instagram']);
            }
        }
        $flash = ['ok', "🗂️ {$q} publication(s) ajoutée(s) à la file (FR→Facebook, EN→Instagram). Elles partiront automatiquement (arrière-plan) ou clique « Traiter la file »."];
    } catch (Throwable $e) {
        $flash = ['err', 'Erreur pendant le rattrapage : ' . $e->getMessage()];
    }
}
// --- Réinitialiser la file en erreur (retenter) ---
if ($act === 'retry_errors') {
    db()->exec("UPDATE social_queue SET status='pending', error=NULL WHERE status='error'");
    $flash = ['ok', '🔁 Publications en erreur remises en file.'];
}

$fbEnabled = (int) get_setting('social_fb_enabled', '0') === 1;
$igEnabled = (int) get_setting('social_ig_enabled', '0') === 1;
$auto      = (int) get_setting('social_auto', '0') === 1;
$fbPage    = social_fb_page();
$igUser    = social_ig_user();
$hasToken  = social_fb_token() !== '';
$siteUrl   = (string) (get_setting('site_public_url', '') ?: social_base());
$dailyMax  = max(1, (int) get_setting('social_daily_max', '10'));
$fbToday   = social_posted_today('facebook');
$igToday   = social_posted_today('instagram');
$stats     = social_stats();
$recent    = social_recent(12);

$tickKey = (string) get_setting('ai_tick_key', '');
if ($tickKey === '') { $tickKey = bin2hex(random_bytes(16)); set_setting('ai_tick_key', $tickKey); }
$cronUrl = rtrim(social_base(), '/') . '/social-tick.php?key=' . $tickKey;
?>
<div class="admin-bar"><h1>📣 Réseaux sociaux</h1></div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>" style="margin:1rem 0"><?= e($flash[1]) ?></div><?php endif; ?>

<p class="muted" style="max-width:820px">Publie <strong>automatiquement</strong> chaque nouvel article sur ta <strong>Page Facebook</strong> et ton <strong>compte Instagram</strong>, avec une légende courte + accrocheuse + hashtags générée par l'IA. Reste inactif tant que les jetons ne sont pas renseignés.</p>
<div class="glass" style="padding:.8rem 1rem;border-radius:12px;margin:.6rem 0;border:1px solid rgba(43,214,255,.25);font-size:.9rem">
    🌍 <strong>Langue par réseau :</strong> 🇫🇷 <strong>Facebook = articles FR</strong> (légende française) · 🇬🇧 <strong>Instagram = traductions EN</strong> (légende anglaise).
    <span class="muted">Pour alimenter Instagram, garde <strong>« Traduire automatiquement »</strong> activé (Admin → Articles IA) : chaque nouvel article FR reçoit sa version EN qui part sur Instagram.</span>
</div>

<!-- État -->
<div class="glass" style="padding:1rem 1.2rem;border-radius:14px;margin:1rem 0;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
    <span><?= social_fb_ready() ? '🟢' : '🔴' ?> <strong>Facebook</strong> <?= social_fb_ready() ? 'prêt' : 'non connecté' ?></span>
    <span><?= social_ig_ready() ? '🟢' : '🔴' ?> <strong>Instagram</strong> <?= social_ig_ready() ? 'prêt' : 'non connecté' ?></span>
    <span class="muted">Auto-publication : <strong style="color:<?= $auto ? '#39ffaa' : '#ff9d5d' ?>"><?= $auto ? 'ACTIVE' : 'en pause' ?></strong></span>
    <span class="muted">File : <strong><?= (int) $stats['pending'] ?></strong> en attente · <strong><?= (int) $stats['posted'] ?></strong> postés · <strong style="color:#ff5d5d"><?= (int) $stats['error'] ?></strong> erreurs</span>
</div>

<!-- Connexion -->
<div class="glass" style="padding:1.4rem;border-radius:16px;margin:1rem 0">
    <h2 style="margin-top:0">🔑 Connexion Meta (Facebook + Instagram)</h2>
    <form method="post" style="display:grid;gap:1rem;max-width:760px">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <label>URL publique du site
            <input type="text" name="site_public_url" value="<?= e($siteUrl) ?>" placeholder="https://vicehubx.com" style="display:block;width:100%;margin-top:.3rem">
            <small class="muted">Sert à construire les liens et images absolus des posts (doit être en https).</small>
        </label>
        <label>ID de la Page Facebook
            <input type="text" name="fb_page_id" value="<?= e($fbPage) ?>" placeholder="ex. 1234567890" style="display:block;width:100%;margin-top:.3rem">
        </label>
        <label>Jeton d'accès de Page (Page Access Token, longue durée)
            <input type="text" name="fb_page_token" value="" placeholder="<?= $hasToken ? '•••••• (déjà enregistré — laisse vide pour conserver)' : 'EAAB...' ?>" style="display:block;width:100%;margin-top:.3rem">
            <small class="muted">Le même jeton sert pour Facebook ET Instagram.</small>
        </label>
        <label>ID du compte Instagram Business
            <input type="text" name="ig_user_id" value="<?= e($igUser) ?>" placeholder="ex. 17841400000000000" style="display:block;width:100%;margin-top:.3rem">
            <small class="muted">Le compte Instagram doit être <strong>Business/Créateur</strong> et relié à la Page Facebook ci-dessus.</small>
        </label>
        <label style="max-width:340px">Plafond de posts par jour <strong>et par réseau</strong> (anti-spam)
            <input type="number" name="social_daily_max" value="<?= $dailyMax ?>" min="1" max="50" style="display:block;width:120px;margin-top:.3rem">
            <small class="muted">🛡️ Protège tes comptes d'un bannissement. 8-12/jour est un rythme sûr et naturel. Aujourd'hui : <strong><?= $fbToday ?></strong> FB · <strong><?= $igToday ?></strong> IG.</small>
        </label>
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
            <label style="display:flex;gap:.5rem;align-items:center;cursor:pointer"><input type="checkbox" name="social_fb_enabled" value="1" <?= $fbEnabled ? 'checked' : '' ?>> Activer Facebook</label>
            <label style="display:flex;gap:.5rem;align-items:center;cursor:pointer"><input type="checkbox" name="social_ig_enabled" value="1" <?= $igEnabled ? 'checked' : '' ?>> Activer Instagram</label>
            <label style="display:flex;gap:.5rem;align-items:center;cursor:pointer"><input type="checkbox" name="social_auto" value="1" <?= $auto ? 'checked' : '' ?>> <strong>Auto-publier chaque nouvel article</strong></label>
        </div>
        <div><button class="btn btn--primary" type="submit">💾 Enregistrer</button></div>
    </form>

    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--glass-brd)">
        <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="test_fb"><button class="btn btn--ghost" <?= social_fb_ready() ? '' : 'disabled' ?>>📘 Tester Facebook</button></form>
        <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="test_ig"><button class="btn btn--ghost" <?= social_ig_ready() ? '' : 'disabled' ?>>📸 Tester Instagram</button></form>
        <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="run"><button class="btn btn--ghost" <?= social_any_ready() ? '' : 'disabled' ?>>▶️ Traiter la file</button></form>
        <form method="post" style="margin:0;display:flex;gap:.4rem;align-items:center"><?= csrf_field() ?><input type="hidden" name="action" value="post_recent">
            <input type="number" name="n" value="3" min="1" max="20" style="width:70px;padding:.4rem;border-radius:8px;background:rgba(255,255,255,.05);color:#fff;border:1px solid var(--glass-brd)">
            <button class="btn btn--ghost" <?= social_any_ready() ? '' : 'disabled' ?>>🗂️ Poster les N derniers</button>
        </form>
        <?php if ($stats['error'] > 0): ?><form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="retry_errors"><button class="btn btn--ghost">🔁 Retenter les erreurs</button></form><?php endif; ?>
    </div>
</div>

<!-- Cron -->
<div class="glass" style="padding:1.2rem 1.4rem;border-radius:16px;margin:1rem 0">
    <h2 style="margin-top:0;font-size:1.1rem">🔁 Publication 24h/24</h2>
    <p class="muted" style="font-size:.88rem;margin:.2rem 0 .4rem">Branche ce lien sur un cron (toutes les 15-30 min) pour poster automatiquement — <a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a> ou cPanel → Cron Jobs. <em>(Le cron des articles poste déjà les nouveaux : ce lien est un renfort.)</em></p>
    <input type="text" readonly value="<?= e($cronUrl) ?>" onclick="this.select()" style="width:100%;font-size:.8rem;font-family:monospace;padding:.5rem;border-radius:8px;background:rgba(255,255,255,.05);color:#cfc9dd;border:1px solid var(--glass-brd)">
    <button class="btn btn--ghost" type="button" data-copy="<?= e($cronUrl) ?>" style="margin-top:.6rem">📋 Copier le lien cron</button>
</div>

<!-- Guide -->
<details class="glass" style="padding:1rem 1.4rem;border-radius:16px;margin:1rem 0">
    <summary style="cursor:pointer;font-weight:700">📖 Comment obtenir l'ID de Page, le jeton et l'ID Instagram ? (guide)</summary>
    <ol class="muted" style="font-size:.9rem;line-height:1.7;margin:.8rem 0 0;padding-left:1.2rem">
        <li>Crée une <strong>Page Facebook</strong> + un compte <strong>Instagram Business</strong>, et relie l'Instagram à la Page (Paramètres Page → Comptes liés).</li>
        <li>Va sur <a href="https://developers.facebook.com" target="_blank" rel="noopener">developers.facebook.com</a> → crée une <strong>App</strong> (type « Business »).</li>
        <li>Ajoute les produits <strong>Facebook Login</strong> + permissions <code>pages_manage_posts</code>, <code>pages_read_engagement</code>, <code>instagram_basic</code>, <code>instagram_content_publish</code>.</li>
        <li>Dans le <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener">Graph API Explorer</a> : récupère un <strong>Page Access Token</strong>, puis convertis-le en <strong>longue durée</strong> (60 jours).</li>
        <li><strong>ID de Page</strong> : Graph Explorer → <code>GET /me/accounts</code>. <strong>ID Instagram Business</strong> : <code>GET /{page-id}?fields=instagram_business_account</code>.</li>
        <li>Colle le tout ci-dessus, coche « Activer » + « Auto-publier », enregistre, puis clique <strong>Tester</strong>.</li>
    </ol>
    <p class="muted" style="font-size:.82rem;margin-top:.6rem">💡 Envoie-moi les identifiants quand tu les as et on connecte/teste ensemble, comme pour Higgsfield.</p>
</details>

<!-- File récente -->
<div class="glass" style="padding:1rem 1.2rem;border-radius:16px;margin:1rem 0;overflow-x:auto">
    <h2 style="margin-top:0;font-size:1.1rem">🗂️ Dernières publications</h2>
    <?php if (!$recent): ?>
        <p class="muted">Aucune publication en file pour le moment.</p>
    <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Article</th><th>Réseau</th><th>Statut</th><th>Détail</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): $st = (string) $r['status'];
                // Lien de vérification vers le post réel (permet de confirmer qu'il existe).
                $pid  = (string) ($r['post_id'] ?? '');
                $link = '';
                if ($st === 'posted' && $pid !== '') {
                    if (preg_match('#^https?://#i', $pid)) { $link = $pid; }
                    elseif ($r['platform'] === 'facebook') { $link = 'https://www.facebook.com/' . rawurlencode($pid); }
                }
            ?>
                <tr>
                    <td><?= e(mb_substr((string) ($r['title'] ?? '—'), 0, 60)) ?></td>
                    <td><?= $r['platform'] === 'instagram' ? '📸 Instagram' : '📘 Facebook' ?></td>
                    <td><?= $st === 'posted' ? '🟢 Posté' : ($st === 'error' ? '🔴 Erreur' : '🟠 En attente') ?></td>
                    <td class="muted" style="font-size:.82rem;max-width:360px;word-break:break-word">
                        <?php if ($st === 'error'): ?>
                            <?= e((string) $r['error']) ?>
                        <?php else: ?>
                            <?= $r['posted_at'] ? 'le ' . date('d/m H:i', strtotime((string) $r['posted_at'])) : '' ?>
                            <?php if ($link !== ''): ?> · <a href="<?= e($link) ?>" target="_blank" rel="noopener" style="color:#2bd6ff">Voir ↗</a><?php elseif ($pid !== ''): ?> · <span class="muted">id <?= e(mb_substr($pid, 0, 18)) ?></span><?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script>document.querySelectorAll('[data-copy]').forEach(function(b){b.addEventListener('click',function(){navigator.clipboard&&navigator.clipboard.writeText(b.getAttribute('data-copy'));var o=b.textContent;b.textContent='✅ Copié';setTimeout(function(){b.textContent=o;},1500);});});</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
