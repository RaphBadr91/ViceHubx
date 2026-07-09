<?php
/**
 * ViceHub X — Déclencheur de la PUBLICATION AUTOMATIQUE d'articles IA.
 * À appeler régulièrement (cron cPanel ou cron externe gratuit), ex. toutes les 30 min :
 *   https://vicehubx.com/ai-tick.php?key=VOTRE_CLE
 *
 * Il génère et publie les articles « dus » selon les réglages (intervalle, taille de lot)
 * définis dans le Dashboard admin → Articles IA. Sécurisé par clé (ou admin connecté).
 */
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/ai.php';

header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(0);
@ignore_user_abort(true);

$key      = (string) ($_GET['key'] ?? '');
$expected = (string) (getenv('AI_TICK_KEY') ?: get_setting('ai_tick_key', ''));
$adminOk  = is_logged_in() && is_admin();
if (!$adminOk && ($expected === '' || !hash_equals($expected, $key))) {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}

// Enveloppe de temps : on génère autant que possible sans dépasser le timeout serveur.
$budget = max(20, min(280, (int) ($_GET['budget'] ?? 110)));
$r = ai_auto_run($budget);
echo $r['message'] . "\n";
