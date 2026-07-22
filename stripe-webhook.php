<?php
/**
 * ViceHub X — Webhook Stripe.
 * Configurez l'endpoint dans le Dashboard Stripe :
 *   https://VOTRE-DOMAINE/stripe-webhook.php   (événement : checkout.session.completed)
 * Renseignez le « signing secret » (whsec_…) dans l'admin (Réglages).
 */
require_once __DIR__ . '/config/config.php';
header('Content-Type: application/json');

$payload = file_get_contents('php://input') ?: '';
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret  = stripe_webhook_secret();

if ($secret === '') {
    http_response_code(500);
    echo '{"error":"webhook secret not configured"}';
    exit;
}

// --- Vérification de la signature Stripe ---
$ts = null;
$v1 = null;
foreach (explode(',', $sig) as $part) {
    [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
    if ($k === 't')  { $ts = $v; }
    if ($k === 'v1') { $v1 = $v; }
}
if (!$ts || !$v1) {
    http_response_code(400);
    echo '{"error":"invalid signature header"}';
    exit;
}
$expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
if (!hash_equals($expected, $v1) || abs(time() - (int) $ts) > 300) {
    http_response_code(400);
    echo '{"error":"signature verification failed"}';
    exit;
}

// --- Traitement de l'événement ---
$event = json_decode($payload, true) ?: [];
if (($event['type'] ?? '') === 'checkout.session.completed') {
    $s = $event['data']['object'] ?? [];
    $sid = $s['id'] ?? '';
    if ($sid !== '') {
        $email  = $s['customer_details']['email'] ?? ($s['customer_email'] ?? '');
        $amount = isset($s['amount_total']) ? $s['amount_total'] / 100 : null;
        $cur    = strtoupper($s['currency'] ?? 'EUR');
        $status = ($s['payment_status'] ?? '') === 'paid' ? 'paid' : 'pending';
        // Reconstruit les lignes numériques depuis la métadonnée pour la livraison e-mail.
        $spec  = (string) ($s['metadata']['digital'] ?? '');
        $items = $spec !== '' ? digital_items_from_spec($spec) : [];
        try {
            $stmt = db()->prepare(
                'INSERT INTO orders (stripe_session, email, amount_total, currency, status, items)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), email = VALUES(email), amount_total = VALUES(amount_total)'
            );
            $stmt->execute([$sid, $email, $amount, $cur, $status, json_encode($items, JSON_UNESCAPED_UNICODE)]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo '{"error":"db"}';
            exit;
        }

        // Livraison automatique des wallpapers par e-mail (sans filigrane) une fois payé.
        if ($status === 'paid') {
            $oid = order_id_for_session($sid);
            if ($oid > 0) {
                try { deliver_order($oid); } catch (Throwable $e) { /* on ne bloque pas l'accusé Stripe */ }
            }
            // Impression à la demande : route les articles physiques vers Printful.
            // 100% dormant tant que la clé n'est pas renseignée + l'interrupteur activé.
            require_once __DIR__ . '/includes/printful.php';
            try { printful_fulfill_session($s); } catch (Throwable $e) { /* ne bloque jamais l'accusé Stripe */ }
        }
    }
}

http_response_code(200);
echo '{"received":true}';
