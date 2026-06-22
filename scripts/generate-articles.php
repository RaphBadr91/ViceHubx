<?php
/**
 * ViceHub X — Générateur d'articles SEO par Anthropic (Claude).
 *
 * Rédige des articles GTA VI ultra professionnels, optimisés SEO,
 * dans la peau d'un expert / journaliste / streamer jeux vidéo, puis
 * les insère en base au statut "draft" (à valider dans l'admin).
 *
 * Prérequis :
 *   export ANTHROPIC_API_KEY="sk-ant-..."
 *
 * Exemples :
 *   php scripts/generate-articles.php --count=3
 *   php scripts/generate-articles.php --lang=en --persona=streamer "GTA 6 map size"
 *   php scripts/generate-articles.php --category=guides --persona=expert "Bien débuter à Vice City"
 *
 * Options :
 *   --count=N        Nombre d'articles si aucun sujet n'est fourni (def. 3)
 *   --lang=fr|en     Langue (def. fr)
 *   --category=...   news | guides | leaks (def. news)
 *   --persona=...    journaliste | streamer | passionne | expert (def. journaliste)
 *   --model=...      Modèle Claude (def. claude-opus-4-8)
 *   --publish        Publier directement (sinon : draft)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Ce script s'exécute en ligne de commande uniquement.\n");
}
require_once __DIR__ . '/../config/config.php';

/* ------------------------------------------------------------------ */
/*  Arguments                                                          */
/* ------------------------------------------------------------------ */
$opts = [
    'count' => 3, 'lang' => 'fr', 'category' => 'news',
    'persona' => 'journaliste', 'model' => 'claude-opus-4-8', 'publish' => false,
];
$topics = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    } else {
        $topics[] = $arg;
    }
}
$lang     = in_array($opts['lang'], ['fr', 'en'], true) ? $opts['lang'] : 'fr';
$category = in_array($opts['category'], ['news', 'guides', 'leaks'], true) ? $opts['category'] : 'news';
$model    = (string) $opts['model'];
$publish  = (bool) $opts['publish'];

$apiKey = getenv('ANTHROPIC_API_KEY');
if (!$apiKey) {
    exit("✗ Définissez ANTHROPIC_API_KEY (export ANTHROPIC_API_KEY=\"sk-ant-...\").\n");
}

/* ------------------------------------------------------------------ */
/*  Personas (voix éditoriale)                                         */
/* ------------------------------------------------------------------ */
$personas = [
    'journaliste' => "un JOURNALISTE jeux vidéo chevronné, spécialiste de la saga GTA, rigoureux et factuel, qui sait vulgariser sans jamais survendre.",
    'streamer'    => "un STREAMER GTA V/VI très suivi, dynamique et complice avec sa communauté, qui partage astuces et hype tout en restant crédible.",
    'passionne'   => "un FAN PASSIONNÉ de GTA, enthousiaste et pointu, qui connaît la licence par cœur et transmet son excitation.",
    'expert'      => "un ANALYSTE/SPÉCIALISTE jeux vidéo, expert technique de GTA VI, précis, qui décortique les détails et le game design.",
];
$personaKey  = array_key_exists($opts['persona'], $personas) ? $opts['persona'] : 'journaliste';
$personaDesc = $personas[$personaKey];

/* ------------------------------------------------------------------ */
/*  Sujets par défaut (si aucun fourni)                               */
/* ------------------------------------------------------------------ */
if (!$topics) {
    $pool = $lang === 'fr'
        ? [
            'Date de sortie de GTA VI : tout ce que l’on sait',
            'GTA VI : la carte de Leonida et Vice City décryptée',
            'GTA VI : Jason et Lucia, ce que l’on sait des protagonistes',
            'GTA VI sur PS5 et Xbox Series : performances attendues',
            'GTA VI : les véhicules et la conduite nouvelle génération',
            'GTA VI Online : à quoi s’attendre pour le multijoueur',
            'GTA VI : différences majeures avec GTA V',
            'GTA VI : analyse complète des trailers',
        ]
        : [
            'GTA VI release date: everything we know',
            'GTA VI map: Leonida and Vice City explained',
            'GTA VI: what we know about Jason and Lucia',
            'GTA VI on PS5 and Xbox Series: expected performance',
            'GTA VI: next-gen vehicles and driving',
            'GTA VI Online: what to expect from multiplayer',
            'GTA VI vs GTA V: the major differences',
            'GTA VI: full trailer breakdown',
        ];
    shuffle($pool);
    $topics = array_slice($pool, 0, max(1, (int) $opts['count']));
}

