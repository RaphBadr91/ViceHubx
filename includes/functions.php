<?php
/**
 * ViceHub X — Fonctions utilitaires partagées.
 */

declare(strict_types=1);

/* ================================================================== */
/*  Sécurité & affichage                                              */
/* ================================================================== */

/** Échappe une chaîne pour le HTML (anti-XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirection HTTP propre. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Génère/récupère le jeton CSRF de session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Champ caché CSRF prêt à insérer dans un formulaire. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Vérifie le jeton CSRF d'une requête POST. */
function verify_csrf(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

/* ================================================================== */
/*  Internationalisation                                              */
/* ================================================================== */

/** Détermine la langue active (fr/en) via ?lang, session, puis défaut. */
/** Langues disponibles (code => libellé natif). Ajoutez une langue = un fichier lang/<code>.php. */
function available_languages(): array
{
    return ['fr' => '🇫🇷 Français', 'en' => '🇬🇧 English', 'es' => '🇪🇸 Español', 'de' => '🇩🇪 Deutsch'];
}

function resolve_language(): string
{
    $allowed = array_keys(available_languages());
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    $l = $_SESSION['lang'] ?? 'fr';
    return in_array($l, $allowed, true) ? $l : 'fr';
}

/** Traduit une clé (langue active → repli anglais → clé). */
function t(string $key): string
{
    return $GLOBALS['LANG'][$key] ?? ($GLOBALS['LANG_FALLBACK'][$key] ?? $key);
}

/** Code langue courant. */
function lang(): string
{
    return $GLOBALS['LANG_CODE'] ?? 'fr';
}

/** Construit une URL en conservant la langue active. */
function with_lang(string $url): string
{
    if (lang() === 'fr') {
        return $url; // langue par défaut : pas de paramètre
    }
    $sep = str_contains($url, '?') ? '&' : '?';
    return $url . $sep . 'lang=' . lang();
}

/** URL de la page courante dans une autre langue (préserve le chemin et les paramètres). */
function lang_url(string $code): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = explode('?', $uri, 2);
    parse_str($parts[1] ?? '', $q);
    $q['lang'] = $code;
    return $parts[0] . '?' . http_build_query($q);
}

/* ================================================================== */
/*  Liens / assets                                                    */
/* ================================================================== */

function asset(string $path): string
{
    return BASE_URL . '/public/assets/' . ltrim($path, '/');
}

function url(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Slug SEO à partir d'un titre. */
function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'article';
}

/** Formate une date courte localisée. */
function fmt_date(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y', $ts) : '';
}

/* ================================================================== */
/*  Badges de fiabilité (Leaks Lab)                                   */
/* ================================================================== */

/** Liste des badges disponibles. */
function badges(): array
{
    return [
        'confirmed' => ['fr' => 'Confirmé',  'en' => 'Confirmed', 'class' => 'badge--confirmed'],
        'official'  => ['fr' => 'Officiel',  'en' => 'Official',  'class' => 'badge--official'],
        'probable'  => ['fr' => 'Probable',  'en' => 'Likely',    'class' => 'badge--probable'],
        'rumor'     => ['fr' => 'Rumeur',    'en' => 'Rumor',     'class' => 'badge--rumor'],
        'analysis'  => ['fr' => 'Analyse',   'en' => 'Analysis',  'class' => 'badge--analysis'],
        'leak'      => ['fr' => 'Leak',      'en' => 'Leak',      'class' => 'badge--leak'],
        'fake'      => ['fr' => 'Faux',      'en' => 'Fake',      'class' => 'badge--fake'],
    ];
}

/** Rendu HTML d'un badge. */
function badge_html(?string $key): string
{
    if (!$key) {
        return '';
    }
    $b = badges()[$key] ?? null;
    if (!$b) {
        return '';
    }
    return '<span class="badge ' . $b['class'] . '">' . e($b[lang()] ?? $b['en']) . '</span>';
}

/* ================================================================== */
/*  Authentification admin                                            */
/* ================================================================== */

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $cache = null;
    if ($cache !== null && (int) $cache['id'] === (int) $_SESSION['user_id']) {
        return $cache;
    }
    $stmt = db()->prepare('SELECT id, username, email, display_name, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $cache = ($stmt->fetch() ?: null);
}

function user_role(): string
{
    return current_user()['role'] ?? '';
}
/** Membre du staff (peut accéder à l'admin). */
function is_admin(): bool
{
    return in_array(user_role(), ['admin', 'editor'], true);
}
/** Nom affiché public d'un utilisateur. */
function display_name(?array $u = null): string
{
    $u = $u ?? current_user();
    if (!$u) {
        return 'Anonyme';
    }
    return $u['display_name'] ?: $u['username'];
}

/** Garde-fou admin : session valide ET rôle staff. */
function require_admin(): void
{
    if (!is_logged_in()) {
        redirect(url('admin/login.php'));
    }
    if (!is_admin()) {
        redirect(with_lang(url('pages/account.php')));
    }
}
/** Garde-fou membre : impose une connexion (toute rôle). */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect(with_lang(url('pages/login.php')));
    }
}

/* ------------------------------------------------------------------ */
/*  Comptes membres / contributeurs                                    */
/* ------------------------------------------------------------------ */
function register_user(string $username, string $email, string $password, string $display = ''): int
{
    $username = trim($username);
    $email = trim(mb_strtolower($email));
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)) {
        throw new RuntimeException('Identifiant invalide (3-64 caractères : lettres, chiffres, . _ -).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Adresse e-mail invalide.');
    }
    if (strlen($password) < 8) {
        throw new RuntimeException('Le mot de passe doit faire au moins 8 caractères.');
    }
    $stmt = db()->prepare('SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Cet identifiant ou cet e-mail est déjà utilisé.');
    }
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $ins = db()->prepare('INSERT INTO users (username, email, display_name, password_hash, role) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$username, $email, $display !== '' ? $display : $username, $hash, 'member']);
    $newId = (int) db()->lastInsertId();
    // Message de bienvenue (et notification) depuis l'équipe
    $adminId = (int) (db()->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0);
    if ($adminId && $adminId !== $newId) {
        send_message($adminId, $newId, 'Bienvenue à Vice City ! 🌴 Présente-toi sur le forum, partage tes fan-arts, débloque des trophées et grimpe les rangs. Bon jeu et à bientôt sur ViceHub X !');
    }
    return $newId;
}

/** Authentifie par identifiant OU e-mail. Retourne l'utilisateur ou null. */
function login_attempt(string $login, string $password): ?array
{
    $login = trim($login);
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$login, mb_strtolower($login)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return null;
}

