<?php
/**
 * ViceHub X — Worker de génération d'articles IA EN ARRIÈRE-PLAN.
 * Lancé automatiquement (détaché) quand tu cliques « Générer 5/10/15/20 » dans l'admin,
 * il vide la file d'attente sans jamais bloquer le site. Peut aussi tourner via cron.
 *   Autorisé : en CLI (lancement interne), ou admin connecté, ou ?key=AI_TICK_KEY.
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

// Draine toute la file (aucune limite de temps en CLI ; enveloppe si appelé en HTTP).
$budget = $cli ? 0 : max(20, min(280, (int) ($_GET['budget'] ?? 200)));
$done   = ai_drain_queue($budget);

if (!$cli) {
    echo "OK : {$done} article(s) généré(s). Reste en file : " . (int) get_setting('ai_gen_queue', '0') . "\n";
}
