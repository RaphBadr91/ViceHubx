<?php
/**
 * ViceHub X — Tableau de bord Analytics (admin).
 * Vue d'ensemble de l'activité : contenu, communauté, boutique et tendances.
 */
// ⚠️ Ne PAS utiliser de constante de config ici : elle n'est définie qu'après le
// require ci-dessous (config.php). Sur PHP 8.1 une constante non définie = fatal 500.
$ADMIN_TITLE = 'ViceHub X — Analytics';
require __DIR__ . '/../includes/admin_header.php';

/** Scalaire d'une requête (0 si la table/colonne n'existe pas). */
$scalar = static function (string $sql, array $args = []) {
    try {
        $st = db()->prepare($sql);
        $st->execute($args);
        return $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
};
/** Lignes d'une requête (tableau vide en cas d'erreur). */
$rows = static function (string $sql, array $args = []): array {
    try {
        $st = db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
};

// ----- Cartes de statistiques -----
$articles_total = (int) $scalar("SELECT COUNT(*) FROM articles");
$articles_pub   = (int) $scalar("SELECT COUNT(*) FROM articles WHERE status='published'");
$members_real   = (int) $scalar("SELECT COUNT(*) FROM users WHERE password_hash <> '!'");
$members_ai     = (int) $scalar("SELECT COUNT(*) FROM users WHERE password_hash = '!'");
$threads        = (int) $scalar("SELECT COUNT(*) FROM forum_threads");
$posts          = (int) $scalar("SELECT COUNT(*) FROM forum_posts");
$products       = (int) $scalar("SELECT COUNT(*) FROM products");
$orders_paid    = (int) $scalar("SELECT COUNT(*) FROM orders WHERE status='paid'");
$subscribers    = (int) $scalar("SELECT COUNT(*) FROM newsletter_subscribers");
$fanarts        = (int) $scalar("SELECT COUNT(*) FROM fanarts");
$events_up      = (int) $scalar("SELECT COUNT(*) FROM events WHERE event_date >= NOW()");
$likes          = (int) $scalar("SELECT COUNT(*) FROM likes");

// Chiffre d'affaires encaissé (par devise)
$revenue = $rows("SELECT currency, SUM(amount_total) total, COUNT(*) n FROM orders WHERE status='paid' GROUP BY currency");

// ----- Tendance : inscriptions & commandes sur 14 jours -----
$signup_series = $rows(
    "SELECT DATE(created_at) d, COUNT(*) n FROM users
     WHERE password_hash <> '!' AND created_at >= (CURRENT_DATE - INTERVAL 13 DAY)
     GROUP BY DATE(created_at)"
);
$order_series = $rows(
    "SELECT DATE(created_at) d, COUNT(*) n FROM orders
     WHERE status='paid' AND created_at >= (CURRENT_DATE - INTERVAL 13 DAY)
     GROUP BY DATE(created_at)"
);
// Indexe par jour pour combler les trous
$by_day = static function (array $series): array {
    $map = [];
    foreach ($series as $r) { $map[(string) $r['d']] = (int) $r['n']; }
    $out = [];
    for ($i = 13; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i day"));
        $out[$day] = $map[$day] ?? 0;
    }
    return $out;
};
$signups14 = $by_day($signup_series);
$orders14  = $by_day($order_series);

// ----- Tableaux -----
$recent_users = $rows(
    "SELECT username, display_name, role, created_at FROM users
     WHERE password_hash <> '!' ORDER BY id DESC LIMIT 8"
);
$recent_orders = $rows(
    "SELECT email, amount_total, currency, status, delivered, created_at
     FROM orders ORDER BY id DESC LIMIT 8"
);
$top_members = $rows(
    "SELECT u.username, u.display_name,
            (SELECT COUNT(*) FROM forum_posts p WHERE p.user_id = u.id) posts,
            (SELECT COUNT(*) FROM forum_threads t WHERE t.user_id = u.id) threads
     FROM users u
     HAVING posts > 0 OR threads > 0
     ORDER BY (posts + threads*2) DESC LIMIT 8"
);
$top_threads = $rows(
    "SELECT t.id, t.title,
            (SELECT COUNT(*) FROM forum_posts p WHERE p.thread_id = t.id) replies,
            t.last_post_at
     FROM forum_threads t ORDER BY replies DESC, t.last_post_at DESC LIMIT 8"
);

/** Mini graphique en barres (14 jours). */
$bar_chart = static function (array $data, string $accent): string {
    $max = max(1, max($data));
    $html = '<div class="mini-chart">';
    foreach ($data as $day => $n) {
        $h = (int) round(($n / $max) * 100);
        $label = date('d/m', strtotime($day));
        $html .= '<div class="mini-chart__col" title="' . e($label . ' — ' . $n) . '">'
            . '<div class="mini-chart__bar" style="height:' . max(4, $h) . '%;background:' . $accent . '"></div>'
            . '<span class="mini-chart__n">' . ($n ?: '') . '</span></div>';
    }
    return $html . '</div>';
};
?>
<div class="admin-bar">
    <h1>📊 Analytics</h1>
    <a class="btn btn--ghost" href="<?= e(url('admin/dashboard.php')) ?>">← <?= e(t('admin_dashboard')) ?></a>
</div>

<div class="stat-grid">
    <div class="stat glass"><div class="num"><?= $articles_pub ?></div><div class="lbl">Articles publiés</div></div>
    <div class="stat glass"><div class="num"><?= $members_real ?></div><div class="lbl">Membres réels</div></div>
    <div class="stat glass"><div class="num"><?= $threads ?></div><div class="lbl">Sujets forum</div></div>
    <div class="stat glass"><div class="num"><?= $posts ?></div><div class="lbl">Messages forum</div></div>
    <div class="stat glass"><div class="num"><?= $products ?></div><div class="lbl">Produits</div></div>
    <div class="stat glass"><div class="num"><?= $orders_paid ?></div><div class="lbl">Commandes payées</div></div>
    <div class="stat glass"><div class="num"><?= $subscribers ?></div><div class="lbl">Newsletter</div></div>
    <div class="stat glass"><div class="num"><?= $fanarts ?></div><div class="lbl">Fan-arts</div></div>
    <div class="stat glass"><div class="num"><?= $likes ?></div><div class="lbl">Likes / réactions</div></div>
    <div class="stat glass"><div class="num"><?= $events_up ?></div><div class="lbl">Événements à venir</div></div>
    <div class="stat glass"><div class="num"><?= $members_ai ?></div><div class="lbl">Personas IA</div></div>
    <div class="stat glass"><div class="num"><?= $articles_total ?></div><div class="lbl">Articles (total)</div></div>
</div>

<?php if ($revenue): ?>
<div class="stat-grid" style="margin-top:1rem">
    <?php foreach ($revenue as $r): ?>
        <div class="stat glass" style="border-color:rgba(57,255,170,.35)">
            <div class="num"><?= e(price_html((float) $r['total'], strtoupper((string) $r['currency']))) ?></div>
            <div class="lbl">CA encaissé · <?= (int) $r['n'] ?> cmd (<?= e(strtoupper((string) $r['currency'])) ?>)</div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="analytics-cols">
    <div class="glass analytics-card">
        <h2>🆕 Inscriptions — 14 jours</h2>
        <?= $bar_chart($signups14, 'linear-gradient(180deg,#ff45c8,#7a3cff)') ?>
    </div>
    <div class="glass analytics-card">
        <h2>🛒 Commandes payées — 14 jours</h2>
        <?= $bar_chart($orders14, 'linear-gradient(180deg,#39ffaa,#16c79a)') ?>
    </div>
</div>

<div class="analytics-cols">
    <div class="glass analytics-card">
        <h2>👥 Derniers inscrits</h2>
        <?php if ($recent_users): ?>
        <table class="data-table">
            <thead><tr><th>Membre</th><th>Rôle</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recent_users as $u): ?>
                <tr>
                    <td><?= e($u['display_name'] ?: $u['username']) ?></td>
                    <td class="muted"><?= e($u['role']) ?></td>
                    <td class="muted"><?= e(fmt_date($u['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="muted">Aucun membre pour le moment.</p><?php endif; ?>
    </div>

    <div class="glass analytics-card">
        <h2>🧾 Dernières commandes</h2>
        <?php if ($recent_orders): ?>
        <table class="data-table">
            <thead><tr><th>E-mail</th><th>Montant</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($recent_orders as $o): ?>
                <tr>
                    <td><?= e($o['email'] ?: '—') ?></td>
                    <td><?= e(price_html((float) $o['amount_total'], strtoupper((string) ($o['currency'] ?: 'EUR')))) ?></td>
                    <td>
                        <?= $o['status'] === 'paid' ? '🟢' : '⚪' ?> <?= e($o['status']) ?>
                        <?php if ((int) $o['delivered'] === 1): ?><span title="Livré par e-mail">✉️</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="muted">Aucune commande pour le moment.</p><?php endif; ?>
    </div>
</div>

<div class="analytics-cols">
    <div class="glass analytics-card">
        <h2>🏆 Membres les plus actifs</h2>
        <?php if ($top_members): ?>
        <table class="data-table">
            <thead><tr><th>Membre</th><th>Messages</th><th>Sujets</th></tr></thead>
            <tbody>
            <?php foreach ($top_members as $m): ?>
                <tr>
                    <td><?= e($m['display_name'] ?: $m['username']) ?></td>
                    <td><?= (int) $m['posts'] ?></td>
                    <td class="muted"><?= (int) $m['threads'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="muted">Pas encore d'activité communautaire.</p><?php endif; ?>
    </div>

    <div class="glass analytics-card">
        <h2>🔥 Sujets les plus suivis</h2>
        <?php if ($top_threads): ?>
        <table class="data-table">
            <thead><tr><th>Sujet</th><th>Réponses</th></tr></thead>
            <tbody>
            <?php foreach ($top_threads as $t): ?>
                <tr>
                    <td><a class="link-all" href="<?= e(url('pages/forum-thread.php?id=' . (int) $t['id'])) ?>"><?= e($t['title']) ?></a></td>
                    <td><?= (int) $t['replies'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="muted">Aucun sujet pour le moment.</p><?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