/* ------------------------------------------------------------------ */
/*  Forum communautaire                                                */
/* ------------------------------------------------------------------ */
function get_forum_categories(): array
{
    return db()->query(
        "SELECT c.*,
            (SELECT COUNT(*) FROM forum_threads t WHERE t.category_id = c.id) AS thread_count,
            (SELECT COUNT(*) FROM forum_posts p JOIN forum_threads t ON t.id = p.thread_id WHERE t.category_id = c.id) AS post_count,
            (SELECT MAX(t.last_post_at) FROM forum_threads t WHERE t.category_id = c.id) AS last_at
         FROM forum_categories c ORDER BY c.sort ASC, c.id ASC"
    )->fetchAll();
}
function get_forum_category(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM forum_categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}
function get_threads(int $categoryId): array
{
    $stmt = db()->prepare(
        "SELECT t.*, u.username, u.display_name,
            (SELECT COUNT(*) FROM forum_posts p WHERE p.thread_id = t.id) AS reply_count
         FROM forum_threads t LEFT JOIN users u ON u.id = t.user_id
         WHERE t.category_id = ? ORDER BY t.pinned DESC, t.last_post_at DESC"
    );
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}
function get_thread(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*, c.name AS cat_name, c.slug AS cat_slug, u.username, u.display_name
         FROM forum_threads t JOIN forum_categories c ON c.id = t.category_id
         LEFT JOIN users u ON u.id = t.user_id WHERE t.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
function get_thread_posts(int $threadId): array
{
    $stmt = db()->prepare(
        'SELECT p.*, u.username, u.display_name, u.role FROM forum_posts p
         LEFT JOIN users u ON u.id = p.user_id WHERE p.thread_id = ? ORDER BY p.created_at ASC, p.id ASC'
    );
    $stmt->execute([$threadId]);
    return $stmt->fetchAll();
}
function create_thread(int $categoryId, int $userId, string $title, string $body): int
{
    $title = trim($title);
    $body  = trim($body);
    if (mb_strlen($title) < 4) {
        throw new RuntimeException('Le titre doit faire au moins 4 caractères.');
    }
    if (mb_strlen($body) < 4) {
        throw new RuntimeException('Le message doit faire au moins 4 caractères.');
    }
    $slug = slugify($title) . '-' . base_convert((string) time(), 10, 36);
    $stmt = db()->prepare('INSERT INTO forum_threads (category_id, user_id, title, slug) VALUES (?, ?, ?, ?)');
    $stmt->execute([$categoryId, $userId, mb_substr($title, 0, 200), $slug]);
    $tid = (int) db()->lastInsertId();
    add_post($tid, $userId, $body);
    return $tid;
}
function add_post(int $threadId, int $userId, string $body): void
{
    $body = trim($body);
    if (mb_strlen($body) < 2) {
        throw new RuntimeException('Message trop court.');
    }
    db()->prepare('INSERT INTO forum_posts (thread_id, user_id, body) VALUES (?, ?, ?)')
        ->execute([$threadId, $userId, mb_substr($body, 0, 5000)]);
    db()->prepare('UPDATE forum_threads SET last_post_at = NOW() WHERE id = ?')->execute([$threadId]);
    // Notifie l'auteur du sujet (si ce n'est pas lui qui répond)
    $t = db()->prepare('SELECT user_id, title FROM forum_threads WHERE id = ?');
    $t->execute([$threadId]);
    if ($row = $t->fetch()) {
        $owner = (int) ($row['user_id'] ?? 0);
        if ($owner && $owner !== $userId) {
            $nm = db()->prepare('SELECT COALESCE(display_name, username) FROM users WHERE id = ?');
            $nm->execute([$userId]);
            $who = (string) ($nm->fetchColumn() ?: 'Quelqu’un');
            notify($owner, $who . ' a répondu à votre sujet « ' . $row['title'] . ' »', '/pages/forum-thread.php?id=' . $threadId);
        }
    }
}

/* ------------------------------------------------------------------ */
/*  Likes / réactions                                                  */
/* ------------------------------------------------------------------ */
function like_count(string $kind, int $item): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM likes WHERE kind = ? AND item_id = ?');
    $st->execute([$kind, $item]);
    return (int) $st->fetchColumn();
}
function user_liked(string $kind, int $item, ?int $uid): bool
{
    if (!$uid) {
        return false;
    }
    $st = db()->prepare('SELECT 1 FROM likes WHERE user_id = ? AND kind = ? AND item_id = ? LIMIT 1');
    $st->execute([$uid, $kind, $item]);
    return (bool) $st->fetchColumn();
}
function like_toggle(string $kind, int $item, int $uid): bool
{
    if (user_liked($kind, $item, $uid)) {
        db()->prepare('DELETE FROM likes WHERE user_id = ? AND kind = ? AND item_id = ?')->execute([$uid, $kind, $item]);
        return false;
    }
    db()->prepare('INSERT IGNORE INTO likes (user_id, kind, item_id) VALUES (?, ?, ?)')->execute([$uid, $kind, $item]);
    return true;
}

/* ------------------------------------------------------------------ */
/*  Notifications                                                      */
/* ------------------------------------------------------------------ */
function notify(int $uid, string $body, string $link = ''): void
{
    if ($uid <= 0) {
        return;
    }
    db()->prepare('INSERT INTO notifications (user_id, body, link) VALUES (?, ?, ?)')
        ->execute([$uid, mb_substr($body, 0, 255), $link]);
}
function unread_count(int $uid): int
{
    if ($uid <= 0) {
        return 0;
    }
    $st = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $st->execute([$uid]);
    return (int) $st->fetchColumn();
}
function get_notifications(int $uid, int $limit = 40): array
{
    $st = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int) $limit);
    $st->execute([$uid]);
    return $st->fetchAll();
}
function mark_notifications_read(int $uid): void
{
    db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
}

/* ------------------------------------------------------------------ */
/*  Profils publics                                                    */
/* ------------------------------------------------------------------ */
function get_user_by_username(string $u): ?array
{
    $st = db()->prepare('SELECT id, username, display_name, role, created_at FROM users WHERE username = ? LIMIT 1');
    $st->execute([$u]);
    return $st->fetch() ?: null;
}
function user_recent_posts(int $uid, int $limit = 8): array
{
    $st = db()->prepare('SELECT p.body, p.created_at, t.id AS tid, t.title FROM forum_posts p JOIN forum_threads t ON t.id = p.thread_id WHERE p.user_id = ? ORDER BY p.id DESC LIMIT ' . (int) $limit);
    $st->execute([$uid]);
    return $st->fetchAll();
}
function user_fanarts(int $uid, int $limit = 12): array
{
    $st = db()->prepare("SELECT * FROM fanarts WHERE user_id = ? AND status = 'approved' ORDER BY id DESC LIMIT " . (int) $limit);
    $st->execute([$uid]);
    return $st->fetchAll();
}

