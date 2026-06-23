<?php
$ADMIN_TITLE = 'ViceHub X — Commandes';
require __DIR__ . '/../includes/admin_header.php';

$orders = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 200')->fetchAll();
$revenue = db()->query("SELECT COALESCE(SUM(amount_total),0) FROM orders WHERE status = 'paid'")->fetchColumn();
?>
<div class="admin-bar">
    <h1>Commandes</h1>
    <a class="btn btn--ghost" href="<?= e(url('admin/settings.php')) ?>">⚙️ Réglages Stripe</a>
</div>

<?php if (!stripe_enabled()): ?>
    <div class="alert alert--err">⚪ Stripe n’est pas configuré. Renseignez vos clés dans <a href="<?= e(url('admin/settings.php')) ?>">Réglages</a> pour activer la vente directe.</div>
<?php endif; ?>

<p class="muted" style="font-size:.95rem">Chiffre d’affaires (commandes payées) : <strong style="color:var(--ink);font-size:1.15rem"><?= e(price_html((float) $revenue, shop_currency())) ?></strong></p>

<div class="glass" style="border-radius:18px;padding:1rem 1.2rem;overflow-x:auto">
    <table class="data-table">
        <thead><tr><th>#</th><th>Date</th><th>E-mail</th><th>Montant</th><th>Statut</th><th>Session Stripe</th></tr></thead>
        <tbody>
        <?php if (!$orders): ?>
            <tr><td colspan="6" class="muted">Aucune commande pour le moment.</td></tr>
        <?php else: foreach ($orders as $o): ?>
            <tr>
                <td><?= (int) $o['id'] ?></td>
                <td class="muted" style="white-space:nowrap"><?= e(substr((string) $o['created_at'], 0, 16)) ?></td>
                <td><?= e($o['email'] ?: '—') ?></td>
                <td><?= e(price_html((float) $o['amount_total'], $o['currency'] ?: 'EUR')) ?></td>
                <td><?= $o['status'] === 'paid' ? '🟢 payée' : '⏳ ' . e($o['status']) ?></td>
                <td class="muted" style="font-size:.78rem"><?= e($o['stripe_session'] ?: '—') ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
