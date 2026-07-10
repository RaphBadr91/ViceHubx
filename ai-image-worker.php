<?php
/**
 * ViceHub X — Worker d'IMAGES en ARRIÈRE-PLAN.
 * Génère (via Higgsfield) les illustrations MANQUANTES des articles sans jamais
 * bloquer le site. Lancé par le bouton « Générer toutes les images manquantes »
 * de l'admin (détaché), ou via cron.
 *   Autorisé : en CLI, ou admin connecté, ou ?key=AI_TICK_KEY.
 */
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/ai.php';

@set_time_limit(0);
@ignore_user_abort(true);
@ini_set('memory_limit', '512M');

$cli = (PHP_SAPI === 'cli');
if (!$cli) {
    $key      = (string) ($_GET['key'] ?? '');
    $expected = (string) (getenv('AI_TICK_KEY') ?: get_setting('ai_tick_key', ''));
    $adminOk  = is_logged_in() && is_admin();
    if (!$adminOk && ($expected === '' || !hash_equals($expected, $key))) {
        http_response_code(403);
        exit("forbidden\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// Aucune limite de temps en CLI ; enveloppe raisonnable si appelé en HTTP.
$budget = $cli ? 0 : max(20, min(280, (int) ($_GET['budget'] ?? 200)));
$done   = ai_generate_missing_images($budget);

if (!$cli) {
    echo "OK : {$done} image(s) générée(s). Restantes : " . ai_missing_image_count() . "\n";
}
