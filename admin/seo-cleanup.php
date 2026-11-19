<?php
/**
 * ViceHub X — Nettoyage SEO : fusion des articles en double + pages minces.
 *
 * Google refuse d'indexer des milliers de pages (« Explorée, actuellement non
 * indexée ») parce que la génération IA a écrit des dizaines de variantes des
 * mêmes sujets. Cette page :
 *   1. détecte les familles de doublons (titres quasi identiques, même langue) ;
 *   2. garde UN article principal par famille (le plus vu sur Google, sinon le
 *      plus complet) et redirige les autres vers lui en 301 ;
 *   3. retire de l'index les pages trop courtes jamais vues sur Google ;
 *   4. permet d'annuler tout le nettoyage en un clic.
 * Rien n'est supprimé : les doublons passent en brouillon.
 */
$ADMIN_TITLE = 'ViceHub X — Nettoyage SEO';
require __DIR__ . '/../includes/admin_header.php';
@set_time_limit(300);
prune_ensure_tables();

$thresholds = ['0.6' => 'Prudent (doublons évidents)', '0.5' => 'Recommandé', '0.45' => 'Large (plus de fusions)'];
$th = (string) ($_REQUEST['th'] ?? '0.5');
if (!isset($thresholds[$th])) { $th = '0.5'; }
$thin = max(0, min(1500, (int) ($_REQUEST['thin'] ?? 300)));

$flash = null;
$act = $_POST['action'] ?? '';
if ($act !== '' && !verify_csrf()) { $flash = ['err', 'Jeton CSRF invalide, recharge la page.']; $act = ''; }

if ($act === 'gsc' && !empty($_FILES['gsc']['tmp_name']) && is_uploaded_file($_FILES['gsc']['tmp_name'])) {
    $tmp = $_FILES['gsc']['tmp_name'];
    $csv = '';
    // Accepte le ZIP exporté tel quel par la Search Console, ou directement Pages.csv.
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmp) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match('#(^|/)pages\.csv$#i', (string) $zip->getNameIndex($i))) { $csv = (string) $zip->getFromIndex($i); break; }
            }
            $zip->close();
        }
    }
    if ($csv === '') { $csv = (string) file_get_contents($tmp); }
    $n = prune_import_gsc($csv);
    $flash = $n > 0
        ? ['ok', "✅ Données Google importées : {$n} articles avec impressions. Ils seront gardés en priorité comme articles principaux."]
        : ['err', 'Aucune ligne /article/ trouvée. Envoie l\'export « Performance » de la Search Console (ZIP ou Pages.csv).'];
}
if ($act === 'apply') {
    try {
        $res = prune_apply(prune_plan((float) $th, $thin));
        $flash = ['ok', "✅ Nettoyage appliqué : {$res['merged']} doublons fusionnés (301) et {$res['thin']} pages minces retirées de l'index. Lot n° {$res['batch']} (annulable ci-dessous)."];
    } catch (Throwable $e) {
        $flash = ['err', 'Erreur, rien n\'a été modifié : ' . $e->getMessage()];
    }
}
if ($act === 'undo') {
    $batch = preg_replace('/[^0-9]/', '', (string) ($_POST['batch'] ?? ''));
    try {
        $n = $batch !== '' ? prune_undo($batch) : 0;
        if ($batch === (string) get_setting('prune_last_batch', '')) { set_setting('prune_last_batch', ''); }
        $flash = ['ok', "↩️ Nettoyage n° {$batch} annulé ({$n} pages remises comme avant)."];
    } catch (Throwable $e) {
        $flash = ['err', 'Annulation impossible : ' . $e->getMessage()];
    }
}

