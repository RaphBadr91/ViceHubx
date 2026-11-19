<?php
/**
 * ViceHub X — Tableau de bord admin.
 * Vue d'ensemble : production de contenu, santé SEO, revenus, communauté,
 * état des services (IA, paiement, réseaux…) et raccourcis.
 * Chaque bloc est tolérant : une table absente affiche « — » sans casser la page.
 */
$ADMIN_TITLE = 'ViceHub X — Tableau de bord';
require __DIR__ . '/../includes/admin_header.php';

/** Exécute une requête scalaire sans jamais casser la page. */
$q1 = static function (string $sql, array $p = []) {
    try {
        $st = db()->prepare($sql);
        $st->execute($p);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    } catch (Throwable $e) {
        return null;
    }
};
$fmt = static fn($v): string => $v === null ? '—' : number_format((float) $v, 0, ',', ' ');

// ---- Contenu ----
$pubFr   = $q1("SELECT COUNT(*) FROM articles WHERE status='published' AND lang='fr'");
$pubEn   = $q1("SELECT COUNT(*) FROM articles WHERE status='published' AND lang='en'");
$pub7    = $q1("SELECT COUNT(*) FROM articles WHERE status='published' AND published_at >= NOW() - INTERVAL 7 DAY");
$sched   = $q1("SELECT COUNT(*) FROM articles WHERE status='pending' AND published_at IS NOT NULL");
$drafts  = $q1("SELECT COUNT(*) FROM articles WHERE status='draft'");
// ---- SEO ----
$merged  = $q1('SELECT COUNT(*) FROM redirects');
$noidx   = $q1('SELECT COUNT(*) FROM article_noindex');
$indexable = ($pubFr !== null && $pubEn !== null) ? max(0, (int) $pubFr + (int) $pubEn - (int) $noidx) : null;
$gscRows = $q1('SELECT COUNT(*) FROM gsc_pages');
// ---- Revenus ----
$rev30   = $q1("SELECT COALESCE(SUM(amount_total),0) FROM orders WHERE status='paid' AND created_at >= NOW() - INTERVAL 30 DAY");
$ord30   = $q1("SELECT COUNT(*) FROM orders WHERE status='paid' AND created_at >= NOW() - INTERVAL 30 DAY");
$revAll  = $q1("SELECT COALESCE(SUM(amount_total),0) FROM orders WHERE status='paid'");
$deals   = $q1('SELECT COUNT(*) FROM affiliate_links WHERE active = 1');
// ---- Communauté ----
$members = $q1('SELECT COUNT(*) FROM users');
$members7 = $q1('SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY');
$subs    = $q1('SELECT COUNT(*) FROM newsletter_subscribers');
$pendCom = $q1("SELECT COUNT(*) FROM comments WHERE status='pending'");
$veilleNew = $q1("SELECT COUNT(*) FROM competitor_items WHERE status='new'");

// ---- Graphique : articles publiés par jour (14 jours, FR / EN) ----
$days = [];
for ($i = 13; $i >= 0; $i--) { $days[date('Y-m-d', strtotime("-$i day"))] = ['fr' => 0, 'en' => 0]; }
try {
    $st = db()->query("SELECT DATE(published_at) d, lang, COUNT(*) n FROM articles
                       WHERE status='published' AND published_at >= CURDATE() - INTERVAL 13 DAY
                       GROUP BY DATE(published_at), lang");
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (isset($days[$r['d']])) { $days[$r['d']][$r['lang'] === 'en' ? 'en' : 'fr'] = (int) $r['n']; }
    }
} catch (Throwable $e) { /* graphique vide */ }
$maxDay = max(1, ...array_values(array_map(static fn($d) => $d["fr"] + $d["en"], $days)));

