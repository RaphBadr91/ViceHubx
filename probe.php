<?php
/**
 * ViceHub X — PROBE de dépannage (TEMPORAIRE, à supprimer).
 * Force l'affichage des erreurs DÈS la 1re ligne et charge chaque brique une par
 * une : la 1re qui casse s'affiche à l'écran (parse OU runtime). Accès direct :
 *   https://vicehubx.com/probe.php
 * N'expose aucun secret.
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

// Capture même les erreurs FATALES (parse/compile) des fichiers inclus.
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR], true)) {
        echo "\n\n*** FATAL ***\n" . $e['message'] . "\nFichier : " . $e['file'] . ':' . $e['line'] . "\n";
    }
});

echo "PHP " . PHP_VERSION . " — SAPI " . PHP_SAPI . "\n";
echo "Extensions clés : "
    . 'pdo_mysql=' . (extension_loaded('pdo_mysql') ? 'ok' : 'MANQUE') . ' '
    . 'mbstring=' . (extension_loaded('mbstring') ? 'ok' : 'MANQUE') . ' '
    . 'gd=' . (extension_loaded('gd') ? 'ok' : 'MANQUE') . ' '
    . 'iconv=' . (extension_loaded('iconv') ? 'ok' : 'MANQUE') . "\n\n";

$steps = [
    'config/config.php'   => __DIR__ . '/config/config.php',
    'includes/functions'  => null,   // déjà chargé par config
    'includes/ai.php'     => __DIR__ . '/includes/ai.php',
];

echo "1) require config/config.php ... \n";
require_once __DIR__ . '/config/config.php';
echo "   OK — config + functions chargés\n\n";

echo "2) db()->query('SELECT 1') ... \n";
db()->query('SELECT 1')->fetchColumn();
echo "   OK — base joignable\n\n";

echo "3) require includes/ai.php ... \n";
require_once __DIR__ . '/includes/ai.php';
echo "   OK — ai.php chargé\n\n";

echo "4) fonctions récentes : "
    . 'unique_slug=' . (function_exists('unique_slug') ? 'ok' : 'ABSENTE') . ' '
    . 'ai_translate_missing=' . (function_exists('ai_translate_missing') ? 'ok' : 'ABSENTE') . "\n\n";

echo "5) require includes/admin_header.php (sans rendu) ... \n";
// On simule juste l'inclusion des dépendances admin, pas le rendu HTML complet.
echo "   (sauté — nécessite une session admin)\n\n";

echo "=== TOUT EST OK CÔTÉ PHP/BASE ===\n";
echo "Si tu vois ce message, le coeur fonctionne : le souci est ailleurs (page précise ou .htaccess).\n";