$plan = prune_plan((float) $th, $thin);
$totMerge = array_sum(array_column($plan['langs'], 'merge'));
$totThin  = array_sum(array_column($plan['langs'], 'thin'));
$totArts  = array_sum(array_column($plan['langs'], 'total'));
try { $gscCount = (int) db()->query('SELECT COUNT(*) FROM gsc_pages')->fetchColumn(); } catch (Throwable $e) { $gscCount = 0; }
try {
    $batches = db()->query("SELECT batch, SUM(action='merge') m, SUM(action='noindex') t, MIN(created_at) d FROM prune_log GROUP BY batch ORDER BY batch DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $batches = []; }
$dupLast = (string) get_setting('ai_dup_last', '');
$artUrl = static fn(string $slug): string => url('pages/article.php?slug=' . rawurlencode($slug));
?>
<div class="admin-bar"><h1>🧹 Nettoyage SEO</h1></div>
<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>" style="margin:1rem 0"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="glass" style="padding:1rem 1.2rem;border-radius:12px;margin:.6rem 0;font-size:.92rem;line-height:1.55">
    <strong>Pourquoi ?</strong> Google refuse d'indexer les pages qui se ressemblent trop (« Explorée, actuellement non indexée »).
    Cet outil garde <strong>un seul article par sujet</strong> (le plus vu sur Google, sinon le plus complet) et redirige les doublons vers lui en 301 :
    leurs visiteurs et leurs liens sont transférés à l'article principal. Les pages trop courtes jamais vues sur Google sont retirées de l'index.
    <strong>Rien n'est supprimé</strong> : les doublons passent en brouillon, et tout est <strong>annulable en un clic</strong>.
</div>

<!-- 1. Données Google (optionnel) -->
<div class="glass" style="padding:1.1rem 1.2rem;border-radius:14px;margin:1rem 0">
    <h2 style="margin-top:0;font-size:1.05rem">1. Données Google <small class="muted">(recommandé)</small></h2>
    <p class="muted" style="margin:.2rem 0 .7rem;font-size:.9rem">
        Search Console → Performances → période <strong>3 derniers mois</strong> → <strong>Exporter → CSV</strong>. Envoie le ZIP tel quel.
        Les articles qui ont déjà des impressions sont alors <strong>toujours gardés</strong> comme articles principaux.
        <?= $gscCount > 0 ? "<br>✅ Déjà importé : <strong>{$gscCount}</strong> articles avec impressions." : '<br>⚪ Rien d\'importé pour l\'instant.' ?>
    </p>
    <form method="post" enctype="multipart/form-data" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
        <?= csrf_field() ?><input type="hidden" name="action" value="gsc">
        <input type="hidden" name="th" value="<?= e($th) ?>"><input type="hidden" name="thin" value="<?= (int) $thin ?>">
        <input type="file" name="gsc" accept=".zip,.csv" required>
        <button class="btn btn--ghost" type="submit">⬆️ Importer</button>
    </form>
</div>

<!-- 2. Réglages + résumé -->
<div class="glass" style="padding:1.1rem 1.2rem;border-radius:14px;margin:1rem 0">
    <h2 style="margin-top:0;font-size:1.05rem">2. Simulation <small class="muted">(rien n'est modifié tant que tu ne cliques pas sur « Appliquer »)</small></h2>
    <form method="get" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end;margin-bottom:1rem">
        <label style="display:grid;gap:.2rem;font-size:.85rem">Niveau de fusion
            <select name="th"><?php foreach ($thresholds as $k => $lbl): ?><option value="<?= e($k) ?>"<?= $k === $th ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?></select></label>
        <label style="display:grid;gap:.2rem;font-size:.85rem">Page mince si moins de (mots)
            <input type="number" name="thin" value="<?= (int) $thin ?>" min="0" max="1500" step="50" style="width:120px"></label>
        <button class="btn btn--ghost" type="submit">🔄 Recalculer</button>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.7rem">
        <div class="glass" style="padding:.8rem;border-radius:10px"><div class="muted" style="font-size:.8rem">Articles publiés</div><strong style="font-size:1.4rem"><?= $totArts ?></strong></div>
        <div class="glass" style="padding:.8rem;border-radius:10px"><div class="muted" style="font-size:.8rem">Doublons à fusionner</div><strong style="font-size:1.4rem;color:#ff2e88"><?= $totMerge ?></strong></div>
        <div class="glass" style="padding:.8rem;border-radius:10px"><div class="muted" style="font-size:.8rem">Pages minces → hors index</div><strong style="font-size:1.4rem;color:#ffb020"><?= $totThin ?></strong></div>
        <div class="glass" style="padding:.8rem;border-radius:10px"><div class="muted" style="font-size:.8rem">Pages restantes</div><strong style="font-size:1.4rem;color:#39ffaa"><?= $totArts - $totMerge - $totThin ?></strong></div>
    </div>
    <p class="muted" style="font-size:.85rem;margin:.7rem 0 0">
        <?php foreach ($plan['langs'] as $lg => $s): ?>
            <?= strtoupper(e($lg)) ?> : <?= $s['total'] ?> articles, <?= $s['groups'] ?> familles de doublons, <?= $s['merge'] ?> à fusionner, <?= $s['thin'] ?> minces.&nbsp;&nbsp;
        <?php endforeach; ?>
    </p>

    <?php if ($totMerge + $totThin > 0): ?>
    <form method="post" style="margin-top:1rem" onsubmit="return confirm('Appliquer le nettoyage : <?= $totMerge ?> fusions 301 et <?= $totThin ?> pages hors index ? (annulable ensuite)');">
        <?= csrf_field() ?><input type="hidden" name="action" value="apply">
        <input type="hidden" name="th" value="<?= e($th) ?>"><input type="hidden" name="thin" value="<?= (int) $thin ?>">
        <button class="btn btn--primary" type="submit">✅ Appliquer le nettoyage</button>
    </form>
    <?php else: ?>
        <p style="margin-top:1rem">✅ Aucun doublon ni page mince détecté avec ces réglages.</p>
    <?php endif; ?>
</div>

<!-- 3. Annulation -->
<?php if ($batches): ?>
<div class="glass" style="padding:1.1rem 1.2rem;border-radius:14px;margin:1rem 0">
    <h2 style="margin-top:0;font-size:1.05rem">3. Nettoyages appliqués</h2>
    <?php foreach ($batches as $b): ?>
        <form method="post" style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;margin:.4rem 0" onsubmit="return confirm('Annuler ce nettoyage et tout remettre comme avant ?');">
            <?= csrf_field() ?><input type="hidden" name="action" value="undo"><input type="hidden" name="batch" value="<?= e($b['batch']) ?>">
            <span>Lot <strong><?= e($b['batch']) ?></strong> du <?= e((string) $b['d']) ?> : <?= (int) $b['m'] ?> fusions, <?= (int) $b['t'] ?> pages hors index</span>
            <button class="btn btn--ghost" type="submit">↩️ Annuler</button>
        </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($dupLast !== ''): ?>
<p class="muted" style="font-size:.85rem">🛡️ Garde-fou du générateur IA, dernier article bloqué car trop proche d'un existant : <?= e($dupLast) ?></p>
<?php endif; ?>

<!-- Détail -->
<?php if ($plan['groups']): ?>
<h2 style="font-size:1.05rem;margin-top:1.6rem">Familles de doublons <small class="muted">(★ = article gardé ; les autres sont redirigés vers lui)</small></h2>
<?php foreach (array_slice($plan['groups'], 0, 150) as $g): ?>
    <div class="glass" style="padding:.7rem .9rem;border-radius:10px;margin:.45rem 0;font-size:.86rem;overflow-wrap:anywhere">
        <div>★ <strong><?= strtoupper(e($g['lang'])) ?></strong> <a href="<?= e($artUrl((string) $g['master']['slug'])) ?>" target="_blank"><?= e((string) $g['master']['title']) ?></a>
            <span class="muted">· ~<?= (int) $g['master']['words'] ?> mots<?= (int) $g['master']['impr'] > 0 ? ' · 👁 ' . (int) $g['master']['impr'] : '' ?></span></div>
        <?php foreach ($g['dups'] as $d): ?>
            <div class="muted" style="margin:.2rem 0 0 1.2rem">↳ <a href="<?= e($artUrl((string) $d['slug'])) ?>" target="_blank"><?= e((string) $d['title']) ?></a>
                · similarité <?= e((string) $d['sim']) ?> · ~<?= (int) $d['words'] ?> mots<?= (int) $d['impr'] > 0 ? ' · 👁 ' . (int) $d['impr'] : '' ?></div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php if (count($plan['groups']) > 150): ?><p class="muted">… et <?= count($plan['groups']) - 150 ?> autres familles (toutes traitées par « Appliquer »).</p><?php endif; ?>
<?php endif; ?>

<?php if ($plan['thin']): ?>
<h2 style="font-size:1.05rem;margin-top:1.6rem">Pages minces retirées de l'index <small class="muted">(restent lisibles, liens suivis)</small></h2>
<div class="glass" style="padding:.7rem .9rem;border-radius:10px;font-size:.86rem;overflow-wrap:anywhere">
<?php foreach (array_slice($plan['thin'], 0, 100) as $a): ?>
    <div class="muted"><?= strtoupper(e($a['lang'])) ?> · <a href="<?= e($artUrl((string) $a['slug'])) ?>" target="_blank"><?= e((string) $a['title']) ?></a> · ~<?= (int) $a['words'] ?> mots</div>
<?php endforeach; ?>
<?php if (count($plan['thin']) > 100): ?><div class="muted">… et <?= count($plan['thin']) - 100 ?> autres.</div><?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