/* ------------------------------------------------------------------ */
/*  Prompt système (E-E-A-T + SEO)                                    */
/* ------------------------------------------------------------------ */
$today = date('d/m/Y');
$system = <<<SYS
Tu es {$personaDesc}
Tu écris pour ViceHub X, un média indépendant NON OFFICIEL dédié à GTA VI.
Langue de rédaction OBLIGATOIRE : {$lang}.
Date du jour : {$today}. Sortie de GTA VI annoncée : 19 novembre 2026.

OBJECTIF : produire des articles de qualité PROFESSIONNELLE, optimisés SEO (E-E-A-T),
agréables à lire, structurés, et honnêtes sur le niveau de certitude.

RÈGLES :
- Ne JAMAIS te présenter comme Rockstar Games, Take-Two ou un porte-parole officiel.
- Distinguer clairement le confirmé, le probable, la rumeur et l'analyse (champ "badge").
- Ne pas inventer de faits "confirmés". En cas de doute, formuler comme rumeur/analyse.
- Ton expert mais accessible ; phrases claires ; éviter le bourrage de mots-clés.
- Pas de promesses commerciales mensongères, pas de fausses dates.

SEO & STRUCTURE (dans body_html) :
- Un chapô d'accroche (1 court paragraphe <p>).
- 3 à 6 sections avec des <h2> (et <h3> si utile) au champ lexical riche.
- Paragraphes <p> aérés, au moins une liste <ul><li>.
- Une section finale "<h2>FAQ</h2>" avec 3 questions/réponses (<h3> + <p>).
- HTML simple et propre UNIQUEMENT : <p> <h2> <h3> <ul> <li> <strong> <em>.
  Pas de <script>, pas de styles, pas d'images, pas de <h1> (le titre est géré à part).

CHAMPS À RENVOYER :
- title : titre accrocheur et optimisé (≤ 70 caractères).
- slug : minuscules, mots séparés par des tirets, sans accents.
- excerpt : méta-description SEO percutante (≤ 155 caractères).
- body_html : l'article complet en HTML (voir structure ci-dessus).
- category : {$category}.
- badge : confirmed | official | probable | rumor | analysis | leak | fake | none.
- tags : 4 à 7 mots-clés pertinents.
SYS;

/* ------------------------------------------------------------------ */
/*  Schéma de sortie structurée                                       */
/* ------------------------------------------------------------------ */
$schema = [
    'type' => 'object',
    'properties' => [
        'title'     => ['type' => 'string'],
        'slug'      => ['type' => 'string'],
        'excerpt'   => ['type' => 'string'],
        'body_html' => ['type' => 'string'],
        'category'  => ['type' => 'string', 'enum' => ['news', 'guides', 'leaks']],
        'badge'     => ['type' => 'string', 'enum' => ['confirmed', 'official', 'probable', 'rumor', 'analysis', 'leak', 'fake', 'none']],
        'tags'      => ['type' => 'array', 'items' => ['type' => 'string']],
    ],
    'required' => ['title', 'slug', 'excerpt', 'body_html', 'category', 'badge', 'tags'],
    'additionalProperties' => false,
];