/* ------------------------------------------------------------------ */
/*  Messagerie privée                                                  */
/* ------------------------------------------------------------------ */
function send_message(int $from, int $to, string $body): bool
{
    $body = trim($body);
    if ($from <= 0 || $to <= 0 || $from === $to || mb_strlen($body) < 1) {
        return false;
    }
    db()->prepare('INSERT INTO messages (from_id, to_id, body) VALUES (?, ?, ?)')
        ->execute([$from, $to, mb_substr($body, 0, 4000)]);
    $nm = db()->prepare('SELECT username, COALESCE(display_name, username) FROM users WHERE id = ?');
    $nm->execute([$from]);
    if ($r = $nm->fetch(PDO::FETCH_NUM)) {
        notify($to, $r[1] . ' t’a envoyé un message privé 💌', '/pages/messages.php?u=' . $r[0]);
    }
    return true;
}
function unread_messages_count(int $uid): int
{
    if ($uid <= 0) {
        return 0;
    }
    $st = db()->prepare('SELECT COUNT(*) FROM messages WHERE to_id = ? AND is_read = 0');
    $st->execute([$uid]);
    return (int) $st->fetchColumn();
}
function get_conversations(int $uid): array
{
    $st = db()->prepare('SELECT IF(from_id = ?, to_id, from_id) AS other_id, MAX(id) AS last_id FROM messages WHERE from_id = ? OR to_id = ? GROUP BY other_id ORDER BY last_id DESC');
    $st->execute([$uid, $uid, $uid]);
    $convs = [];
    foreach ($st->fetchAll() as $row) {
        $oid = (int) $row['other_id'];
        $m = db()->prepare('SELECT * FROM messages WHERE id = ?');
        $m->execute([(int) $row['last_id']]);
        $last = $m->fetch();
        $u = db()->prepare('SELECT username, COALESCE(display_name, username) AS name FROM users WHERE id = ?');
        $u->execute([$oid]);
        $usr = $u->fetch();
        if (!$usr) {
            continue;
        }
        $un = db()->prepare('SELECT COUNT(*) FROM messages WHERE from_id = ? AND to_id = ? AND is_read = 0');
        $un->execute([$oid, $uid]);
        $convs[] = ['other_id' => $oid, 'username' => $usr['username'], 'name' => $usr['name'], 'last' => $last, 'unread' => (int) $un->fetchColumn()];
    }
    return $convs;
}
function get_conversation(int $uid, int $other): array
{
    $st = db()->prepare('SELECT * FROM messages WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?) ORDER BY id ASC LIMIT 300');
    $st->execute([$uid, $other, $other, $uid]);
    $rows = $st->fetchAll();
    db()->prepare('UPDATE messages SET is_read = 1 WHERE to_id = ? AND from_id = ?')->execute([$uid, $other]);
    return $rows;
}

/* ------------------------------------------------------------------ */
/*  Gamification : XP & rangs (façon GTA)                              */
/* ------------------------------------------------------------------ */
function rank_tiers(): array
{
    return [
        [0,    'Touriste',           '🧳'],
        [50,   'Bizut',              '🛹'],
        [150,  'Chauffeur',          '🚗'],
        [350,  'Braqueur',           '💰'],
        [700,  'Lieutenant',         '🕶️'],
        [1200, 'Caïd',               '👑'],
        [2000, 'Boss de Vice City',  '🌴'],
        [3500, 'Légende de Leonida', '⭐'],
    ];
}
/** Stats d'activité forum d'un membre (posts, sujets, XP). */
function user_xp_stats(int $uid): array
{
    static $cache = [];
    if (isset($cache[$uid])) {
        return $cache[$uid];
    }
    $st = db()->prepare('SELECT (SELECT COUNT(*) FROM forum_posts WHERE user_id = ?) AS posts, (SELECT COUNT(*) FROM forum_threads WHERE user_id = ?) AS threads');
    $st->execute([$uid, $uid]);
    $r = $st->fetch() ?: [];
    $posts = (int) ($r['posts'] ?? 0);
    $threads = (int) ($r['threads'] ?? 0);
    return $cache[$uid] = ['posts' => $posts, 'threads' => $threads, 'xp' => $posts * 10 + $threads * 20];
}
/** Rang correspondant à un total d'XP (+ palier suivant). */
function rank_for_xp(int $xp): array
{
    $tiers = rank_tiers();
    $cur = $tiers[0];
    $next = null;
    foreach ($tiers as $i => $t) {
        if ($xp >= $t[0]) {
            $cur = $t;
            $next = $tiers[$i + 1] ?? null;
        }
    }
    return ['min' => $cur[0], 'name' => $cur[1], 'emoji' => $cur[2], 'next' => $next];
}
/** Puce de rang affichable (forum, profil). */
function rank_chip_html(?int $uid): string
{
    if (!$uid) {
        return '';
    }
    $s = user_xp_stats($uid);
    $r = rank_for_xp($s['xp']);
    return '<span class="rank-chip" title="' . e($r['name'] . ' · ' . $s['xp'] . ' XP') . '">' . $r['emoji'] . ' ' . e($r['name']) . '</span>';
}
/** Galerie de fan-arts (approuvés par défaut). */
function get_fanarts(bool $approvedOnly = true, int $limit = 60): array
{
    $sql = "SELECT f.*, u.username, COALESCE(u.display_name, u.username) AS author
            FROM fanarts f LEFT JOIN users u ON u.id = f.user_id";
    if ($approvedOnly) {
        $sql .= " WHERE f.status = 'approved'";
    }
    $sql .= " ORDER BY f.id DESC LIMIT " . (int) $limit;
    return db()->query($sql)->fetchAll();
}

/** Événements à venir (et récents) avec compte à rebours. */
function get_events(int $limit = 12): array
{
    $stmt = db()->prepare('SELECT * FROM events ORDER BY event_date ASC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Succès/trophées d'un membre, calculés à partir de son activité. */
function user_achievements(int $uid): array
{
    $s = user_xp_stats($uid);
    $u = (int) $uid;
    $articles = (int) db()->query('SELECT COUNT(*) FROM articles WHERE author_id = ' . $u . " AND status='published'")->fetchColumn();
    $arts = (int) db()->query('SELECT COUNT(*) FROM fanarts WHERE user_id = ' . $u . " AND status='approved'")->fetchColumn();
    $likesGiven = (int) db()->query('SELECT COUNT(*) FROM likes WHERE user_id = ' . $u)->fetchColumn();
    $likesRecv = (int) db()->query("SELECT COUNT(*) FROM likes l WHERE (l.kind='post' AND l.item_id IN (SELECT id FROM forum_posts WHERE user_id=$u)) OR (l.kind='fanart' AND l.item_id IN (SELECT id FROM fanarts WHERE user_id=$u))")->fetchColumn();
    $msgs = (int) db()->query('SELECT COUNT(*) FROM messages WHERE from_id = ' . $u)->fetchColumn();
    $age = (int) db()->query('SELECT COALESCE(DATEDIFF(NOW(), created_at),0) FROM users WHERE id = ' . $u)->fetchColumn();
    return [
        ['🎟️', 'Bienvenue à Vice City', 'Crée ton compte', true],
        ['💬', 'Première prise de parole', 'Poste ton 1er message', $s['posts'] >= 1],
        ['🧵', 'Lanceur de sujet', 'Ouvre ton 1er sujet', $s['threads'] >= 1],
        ['🗣️', 'Bavard', 'Atteins 10 messages', $s['posts'] >= 10],
        ['🏛️', 'Pilier du forum', 'Atteins 50 messages', $s['posts'] >= 50],
        ['✍️', 'Plume de Vice City', 'Publie un article', $articles >= 1],
        ['🎨', 'Artiste de Leonida', 'Fais valider un fan-art', $arts >= 1],
        ['💜', 'Généreux', 'Aime 5 publications', $likesGiven >= 5],
        ['🔥', 'Apprécié', 'Reçois 10 likes', $likesRecv >= 10],
        ['💌', 'Sociable', 'Envoie un message privé', $msgs >= 1],
        ['📅', 'Vétéran', 'Membre depuis 30 jours', $age >= 30],
        ['👑', 'Légende', 'Atteins 2000 XP', $s['xp'] >= 2000],
    ];
}

/** Classement des membres les plus actifs du forum. */
function leaderboard(int $limit = 30): array
{
    $sql = "SELECT u.id, u.username, u.display_name, u.role,
                (SELECT COUNT(*) FROM forum_posts p WHERE p.user_id = u.id) AS posts,
                (SELECT COUNT(*) FROM forum_threads t WHERE t.user_id = u.id) AS threads
            FROM users u
            HAVING posts > 0 OR threads > 0
            ORDER BY (posts * 10 + threads * 20) DESC, posts DESC
            LIMIT " . (int) $limit;
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$r) {
        $r['xp'] = (int) $r['posts'] * 10 + (int) $r['threads'] * 20;
        $r['rank'] = rank_for_xp($r['xp']);
    }
    return $rows;
}

/* ================================================================== */
/*  Accès aux données                                                 */
/* ================================================================== */

function get_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
}

