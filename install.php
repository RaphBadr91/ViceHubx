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

$done = false;
$error = null;
$run = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($run) {
    try {
        // Connexion SANS nom de base (pour pouvoir la créer)
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $sqlFile = ROOT_PATH . '/database/schema.sql';
        $sql = @file_get_contents($sqlFile);
        if ($sql === false) {
            throw new RuntimeException('Fichier introuvable : ' . $sqlFile);
        }

        // PDO::exec exécute toutes les instructions du script (driver mysql)
        $pdo->exec($sql);
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
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

        <?php if ($done): ?>
            <div class="alert alert--ok">
                ✓ Base <strong>vicehubx</strong> créée et remplie avec succès.
            </div>
            <p class="muted" style="font-size:.9rem">
                Cible : <code><?= e(DB_USER . '@' . DB_HOST . ':' . DB_PORT) ?></code>
            </p>
            <a class="btn btn--primary" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%">
                Ouvrir le site →
            </a>
            <p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">
                ⚠️ Sécurité : supprimez maintenant le fichier <code>install.php</code>.
            </p>
        <?php elseif ($error): ?>
            <div class="alert alert--err">✗ <?= e($error) ?></div>
            <p class="muted" style="font-size:.88rem">
                Vérifiez que MySQL tourne et les identifiants dans <code>config/config.php</code>
                (par défaut <code>root</code> sans mot de passe).
            </p>
            <form method="post"><button class="btn btn--ghost" style="width:100%;justify-content:center">Réessayer</button></form>
        <?php else: ?>
            <p class="muted">
                Cet outil crée la base <strong>vicehubx</strong> et importe le contenu de
                démonstration depuis <code>database/schema.sql</code>.
            </p>
            <p class="muted" style="font-size:.85rem">
                Cible : <code><?= e(DB_USER . '@' . DB_HOST . ':' . DB_PORT) ?></code> ·
                ⚠️ recrée la base à zéro.
            </p>
            <form method="post">
                <button class="btn btn--primary" type="submit" style="width:100%;justify-content:center">
                    Installer maintenant
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
