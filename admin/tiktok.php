<?php
/**
 * ViceHub X — TikTok Studio : génération + auto-publication de vidéos courtes.
 * Connexion OAuth du compte TikTok, mode brouillon/public, file de vidéos,
 * test, traitement de la file et suivi. Publications en ANGLAIS.
 */
$ADMIN_TITLE = 'ViceHub X — TikTok';
require __DIR__ . '/../includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/ai.php';      // légendes IA
require_once dirname(__DIR__) . '/includes/social.php';  // social_base()
require_once dirname(__DIR__) . '/includes/tiktok.php';

// Flash éventuel renvoyé par le callback OAuth.
$flash = null;
if (!empty($_SESSION['tiktok_flash'])) { $flash = $_SESSION['tiktok_flash']; unset($_SESSION['tiktok_flash']); }

// --- Démarrage OAuth (lien GET) : pas de jeton CSRF requis — c'est le paramètre
//     `state` (vérifié au retour) qui protège le flux. Robuste même si la page a
//     été servie depuis un cache. ---
if (isset($_GET['connect'])) {
    if (tiktok_client_key() === '' || tiktok_client_secret() === '') {
        $flash = ['err', 'Renseigne d\'abord le Client key + Client secret, puis enregistre.'];
    } else {
        $state = bin2hex(random_bytes(16));
        $_SESSION['tiktok_oauth_state'] = $state;
        redirect(tiktok_auth_url($state));
    }
}

$act = $_POST['action'] ?? '';
if ($act !== '' && !verify_csrf()) { $flash = ['err', 'Jeton CSRF invalide.']; $act = ''; }

