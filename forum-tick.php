<?php
/**
 * ViceHub X — Déclencheur WEB du « battement de cœur » du forum.
 * Fait poster quelques membres IA « dus » (selon leur cadence) sur des sujets
 * actifs, et crée parfois un nouveau sujet. Aucune API, 100% gratuit.
 *
 * À appeler périodiquement (toutes les 1-2 h) via un cron cPanel ou un service
 * de cron externe gratuit (cron-job.org, UptimeRobot) :
 *   https://vicehubx.com/forum-tick.php?key=VOTRE_CLE
 *
 * Sécurité : n'agit que si ?key= correspond à FORUM_TICK_KEY (.env) — ou si un
 * admin est connecté (déclenchement manuel).
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

$max       = max(1, min(40, (int) ($_GET['max'] ?? 18)));
$newChance = (int) ($_GET['newchance'] ?? 5);
$pdo       = db();

try {
    $pdo->query('SELECT 1 FROM forum_bot_agents LIMIT 1');
} catch (Throwable $e) {
    echo "Table forum_bot_agents absente. Lance d'abord gen-forum-users.php.\n";
    exit;
}

/* 1) Membres « dus » selon leur cadence. */
$due = $pdo->prepare(
    'SELECT user_id, cadence_days, emojis FROM forum_bot_agents
     WHERE active = 1 AND next_post_at <= NOW() ORDER BY next_post_at ASC LIMIT ?'
);
$due->bindValue(1, $max, PDO::PARAM_INT);
$due->execute();
$agents = $due->fetchAll(PDO::FETCH_ASSOC);

if (!$agents) {
    echo "Aucun membre dû pour le moment. (Reviens plus tard.)\n";
    exit;
}

/* 2) Sujets candidats (récents + un peu d'ancien). */
$recent = $pdo->query(
    'SELECT t.id, t.title, (SELECT p.user_id FROM forum_posts p WHERE p.thread_id = t.id ORDER BY p.id DESC LIMIT 1) AS last_uid
     FROM forum_threads t WHERE t.locked = 0 ORDER BY t.last_post_at DESC LIMIT 25'
)->fetchAll(PDO::FETCH_ASSOC);
$older = $pdo->query(
    'SELECT t.id, t.title, (SELECT p.user_id FROM forum_posts p WHERE p.thread_id = t.id ORDER BY p.id DESC LIMIT 1) AS last_uid
     FROM forum_threads t WHERE t.locked = 0 ORDER BY RAND() LIMIT 25'
)->fetchAll(PDO::FETCH_ASSOC);
if (!$recent && !$older) { echo "Aucun sujet ouvert.\n"; exit; }

$insPost = $pdo->prepare('INSERT INTO forum_posts (thread_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())');
$bump    = $pdo->prepare('UPDATE forum_threads SET last_post_at = NOW() WHERE id = ?');
$resched = $pdo->prepare('UPDATE forum_bot_agents SET last_post_at = NOW(), next_post_at = ? WHERE user_id = ?');

$pickThread = static function (int $uid) use ($recent, $older) {
    $pool = (mt_rand(1, 100) <= 80 && $recent) ? $recent : ($older ?: $recent);
    for ($i = 0; $i < 5; $i++) {
        $t = $pool[array_rand($pool)];
        if ((int) $t['last_uid'] !== $uid) { return $t; }
    }
    return $pool[array_rand($pool)];
};

$posted = 0;
$nowTs  = time();
foreach ($agents as $a) {
    $uid = (int) $a['user_id'];
    $t   = $pickThread($uid);
    $body = fv_reply($t['title'], $a['emojis'] ?? '');
    try {
        $insPost->execute([(int) $t['id'], $uid, $body]);
        $bump->execute([(int) $t['id']]);
        $cad    = (float) $a['cadence_days'];
        $jitter = (int) ($cad * 86400 * (mt_rand(75, 125) / 100));
        $next   = date('Y-m-d H:i:s', $nowTs + max(3600, $jitter));
        $resched->execute([$next, $uid]);
        $posted++;
    } catch (Throwable $e) { /* on ignore ce membre et on continue */ }
}

/* 3) Parfois, un nouveau sujet. */
$newThread = 0;
if ($newChance > 0 && mt_rand(1, 100) <= $newChance) {
    $starters = fv_starters();
    shuffle($starters);
    $exists = $pdo->prepare('SELECT 1 FROM forum_threads WHERE title = ? LIMIT 1');
    foreach ($starters as $st) {
        $exists->execute([$st[0]]);
        if ($exists->fetchColumn()) { continue; }
        try {
            create_thread(6, (int) $agents[array_rand($agents)]['user_id'], $st[0], $st[1]);
            $newThread = 1;
        } catch (Throwable $e) { /* ignore */ }
        break;
    }
}

echo "OK : {$posted} réponse(s) postée(s)" . ($newThread ? " + 1 nouveau sujet" : '') . ".\n";
