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

/*
 * Menu admin organisé par RUBRIQUES (barre latérale sur ordinateur, tiroir sur mobile).
 * [fichier, libellé, icône, autres fichiers qui activent ce lien]
 */
$admin_groups = [
    'Pilotage' => [
        ['dashboard.php', 'Tableau de bord', 'home'],
        ['analytics.php', 'Analytics', 'chart'],
    ],
    'Contenu' => [
        ['articles.php', 'Articles', 'file', ['article-edit.php']],
        ['article-create.php', 'Nouvel article', 'plus'],
        ['ai-articles.php', 'Articles IA', 'spark'],
        ['veille.php', 'Veille', 'radar'],
        ['seo-cleanup.php', 'Nettoyage SEO', 'broom'],
    ],
    'Diffusion' => [
        ['social.php', 'Réseaux sociaux', 'share'],
        ['tiktok.php', 'TikTok', 'video'],
        ['newsletter.php', 'Newsletter', 'mail'],
    ],
    'Revenus' => [
        ['products.php', 'Boutique', 'bag', ['product-create.php', 'product-edit.php']],
        ['deals.php', 'Deals Amazon', 'tag'],
        ['orders.php', 'Commandes', 'receipt'],
    ],
    'Communauté' => [
        ['users.php', 'Membres', 'users'],
        ['fanarts.php', 'Fan-arts', 'image'],
        ['events.php', 'Événements', 'calendar'],
    ],
    'Système' => [
        ['settings.php', 'Réglages', 'gear'],
    ],
];
// Icônes au trait (style uniforme, pas d'emoji).
$adm_icons = [
    'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/>',
    'chart' => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M3 20h18"/>',
    'file' => '<path d="M14 3H6.5v18h11V6.5Z"/><path d="M14 3v3.5h3.5"/><path d="M9 12h6M9 16h6"/>',
    'plus' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
    'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><path d="m12 8 1.5 2.5L16 12l-2.5 1.5L12 16l-1.5-2.5L8 12l2.5-1.5Z"/>',
    'radar' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><path d="M12 12 18.5 6"/>',
    'broom' => '<path d="m14 4 6 6"/><path d="M12.5 7.5 4 16l4 4 8.5-8.5"/><path d="m8 12 4 4"/>',
    'share' => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6"/>',
    'video' => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>',
    'bag' => '<path d="M5 8h14l-1 12H6Z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>',
    'tag' => '<path d="M3 12V4h8l10 10-8 8Z"/><circle cx="7.5" cy="8.5" r="1.3"/>',
    'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6M9 12h6"/>',
    'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c.6-3.6 3-5.5 6-5.5s5.4 1.9 6 5.5"/><path d="M16 4.8a3 3 0 0 1 0 6.2M18 14.8c1.7.6 2.7 2.2 3 5.2"/>',
    'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.7"/><path d="m21 16-5-5-9 9"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 2.8v2.4M12 18.8v2.4M4.2 7.5l2.1 1.2M17.7 15.3l2.1 1.2M4.2 16.5l2.1-1.2M17.7 8.7l2.1-1.2"/><circle cx="12" cy="12" r="7"/>',
    'site' => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v6H4V6h6"/>',
    'logout' => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h10"/>',
];
$adm_svg = static fn(string $k): string => '<svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true">' . ($adm_icons[$k] ?? '') . '</svg>';
$adm_current = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$adm_current_label = 'Admin';
foreach ($admin_groups as $__items) {
    foreach ($__items as $__it) {
        if ($__it[0] === $adm_current || in_array($adm_current, $__it[3] ?? [], true)) { $adm_current_label = $__it[1]; }
    }
}
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
    <style>
        /* ---------- Admin : barre latérale (ordinateur) + tiroir (mobile) ---------- */
        body.adm { padding-top: 0; }
        .adm-layout { display: grid; grid-template-columns: 252px minmax(0, 1fr); min-height: 100vh; }
        .adm-side {
            position: sticky; top: 0; height: 100vh; overflow-y: auto; overscroll-behavior: contain;
            display: flex; flex-direction: column; gap: .2rem; padding: 1.1rem .8rem 1rem;
            background: rgba(14, 12, 26, .92); border-right: 1px solid rgba(255, 255, 255, .07);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); z-index: 60;
        }
        .adm-brand { display: block; padding: .3rem .7rem 1rem; font-size: 1.25rem; font-weight: 800; letter-spacing: -.01em; color: #fff; text-decoration: none; }
        .adm-brand small { display: block; font-size: .72rem; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: #8f88a8; margin-top: .15rem; }
        .adm-group { margin-top: .7rem; }
        .adm-group__title { padding: 0 .75rem .3rem; font-size: .68rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #7d7698; }
        .adm-link {
            display: flex; align-items: center; gap: .7rem; padding: .55rem .75rem; border-radius: 10px;
            color: #c9c3dc; text-decoration: none; font-size: .92rem; font-weight: 500; line-height: 1.2;
            transition: background .15s, color .15s;
        }
        .adm-link:hover { background: rgba(255, 255, 255, .06); color: #fff; }
        .adm-link.is-active { background: linear-gradient(90deg, rgba(255, 46, 136, .22), rgba(255, 46, 136, .06)); color: #fff; box-shadow: inset 3px 0 0 #ff2e88; }
        .adm-ico { width: 18px; height: 18px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
        .adm-side__foot { margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, .07); display: grid; gap: .2rem; }
        .adm-user { display: flex; align-items: center; gap: .6rem; padding: .5rem .75rem; font-size: .85rem; color: #a59fbd; }
        .adm-user b { color: #fff; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .adm-avatar { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; flex: none; background: linear-gradient(135deg, #ff2e88, #7b2ff7); color: #fff; font-weight: 700; font-size: .85rem; }
        .adm-main { min-width: 0; }
        .adm-main .admin-shell { max-width: 1180px; }
        .adm-top { display: none; }
        .adm-overlay { display: none; }

        @media (max-width: 1023px) {
            .adm-layout { display: block; }
            .adm-top {
                position: sticky; top: 0; z-index: 50; display: flex; align-items: center; gap: .75rem;
                padding: .65rem 1rem; background: rgba(14, 12, 26, .94); border-bottom: 1px solid rgba(255, 255, 255, .07);
                backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            }
            .adm-burger {
                width: 42px; height: 42px; flex: none; display: grid; place-items: center; border-radius: 10px;
                background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .1); color: #fff; cursor: pointer;
            }
            .adm-top__title { min-width: 0; display: grid; line-height: 1.15; }
            .adm-top__title small { font-size: .68rem; letter-spacing: .14em; text-transform: uppercase; color: #8f88a8; font-weight: 700; }
            .adm-top__title b { font-size: 1rem; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .adm-side {
                position: fixed; top: 0; left: 0; bottom: 0; width: min(300px, 86vw); height: auto;
                transform: translateX(-102%); transition: transform .22s ease; box-shadow: 0 0 40px rgba(0, 0, 0, .5); z-index: 80;
            }
            body.adm-open .adm-side { transform: none; }
            .adm-overlay { position: fixed; inset: 0; z-index: 70; background: rgba(5, 4, 12, .6); }
            body.adm-open .adm-overlay { display: block; }
            body.adm-open { overflow: hidden; }
            .adm-link { padding: .7rem .8rem; font-size: .96rem; }   /* cibles tactiles confortables */
        }
        @media (prefers-reduced-motion: reduce) { .adm-side { transition: none; } }
    </style>
</head>
<body class="adm">
<div class="adm-layout">
    <aside class="adm-side" id="adm-side" aria-label="Menu d'administration">
        <a class="adm-brand" href="<?= e(url('admin/dashboard.php')) ?>">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span><small>Administration</small></a>
        <?php foreach ($admin_groups as $__group => $__items): ?>
            <div class="adm-group">
                <div class="adm-group__title"><?= e($__group) ?></div>
                <?php foreach ($__items as $__it):
                    $__on = $__it[0] === $adm_current || in_array($adm_current, $__it[3] ?? [], true); ?>
                    <a class="adm-link<?= $__on ? ' is-active' : '' ?>" href="<?= e(url('admin/' . $__it[0])) ?>"<?= $__on ? ' aria-current="page"' : '' ?>><?= $adm_svg($__it[2]) ?><span><?= e($__it[1]) ?></span></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="adm-side__foot">
            <div class="adm-user"><span class="adm-avatar"><?= e(mb_strtoupper(mb_substr((string) ($admin_user['username'] ?? 'A'), 0, 1))) ?></span><b><?= e($admin_user['username'] ?? '') ?></b></div>
            <a class="adm-link" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener"><?= $adm_svg('site') ?><span>Voir le site</span></a>
            <a class="adm-link" href="<?= e(url('admin/logout.php')) ?>"><?= $adm_svg('logout') ?><span><?= e(t('admin_logout')) ?></span></a>
        </div>
    </aside>
    <div class="adm-overlay" data-adm-close></div>

    <div class="adm-main">
        <div class="adm-top">
            <button class="adm-burger" type="button" aria-controls="adm-side" aria-expanded="false" aria-label="Ouvrir le menu" data-adm-toggle>
                <svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <div class="adm-top__title"><small>ViceHub X · Admin</small><b><?= e($adm_current_label) ?></b></div>
        </div>
        <script>
        (function () {
            var b = document.body, btn = document.querySelector('[data-adm-toggle]');
            function set(open) { b.classList.toggle('adm-open', open); if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
            document.addEventListener('click', function (e) {
                if (e.target.closest('[data-adm-toggle]')) { set(!b.classList.contains('adm-open')); }
                else if (e.target.closest('[data-adm-close]') || (b.classList.contains('adm-open') && e.target.closest('.adm-link'))) { set(false); }
            });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
        })();
        </script>
<main class="admin-shell">
