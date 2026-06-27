<?php
/**
 * ViceHub X — Librairie IA (génération d'articles dans la niche GTA VI / Vice City).
 * Utilise l'API Anthropic (clé : réglage 'anthropic_key' ou env ANTHROPIC_API_KEY).
 * Chaque article généré reçoit :
 *   - une illustration piochée dans la banque d'images IA (CDN) ;
 *   - un PROMPT IMAGE (stocké en OFF, admin-only) prêt pour Higgsfield, afin de
 *     produire une illustration sur-mesure encore plus pro.
 * Réutilisé par admin/ai-articles.php et scripts/gen-ai-articles.php.
 */

/** Clé API Anthropic (env prioritaire, sinon réglage admin). */
function ai_key(): string
{
    return getenv('ANTHROPIC_API_KEY') ?: (string) get_setting('anthropic_key', '');
}

function ai_enabled(): bool
{
    return ai_key() !== '';
}

/** Modèle par défaut (réglable). */
function ai_model(): string
{
    return get_setting('ai_model', '') ?: 'claude-haiku-4-5-20251001';
}

/** Appel minimal à l'API Anthropic Messages — renvoie le texte, lève en cas d'erreur. */
function anthropic_complete(string $system, string $user, int $maxTok = 1200, ?string $model = null): string
{
    $key = ai_key();
    if ($key === '') {
        throw new RuntimeException('Clé API Anthropic manquante.');
    }
    $payload = [
        'model'    => $model ?: ai_model(),
        'max_tokens' => $maxTok,
        'system'   => $system,
        'messages' => [['role' => 'user', 'content' => $user]],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['content-type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 90,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException('Connexion API échouée : ' . $err);
    }
    if ($code >= 400) {
        throw new RuntimeException('Anthropic ' . $code . ' : ' . substr((string) $raw, 0, 300));
    }
    $d = json_decode($raw, true);
    return trim($d['content'][0]['text'] ?? '');
}

/** Banque d'images IA : thème → fichiers CDN disponibles. */
function ai_image_bank(): array
{
    return [
        'night'     => ['night.png', 'rain-neon.png', 'heli-night.png'],
        'city'      => ['downtown.png', 'artdeco.png', 'peninsula.png', 'bridge.png'],
        'beach'     => ['beachlife.png', 'beach-sunset.png', 'beach-cruise.png', 'boardwalk.png'],
        'car'       => ['veh-supercar.png', 'veh-muscle.png', 'drift.png', 'muscle-diner.png', 'gas-station.png'],
        'police'    => ['police.png', 'heli.png'],
        'heli'      => ['heli.png', 'heli-night.png'],
        'marina'    => ['marina.png', 'marina-aerial.png', 'veh-boat.png'],
        'storm'     => ['storm.png', 'desert-road.png'],
        'casino'    => ['casino.png'],
        'nightlife' => ['nightlife.png', 'pool-party.png', 'street-market.png'],
        'drift'     => ['drift.png'],
        'sunset'    => ['sunset-cruise.png', 'ocean-drive.png', 'beach-sunset.png'],
        'market'    => ['street-market.png', 'graffiti.png'],
        'plane'     => ['plane.png', 'aerial.png'],
        'swamp'     => ['airboat.png', 'veh-swamp.png'],
    ];
}

/** Choisit une illustration de la banque selon le thème (ou le titre en repli). */
function ai_pick_image(string $theme, string $title = ''): string
{
    $bank = ai_image_bank();
    $theme = strtolower(trim($theme));
    if (isset($bank[$theme])) {
        $opts = $bank[$theme];
        return $opts[array_rand($opts)];
    }
    // Repli : déduction par mots-clés du titre.
    $t = mb_strtolower($title);
    $hints = [
        'nuit' => 'night', 'soir' => 'night', 'carte' => 'city', 'map' => 'city', 'ville' => 'city',
        'voiture' => 'car', 'véhicule' => 'car', 'bagnole' => 'car', 'plage' => 'beach', 'mer' => 'beach',
        'police' => 'police', 'casino' => 'casino', 'marina' => 'marina', 'bateau' => 'marina',
        'orage' => 'storm', 'tempête' => 'storm', 'club' => 'nightlife', 'fête' => 'nightlife',
    ];
    foreach ($hints as $kw => $th) {
        if (mb_strpos($t, $kw) !== false && isset($bank[$th])) {
            return $bank[$th][array_rand($bank[$th])];
        }
    }
    // Repli final : une scène au hasard.
    $all = array_merge(...array_values($bank));
    return $all[array_rand($all)];
}

/** Pool de sujets de la niche (rotation pour varier les angles). */
function ai_topics(): array
{
    return [
        'la date de sortie et le compte à rebours',
        'les éditions Standard et Ultimate et leur rapport qualité-prix',
        'la précommande et le Vintage Vice City Pack',
        'le duo Jason Duval et Lucia Caminos',
        'la carte de Leonida et ses lieux (Port Gellhorn, Mount Kalaga, les Keys)',
        'Vice City et son ambiance néon des années 80',
        'les véhicules : supercars, muscle cars, bateaux, motos',
        'les radios et la bande-son (V-Rock, soundtrack des trailers)',
        'les activités : plongée, pêche, fight clubs, courses-poursuites',
        'la version PC et les plateformes',
        'le futur de GTA Online / multijoueur',
        'l’analyse du trailer 2 et ses détails cachés',
        'la comparaison GTA 6 vs GTA 5',
        'les personnages secondaires (Boobie Ike, Dre’Quan Priest, Real Dimez)',
        'la météo dynamique et les ouragans de Leonida',
        'les théories de la communauté et la fiabilité des leaks',
        'comment bien se préparer pour le jour de la sortie',
        'l’héritage de Vice City (2002) et la nostalgie de la saga',
        'la hype mondiale et les records des trailers',
        'les fonds d’écran et goodies pour fans de Vice City',
    ];
}

/** Catégorie (slug) → id. */
function ai_cat_id(string $slug): int
{
    $map = ['news' => 1, 'guides' => 2, 'leaks' => 3, 'trailers' => 4, 'blog' => 5];
    return $map[strtolower($slug)] ?? 5;
}

/**
 * Génère UN article via l'IA. Retourne un tableau structuré ou lève une exception.
 * @return array{title:string,excerpt:string,body:string,image:string,image_prompt:string,category:string}
 */
function ai_generate_article(?string $topic = null): array
{
    $topics = ai_topics();
    $topic  = $topic ?: $topics[array_rand($topics)];

    $system = 'Tu es rédacteur SEO senior pour ViceHub X, un média de fans INDÉPENDANT et NON OFFICIEL '
        . 'dédié à GTA VI et Vice City. Tu écris en français, ton professionnel, factuel et passionné. '
        . 'Tu N’INVENTES JAMAIS d’information officielle (dates, prix, contenus non confirmés). '
        . 'Tu connais les faits : sortie le 19 novembre 2026 (PS5/Xbox Series), éditions Standard (79,99$) et '
        . 'Ultimate (99,99$), duo Jason Duval & Lucia Caminos, État de Leonida, Vice City, V-Rock.';

    $user = "Rédige un article original et professionnel sur le thème : « {$topic} ».\n"
        . "Réponds STRICTEMENT en JSON valide (aucun texte autour), avec EXACTEMENT ces clés :\n"
        . '{"categorie":"news|guides|leaks|blog|trailers","titre":"titre accrocheur et unique (<=90 caractères)",'
        . '"extrait":"résumé d\'une phrase (<=180 caractères)",'
        . '"corps_html":"3 à 5 paragraphes en HTML, balises autorisées <p> <h2> <ul> <li> <strong> <em> uniquement, '
        . 'AUCUN lien <a>, AUCUN markdown",'
        . '"prompt_image":"un prompt en ANGLAIS pour générer une illustration photoréaliste sur Higgsfield, '
        . 'ambiance GTA VI / Vice City néon, cinématographique, 16:9, no text, no logo",'
        . '"theme_image":"un seul mot parmi : night, city, beach, car, police, heli, marina, storm, casino, nightlife, drift, sunset, market, plane, swamp"}';

    $raw = anthropic_complete($system, $user, 1400);
    // Extrait le bloc JSON même si l'IA ajoute du texte autour.
    $start = strpos($raw, '{');
    $end   = strrpos($raw, '}');
    if ($start === false || $end === false || $end <= $start) {
        throw new RuntimeException('Réponse IA non exploitable.');
    }
    $json = json_decode(substr($raw, $start, $end - $start + 1), true);
    if (!is_array($json) || empty($json['titre']) || empty($json['corps_html'])) {
        throw new RuntimeException('JSON IA incomplet.');
    }

    $title   = trim((string) $json['titre']);
    $excerpt = mb_substr(trim((string) ($json['extrait'] ?? '')), 0, 200);
    $body    = strip_tags((string) $json['corps_html'], '<p><h2><h3><ul><ol><li><strong><em><blockquote><br>');
    $iprompt = trim((string) ($json['prompt_image'] ?? ''));
    $theme   = (string) ($json['theme_image'] ?? '');
    $cat     = (string) ($json['categorie'] ?? 'blog');

    return [
        'title'        => $title,
        'excerpt'      => $excerpt !== '' ? $excerpt : mb_substr(strip_tags($body), 0, 160),
        'body'         => $body,
        'image'        => ai_pick_image($theme, $title),
        'image_prompt' => $iprompt,
        'category'     => $cat,
    ];
}

/**
 * Enregistre un article généré. Idempotent (saute si le slug existe déjà).
 * @return int|null id inséré, ou null si doublon.
 */
function ai_save_article(array $data, string $status = 'draft', ?int $authorId = null): ?int
{
    $slug = slugify($data['title']);
    if ($slug === '') {
        return null;
    }
    $chk = db()->prepare('SELECT 1 FROM articles WHERE slug = ? LIMIT 1');
    $chk->execute([$slug]);
    if ($chk->fetchColumn()) {
        return null;
    }
    $status = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'draft';
    $pub = $status === 'published' ? date('Y-m-d H:i:s') : null;
    $st = db()->prepare(
        'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, image_prompt, author_id, status, published_at, created_at)
         VALUES (?, \'fr\', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $st->execute([
        ai_cat_id($data['category']), $data['title'], $slug, $data['excerpt'], $data['body'],
        $data['image'], $data['image_prompt'], $authorId, $status, $pub,
    ]);
    return (int) db()->lastInsertId();
}