// --- Enregistrement des réglages ---
if ($act === 'save') {
    if (isset($_POST['site_public_url'])) { set_setting('site_public_url', trim((string) $_POST['site_public_url'])); }
    if (isset($_POST['tiktok_client_key'])) { set_setting('tiktok_client_key', trim((string) $_POST['tiktok_client_key'])); }
    $sec = trim((string) ($_POST['tiktok_client_secret'] ?? ''));
    if ($sec !== '') { set_setting('tiktok_client_secret', $sec); } // vide = on conserve
    set_setting('tiktok_mode', ($_POST['tiktok_mode'] ?? 'draft') === 'public' ? 'public' : 'draft');
    if (isset($_POST['tiktok_daily_max'])) {
        set_setting('tiktok_daily_max', (string) max(1, min(20, (int) $_POST['tiktok_daily_max'])));
    }
    set_setting('tiktok_enabled', !empty($_POST['tiktok_enabled']) ? '1' : '0');
    set_setting('tiktok_public_ok', !empty($_POST['tiktok_public_ok']) ? '1' : '0');
    set_setting('tiktok_also_ig', !empty($_POST['tiktok_also_ig']) ? '1' : '0');
    $flash = ['ok', '💾 Réglages TikTok enregistrés.'];
}
// --- Déconnexion ---
if ($act === 'disconnect') { tiktok_disconnect(); $flash = ['ok', '🔌 Compte TikTok déconnecté (clé/secret conservés).']; }
// --- Ajout d'une vidéo à la file ---
if ($act === 'add_video') {
    $url   = trim((string) ($_POST['source_url'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));
    $id = tiktok_enqueue($url, $title);
    $flash = $id > 0
        ? ['ok', '🎬 Vidéo ajoutée à la file (#' . $id . ').']
        : ['err', 'URL invalide ou déjà en file (elle doit commencer par https://).'];
}
// --- Test : poste la première vidéo en attente ---
if ($act === 'test') { $r = tiktok_test(); $flash = [$r['ok'] ? 'ok' : 'err', $r['msg']]; }
// --- Test : poste un Reel Instagram avec la dernière vidéo ---
if ($act === 'test_ig_reel') { @set_time_limit(0); try { $r = tiktok_test_ig(); $flash = [$r['ok'] ? 'ok' : 'err', $r['msg']]; } catch (Throwable $e) { $flash = ['err', 'Erreur Reel IG : ' . $e->getMessage()]; } }
// --- Traiter la file maintenant (en arrière-plan pour éviter tout timeout) ---
if ($act === 'run') {
    set_setting('tiktok_lock', '0'); // débloque un verrou resté coincé (action manuelle)
    if (function_exists('litespeed_finish_request') || function_exists('fastcgi_finish_request')) {
        register_shutdown_function(static function () {
            // Envoie D'ABORD la page au navigateur (vide le tampon), SINON page blanche.
            while (ob_get_level() > 0) { @ob_end_flush(); }
            @flush();
            if (function_exists('litespeed_finish_request')) { @litespeed_finish_request(); }
            elseif (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
            @set_time_limit(0); @ignore_user_abort(true);
            try { tiktok_drain(120); } catch (Throwable $e) { /* silencieux */ }
        });
        $flash = ['ok', '✅ Traitement lancé en arrière-plan — rafraîchis dans ~30 s pour voir le résultat.'];
    } else {
        @set_time_limit(0);
        try { $r = tiktok_drain(25); $flash = ['ok', "✅ File traitée : {$r['posted']} postée(s), {$r['failed']} échec(s)."]; }
        catch (Throwable $e) { $flash = ['err', 'Erreur pendant le traitement : ' . $e->getMessage()]; }
    }
}
// --- Retenter les erreurs ---
if ($act === 'retry_errors') {
    try { db()->exec("UPDATE tiktok_queue SET status='pending', error=NULL WHERE status='error'"); } catch (Throwable $e) {}
    $flash = ['ok', '🔁 Vidéos en erreur remises en file.'];
}
// --- Supprimer une entrée ---
if ($act === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) { try { db()->prepare('DELETE FROM tiktok_queue WHERE id=?')->execute([$id]); } catch (Throwable $e) {} }
    $flash = ['ok', '🗑️ Entrée supprimée.'];
}

$hasKey    = tiktok_client_key() !== '';
$hasSecret = tiktok_client_secret() !== '';
$connected = tiktok_connected();
$enabled   = (int) get_setting('tiktok_enabled', '0') === 1;
$mode      = tiktok_mode();
$openId    = (string) get_setting('tiktok_open_id', '');
$scope     = (string) get_setting('tiktok_scope', '');
$siteUrl   = (string) (get_setting('site_public_url', '') ?: tiktok_base());
$dailyMax  = max(1, (int) get_setting('tiktok_daily_max', '3'));
$today     = tiktok_posted_today();
$stats     = tiktok_stats();
$recent    = tiktok_recent(15);
$redirect  = tiktok_redirect_uri();

$tickKey = (string) get_setting('ai_tick_key', '');
if ($tickKey === '') { $tickKey = bin2hex(random_bytes(16)); set_setting('ai_tick_key', $tickKey); }
$cronUrl = rtrim(tiktok_base(), '/') . '/tiktok-tick.php?key=' . $tickKey;
?>
<div class="admin-bar"><h1>🎵 TikTok Studio</h1></div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>" style="margin:1rem 0"><?= e($flash[1]) ?></div><?php endif; ?>

<p class="muted" style="max-width:820px">Publie des <strong>vidéos courtes (15-25 s)</strong> sur TikTok en <strong>anglais</strong> (audience maximale). Les vidéos sont générées via Higgsfield, mises en file, puis envoyées <strong>automatiquement</strong>. Reste inactif tant que le compte n'est pas connecté.</p>

<!-- État -->
<div class="glass" style="padding:1rem 1.2rem;border-radius:14px;margin:1rem 0;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
    <span><?= $connected ? '🟢' : '🔴' ?> <strong>Compte TikTok</strong> <?= $connected ? 'connecté' : 'non connecté' ?><?= $connected && $openId ? ' <span class="muted">(open_id ' . e(mb_substr($openId, 0, 8)) . '…)</span>' : '' ?></span>
    <span class="muted">Mode : <strong style="color:<?= $mode === 'public' ? '#39ffaa' : '#ffd166' ?>"><?= $mode === 'public' ? 'PUBLIC (auto)' : 'BROUILLON (boîte de réception)' ?></strong></span>
    <span class="muted">Auto : <strong style="color:<?= $enabled ? '#39ffaa' : '#ff9d5d' ?>"><?= $enabled ? 'ACTIVE' : 'en pause' ?></strong></span>
    <span class="muted">File : <strong><?= (int) $stats['pending'] ?></strong> en attente · <strong><?= (int) $stats['posted'] ?></strong> postées · <strong style="color:#ff5d5d"><?= (int) $stats['error'] ?></strong> erreurs · <strong><?= $today ?>/<?= $dailyMax ?></strong> aujourd'hui</span>
</div>

<!-- Connexion -->
<div class="glass" style="padding:1.4rem;border-radius:16px;margin:1rem 0">
    <h2 style="margin-top:0">🔑 Connexion TikTok (Content Posting API)</h2>
    <form method="post" style="display:grid;gap:1rem;max-width:760px">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <label>URL publique du site
            <input type="text" name="site_public_url" value="<?= e($siteUrl) ?>" placeholder="https://vicehubx.com" style="display:block;width:100%;margin-top:.3rem">
            <small class="muted">Domaine <strong>vérifié</strong> chez TikTok — sert à héberger la vidéo pour la publication (PULL_FROM_URL).</small>
        </label>
        <label>Client key
            <input type="text" name="tiktok_client_key" value="<?= e(tiktok_client_key()) ?>" placeholder="ex. awygg..." style="display:block;width:100%;margin-top:.3rem">
        </label>
        <label>Client secret
            <input type="text" name="tiktok_client_secret" value="" placeholder="<?= $hasSecret ? '•••••• (déjà enregistré — laisse vide pour conserver)' : 'colle le Client secret' ?>" style="display:block;width:100%;margin-top:.3rem">
        </label>
        <fieldset style="border:1px solid var(--glass-brd);border-radius:12px;padding:.8rem 1rem;margin:0">
            <legend class="muted" style="padding:0 .4rem">Mode de publication</legend>
            <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer;margin-bottom:.5rem">
                <input type="radio" name="tiktok_mode" value="draft" <?= $mode !== 'public' ? 'checked' : '' ?> style="margin-top:.25rem">
                <span><strong>Brouillon</strong> — la vidéo arrive dans ta <strong>boîte de réception TikTok</strong>, tu la postes en 1 tap. <span class="muted">✅ Fonctionne <strong>sans audit</strong> (scope video.upload).</span></span>
            </label>
            <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer">
                <input type="radio" name="tiktok_mode" value="public" <?= $mode === 'public' ? 'checked' : '' ?> style="margin-top:.25rem">
                <span><strong>Public (100% auto)</strong> — publication directe et publique, sans toucher au téléphone. <span class="muted">⚠️ Nécessite l'<strong>audit TikTok</strong> (scope video.publish) ; avant l'audit, la vidéo reste en privé.</span></span>
            </label>
            <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer;margin-top:.6rem;padding:.5rem .6rem;background:rgba(57,255,170,.06);border-radius:8px">
                <input type="checkbox" name="tiktok_public_ok" value="1" <?= (int) get_setting('tiktok_public_ok', '0') === 1 ? 'checked' : '' ?> style="margin-top:.25rem">
                <span class="muted" style="font-size:.85rem">✅ <strong>Audit TikTok validé</strong> → publier réellement en <strong>PUBLIC</strong>. <em>À cocher UNIQUEMENT après l'accord de TikTok.</em> Tant que c'est décoché, les vidéos partent en <strong>privé</strong> (idéal pour tester/filmer la démo sans erreur).</span>
            </label>
        </fieldset>
        <label style="max-width:340px">Plafond de vidéos par jour (anti-spam)
            <input type="number" name="tiktok_daily_max" value="<?= $dailyMax ?>" min="1" max="20" style="display:block;width:120px;margin-top:.3rem">
            <small class="muted">🛡️ 2-4/jour est un rythme sûr et naturel pour lancer un compte.</small>
        </label>
        <label style="display:flex;gap:.5rem;align-items:center;cursor:pointer"><input type="checkbox" name="tiktok_enabled" value="1" <?= $enabled ? 'checked' : '' ?>> <strong>Activer la publication automatique (cron)</strong></label>
        <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer"><input type="checkbox" name="tiktok_also_ig" value="1" <?= (int) get_setting('tiktok_also_ig', '0') === 1 ? 'checked' : '' ?> style="margin-top:.25rem"> <span>📸 <strong>Poster aussi en Reel Instagram</strong> <span class="muted" style="font-size:.85rem">(même vidéo + légende ; audio intégré — pas de son tendance via API)</span></span></label>
        <div><button class="btn btn--primary" type="submit">💾 Enregistrer</button></div>
    </form>

    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--glass-brd);align-items:center">
        <?php if (!$connected): ?>
            <?php if ($hasKey && $hasSecret): ?>
                <a class="btn btn--primary" href="?connect=1">🔗 Connecter mon compte TikTok</a>
            <?php else: ?>
                <button class="btn btn--primary" disabled>🔗 Connecter mon compte TikTok</button>
                <span class="muted" style="font-size:.85rem">↑ Renseigne d'abord la clé + le secret, puis enregistre.</span>
            <?php endif; ?>
        <?php else: ?>
            <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="test"><button class="btn btn--primary">🚀 Tester (poster la 1ʳᵉ vidéo)</button></form>
            <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="run"><button class="btn btn--ghost">▶️ Traiter la file</button></form>
            <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="test_ig_reel"><button class="btn btn--ghost">📸 Tester Reel Instagram</button></form>
            <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="disconnect"><button class="btn btn--ghost">🔌 Déconnecter</button></form>
        <?php endif; ?>
        <?php if ($stats['error'] > 0): ?><form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="retry_errors"><button class="btn btn--ghost">🔁 Retenter les erreurs</button></form><?php endif; ?>
    </div>
    <?php if ($connected && $scope): ?><p class="muted" style="font-size:.8rem;margin:.8rem 0 0">Scopes autorisés : <code><?= e($scope) ?></code></p><?php endif; ?>
