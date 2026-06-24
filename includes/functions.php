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
function resolve_language(): string
{
    $allowed = ['fr', 'en'];
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    return $_SESSION['lang'] ?? 'fr';
}

/** Traduit une clé. Retourne la clé si absente. */
function t(string $key): string
{
    return $GLOBALS['LANG'][$key] ?? $key;
}

/** Code langue courant. */
function lang(): string
{
    return $GLOBALS['LANG_CODE'] ?? 'fr';
}

/** Construit une URL en conservant la langue active. */
function with_lang(string $url): string
{
    $sep = str_contains($url, '?') ? '&' : '?';
    return $url . $sep . 'lang=' . lang();
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
    return (int) db()->lastInsertId();
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
    $sql = "SELECT f.*, COALESCE(u.display_name, u.username) AS author
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
    $articles = (int) db()->query('SELECT COUNT(*) FROM articles WHERE author_id = ' . (int) $uid . " AND status='published'")->fetchColumn();
    $arts = (int) db()->query('SELECT COUNT(*) FROM fanarts WHERE user_id = ' . (int) $uid . " AND status='approved'")->fetchColumn();
    return [
        ['🎟️', 'Bienvenue à Vice City', 'Crée ton compte', true],
        ['💬', 'Première prise de parole', 'Poste ton 1er message', $s['posts'] >= 1],
        ['🧵', 'Lanceur de sujet', 'Ouvre ton 1er sujet', $s['threads'] >= 1],
        ['🗣️', 'Bavard', 'Atteins 10 messages', $s['posts'] >= 10],
        ['🏛️', 'Pilier du forum', 'Atteins 50 messages', $s['posts'] >= 50],
        ['✍️', 'Plume de Vice City', 'Publie un article', $articles >= 1],
        ['🎨', 'Artiste de Leonida', 'Fais valider un fan-art', $arts >= 1],
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
function get_products(?string $category = null, ?int $limit = null): array
{
    $sql = "SELECT * FROM products WHERE active = 1";
    $args = [];
    if ($category !== null && $category !== '' && $category !== 'all') {
        $sql .= " AND category = ?";
        $args[] = $category;
    }
    $sql .= " ORDER BY sort ASC, id ASC";
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
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

/** Bloc média d'une carte : image réelle si dispo, sinon emoji sur dégradé. */
function media_html(?string $img, string $emoji, string $alt = ''): string
{
    $src = img_src($img);
    $alt = $alt !== '' ? $alt : 'Illustration GTA VI — ViceHub X';
    $out = '<div class="card__media"><span class="card__emoji" aria-hidden="true">' . $emoji . '</span>';
    if ($src !== '') {
        $out .= '<img class="card__img" src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy" onerror="this.remove()">';
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
