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

/* ============================================================================
 *  ILLUSTRATION IA SUR-MESURE (Higgsfield) — générée DIRECTEMENT à la création.
 *  Le serveur appelle l'API REST Higgsfield (platform.higgsfield.ai) avec le
 *  prompt image de l'article, télécharge le rendu, le compresse en WebP local
 *  et l'utilise comme illustration. Si la clé est absente ou l'appel échoue,
 *  on retombe proprement sur la banque d'images (aucune régression).
 *  Clé : réglage 'higgsfield_key' (format KEY_ID:KEY_SECRET) ou env HIGGSFIELD_KEY.
 * ========================================================================== */

/** Clé API Higgsfield (env prioritaire, sinon réglage admin). Format KEY_ID:KEY_SECRET. */
function ai_img_key(): string
{
    return getenv('HIGGSFIELD_KEY') ?: (string) get_setting('higgsfield_key', '');
}

/** La génération d'illustration IA est-elle active ? (activée + clé + GD WebP). */
function ai_img_enabled(): bool
{
    return (int) get_setting('ai_image_enabled', '0') === 1
        && ai_img_key() !== ''
        && function_exists('imagewebp');
}

/** Endpoint text-to-image Higgsfield (réglable si l'API évolue). */
function ai_img_endpoint(): string
{
    return get_setting('ai_image_endpoint', '') ?: 'https://platform.higgsfield.ai/v1/flux-pro/kontext/max/text-to-image';
}

/** Cherche récursivement la 1re URL d'image dans une réponse JSON (schéma tolérant). */
function ai_find_image_url($node): ?string
{
    if (is_string($node)) {
        if (preg_match('#^https?://[^\s"\']+#i', $node)
            && (preg_match('#\.(png|jpe?g|webp)(\?|$)#i', $node) || stripos($node, 'cloudfront') !== false || stripos($node, 'higgsfield') !== false)) {
            return $node;
        }
        return null;
    }
    if (is_array($node)) {
        // Priorité aux clés « url » habituelles.
        foreach (['url', 'image_url', 'output_url', 'raw'] as $k) {
            if (isset($node[$k])) {
                $u = ai_find_image_url($node[$k]);
                if ($u) { return $u; }
            }
        }
        foreach ($node as $v) {
            $u = ai_find_image_url($v);
            if ($u) { return $u; }
        }
    }
    return null;
}

/**
 * Génère une illustration IA (Higgsfield) à partir du prompt, la télécharge,
 * la compresse en WebP local et renvoie son chemin web. Ne lève JAMAIS :
 * renvoie null en cas d'échec (l'appelant garde alors l'image de la banque).
 */
function ai_generate_image(string $prompt, string $slug): ?string
{
    $prompt = trim($prompt);
    if ($prompt === '') { return null; }
    if (!ai_img_enabled()) {
        set_setting('ai_img_last_error', 'Illustrations IA désactivées, ou clé Higgsfield absente, ou GD/WebP indisponible sur le serveur.');
        return null;
    }
    $endpoint = ai_img_endpoint();
    $auth     = 'Authorization: Key ' . ai_img_key();
    try {
        $input = ['prompt' => $prompt, 'aspect_ratio' => '16:9', 'safety_tolerance' => 2];
        $res = trim((string) get_setting('ai_image_resolution', ''));
        if ($res !== '') { $input['resolution'] = $res; }
        // withPolling:true → l'API attend la fin et renvoie (idéalement) le rendu directement.
        $payload = json_encode(['input' => $input, 'withPolling' => true]);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['content-type: application/json', 'accept: application/json', $auth],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 180,
        ]);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($raw === false)  { set_setting('ai_img_last_error', "Connexion à {$endpoint} échouée : {$cerr}"); return null; }
        if ($code >= 400)    { set_setting('ai_img_last_error', "HTTP {$code} sur {$endpoint} — " . substr((string) $raw, 0, 500)); return null; }

        $data   = json_decode((string) $raw, true);
        $imgUrl = is_array($data) ? ai_find_image_url($data) : null;
        if (!$imgUrl && preg_match('#https?://[^\s"\']+\.(?:png|jpe?g|webp)#i', (string) $raw, $m)) { $imgUrl = $m[0]; }
        // API asynchrone : pas d'image mais un id/status_url → on interroge jusqu'au rendu.
        if (!$imgUrl && is_array($data)) { $imgUrl = ai_poll_image($data, $endpoint, $auth); }
        if (!$imgUrl) { set_setting('ai_img_last_error', "Réponse HTTP {$code} sans URL d'image — " . substr((string) $raw, 0, 500)); return null; }

        $local = ai_download_as_webp($imgUrl, $slug);
        if (!$local) { set_setting('ai_img_last_error', "Image reçue mais téléchargement/conversion WebP échoué : {$imgUrl}"); return null; }

        set_setting('ai_img_last_error', '');
        set_setting('ai_img_last_ok', date('Y-m-d H:i:s'));
        return $local;
    } catch (Throwable $e) {
        set_setting('ai_img_last_error', 'Exception : ' . $e->getMessage());
        return null;
    }
}

