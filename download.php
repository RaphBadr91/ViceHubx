<?php
/**
 * ViceHub X — Livraison sécurisée d'un produit numérique après paiement.
 * Accès autorisé uniquement si une commande PAYÉE (table orders) contient
 * le produit demandé.   Usage : /download.php?s=<stripe_session>&p=<product_id>
 */
require_once __DIR__ . '/config/config.php';
// Endpoint binaire : on streame le fichier directement (pas de temporisation mémoire).
while (ob_get_level() > 0) { ob_end_clean(); }

$session = trim((string) ($_GET['s'] ?? ''));
$pid = (int) ($_GET['p'] ?? 0);
if ($session === '' || $pid <= 0) {
    http_response_code(400);
    exit('Requête invalide.');
}

// La commande doit exister ET être payée
$stmt = db()->prepare("SELECT items FROM orders WHERE stripe_session = ? AND status = 'paid' LIMIT 1");
$stmt->execute([$session]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(403);
    exit('Accès refusé : paiement introuvable.');
}

// Le produit doit faire partie de la commande
$items = json_decode((string) $order['items'], true) ?: [];
$file = null;
$name = 'wallpaper';
foreach ($items as $it) {
    if ((int) ($it['id'] ?? 0) === $pid && !empty($it['digital_file'])) {
        $file = (string) $it['digital_file'];
        $name = $it['name'] ?? $name;
        break;
    }
}
if (!$file) {
    http_response_code(403);
    exit('Ce fichier ne fait pas partie de votre commande.');
}

// Amorçage : si le fichier propre n'est pas encore en local, on le récupère depuis le CDN
$wallName = pathinfo($file, PATHINFO_FILENAME);
if (!is_file(ROOT_PATH . '/' . ltrim($file, '/'))) {
    wallpaper_path($wallName);
}

// Chemin sûr, confiné à /storage
$path = realpath(ROOT_PATH . '/' . ltrim($file, '/'));
$base = realpath(ROOT_PATH . '/storage');
if ($path === false || $base === false || strpos($path, $base) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit('Fichier indisponible. Contactez-nous avec votre numéro de commande.');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
$dl = preg_replace('/[^a-z0-9._-]/i', '-', $name) . '.' . $ext;

header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $dl . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
