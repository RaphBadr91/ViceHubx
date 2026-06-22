<?php
/**
 * ViceHub X — En-tête de l'espace d'administration (protégé).
 */
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
$admin_user = current_user();
$admin_nav = [
    ['dashboard.php', t('admin_dashboard')],
    ['articles.php',  t('admin_articles')],
    ['article-create.php', t('admin_new')],
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
