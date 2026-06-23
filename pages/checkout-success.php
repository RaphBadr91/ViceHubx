<?php
require_once dirname(__DIR__) . '/config/config.php';

$session_id = trim((string) ($_GET['session_id'] ?? ''));
$paid = false;
$email = '';
$amount = null;
$cur = shop_currency();

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
                $items[] = ['name' => $l['name'], 'qty' => $l['qty'], 'price' => (float) $l['price']];
            }
            $stmt = db()->prepare(
                'INSERT INTO orders (stripe_session, email, amount_total, currency, status, items)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), email = VALUES(email), amount_total = VALUES(amount_total)'
            );
            $stmt->execute([$session_id, $email, $amount, $cur, 'paid', json_encode($items, JSON_UNESCAPED_UNICODE)]);
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
