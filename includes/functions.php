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

/**
 * Verrou des scripts d'installation/maintenance (make-hero, optimize-images,
 * fetch-*, install, diag…). Bloque l'accès PUBLIC : autorisé seulement si connecté
 * en admin, ou si la variable d'environnement VICEHUB_SETUP=1 est présente (à retirer
 * après usage). L'idéal reste de SUPPRIMER ces fichiers une fois l'installation faite.
 *
 * @param bool $allowAdmin false = exige VICEHUB_SETUP=1 (scripts les plus sensibles).
 */
function setup_guard(bool $allowAdmin = true): void
{
    $envOk   = getenv('VICEHUB_SETUP') === '1';
    $adminOk = $allowAdmin && is_logged_in() && is_admin();
    if ($envOk || $adminOk) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Désactivé</title>'
       . '<div style="font-family:system-ui,Arial;max-width:560px;margin:12vh auto;padding:26px;background:#141225;color:#e9e6f5;border-radius:14px;border:1px solid #2a2740">'
       . '<h1 style="font-size:18px;margin:.2rem 0 1rem">🔒 Script d\'installation désactivé</h1>'
       . '<p style="color:#cfc9dd;font-size:14px;line-height:1.6">Par sécurité, ce script ne s\'exécute pas en accès public.</p>'
       . '<p style="color:#cfc9dd;font-size:14px;line-height:1.6"><strong>Supprime ce fichier</strong> du serveur une fois l\'installation faite. Pour le relancer : connecte-toi en <strong>admin</strong>, ou ajoute temporairement <code>VICEHUB_SETUP=1</code> dans ton <code>.env</code>.</p>'
       . '</div>';
    exit;
}

