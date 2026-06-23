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
    $stmt = db()->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/** Garde-fou : impose une session admin valide. */
function require_admin(): void
{
    if (!is_logged_in()) {
        redirect(url('admin/login.php'));
    }
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
        ? ['poster' => 'Affiches', 'game' => 'Jeux', 'console' => 'Consoles', 'apparel' => 'Vêtements', 'accessory' => 'Accessoires', 'collectible' => 'Collectors']
        : ['poster' => 'Posters', 'game' => 'Games', 'console' => 'Consoles', 'apparel' => 'Apparel', 'accessory' => 'Accessories', 'collectible' => 'Collectibles'];
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

/** Bloc média d'une carte : image réelle si dispo, sinon emoji sur dégradé. */
function media_html(?string $img, string $emoji): string
{
    $img = trim((string) $img);
    $out = '<div class="card__media"><span class="card__emoji" aria-hidden="true">' . $emoji . '</span>';
    if ($img !== '') {
        $out .= '<img class="card__img" src="' . e($img) . '" alt="" loading="lazy" onerror="this.remove()">';
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
