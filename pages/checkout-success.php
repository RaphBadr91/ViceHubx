<?php
require_once dirname(__DIR__) . '/config/config.php';

$session_id = trim((string) ($_GET['session_id'] ?? ''));
$paid = false;
$email = '';
$amount = null;
$cur = active_currency();

// On capture le panier AVANT de le vider (pour l'enregistrement de la commande)
$lines = cart_lines();

if ($session_id !== '' && stripe_enabled()) {
    try {
        $session = stripe_api('GET', 'checkout/sessions/' . urlencode($session_id));
        $paid   = ($session['payment_status'] ?? '') === 'paid';
        $email  = $session['customer_details']['email'] ?? '';
        $amount = isset($session['amount_total']) ? $session['amount_total'] / 100 : null;
        $cur    = strtoupper($session['currency'] ?? $cur);

        if ($paid) {
            // Filet de sécurité si le webhook n'est pas (encore) configuré : on enregistre la commande.
            $items = [];
            foreach ($lines as $l) {
                $items[] = [
                    'id'           => (int) $l['id'],
                    'name'         => $l['name'],
                    'qty'          => $l['qty'],
                    'price'        => (float) $l['price'],
                    'digital_file' => $l['digital_file'] ?? null,
                ];
            }
            $stmt = db()->prepare(
                'INSERT INTO orders (stripe_session, email, amount_total, currency, status, items)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), email = VALUES(email), amount_total = VALUES(amount_total), items = VALUES(items)'
            );
            $stmt->execute([$session_id, $email, $amount, $cur, 'paid', json_encode($items, JSON_UNESCAPED_UNICODE)]);

            // Livraison automatique par e-mail des fichiers numériques (sans filigrane).
            $oid = order_id_for_session($session_id);
            if ($oid > 0) {
                try { deliver_order($oid); } catch (Throwable $e) { /* silencieux côté visiteur */ }
            }
        }
    } catch (Throwable $e) {
        // On reste silencieux côté visiteur ; l'achat est confirmé par Stripe de toute façon.
    }
}

// On vide le panier après une commande aboutie
if ($paid) {
    cart_clear();
}

$SEO_TITLE = (lang() === 'fr' ? 'Merci pour votre commande' : 'Thank you for your order') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr' ? 'Confirmation de commande ViceHub X.' : 'ViceHub X order confirmation.';
$ROBOTS    = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="text-align:center;max-width:640px">
    <?php if ($paid): ?>
        <div style="font-size:3.4rem">✅</div>
        <h1><?= lang() === 'fr' ? 'Merci pour votre commande !' : 'Thank you for your order!' ?></h1>
        <p class="muted">
            <?= lang() === 'fr'
                ? 'Votre paiement a bien été reçu. Un reçu vous a été envoyé par e-mail.'
                : 'Your payment was received. A receipt has been emailed to you.' ?>
        </p>
        <?php if ($amount !== null): ?>
            <p style="font-size:1.3rem;font-weight:800"><?= price_html($amount, $cur) ?></p>
        <?php endif; ?>
        <?php if ($email): ?><p class="muted" style="font-size:.9rem"><?= e($email) ?></p><?php endif; ?>

        <?php
        $downloads = array_filter($lines, fn($l) => !empty($l['digital_file']));
        if ($downloads && $session_id !== ''): ?>
            <div class="glass" style="margin:1.6rem auto 0;padding:1.2rem;border-radius:14px;max-width:460px;text-align:left">
                <h2 style="font-size:1.05rem;margin:0 0 .8rem">⬇️ <?= lang() === 'fr' ? 'Vos téléchargements' : 'Your downloads' ?></h2>
                <?php foreach ($downloads as $l): ?>
                    <a class="btn btn--primary" style="width:100%;justify-content:center;margin-bottom:.5rem"
                       href="<?= e(url('download.php?s=' . urlencode($session_id) . '&p=' . (int) $l['id'])) ?>">
                        <?= e($l['name']) ?> ↓
                    </a>
                <?php endforeach; ?>
                <p class="muted" style="font-size:.78rem;margin:.5rem 0 0"><?= lang() === 'fr'
                    ? 'Conservez cette page : les liens restent valides. Un reçu vous est aussi envoyé par e-mail.'
                    : 'Keep this page: links stay valid. A receipt is also emailed to you.' ?></p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="font-size:3.4rem">⏳</div>
        <h1><?= lang() === 'fr' ? 'Paiement en cours de validation' : 'Payment being confirmed' ?></h1>
        <p class="muted"><?= lang() === 'fr' ? 'Si le montant a été débité, votre commande est bien prise en compte.' : 'If you were charged, your order has been registered.' ?></p>
    <?php endif; ?>
    <div style="margin-top:1.6rem">
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= lang() === 'fr' ? 'Retour à la boutique' : 'Back to shop' ?> →</a>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