/**
 * Articles publiés, filtrables par catégorie (slug) et langue.
 */
function get_articles(array $filters = []): array
{
    $sql = "SELECT a.*, c.slug AS category_slug, c.name AS category_name
            FROM articles a
            LEFT JOIN categories c ON c.id = a.category_id
            WHERE a.status = 'published'";
    $params = [];

    if (!empty($filters['category'])) {
        $sql .= ' AND c.slug = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['lang'])) {
        $sql .= ' AND a.lang = ?';
        $params[] = $filters['lang'];
    }
    if (!empty($filters['badges'])) {
        $in = implode(',', array_fill(0, count($filters['badges']), '?'));
        $sql .= " AND a.badge IN ($in)";
        $params = array_merge($params, $filters['badges']);
    }

    $sql .= ' ORDER BY a.published_at DESC, a.id DESC';
    if (!empty($filters['limit'])) {
        $sql .= ' LIMIT ' . (int) $filters['limit'];
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_article_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        "SELECT a.*, c.slug AS category_slug, c.name AS category_name
         FROM articles a LEFT JOIN categories c ON c.id = a.category_id
         WHERE a.slug = ? LIMIT 1"
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function get_vehicles(): array
{
    return db()->query('SELECT * FROM vehicles ORDER BY id ASC')->fetchAll();
}

function get_characters(): array
{
    return db()->query('SELECT * FROM characters ORDER BY id ASC')->fetchAll();
}

function get_map_zones(): array
{
    return db()->query('SELECT * FROM map_zones ORDER BY id ASC')->fetchAll();
}

function get_trailer_analyses(): array
{
    return db()->query('SELECT * FROM trailer_analyses ORDER BY importance DESC, id ASC')->fetchAll();
}

function get_deals(): array
{
    return db()->query("SELECT * FROM affiliate_links WHERE active = 1 ORDER BY id ASC")->fetchAll();
}

/** Produits de la boutique (filtrables par catégorie). */
function get_products(?string $category = null, ?int $limit = null, ?string $subcategory = null): array
{
    $sql = "SELECT * FROM products WHERE active = 1";
    $args = [];
    if ($category !== null && $category !== '' && $category !== 'all') {
        $sql .= " AND category = ?";
        $args[] = $category;
    }
    if ($subcategory !== null && $subcategory !== '' && $subcategory !== 'all') {
        $sql .= " AND subcategory = ?";
        $args[] = $subcategory;
    }
    $sql .= " ORDER BY sort ASC, id ASC";
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

/**
 * Encart promotionnel « Boutique » à insérer dans un article (CTA).
 * Priorité aux produits « propulsés » (cta=1) choisis par l'admin, tous types
 * confondus (wallpapers, t-shirts, mugs…), sinon repli sur les wallpapers vedette.
 */
function article_shop_cta(string $variant = 'full'): string
{
    static $pool = null;
    if ($pool === null) {
        try {
            // 1) produits propulsés par l'admin (toutes catégories)
            $pool = db()->query("SELECT * FROM products WHERE active=1 AND cta=1 ORDER BY RAND() LIMIT 10")->fetchAll();
            if (!$pool) { // 2) repli : wallpapers vedette
                $pool = db()->query("SELECT * FROM products WHERE active=1 AND category='wallpaper' AND featured=1 ORDER BY RAND() LIMIT 8")->fetchAll();
            }
            if (!$pool) { // 3) repli : n'importe quel wallpaper
                $pool = db()->query("SELECT * FROM products WHERE active=1 AND category='wallpaper' ORDER BY RAND() LIMIT 8")->fetchAll();
            }
        } catch (Throwable $e) {
            $pool = [];
        }
    }
    if (!$pool) {
        return '';
    }
    $p = $pool[array_rand($pool)];
    $fr = lang() === 'fr';
    $is_wp = !empty($p['digital_file']);
    $purl = with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])));
    $allurl = with_lang(url('pages/shop.php' . ($is_wp ? '?cat=wallpaper' : '')));
    $price = price_html($p['price'], active_currency());
    $cats = product_categories();
    $badge = $is_wp ? 'HD' : ($p['badge'] ?: ($cats[$p['category']] ?? '★'));

    if ($variant === 'inline') {
        $msg = $is_wp
            ? ($fr ? 'Tu aimes l’univers de Vice City&nbsp;? Habille ton écran avec nos wallpapers HD.' : 'Love the Vice City vibe? Dress up your screen with our HD wallpapers.')
            : ($fr ? 'Soutiens le site et affiche ton style avec nos goodies Vice City.' : 'Support the site and show your style with our Vice City goodies.');
        return '<aside class="art-cta art-cta--inline">'
            . '<span class="art-cta__tag">🛍️ ' . ($fr ? 'Boutique' : 'Shop') . '</span>'
            . '<p>' . $msg . '</p>'
            . '<a class="btn btn--primary" href="' . e($allurl) . '">' . ($fr ? 'Voir la boutique' : 'Browse the shop') . ' →</a>'
            . '</aside>';
    }

    $title = $is_wp
        ? ($fr ? 'Habille ton écran avec Vice City' : 'Bring Vice City to your screen')
        : ($fr ? 'Le style Vice City, sur toi' : 'Wear the Vice City vibe');
    $desc = $is_wp
        ? ($fr ? 'Qualité magnifique, livrée sans filigrane en PNG, JPEG et PDF après achat.' : 'Gorgeous quality, delivered watermark-free in PNG, JPEG and PDF after purchase.')
        : ($fr ? 'Édition fan ViceHub X. Paiement sécurisé Stripe.' : 'ViceHub X fan edition. Secure Stripe checkout.');
    $cta1 = $is_wp ? ($fr ? 'Voir ce wallpaper' : 'View this wallpaper') : ($fr ? 'Voir le produit' : 'View product');

    return '<aside class="art-cta">'
        . '<a class="art-cta__media" href="' . e($purl) . '" aria-label="' . e($p['name']) . '">'
        . '<img src="' . e(img_src($p['image'])) . '" alt="' . e($p['name']) . '" loading="lazy" decoding="async" onerror="this.style.display=\'none\'">'
        . '<span class="art-cta__badge">' . e($badge) . '</span></a>'
        . '<div class="art-cta__body">'
        . '<span class="art-cta__tag">🛍️ ' . ($fr ? 'Boutique ViceHub X' : 'ViceHub X Shop') . '</span>'
        . '<h3>' . $title . '</h3>'
        . '<p>« ' . e($p['name']) . ' » — <strong>' . $price . '</strong>. ' . $desc . '</p>'
        . '<div class="art-cta__btns">'
        . '<a class="btn btn--primary" href="' . e($purl) . '">' . $cta1 . ' →</a>'
        . '<a class="btn btn--ghost" href="' . e($allurl) . '">' . ($fr ? 'La boutique' : 'The shop') . '</a>'
        . '</div></div></aside>';
}

