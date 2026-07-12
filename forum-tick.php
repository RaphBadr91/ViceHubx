<?php
/**
 * ViceHub X — Déclencheur WEB du « battement de cœur » du forum.
 * À chaque appel, il fait (au plus) UNE interaction : un membre IA répond sur un
 * sujet, souvent en s'adressant au membre précédent par son nom. Un verrou de
 * rythme garantit une cadence HUMAINE : une interaction toutes les 2 à 12 h
 * (aléatoire), jamais en rafale → 100% crédible et bon pour le référencement.
 *
 * À appeler souvent (toutes les 15-30 min) via un cron ; le verrou interne
 * décide quand poster réellement :
 *   https://vicehubx.com/forum-tick.php?key=VOTRE_CLE
 *   ...&force=1  → force une interaction tout de suite (test admin).
 *
 * Sécurité : ?key= doit correspondre à FORUM_TICK_KEY (.env / réglage) — ou admin connecté.
 * Réglages : forum_gap_min_h (2) · forum_gap_max_h (12) · forum_new_chance (6).
 */
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/scripts/_forum_voices.php';

header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(0);
@ignore_user_abort(true);

$key      = (string) ($_GET['key'] ?? '');
$expected = (string) (getenv('FORUM_TICK_KEY') ?: get_setting('forum_tick_key', ''));
$adminOk  = is_logged_in() && is_admin();
if (!$adminOk && ($expected === '' || !hash_equals($expected, $key))) {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}

// L'admin connecté peut forcer une interaction immédiate (?force=1) pour tester.
$force = $adminOk && !empty($_GET['force']);
echo fv_heartbeat(['force' => $force]) . "\n";
