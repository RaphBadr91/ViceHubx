<?php
/**
 * ViceHub X — Veille concurrentielle : voir les sujets publiés par les concurrents
 * (flux RSS/sitemap publics) et lancer NOTRE propre article (angle GTA VI, original).
 */
$ADMIN_TITLE = 'ViceHub X — Veille concurrents';
require __DIR__ . '/../includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/veille.php';
require_once dirname(__DIR__) . '/includes/ai.php';

// Sujet de réécriture (angle GTA VI, 100% original) + libellé de statut lisible.
$vhxTopic = static fn(array $it, string $lang): string => $lang === 'en'
    ? $it['title'] . ' — original ViceHub X angle for GTA 6, rewritten our way, 100% original'
    : $it['title'] . ' — angle original ViceHub X pour GTA 6, réécrit à notre manière, 100% original';
$vhxStatusLabel = static fn(string $s): string => $s === 'published' ? 'publication directe' : ($s === 'pending' ? 'programmation' : 'brouillon');

$flash = null;
$act = $_POST['action'] ?? '';
if ($act !== '' && !verify_csrf()) { $flash = ['err', 'Jeton CSRF invalide.']; $act = ''; }

if ($act === 'add_source') {
    $ok = veille_add_source((string) ($_POST['name'] ?? ''), (string) ($_POST['url'] ?? ''), (string) ($_POST['type'] ?? 'rss'), (string) ($_POST['lang'] ?? 'en'));
    $flash = $ok ? ['ok', 'Source ajoutée.'] : ['err', 'Nom manquant ou URL invalide (http/https).'];
} elseif ($act === 'del_source') {
    veille_delete_source((int) ($_POST['id'] ?? 0));
    $flash = ['ok', 'Source supprimée.'];
} elseif ($act === 'refresh') {
    $n = veille_fetch_all();
    $flash = ['ok', "Veille rafraîchie : {$n} nouveau(x) sujet(s) détecté(s)."];
} elseif ($act === 'ignore') {
    veille_set_item_status((int) ($_POST['id'] ?? 0), 'ignored');
    $flash = ['ok', 'Sujet ignoré.'];
} elseif ($act === 'write') {
    $it = veille_item((int) ($_POST['id'] ?? 0));
    if ($it) {
        $lang   = in_array($_POST['lang'] ?? 'fr', ['fr', 'en'], true) ? $_POST['lang'] : 'fr';
        $status = in_array($_POST['status'] ?? 'draft', ['draft', 'pending', 'published'], true) ? $_POST['status'] : 'draft';
        // Réécriture RICHE : garde l'image de la source (créditée), comme le mode auto.
        ai_brief_add_rich($vhxTopic($it, $lang), $status, $lang, (string) ($it['image_url'] ?? ''), (string) ($it['source_name'] ?? ''));
        ai_spawn_worker();                         // tente une génération immédiate (si dispo)
        veille_set_item_status((int) $it['id'], 'written');
        $flash = ['ok', 'Article lancé en réécriture (' . $vhxStatusLabel($status) . '). Il apparaîtra dans « Articles » dès sa génération (arrière-plan).'];
    } else {
        $flash = ['err', 'Sujet introuvable.'];
    }
} elseif ($act === 'write_bulk') {
    $ids    = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn($v) => $v > 0));
    $lang   = in_array($_POST['bulk_lang'] ?? 'fr', ['fr', 'en'], true) ? $_POST['bulk_lang'] : 'fr';
    $status = in_array($_POST['bulk_status'] ?? 'pending', ['draft', 'pending', 'published'], true) ? $_POST['bulk_status'] : 'pending';
    $n = 0;
    foreach ($ids as $id) {
        $it = veille_item($id);
        if (!$it) { continue; }
        ai_brief_add_rich($vhxTopic($it, $lang), $status, $lang, (string) ($it['image_url'] ?? ''), (string) ($it['source_name'] ?? ''));
        veille_set_item_status($id, 'written');
        $n++;
    }
    if ($n > 0) { ai_spawn_worker(); }
    $flash = $n > 0
        ? ['ok', "{$n} article(s) lancé(s) en réécriture (" . $vhxStatusLabel($status) . "). Ils apparaîtront dans « Articles » au fil de leur génération — plus aucun ne se perd."]
        : ['err', 'Aucun sujet sélectionné.'];
} elseif ($act === 'toggle_auto') {
    set_setting('veille_auto', ($_POST['veille_auto'] ?? '') === '1' ? '1' : '0');
    $flash = ['ok', veille_is_auto()
        ? '🟢 Auto-publication ACTIVÉE : les meilleurs sujets GTA 6 récents sont réécrits à notre plume et publiés automatiquement (2-3 par jour max).'
        : '⚪ Auto-publication désactivée (mode manuel).'];
}