/** Insère un bloc HTML après le n-ième paragraphe d'un corps d'article. */
function inject_after_paragraph(string $html, int $after, string $insert): string
{
    if ($insert === '') {
        return $html;
    }
    $parts = explode('</p>', $html);
    if (count($parts) <= $after) {
        return $html . $insert;
    }
    $out = '';
    $last = count($parts) - 1;
    foreach ($parts as $i => $part) {
        $out .= $part . ($i < $last ? '</p>' : '');
        if ($i === $after - 1) {
            $out .= $insert;
        }
    }
    return $out;
}

/** Thèmes de wallpapers (sous-catégories) avec libellés bilingues + emoji. */
function wallpaper_themes(): array
{
    return lang() === 'fr'
        ? ['voiture' => '🚗 Voiture', 'avion' => '✈️ Avion', 'ville' => '🌆 Ville', 'nuit' => '🌃 Nuit', 'fille' => '💃 Fille']
        : ['voiture' => '🚗 Cars', 'avion' => '✈️ Planes', 'ville' => '🌆 City', 'nuit' => '🌃 Night', 'fille' => '💃 Girls'];
}

/** Produits mis en avant (page d'accueil). */
function get_featured_products(int $limit = 4): array
{
    $stmt = db()->prepare("SELECT * FROM products WHERE active = 1 AND featured = 1 ORDER BY sort ASC, id ASC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_product_by_slug(string $slug): ?array
{
    $stmt = db()->prepare("SELECT * FROM products WHERE slug = ? AND active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/** Catégories de la boutique avec libellés bilingues. */
function product_categories(): array
{
    return lang() === 'fr'
        ? ['poster' => 'Affiches', 'wallpaper' => 'Wallpapers', 'game' => 'Jeux', 'console' => 'Consoles', 'apparel' => 'Vêtements', 'accessory' => 'Accessoires', 'collectible' => 'Collectors']
        : ['poster' => 'Posters', 'wallpaper' => 'Wallpapers', 'game' => 'Games', 'console' => 'Consoles', 'apparel' => 'Apparel', 'accessory' => 'Accessories', 'collectible' => 'Collectibles'];
}

/** Prix formaté (ex. « 24,90 € »). */
function price_html($price, string $currency = 'EUR'): string
{
    if ($price === null || $price === '') {
        return '';
    }
    $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
    $sym = $symbols[$currency] ?? $currency;
    $val = number_format((float) $price, 2, lang() === 'fr' ? ',' : '.', lang() === 'fr' ? ' ' : ',');
    return lang() === 'fr' ? $val . ' ' . $sym : $sym . $val;
}

/* ------------------------------------------------------------------ */
/*  Stripe (paiement direct des produits ViceHub)                      */
/* ------------------------------------------------------------------ */
function shop_currency(): string
{
    return strtoupper((string) (get_setting('shop_currency', 'EUR') ?: 'EUR'));
}
/**
 * Devise affichée/encaissée selon l'audience :
 *   francophones (fr) → EUR (5 €) · anglophones & autres (en) → USD (5 $).
 * Même montant, devise différente. Surchargeable via le réglage 'shop_currency_en'.
 */
function active_currency(): string
{
    if (lang() === 'fr') {
        return 'EUR';
    }
    return strtoupper((string) (get_setting('shop_currency_en', 'USD') ?: 'USD'));
}
function stripe_secret(): string
{
    return (string) (getenv('STRIPE_SECRET_KEY') ?: get_setting('stripe_secret_key', ''));
}
function stripe_pk(): string
{
    return (string) (getenv('STRIPE_PUBLISHABLE_KEY') ?: get_setting('stripe_publishable_key', ''));
}
function stripe_webhook_secret(): string
{
    return (string) (getenv('STRIPE_WEBHOOK_SECRET') ?: get_setting('stripe_webhook_secret', ''));
}
/** Le paiement direct est-il configuré ? */
function stripe_enabled(): bool
{
    $sk = stripe_secret();
    return $sk !== '' && str_starts_with($sk, 'sk_');
}
/** Appel REST minimal à l'API Stripe (sans SDK, via cURL). */
function stripe_api(string $method, string $path, array $params = []): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL est requis pour Stripe.');
    }
    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . stripe_secret()],
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_TIMEOUT        => 25,
    ];
    if ($params) {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params); // notation imbriquée OK
    }
    curl_setopt_array($ch, $opts);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException('Connexion à Stripe impossible : ' . $err);
    }
    $data = json_decode((string) $raw, true) ?: [];
    if ($code >= 400) {
        throw new RuntimeException($data['error']['message'] ?? ('Erreur Stripe (' . $code . ')'));
    }
    return $data;
}

/* ------------------------------------------------------------------ */
/*  Panier (session)                                                   */
/* ------------------------------------------------------------------ */
function cart_get(): array
{
    return (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
}
function cart_set(array $cart): void
{
    $_SESSION['cart'] = $cart;
}
function cart_clear(): void
{
    unset($_SESSION['cart']);
}
function cart_count(): int
{
    return (int) array_sum(cart_get());
}
/** Lignes du panier hydratées (produits Stripe actifs uniquement). */
function cart_lines(): array
{
    $cart = cart_get();
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE id IN ($in) AND active = 1 AND sale_type = 'stripe'");
    $stmt->execute($ids);
    $lines = [];
    foreach ($stmt->fetchAll() as $p) {
        $qty = max(1, (int) ($cart[$p['id']] ?? 1));
        $p['qty'] = $qty;
        $p['line_total'] = (float) $p['price'] * $qty;
        $lines[] = $p;
    }
    return $lines;
}
function cart_total(): float
{
    $t = 0.0;
    foreach (cart_lines() as $l) {
        $t += $l['line_total'];
    }
    return $t;
}

/** Bouton d'achat contextuel : panier Stripe (produit direct) ou lien revendeur. */
function product_buy_button(array $p, bool $small = true): string
{
    $cls = 'btn btn--primary product__buy' . ($small ? '' : ' btn--lg');
    if (($p['sale_type'] ?? 'external') === 'stripe') {
        return '<form method="post" action="' . e(with_lang(url('pages/cart.php'))) . '" class="add-cart-form">'
            . csrf_field()
            . '<input type="hidden" name="action" value="add">'
            . '<input type="hidden" name="id" value="' . (int) $p['id'] . '">'
            . '<button class="' . $cls . '" type="submit">🛒 ' . e(t('add_to_cart')) . '</button>'
            . '</form>';
    }
    $merchant = !empty($p['merchant']) ? ' · ' . e($p['merchant']) : '';
    return '<a class="' . $cls . '" href="' . e((string) ($p['url'] ?? '#')) . '" target="_blank" rel="sponsored nofollow noopener">'
        . e(t('shop_buy')) . $merchant . ' ↗</a>';
}

function get_setting(string $key, ?string $default = null): ?string
{
    $stmt = db()->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row['value'] ?? $default;
}

/** Enregistre/écrase un réglage. */
function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (`key`, value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    );
    $stmt->execute([$key, $value]);
}

