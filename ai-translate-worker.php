<?php
/**
 * ViceHub X — Worker de TRADUCTION EN ARRIÈRE-PLAN.
 * Traduit en ANGLAIS (URL/slug anglais, même image & catégorie) tous les articles
 * FR déjà créés qui n'ont pas encore de version EN — sans jamais bloquer le site.
 * Lancé (détaché) par le bouton « Traduire tous les articles en anglais » de
 * l'admin, ou via cron.
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
$done   = ai_translate_missing($budget);

if (!$cli) {
    echo "OK : {$done} article(s) traduit(s) en anglais. Restants : " . ai_untranslated_count() . "\n";
}
