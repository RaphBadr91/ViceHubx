<?php
/**
 * ViceHub X — Veille concurrentielle : voir les sujets publiés par les concurrents
 * (flux RSS/sitemap publics) et lancer NOTRE propre article (angle GTA VI, original).
 */
$ADMIN_TITLE = 'ViceHub X — Veille concurrents';
require __DIR__ . '/../includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/veille.php';
require_once dirname(__DIR__) . '/includes/ai.php';

$flash = null;
$act = $_POST['action'] ?? '';
if ($act !== '' && !verify_csrf()) { $flash = ['err', 'Jeton CSRF invalide.']; $act = ''; }

if ($act === 'add_source') {
    $ok = veille_add_source((string) ($_POST['name'] ?? ''), (string) ($_POST['url'] ?? ''), (string) ($_POST['type'] ?? 'rss'));
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
        $lang  = in_array($_POST['lang'] ?? 'fr', ['fr', 'en'], true) ? $_POST['lang'] : 'fr';
        $topic = $lang === 'en'
            ? $it['title'] . ' — original ViceHub X angle for GTA 6, rewritten our way, 100% original'
            : $it['title'] . ' — angle original ViceHub X pour GTA 6, réécrit à notre manière, 100% original';
        ai_brief_add([$topic], 'draft', $lang);   // brouillon : on relit avant publication
        ai_spawn_worker();                         // lance la génération en arrière-plan
        veille_set_item_status((int) $it['id'], 'written');
        $flash = ['ok', 'Article lancé (brouillon, en génération). Retrouve-le dans « Articles ».'];
    } else {
        $flash = ['err', 'Sujet introuvable.'];
    }
}

$sources = veille_sources(false);
$items   = veille_items('new', 80);
$counts  = veille_counts();
?>
<div class="admin-bar">
    <h1>🔭 Veille concurrents</h1>
    <span class="muted"><?= (int) $counts['new'] ?> à traiter · <?= (int) $counts['written'] ?> lancés · <?= (int) $counts['ignored'] ?> ignorés</span>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="glass" style="padding:1.2rem;border-radius:16px;margin-bottom:1.2rem">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <h2 style="margin:0;font-size:1.05rem">Sources surveillées (<?= count($sources) ?>)</h2>
        <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="refresh">
            <button class="btn btn--primary" type="submit">🔄 Rafraîchir la veille</button>
        </form>
    </div>
    <?php if ($sources): ?>
    <div style="margin-top:.8rem;display:flex;flex-direction:column;gap:.4rem">
        <?php foreach ($sources as $s): ?>
            <div style="display:flex;gap:.6rem;align-items:center;justify-content:space-between;border-bottom:1px solid var(--glass-brd,rgba(255,255,255,.08));padding:.35rem 0">
                <span><strong><?= e($s['name']) ?></strong> <span class="muted">— <?= e($s['type']) ?></span><br><span class="muted" style="font-size:.8rem"><?= e($s['url']) ?></span></span>
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

    <form method="post" style="margin-top:1rem;display:grid;grid-template-columns:1fr 2fr auto auto;gap:.6rem;align-items:end">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_source">
        <div><label>Nom</label><input type="text" name="name" required maxlength="120" placeholder="Concurrent X"></div>
        <div><label>URL du flux RSS / sitemap</label><input type="url" name="url" required maxlength="500" placeholder="https://…/feed"></div>
        <div><label>Type</label><select name="type"><option value="rss">RSS/Atom</option><option value="sitemap">Sitemap</option></select></div>
        <button class="btn btn--primary" type="submit">Ajouter</button>
    </form>
</div>

<h2 style="font-size:1.05rem">Sujets détectés (<?= count($items) ?>)</h2>
<p class="muted" style="font-size:.85rem;margin-top:0">On ne reprend QUE l'idée de sujet — l'article est écrit à notre manière, sous l'angle GTA VI, 100% original.</p>

<?php if (!$items): ?>
    <p class="muted">Rien pour l'instant. Ajoute des sources puis clique « Rafraîchir la veille ».</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:.5rem">
    <?php foreach ($items as $it): ?>
        <div class="glass" style="padding:.7rem 1rem;border-radius:12px;display:flex;gap:1rem;align-items:center;justify-content:space-between;flex-wrap:wrap">
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
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