/* ================================================================== */
/*  Date de sortie & compte à rebours                                 */
/* ================================================================== */

/** Date de sortie GTA VI (surchargée par le réglage release_date). */
function release_date(): string
{
    return get_setting('release_date', RELEASE_DATE) ?: RELEASE_DATE;
}

/** Date de sortie formatée et localisée (ex. "Disponible le 19 novembre 2026"). */
function release_human(): string
{
    $ts = strtotime(release_date());
    if (!$ts) {
        return '';
    }
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = date('Y', $ts);
    $fr = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $en = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    return lang() === 'fr'
        ? "Disponible le {$d} {$fr[$m]} {$y}"
        : "Coming {$en[$m]} {$d}, {$y}";
}

/* ================================================================== */
/*  Publicité / Google AdSense                                        */
/* ================================================================== */

/** Identifiant client AdSense (ca-pub-...) ou null. */
function adsense_client(): ?string
{
    $c = trim((string) get_setting('adsense_client', ''));
    return $c !== '' ? $c : null;
}

/**
 * Emplacement publicitaire. Affiche le bloc AdSense uniquement si configuré
 * (client + slot). Sinon : rien (aucune zone "réservée" visible).
 */
function ad_slot(string $slotId = '', string $label = 'Publicité'): string
{
    $client = adsense_client();
    if (!$client || $slotId === '') {
        return ''; // pas de pub configurée → on n'affiche rien
    }
    return '<div class="ad-slot"><ins class="adsbygoogle" style="display:block"'
        . ' data-ad-client="' . e($client) . '"'
        . ' data-ad-slot="' . e($slotId) . '"'
        . ' data-ad-format="auto" data-full-width-responsive="true"></ins>'
        . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>';
}

/* ================================================================== */
/*  Niveaux d'importance (Trailer Lab)                                */
/* ================================================================== */

function importance_label(int $level): string
{
    $map = lang() === 'fr'
        ? [1 => 'Mineur', 2 => 'Notable', 3 => 'Important', 4 => 'Majeur', 5 => 'Critique']
        : [1 => 'Minor', 2 => 'Notable', 3 => 'Important', 4 => 'Major', 5 => 'Critical'];
    return $map[$level] ?? (string) $level;
}

/* ================================================================== */
/*  Médias de carte (vraie image + repli)                             */
/* ================================================================== */

/** Petit GET HTTP (curl si dispo, sinon file_get_contents). */
function http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true]);
        $data = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data !== false && $code < 400) {
            return (string) $data;
        }
    }
    if (ini_get('allow_url_fopen')) {
        $data = @file_get_contents($url);
        if ($data !== false) {
            return $data;
        }
    }
    return null;
}

/**
 * Chemin local du fichier propre d'un wallpaper. S'il est absent, on tente de
 * le télécharger depuis le CDN (config/wallpapers.php) et de le mettre en cache.
 */
function wallpaper_path(string $name): ?string
{
    $name = preg_replace('/[^a-z0-9_-]/i', '', $name);
    if ($name === '') {
        return null;
    }
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
        $p = ROOT_PATH . '/storage/wallpapers/' . $name . '.' . $ext;
        if (is_file($p)) {
            return $p;
        }
    }
    // Amorçage depuis le CDN
    static $sources = null;
    if ($sources === null) {
        $f = ROOT_PATH . '/config/wallpapers.php';
        $sources = is_file($f) ? (require $f) : [];
    }
    $src = $sources[$name] ?? '';
    if ($src !== '') {
        $data = http_get($src);
        if ($data !== null && strlen($data) > 1000) {
            $dest = ROOT_PATH . '/storage/wallpapers/' . $name . '.png';
            if (is_dir(dirname($dest)) && is_writable(dirname($dest)) && @file_put_contents($dest, $data) !== false) {
                return $dest;
            }
        }
    }
    return null;
}

/** URL CDN d'un visuel à partir de son nom de fichier (ou '' si inconnu). */
function cdn_url(string $filename): string
{
    static $map = null;
    if ($map === null) {
        $f = ROOT_PATH . '/config/cdn_map.php';
        $map = is_file($f) ? (require $f) : [];
    }
    return $map[$filename] ?? '';
}

/**
 * Résout la source d'une image : fichier local s'il existe, sinon CDN public,
 * sinon le chemin d'origine. Les URL absolues et /preview.php sont laissées telles quelles.
 */
function img_src(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '' || preg_match('#^https?://#', $path) || str_starts_with($path, '/preview.php')) {
        return $path;
    }
    $rel = ltrim($path, '/');
    if (is_file(ROOT_PATH . '/' . $rel)) {
        return $path; // fichier local présent
    }
    $cdn = cdn_url(basename($rel));
    return $cdn !== '' ? $cdn : $path;
}

/**
 * Variante WebP légère d'une image (chargement rapide).
 *  · CDN Higgsfield : « …_<id>.png » → « …_<id>_min.webp »
 *  · Fichier local  : « image.png » → « image.webp » s'il existe
 * Retourne '' si aucune variante n'est connue.
 */
function webp_variant(string $src): string
{
    if ($src === '') {
        return '';
    }
    if (preg_match('#^https?://[^/]*cloudfront\.net/.+#i', $src)) {
        if (preg_match('/_min\.webp$/i', $src)) {
            return $src;
        }
        if (preg_match('/\.(png|jpe?g|webp)$/i', $src)) {
            return preg_replace('/\.(png|jpe?g|webp)$/i', '_min.webp', $src);
        }
        return '';
    }
    $rel = ltrim($src, '/');
    if (preg_match('/\.(png|jpe?g)$/i', $rel)) {
        $cand = preg_replace('/\.(png|jpe?g)$/i', '.webp', $rel);
        if (is_file(ROOT_PATH . '/' . $cand)) {
            return '/' . $cand;
        }
    }
    return '';
}

/** Bloc média d'une carte : image réelle si dispo, sinon emoji sur dégradé. */
function media_html(?string $img, string $emoji, string $alt = ''): string
{
    $src = img_src($img);
    $alt = $alt !== '' ? $alt : 'Illustration GTA VI — ViceHub X';
    $out = '<div class="card__media"><span class="card__emoji" aria-hidden="true">' . $emoji . '</span>';
    if ($src !== '') {
        $img_tag = '<img class="card__img" src="' . e($src) . '" alt="' . e($alt)
            . '" loading="lazy" decoding="async" onerror="(this.closest(\'picture\')||this).remove()">';
        $webp = webp_variant($src);
        if ($webp !== '') {
            $out .= '<picture><source srcset="' . e($webp) . '" type="image/webp">' . $img_tag . '</picture>';
        } else {
            $out .= $img_tag;
        }
    }
    return $out . '</div>';
}