// ---- État des services ----
$has = static fn(string $fn, ...$a): bool => function_exists($fn) && (bool) $fn(...$a);
foreach (['ai.php', 'social.php', 'tiktok.php'] as $__f) { if (is_file(ROOT_PATH . '/includes/' . $__f)) { require_once ROOT_PATH . '/includes/' . $__f; } }
$autoOn   = (int) get_setting('ai_auto_enabled', '0') === 1;
$trAuto   = (int) get_setting('ai_tr_auto', '0') === 1;
if (is_file(ROOT_PATH . '/includes/veille.php')) { require_once ROOT_PATH . '/includes/veille.php'; }
$veilleQ = function_exists('veille_lang_quotas') ? veille_lang_quotas() : ['fr' => 1, 'en' => 0];
$health = [
    ['Rédaction IA', $has('ai_enabled'), 'Clé Anthropic configurée', 'Clé API manquante', 'admin/settings.php'],
    ['Paiement Stripe', $has('stripe_enabled'), 'Paiements actifs', 'À configurer (mode Test)', 'admin/settings.php'],
    ['Affiliation Amazon', trim((string) get_setting('amazon_tag', '')) !== '', 'Tag actif sur tous les liens', 'Tag manquant : aucune commission', 'admin/settings.php'],
    ['Facebook / Instagram', $has('social_any_ready'), 'Publication automatique prête', 'Non connecté', 'admin/social.php'],
    ['TikTok', $has('tiktok_ready'), 'Connecté', 'Non connecté (optionnel)', 'admin/tiktok.php', true],
    ['E-mails (Resend)', $has('resend_enabled'), 'Envoi fiable', 'Envoi via le serveur (risque de spams)', 'admin/settings.php', true],
];
$rel = strtotime((string) (get_setting('release_date', RELEASE_DATE) ?: RELEASE_DATE)) ?: strtotime('2026-11-19');
$sinceLaunch = (int) floor((time() - $rel) / 86400);

