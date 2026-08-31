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

function veille_add_source(string $name, string $url, string $type): bool
{
    veille_ensure_tables();
    $name = trim($name); $url = trim($url);
    $type = in_array($type, ['rss', 'sitemap'], true) ? $type : 'rss';
    if ($name === '' || !preg_match('#^https?://#i', $url)) { return false; }
    try {
        db()->prepare('INSERT INTO competitor_sources (name, url, type) VALUES (?, ?, ?)')->execute([$name, $url, $type]);
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
    $ins = db()->prepare(
        'INSERT IGNORE INTO competitor_items (source_id, title, url, published_at) VALUES (?, ?, ?, ?)'
    );
    $new = 0;
    foreach (array_slice($items, 0, 60) as [$title, $url, $date]) {
        try {
            $ins->execute([(int) $src['id'], mb_substr($title, 0, 300), mb_substr($url, 0, 500), $date]);
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