/** Indice de fiabilité (0-100) associé à un badge, pour la jauge Leaks Lab. */
function badge_reliability(?string $key): int
{
    return [
        'confirmed' => 100, 'official' => 100, 'probable' => 72,
        'analysis' => 58, 'leak' => 46, 'rumor' => 34, 'fake' => 6,
    ][$key] ?? 50;
}

/** Icône SVG inline pour un module (24x24, stroke currentColor). */
function os_icon(string $key): string
{
    $p = [
        'news'       => '<path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h8M8 17h5"/>',
        'map'        => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>',
        'trailer'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9l5 3-5 3z"/>',
        'leaks'      => '<path d="M9 3h6M10 3v5l-4 9a2 2 0 0 0 2 3h8a2 2 0 0 0 2-3l-4-9V3"/>',
        'vehicles'   => '<path d="M3 13l2-5a2 2 0 0 1 2-1h10a2 2 0 0 1 2 1l2 5v5h-3M6 18H3v-5M6 18a2 2 0 1 0 4 0M18 18a2 2 0 1 0-4 0"/>',
        'characters' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'community'  => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/>',
        'deals'      => '<path d="M3 8a2 2 0 0 1 2-2h9l7 7-7 7-9-9z"/><circle cx="8" cy="11" r="1.4"/>',
    ];
    $d = $p[$key] ?? '<circle cx="12" cy="12" r="9"/>';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/** Miniature YouTube (maxres) pour un ID donné. */
function yt_thumb(string $id): string
{
    return 'https://i.ytimg.com/vi/' . rawurlencode($id) . '/maxresdefault.jpg';
}

/**
 * Bandeau « Weazel News » : fil d'actualité défilant, immersif (univers GTA / Vice City).
 * Mêle les vrais titres news aux gros titres satiriques de Leonida + horloge en direct.
 */
function weazel_ticker_html(): string
{
    $fr = lang() === 'fr';
    // Gros titres « in-universe » (satire façon GTA, indépendants du contenu réel)
    $flavor = $fr ? [
        'Météo Vice City : 35°C et 100 % de chances de braquage en fin de journée',
        'Bouchon monstre sur le pont de Leonida après un airboat égaré sur la voie rapide',
        'Le cours du dollar local s’effondre, les flamants roses entrent en panique',
        'Un alligator élu maire honoraire de Vice Beach à l’unanimité',
        'Course-poursuite en direct sur Ocean Drive : la police a perdu la trace',
        'Les radios de Leonida se disputent le tube de l’été',
        'Record battu au marché de nuit : les néons consomment plus que la ville',
    ] : [
        'Vice City weather: 95°F and a 100% chance of a heist by sundown',
        'Massive jam on the Leonida bridge after a stray airboat hits the freeway',
        'Local dollar crashes — the flamingos are officially panicking',
        'An alligator elected honorary mayor of Vice Beach by a landslide',
        'Live chase on Ocean Drive: police have lost the trail',
        'Leonida radio stations fight over the song of the summer',
        'Night market breaks records: the neon now outdraws the whole city',
    ];
    // Vrais titres récents (news) pour relier au contenu du site
    $real = [];
    foreach (get_articles(['category' => 'news', 'lang' => lang(), 'limit' => 6]) as $a) {
        $real[] = ['t' => $a['title'], 'u' => with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))];
    }
    // Construit la liste entrelacée
    $items = [];
    $max = max(count($flavor), count($real));
    for ($i = 0; $i < $max; $i++) {
        if (isset($real[$i])) {
            $items[] = '<a class="wz-item" href="' . e($real[$i]['u']) . '"><span class="wz-dot"></span>' . e($real[$i]['t']) . '</a>';
        }
        if (isset($flavor[$i])) {
            $items[] = '<span class="wz-item wz-item--flavor"><span class="wz-dot"></span>' . e($flavor[$i]) . '</span>';
        }
    }
    if (!$items) {
        return '';
    }
    $track = implode('', $items);
    $live  = $fr ? 'EN DIRECT' : 'LIVE';
    $brand = $fr ? 'WEAZEL NEWS' : 'WEAZEL NEWS';
    // Le ruban est dupliqué pour un défilement continu sans couture.
    return '<div class="weazel" role="complementary" aria-label="Weazel News">'
        . '<div class="weazel__brand"><span class="weazel__live">● ' . $live . '</span> ' . $brand . '</div>'
        . '<div class="weazel__viewport"><div class="weazel__track">' . $track . $track . '</div></div>'
        . '<div class="weazel__clock"><span aria-hidden="true">📡</span> LEONIDA <b data-leonida-clock>--:--</b></div>'
        . '</div>'
        . '<script>(function(){var b=document.querySelector("[data-leonida-clock]");if(!b)return;'
        . 'function p(n){return(n<10?"0":"")+n;}function tick(){var d=new Date();'
        . 'b.textContent=p(d.getHours())+":"+p(d.getMinutes())+":"+p(d.getSeconds());}tick();setInterval(tick,1000);})();</script>';
}

/* ================================================================== */
/*  Upload d'image sécurisé (admin)                                   */
/* ================================================================== */

/**
 * Valide et enregistre une image téléversée.
 * Retourne l'URL publique relative, ou null si aucun fichier.
 * @throws RuntimeException en cas de fichier invalide.
 */
function handle_image_upload(string $field): ?string
{
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Échec du téléversement (code ' . $file['error'] . ').');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Fichier trop volumineux (max 3 Mo).');
    }

    // Validation réelle du type MIME, pas l'extension fournie
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    if (!in_array($mime, UPLOAD_ALLOWED, true)) {
        throw new RuntimeException('Format non autorisé (JPG, PNG, WebP uniquement).');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'bin',
    };

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Impossible d’enregistrer le fichier.');
    }

    db()->prepare('INSERT INTO media (filename, mime) VALUES (?, ?)')->execute([$name, $mime]);
    return UPLOAD_URL . '/' . $name;
}

/* ================================================================== */
/*  Livraison numérique : conversion JPEG/PDF + envoi par e-mail       */
/* ================================================================== */

/** Convertit une image (chemin) en JPEG (binaire), via GD. */
function image_to_jpeg(string $path, int $quality = 92): ?string
{
    $data = @file_get_contents($path);
    if ($data === false) {
        return null;
    }
    $im = @imagecreatefromstring($data);
    if (!$im) {
        return null;
    }
    ob_start();
    imagejpeg($im, null, $quality);
    $out = ob_get_clean();
    imagedestroy($im);
    return $out ?: null;
}