</div>

<!-- Ajouter une vidéo -->
<div class="glass" style="padding:1.4rem;border-radius:16px;margin:1rem 0">
    <h2 style="margin-top:0;font-size:1.1rem">🎬 Ajouter une vidéo à la file</h2>
    <p class="muted" style="font-size:.88rem;margin:.2rem 0 .8rem">Colle l'URL d'une vidéo verticale (9:16, 15-25 s). <em>Dis-moi le thème et je génère la vidéo via Higgsfield puis je l'ajoute ici automatiquement.</em> La légende anglaise est créée par l'IA au moment de la publication.</p>
    <form method="post" style="display:grid;gap:.8rem;max-width:760px">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_video">
        <label>URL de la vidéo (mp4)
            <input type="text" name="source_url" placeholder="https://...cloudfront.net/....mp4" style="display:block;width:100%;margin-top:.3rem" required>
        </label>
        <label>Thème / titre (optionnel — guide la légende IA)
            <input type="text" name="title" placeholder="ex. GTA 6 Vice City map reveal" style="display:block;width:100%;margin-top:.3rem">
        </label>
        <div><button class="btn btn--ghost" type="submit">➕ Ajouter à la file</button></div>
    </form>
    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--glass-brd)">
        <a class="btn btn--primary" href="<?= e(url('admin/download-videos.php')) ?>">⬇️ Télécharger TOUTES les vidéos (ZIP)</a>
        <p class="muted" style="font-size:.82rem;margin:.5rem 0 0">21 vidéos GTA6 nommées par jour (jour-01…jour-19 + 2 bonus). Le téléchargement peut prendre ~30 s (le serveur récupère chaque vidéo).</p>
    </div>