$recent = [];
try {
    $recent = db()->query(
        "SELECT a.id, a.title, a.slug, a.status, a.lang, a.published_at, c.name cat
         FROM articles a LEFT JOIN categories c ON c.id = a.category_id
         ORDER BY a.id DESC LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* rien */ }
$statusLbl = ['published' => ['En ligne', 'ok'], 'pending' => ['Programmé', 'info'], 'draft' => ['Brouillon', 'mute']];
$hour = (int) date('G');
$hello = $hour < 6 ? 'Bonne nuit' : ($hour < 18 ? 'Bonjour' : 'Bonsoir');
?>
<style>
    .dsh { display: grid; gap: 1.1rem; }
    .dsh-hero {
        position: relative; overflow: hidden; border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.8rem);
        background: radial-gradient(120% 140% at 0% 0%, rgba(255, 46, 136, .28), transparent 55%),
                    radial-gradient(120% 140% at 100% 100%, rgba(123, 47, 247, .3), transparent 55%),
                    rgba(20, 17, 36, .85);
        border: 1px solid rgba(255, 255, 255, .08); display: flex; gap: 1rem; align-items: center; justify-content: space-between; flex-wrap: wrap;
    }
    .dsh-hero h1 { margin: 0; font-size: clamp(1.4rem, 3.4vw, 2rem); letter-spacing: -.02em; text-transform: none; }
    .dsh-hero p { margin: .35rem 0 0; color: #b9b3cc; font-size: .95rem; }
    .dsh-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 600; background: rgba(57, 255, 170, .12); color: #7dffc8; border: 1px solid rgba(57, 255, 170, .25); }
    .dsh-chip i { width: 7px; height: 7px; border-radius: 50%; background: currentColor; box-shadow: 0 0 10px currentColor; }
    .dsh-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .dsh-btn { display: inline-flex; align-items: center; gap: .45rem; padding: .6rem 1rem; border-radius: 12px; font-weight: 600; font-size: .9rem; text-decoration: none; color: #fff; background: rgba(255, 255, 255, .07); border: 1px solid rgba(255, 255, 255, .12); transition: background .15s, transform .15s; }
    .dsh-btn:hover { background: rgba(255, 255, 255, .13); transform: translateY(-1px); }
    .dsh-btn--primary { background: linear-gradient(135deg, #ff2e88, #b03cff); border-color: transparent; box-shadow: 0 8px 24px rgba(255, 46, 136, .3); }
    .dsh-btn--primary:hover { background: linear-gradient(135deg, #ff4797, #bd55ff); }

    .dsh-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; }
    .dsh-card { border-radius: 16px; padding: 1rem 1.1rem; background: rgba(20, 17, 36, .78); border: 1px solid rgba(255, 255, 255, .07); min-width: 0; }
    .dsh-kpi__top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; color: #a59fbd; font-size: .8rem; font-weight: 600; letter-spacing: .02em; }
    .dsh-kpi__ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: none; }
    .dsh-kpi__ico svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .dsh-kpi__val { font-size: clamp(1.6rem, 3vw, 2.1rem); font-weight: 800; letter-spacing: -.02em; color: #fff; margin: .55rem 0 .15rem; line-height: 1; font-variant-numeric: tabular-nums; }
    .dsh-kpi__sub { font-size: .8rem; color: #8f88a8; }
    .dsh-kpi__sub b { color: #c9c3dc; font-weight: 600; }
    .t-pink { background: rgba(255, 46, 136, .15); color: #ff5fa2; }
    .t-violet { background: rgba(155, 92, 255, .16); color: #b48cff; }
    .t-green { background: rgba(57, 255, 170, .13); color: #5dffbe; }
    .t-amber { background: rgba(255, 176, 32, .15); color: #ffc65c; }

    .dsh-row { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 1.1rem; }
    .dsh-card h2 { margin: 0 0 .9rem; font-size: 1rem; letter-spacing: -.01em; text-transform: none; display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; flex-wrap: wrap; }
    .dsh-card h2 small { font-size: .78rem; color: #8f88a8; font-weight: 500; }
    .dsh-legend { display: flex; gap: .9rem; font-size: .78rem; color: #a59fbd; }
    .dsh-legend span::before { content: ""; display: inline-block; width: 9px; height: 9px; border-radius: 3px; margin-right: .35rem; vertical-align: -1px; background: var(--c); }
    .dsh-chart { display: grid; grid-template-columns: repeat(14, minmax(0, 1fr)); gap: .4rem; align-items: end; height: 170px; padding-top: .4rem; }
    .dsh-bar { height: 100%; position: relative; display: flex; align-items: flex-end; }
    .dsh-stack { width: 100%; display: flex; flex-direction: column-reverse; border-radius: 5px 5px 2px 2px; overflow: hidden; min-height: 2px; background: rgba(255, 255, 255, .06); }
    .dsh-bar__fr { background: linear-gradient(180deg, #ff5fa2, #ff2e88); }
    .dsh-bar__en { background: linear-gradient(180deg, #b48cff, #7b2ff7); }
    .dsh-bar__n { position: absolute; top: -1.15rem; left: 0; right: 0; text-align: center; font-size: .68rem; color: #8f88a8; font-variant-numeric: tabular-nums; }
    .dsh-axis { display: grid; grid-template-columns: repeat(14, minmax(0, 1fr)); gap: .4rem; margin-top: .4rem; font-size: .66rem; color: #7d7698; text-align: center; }
    .dsh-mini { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; margin-top: 1rem; }
    .dsh-mini div { border-radius: 12px; padding: .6rem .7rem; background: rgba(255, 255, 255, .04); }
    .dsh-mini b { display: block; font-size: 1.15rem; color: #fff; font-variant-numeric: tabular-nums; }
    .dsh-mini span { font-size: .74rem; color: #8f88a8; }

    .dsh-health { display: grid; gap: .45rem; }
    .dsh-health a { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .7rem; padding: .6rem .7rem; border-radius: 12px; text-decoration: none; color: inherit; background: rgba(255, 255, 255, .03); border: 1px solid transparent; transition: border-color .15s, background .15s; }
    .dsh-health a:hover { border-color: rgba(255, 255, 255, .1); background: rgba(255, 255, 255, .05); }
    .dsh-dot { width: 10px; height: 10px; border-radius: 50%; }
    .dsh-dot.ok { background: #39ffaa; box-shadow: 0 0 10px rgba(57, 255, 170, .6); }
    .dsh-dot.warn { background: #ffb020; box-shadow: 0 0 10px rgba(255, 176, 32, .5); }
    .dsh-dot.bad { background: #ff4d6d; box-shadow: 0 0 10px rgba(255, 77, 109, .5); }
    .dsh-health strong { display: block; font-size: .88rem; color: #fff; font-weight: 600; }
    .dsh-health small { font-size: .76rem; color: #8f88a8; }
    .dsh-health em { font-style: normal; font-size: .78rem; color: #8f88a8; }

    .dsh-list { display: grid; }
    .dsh-item { display: grid; grid-template-columns: auto 1fr auto; gap: .75rem; align-items: center; padding: .65rem .2rem; border-top: 1px solid rgba(255, 255, 255, .06); }
    .dsh-item:first-child { border-top: 0; }
    .dsh-lang { font-size: .68rem; font-weight: 800; letter-spacing: .06em; padding: .2rem .4rem; border-radius: 6px; background: rgba(255, 255, 255, .07); color: #c9c3dc; }
    .dsh-item a.t { color: #ece9f5; text-decoration: none; font-size: .9rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
    .dsh-item a.t:hover { color: #fff; text-decoration: underline; }
    .dsh-item small { color: #8f88a8; font-size: .76rem; }
    .dsh-pill { font-size: .72rem; font-weight: 700; padding: .25rem .6rem; border-radius: 999px; white-space: nowrap; }
    .dsh-pill.ok { background: rgba(57, 255, 170, .12); color: #5dffbe; }
    .dsh-pill.info { background: rgba(155, 92, 255, .16); color: #c3a3ff; }
    .dsh-pill.mute { background: rgba(255, 255, 255, .07); color: #a59fbd; }

    .dsh-quick { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
    .dsh-quick a { display: flex; align-items: center; gap: .6rem; padding: .75rem .8rem; border-radius: 12px; text-decoration: none; color: #ece9f5; font-size: .86rem; font-weight: 600; background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .07); transition: background .15s, border-color .15s; }
    .dsh-quick a:hover { background: rgba(255, 46, 136, .1); border-color: rgba(255, 46, 136, .35); }
    .dsh-quick svg { width: 18px; height: 18px; flex: none; fill: none; stroke: #ff5fa2; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 1100px) { .dsh-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .dsh-row { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 520px) {
        .dsh-kpis { gap: .6rem; }
        .dsh-card { padding: .85rem .9rem; }
        .dsh-kpi__val { font-size: 1.45rem; }
        .dsh-chart { gap: .2rem; height: 140px; }
        .dsh-axis { gap: .2rem; font-size: .58rem; }
        .dsh-axis span:nth-child(even) { visibility: hidden; }
        .dsh-mini { grid-template-columns: 1fr 1fr 1fr; }
        .dsh-hero .dsh-actions { width: 100%; }
        .dsh-hero .dsh-btn { flex: 1; justify-content: center; }
    }
</style>

<div class="dsh">
    <!-- En-tête -->
    <section class="dsh-hero">
        <div>
            <span class="dsh-chip"><i></i><?= $sinceLaunch >= 0 ? 'GTA 6 est sorti · jour ' . ($sinceLaunch + 1) : 'Sortie de GTA 6 dans ' . abs($sinceLaunch) . ' jours' ?></span>
            <h1 style="margin-top:.7rem"><?= e($hello) ?>, <?= e($admin_user['username'] ?? '') ?></h1>
            <p><?= e(fmt_date(date('Y-m-d H:i:s'))) ?> · <?= $fmt($pub7) ?> article(s) publié(s) ces 7 derniers jours</p>
        </div>
        <div class="dsh-actions">
            <a class="dsh-btn dsh-btn--primary" href="<?= e(url('admin/article-create.php')) ?>">+ Nouvel article</a>
            <a class="dsh-btn" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">Voir le site ↗</a>
        </div>
    </section>

    <!-- Indicateurs clés -->
    <section class="dsh-kpis" aria-label="Indicateurs clés">
        <div class="dsh-card">
            <div class="dsh-kpi__top">Articles en ligne<span class="dsh-kpi__ico t-pink"><svg viewBox="0 0 24 24"><path d="M14 3H6.5v18h11V6.5Z"/><path d="M14 3v3.5h3.5"/><path d="M9 12h6M9 16h6"/></svg></span></div>
            <div class="dsh-kpi__val"><?= $fmt($pubFr === null ? null : (int) $pubFr + (int) $pubEn) ?></div>
            <div class="dsh-kpi__sub"><b><?= $fmt($pubFr) ?></b> FR · <b><?= $fmt($pubEn) ?></b> EN</div>
        </div>
        <div class="dsh-card">
            <div class="dsh-kpi__top">Pages indexables<span class="dsh-kpi__ico t-green"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg></span></div>
            <div class="dsh-kpi__val"><?= $fmt($indexable) ?></div>
            <div class="dsh-kpi__sub"><b><?= $fmt($merged) ?></b> fusionnées · <b><?= $fmt($noidx) ?></b> hors index</div>
        </div>
        <div class="dsh-card">
            <div class="dsh-kpi__top">Revenus 30 jours<span class="dsh-kpi__ico t-amber"><svg viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8Z"/><circle cx="7.5" cy="8.5" r="1.3"/></svg></span></div>
            <div class="dsh-kpi__val"><?= $rev30 === null ? '—' : number_format((float) $rev30, 2, ',', ' ') . ' €' ?></div>
            <div class="dsh-kpi__sub"><b><?= $fmt($ord30) ?></b> commande(s) · total <b><?= $revAll === null ? '—' : number_format((float) $revAll, 0, ',', ' ') . ' €' ?></b></div>
        </div>
        <div class="dsh-card">
            <div class="dsh-kpi__top">Communauté<span class="dsh-kpi__ico t-violet"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c.6-3.6 3-5.5 6-5.5s5.4 1.9 6 5.5"/><path d="M16 4.8a3 3 0 0 1 0 6.2M18 14.8c1.7.6 2.7 2.2 3 5.2"/></svg></span></div>
            <div class="dsh-kpi__val"><?= $fmt($members) ?></div>
            <div class="dsh-kpi__sub"><b>+<?= $fmt($members7) ?></b> cette semaine · <b><?= $fmt($subs) ?></b> abonnés</div>
        </div>
    </section>

    <div class="dsh-row">
        <!-- Production de contenu -->
        <section class="dsh-card">
            <h2>Publications des 14 derniers jours
                <span class="dsh-legend"><span style="--c:#ff2e88">Français</span><span style="--c:#7b2ff7">Anglais</span></span></h2>
            <div class="dsh-chart" role="img" aria-label="Articles publiés par jour sur 14 jours">
                <?php foreach ($days as $d => $v): $tot = $v['fr'] + $v['en']; ?>
                    <div class="dsh-bar" title="<?= e(date('d/m', strtotime($d))) ?> : <?= $v['fr'] ?> FR, <?= $v['en'] ?> EN">
                        <?php if ($tot > 0): ?><span class="dsh-bar__n" style="bottom:calc(<?= round($tot / $maxDay * 100, 1) ?>% + .2rem);top:auto"><?= $tot ?></span><?php endif; ?>
                        <div class="dsh-stack" style="height:<?= $tot > 0 ? round($tot / $maxDay * 100, 1) : 0 ?>%">
                            <?php if ($v['fr'] > 0): ?><span class="dsh-bar__fr" style="height:<?= round($v['fr'] / max(1, $tot) * 100, 1) ?>%"></span><?php endif; ?>
                            <?php if ($v['en'] > 0): ?><span class="dsh-bar__en" style="height:<?= round($v['en'] / max(1, $tot) * 100, 1) ?>%"></span><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="dsh-axis" aria-hidden="true"><?php foreach (array_keys($days) as $d): ?><span><?= e(date('d/m', strtotime($d))) ?></span><?php endforeach; ?></div>
            <div class="dsh-mini">
                <div><b><?= $fmt($sched) ?></b><span>Programmés</span></div>
                <div><b><?= $fmt($drafts) ?></b><span>Brouillons</span></div>
                <div><b><?= $fmt($veilleNew) ?></b><span>Actus en veille</span></div>
            </div>
        </section>

        <!-- État des services -->
        <section class="dsh-card">
            <h2>État des services <small>clique pour régler</small></h2>
            <div class="dsh-health">
                <?php foreach ($health as $h): $cls = $h[1] ? 'ok' : (!empty($h[5]) ? 'warn' : 'bad'); ?>
                    <a href="<?= e(url($h[4])) ?>">
                        <span class="dsh-dot <?= $cls ?>" aria-hidden="true"></span>
                        <span><strong><?= e($h[0]) ?></strong><small><?= e($h[1] ? $h[2] : $h[3]) ?></small></span>
                        <em aria-hidden="true">›</em>
                    </a>
                <?php endforeach; ?>
                <a href="<?= e(url('admin/ai-articles.php')) ?>">
                    <span class="dsh-dot <?= get_setting('veille_auto', '0') === '1' ? 'ok' : 'warn' ?>" aria-hidden="true"></span>
                    <span><strong>Rythme de publication</strong><small><?= get_setting('veille_auto', '0') === '1' ? 'Veille auto activée' : 'Veille auto coupée' ?> · <?= (int) $veilleQ['fr'] ?> FR + <?= (int) $veilleQ['en'] ?> EN/jour · traduction auto <?= $trAuto ? 'activée' : 'désactivée' ?></small></span>
                    <em aria-hidden="true">›</em>
                </a>
            </div>
        </section>
    </div>

    <div class="dsh-row">
        <!-- Derniers articles -->
        <section class="dsh-card">
            <h2>Derniers articles <a class="link-all" style="font-size:.82rem" href="<?= e(url('admin/articles.php')) ?>">Tout voir →</a></h2>
            <div class="dsh-list">
                <?php foreach ($recent as $r): [$lbl, $cls] = $statusLbl[$r['status']] ?? [$r['status'], 'mute']; ?>
                    <div class="dsh-item">
                        <span class="dsh-lang"><?= e(strtoupper((string) $r['lang'])) ?></span>
                        <div style="min-width:0">
                            <a class="t" href="<?= e(url('admin/article-edit.php?id=' . (int) $r['id'])) ?>" title="<?= e($r['title']) ?>"><?= e($r['title']) ?></a>
                            <small><?= e($r['cat'] ?? '—') ?><?= !empty($r['published_at']) ? ' · ' . e(fmt_date($r['published_at'])) : '' ?></small>
                        </div>
                        <span class="dsh-pill <?= $cls ?>"><?= e($lbl) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$recent): ?><p class="muted">Aucun article pour l'instant.</p><?php endif; ?>
            </div>
        </section>

        <!-- Raccourcis -->
        <section class="dsh-card">
            <h2>Raccourcis</h2>
            <div class="dsh-quick">
                <a href="<?= e(url('admin/ai-articles.php')) ?>"><svg viewBox="0 0 24 24"><path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><path d="m12 8 1.5 2.5L16 12l-2.5 1.5L12 16l-1.5-2.5L8 12l2.5-1.5Z"/></svg>Articles IA</a>
                <a href="<?= e(url('admin/veille.php')) ?>"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><path d="M12 12 18.5 6"/></svg>Veille</a>
                <a href="<?= e(url('admin/seo-cleanup.php')) ?>"><svg viewBox="0 0 24 24"><path d="m14 4 6 6"/><path d="M12.5 7.5 4 16l4 4 8.5-8.5"/></svg>Nettoyage SEO</a>
                <a href="<?= e(url('admin/social.php')) ?>"><svg viewBox="0 0 24 24"><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6"/></svg>Réseaux</a>
                <a href="<?= e(url('admin/deals.php')) ?>"><svg viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8Z"/><circle cx="7.5" cy="8.5" r="1.3"/></svg>Deals (<?= $fmt($deals) ?>)</a>
                <a href="<?= e(url('admin/orders.php')) ?>"><svg viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6M9 12h6"/></svg>Commandes</a>
            </div>
            <?php if ((int) $pendCom > 0): ?>
                <p style="margin:.9rem 0 0;font-size:.85rem;color:#ffc65c">💬 <?= $fmt($pendCom) ?> commentaire(s) en attente de modération.</p>
            <?php endif; ?>
            <?php if ((int) $gscRows === 0): ?>
                <p style="margin:.9rem 0 0;font-size:.85rem;color:#a59fbd">Astuce : importe ton export Search Console dans <a class="link-all" href="<?= e(url('admin/seo-cleanup.php')) ?>">Nettoyage SEO</a> pour protéger tes pages qui ont du trafic.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
