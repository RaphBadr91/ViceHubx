<?php
/**
 * ViceHub X — Création d'une session Stripe Checkout à partir du panier.
 * Paiement hébergé par Stripe (aucune donnée carte ne transite par le site).
 */
require_once __DIR__ . '/config/config.php';

// POST + CSRF uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect(with_lang(url('pages/cart.php')));
}

$lines = cart_lines();
if (!$lines) {
    redirect(with_lang(url('pages/cart.php')));
}
if (!stripe_enabled()) {
    $_SESSION['cart_flash'] = lang() === 'fr' ? 'Le paiement en ligne n’est pas encore configuré.' : 'Online checkout is not configured yet.';
    redirect(with_lang(url('pages/cart.php')));
}

// Base absolue pour les URL de retour + images
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$origin = BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$abs = static fn(string $p): string => str_starts_with($p, 'http') ? $p : $origin . '/' . ltrim($p, '/');

$cur = strtolower(active_currency());
$has_physical = false;
$line_items = [];
foreach ($lines as $l) {
    if (empty($l['digital_file'])) {
        $has_physical = true;
    }
    if (!empty($l['stripe_price_id'])) {
        $line_items[] = ['price' => $l['stripe_price_id'], 'quantity' => $l['qty']];
        continue;
    }
    $pd = ['currency' => $cur, 'unit_amount' => (int) round(((float) $l['price']) * 100), 'product_data' => ['name' => $l['name']]];
    if (!empty($l['image'])) {
        $pd['product_data']['images'] = [$abs($l['image'])];
    }
    $line_items[] = ['price_data' => $pd, 'quantity' => $l['qty']];
}

$params = [
    'mode'                       => 'payment',
    'line_items'                 => $line_items,
    'success_url'                => $abs('pages/checkout-success.php') . '?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'                 => $abs('pages/checkout-cancel.php'),
    'billing_address_collection' => 'auto',
];
// Adresse de livraison uniquement si un produit physique est présent (pas pour les wallpapers)
if ($has_physical) {
    $params['shipping_address_collection'] = ['allowed_countries' => ['FR', 'BE', 'CH', 'LU', 'CA', 'MC']];
}

try {
    $session = stripe_api('POST', 'checkout/sessions', $params);
    if (!empty($session['url'])) {
        header('Location: ' . $session['url'], true, 303);
        exit;
    }
    throw new RuntimeException('Réponse Stripe invalide.');
} catch (Throwable $e) {
    $_SESSION['cart_flash'] = (lang() === 'fr' ? 'Erreur de paiement : ' : 'Payment error: ') . $e->getMessage();
    redirect(with_lang(url('pages/cart.php')));
}