/** Recherche récursive de la 1re valeur scalaire pour l'un des noms de clés donnés. */
function ai_find_key($node, array $keys): ?string
{
    if (is_array($node)) {
        foreach ($keys as $k) {
            if (isset($node[$k]) && is_scalar($node[$k])) { return (string) $node[$k]; }
        }
        foreach ($node as $v) {
            $r = ai_find_key($v, $keys);
            if ($r !== null) { return $r; }
        }
    }
    return null;
}

/** Cas API asynchrone : interroge le status jusqu'à obtenir l'URL de l'image (≤150 s). */
function ai_poll_image(array $data, string $endpoint, string $auth): ?string
{
    $statusUrl = ai_find_key($data, ['status_url', 'statusUrl']);
    $id        = ai_find_key($data, ['request_id', 'requestId', 'generation_id', 'generationId', 'job_id', 'id']);
    $host      = preg_match('#^(https?://[^/]+)#i', $endpoint, $m) ? $m[1] : '';
    $u         = $statusUrl ?: (($id && $host) ? $host . '/v1/requests/' . rawurlencode($id) . '/status' : null);
    if (!$u) { return null; }
    $deadline = time() + 150;
    while (time() < $deadline) {
        sleep(3);
        $ch = curl_init($u);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['accept: application/json', $auth], CURLOPT_TIMEOUT => 30]);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code >= 400) { set_setting('ai_img_last_error', "Polling {$u} → HTTP {$code}"); return null; }
        $d   = json_decode((string) $raw, true);
        $url = is_array($d) ? ai_find_image_url($d) : null;
        if ($url) { return $url; }
        $st = is_array($d) ? strtolower((string) (ai_find_key($d, ['status']) ?? '')) : '';
        if (in_array($st, ['failed', 'error', 'canceled', 'cancelled'], true)) {
            set_setting('ai_img_last_error', "Génération {$st} — " . substr((string) $raw, 0, 300));
            return null;
        }
    }
    return null;
}

/** Test synchrone : génère UNE image et renvoie un diagnostic lisible pour l'admin. */
function ai_image_test(): array
{
    if (ai_img_key() === '') {
        return ['ok' => false, 'msg' => '❌ Aucune clé Higgsfield enregistrée.'];
    }
    if (!function_exists('imagewebp')) {
        return ['ok' => false, 'msg' => '❌ La librairie GD/WebP est absente sur le serveur (imagewebp introuvable).'];
    }
    if ((int) get_setting('ai_image_enabled', '0') !== 1) {
        return ['ok' => false, 'msg' => '❌ Coche « Activer » et enregistre avant de tester.'];
    }
    $path = ai_generate_image('Grand Theft Auto VI Vice City, neon sunset skyline, palm trees, cinematic, photorealistic, 16:9, no text, no watermark', 'diagnostic-test');
    if ($path) {
        return ['ok' => true, 'msg' => '✅ Génération réussie ! Image : ' . $path . ' — Endpoint : ' . ai_img_endpoint()];
    }
    return ['ok' => false, 'msg' => '❌ Échec — ' . (get_setting('ai_img_last_error', '(aucun détail)')) . ' | Endpoint : ' . ai_img_endpoint()];
}

/**
 * Télécharge une image distante et l'enregistre en WebP local (max 1280px de large,
 * léger et rapide). Renvoie le chemin web (/public/assets/img/ai/<slug>.webp) ou null.
 */