</div>

<!-- Cron -->
<div class="glass" style="padding:1.2rem 1.4rem;border-radius:16px;margin:1rem 0">
    <h2 style="margin-top:0;font-size:1.1rem">🔁 Publication automatique</h2>
    <p class="muted" style="font-size:.88rem;margin:.2rem 0 .4rem">Branche ce lien sur un cron (toutes les 1-3 h) — <a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a> ou cPanel → Cron Jobs.</p>
    <input type="text" readonly value="<?= e($cronUrl) ?>" onclick="this.select()" style="width:100%;font-size:.8rem;font-family:monospace;padding:.5rem;border-radius:8px;background:rgba(255,255,255,.05);color:#cfc9dd;border:1px solid var(--glass-brd)">
    <button class="btn btn--ghost" type="button" data-copy="<?= e($cronUrl) ?>" style="margin-top:.6rem">📋 Copier le lien cron</button>
</div>

<!-- Guide -->
<details class="glass" style="padding:1rem 1.4rem;border-radius:16px;margin:1rem 0">
    <summary style="cursor:pointer;font-weight:700">📖 Mise en route & audit TikTok (guide)</summary>
    <ol class="muted" style="font-size:.9rem;line-height:1.7;margin:.8rem 0 0;padding-left:1.2rem">
        <li>Dans le portail <a href="https://developers.tiktok.com" target="_blank" rel="noopener">TikTok for Developers</a> → ton app <strong>ViceHubX</strong> → produit <strong>Content Posting API</strong>, scopes <code>video.upload</code> + <code>video.publish</code>.</li>
        <li><strong>Redirect URI</strong> à déclarer (identique) : <code style="user-select:all"><?= e($redirect) ?></code></li>
        <li>Vérifie le domaine <code>vicehubx.com</code> (DNS TXT) — ✅ déjà fait.</li>
        <li>Colle <strong>Client key + Client secret</strong> ci-dessus, enregistre, puis <strong>Connecter mon compte TikTok</strong>.</li>
        <li><strong>Mode Brouillon</strong> marche <strong>tout de suite</strong> (sans audit) : idéal pour l'enregistrement de la <strong>vidéo démo</strong> exigée par l'audit.</li>
        <li>Après validation de l'audit <code>video.publish</code>, passe en <strong>Mode Public</strong> = publication 100% automatique.</li>
    </ol>
