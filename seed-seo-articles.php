<?php
/**
 * ViceHub X — Insertion d'un lot d'articles SEO pré-rédigés (FR + traduction EN).
 * Lit database/seo-articles-data.json et insère chaque article FR (publié) + sa
 * version anglaise (lang=en, source_id, slug anglais). IDEMPOTENT : saute un
 * article dont le slug FR existe déjà → on peut relancer sans créer de doublon.
 *   Accès : en CLI, ou admin connecté, ou ?key=AI_TICK_KEY.
 */
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/ai.php';

@set_time_limit(0);

$cli = (PHP_SAPI === 'cli');
if (!$cli) {
    $key      = (string) ($_GET['key'] ?? '');
    $expected = (string) (getenv('AI_TICK_KEY') ?: get_setting('ai_tick_key', ''));
    $adminOk  = is_logged_in() && is_admin();
    if (!$adminOk && ($expected === '' || !hash_equals($expected, $key))) {
        http_response_code(403);
        exit("forbidden\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
}

ai_ensure_source_col();

$file = ROOT_PATH . '/database/seo-articles-data.json';
if (!is_file($file)) { exit("Fichier introuvable : database/seo-articles-data.json\n"); }
$data = json_decode((string) file_get_contents($file), true);
if (!is_array($data)) { exit("JSON invalide dans seo-articles-data.json\n"); }

$author = ai_admin_author_id();
$now    = time();
$allowed = '<p><h2><h3><ul><ol><li><strong><em><blockquote><br>';
$ins = 0; $insEn = 0; $skip = 0; $i = 0;

$chk = db()->prepare('SELECT 1 FROM articles WHERE slug = ? LIMIT 1');

foreach ($data as $item) {
    if (empty($item['fr']['titre']) || empty($item['fr']['corps'])) { continue; }
    $cat   = ai_cat_id((string) ($item['categorie'] ?? 'blog'));
    $theme = (string) ($item['theme_image'] ?? '');

    $frTitle = trim((string) $item['fr']['titre']);
    $baseSlug = slugify($frTitle);
    $chk->execute([$baseSlug]);
    if ($chk->fetchColumn()) { $skip++; $i++; continue; }   // déjà en base → on saute

    $slug    = unique_slug($frTitle, 'articles');
    $img     = '/public/assets/img/scenes/' . ai_pick_image($theme, $frTitle);
    $body    = clean_ai_markers(strip_tags((string) $item['fr']['corps'], $allowed));
    $excerpt = clean_ai_markers(mb_substr(trim((string) ($item['fr']['extrait'] ?? '')), 0, 200));
    if ($excerpt === '') { $excerpt = mb_substr(strip_tags($body), 0, 160); }
    // Dates échelonnées vers le passé (~7 h d'écart) : rend le lot naturel pour Google.
    $pub = date('Y-m-d H:i:s', $now - $i * 7 * 3600);

    $st = db()->prepare(
        'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, author_id, status, published_at, created_at)
         VALUES (?, "fr", ?, ?, ?, ?, ?, ?, "published", ?, NOW())'
    );
    $st->execute([$cat, $frTitle, $slug, $excerpt, $body, $img, $author, $pub]);
    $frId = (int) db()->lastInsertId();
    $ins++;

    // Version anglaise liée (URL anglaise, même image/catégorie).
    if (!empty($item['en']['titre']) && !empty($item['en']['corps'])) {
        $enTitle   = trim((string) $item['en']['titre']);
        $enSlug    = unique_slug($enTitle, 'articles');
        $enBody    = clean_ai_markers(strip_tags((string) $item['en']['corps'], $allowed));
        $enExcerpt = clean_ai_markers(mb_substr(trim((string) ($item['en']['extrait'] ?? '')), 0, 200));
        if ($enExcerpt === '') { $enExcerpt = mb_substr(strip_tags($enBody), 0, 160); }
        $ste = db()->prepare(
            'INSERT INTO articles (category_id, lang, source_id, title, slug, excerpt, body, image, author_id, status, published_at, created_at)
             VALUES (?, "en", ?, ?, ?, ?, ?, ?, ?, "published", ?, NOW())'
        );
        $ste->execute([$cat, $frId, $enTitle, $enSlug, $enExcerpt, $enBody, $img, $author, $pub]);
        $insEn++;
    }
    $i++;
}

echo "✅ Terminé.\n";
echo "Articles FR insérés : {$ins}\n";
echo "Traductions EN insérées : {$insEn}\n";
echo "Déjà existants (sautés) : {$skip}\n";
echo "Total en base maintenant : " . (int) db()->query("SELECT COUNT(*) FROM articles")->fetchColumn() . " articles.\n";
