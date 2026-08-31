<?php
/**
 * ViceHub X — Veille concurrentielle d'articles.
 *
 * Surveille les FLUX PUBLICS (RSS/Atom ou sitemap) de sites concurrents pour
 * lister les sujets qu'ils publient. On ne stocke que le TITRE + le LIEN
 * (métadonnées publiques) : ce sont des IDÉES DE SUJETS. On écrit ENSUITE nos
 * propres articles, à notre manière et sous l'angle GTA VI (aucun copier-coller).
 */

require_once __DIR__ . '/../config/config.php';

/** Crée les tables de veille si besoin (best-effort). */
function veille_ensure_tables(): void
{
    static $done = false;
    if ($done) { return; }
    $done = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS competitor_sources (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(120) NOT NULL,
                url        VARCHAR(500) NOT NULL,
                type       ENUM('rss','sitemap') NOT NULL DEFAULT 'rss',
                lang       ENUM('fr','en') NOT NULL DEFAULT 'en',
                active     TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS competitor_items (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                source_id    INT,
                title        VARCHAR(300) NOT NULL,
                url          VARCHAR(500) NOT NULL UNIQUE,
                published_at DATETIME NULL,
                status       ENUM('new','ignored','written') NOT NULL DEFAULT 'new',
                lang         ENUM('fr','en') NOT NULL DEFAULT 'en',
                seen_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status (status)
            ) ENGINE=InnoDB"
        );
    } catch (Throwable $e) { /* droits DDL limités */ }
}

/** Liste des sources (toutes, ou actives seulement). */
function veille_sources(bool $activeOnly = false): array
{
    veille_ensure_tables();
    try {
        $sql = 'SELECT * FROM competitor_sources' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY name ASC';
        return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) { return []; }
}

/** Le titre parle-t-il de GTA 6 ? (filtre : on ne garde QUE les sujets GTA 6). */
function veille_is_gta6(string $text): bool
{
    return (bool) preg_match('/\bgta\s?6\b|\bgta\s?vi\b|grand\s+theft\s+auto\s+(6|vi|six)|vice\s+city/i', $text);
}

function veille_add_source(string $name, string $url, string $type, string $lang = 'en'): bool
{
    veille_ensure_tables();
    $name = trim($name); $url = trim($url);
    $type = in_array($type, ['rss', 'sitemap'], true) ? $type : 'rss';
    $lang = in_array($lang, ['fr', 'en'], true) ? $lang : 'en';
    if ($name === '' || !preg_match('#^https?://#i', $url)) { return false; }
    // Anti-SSRF (léger) : refuse les hôtes internes/loopback même côté admin.
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1', '169.254.169.254', 'metadata.google.internal'], true)
        || preg_match('/^(10|127)\./', $host) || preg_match('/^192\.168\./', $host)
        || preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $host)) {
        return false;
    }
    try {
        db()->prepare('INSERT INTO competitor_sources (name, url, type, lang) VALUES (?, ?, ?, ?)')->execute([$name, $url, $type, $lang]);
        return true;
    } catch (Throwable $e) { return false; }
}

function veille_delete_source(int $id): void
{
    veille_ensure_tables();
    try { db()->prepare('DELETE FROM competitor_sources WHERE id = ?')->execute([$id]); } catch (Throwable $e) {}
}

/** Titre lisible déduit d'une URL (pour les sitemaps sans titre). */
function veille_title_from_url(string $url): string
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    $slug = trim((string) (array_slice(array_filter(explode('/', $path)), -1)[0] ?? ''), '/');
    $slug = preg_replace('/\.(html?|php)$/i', '', $slug);
    $slug = str_replace(['-', '_'], ' ', $slug);
    return ucfirst(trim($slug)) ?: $url;
}

/** Parse un flux RSS 2.0 ou Atom → [ [title, url, date|null], ... ]. */
function veille_parse_feed(string $xml): array
{
    $out = [];
    $prev = libxml_use_internal_errors(true);
    $sx = simplexml_load_string($xml);
    libxml_use_internal_errors($prev);
    if (!$sx) { return $out; }
    // RSS 2.0
    if (isset($sx->channel->item)) {
        foreach ($sx->channel->item as $it) {
            $title = trim((string) $it->title);
            $link  = trim((string) $it->link);
            $date  = trim((string) $it->pubDate);
            if ($title !== '' && $link !== '') {
                $out[] = [$title, $link, $date !== '' ? date('Y-m-d H:i:s', strtotime($date) ?: time()) : null];
            }
        }
        return $out;
    }
    // Atom
    if (isset($sx->entry)) {
        foreach ($sx->entry as $e) {
            $title = trim((string) $e->title);
            $link = '';
            foreach ($e->link as $l) {
                $rel = (string) $l['rel'];
                if ($rel === '' || $rel === 'alternate') { $link = trim((string) $l['href']); break; }
            }
            $date = trim((string) ($e->updated ?? $e->published ?? ''));
            if ($title !== '' && $link !== '') {
                $out[] = [$title, $link, $date !== '' ? date('Y-m-d H:i:s', strtotime($date) ?: time()) : null];
            }
        }
    }
    return $out;
}

/** Parse un sitemap XML → [ [title(dérivé), url, lastmod|null], ... ]. */
function veille_parse_sitemap(string $xml): array
{
    $out = [];
    $prev = libxml_use_internal_errors(true);
    $sx = simplexml_load_string($xml);
    libxml_use_internal_errors($prev);
    if (!$sx || !isset($sx->url)) { return $out; }
    foreach ($sx->url as $u) {
        $loc = trim((string) $u->loc);
        if ($loc === '') { continue; }
        $mod = trim((string) $u->lastmod);
        $out[] = [veille_title_from_url($loc), $loc, $mod !== '' ? date('Y-m-d H:i:s', strtotime($mod) ?: time()) : null];
    }
    return $out;
}