function ai_download_as_webp(string $url, string $slug): ?string
{
    if (!function_exists('imagewebp')) { return null; }
    $dir = ROOT_PATH . '/public/assets/img/ai';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) { return null; }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $bytes = curl_exec($ch);
    $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($bytes === false || $code >= 400 || strlen((string) $bytes) < 1024) { return null; }

    $im = @imagecreatefromstring($bytes);
    if (!$im) { return null; }
    // Ramène l'image dans un cadre 720p MAX (1280×720) → fichier léger, site fluide,
    // quelle que soit la résolution renvoyée par Higgsfield.
    $w = imagesx($im); $h = imagesy($im);
    $maxW = 1280; $maxH = 720;
    $ratio = min(1.0, $maxW / max(1, $w), $maxH / max(1, $h));
    if ($ratio < 1.0) {
        $nw  = max(1, (int) round($w * $ratio));
        $nh  = max(1, (int) round($h * $ratio));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($im);
        $im = $dst;
    }
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug)) ?: 'ai-' . substr(md5($url), 0, 8);
    $rel  = 'public/assets/img/ai/' . $slug . '.webp';
    // Qualité 80 : ~80–140 Ko en 720p, indistinguable à l'œil, ultra-rapide à charger.
    $ok   = imagewebp($im, ROOT_PATH . '/' . $rel, 80);
    imagedestroy($im);
    return $ok ? '/' . $rel : null;
}

/** Compte les articles SANS illustration sur-mesure (vide, ou image de la banque). */
function ai_missing_image_count(): int
{
    return (int) db()->query(
        "SELECT COUNT(*) FROM articles WHERE image IS NULL OR image='' OR image LIKE '/public/assets/img/scenes/%'"
    )->fetchColumn();
}

/**
 * Génère (Higgsfield) les illustrations MANQUANTES des articles, en arrière-plan.
 * Un verrou évite les doublons. $budget=0 → traite tout (CLI). Les échecs isolés
 * sont sautés (on passe à l'article suivant) ; on stoppe si trop d'échecs (clé/quota).
 * @return int nombre d'images générées
 */
function ai_generate_missing_images(int $budgetSeconds = 0): int
{
    if (!ai_img_enabled()) { return 0; }
    $lock = (int) get_setting('ai_img_worker_lock', '0');
    if (time() - $lock < 600) { return 0; }          // verrou 10 min (génération lente)
    set_setting('ai_img_worker_lock', (string) time());

    $deadline  = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $done = 0; $fail = 0; $failedIds = [];
    try {
        while (time() < $deadline && $fail < 5) {
            $excl = $failedIds ? (' AND id NOT IN (' . implode(',', array_map('intval', $failedIds)) . ')') : '';
            $art  = db()->query(
                "SELECT id, slug, title, image_prompt FROM articles
                 WHERE (image IS NULL OR image='' OR image LIKE '/public/assets/img/scenes/%')$excl
                 ORDER BY (status='published') DESC, id DESC LIMIT 1"
            )->fetch();
            if (!$art) { break; }
            $prompt = trim((string) $art['image_prompt']);
            if ($prompt === '') {
                $prompt = 'Grand Theft Auto VI, Vice City, ' . mb_substr((string) $art['title'], 0, 120)
                    . ', 1980s Miami neon, cinematic, photorealistic, 16:9, no text, no watermark';
            }
            $slug = (string) ($art['slug'] ?: ('article-' . (int) $art['id']));
            $path = ai_generate_image($prompt, $slug);
            if ($path) {
                db()->prepare('UPDATE articles SET image=? WHERE id=?')->execute([$path, (int) $art['id']]);
                $done++;
                set_setting('ai_img_worker_lock', (string) time()); // rafraîchit le verrou
            } else {
                $fail++; $failedIds[] = (int) $art['id'];            // saute cet article
            }
        }
    } finally {
        set_setting('ai_img_worker_lock', '0');                     // libère
    }
    return $done;
}

/**
 * Progression du remplissage des illustrations manquantes (barre de %).
 * Repère haut (ai_img_total) posé au lancement, remis à zéro une fois terminé.
 * @return array{total:int,remaining:int,done:int,percent:int}
 */