/* ------------------------------------------------------------------ */
/*  Anti-force-brute (connexions & réinitialisation) — par IP          */
/* ------------------------------------------------------------------ */
function client_ip(): string
{
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return substr((string) $ip, 0, 64);
}
/** Table des tentatives (auto-installée). */
function auth_attempts_table(): void
{
    static $done = false;
    if ($done) { return; }
    db()->exec(
        'CREATE TABLE IF NOT EXISTS auth_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(64) NOT NULL,
            action VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip, action, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $done = true;
}
/** true si l'action est encore autorisée pour cette IP (sous le seuil). */
function throttle_ok(string $action, int $max = 8, int $minutes = 15): bool
{
    try {
        auth_attempts_table();
        $st = db()->prepare('SELECT COUNT(*) FROM auth_attempts WHERE ip = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $st->execute([client_ip(), $action, $minutes]);
        return (int) $st->fetchColumn() < $max;
    } catch (Throwable $e) {
        return true; // en cas de souci base : on ne bloque jamais l'utilisateur légitime
    }
}
/** Enregistre une tentative échouée (+ purge légère de l'historique ancien). */
function throttle_hit(string $action): void
{
    try {
        auth_attempts_table();
        db()->prepare('INSERT INTO auth_attempts (ip, action) VALUES (?, ?)')->execute([client_ip(), $action]);
        if (random_int(1, 12) === 1) {
            db()->exec('DELETE FROM auth_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        }
    } catch (Throwable $e) { /* silencieux */ }
}
/** Remet le compteur à zéro (après un succès). */
function throttle_clear(string $action): void
{
    try {
        auth_attempts_table();
        db()->prepare('DELETE FROM auth_attempts WHERE ip = ? AND action = ?')->execute([client_ip(), $action]);
    } catch (Throwable $e) { /* silencieux */ }
}

/* ------------------------------------------------------------------ */
/*  « Rester connecté » — jeton persistant sécurisé (selector:validator) */
/* ------------------------------------------------------------------ */
function remember_tokens_table(): void
{
    static $done = false;
    if ($done) { return; }
    db()->exec(
        'CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            selector VARCHAR(32) NOT NULL,
            validator_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_selector (selector),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $done = true;
}
/** Émet un cookie « Rester connecté » (30 j par défaut) lié à l'utilisateur. */
function set_remember_cookie(int $userId, int $days = 30): void
{
    if ($userId <= 0) { return; }
    try {
        remember_tokens_table();
        $selector  = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $expires   = time() + $days * 86400;
        db()->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, FROM_UNIXTIME(?))')
            ->execute([$userId, $selector, hash('sha256', $validator), $expires]);
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie('vhx_remember', $selector . ':' . $validator, [
            'expires' => $expires, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax',
        ]);
    } catch (Throwable $e) { /* silencieux */ }
}
/** Connexion automatique via le cookie « Rester connecté » (appelée dans config). */
function try_remember_login(): void
{
    if (!empty($_SESSION['user_id'])) { return; }
    $raw = (string) ($_COOKIE['vhx_remember'] ?? '');
    if (strpos($raw, ':') === false) { return; }
    [$selector, $validator] = explode(':', $raw, 2);
    try {
        remember_tokens_table();
        $st = db()->prepare('SELECT user_id, validator_hash FROM remember_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1');
        $st->execute([$selector]);
        $row = $st->fetch();
        if ($row && hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
            $chk = db()->prepare('SELECT 1 FROM users WHERE id = ? LIMIT 1');
            $chk->execute([(int) $row['user_id']]);
            if ($chk->fetchColumn()) { $_SESSION['user_id'] = (int) $row['user_id']; }
        }
    } catch (Throwable $e) { /* silencieux */ }
}
/** Supprime le jeton « Rester connecté » (à la déconnexion). */
function clear_remember_cookie(): void
{
    $raw = (string) ($_COOKIE['vhx_remember'] ?? '');
    if (strpos($raw, ':') !== false) {
        [$selector] = explode(':', $raw, 2);
        try { remember_tokens_table(); db()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]); } catch (Throwable $e) {}
    }
    setcookie('vhx_remember', '', ['expires' => time() - 3600, 'path' => '/']);
    unset($_COOKIE['vhx_remember']);
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
    $rel  = 'public/assets/' . ltrim($path, '/');
    $url  = BASE_URL . '/' . $rel;
    $full = ROOT_PATH . '/' . $rel;
    // Anti-cache : ?v=<date de modif> → toute mise à jour (CSS, JS, vidéo, image)
    // est servie fraîche aux visiteurs, sans cache navigateur périmé.
    if (is_file($full)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'v=' . filemtime($full);
    }
    return $url;
}

/**
 * Génère une URL PROPRE (sans « /pages/ » ni « .php ») :
 *   index.php               → /
 *   pages/news.php          → /news
 *   pages/article.php?slug= → /article/<slug>
 *   pages/product.php?slug= → /produit/<slug>
 *   pages/forum-thread.php?id= → /sujet/<id>
 *   pages/forum-category.php?cat= → /categorie/<cat>
 *   pages/profil.php?u=     → /membre/<u>
 * Les URL admin et les endpoints racine (like.php…) restent inchangés.
 * Le routage réel est assuré par .htaccess.
 */
function url(string $path): string
{
    $path = ltrim($path, '/');
    $query = '';
    if (($qpos = strpos($path, '?')) !== false) {
        $query = substr($path, $qpos + 1);
        $path  = substr($path, 0, $qpos);
    }
    parse_str($query, $params);
    $pretty = static function (string $prefix, string $value, string $key) use ($params): string {
        unset($params[$key]);
        $rest = $params ? '?' . http_build_query($params) : '';
        return BASE_URL . '/' . $prefix . '/' . rawurlencode($value) . $rest;
    };

    if ($path === '' || $path === 'index.php') {
        $rest = $params ? '?' . http_build_query($params) : '';
        return BASE_URL . '/' . $rest;
    }
    if ($path === 'pages/article.php' && isset($params['slug']))        { return $pretty('article', (string) $params['slug'], 'slug'); }
    if ($path === 'pages/product.php' && isset($params['slug']))        { return $pretty('produit', (string) $params['slug'], 'slug'); }
    if ($path === 'pages/forum-thread.php' && isset($params['id']))     { return $pretty('sujet', (string) $params['id'], 'id'); }
    if ($path === 'pages/forum-category.php' && isset($params['cat']))  { return $pretty('categorie', (string) $params['cat'], 'cat'); }
    if ($path === 'pages/profil.php' && isset($params['u']))            { return $pretty('membre', (string) $params['u'], 'u'); }
    if (preg_match('#^pages/([a-z0-9-]+)\.php$#', $path, $m)) {
        return BASE_URL . '/' . $m[1] . ($query !== '' ? '?' . $query : '');
    }
    return BASE_URL . '/' . $path . ($query !== '' ? '?' . $query : '');
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
    // E-mail de bienvenue (non bloquant : n'empêche jamais l'inscription).
    try {
        $name = $display !== '' ? $display : $username;
        send_mail($email, 'Bienvenue sur ' . APP_NAME . ' 🌴', email_layout(
            'Bienvenue, ' . $name . ' !',
            '<p>Ton compte <strong>ViceHub X</strong> est créé. Tu peux dès maintenant participer au forum, publier des fan-arts, suivre l\'actu GTA VI et grimper les rangs de Vice City.</p>'
            . '<p>À très vite sur le site ! 🌴</p>',
            'Aller sur le site', rtrim(site_base_url(), '/') . '/'
        ));
    } catch (Throwable $e) { /* silencieux */ }
    return $newId;
}

/** Base absolue du site (schéma + hôte), pour les liens dans les e-mails. */
function site_base_url(): string
{
    if (BASE_URL !== '') {
        return rtrim(BASE_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
}

/** Gabarit HTML d'e-mail (identité ViceHub X) avec bouton d'action optionnel. */
function email_layout(string $heading, string $bodyHtml, string $ctaLabel = '', string $ctaUrl = ''): string
{
    $btn = ($ctaLabel !== '' && $ctaUrl !== '')
        ? '<a href="' . e($ctaUrl) . '" style="display:inline-block;margin:18px 0;padding:12px 24px;background:linear-gradient(90deg,#ff2e88,#8a3cff);color:#fff;text-decoration:none;border-radius:10px;font-weight:700">' . e($ctaLabel) . '</a>'
        : '';
    return '<div style="font-family:Arial,Helvetica,sans-serif;background:#0b0a14;padding:24px">'
        . '<div style="max-width:520px;margin:auto;background:#141225;border:1px solid #2a2740;border-radius:16px;padding:28px;color:#e9e6f5">'
        . '<div style="font-size:22px;font-weight:800;color:#fff;margin-bottom:2px">Vice<span style="color:#ff2e88">Hub</span> <span style="color:#2bd6ff">X</span></div>'
        . '<h1 style="font-size:19px;color:#fff;margin:.5rem 0 1rem">' . e($heading) . '</h1>'
        . '<div style="font-size:14px;line-height:1.6;color:#cfc9dd">' . $bodyHtml . '</div>'
        . $btn
        . '<hr style="border:none;border-top:1px solid #2a2740;margin:20px 0">'
        . '<p style="font-size:12px;color:#8a86a0">ViceHub X — média fan indépendant et non officiel dédié à GTA VI / Vice City.</p>'
        . '</div></div>';
}

/** Crée la table des jetons de réinitialisation si besoin (auto-installation). */
function ensure_password_resets_table(): void
{
    static $done = false;
    if ($done) { return; }
    db()->exec(
        'CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $done = true;
}

/**
 * Crée un jeton de réinitialisation pour un e-mail (s'il correspond à un compte)
 * et envoie le lien par e-mail. Retourne toujours true côté appelant : on ne révèle
 * jamais si l'e-mail existe (anti-énumération).
 */
function request_password_reset(string $email): void
{
    $email = trim(mb_strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return; }
    ensure_password_resets_table();
    $st = db()->prepare('SELECT id, username, display_name FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u) { return; } // e-mail inconnu : on ne fait rien (mais l'appelant affiche le même message)

    // Un seul jeton actif par e-mail.
    db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
    $raw = bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
        ->execute([$email, hash('sha256', $raw)]);

    // Un SEUL paramètre opaque (pas de '&' dans l'URL) → robuste avec tous les clients mail.
    $link = site_base_url() . '/pages/reinitialiser.php?t=' . $raw;
    $name = (string) ($u['display_name'] ?: $u['username']);
    send_mail($email, 'Réinitialisation de ton mot de passe — ' . APP_NAME, email_layout(
        'Réinitialise ton mot de passe',
        '<p>Bonjour ' . e($name) . ',</p>'
        . '<p>Tu as demandé à réinitialiser ton mot de passe sur <strong>ViceHub X</strong>. Clique sur le bouton ci-dessous (lien valable <strong>1 heure</strong>) :</p>'
        . '<p style="font-size:12px;color:#8a86a0">Si tu n\'es pas à l\'origine de cette demande, ignore cet e-mail : ton mot de passe reste inchangé.</p>',
        'Réinitialiser mon mot de passe', $link
    ));
}

/** Nettoie un jeton reçu (retire espaces/retours à la ligne éventuels des e-mails). */
function clean_reset_token(string $rawToken): string
{
    return (string) preg_replace('/[^a-f0-9]/i', '', trim($rawToken));
}

/** Vérifie un jeton de réinitialisation (recherché par son hash). Retourne l'id utilisateur ou null. */
function verify_password_reset(string $rawToken): ?int
{
    $rawToken = clean_reset_token($rawToken);
    if ($rawToken === '') { return null; }
    ensure_password_resets_table();
    $st = db()->prepare('SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
    $st->execute([hash('sha256', $rawToken)]);
    $email = (string) $st->fetchColumn();
    if ($email === '') { return null; }
    $u = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $u->execute([$email]);
    $id = (int) $u->fetchColumn();
    return $id ?: null;
}

/** Applique un nouveau mot de passe après jeton valide, puis invalide le jeton. */
function complete_password_reset(string $rawToken, string $newPassword): bool
{
    $uid = verify_password_reset($rawToken);
    if (!$uid || strlen($newPassword) < 8) { return false; }
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]), $uid]);
    db()->prepare('DELETE FROM password_resets WHERE token_hash = ?')
        ->execute([hash('sha256', clean_reset_token($rawToken))]);
    return true;
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
/**
 * Pool de produits « propulsés » en rotation.
 * L'admin choisit un nombre (réglage cta_count) ; on sélectionne ce nombre de
 * produits, en rotation quotidienne (déterministe par jour) pour l'immersion.
 * Candidats : produits cochés 🚀 par l'admin, sinon tout produit vendable.
 */
function cta_pool(): array
{
    try {
        $count = max(1, min(50, (int) get_setting('cta_count', '6')));
        $cand = db()->query("SELECT * FROM products WHERE active=1 AND cta=1")->fetchAll();
        if (!$cand) {
            $cand = db()->query("SELECT * FROM products WHERE active=1 AND sale_type='stripe' AND image IS NOT NULL AND image <> ''")->fetchAll();
        }
        if (!$cand) {
            $cand = db()->query("SELECT * FROM products WHERE active=1 AND category='wallpaper'")->fetchAll();
        }
        if (!$cand) {
            return [];
        }
        // Rotation stable sur la journée : ordre déterministe par (id, jour), puis on garde N.
        $day = date('Ymd');
        usort($cand, fn($a, $b) => strcmp(md5($a['id'] . '-' . $day), md5($b['id'] . '-' . $day)));
        return array_slice($cand, 0, $count);
    } catch (Throwable $e) {
        return [];
    }
}

function article_shop_cta(string $variant = 'full'): string
{
    static $pool = null;
    if ($pool === null) {
        $pool = cta_pool();
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
        . picture_html((string) $p['image'], (string) $p['name'], '', 'loading="lazy" decoding="async" onerror="(this.closest(\'picture\')||this).style.display=\'none\'"')
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

/**
 * Retire tout marqueur technique laissé par l'IA (===FIN===, ===CORPS===, ===DEBUT===…),
 * seul ou enveloppé dans un <p>. Les lecteurs ne doivent JAMAIS voir ces balises :
 * un article doit se lire comme s'il était écrit par un humain.
 */
function clean_ai_markers(string $text): string
{
    if ($text === '' || strpos($text, '==') === false) {
        return $text;
    }
    // <p>=== FIN ===</p>  →  supprimé
    $text = preg_replace('#<p>\s*={2,}\s*[A-Za-zÀ-ÿ_\- ]{1,24}\s*={2,}\s*</p>#u', '', $text) ?? $text;
    // === FIN ===  (nu, n'importe où)  →  supprimé
    $text = preg_replace('#={2,}\s*[A-Za-zÀ-ÿ_\- ]{1,24}\s*={2,}#u', '', $text) ?? $text;
    return trim($text);
}

/** Mots-clés significatifs d'un texte (pour lier pubs internes au sujet de l'article). */
function article_keywords(string $text, int $min = 4, int $max = 6): array
{
    $text = mb_strtolower(strip_tags($text));
    $text = preg_replace('/[^a-zà-ÿ0-9\s]/u', ' ', $text) ?? '';
    $stop = ['avec', 'pour', 'dans', 'les', 'des', 'une', 'que', 'qui', 'sur', 'par', 'plus',
        'vice', 'city', 'sont', 'cette', 'vos', 'leur', 'tout', 'tous', 'est', 'son', 'ses',
        'aux', 'the', 'and', 'gta', 'nous', 'vous', 'être', 'faire', 'comme', 'entre', 'ainsi'];
    $out = [];
    foreach (preg_split('/\s+/', $text) ?: [] as $w) {
        if (mb_strlen($w) >= $min && !in_array($w, $stop, true) && !ctype_digit($w)) {
            $out[$w] = true;
        }
    }
    return array_slice(array_keys($out), 0, $max);
}

/** Encart FORUM lié à l'article : un sujet pertinent, sinon le plus actif. */
function article_forum_cta(array $article): string
{
    static $shown = [];
    try {
        $fr = lang() === 'fr';
        $kw = article_keywords((string) ($article['title'] ?? '') . ' ' . (string) ($article['excerpt'] ?? ''));
        $excl = $shown ? (' AND t.id NOT IN (' . implode(',', array_map('intval', $shown)) . ')') : '';
        $thread = null;
        if ($kw) {
            $likes = []; $args = [];
            foreach ($kw as $w) { $likes[] = 't.title LIKE ?'; $args[] = '%' . $w . '%'; }
            $st = db()->prepare(
                "SELECT t.id, t.title, (SELECT COUNT(*) FROM forum_posts p WHERE p.thread_id=t.id) AS replies
                 FROM forum_threads t WHERE (" . implode(' OR ', $likes) . ")$excl
                 ORDER BY replies DESC, t.last_post_at DESC LIMIT 1"
            );
            $st->execute($args);
            $thread = $st->fetch() ?: null;
        }
        if (!$thread) {
            $thread = db()->query(
                "SELECT t.id, t.title, (SELECT COUNT(*) FROM forum_posts p WHERE p.thread_id=t.id) AS replies
                 FROM forum_threads t WHERE 1=1" . ($shown ? ' AND t.id NOT IN (' . implode(',', array_map('intval', $shown)) . ')' : '') . "
                 ORDER BY t.pinned DESC, replies DESC, t.last_post_at DESC LIMIT 1"
            )->fetch() ?: null;
        }
        if (!$thread) { return ''; }
        static $vi = 0;
        $shown[] = (int) $thread['id'];
        $url = with_lang(url('pages/forum-thread.php?id=' . (int) $thread['id']));
        $rep = (int) $thread['replies'];
        $ttl = e($thread['title']);

        // Copywriting persuasif (accroche + bénéfice + bouton), en rotation pour varier.
        if ($fr) {
            $variants = $rep >= 3 ? [
                ['🔥 Tout le monde en parle en ce moment',
                 '<strong>' . $rep . ' fans</strong> ont déjà lâché leur théorie sur <strong>« ' . $ttl . ' »</strong>. Il ne manque plus que <strong>la tienne</strong>.',
                 'Je donne mon avis'],
                ['💬 Ne reste pas spectateur',
                 'Pendant que tu lis, <strong>' . $rep . ' passionnés</strong> refont GTA&nbsp;VI en direct sur <strong>« ' . $ttl . ' »</strong>. Rejoins-les avant que ça se calme.',
                 'Rejoindre le débat'],
                ['👀 Tu vas vouloir réagir',
                 '<strong>« ' . $ttl . ' »</strong> — ' . $rep . ' réponses, des théories qui fusent, des infos que tu ne verras nulle part ailleurs.',
                 'Voir ce qui se dit'],
                ['⭐ Les vrais fans sont déjà là',
                 'Débats, leaks, prédictions&nbsp;: <strong>« ' . $ttl . ' »</strong> réunit <strong>' . $rep . ' membres</strong>. Ta place t’attend, gratuitement.',
                 'Je rejoins la communauté'],
            ] : [
                ['🚀 Sois le premier à réagir',
                 'Le sujet <strong>« ' . $ttl . ' »</strong> vient d’ouvrir. Pose <strong>ta</strong> théorie avant tout le monde et lance le débat.',
                 'Ouvrir la discussion'],
                ['💬 Ton avis peut tout lancer',
                 'Personne n’a encore le dernier mot sur <strong>« ' . $ttl . ' »</strong>. Donne le ton sur le forum ViceHub X.',
                 'Je participe'],
            ];
        } else {
            $variants = $rep >= 3 ? [
                ['🔥 Everyone’s talking about this',
                 '<strong>' . $rep . ' fans</strong> already dropped their theory on <strong>“' . $ttl . '”</strong>. Yours is the one that’s missing.',
                 'Share my take'],
                ['💬 Don’t just watch',
                 'Right now <strong>' . $rep . ' fans</strong> are reshaping GTA&nbsp;VI over <strong>“' . $ttl . '”</strong>. Jump in before it cools down.',
                 'Join the debate'],
            ] : [
                ['🚀 Be the first to react',
                 'The thread <strong>“' . $ttl . '”</strong> just opened. Drop your theory before anyone else.',
                 'Start the discussion'],
            ];
        }
        $scenes = ['nightlife.png', 'pool-party.png', 'street-market.png', 'downtown.png', 'artdeco.png'];
        $v   = $variants[$vi % count($variants)];
        $img = '/public/assets/img/scenes/' . $scenes[$vi % count($scenes)];
        $vi++;
        $badge = $rep > 0 ? '💬 ' . $rep : '💬';

        return '<aside class="art-cta">'
            . '<a class="art-cta__media" href="' . e($url) . '" aria-label="' . ($fr ? 'Forum ViceHub X' : 'ViceHub X Forum') . '">'
            . picture_html($img, $fr ? 'Forum ViceHub X — la communauté GTA VI' : 'ViceHub X Forum', '', 'loading="lazy" decoding="async" onerror="(this.closest(\'picture\')||this).style.display=\'none\'"')
            . '<span class="art-cta__badge">' . $badge . '</span></a>'
            . '<div class="art-cta__body">'
            . '<span class="art-cta__tag">💬 ' . ($fr ? 'Forum ViceHub X' : 'ViceHub X Forum') . '</span>'
            . '<h3>' . $v[0] . '</h3>'
            . '<p>' . $v[1] . '</p>'
            . '<div class="art-cta__btns">'
            . '<a class="btn btn--primary" href="' . e($url) . '">' . $v[2] . ' →</a>'
            . '<a class="btn btn--ghost" href="' . e(with_lang(url('pages/forum.php'))) . '">' . ($fr ? 'Tout le forum' : 'The forum') . '</a>'
            . '</div></div></aside>';
    } catch (Throwable $e) {
        return '';
    }
}

/** Encart « À lire aussi » : un autre article (même catégorie, sinon Blog). */
function article_blog_cta(array $article): string
{
    static $shown = [];
    try {
        $fr = lang() === 'fr';
        $lang = (string) ($article['lang'] ?? lang());
        $selfId = (int) ($article['id'] ?? 0);
        $cats = array_values(array_unique(array_filter([(string) ($article['category_slug'] ?? ''), 'blog', ''])));
        $pick = null;
        foreach ($cats as $cat) {
            $rows = get_articles($cat !== '' ? ['category' => $cat, 'lang' => $lang, 'limit' => 12] : ['lang' => $lang, 'limit' => 12]);
            foreach ($rows as $r) {
                if ((int) $r['id'] !== $selfId && !in_array((int) $r['id'], $shown, true)) { $pick = $r; break 2; }
            }
        }
        if (!$pick) { return ''; }
        static $hi = 0;
        $shown[] = (int) $pick['id'];
        $url   = with_lang(url('pages/article.php?slug=' . urlencode((string) $pick['slug'])));
        $tease = mb_substr(strip_tags((string) ($pick['excerpt'] ?? '')), 0, 110);
        $img   = !empty($pick['image']) ? (string) $pick['image'] : '/public/assets/img/scenes/downtown.png';
        $hooks = $fr
            ? ['🔥 À dévorer juste après', '📰 Ça va te plaire', '👉 Pour aller plus loin', '⭐ L’article que les fans adorent']
            : ['🔥 Read this next', '📰 You’ll love this', '👉 Go deeper', '⭐ Fan favourite'];
        $hook  = $hooks[$hi % count($hooks)]; $hi++;
        return '<aside class="art-cta">'
            . '<a class="art-cta__media" href="' . e($url) . '" aria-label="' . e((string) $pick['title']) . '">'
            . picture_html($img, (string) $pick['title'], '', 'loading="lazy" decoding="async" onerror="(this.closest(\'picture\')||this).style.display=\'none\'"')
            . '<span class="art-cta__badge">📖</span></a>'
            . '<div class="art-cta__body">'
            . '<span class="art-cta__tag">📰 ' . ($fr ? 'À lire aussi' : 'Read next') . '</span>'
            . '<h3>' . e($hook) . '</h3>'
            . '<p><strong>« ' . e($pick['title']) . ' »</strong>' . ($tease !== '' ? ' — ' . e($tease) . '…' : '') . '</p>'
            . '<div class="art-cta__btns">'
            . '<a class="btn btn--primary" href="' . e($url) . '">' . ($fr ? 'Lire l’article' : 'Read the article') . ' →</a>'
            . '</div></div></aside>';
    } catch (Throwable $e) {
        return '';
    }
}

/**
 * Insère 3 à 6 PUBS INTERNES variées (Boutique / Forum / Blog) réparties dans le
 * corps de l'article (~2000 mots), en rapport avec le sujet pour rester cohérent.
 */
function inject_internal_ads(string $html, array $article): string
{
    $paraCount = substr_count($html, '</p>');
    if ($paraCount < 4) {
        return $html . article_shop_cta('full');
    }
    // ~1 pub toutes les 4 paragraphes, borné entre 3 et 6, sans dépasser l'espace dispo.
    $n = max(3, min(6, (int) floor($paraCount / 4)));
    $n = min($n, max(1, $paraCount - 1));

    // Rotation Boutique → Forum → Blog (relève toujours quelque chose de pertinent).
    $blocks = [];
    for ($i = 0; $i < $n; $i++) {
        $type = ['shop', 'forum', 'blog'][$i % 3];
        $b = $type === 'shop' ? article_shop_cta('full')
            : ($type === 'forum' ? article_forum_cta($article) : article_blog_cta($article));
        if ($b === '') { $b = article_shop_cta('full'); } // repli si forum/blog vide (garde une image)
        if ($b !== '') { $blocks[] = $b; }
    }
    if (!$blocks) { return $html; }

    // Positions réparties régulièrement.
    $step = max(2, (int) floor($paraCount / (count($blocks) + 1)));
    $positions = [];
    for ($i = 0; $i < count($blocks); $i++) {
        $positions[$i] = min($paraCount - 1, $step * ($i + 1));
    }
    // Insertion de la position la PLUS GRANDE à la plus petite (les blocs contiennent
    // des </p> ; injecter en descendant préserve les indices des positions plus petites).
    for ($i = count($blocks) - 1; $i >= 0; $i--) {
        $html = inject_after_paragraph($html, $positions[$i], $blocks[$i]);
    }
    return $html;
}

/** Thèmes de wallpapers (sous-catégories) avec libellés bilingues + emoji. */
function wallpaper_themes(): array
{
    return lang() === 'fr'
        ? ['pack' => '📦 Packs', 'voiture' => '🚗 Voiture', 'avion' => '✈️ Avion', 'ville' => '🌆 Ville', 'nuit' => '🌃 Nuit', 'fille' => '💃 Fille']
        : ['pack' => '📦 Bundles', 'voiture' => '🚗 Cars', 'avion' => '✈️ Planes', 'ville' => '🌆 City', 'nuit' => '🌃 Night', 'fille' => '💃 Girls'];
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

/**
 * Maillage interne automatique : transforme certaines expressions-clés du corps
 * d'un article en liens vers les pages piliers (GTA 6, carte, persos, boutique…).
 * Sûr (hrefs contrôlés), appliqué après strip_tags. Une fois par expression et
 * par article, hors titres/liens existants, plafonné pour rester naturel.
 */
function internal_autolink(string $html): string
{
    static $defs = null;
    if ($defs === null) {
        $defs = [
            'Lucia Caminos'     => 'pages/characters.php',
            'Jason Duval'       => 'pages/characters.php',
            'Jason et Lucia'    => 'pages/characters.php',
            'Mount Kalaga'      => 'pages/map.php',
            'Port Gellhorn'     => 'pages/map.php',
            'carte de Leonida'  => 'pages/map.php',
            'État de Leonida'   => 'pages/map.php',
            'Vintage Vice City' => 'pages/gta6.php',
            'édition Ultimate'  => 'pages/gta6.php',
            'édition Standard'  => 'pages/gta6.php',
            'date de sortie'    => 'pages/gta6.php',
            'précommande'       => 'pages/gta6.php',
            'fonds d’écran'     => 'pages/fonds-ecran-gta6.php',
            'GTA 5'             => 'pages/gta6-vs-gta5.php',
            'GTA V'             => 'pages/gta6-vs-gta5.php',
            'Leonida'           => 'pages/map.php',
            'Vice City'         => 'pages/map.php',
            'véhicules'         => 'pages/vehicles.php',
            'boutique'          => 'pages/shop.php',
        ];
        // Plus longue expression d'abord (évite de capter « Leonida » avant « État de Leonida »).
        uksort($defs, static fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    }

    $segments = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $store = [];
    $usedPage = [];       // une page liée au plus 2 fois / article
    $usedPhrase = [];     // une expression au plus 1 fois / article
    $total = 0; $maxTotal = 6; $skip = 0;

    foreach ($segments as $idx => $seg) {
        if ($seg === '') {
            continue;
        }
        if ($seg[0] === '<') {
            if (preg_match('~^<\s*(h[1-6]|a|aside|blockquote)\b~i', $seg)) {
                $skip++;
            } elseif (preg_match('~^<\s*/\s*(h[1-6]|a|aside|blockquote)\b~i', $seg) && $skip > 0) {
                $skip--;
            }
            continue;
        }
        if ($skip > 0 || $total >= $maxTotal) {
            continue;
        }
        foreach ($defs as $phrase => $page) {
            if ($total >= $maxTotal) {
                break;
            }
            if (isset($usedPhrase[$phrase]) || ($usedPage[$page] ?? 0) >= 2) {
                continue;
            }
            $pat = '/(?<![\p{L}\p{N}])' . preg_quote($phrase, '/') . '(?![\p{L}\p{N}])/u';
            $cnt = 0;
            $seg = preg_replace_callback($pat, static function ($m) use ($page, &$store) {
                $token = "\x01" . count($store) . "\x01";
                $store[$token] = '<a href="' . e(with_lang(url($page))) . '">' . $m[0] . '</a>';
                return $token;
            }, $seg, 1, $cnt);
            if ($cnt > 0) {
                $usedPhrase[$phrase] = true;
                $usedPage[$page] = ($usedPage[$page] ?? 0) + 1;
                $total += $cnt;
            }
        }
        $segments[$idx] = $seg;
    }

    $out = implode('', $segments);
    return $store ? strtr($out, $store) : $out;
}

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
 * Source d'une image, en privilégiant une variante WebP STATIQUE locale (légère,
 * servie directement par le serveur web → ultra-rapide, sans PHP par image).
 * Ordre : WebP local → original local → variante _min.webp du CDN → original.
 * Les SVG, data: et /preview.php sont laissés tels quels.
 */
function img_src(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '' || str_contains($path, '/preview.php') || str_contains($path, '/img.php') || str_starts_with($path, 'data:')) {
        return $path;
    }
    // URL distante → telle quelle (déjà servie par un CDN).
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $rel = ltrim($path, '/');
    // 1) Variante WebP locale (statique, légère) — la plus rapide.
    if (preg_match('#\.(png|jpe?g)$#i', $rel)) {
        $webp = preg_replace('#\.(png|jpe?g)$#i', '.webp', $rel);
        if (is_file(ROOT_PATH . '/' . $webp)) {
            return BASE_URL . '/' . $webp;
        }
    }
    // 2) Fichier original local.
    if (is_file(ROOT_PATH . '/' . $rel)) {
        return $path;
    }
    // 3) Sinon, l'original du CDN (CloudFront, fiable et rapide). PAS de variante
    //    devinée (_min.webp) : elle n'existe pas toujours → requêtes en échec = lenteur.
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
    // On NE devine PLUS de variante CDN (_min.webp) : elle n'existe pas toujours et
    // un <source> en 404 casse la balise <picture> (image vide). Seul le WebP LOCAL
    // (généré par optimize-images.php) est utilisé comme source WebP fiable.
    if (preg_match('#^https?://#i', $src)) {
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

/** <picture> avec variante WebP légère + repli PNG/JPEG, pour une image locale ou CDN. */
function picture_html(string $img, string $alt = '', string $class = '', string $attrs = 'loading="lazy" decoding="async"'): string
{
    $src = img_src($img);
    if ($src === '') {
        return '';
    }
    $cls = $class !== '' ? ' class="' . e($class) . '"' : '';
    $tag = '<img' . $cls . ' src="' . e($src) . '" alt="' . e($alt) . '" ' . $attrs . '>';
    $webp = webp_variant($src);
    return $webp !== '' ? '<picture><source srcset="' . e($webp) . '" type="image/webp">' . $tag . '</picture>' : $tag;
}

/** Bloc média d'une carte : image réelle si dispo, sinon emoji sur dégradé. */
/**
 * Portrait IA d'un personnage (généré via Higgsfield, style Vice City néon).
 * Priorité au fichier LOCAL s'il a été rapatrié (fetch-character-images.php),
 * sinon l'image servie par le CDN. '' si le personnage n'a pas de portrait.
 */
function character_image(string $name): string
{
    $key = mb_strtolower(trim($name));
    $cdn = [
        'lucia'      => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_211644_aff2f239-80b7-48cf-bdee-3faf6271dc8a_min.webp',
        'jason'      => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_211647_99550546-15b5-42f2-abad-bce54c1be2a2_min.webp',
        'le maire'   => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_210421_0c3c9be9-cf79-491f-9dc9-efaf672aa214_min.webp',
        'dj solaris' => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_210423_d3a12ac6-212d-49bd-9692-a9859d2db0f7_min.webp',
    ];
    if (!isset($cdn[$key])) {
        return '';
    }
    $slug  = ['lucia' => 'lucia', 'jason' => 'jason', 'le maire' => 'le-maire', 'dj solaris' => 'dj-solaris'][$key] ?? '';
    $local = 'public/assets/img/characters/' . $slug . '.webp';
    if ($slug !== '' && is_file(ROOT_PATH . '/' . $local)) {
        return '/' . $local;
    }
    return $cdn[$key];
}

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
    $byId = db()->prepare('SELECT id, name, price, digital_file FROM products WHERE id = ? LIMIT 1');
    $push = static function (array $p, int $qty) use (&$items) {
        if (empty($p['digital_file'])) {
            return;
        }
        $items[] = [
            'id'           => (int) $p['id'],
            'name'         => $p['name'],
            'qty'          => max(1, $qty),
            'price'        => (float) $p['price'],
            'digital_file' => $p['digital_file'],
        ];
    };
    foreach (array_filter(array_map('trim', explode(',', $spec))) as $pair) {
        [$id, $qty] = array_pad(explode(':', $pair, 2), 2, '1');
        $id = (int) $id;
        $qty = max(1, (int) $qty);
        if ($id <= 0) {
            continue;
        }
        // Un bundle s'expanse en ses produits constituants (livrés ensemble).
        $bs = db()->prepare('SELECT bundle_items FROM products WHERE id = ? LIMIT 1');
        $bs->execute([$id]);
        $spec2 = (string) ($bs->fetchColumn() ?: '');
        if ($spec2 !== '') {
            foreach (array_filter(array_map('intval', explode(',', $spec2))) as $cid) {
                $byId->execute([$cid]);
                if ($p = $byId->fetch()) {
                    $push($p, $qty);
                }
            }
            continue;
        }
        $byId->execute([$id]);
        if ($p = $byId->fetch()) {
            $push($p, $qty);
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