/** Récupère + parse + insère les nouveautés d'une source. Retourne le nb de NOUVEAUX items. */
function veille_fetch_source(array $src): int
{
    veille_ensure_tables();
    $body = http_get((string) $src['url']);
    if ($body === null || $body === '') { return 0; }
    $items = ($src['type'] === 'sitemap') ? veille_parse_sitemap($body) : veille_parse_feed($body);
    if (!$items) { return 0; }
    $lang = in_array($src['lang'] ?? 'en', ['fr', 'en'], true) ? $src['lang'] : 'en';
    $ins = db()->prepare(
        'INSERT IGNORE INTO competitor_items (source_id, title, url, published_at, lang) VALUES (?, ?, ?, ?, ?)'
    );
    $new = 0;
    foreach (array_slice($items, 0, 80) as [$title, $url, $date]) {
        if (!veille_is_gta6($title)) { continue; }   // ne conserve QUE les sujets GTA 6
        try {
            $ins->execute([(int) $src['id'], mb_substr($title, 0, 300), mb_substr($url, 0, 500), $date, $lang]);
            if ($ins->rowCount() > 0) { $new++; }
        } catch (Throwable $e) { /* url trop longue / doublon */ }
    }
    return $new;
}

/** Rafraîchit toutes les sources actives. Retourne le nb total de nouveaux items. */
function veille_fetch_all(): int
{
    $total = 0;
    foreach (veille_sources(true) as $src) { $total += veille_fetch_source($src); }
    return $total;
}

/** Items de veille (par statut), plus récents d'abord. */
function veille_items(string $status = 'new', int $limit = 80): array
{
    veille_ensure_tables();
    try {
        $q = db()->prepare(
            'SELECT i.*, s.name AS source_name FROM competitor_items i
             LEFT JOIN competitor_sources s ON s.id = i.source_id
             WHERE i.status = ? ORDER BY i.published_at DESC, i.id DESC LIMIT ?'
        );
        $q->bindValue(1, $status);
        $q->bindValue(2, max(1, $limit), PDO::PARAM_INT);
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) { return []; }
}

function veille_item(int $id): ?array
{
    veille_ensure_tables();
    try {
        $q = db()->prepare('SELECT * FROM competitor_items WHERE id = ?');
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) { return null; }
}

function veille_set_item_status(int $id, string $status): void
{
    if (!in_array($status, ['new', 'ignored', 'written'], true)) { return; }
    veille_ensure_tables();
    try { db()->prepare('UPDATE competitor_items SET status = ? WHERE id = ?')->execute([$status, $id]); } catch (Throwable $e) {}
}

function veille_counts(): array
{
    veille_ensure_tables();
    $c = ['new' => 0, 'written' => 0, 'ignored' => 0];
    try {
        foreach (db()->query('SELECT status, COUNT(*) n FROM competitor_items GROUP BY status')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $c[$r['status']] = (int) $r['n'];
        }
    } catch (Throwable $e) {}
    return $c;
}

/** L'auto-publication de la veille est-elle activée ? */
function veille_is_auto(): bool
{
    return get_setting('veille_auto', '0') === '1';
}

/**
 * Lance NOTRE version des $max sujets « new » les plus récents (publication directe
 * pour être premier). Plafonné pour ne pas inonder la file ni exploser les coûts IA.
 * Retourne le nombre d'articles mis en génération.
 */
function veille_auto_generate(int $max = 3): int
{
    veille_ensure_tables();
    if (!function_exists('ai_brief_add')) {
        if (defined('ROOT_PATH') && is_file(ROOT_PATH . '/includes/ai.php')) { require_once ROOT_PATH . '/includes/ai.php'; }
    }
    if (!function_exists('ai_brief_add')) { return 0; }
    $done = 0;
    try {
        // Fraîcheur : on ne réécrit que l'actu RÉCENTE (évite d'inonder avec un vieux backlog).
        $q = db()->prepare(
            "SELECT id, title, lang FROM competitor_items
             WHERE status = 'new'
               AND (published_at >= NOW() - INTERVAL 3 DAY
                    OR (published_at IS NULL AND seen_at >= NOW() - INTERVAL 1 DAY))
             ORDER BY published_at DESC, id DESC LIMIT ?"
        );
        $q->bindValue(1, max(1, $max), PDO::PARAM_INT);
        $q->execute();
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $it) {
            $lang  = in_array($it['lang'] ?? 'en', ['fr', 'en'], true) ? $it['lang'] : 'en';
            $topic = $lang === 'fr'
                ? $it['title'] . ' — actu GTA 6, réécrite à notre manière (angle ViceHub X), 100% originale'
                : $it['title'] . ' — GTA 6 news, rewritten our way (ViceHub X angle), 100% original';
            ai_brief_add([$topic], 'published', $lang);   // publication directe = on est premier
            veille_set_item_status((int) $it['id'], 'written');
            $done++;
        }
        if ($done > 0 && function_exists('ai_spawn_worker')) { ai_spawn_worker(); }
    } catch (Throwable $e) { /* silencieux */ }
    return $done;
}

/**
 * Tick appelé par le heartbeat public : récupère les nouveaux sujets GTA 6 et, si
 * l'auto-publication est activée, lance NOTRE version. Auto-throttlé (~30 min).
 */
function veille_auto_tick(): void
{
    try {
        $last = (int) get_setting('veille_hb', '0');
        if (time() - $last < 1800) { return; }        // au plus une fois toutes les 30 min
        set_setting('veille_hb', (string) time());
    } catch (Throwable $e) { return; }
    veille_fetch_all();
    if (veille_is_auto()) { veille_auto_generate(3); } // max 3 articles auto par cycle
}
