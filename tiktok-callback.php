<?php
/**
 * ViceHub X — Callback OAuth TikTok.
 * TikTok redirige ici après autorisation : on échange le « code » contre les
 * jetons, puis on renvoie vers l'admin. Réservé à un administrateur connecté
 * (même session navigateur que celle qui a lancé la connexion).
 *   Redirect URI à déclarer dans le portail TikTok : https://vicehubx.com/tiktok-callback.php
 */
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/ai.php';       // pour anthropic_complete / ai_enabled (légendes)
require_once ROOT_PATH . '/includes/social.php';   // social_base()
require_once ROOT_PATH . '/includes/tiktok.php';

// Accès réservé aux admins (empêche un tiers de forcer une connexion).
if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    exit('Accès réservé à l\'administration.');
}

$dest = url('admin/tiktok.php');

// Erreur renvoyée par TikTok (refus utilisateur, etc.).
if (!empty($_GET['error'])) {
    $_SESSION['tiktok_flash'] = ['err', 'TikTok a refusé la connexion : ' . htmlspecialchars((string) ($_GET['error_description'] ?? $_GET['error']))];
    redirect($dest);
}

$code  = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');
$expected = (string) ($_SESSION['tiktok_oauth_state'] ?? '');
unset($_SESSION['tiktok_oauth_state']);

if ($code === '' || $state === '' || $expected === '' || !hash_equals($expected, $state)) {
    $_SESSION['tiktok_flash'] = ['err', 'Connexion TikTok invalide (state/CSRF). Relance la connexion depuis l\'admin.'];
    redirect($dest);
}

$r = tiktok_exchange_code($code);
$_SESSION['tiktok_flash'] = [$r['ok'] ? 'ok' : 'err', $r['msg']];
redirect($dest);