/** Génère un PDF d'une seule page contenant un JPEG (sans librairie). */
function jpeg_to_pdf(string $jpeg, int $w, int $h): string
{
    $pw = 842.0;                 // largeur page (paysage ~A4)
    $ph = $w > 0 ? $pw * $h / $w : 595.0;
    $pwF = number_format($pw, 2, '.', '');
    $phF = number_format($ph, 2, '.', '');
    $content = "q\n$pwF 0 0 $phF 0 0 cm\n/Im0 Do\nQ";
    $pdf = "%PDF-1.4\n";
    $off = [];
    $add = function (string $s) use (&$pdf, &$off) { $off[] = strlen($pdf); $pdf .= $s; };
    $add("1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n");
    $add("2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n");
    $add("3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pwF $phF] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>\nendobj\n");
    $add("4 0 obj\n<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($jpeg) . " >>\nstream\n" . $jpeg . "\nendstream\nendobj\n");
    $add("5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj\n");
    $xref = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    foreach ($off as $o) {
        $pdf .= sprintf("%010d 00000 n \n", $o);
    }
    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    return $pdf;
}

/* ------------------------------------------------------------------ */
/*  E-mail : Resend (API) avec repli sur mail()                         */
/* ------------------------------------------------------------------ */

/** Clé API Resend (env prioritaire, sinon réglage admin). */
function resend_api_key(): string
{
    return (string) (getenv('RESEND_API_KEY') ?: get_setting('resend_api_key', ''));
}
/** Resend est-il configuré ? (clé « re_… »). */
function resend_enabled(): bool
{
    return str_starts_with(resend_api_key(), 're_');
}
/** Adresse d'expédition (réglage mail_from, sinon no-reply@domaine). */
function mail_from_address(): string
{
    $host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'vicehubx.fr');
    return (string) (get_setting('mail_from', '') ?: 'no-reply@' . $host);
}
/** Nom d'expéditeur affiché. */
function mail_from_name(): string
{
    return (string) (get_setting('mail_from_name', '') ?: APP_NAME);
}
/**
 * Envoie un e-mail via l'API Resend (HTTPS, sans SDK).
 * @param array $attachments  [['name'=>…, 'data'=>binaire], …]
 */
function resend_send(string $to, string $subject, string $html, array $attachments = []): bool
{
    if (!function_exists('curl_init') || !resend_enabled()) {
        return false;
    }
    $payload = [
        'from'    => mail_from_name() . ' <' . mail_from_address() . '>',
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $html,
    ];
    if ($attachments) {
        $payload['attachments'] = array_map(
            static fn($a) => ['filename' => $a['name'], 'content' => base64_encode($a['data'])],
            $attachments
        );
    }
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . resend_api_key(),
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT    => 25,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $raw !== false && $code >= 200 && $code < 300;
}

/** Envoie un e-mail HTML simple (Resend si configuré, sinon mail()). */
function send_mail(string $to, string $subject, string $html): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (resend_enabled()) {
        return resend_send($to, $subject, $html);
    }
    $headers = 'From: ' . mail_from_name() . ' <' . mail_from_address() . ">\r\n"
        . 'Reply-To: ' . mail_from_address() . "\r\n"
        . "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
}

/** Envoie un e-mail HTML avec pièces jointes (Resend si configuré, sinon mail() multipart). */
function send_mail_attachments(string $to, string $subject, string $html, array $attachments): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // Resend gère nativement les pièces jointes (PNG/JPEG/PDF en base64).
    if (resend_enabled()) {
        return resend_send($to, $subject, $html, $attachments);
    }
    $sender = mail_from_address();
    $b = 'vhx_' . bin2hex(random_bytes(10));
    $from = mail_from_name();
    $headers = "From: $from <$sender>\r\nReply-To: $sender\r\nMIME-Version: 1.0\r\n"
        . "Content-Type: multipart/mixed; boundary=\"$b\"\r\n";
    $msg = "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $html . "\r\n";
    foreach ($attachments as $a) {
        $msg .= "--$b\r\nContent-Type: " . $a['mime'] . "; name=\"" . $a['name'] . "\"\r\n"
            . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"" . $a['name'] . "\"\r\n\r\n"
            . chunk_split(base64_encode($a['data'])) . "\r\n";
    }
    $msg .= "--$b--";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $msg, $headers);
}

/** Reconstruit les lignes numériques d'une commande depuis une spec « id:qty,id:qty ». */
function digital_items_from_spec(string $spec): array
{
    $items = [];
    foreach (array_filter(array_map('trim', explode(',', $spec))) as $pair) {
        [$id, $qty] = array_pad(explode(':', $pair, 2), 2, '1');
        $id = (int) $id;
        if ($id <= 0) {
            continue;
        }
        $st = db()->prepare('SELECT id, name, price, digital_file FROM products WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $p = $st->fetch();
        if ($p && !empty($p['digital_file'])) {
            $items[] = [
                'id'           => (int) $p['id'],
                'name'         => $p['name'],
                'qty'          => max(1, (int) $qty),
                'price'        => (float) $p['price'],
                'digital_file' => $p['digital_file'],
            ];
        }
    }
    return $items;
}

/** Récupère (ou crée) l'identifiant d'une commande à partir de sa session Stripe. */
function order_id_for_session(string $sessionId): int
{
    if ($sessionId === '') {
        return 0;
    }
    $q = db()->prepare('SELECT id FROM orders WHERE stripe_session = ? LIMIT 1');
    $q->execute([$sessionId]);
    return (int) $q->fetchColumn();
}

/** Livre par e-mail les fichiers numériques d'une commande payée (PNG+JPEG+PDF). Idempotent. */
function deliver_order(int $orderId): bool
{
    $st = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $st->execute([$orderId]);
    $o = $st->fetch();
    if (!$o || $o['status'] !== 'paid' || (int) $o['delivered'] === 1) {
        return false;
    }
    $email = (string) ($o['email'] ?? '');
    $items = json_decode((string) $o['items'], true) ?: [];
    $attach = [];
    foreach ($items as $it) {
        if (empty($it['digital_file'])) {
            continue;
        }
        $name = pathinfo((string) $it['digital_file'], PATHINFO_FILENAME);
        $path = function_exists('wallpaper_path') ? wallpaper_path($name) : null;
        if (!$path || !is_file($path)) {
            continue;
        }
        $png = @file_get_contents($path);
        $base = preg_replace('/[^a-z0-9._-]/i', '-', (string) ($it['name'] ?? $name));
        if ($png !== false) {
            $attach[] = ['name' => $base . '.png', 'mime' => 'image/png', 'data' => $png];
        }
        $jpeg = image_to_jpeg($path, 92);
        if ($jpeg !== null) {
            $attach[] = ['name' => $base . '.jpg', 'mime' => 'image/jpeg', 'data' => $jpeg];
            $sz = @getimagesizefromstring($jpeg);
            $attach[] = ['name' => $base . '.pdf', 'mime' => 'application/pdf', 'data' => jpeg_to_pdf($jpeg, (int) ($sz[0] ?? 1376), (int) ($sz[1] ?? 768))];
        }
    }
    if (!$attach || $email === '') {
        // Rien à livrer (ou pas d'e-mail) : on marque tout de même pour ne pas réessayer indéfiniment
        db()->prepare('UPDATE orders SET delivered = 1 WHERE id = ?')->execute([$orderId]);
        return false;
    }
    $html = '<div style="font-family:sans-serif"><h2>Merci pour ton achat sur ViceHub X ! 🌴</h2>'
        . '<p>Tes wallpapers haute qualité sont en pièces jointes, en <strong>PNG, JPEG et PDF</strong>, sans filigrane.</p>'
        . '<p>Bon jeu à Vice City,<br>— L’équipe ViceHub X</p></div>';
    $ok = send_mail_attachments($email, 'Tes wallpapers ViceHub X 🌴', $html, $attach);
    db()->prepare('UPDATE orders SET delivered = 1 WHERE id = ?')->execute([$orderId]);
    return $ok;
}
