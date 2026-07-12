<?php
/**
 * ViceHub X — Battement de cœur du forum (sans API, gratuit & scalable).
 *
 * Fait vivre le forum avec un RYTHME HUMAIN : une interaction toutes les 2 à 12 h
 * (aléatoire), un membre IA répondant souvent au précédent par son nom. Un verrou
 * interne (réglage forum_next_at) évite toute rafale → crédible et bon pour le SEO.
 *
 * Usage :
 *   php scripts/forum-life.php            # respecte le rythme (verrou 2-12 h)
 *   php scripts/forum-life.php --force    # force une interaction maintenant
 *
 * CRON conseillé (toutes les 15-30 min ; le verrou décide quand poster) :
 *   *(/20) * * * *  cd /chemin/ViceHubx && php scripts/forum-life.php >/dev/null 2>&1
 *
 * Réglages : forum_gap_min_h (2) · forum_gap_max_h (12) · forum_new_chance (6).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/_forum_voices.php';

$opts  = getopt('', ['force']);
$force = isset($opts['force']);

echo fv_heartbeat(['force' => $force]) . "\n";