/* ------------------------------------------------------------------ */
/*  Appel API Claude (HTTP brut via cURL)                            */
/* ------------------------------------------------------------------ */
function claude_article(string $apiKey, string $model, string $system, array $schema, string $topic): array
{
    $payload = [
        'model'      => $model,
        'max_tokens' => 8000,
        'system'     => $system,
        'messages'   => [[
            'role' => 'user',
            'content' => "Rédige l'article complet sur le sujet suivant : \"{$topic}\". "
                . "Respecte la structure SEO et renvoie uniquement les champs demandés.",
        ]],
        'output_config' => ['format' => ['type' => 'json_schema', 'schema' => $schema]],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new RuntimeException("Erreur réseau : {$err}");
    }
    $data = json_decode($resp, true);
    if ($code !== 200) {
        $msg = $data['error']['message'] ?? $resp;
        throw new RuntimeException("HTTP {$code} : {$msg}");
    }
    // Concatène les blocs texte de la réponse
    $text = '';
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= $block['text'];
        }
    }
    $article = json_decode($text, true);
    if (!is_array($article)) {
        throw new RuntimeException('Réponse non JSON : ' . substr($text, 0, 200));
    }
    return $article;
}

/* ------------------------------------------------------------------ */
/*  Insertion en base                                                 */
/* ------------------------------------------------------------------ */
function category_id_for(string $slug): ?int
{
    $stmt = db()->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

function unique_slug(string $base): string
{
    $base = slugify($base);
    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = db()->prepare('SELECT 1 FROM articles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

/* ------------------------------------------------------------------ */
/*  Boucle principale                                                 */
/* ------------------------------------------------------------------ */
echo "ViceHub X — génération SEO (" . count($topics) . " article(s), persona: {$personaKey}, modèle: {$model})\n";
$ok = 0;
foreach ($topics as $topic) {
    echo "→ « {$topic} » … ";
    try {
        $a = claude_article($apiKey, $model, $system, $schema, $topic);

        $cat = in_array($a['category'] ?? '', ['news', 'guides', 'leaks'], true) ? $a['category'] : $category;
        $catId = category_id_for($cat);
        $badge = ($a['badge'] ?? 'none') === 'none' ? null : $a['badge'];
        $slug  = unique_slug($a['slug'] ?? ($a['title'] ?? $topic));
        $status = $publish ? 'published' : 'draft';
        $pub = $publish ? date('Y-m-d H:i:s') : null;

        // Visuel : image générée par IA (récupérée via make-hero-video.sh)
        $scene_pool = $cat === 'guides'
            ? ['beach-cruise', 'marina', 'night']
            : ['aerial', 'night', 'marina', 'plane', 'heli', 'police'];
        $image = '/public/assets/img/scenes/' . $scene_pool[array_rand($scene_pool)] . '.png';

        $stmt = db()->prepare(
            'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, badge, image, status, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $catId, $lang,
            mb_substr((string) ($a['title'] ?? $topic), 0, 200),
            $slug,
            mb_substr((string) ($a['excerpt'] ?? ''), 0, 400),
            (string) ($a['body_html'] ?? ''),
            $badge, $image, $status, $pub,
        ]);
        $id = (int) db()->lastInsertId();

        // Tags
        foreach (array_slice((array) ($a['tags'] ?? []), 0, 8) as $tagName) {
            $tagName = trim((string) $tagName);
            if ($tagName === '') continue;
            $tslug = slugify($tagName);
            db()->prepare('INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)')->execute([$tagName, $tslug]);
            $tid = db()->query('SELECT id FROM tags WHERE slug = ' . db()->quote($tslug))->fetchColumn();
            if ($tid) {
                db()->prepare('INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)')->execute([$id, (int) $tid]);
            }
        }

        echo "✓ #{$id} [{$status}] {$slug}\n";
        $ok++;
    } catch (Throwable $ex) {
        echo "✗ " . $ex->getMessage() . "\n";
    }
}
echo "\nTerminé : {$ok}/" . count($topics) . " article(s) créés. À valider dans l'admin (Articles).\n";