</details>

<!-- File récente -->
<div class="glass" style="padding:1rem 1.2rem;border-radius:16px;margin:1rem 0;overflow-x:auto">
    <h2 style="margin-top:0;font-size:1.1rem">🗂️ Dernières vidéos</h2>
    <?php if (!$recent): ?>
        <p class="muted">Aucune vidéo en file pour le moment.</p>
    <?php else: ?>
        <table class="data-table">
            <thead><tr><th>#</th><th>Thème</th><th>Statut</th><th>Détail</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): $st = (string) $r['status']; ?>
                <tr>
                    <td><?= (int) $r['id'] ?></td>
                    <td><?= e(mb_substr((string) ($r['title'] ?? '—') ?: '—', 0, 50)) ?></td>
                    <td><?= $st === 'posted' ? '🟢 Postée' : ($st === 'error' ? '🔴 Erreur' : '🟠 En attente') ?><?= $st === 'posted' && $r['mode'] ? ' <span class="muted">(' . e((string) $r['mode']) . ')</span>' : '' ?></td>
                    <td class="muted" style="font-size:.82rem;max-width:360px;word-break:break-word"><?= e($st === 'error' ? (string) $r['error'] : ($r['posted_at'] ? 'le ' . date('d/m H:i', strtotime((string) $r['posted_at'])) : '')) ?></td>
                    <td><form method="post" style="margin:0" onsubmit="return confirm('Supprimer cette entrée ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn--ghost" style="padding:.2rem .5rem">🗑️</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script>document.querySelectorAll('[data-copy]').forEach(function(b){b.addEventListener('click',function(){navigator.clipboard&&navigator.clipboard.writeText(b.getAttribute('data-copy'));var o=b.textContent;b.textContent='✅ Copié';setTimeout(function(){b.textContent=o;},1500);});});</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