$sources = veille_sources(false);
$items   = veille_items('new', 80);
$counts  = veille_counts();
$pending = count(json_decode((string) get_setting('ai_brief_queue', '[]'), true) ?: []); // articles en file de génération
?>
<div class="admin-bar">
    <h1>🔭 Veille concurrents</h1>
    <span class="muted"><?= (int) $counts['new'] ?> à traiter · <?= (int) $counts['written'] ?> lancés · <?= (int) $counts['ignored'] ?> ignorés</span>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="glass" style="padding:1.2rem;border-radius:16px;margin-bottom:1.2rem">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <h2 style="margin:0;font-size:1.05rem">Sources surveillées (<?= count($sources) ?>)</h2>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <form method="post" style="margin:0"><?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_auto">
                <input type="hidden" name="veille_auto" value="<?= veille_is_auto() ? '0' : '1' ?>">
                <button class="btn <?= veille_is_auto() ? 'btn--ghost' : 'btn--primary' ?>" type="submit">
                    <?= veille_is_auto() ? '🟢 Auto ON — cliquer pour couper' : '⚪ Activer l\'auto-publication' ?>
                </button>
            </form>
            <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="refresh">
                <button class="btn btn--primary" type="submit">🔄 Rafraîchir</button>
            </form>
        </div>
    </div>
    <?php if (veille_is_auto()): ?>
        <p class="muted" style="font-size:.82rem;margin:.4rem 0 0">⚠️ En mode auto, les <strong>2-3 meilleurs sujets GTA 6</strong> du jour sont réécrits (notre plume) et <strong>PUBLIÉS automatiquement</strong>, déclenché par le trafic. L'article reprend l'<strong>image de la source</strong> avec le crédit « Source : … » en bas. <em>Réutiliser l'image d'un concurrent peut poser un souci de droits d'auteur — le crédit atténue, mais surveille et remplace si besoin.</em></p>
    <?php endif; ?>
    <?php if ($sources): ?>
    <div style="margin-top:.8rem;display:flex;flex-direction:column;gap:.4rem">
        <?php foreach ($sources as $s): ?>
            <div style="display:flex;gap:.6rem;align-items:center;justify-content:space-between;border-bottom:1px solid var(--glass-brd,rgba(255,255,255,.08));padding:.35rem 0">
                <span><strong><?= e($s['name']) ?></strong> <span class="muted">— <?= e(strtoupper((string) ($s['lang'] ?? 'en'))) ?> · <?= e($s['type']) ?></span><br><span class="muted" style="font-size:.8rem"><?= e($s['url']) ?></span></span>
                <form method="post" style="margin:0" onsubmit="return confirm('Supprimer cette source ?')"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="del_source"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <button class="btn btn--ghost" style="padding:.3rem .6rem" type="submit">✕</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="muted" style="margin-top:.6rem">Aucune source. Ajoute le flux RSS (ex. <code>https://site-concurrent.com/feed</code>) ou le sitemap d'un concurrent GTA/gaming.</p>
    <?php endif; ?>

    <form method="post" style="margin-top:1rem;display:grid;grid-template-columns:1fr 2fr auto auto auto;gap:.6rem;align-items:end">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_source">
        <div><label>Nom</label><input type="text" name="name" required maxlength="120" placeholder="Concurrent X"></div>
        <div><label>URL du flux RSS / sitemap</label><input type="url" name="url" required maxlength="500" placeholder="https://…/feed"></div>
        <div><label>Langue</label><select name="lang"><option value="en">EN</option><option value="fr">FR</option></select></div>
        <div><label>Type</label><select name="type"><option value="rss">RSS/Atom</option><option value="sitemap">Sitemap</option></select></div>
        <button class="btn btn--primary" type="submit">Ajouter</button>
    </form>
</div>

<h2 style="font-size:1.05rem">Sujets détectés (<?= count($items) ?>)</h2>
<p class="muted" style="font-size:.85rem;margin-top:0">On ne reprend QUE l'idée de sujet — l'article est écrit à notre manière, sous l'angle GTA VI, 100% original.</p>

<?php if ($pending > 0): ?>
    <p class="muted" style="font-size:.85rem;margin:.2rem 0 .6rem;color:var(--blue)">🕒 <strong><?= (int) $pending ?></strong> article(s) en file de génération — ils se créent en arrière-plan (déclenché par le trafic du site) et remontent dans « Articles » au fur et à mesure. Aucun n'est perdu.</p>
<?php endif; ?>

<?php if ($items): ?>
<div class="glass" style="padding:.8rem 1rem;border-radius:12px;margin-bottom:.8rem;display:flex;gap:.7rem;align-items:center;flex-wrap:wrap">
    <label style="display:flex;gap:.4rem;align-items:center;margin:0;cursor:pointer"><input type="checkbox" id="vselAll"> <strong>Tout sélectionner</strong></label>
    <span class="muted" style="font-size:.85rem"><span id="vselCount">0</span> sélectionné(s)</span>
    <span style="flex:1;min-width:10px"></span>
    <form method="post" id="veilleBulk" style="margin:0;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap" onsubmit="return vhxBulkConfirm(this)">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="write_bulk">
        <select name="bulk_lang" style="padding:.35rem" title="Langue des articles"><option value="fr">FR</option><option value="en">EN</option></select>
        <select name="bulk_status" style="padding:.35rem" title="Que faire des articles réécrits">
            <option value="pending">📅 Réécrire + programmer</option>
            <option value="published">🚀 Réécrire + publier direct</option>
            <option value="draft">📝 Réécrire en brouillon</option>
        </select>
        <button class="btn btn--primary" type="submit">✍️ Réécrire la sélection</button>
    </form>
</div>
<?php endif; ?>

<?php if (!$items): ?>
    <p class="muted">Rien pour l'instant. Ajoute des sources puis clique « Rafraîchir la veille ».</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:.5rem">
    <?php foreach ($items as $it): ?>
        <div class="glass" style="padding:.7rem 1rem;border-radius:12px;display:flex;gap:1rem;align-items:center;justify-content:space-between;flex-wrap:wrap">
            <input type="checkbox" name="ids[]" value="<?= (int) $it['id'] ?>" form="veilleBulk" class="vsel" onchange="vhxBulkCount()" title="Sélectionner pour réécriture groupée" style="width:18px;height:18px;flex:0 0 auto">
            <div style="min-width:220px;flex:1">
                <a href="<?= e($it['url']) ?>" target="_blank" rel="noopener nofollow" style="font-weight:600"><?= e($it['title']) ?></a>
                <div class="muted" style="font-size:.78rem"><?= e($it['source_name'] ?? '—') ?> · <?= e($it['published_at'] ? fmt_date($it['published_at']) : '') ?></div>
            </div>
            <div style="display:flex;gap:.4rem;align-items:center">
                <form method="post" style="margin:0"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="write"><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                    <select name="lang" style="padding:.3rem"><option value="fr">FR</option><option value="en">EN</option></select>
                    <button class="btn btn--primary" style="padding:.35rem .7rem" type="submit">✍️ Écrire notre version</button>
                </form>
                <form method="post" style="margin:0"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="ignore"><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                    <button class="btn btn--ghost" style="padding:.35rem .6rem" type="submit">Ignorer</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<script>
function vhxBulkCount(){var n=document.querySelectorAll('.vsel:checked').length;var c=document.getElementById('vselCount');if(c)c.textContent=n;}
(function(){var all=document.getElementById('vselAll');if(all){all.addEventListener('change',function(){document.querySelectorAll('.vsel').forEach(function(cb){cb.checked=all.checked;});vhxBulkCount();});}})();
function vhxBulkConfirm(f){
  var n=document.querySelectorAll('.vsel:checked').length;
  if(n===0){alert('Sélectionne au moins un sujet.');return false;}
  var st=f.bulk_status.value;
  var msg=st==='published'
    ? 'Réécrire ET PUBLIER directement '+n+' article(s) ? Ils partent en ligne dès leur génération.'
    : (st==='pending' ? 'Réécrire et programmer '+n+' article(s) ? Ils se publieront de façon espacée.' : 'Réécrire '+n+' article(s) en brouillon (à relire avant publication) ?');
  return confirm(msg);
}
</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
