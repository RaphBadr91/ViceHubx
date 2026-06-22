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
 * Emplacement publicitaire. Affiche le bloc AdSense si configuré,
 * sinon une zone "réservée pub" discrète (utile en dev).
 */
function ad_slot(string $slotId = '', string $label = 'Publicité'): string
{
    $client = adsense_client();
    if ($client && $slotId !== '') {
        return '<div class="ad-slot"><ins class="adsbygoogle" style="display:block"'
            . ' data-ad-client="' . e($client) . '"'
            . ' data-ad-slot="' . e($slotId) . '"'
            . ' data-ad-format="auto" data-full-width-responsive="true"></ins>'
            . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>';
    }
    // Placeholder (aucune pub configurée)
    return '<div class="ad-slot ad-slot--ph" aria-hidden="true"><span>' . e($label)
        . '</span><small>Emplacement Google AdSense</small></div>';
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
