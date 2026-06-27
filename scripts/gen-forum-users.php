<?php
/**
 * ViceHub X — Génère ~1000 membres « vivants » pour le forum.
 *
 * Crée des comptes membres (rôle 'member') + une table forum_bot_agents qui
 * pilote leur RYTHME de réponse : réguliers, chaque semaine, tous les 4j, tous
 * les 10j. Backfill quelques messages historiques sur les sujets existants et
 * crée de nouveaux sujets, pour que le forum soit vivant dès le premier jour.
 * Le « battement de cœur » (scripts/forum-life.php) prend ensuite le relais.
 *
 * Usage :
 *   php scripts/gen-forum-users.php                 # vise 1000 membres + backfill + 2 sujets
 *   php scripts/gen-forum-users.php --users=1000 --backfill=1 --threads=2
 *   php scripts/gen-forum-users.php --users=300 --backfill=0 --threads=0
 *
 * Idempotent : complète jusqu’à la cible sans doublonner ; ne re-remplit pas
 * les sujets déjà fournis ; ne recrée pas un sujet d’ouverture déjà présent.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/_forum_voices.php';

$opts     = getopt('', ['users::', 'backfill::', 'threads::']) ?: []; // ?: [] => robuste hors CLI (installateur web)
$target   = (int) ($opts['users'] ?? 1000);
$doBack   = (int) ($opts['backfill'] ?? 1);
$nThreads = (int) ($opts['threads'] ?? 2);

$pdo = db();

/* 1) Table de pilotage des « agents » (rythmes). -------------------------- */
$pdo->exec("CREATE TABLE IF NOT EXISTS forum_bot_agents (
    user_id      INT PRIMARY KEY,
    cadence_days DECIMAL(4,1) NOT NULL DEFAULT 7.0,
    tier         VARCHAR(16) NOT NULL DEFAULT 'hebdo',
    emojis       VARCHAR(24) NOT NULL DEFAULT '',
    fav          VARCHAR(12) NOT NULL DEFAULT 'GTA6',
    bio          VARCHAR(300) NOT NULL DEFAULT '',
    active       TINYINT(1) NOT NULL DEFAULT 1,
    last_post_at DATETIME NULL,
    next_post_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_due (active, next_post_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* 2) Répartition des rythmes (le total fait 100). ------------------------- */
// [tier, cadence_jours, poids %]
$tiers = [
    ['regulier', 1.5, 15],   // réguliers : postent souvent
    ['4j',       4.0, 30],   // tous les ~4 jours
    ['hebdo',    7.0, 35],   // chaque semaine
    ['10j',      10.0, 20],  // tous les ~10 jours
];
$tierBag = [];
foreach ($tiers as $t) { for ($i = 0; $i < $t[2]; $i++) { $tierBag[] = $t; } }

/* 3) Prépare les briques de génération. ----------------------------------- */
$archetypes = fv_archetypes();
$roots      = fv_roots();
$suffixes   = fv_suffixes();
$firstNames = fv_first_names();

// Pseudos déjà pris.
$taken = [];
foreach ($pdo->query('SELECT username FROM users')->fetchAll(PDO::FETCH_COLUMN) as $u) {
    $taken[mb_strtolower($u)] = true;
}
$existingAgents = (int) $pdo->query('SELECT COUNT(*) FROM forum_bot_agents')->fetchColumn();
$toCreate = max(0, $target - $existingAgents);

$pwHash = password_hash('!bot-' . bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
$nowTs  = time();

$genUsername = static function () use ($roots, $suffixes, &$taken): ?string {
    for ($try = 0; $try < 40; $try++) {
        $r = $roots[array_rand($roots)];
        $mode = mt_rand(1, 3);
        if ($mode === 1) {
            $u = $r . $roots[array_rand($roots)];
        } elseif ($mode === 2) {
            $u = $r . $suffixes[array_rand($suffixes)];
        } else {
            $u = $r . mt_rand(2, 999);
        }
        if (mt_rand(1, 100) <= 35) { $u .= mt_rand(2, 99); }
        $u = mb_substr($u, 0, 60);
        $k = mb_strtolower($u);
        if ($k !== '' && empty($taken[$k])) { $taken[$k] = true; return $u; }
    }
    return null;
};

/* 4) Insertion des membres + agents. -------------------------------------- */
$insUser  = $pdo->prepare('INSERT IGNORE INTO users (username, display_name, password_hash, role, created_at) VALUES (?, ?, ?, "member", ?)');
$insAgent = $pdo->prepare('INSERT INTO forum_bot_agents (user_id, cadence_days, tier, emojis, fav, bio, next_post_at) VALUES (?, ?, ?, ?, ?, ?, ?)');

$created = 0;
$pdo->beginTransaction();
for ($i = 0; $i < $toCreate; $i++) {
    $username = $genUsername();
    if ($username === null) { continue; }
    $arch = $archetypes[array_rand($archetypes)];
    [$emojis, $bio, $fav] = $arch;

    // display_name : 55% « Prénom + chiffre/mot », sinon le pseudo tel quel.
    if (mt_rand(1, 100) <= 55) {
        $display = $firstNames[array_rand($firstNames)];
        if (mt_rand(1, 100) <= 60) { $display .= ' ' . ucfirst($roots[array_rand($roots)]); }
    } else {
        $display = $username;
    }
    $display = mb_substr($display, 0, 80);

    // Ancienneté : inscrit il y a 1 à 430 jours.
    $createdAt = date('Y-m-d H:i:s', $nowTs - mt_rand(1, 430) * 86400 - mt_rand(0, 86399));
    $insUser->execute([$username, $display, $pwHash, $createdAt]);
    $uid = (int) $pdo->lastInsertId();
    if ($uid <= 0) { continue; } // collision rare → ignoré

    $tier = $tierBag[array_rand($tierBag)];
    // Premier passage étalé sur une fenêtre de cadence (pas tous en même temps).
    $next = date('Y-m-d H:i:s', $nowTs + (int) (mt_rand(0, (int) ($tier[1] * 86400))));
    $insAgent->execute([$uid, $tier[1], $tier[0], $emojis, $fav, $bio, $next]);
    $created++;
}
$pdo->commit();
echo "✓ Membres créés : {$created} (cible {$target}, déjà présents {$existingAgents}).\n";

/* 5) Liste des agents pour le backfill / nouveaux sujets. ----------------- */
$agents = $pdo->query('SELECT a.user_id, a.emojis FROM forum_bot_agents a')->fetchAll(PDO::FETCH_ASSOC);
$agentIds = array_column($agents, 'user_id');
$emojiOf  = [];
foreach ($agents as $a) { $emojiOf[(int) $a['user_id']] = $a['emojis']; }

// Insertion d’un post backdaté (sans notification, pour éviter le bruit).
$insPost = $pdo->prepare('INSERT INTO forum_posts (thread_id, user_id, body, created_at) VALUES (?, ?, ?, ?)');
$bumpThread = $pdo->prepare('UPDATE forum_threads SET last_post_at = (SELECT MAX(created_at) FROM forum_posts WHERE thread_id = ?) WHERE id = ?');

/* 6) Backfill : densifie les sujets existants peu fournis. ----------------- */
$backfilled = 0;
if ($doBack && $agentIds) {
    $threads = $pdo->query('SELECT id, title, created_at, (SELECT COUNT(*) FROM forum_posts WHERE thread_id = forum_threads.id) AS n FROM forum_threads')->fetchAll(PDO::FETCH_ASSOC);
    $pdo->beginTransaction();
    foreach ($threads as $th) {
        if ((int) $th['n'] >= 8) { continue; } // déjà fourni → on passe
        $add = mt_rand(6, 20);
        $startTs = strtotime($th['created_at']) ?: ($nowTs - 30 * 86400);
        $hi = $nowTs - 600;
        $lo = min($startTs + 60, $hi - 60); // garantit lo < hi même pour un sujet récent
        for ($k = 0; $k < $add; $k++) {
            $uid = $agentIds[array_rand($agentIds)];
            $body = fv_reply($th['title'], $emojiOf[$uid] ?? '');
            $ts = mt_rand($lo, $hi);
            $insPost->execute([(int) $th['id'], $uid, $body, date('Y-m-d H:i:s', $ts)]);
            $backfilled++;
        }
        $bumpThread->execute([(int) $th['id'], (int) $th['id']]);
    }
    $pdo->commit();
}
echo "✓ Messages historiques ajoutés : {$backfilled}.\n";

/* 7) Nouveaux sujets d’ouverture (les « Nouveaux Sujets »). ---------------- */
$createdThreads = 0;
if ($nThreads > 0 && $agentIds) {
    $starters = fv_starters();
    shuffle($starters);
    $gtaCat = 6; // GTA VI
    $exists = $pdo->prepare('SELECT 1 FROM forum_threads WHERE title = ? LIMIT 1');
    foreach ($starters as $st) {
        if ($createdThreads >= $nThreads) { break; }
        $exists->execute([$st[0]]);
        if ($exists->fetchColumn()) { continue; } // déjà créé → on évite le doublon
        $author = $agentIds[array_rand($agentIds)];
        $tid = create_thread($gtaCat, $author, $st[0], $st[1]);
        // Quelques réponses backdatées sur les derniers jours.
        $replies = mt_rand(8, 24);
        $startTs = $nowTs - mt_rand(2, 6) * 86400;
        $pdo->beginTransaction();
        for ($k = 0; $k < $replies; $k++) {
            $uid = $agentIds[array_rand($agentIds)];
            $body = fv_reply($st[0], $emojiOf[$uid] ?? '');
            $ts = mt_rand($startTs, $nowTs - 600);
            $insPost->execute([$tid, $uid, $body, date('Y-m-d H:i:s', $ts)]);
        }
        $pdo->commit();
        $bumpThread->execute([$tid, $tid]);
        echo "  + nouveau sujet #{$tid} : « {$st[0]} » (+{$replies} réponses)\n";
        $createdThreads++;
    }
}
echo "✓ Nouveaux sujets créés : {$createdThreads}.\n";

$tot = $pdo->query('SELECT (SELECT COUNT(*) FROM users) u,(SELECT COUNT(*) FROM forum_threads) t,(SELECT COUNT(*) FROM forum_posts) p')->fetch(PDO::FETCH_ASSOC);
echo "→ Total : {$tot['u']} membres · {$tot['t']} sujets · {$tot['p']} messages.\n";
