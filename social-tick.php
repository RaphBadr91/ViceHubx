<?php
/**
 * ViceHub X — Cron RÉSEAUX SOCIAUX.
 * Enfile les nouveaux articles publiés et les poste sur Facebook / Instagram.
 *   Autorisé : en CLI, ou admin connecté, ou ?key=AI_TICK_KEY.
 */
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/ai.php';       // ai_enabled() pour les légendes IA
require_once ROOT_PATH . '/includes/social.php';

@set_time_limit(0);
@ignore_user_abort(true);

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

$budget = $cli ? 0 : max(20, min(120, (int) ($_GET['budget'] ?? 90)));
$r = social_drain($budget);

if (!$cli) {
    echo "OK : {$r['posted']} publication(s) postée(s), {$r['failed']} échec(s).\n";
}
