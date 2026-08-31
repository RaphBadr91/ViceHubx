<?php
/**
 * ViceHub X — En-tête de l'espace d'administration (protégé).
 */
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
// Anti-cache : une page admin ne doit JAMAIS être servie depuis un cache
// (LiteSpeed/O2Switch ou navigateur) — un jeton CSRF périmé provoquerait
// « Jeton CSRF invalide » sur les formulaires (connexion, test, réglages…).
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}
$admin_user = current_user();
$admin_nav = [
    ['dashboard.php', t('admin_dashboard')],
    ['analytics.php', '📊 Analytics'],
    ['articles.php',  t('admin_articles')],
    ['article-create.php', t('admin_new')],
    ['ai-articles.php', '🤖 Articles IA'],
    ['veille.php',    '🔭 Veille'],
    ['social.php',    '📣 Réseaux'],
    ['newsletter.php', '📧 Newsletter'],
    ['tiktok.php',    '🎵 TikTok'],
    ['products.php',  'Boutique'],
    ['orders.php',    'Commandes'],
    ['fanarts.php',   'Fan-arts'],
    ['events.php',    'Événements'],
    ['users.php',     'Membres'],
    ['settings.php',  'Réglages'],
];
$ADMIN_TITLE = $ADMIN_TITLE ?? (APP_NAME . ' — Admin');
?>
<!DOCTYPE html>
<html lang="<?= e(lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($ADMIN_TITLE) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<header class="site-header glass">
    <div class="header-inner">
        <a class="logo" href="<?= e(url('admin/dashboard.php')) ?>">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span> · Admin</a>
        <nav class="site-nav">
            <?php foreach ($admin_nav as [$href, $label]): ?>
                <a href="<?= e(url('admin/' . $href)) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <span class="muted" style="font-size:.85rem">👤 <?= e($admin_user['username'] ?? '') ?></span>
            <a class="btn btn--ghost" href="<?= e(url('admin/logout.php')) ?>"><?= e(t('admin_logout')) ?></a>
        </div>
    </div>
</header>
<main class="admin-shell">
