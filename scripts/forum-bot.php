<?php
/**
 * ViceHub X — Bot du forum : les 50 personas IA discutent entre eux.
 * Génère de nouveaux sujets et des réponses « en personnage » via l'API Anthropic,
 * pour faire vivre le forum en attendant de vrais membres.
 *
 * Prérequis :
 *   export ANTHROPIC_API_KEY="sk-ant-..."   (ou réglage 'anthropic_key' en base)
 *
 * Exemples :
 *   php scripts/forum-bot.php                 # 6 réponses entre personas
 *   php scripts/forum-bot.php --reply=10 --new=2
 *   php scripts/forum-bot.php --model=claude-haiku-4-5-20251001
 *
 * CRON (toutes les 30 min, ambiance vivante) :
 *   *(/30) * * * *  cd /chemin/ViceHubx && php scripts/forum-bot.php --reply=3 >/dev/null 2>&1
 */
require_once __DIR__ . '/../config/config.php';

$personas = require __DIR__ . '/../config/personas.php';
$byUser = [];
foreach ($personas as $p) { $byUser[$p[0]] = ['name' => $p[1], 'fav' => $p[2], 'bio' => $p[3]]; }

$opts = getopt('', ['reply::', 'new::', 'model::']);
$nReply = (int) ($opts['reply'] ?? 6);
$nNew   = (int) ($opts['new'] ?? 0);
$model  = (string) ($opts['model'] ?? 'claude-haiku-4-5-20251001');

$apiKey = getenv('ANTHROPIC_API_KEY') ?: (string) get_setting('anthropic_key', '');
if ($apiKey === '') {
    fwrite(STDERR, "✗ ANTHROPIC_API_KEY manquant (export ANTHROPIC_API_KEY=\"sk-ant-...\" ou réglage 'anthropic_key').\n");
    exit(1);
}

/** Appel minimal à l'API Anthropic, renvoie le texte. */
function claude(string $key, string $model, string $system, string $user, int $maxTok = 220): string {
    $payload = [
        'model' => $model, 'max_tokens' => $maxTok, 'system' => $system,
        'messages' => [['role' => 'user', 'content' => $user]],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01'],
        CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => 60,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code >= 400) {
        throw new RuntimeException('Anthropic ' . $code . ' : ' . substr((string) $raw, 0, 200));
    }
    $d = json_decode($raw, true);
    return trim($d['content'][0]['text'] ?? '');
}

/** id utilisateur d'un persona (par username). */
function persona_id(string $username): ?int {
    $st = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $st->execute([$username]);
    $id = $st->fetchColumn();
    return $id ? (int) $id : null;
}

function persona_system(array $info): string {
    return "Tu es {$info['name']}, un membre du forum francophone ViceHub X dédié à GTA V et GTA VI. "
        . "Ta personnalité : {$info['bio']} Jeu favori : {$info['fav']}. "
        . "Écris UN message de forum court (1 à 3 phrases), naturel et en personnage, en français. "
        . "Pas de markdown, pas de titre, ne te présente pas, n'invente pas d'info officielle. Reste courtois. "
        . "Tu peux utiliser quelques emojis si ça colle à ton personnage.";
}

$usernames = array_keys($byUser);
$cats = db()->query('SELECT id FROM forum_categories')->fetchAll(PDO::FETCH_COLUMN);
$made = 0;

// 1) Nouveaux sujets
for ($i = 0; $i < $nNew; $i++) {
    $u = $usernames[array_rand($usernames)];
    $uid = persona_id($u);
    if (!$uid) continue;
    try {
        $txt = claude($apiKey, $model, persona_system($byUser[$u]),
            "Lance un NOUVEAU sujet de discussion original sur GTA V ou GTA VI (théorie, question, débat…). "
            . "Réponds EXACTEMENT au format :\nTITRE: <titre court et accrocheur>\nMESSAGE: <message d'ouverture>", 300);
        if (preg_match('/TITRE\s*:\s*(.+?)\s*[\r\n]+MESSAGE\s*:\s*(.+)/is', $txt, $m)) {
            $tid = create_thread((int) $cats[array_rand($cats)], $uid, trim($m[1]), trim($m[2]));
            echo "+ sujet #$tid par {$byUser[$u]['name']}\n";
            $made++;
        }
    } catch (Throwable $e) { fwrite(STDERR, "thread: " . $e->getMessage() . "\n"); }
    usleep(400000);
}

// 2) Réponses entre personas sur les sujets récents
$threads = db()->query('SELECT id, title FROM forum_threads ORDER BY last_post_at DESC LIMIT 14')->fetchAll();
for ($i = 0; $i < $nReply && $threads; $i++) {
    $t = $threads[array_rand($threads)];
    // contexte : 4 derniers messages
    $ps = db()->prepare('SELECT p.body, p.user_id, COALESCE(u.display_name,u.username) name FROM forum_posts p LEFT JOIN users u ON u.id=p.user_id WHERE p.thread_id=? ORDER BY p.id DESC LIMIT 4');
    $ps->execute([(int) $t['id']]);
    $recent = array_reverse($ps->fetchAll());
    $lastUid = $recent ? (int) $recent[count($recent) - 1]['user_id'] : 0;
    // persona différent du dernier intervenant
    do { $u = $usernames[array_rand($usernames)]; $uid = persona_id($u); } while ($uid === $lastUid);
    if (!$uid) continue;
    $ctx = "Sujet : {$t['title']}\nDerniers messages :\n";
    foreach ($recent as $r) { $ctx .= '- ' . $r['name'] . ' : ' . mb_substr($r['body'], 0, 240) . "\n"; }
    $ctx .= "\nRéagis à la discussion en 1-3 phrases, en personnage.";
    try {
        $txt = claude($apiKey, $model, persona_system($byUser[$u]), $ctx);
        if ($txt !== '') { add_post((int) $t['id'], $uid, $txt); echo "↳ réponse de {$byUser[$u]['name']} sur « {$t['title']} »\n"; $made++; }
    } catch (Throwable $e) { fwrite(STDERR, "reply: " . $e->getMessage() . "\n"); }
    usleep(400000);
}

echo "✓ $made message(s) générés.\n";
