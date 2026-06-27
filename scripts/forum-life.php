<?php
/**
 * ViceHub X — Battement de cœur du forum (sans API, gratuit & scalable).
 *
 * À chaque passage (cron), sélectionne les membres « dus » selon leur rythme
 * (réguliers / 4j / 7j / 10j) et leur fait poster une réponse naturelle et
 * contextuelle sur un sujet actif, puis reprogramme leur prochaine prise de
 * parole. Donne une vie continue et crédible au forum, 24h/24.
 *
 * Usage :
 *   php scripts/forum-life.php                 # jusqu’à 25 réponses dues
 *   php scripts/forum-life.php --max=15
 *   php scripts/forum-life.php --max=40 --newchance=8   # 8% de chance d’un nouveau sujet
 *
 * CRON conseillé (toutes les 2h, flux vivant mais maîtrisé) :
 *   0 *(/2) * * *  cd /chemin/ViceHubx && php scripts/forum-life.php --max=18 >/dev/null 2>&1
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/_forum_voices.php';

$opts      = getopt('', ['max::', 'newchance::']);
$max       = max(1, (int) ($opts['max'] ?? 25));
$newChance = (int) ($opts['newchance'] ?? 4);

$pdo = db();

// Garde-fou : la table doit exister (créée par gen-forum-users.php).
try {
    $pdo->query('SELECT 1 FROM forum_bot_agents LIMIT 1');
} catch (Throwable $e) {
    fwrite(STDERR, "✗ Table forum_bot_agents absente. Lance d’abord : php scripts/gen-forum-users.php\n");
    exit(1);
}

/* 1) Membres « dus ». ----------------------------------------------------- */
$due = $pdo->prepare(
    'SELECT user_id, cadence_days, emojis FROM forum_bot_agents
     WHERE active = 1 AND next_post_at <= NOW()
     ORDER BY next_post_at ASC LIMIT ?'
);
$due->bindValue(1, $max, PDO::PARAM_INT);
$due->execute();
$agents = $due->fetchAll(PDO::FETCH_ASSOC);

if (!$agents) {
    echo "Aucun membre dû pour le moment.\n";
    exit(0);
}

/* 2) Sujets candidats (récents en priorité, + un peu d’ancien). ----------- */
$recent = $pdo->query(
    'SELECT t.id, t.title, (SELECT p.user_id FROM forum_posts p WHERE p.thread_id = t.id ORDER BY p.id DESC LIMIT 1) AS last_uid
     FROM forum_threads t WHERE t.locked = 0 ORDER BY t.last_post_at DESC LIMIT 25'
)->fetchAll(PDO::FETCH_ASSOC);
$older = $pdo->query(
    'SELECT t.id, t.title, (SELECT p.user_id FROM forum_posts p WHERE p.thread_id = t.id ORDER BY p.id DESC LIMIT 1) AS last_uid
     FROM forum_threads t WHERE t.locked = 0 ORDER BY RAND() LIMIT 25'
)->fetchAll(PDO::FETCH_ASSOC);

if (!$recent && !$older) {
    fwrite(STDERR, "Aucun sujet ouvert où poster.\n");
    exit(0);
}

$insPost  = $pdo->prepare('INSERT INTO forum_posts (thread_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())');
$bump     = $pdo->prepare('UPDATE forum_threads SET last_post_at = NOW() WHERE id = ?');
$resched  = $pdo->prepare('UPDATE forum_bot_agents SET last_post_at = NOW(), next_post_at = ? WHERE user_id = ?');

$pickThread = static function (int $uid) use ($recent, $older) {
    // 80% un sujet récent, 20% un sujet plus ancien (pour faire vivre partout).
    $pool = (mt_rand(1, 100) <= 80 && $recent) ? $recent : ($older ?: $recent);
    // Évite de répondre juste après soi-même.
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
        // Reprogrammation : cadence ± 25% de jitter.
        $cad = (float) $a['cadence_days'];
        $jitter = (int) ($cad * 86400 * (mt_rand(75, 125) / 100));
        $next = date('Y-m-d H:i:s', $nowTs + max(3600, $jitter));
        $resched->execute([$next, $uid]);
        $posted++;
    } catch (Throwable $e) {
        fwrite(STDERR, 'post: ' . $e->getMessage() . "\n");
    }
}

/* 3) (Optionnel) un nouveau sujet de temps en temps. ---------------------- */
$newThread = 0;
if ($newChance > 0 && mt_rand(1, 100) <= $newChance) {
    $starters = fv_starters();
    shuffle($starters);
    $exists = $pdo->prepare('SELECT 1 FROM forum_threads WHERE title = ? LIMIT 1');
    foreach ($starters as $st) {
        $exists->execute([$st[0]]);
        if ($exists->fetchColumn()) { continue; }
        $author = (int) $agents[array_rand($agents)]['user_id'];
        try {
            $tid = create_thread(6, $author, $st[0], $st[1]);
            echo "+ nouveau sujet #{$tid} : « {$st[0]} »\n";
            $newThread = 1;
        } catch (Throwable $e) { fwrite(STDERR, 'thread: ' . $e->getMessage() . "\n"); }
        break;
    }
}

echo "✓ {$posted} réponse(s) postée(s)" . ($newThread ? " + 1 nouveau sujet" : '') . ".\n";