function ai_img_progress(): array
{
    $remaining = ai_missing_image_count();
    $total = (int) get_setting('ai_img_total', '0');
    if ($remaining <= 0) {
        if ($total !== 0) { set_setting('ai_img_total', '0'); }
        return ['total' => 0, 'remaining' => 0, 'done' => 0, 'percent' => 100];
    }
    if ($remaining > $total) { $total = $remaining; set_setting('ai_img_total', (string) $total); }
    $doneN   = max(0, $total - $remaining);
    $percent = $total > 0 ? (int) round($doneN / $total * 100) : 0;
    return ['total' => $total, 'remaining' => $remaining, 'done' => $doneN, 'percent' => $percent];
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

/** ID réel de la catégorie « Blog » (les articles IA y sont TOUS rangés). */
function ai_blog_cat_id(): int
{
    static $id = null;
    if ($id === null) {
        $id = (int) (db()->query("SELECT id FROM categories WHERE slug='blog' LIMIT 1")->fetchColumn() ?: 0);
        if ($id === 0) { $id = ai_cat_id('blog'); }
    }
    return $id;
}

/** Tons rédactionnels (pour toucher un maximum de lecteurs). */
function ai_tones(): array
{
    return [
        'journalistique' => 'TON JOURNALISTIQUE : factuel, structuré et crédible, façon grand média gaming (IGN, JVC). Mets les faits en perspective, cite des chiffres, reste neutre et pro.',
        'joueur'         => 'TON JOUEUR : direct, enthousiaste, « à hauteur de manette ». Parle d\'expérience de jeu, de fun, de hype, avec quelques expressions gaming naturelles.',
        'connaisseur'    => 'TON CONNAISSEUR : expert de la saga GTA. Références précises aux opus précédents, analyse fine, vocabulaire maîtrisé, mise en contexte historique.',
        'passionne'      => 'TON PASSIONNÉ : vibrant et nostalgique de Vice City, chargé d\'émotion, qui transmet l\'amour de la licence et fait monter l\'attente.',
        'geek'           => 'TON GEEK : pointu sur la technique (moteur RAGE, 60 fps, physique, IA des PNJ, ray tracing), les easter eggs, les théories et les détails cachés des trailers.',
    ];
}

/** Libellés courts des 5 personnalités (pour les menus admin). Clés = ai_tones(). */
function ai_tone_labels(): array
{
    return [
        'journalistique' => '📰 Journaliste',
        'joueur'         => '🎮 Joueur',
        'connaisseur'    => '🎓 Connaisseur',
        'passionne'      => '❤️ Passionné',
        'geek'           => '🤓 Geek',
    ];
}

/** Résout un choix de personnalité : clé fixe valide, sinon 'multi' (rotation). */
function ai_resolve_tone(string $sel, int $index = 0): string
{
    $keys = array_keys(ai_tones());
    if ($sel !== 'multi' && in_array($sel, $keys, true)) {
        return $sel;
    }
    return $keys[$index % count($keys)]; // Multi → rotation sur les 5 personnalités
}

/**
 * Génère UN article COMPLET (~2000 mots) via l'IA, avec un TON donné (ou aléatoire).
 * @return array{title:string,excerpt:string,body:string,image:string,image_prompt:string,category:string,tone:string}
 */
function ai_generate_article(?string $topic = null, ?string $toneKey = null): array
{
    $topics  = ai_topics();
    $topic   = $topic ?: $topics[array_rand($topics)];
    $tones   = ai_tones();
    $toneKey = ($toneKey !== null && isset($tones[$toneKey])) ? $toneKey : (string) array_rand($tones);

    // Anti-doublon SEO : on liste les titres/metas déjà utilisés pour que l'IA en
    // produise de 100% différents (chaque page cible d'autres requêtes Google).
    $used = [];
    try {
        $used = db()->query("SELECT title, excerpt FROM articles ORDER BY id DESC LIMIT 60")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $used = [];
    }
    $avoid = '';
    if ($used) {
        $titles = array_filter(array_map(fn($r) => mb_substr((string) $r['title'], 0, 100), $used));
        $metas  = array_filter(array_map(fn($r) => mb_substr((string) $r['excerpt'], 0, 120), $used));
        $avoid  = "\n\n⛔ DÉJÀ UTILISÉS SUR LE SITE — INTERDIT de reprendre ou paraphraser (trouve un TITRE et une META 100% différents, ciblant d'AUTRES mots-clés) :\n"
            . "Titres existants :\n- " . implode("\n- ", array_slice($titles, 0, 40)) . "\n"
            . "Metas existantes :\n- " . implode("\n- ", array_slice($metas, 0, 20)) . "\n";
    }

    $system = 'Tu es rédacteur SEO SENIOR pour ViceHub X, média de fans INDÉPENDANT et NON OFFICIEL sur GTA VI '
        . 'et Vice City. Tu écris un français impeccable, riche et fluide. '
        . "\n\nRÈGLE D'OR — FIABILITÉ : tu ne publies QUE des informations VÉRIFIÉES. Tu N'INVENTES JAMAIS de "
        . 'date, prix, nom, fonctionnalité ou déclaration. Tu distingues TOUJOURS clairement les FAITS CONFIRMÉS '
        . 'des rumeurs : toute information non officielle doit être explicitement présentée comme « rumeur », '
        . '« non confirmé » ou « selon des fuites ». Aucune spéculation présentée comme un fait. En cas de doute, '
        . 'reste général ou présente-le comme un débat de la communauté. '
        . "\n\nFAITS CONFIRMÉS (utilisables tels quels) : jeu développé par Rockstar Games (éditeur Take-Two) ; "
        . '1er trailer en décembre 2023, 2e trailer en 2025 ; sortie annoncée le 19 novembre 2026 sur PS5 et '
        . 'Xbox Series X|S ; éditions Standard (79,99$) et Ultimate (99,99$) ; duo de protagonistes Jason Duval & '
        . 'Lucia Caminos (Lucia = 1re protagoniste féminine de la série principale) ; État fictif de Leonida '
        . '(inspiré de la Floride) et retour de Vice City ; moteur RAGE ; radios dont V-Rock. Tout le reste '
        . '(carte détaillée, véhicules précis, dates PC, contenu online) n\'est PAS confirmé : traite-le en rumeur. '
        . "\n\nObjectif : un article de RÉFÉRENCE, exact et utile, qui mérite la 1re place Google et une AI Overview. "
        . 'Écris comme un HUMAIN passionné (jamais de mention d\'IA). ' . $tones[$toneKey];

    $user = "Rédige un ARTICLE COMPLET et ORIGINAL d'environ 2000 mots sur : « {$topic} ».\n\n"
        . "Règles :\n"
        . "- FIABILITÉ ABSOLUE : n'affirme que des faits VÉRIFIÉS. Toute info non officielle = présentée comme rumeur/fuite (« selon des rumeurs… », « non confirmé »). Jamais d'invention de date, prix, nom ou fonctionnalité.\n"
        . "- TITRE et META DESCRIPTION 100% UNIQUES : jamais le même titre ni la même meta qu'un autre article du site. Chaque article vise des mots-clés/angle DIFFÉRENTS pour capter un maximum de recherches Google (longue traîne).\n"
        . "- ~2000 mots, riche, sans remplissage, optimisé SEO (mots-clés naturels : GTA 6, GTA VI, Vice City, Leonida).\n"
        . "- Structure : accroche forte, puis 5 à 7 sections <h2> (avec des <h3> si utile), des listes <ul>/<ol>, "
        . "et une section finale <h2>FAQ</h2> avec 3-4 questions au format <h3>Question ?</h3><p>Réponse</p> (idéal Google AI Overview).\n"
        . "- Balises AUTORISÉES uniquement : <p> <h2> <h3> <ul> <ol> <li> <strong> <em> <blockquote>. AUCUN <a>, AUCUN <h1>, AUCUN markdown.\n"
        . "- Écris comme un HUMAIN passionné : n'indique JAMAIS que tu es une IA, et n'ajoute AUCUN marqueur technique. NE TERMINE PAS par « ===FIN=== » ni par un quelconque séparateur : le dernier <p> de la FAQ clôt l'article.\n"
        . $avoid . "\n"
        . "FORMAT DE RÉPONSE STRICT (rien d'autre) :\n"
        . 'LIGNE 1 = JSON compact : {"categorie":"news|guides|leaks|blog|trailers","titre":"titre accrocheur unique <=90 car. avec le mot-clé principal","extrait":"meta description <=180 car.","theme_image":"un mot parmi: night, city, beach, car, police, heli, marina, storm, casino, nightlife, drift, sunset, market, plane, swamp","prompt_image":"prompt EN ANGLAIS pour une illustration photorealiste Higgsfield, GTA VI Vice City neon cinematic 16:9, no text"}' . "\n"
        . "LIGNE 2 = exactement : ===CORPS===\n"
        . "PUIS = le corps de l'article en HTML (~2000 mots).";

    $raw = anthropic_complete($system, $user, 5200);

    // Sépare l'en-tête JSON du corps HTML (robuste pour les longs contenus).
    $bpos = strpos($raw, '===CORPS===');
    if ($bpos === false) {
        throw new RuntimeException('Réponse IA sans séparateur ===CORPS===.');
    }
    $head     = substr($raw, 0, $bpos);
    $bodyHtml = trim(substr($raw, $bpos + strlen('===CORPS===')));
    $js = strpos($head, '{'); $je = strrpos($head, '}');
    $json = ($js !== false && $je !== false && $je > $js) ? json_decode(substr($head, $js, $je - $js + 1), true) : null;
    if (!is_array($json) || empty($json['titre']) || $bodyHtml === '') {
        throw new RuntimeException('En-tête/corps IA incomplet.');
    }

    $title   = trim((string) $json['titre']);
    $excerpt = clean_ai_markers(mb_substr(trim((string) ($json['extrait'] ?? '')), 0, 200));
    // Nettoie tout marqueur IA résiduel (===FIN===…) : le lecteur ne doit jamais le voir.
    $body    = clean_ai_markers(strip_tags($bodyHtml, '<p><h2><h3><ul><ol><li><strong><em><blockquote><br>'));
    $iprompt = trim((string) ($json['prompt_image'] ?? ''));
    $theme   = (string) ($json['theme_image'] ?? '');
    $cat     = (string) ($json['categorie'] ?? 'blog');

    return [
        'title'        => $title,
        'excerpt'      => $excerpt !== '' ? $excerpt : mb_substr(strip_tags($body), 0, 160),
        'body'         => $body,
        'image'        => '/public/assets/img/scenes/' . ai_pick_image($theme, $title),
        'image_prompt' => $iprompt,
        'category'     => $cat,
        'tone'         => $toneKey,
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
    // Publié → maintenant ; Programmé (pending) → prochain créneau CRON ; Brouillon → sans date.
    $interval = max(1, (int) get_setting('ai_auto_interval', '6'));
    $pub = $status === 'published'
        ? date('Y-m-d H:i:s')
        : ($status === 'pending' ? ai_next_slot($interval) : null);

    // Illustration IA sur-mesure (Higgsfield) générée à la création, si activée.
    if (ai_img_enabled() && !empty($data['image_prompt'])) {
        $gen = ai_generate_image((string) $data['image_prompt'], $slug);
        if ($gen) { $data['image'] = $gen; }
    }

    $st = db()->prepare(
        'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, image_prompt, author_id, status, published_at, created_at)
         VALUES (?, \'fr\', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $st->execute([
        ai_blog_cat_id(), $data['title'], $slug, $data['excerpt'], $data['body'],
        $data['image'], $data['image_prompt'], $authorId, $status, $pub,
    ]);
    return (int) db()->lastInsertId();
}

/**
 * Prochain créneau de publication programmée : juste après le dernier créneau
 * déjà planifié (ou maintenant), + l'intervalle CRON. Espace les articles.
 */
function ai_next_slot(int $interval): string
{
    $interval = max(1, $interval);
    $maxPlanned = db()->query("SELECT MAX(published_at) FROM articles WHERE status='pending' AND published_at IS NOT NULL")->fetchColumn();
    $base = max(time(), $maxPlanned ? (int) strtotime((string) $maxPlanned) : 0);
    return date('Y-m-d H:i:s', $base + $interval * 3600);
}

/**
 * Auto-réparation : attribue une date de publication espacée aux articles
 * « Programmée CRON » qui n'en ont pas encore (published_at NULL).
 */
function ai_schedule_pending(int $interval): void
{
    $interval = max(1, $interval);
    $rows = db()->query(
        "SELECT id FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> '' AND published_at IS NULL ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
    if (!$rows) { return; }
    $maxPlanned = db()->query("SELECT MAX(published_at) FROM articles WHERE status='pending' AND published_at IS NOT NULL")->fetchColumn();
    $base = max(time(), $maxPlanned ? (int) strtotime((string) $maxPlanned) : 0);
    $upd = db()->prepare("UPDATE articles SET published_at=? WHERE id=?");
    foreach ($rows as $i => $id) {
        $upd->execute([date('Y-m-d H:i:s', $base + ($i + 1) * $interval * 3600), (int) $id]);
    }
}

/** ID de l'auteur admin (pour attribuer les articles générés). */
function ai_admin_author_id(): ?int
{
    return (int) (db()->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0) ?: null;
}

/** Ajoute N articles à la FILE de génération en arrière-plan (statut + personnalité). */
function ai_queue_add(int $n, string $status, string $tone = 'multi'): void
{
    $status = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'draft';
    set_setting('ai_gen_status', $status);
    $tone = ($tone === 'multi' || in_array($tone, array_keys(ai_tones()), true)) ? $tone : 'multi';
    set_setting('ai_gen_tone', $tone);
    set_setting('ai_gen_queue', (string) max(0, (int) get_setting('ai_gen_queue', '0') + max(0, $n)));
}

/**
 * VIDE la file de génération (worker en arrière-plan). Un verrou évite que deux
 * process génèrent en double. $budgetSeconds = 0 → aucune limite (draine tout).
 * @return int nombre d'articles générés
 */
function ai_drain_queue(int $budgetSeconds = 0): int
{
    if (!ai_enabled()) { return 0; }
    // Verrou anti-double-exécution (expire au bout de 5 min sans rafraîchissement).
    $lock = (int) get_setting('ai_worker_lock', '0');
    if (time() - $lock < 300) { return 0; }
    set_setting('ai_worker_lock', (string) time());

    $status   = get_setting('ai_gen_status', 'draft');
    $status   = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'draft';
    $toneSel  = (string) get_setting('ai_gen_tone', 'multi'); // 'multi' = rotation des 5 personnalités
    $authorId = ai_admin_author_id();
    $deadline = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $done = 0;
    while ((int) get_setting('ai_gen_queue', '0') > 0 && time() < $deadline) {
        try {
            // Multi → chaque article change de personnalité (rotation) ; sinon personnalité fixe.
            $art = ai_generate_article(null, ai_resolve_tone($toneSel, $done));
            ai_save_article($art, $status, $authorId);
            $done++;
        } catch (Throwable $e) {
            break; // erreur API : on s'arrête, on reprendra plus tard
        }
        set_setting('ai_gen_queue', (string) max(0, (int) get_setting('ai_gen_queue', '0') - 1));
        set_setting('ai_worker_lock', (string) time()); // rafraîchit le verrou
    }
    set_setting('ai_worker_lock', '0'); // libère
    return $done;
}

/** Lance un worker en ARRIÈRE-PLAN (best-effort, sans bloquer la page). */
function ai_spawn_worker(string $script = 'ai-worker.php'): bool
{
    $script = basename($script); // sécurité : pas de traversée de chemin
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (!function_exists('shell_exec') || in_array('shell_exec', $disabled, true)) { return false; }
    $php = null;
    foreach (['/usr/local/bin/php', '/usr/bin/php', '/opt/cpanel/ea-php81/root/usr/bin/php', 'php'] as $c) {
        if ($c === 'php' || @is_file($c)) { $php = $c; break; }
    }
    if ($php === null) { return false; }
    // nohup + & → le worker survit à la fin de la requête web (arrière-plan réel).
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg(ROOT_PATH . '/' . $script) . ' >/dev/null 2>&1';
    @shell_exec('nohup ' . $cmd . ' & echo ok');
    return true;
}

/**
 * Appelée par le CRON (ai-tick.php). Fait deux choses, sans jamais bloquer :
 *  1) vide un peu la file de génération manuelle (arrière-plan) ;
 *  2) DRIP : publie 1 article « programmé » (pending IA) à chaque intervalle,
 *     et si aucun n'est en attente et l'auto est activée, en génère 1 frais.
 * @return array{generated:int,message:string}
 */
function ai_auto_run(int $budgetSeconds = 130): array
{
    if (!ai_enabled()) {
        return ['generated' => 0, 'message' => '⛔ Clé API Anthropic manquante (Réglages).'];
    }
    $msgs = [];

    // 1) File de génération manuelle (on lui laisse la moitié du budget).
    $drained = ai_drain_queue((int) ($budgetSeconds / 2));
    if ($drained > 0) { $msgs[] = "{$drained} article(s) généré(s) (file)"; }

    // 2) PUBLICATION PROGRAMMÉE — respecte la DATE de chaque article.
    $interval = max(1, (int) get_setting('ai_auto_interval', '6'));
    $autoOn   = (int) get_setting('ai_auto_enabled', '0') === 1;
    $now      = time();

    // Auto-répare : donne une date espacée aux articles programmés sans date.
    ai_schedule_pending($interval);

    // Publie TOUS les articles programmés dont l'heure prévue est arrivée.
    $due = db()->query(
        "SELECT id FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> '' AND published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
    if ($due) {
        $in = implode(',', array_map('intval', $due));
        db()->exec("UPDATE articles SET status='published' WHERE id IN ($in)");
        set_setting('ai_auto_last', (string) $now);
        $msgs[] = count($due) . ' article(s) programmé(s) publié(s)';
    }

    // Génération auto : si activée et plus AUCUN article programmé en attente,
    // produit 1 article frais par intervalle.
    $last       = (int) get_setting('ai_auto_last', '0');
    $stillSched = (int) db()->query("SELECT COUNT(*) FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> ''")->fetchColumn();
    if ($autoOn && $stillSched === 0 && ($last === 0 || ($now - $last) >= $interval * 3600)) {
        $st = get_setting('ai_auto_status', 'published') === 'draft' ? 'draft' : 'published';
        $autoTone = (string) get_setting('ai_auto_tone', 'multi');
        try {
            $id = null; $t = 0;
            while (!$id && $t < 3) { $id = ai_save_article(ai_generate_article(null, ai_resolve_tone($autoTone, (int) get_setting('ai_auto_last', '0') + $t)), $st, ai_admin_author_id()); $t++; }
            if ($id) { set_setting('ai_auto_last', (string) $now); $msgs[] = '1 article ' . ($st === 'published' ? 'publié' : 'en brouillon'); }
        } catch (Throwable $e) { $msgs[] = 'erreur API : ' . $e->getMessage(); }
    }

    if (!$msgs) {
        // Prochain article programmé (date la plus proche à venir).
        $next = db()->query("SELECT MIN(published_at) FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> '' AND published_at > NOW()")->fetchColumn();
        $tail = '';
        if ($next) {
            $mins = max(1, (int) ceil((strtotime((string) $next) - $now) / 60));
            $tail = ' Prochain le ' . date('d/m à H:i', strtotime((string) $next)) . " (~{$mins} min).";
        } elseif ($autoOn) {
            $nextIn = ($last > 0) ? max(0, $interval * 3600 - ($now - $last)) : 0;
            $tail = ' Prochain dans ~' . max(1, (int) ceil($nextIn / 60)) . ' min.';
        }
        return ['generated' => 0, 'message' => '✓ À jour.' . $tail];
    }
    return ['generated' => $drained + 1, 'message' => '✅ ' . implode(' · ', $msgs) . '.'];
}

/**
 * Progression de publication des articles « Programmée CRON » (pending).
 * Maintient un repère haut (high-water) remis à zéro dès que tout est publié,
 * pour une barre de % « combien reste-t-il à publier ».
 * @return array{total:int,pending:int,published:int,percent:int}
 */
function ai_sched_progress(): array
{
    $pending = (int) db()->query(
        "SELECT COUNT(*) FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> ''"
    )->fetchColumn();

    $total = (int) get_setting('ai_sched_total', '0');
    if ($pending <= 0) {
        if ($total !== 0) { set_setting('ai_sched_total', '0'); }
        return ['total' => 0, 'pending' => 0, 'published' => 0, 'percent' => 100];
    }
    if ($pending > $total) {                       // nouveau lot programmé → nouveau repère
        $total = $pending;
        set_setting('ai_sched_total', (string) $total);
    }
    $published = max(0, $total - $pending);
    $percent   = $total > 0 ? (int) round($published / $total * 100) : 0;
    return ['total' => $total, 'pending' => $pending, 'published' => $published, 'percent' => $percent];
}
