<?php
/**
 * ViceHub X — Installateur web (création + import de la base).
 *
 * À utiliser une seule fois si l'import en ligne de commande pose problème :
 *   ouvrez http://localhost:8000/install.php
 *
 * ⚠️ Supprimez ce fichier après usage (il recrée la base à zéro).
 */
require_once __DIR__ . '/config/config.php';

$done   = false;   // schéma importé
$seeded = null;    // sortie du remplissage des données
$error  = null;
$step   = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['step'] ?? 'install') : '';

// Étape 1 — import du schéma (création des tables + contenu de base).
if ($step === 'install') {
    try {
        // Hébergement mutualisé (O2Switch…) : la base est déjà créée via cPanel
        // sous un nom préfixé (ex. compte_vicehubx) et l'utilisateur n'a PAS le
        // droit CREATE DATABASE. On se connecte donc directement à DB_NAME (.env).
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql = @file_get_contents(ROOT_PATH . '/database/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('Fichier introuvable : database/schema.sql');
        }
        // La base existe déjà (autre nom) : on retire CREATE DATABASE / USE.
        $sql = preg_replace('/CREATE\s+DATABASE\b[^;]*;/i', '', $sql, 1);
        $sql = preg_replace('/\bUSE\s+`?\w+`?\s*;/i', '', $sql, 1);
        $pdo->exec($sql);
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Étape 2 — remplissage : 1000 membres + packs + 60 articles (sans ligne de commande).
if ($step === 'seed') {
    try {
        @set_time_limit(0);
        ob_start();
        require __DIR__ . '/scripts/gen-forum-users.php';  // 1000 membres + rythmes + sujets
        require __DIR__ . '/scripts/gen-bundles.php';       // 7 packs de fonds d'écran
        require __DIR__ . '/scripts/gen-news-research.php';  // 60 articles d'actu
        $seeded = ob_get_clean();
    } catch (Throwable $e) {
        if (ob_get_level() > 0) { ob_end_clean(); }
        $error = 'Remplissage : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>ViceHub X — Installation</title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<div class="admin-login-wrap">
    <div class="admin-card glass" style="width:min(560px,94vw)">
        <a class="logo" href="<?= e(url('index.php')) ?>" style="display:block;text-align:center;margin-bottom:1rem">
            Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span>
        </a>
        <h1 style="text-align:center;font-size:1.25rem">Installation de la base</h1>

        <?php if ($seeded !== null): ?>
            <div class="alert alert--ok">✓ Données ajoutées : <strong>1000 membres</strong>, packs &amp; articles.</div>
            <pre style="max-height:220px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.72rem;line-height:1.5"><?= e($seeded) ?></pre>
            <a class="btn btn--primary" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%">Ouvrir le site →</a>
            <p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">
                ⚠️ Sécurité : <strong>supprime maintenant le fichier <code>install.php</code></strong>.
            </p>

        <?php elseif ($done): ?>
            <div class="alert alert--ok">✓ Base <strong>vicehubx</strong> créée (tables + contenu de base).</div>
            <p class="muted" style="font-size:.9rem">Cible : <code><?= e(DB_USER . '@' . DB_HOST . ':' . DB_PORT) ?></code></p>
            <p class="muted">Dernière étape (recommandée) : remplir le site pour qu'il soit vivant dès l'ouverture — <strong>1000 membres de forum</strong> qui discutent, <strong>7 packs</strong> de fonds d'écran et <strong>60 articles</strong> d'actu.</p>
            <form method="post" style="margin-top:.6rem">
                <input type="hidden" name="step" value="seed">
                <button class="btn btn--primary" type="submit" style="width:100%;justify-content:center">🌴 Remplir les données (forum, packs, articles)</button>
            </form>
            <a class="btn btn--ghost" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%;margin-top:.5rem">Passer (site sans données de démo)</a>

        <?php elseif ($error): ?>
            <div class="alert alert--err">✗ <?= e($error) ?></div>
            <p class="muted" style="font-size:.88rem">
                Vérifie que MySQL tourne et les identifiants dans le <code>.env</code> /
                <code>config/config.php</code>.
            </p>
            <form method="post"><input type="hidden" name="step" value="install"><button class="btn btn--ghost" style="width:100%;justify-content:center">Réessayer</button></form>

        <?php else: ?>
            <p class="muted">
                Cet outil crée la base <strong>vicehubx</strong> et importe le schéma +
                le contenu de base depuis <code>database/schema.sql</code>.
            </p>
            <p class="muted" style="font-size:.85rem">
                Cible : <code><?= e(DB_USER . '@' . DB_HOST . ':' . DB_PORT) ?></code> ·
                ⚠️ recrée la base à zéro.
            </p>
            <form method="post">
                <input type="hidden" name="step" value="install">
                <button class="btn btn--primary" type="submit" style="width:100%;justify-content:center">
                    Installer maintenant
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
