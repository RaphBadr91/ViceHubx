<?php
/**
 * ViceHub X — Configuration globale
 * Média indépendant non officiel autour de GTA VI.
 */

declare(strict_types=1);

/* ------------------------------------------------------------------ */
/*  Chargement d'un fichier .env (hébergement mutualisé : O2Switch…)   */
/*  Sur un mutualisé on ne peut pas définir de variables système ;     */
/*  on lit donc un fichier .env (non versionné) à la racine du projet. */
/* ------------------------------------------------------------------ */
$__envFile = dirname(__DIR__) . '/.env';
if (is_file($__envFile)) {
    foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || $__line[0] === '#') {
            continue;
        }
        $__parts = explode('=', $__line, 2);
        $__k = trim($__parts[0]);
        $__v = trim($__parts[1] ?? '');
        // Retire des guillemets éventuels autour de la valeur
        if (strlen($__v) >= 2 && ($__v[0] === '"' || $__v[0] === "'") && $__v[strlen($__v) - 1] === $__v[0]) {
            $__v = substr($__v, 1, -1);
        }
        if ($__k !== '' && getenv($__k) === false) {
            putenv("$__k=$__v");
            $_ENV[$__k] = $__v;
            $_SERVER[$__k] = $__v;
        }
    }
    unset($__line, $__parts, $__k, $__v);
}
unset($__envFile);


/* ------------------------------------------------------------------ */
/*  Environnement                                                      */
/* ------------------------------------------------------------------ */
define('APP_NAME', 'ViceHub X');
define('APP_SLOGAN_EN', 'Enter The Next Generation Of Vice City.');
define('APP_SLOGAN_FR', 'Entrez dans la nouvelle génération de Vice City.');

// Date de sortie GTA VI (compte à rebours). Surchargée par le réglage "release_date".
define('RELEASE_DATE', '2026-11-19T00:00:00');

// Chemins
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', getenv('VICEHUB_BASE_URL') ?: '');           // vide => racine du serveur
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// Base de données (surchargeable via variables d'environnement)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'vicehubx');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Sécurité
define('CSRF_KEY', getenv('VICEHUB_CSRF_KEY') ?: 'change-me-in-production');
define('UPLOAD_MAX_BYTES', 3 * 1024 * 1024); // 3 Mo
define('UPLOAD_ALLOWED', ['image/jpeg', 'image/png', 'image/webp']);

/* ------------------------------------------------------------------ */
/*  Affichage des erreurs (dev) — passer à 0 en production             */
/* ------------------------------------------------------------------ */
$IS_DEV = (getenv('APP_ENV') ?: 'dev') !== 'prod';
error_reporting($IS_DEV ? E_ALL : 0);
ini_set('display_errors', $IS_DEV ? '1' : '0');

/* ------------------------------------------------------------------ */
/*  Session sécurisée                                                  */
/* ------------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('VICEHUBX_SID');
    session_start();
}

/* ------------------------------------------------------------------ */
/*  Dépendances                                                        */
/* ------------------------------------------------------------------ */
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';

/* ------------------------------------------------------------------ */
/*  Langue active + chargement des traductions                         */
/* ------------------------------------------------------------------ */
$GLOBALS['LANG_CODE'] = resolve_language();
$__langFile = ROOT_PATH . '/lang/' . $GLOBALS['LANG_CODE'] . '.php';
$GLOBALS['LANG'] = is_file($__langFile) ? require $__langFile : [];
// Repli anglais pour les clés non traduites dans les langues étendues
$GLOBALS['LANG_FALLBACK'] = $GLOBALS['LANG_CODE'] === 'en'
    ? $GLOBALS['LANG']
    : (is_file(ROOT_PATH . '/lang/en.php') ? require ROOT_PATH . '/lang/en.php' : []);
