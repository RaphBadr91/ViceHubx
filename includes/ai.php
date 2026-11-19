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
    return get_setting('ai_model', '') ?: 'claude-haiku-4-5';
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

/** Basenames de scènes déjà utilisées par un article (garde-fou anti-doublon d'image). */
function ai_used_scene_images(): array
{
    static $used = null;
    if ($used !== null) { return $used; }
    $used = [];
    try {
        $rows = db()->query("SELECT image FROM articles WHERE image LIKE '%/scenes/%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $img) { $used[basename((string) $img)] = true; }
    } catch (Throwable $e) { /* table indisponible → pas de dédup */ }
    return $used;
}

/**
 * Choisit une illustration de la banque selon le thème (repli titre), en évitant
 * TOUTE image déjà utilisée par un autre article : garde-fou « jamais deux fois la
 * même image » tant que la banque n'est pas épuisée. L'idéal reste une image
 * générée sur-mesure (Higgsfield, nommée par slug) = unique par définition.
 */
function ai_pick_image(string $theme, string $title = ''): string
{
    $bank  = ai_image_bank();
    $theme = strtolower(trim($theme));
    $used  = ai_used_scene_images();
    $all   = array_merge(...array_values($bank));
    // Renvoie une image NON utilisée du pool donné, sinon '' (pool épuisé).
    $free = static function (array $opts) use ($used) {
        $f = array_values(array_filter($opts, static fn($x) => empty($used[$x])));
        return $f ? $f[array_rand($f)] : '';
    };
    // 1) thème exact → image libre du thème
    if (isset($bank[$theme]) && ($p = $free($bank[$theme])) !== '') { return $p; }
    // 2) déduction par mots-clés du titre → image libre du thème déduit
    $t = mb_strtolower($title);
    $hints = [
        'nuit' => 'night', 'soir' => 'night', 'carte' => 'city', 'map' => 'city', 'ville' => 'city',
        'voiture' => 'car', 'véhicule' => 'car', 'bagnole' => 'car', 'plage' => 'beach', 'mer' => 'beach',
        'police' => 'police', 'casino' => 'casino', 'marina' => 'marina', 'bateau' => 'marina',
        'orage' => 'storm', 'tempête' => 'storm', 'club' => 'nightlife', 'fête' => 'nightlife',
    ];
    foreach ($hints as $kw => $th) {
        if (mb_strpos($t, $kw) !== false && isset($bank[$th]) && ($p = $free($bank[$th])) !== '') { return $p; }
    }
    // 3) n'importe quelle image LIBRE de la banque (unicité prioritaire sur le thème)
    if (($p = $free($all)) !== '') { return $p; }
    // 4) banque épuisée : dernier recours au hasard (rare — configurez higgsfield_key pour
    //    générer une image UNIQUE par article et ne jamais retomber ici).
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
    $k = getenv('HIGGSFIELD_KEY') ?: (string) get_setting('higgsfield_key', '');
    $k = trim($k, " \t\n\r\0\x0B\"'");                 // retire espaces/guillemets parasites
    $k = preg_replace('/^(Key|Bearer)\s+/i', '', $k);   // au cas où l'en-tête complet est collé
    return (string) $k;
}

/** La génération d'illustration IA est-elle active ? (activée + clé + GD WebP). */
function ai_img_enabled(): bool
{
    return (int) get_setting('ai_image_enabled', '0') === 1
        && ai_img_key() !== ''
        && function_exists('imagewebp');
}

/** Endpoint text-to-image Higgsfield (réglable). Flux Pro Kontext Max par défaut (confirmé 200). */
function ai_img_endpoint(): string
{
    return get_setting('ai_image_endpoint', '') ?: 'https://platform.higgsfield.ai/flux-pro/kontext/max/text-to-image';
}

/** Cherche récursivement la 1re URL d'image dans une réponse JSON (schéma tolérant). */
function ai_find_image_url($node): ?string
{
    if (is_string($node)) {
        // Une VRAIE image : extension image, ou hébergée sur le CDN cloudfront.
        // (On ne matche PAS les URLs d'API higgsfield type /requests/{id}/status.)
        if (preg_match('#^https?://[^\s"\']+#i', $node)
            && (preg_match('#\.(png|jpe?g|webp)(\?|$)#i', $node) || stripos($node, 'cloudfront') !== false)) {
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
        // Style maison appliqué à CHAQUE image (même rendu que nos visuels Vice City).
        $style = trim((string) get_setting('ai_img_style', ai_img_default_style()));
        $fullPrompt = $style !== '' ? ($prompt . ' — ' . $style) : $prompt;
        // Corps À PLAT confirmé (HTTP 200 « queued ») : prompt + aspect_ratio.
        $payload = json_encode(['prompt' => $fullPrompt, 'aspect_ratio' => '16:9']);
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
        // Compteur de dépenses : +1 image générée avec succès.
        set_setting('ai_img_generated_total', (string) ((int) get_setting('ai_img_generated_total', '0') + 1));
        return $local;
    } catch (Throwable $e) {
        set_setting('ai_img_last_error', 'Exception : ' . $e->getMessage());
        return null;
    }
}

/** Style « maison » par défaut appliqué à toutes les illustrations (rendu Vice City). */
function ai_img_default_style(): string
{
    return 'GTA VI Vice City, 1980s Miami neon aesthetic, cinematic lighting, photorealistic, vibrant teal and pink, ultra detailed, no text, no watermark';
}

/**
 * Récapitulatif des dépenses de génération d'images.
 * @return array{count:int,cost:float,credits:float,usd:float,eur:float}
 */
function ai_img_spend(): array
{
    $count   = (int) get_setting('ai_img_generated_total', '0');
    $cost    = (float) (get_setting('ai_img_cost', '3') ?: 3); // crédits par image (réglable)
    $credits = $count * $cost;
    $usd     = $credits / 16.0;   // Higgsfield : 1 $ = 16 crédits
    $eur     = $usd * 0.92;       // conversion approximative $→€
    return ['count' => $count, 'cost' => $cost, 'credits' => $credits, 'usd' => $usd, 'eur' => $eur];
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

/** GET JSON authentifié : renvoie [raw|false, code]. */
function ai_http_get(string $url, string $auth): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['accept: application/json', $auth], CURLOPT_TIMEOUT => 30]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$raw, $code];
}

/** Cas API asynchrone : interroge status/response URL jusqu'à obtenir l'image (≤150 s). */
function ai_poll_image(array $data, string $endpoint, string $auth): ?string
{
    $statusUrl   = ai_find_key($data, ['status_url', 'statusUrl']);
    $responseUrl = ai_find_key($data, ['response_url', 'responseUrl', 'result_url', 'resultUrl']);
    $id          = ai_find_key($data, ['request_id', 'requestId', 'generation_id', 'generationId', 'job_id', 'id']);
    $host        = preg_match('#^(https?://[^/]+)#i', $endpoint, $m) ? $m[1] : '';
    // Status officiel : GET /requests/{id}/status (SANS /v1/).
    $poll        = $statusUrl ?: (($id && $host) ? $host . '/requests/' . rawurlencode($id) . '/status' : null);
    if (!$poll && !$responseUrl) { return null; }

    $deadline = time() + 150;
    while (time() < $deadline) {
        sleep(3);
        $target = $poll ?: $responseUrl;
        [$raw, $code] = ai_http_get($target, $auth);
        if ($raw === false || $code >= 400) { set_setting('ai_img_last_error', "Polling {$target} → HTTP {$code} — " . substr((string) $raw, 0, 200)); return null; }
        $d   = json_decode((string) $raw, true);
        $url = is_array($d) ? ai_find_image_url($d) : null;
        if ($url) { return $url; }
        $st = is_array($d) ? strtolower((string) (ai_find_key($d, ['status']) ?? '')) : '';
        if (in_array($st, ['failed', 'error', 'canceled', 'cancelled'], true)) {
            set_setting('ai_img_last_error', "Génération {$st} — " . substr((string) $raw, 0, 300));
            return null;
        }
        if (in_array($st, ['completed', 'complete', 'succeeded', 'success', 'done'], true)) {
            // Terminé : va chercher le résultat final si l'image n'est pas déjà là.
            $ru = $responseUrl ?: ai_find_key($d, ['response_url', 'responseUrl', 'result_url', 'resultUrl']);
            if ($ru) {
                [$r2, $c2] = ai_http_get($ru, $auth);
                if ($c2 < 400) {
                    $d2 = json_decode((string) $r2, true);
                    $u2 = is_array($d2) ? ai_find_image_url($d2) : null;
                    if ($u2) { return $u2; }
                    if (preg_match('#https?://[^\s"\']+\.(?:png|jpe?g|webp)#i', (string) $r2, $mm)) { return $mm[0]; }
                }
            }
            set_setting('ai_img_last_error', "Génération terminée mais URL d'image introuvable — " . substr((string) $raw, 0, 300));
            return null;
        }
    }
    set_setting('ai_img_last_error', 'Délai de génération dépassé (150 s) sans image.');
    return null;
}

/**
 * SONDE l'API Higgsfield : teste plusieurs structures d'URL/modèles et renvoie un
 * rapport (code HTTP + début de réponse) pour trouver le bon endpoint en un coup.
 */
/** Une requête de sondage : renvoie [code, body, allowHeader]. */
function ai_probe_req(string $method, string $url, string $auth, ?string $body = null): array
{
    $allow = '';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => ['content-type: application/json', 'accept: application/json', $auth],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HEADERFUNCTION => function ($ch, $h) use (&$allow) {
            if (stripos($h, 'allow:') === 0) { $allow = trim(substr($h, 6)); }
            return strlen($h);
        },
    ]);
    if ($body !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
    $r = curl_exec($ch);
    $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, (string) $r, $allow];
}

function ai_image_probe(): string
{
    if (ai_img_key() === '') { return 'Aucune clé Higgsfield enregistrée.'; }
    $auth = 'Authorization: Key ' . ai_img_key();
    $host = 'https://platform.higgsfield.ai'; // SANS /v1/ (confirmé par l'API Reference).
    $clip = fn($s) => substr(preg_replace('/\s+/', ' ', (string) $s) ?? '', 0, 150);
    $body = json_encode(['prompt' => 'a red sports car at sunset, neon city', 'aspect_ratio' => '16:9']);
    $out  = [];

    // Modèles text-to-image ÉCONOMIQUES à tester (le moins cher qui répond 200 gagne).
    $paths = [
        '/flux/schnell/text-to-image',            // Flux Schnell — le moins cher
        '/flux-1/schnell/text-to-image',
        '/flux/dev/text-to-image',                // Flux Dev — bon marché
        '/higgsfield-ai/popcorn/auto',            // Popcorn Auto (natif Higgsfield)
        '/higgsfield-ai/popcorn',
        '/flux-pro/kontext/text-to-image',        // Kontext Pro (moins cher que Max)
        '/flux-pro/v1.1/text-to-image',
        '/higgsfield-ai/soul/cinema',             // Soul Cinema (cinématique)
        '/flux-pro/kontext/max/text-to-image',    // référence qui marche déjà (200)
    ];
    foreach ($paths as $p) {
        [$c, $r, $a] = ai_probe_req('POST', $host . $p, $auth, $body);
        $mark = (!in_array($c, [404, 0], true)) ? '   <<< ✅ RÉPOND' : '';
        $out[] = str_pad($p, 40) . " → {$c}" . ($a ? " [Allow: {$a}]" : '') . ' : ' . $clip($r) . $mark;
    }
    return "POST {$host}{chemin} (sans /v1/) :\n" . implode("\n", $out);
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
    ai_ensure_source_col();
    // On ne compte que les ORIGINAUX (source_id IS NULL) : les traductions EN
    // partagent l'image de leur article FR source → aucune 2e génération payante.
    try {
        return (int) db()->query(
            "SELECT COUNT(*) FROM articles
             WHERE (image IS NULL OR image='' OR image LIKE '/public/assets/img/scenes/%') AND source_id IS NULL"
        )->fetchColumn();
    } catch (Throwable $e) {
        return (int) db()->query(
            "SELECT COUNT(*) FROM articles WHERE image IS NULL OR image='' OR image LIKE '/public/assets/img/scenes/%'"
        )->fetchColumn();
    }
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
    ai_ensure_source_col();
    $lock = (int) get_setting('ai_img_worker_lock', '0');
    if (time() - $lock < 600) { return 0; }          // verrou 10 min (génération lente)
    set_setting('ai_img_worker_lock', (string) time());

    $deadline  = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $done = 0; $fail = 0; $failedIds = [];
    $prop = db()->prepare('UPDATE articles SET image=? WHERE source_id=?'); // propage aux VO EN
    try {
        while (time() < $deadline && $fail < 5) {
            $excl = $failedIds ? (' AND id NOT IN (' . implode(',', array_map('intval', $failedIds)) . ')') : '';
            // Uniquement les ORIGINAUX : une seule image payante partagée avec la VO EN.
            $art  = db()->query(
                "SELECT id, slug, title, image_prompt FROM articles
                 WHERE (image IS NULL OR image='' OR image LIKE '/public/assets/img/scenes/%')
                   AND source_id IS NULL$excl
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
                $prop->execute([$path, (int) $art['id']]);          // même image pour la VO anglaise
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
        // Sortie / dates
        'la date de sortie et le compte à rebours',
        'la date de sortie exacte du 19 novembre 2026 et ce qu’elle implique',
        'un report de la date de sortie de GTA 6 est-il encore possible ?',
        'combien de temps reste-t-il avant la sortie de GTA 6',
        'pourquoi Rockstar a choisi l’automne 2026 pour GTA 6',
        // Éditions / prix / précommande
        'les éditions Standard et Ultimate et leur rapport qualité-prix',
        'quelle édition de GTA 6 choisir selon ton budget',
        'le prix de GTA 6 à 79,99$ : est-ce justifié ?',
        'où et comment précommander GTA 6 au meilleur prix',
        'la précommande et le Vintage Vice City Pack en détail',
        'GTA 6 sera-t-il disponible dans le Xbox Game Pass ?',
        // Personnages
        'le duo Jason Duval et Lucia Caminos',
        'qui est Lucia Caminos, première héroïne jouable de la saga',
        'qui est Jason Duval et son passé mystérieux',
        'la relation Jason et Lucia façon Bonnie and Clyde moderne',
        'les personnages secondaires (Boobie Ike, Dre’Quan Priest, Real Dimez)',
        'les antagonistes possibles de GTA 6',
        // Carte / lieux
        'la carte de Leonida et ses lieux (Port Gellhorn, Mount Kalaga, les Keys)',
        'la carte complète de Leonida, quartier par quartier',
        'Vice City en 2026 : ce qui a changé depuis 2002',
        'les Keys, les marécages et la campagne de Leonida',
        'la taille de la carte de GTA 6 comparée à GTA 5',
        'les lieux emblématiques repérés dans les bandes-annonces',
        // Véhicules
        'les véhicules : supercars, muscle cars, bateaux, motos',
        'les supercars aperçues dans les trailers de GTA 6',
        'les motos et véhicules tout-terrain de Leonida',
        'la conduite et la physique des véhicules dans GTA 6',
        'bateaux, jet-skis et vie nautique à Vice City',
        'les avions et hélicoptères de GTA 6',
        // Gameplay
        'les nouveautés de gameplay confirmées dans GTA 6',
        'le système de recherche (wanted) et la police dans GTA 6',
        'l’IA des PNJ et la vie de la ville dans GTA 6',
        'les braquages et grandes missions attendus dans GTA 6',
        'la personnalisation des personnages et des véhicules',
        'les mini-jeux et activités annexes attendus',
        'le mode photo et les réseaux sociaux in-game',
        'l’économie, l’argent et les propriétés dans GTA 6',
        'les animaux et la faune de Leonida',
        'les activités : plongée, pêche, fight clubs, courses-poursuites',
        // Technique
        'GTA 6 tournera-t-il en 60 fps sur PS5 ?',
        'le moteur RAGE et les graphismes de GTA 6',
        'le ray tracing et la technologie derrière GTA 6',
        'la taille du jeu et l’espace de stockage nécessaire',
        // Plateformes / online
        'la version PC de GTA 6 : date probable et attentes',
        'GTA 6 sur PS5 vs Xbox Series X : quelle version choisir',
        'le futur de GTA Online et du multijoueur sur GTA 6',
        'jouer en coopération dans GTA 6 : ce que l’on sait',
        // Trailers / leaks / théories
        'l’analyse du trailer 1 image par image',
        'l’analyse du trailer 2 et ses détails cachés',
        'les easter eggs cachés dans les bandes-annonces de GTA 6',
        'les plus gros leaks de GTA 6 et leur fiabilité',
        'la chronologie officielle des annonces de GTA 6',
        'les théories de la communauté et la fiabilité des leaks',
        'ce que Rockstar n’a pas encore montré de GTA 6',
        // Ambiance / culture / comparaisons
        'Vice City et son ambiance néon des années 80',
        'les radios et la bande-son (V-Rock, soundtrack des trailers)',
        'la météo dynamique et les ouragans de Leonida',
        'la comparaison GTA 6 vs GTA 5',
        'GTA 6 vs Red Dead Redemption 2 : les leçons de Rockstar',
        'l’évolution de la saga GTA de 1997 à 2026',
        'pourquoi GTA 6 est le jeu le plus attendu de l’histoire',
        'les références à Miami et à la Floride dans GTA 6',
        'la musique synthwave et l’esprit des années 80 dans GTA 6',
        'l’héritage de Vice City (2002) et la nostalgie de la saga',
        // Pratique / how-to
        'comment bien se préparer pour le jour de la sortie',
        'que faire en attendant GTA 6 : jeux similaires à essayer',
        'la configuration PC recommandée probable pour GTA 6',
        'comment éviter les spoilers avant la sortie de GTA 6',
        // Actu / business / communauté
        'Take-Two, Rockstar et les enjeux financiers de GTA 6',
        'la hype mondiale et les records des bandes-annonces',
        'le marketing de Rockstar autour de GTA 6',
        'la communauté GTA 6 : forums, créateurs et fans',
        'l’impact économique et culturel attendu de GTA 6',
        // Fan / goodies
        'les meilleurs fonds d’écran GTA 6 et Vice City',
        'les goodies et le merchandising GTA 6 à collectionner',
        'la nostalgie de Vice City : pourquoi elle nous obsède',
    ];
}

/** Catégorie (slug) → id. */
function ai_cat_id(string $slug): int
{
    static $cache = [];
    $slug = strtolower(trim($slug));
    if (!in_array($slug, ['news', 'guides', 'leaks', 'trailers', 'blog'], true)) { $slug = 'blog'; }
    if (isset($cache[$slug])) { return $cache[$slug]; }
    // Vraie recherche par slug (robuste si les ids diffèrent), repli sur 'blog' puis map figée.
    $id = 0;
    try {
        $q = db()->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
        $q->execute([$slug]);
        $id = (int) ($q->fetchColumn() ?: 0);
    } catch (Throwable $e) { $id = 0; }
    if ($id === 0 && $slug !== 'blog') {
        try {
            $id = (int) (db()->query("SELECT id FROM categories WHERE slug='blog' LIMIT 1")->fetchColumn() ?: 0);
        } catch (Throwable $e) { $id = 0; }
    }
    if ($id === 0) {
        $map = ['news' => 1, 'guides' => 2, 'leaks' => 3, 'trailers' => 4, 'blog' => 5];
        $id  = $map[$slug] ?? 5;
    }
    return $cache[$slug] = $id;
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

/** Tons rédactionnels (pour toucher un maximum de lecteurs). Bilingue FR/EN. */
function ai_tones(string $lang = 'fr'): array
{
    // Registres PROFESSIONNELS uniquement (style rédaction, cohérent d'un article à l'autre).
    // Les clés historiques sont conservées pour les menus et réglages de l'admin.
    if ($lang === 'en') {
        return [
            'journalistique' => 'NEWS DESK REGISTER: factual, concise, inverted pyramid. Lead with what happened, then context and implications.',
            'joueur'         => 'PRACTICAL GUIDE REGISTER: clear, actionable and organised by steps or criteria. Speak to the reader directly, without hype.',
            'connaisseur'    => 'ANALYSIS REGISTER: expert perspective on the series and the industry, careful reasoning, precise references to previous entries.',
            'passionne'      => 'FEATURE REGISTER: polished long-form editorial on culture and setting, measured and elegant, never gushing.',
            'geek'           => 'TECHNICAL REGISTER: precise and sober on engine, performance and hardware, with exact terminology and no speculation dressed as fact.',
        ];
    }
    return [
        'journalistique' => 'REGISTRE ACTUALITÉ : factuel, concis, pyramide inversée. D\'abord ce qui s\'est passé, puis le contexte et les conséquences.',
        'joueur'         => 'REGISTRE GUIDE PRATIQUE : clair, concret, organisé par étapes ou critères. Tu t\'adresses au lecteur directement, sans emphase.',
        'connaisseur'    => 'REGISTRE ANALYSE : regard expert sur la saga et l\'industrie, raisonnement rigoureux, références précises aux opus précédents.',
        'passionne'      => 'REGISTRE DOSSIER : article de fond soigné sur la culture et l\'univers du jeu, mesuré et élégant, jamais dithyrambique.',
        'geek'           => 'REGISTRE TECHNIQUE : précis et sobre sur le moteur, les performances et le matériel, vocabulaire exact, aucune spéculation présentée comme un fait.',
    ];
}

/** Le jeu est-il sorti ? (date de sortie réglable dans l'admin, RELEASE_DATE par défaut) */
function ai_is_postlaunch(): bool
{
    $d = function_exists('get_setting') ? (string) (get_setting('release_date', defined('RELEASE_DATE') ? RELEASE_DATE : '2026-11-19') ?: '2026-11-19') : '2026-11-19';
    $ts = strtotime($d) ?: strtotime('2026-11-19');
    return time() >= $ts;
}

/**
 * Titre « propre » : retire les mots racoleurs que l'IA ajoutait systématiquement
 * (Révélé, Confirmé, Décrypté, Everything We Know…), source de titres tous identiques.
 */
function ai_clean_title(string $t): string
{
    // Uniquement des retraits SÛRS (la grammaire du titre reste intacte) : un mot racoleur
    // en fin de titre, juste avant « : » / « - », ou un segment final « : Everything We Know ».
    // Les consignes de rédaction font l'essentiel ; ceci n'est qu'un filet de sécurité.
    $w = '(?:officially\s+)?(?:revealed|confirmed|decoded|unveiled|exposed|r[ée]v[ée]l[ée]e?s?|confirm[ée]e?s?|d[ée]crypt[ée]e?s?|d[ée]voil[ée]e?s?)';
    $c = $t;
    $c = preg_replace('/\s*[:\x{2013}\x{2014}|\-]\s*(?:everything\s+we\s+(?:actually\s+)?know|tout\s+ce\s+qu[\x{2019}\']on\s+sait)\s*$/iu', '', $c) ?? $c;
    $c = preg_replace('/\s+' . $w . '\s*(?=[:\x{2013}\x{2014}|])/iu', '', $c) ?? $c;   // « Soundtrack Revealed: » → « Soundtrack: »
    $c = preg_replace('/\s*[:\x{2013}\x{2014}|\-]?\s+' . $w . '\s*$/iu', '', $c) ?? $c; // « … Decoded » en fin
    $c = preg_replace('/(\d+)\s+' . $w . '\s+/iu', '$1 ', $c) ?? $c;                   // « 19 Confirmed Vehicles » → « 19 Vehicles »
    $c = trim(preg_replace('/\s{2,}/u', ' ', $c) ?? $c, " \t\n\r\0\x0B,;:-");
    return mb_strlen($c) >= 15 ? $c : $t;
}

/** Résout une langue : 'fr'/'en' fixe, ou 'both' → alterne selon l'index. */
function ai_resolve_lang(string $sel, int $index = 0): string
{
    if ($sel === 'en' || $sel === 'fr') { return $sel; }
    return ($index % 2 === 0) ? 'fr' : 'en'; // 'both' → alternance FR/EN
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
function ai_generate_article(?string $topic = null, ?string $toneKey = null, string $lang = 'fr'): array
{
    $lang    = $lang === 'en' ? 'en' : 'fr';
    $topics  = ai_topics();
    $topic   = $topic ?: $topics[array_rand($topics)];
    $tones   = ai_tones($lang);
    $toneKey = ($toneKey !== null && isset($tones[$toneKey])) ? $toneKey : (string) array_rand($tones);

    // Anti-doublon SEO : titres/metas déjà utilisés (même langue) à ne pas reprendre.
    $used = [];
    try {
        $st = db()->prepare("SELECT title, excerpt FROM articles WHERE lang = ? ORDER BY id DESC LIMIT 60");
        $st->execute([$lang]);
        $used = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $used = [];
    }
    $titles = $used ? array_slice(array_filter(array_map(fn($r) => mb_substr((string) $r['title'], 0, 100), $used)), 0, 40) : [];
    $metas  = $used ? array_slice(array_filter(array_map(fn($r) => mb_substr((string) $r['excerpt'], 0, 120), $used)), 0, 20) : [];

    $launched = ai_is_postlaunch();
    $factsEn = 'GTA VI is developed by Rockstar Games (publisher Take-Two Interactive). Trailer 1: December 2023; trailer 2: May 2025. '
        . ($launched
            ? 'The game RELEASED on November 19, 2026 on PlayStation 5 and Xbox Series X|S (no PC version at launch). '
            : 'Release scheduled for November 19, 2026 on PlayStation 5 and Xbox Series X|S. ')
        . 'Standard ($79.99) and Ultimate ($99.99) editions. Protagonists: Jason Duval and Lucia Caminos (Lucia is the first female lead of the main series). '
        . 'Setting: the fictional state of Leonida, inspired by Florida, including Vice City. Engine: RAGE. Radio stations include V-Rock.';
    $factsFr = 'GTA VI est développé par Rockstar Games (éditeur Take-Two Interactive). Trailer 1 : décembre 2023 ; trailer 2 : mai 2025. '
        . ($launched
            ? 'Le jeu EST SORTI le 19 novembre 2026 sur PlayStation 5 et Xbox Series X|S (pas de version PC au lancement). '
            : 'Sortie prévue le 19 novembre 2026 sur PlayStation 5 et Xbox Series X|S. ')
        . 'Éditions Standard (79,99 $) et Ultimate (99,99 $). Protagonistes : Jason Duval et Lucia Caminos (Lucia est la première héroïne de la série principale). '
        . 'Cadre : l\'État fictif de Leonida, inspiré de la Floride, avec Vice City. Moteur : RAGE. Radios, dont V-Rock.';

    if ($lang === 'en') {
        $avoid = '';
        if ($titles) {
            $avoid = "\n\nTITLES ALREADY ON THE SITE (your article must take a clearly different angle and must not paraphrase any of them):\n- "
                . implode("\n- ", $titles) . "\n";
        }
        $system = 'You are a senior editor at ViceHub X, an independent publication covering GTA VI (not affiliated with Rockstar Games). '
            . 'You write precise, idiomatic, professional English.'
            . "\n\nHOUSE STYLE: a professional editorial desk comparable to Eurogamer, IGN or GameSpot. Neutral, exact, measured. "
            . 'No hype, no exclamation marks, no emojis, no clickbait, no superlatives without evidence, no filler sentences. '
            . 'Short paragraphs of 2 to 4 sentences, each one carrying information. Never mention AI or how the text was produced.'
            . "\n\nACCURACY: state only verified facts. VERIFIED FACTS: " . $factsEn
            . "\nYou have NO reliable knowledge of the game's actual content (missions, story events, map details, vehicles, weapons, "
            . 'in-game prices, cheats, collectibles, online mode, patches, sales figures). NEVER describe such specifics unless they appear '
            . 'in the source material quoted in the topic below. Without source material, write context, analysis, series history or '
            . 'practical advice that does not depend on unverified details. Anything unconfirmed must be explicitly labelled "unverified".'
            . "\n\nREGISTER: " . $tones[$toneKey];
        $user = "Topic: \"{$topic}\"\n\n"
            . "Write an original article for ViceHub X.\n"
            . "- LENGTH follows substance: 700 to 1,400 words. A short precise article beats a long padded one. Never pad.\n"
            . "- STRUCTURE: (1) a 2-3 sentence lede that answers the reader's main question immediately; (2) a <ul> of 3-5 key points; "
            . "(3) 3 to 6 <h2> sections with descriptive headings (use <h3> when useful); (4) a <table> ONLY when comparing real data; "
            . "(5) <blockquote> ONLY for an official quote or to flag an unverified claim (start it with \"Unverified:\"); "
            . "(6) a final <h2>Sources</h2> listing ONLY sources actually named in the topic or facts (e.g. Rockstar Newswire), as plain text, omitted if there are none; "
            . "(7) a <h2>FAQ</h2> with 2-3 genuine reader questions, only if useful.\n"
            . "- TITLE: max 60 characters, specific and descriptive, main keyword first, then what the article actually delivers. "
            . "FORBIDDEN in titles: Revealed, Confirmed, Decoded, Unveiled, Everything We Know, Ultimate, Insane, Shocking, emojis, ALL CAPS, and the year unless the topic is time-bound.\n"
            . "- META DESCRIPTION: 140-160 characters, a factual summary of what the reader gets. No hype.\n"
            . "- ALLOWED TAGS ONLY: <p> <h2> <h3> <h4> <ul> <ol> <li> <strong> <em> <blockquote> <table> <thead> <tbody> <tr> <th> <td>. No <a>, no <h1>, no markdown, no closing separator.\n"
            . $avoid . "\n"
            . "STRICT RESPONSE FORMAT (nothing else):\n"
            . 'LINE 1 = compact JSON: {"categorie":"news|guides|leaks|blog|trailers","titre":"title in English, max 60 characters, rules above","extrait":"meta description in English, 140-160 characters","theme_image":"one word among: night, city, beach, car, police, heli, marina, storm, casino, nightlife, drift, sunset, market, plane, swamp","prompt_image":"English prompt for a photorealistic editorial illustration, GTA VI Vice City atmosphere, cinematic 16:9, no text"}' . "\n"
            . "LINE 2 = exactly: ===CORPS===\n"
            . "THEN = the article body in HTML, in English.";
    } else {
        $avoid = '';
        if ($titles) {
            $avoid = "\n\nTITRES DÉJÀ PRÉSENTS SUR LE SITE (ton article doit prendre un angle nettement différent et n'en paraphraser aucun) :\n- "
                . implode("\n- ", $titles) . "\n";
        }
        $system = 'Tu es rédacteur en chef adjoint de ViceHub X, média indépendant consacré à GTA VI (sans lien avec Rockstar Games). '
            . 'Tu écris un français précis, sobre et professionnel.'
            . "\n\nCHARTE ÉDITORIALE : une rédaction professionnelle comparable à Eurogamer, IGN ou Gamekult. Neutre, exacte, mesurée. "
            . 'Pas d\'emphase, pas de points d\'exclamation, pas d\'emojis, pas de titres racoleurs, pas de superlatifs non étayés, aucune phrase de remplissage. '
            . 'Paragraphes courts de 2 à 4 phrases, chacun porteur d\'information. Ne mentionne jamais l\'IA ni la façon dont le texte a été produit.'
            . "\n\nEXACTITUDE : n'affirme que des faits vérifiés. FAITS VÉRIFIÉS : " . $factsFr
            . "\nTu n'as AUCUNE connaissance fiable du contenu réel du jeu (missions, scénario, détails de la carte, véhicules, armes, "
            . 'prix en jeu, codes de triche, objets à collectionner, mode en ligne, correctifs, chiffres de ventes). Ne décris JAMAIS ces éléments '
            . 's\'ils ne figurent pas dans les éléments de source cités dans le sujet ci-dessous. Sans source, écris du contexte, de l\'analyse, '
            . 'l\'histoire de la saga ou des conseils pratiques qui ne dépendent pas de détails non vérifiés. Toute information non confirmée est signalée « non vérifié ».'
            . "\n\nREGISTRE : " . $tones[$toneKey];
        $user = "Sujet : « {$topic} »\n\n"
            . "Rédige un article original pour ViceHub X.\n"
            . "- LONGUEUR selon la matière : 700 à 1 400 mots. Un article court et précis vaut mieux qu'un long article délayé. Ne remplis jamais.\n"
            . "- STRUCTURE : (1) un chapeau de 2-3 phrases qui répond tout de suite à la question principale du lecteur ; (2) un <ul> de 3 à 5 points clés ; "
            . "(3) 3 à 6 sections <h2> aux intertitres descriptifs (avec <h3> si utile) ; (4) un <table> UNIQUEMENT pour comparer des données réelles ; "
            . "(5) un <blockquote> UNIQUEMENT pour une citation officielle ou pour signaler une information non vérifiée (commence-le par « Non vérifié : ») ; "
            . "(6) une section finale <h2>Sources</h2> listant SEULEMENT les sources réellement citées dans le sujet ou les faits (ex. Rockstar Newswire), en texte simple, omise s'il n'y en a pas ; "
            . "(7) une <h2>FAQ</h2> de 2-3 vraies questions de lecteurs, seulement si elle est utile.\n"
            . "- TITRE : 60 caractères maximum, précis et descriptif, mot-clé principal en tête, puis ce que l'article apporte réellement. "
            . "INTERDITS dans le titre : Révélé, Confirmé, Décrypté, Dévoilé, Tout ce qu'on sait, Ultime, Choc, Incroyable, emojis, MAJUSCULES, et l'année sauf si le sujet est daté.\n"
            . "- META DESCRIPTION : 140-160 caractères, résumé factuel de ce que le lecteur va trouver. Sans emphase.\n"
            . "- BALISES AUTORISÉES UNIQUEMENT : <p> <h2> <h3> <h4> <ul> <ol> <li> <strong> <em> <blockquote> <table> <thead> <tbody> <tr> <th> <td>. Aucun <a>, aucun <h1>, aucun markdown, aucun séparateur final.\n"
            . $avoid . "\n"
            . "FORMAT DE RÉPONSE STRICT (rien d'autre) :\n"
            . 'LIGNE 1 = JSON compact : {"categorie":"news|guides|leaks|blog|trailers","titre":"titre en français, 60 caractères max, règles ci-dessus","extrait":"meta description en français, 140-160 caractères","theme_image":"un mot parmi : night, city, beach, car, police, heli, marina, storm, casino, nightlife, drift, sunset, market, plane, swamp","prompt_image":"prompt EN ANGLAIS pour une illustration éditoriale photoréaliste, ambiance GTA VI Vice City, cinématique 16:9, sans texte"}' . "\n"
            . "LIGNE 2 = exactement : ===CORPS===\n"
            . "PUIS = le corps de l'article en HTML.";
    }

    $raw = anthropic_complete($system, $user, 6500);

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

    $title   = ai_clean_title(trim((string) $json['titre']));
    $excerpt = clean_ai_markers(mb_substr(trim((string) ($json['extrait'] ?? '')), 0, 200));
    // Nettoie tout marqueur IA résiduel (===FIN===…) : le lecteur ne doit jamais le voir.
    $body    = clean_ai_markers(strip_tags($bodyHtml, '<p><h2><h3><h4><ul><ol><li><strong><em><blockquote><br><table><thead><tbody><tr><th><td><figure><figcaption>'));
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
        'lang'         => $lang,
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
    // Anti-doublon de SUJET : avant, seuls les 40 derniers titres étaient vérifiés, d'où
    // des dizaines d'articles quasi identiques (néon, coop, stockage…) que Google refuse
    // d'indexer. On compare désormais à TOUS les articles de la langue.
    if (function_exists('prune_closest_title')) {
        $dupLang = (($data['lang'] ?? 'fr') === 'en') ? 'en' : 'fr';
        $dupMax  = (float) (get_setting('ai_dup_threshold', '0.5') ?: 0.5);
        [$dupSim, $dupSlug] = prune_closest_title((string) $data['title'], $dupLang);
        if ($dupSim >= $dupMax) {
            set_setting('ai_dup_last', date('Y-m-d H:i') . ' — « ' . mb_substr((string) $data['title'], 0, 120) . ' » trop proche de ' . $dupSlug . ' (' . round($dupSim, 2) . ')');
            return null;
        }
    }
    $status = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'draft';
    // Publié → maintenant ; Programmé (pending) → prochain créneau CRON ; Brouillon → sans date.
    $interval = max(1, (int) get_setting('ai_auto_interval', '48'));
    $pub = $status === 'published'
        ? date('Y-m-d H:i:s')
        : ($status === 'pending' ? ai_next_slot($interval) : null);

    // Visuel : si un IMAGE SOURCE est fourni (veille concurrents), on le garde (crédité) et
    // on NE génère PAS d'illustration. Sinon illustration IA sur-mesure (Higgsfield) si activée.
    if (!empty($data['source_image'])) {
        $data['image'] = (string) $data['source_image'];
    } elseif (ai_img_enabled() && !empty($data['image_prompt'])) {
        $gen = ai_generate_image((string) $data['image_prompt'], $slug);
        if ($gen) { $data['image'] = $gen; }
    }

    $lang = (($data['lang'] ?? 'fr') === 'en') ? 'en' : 'fr';

    // La colonne source_credit n'est PAS garantie (migration best-effort). On ne la met
    // dans l'INSERT que si elle existe RÉELLEMENT : ainsi un droit DDL manquant ou un
    // MySQL sans "IF NOT EXISTS" ne casse jamais TOUTE la génération (et évite un coût
    // API qui tournerait en boucle à chaque heartbeat sur un INSERT invalide).
    if (ai_has_source_credit_col()) {
        $st = db()->prepare(
            'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, image_prompt, source_credit, author_id, status, published_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $st->execute([
            ai_cat_id((string) ($data['category'] ?? 'blog')), $lang, $data['title'], $slug, $data['excerpt'], $data['body'],
            $data['image'], $data['image_prompt'], ($data['source_credit'] ?? null), $authorId, $status, $pub,
        ]);
    } else {
        $st = db()->prepare(
            'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, image_prompt, author_id, status, published_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $st->execute([
            ai_cat_id((string) ($data['category'] ?? 'blog')), $lang, $data['title'], $slug, $data['excerpt'], $data['body'],
            $data['image'], $data['image_prompt'], $authorId, $status, $pub,
        ]);
    }
    $newId = (int) db()->lastInsertId();
    // Indexation INSTANTANÉE (IndexNow → Bing/Yandex…) dès qu'un article est publié.
    if ($status === 'published' && $newId > 0 && function_exists('indexnow_ping')) {
        $base = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : '';
        if ($base !== '') { indexnow_ping([$base . '/article/' . rawurlencode($slug)]); }
    }
    return $newId;
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

/** Ajoute N articles à la FILE de génération en arrière-plan (statut + personnalité + langue). */
function ai_queue_add(int $n, string $status, string $tone = 'multi', string $lang = 'fr'): void
{
    $status = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'draft';
    set_setting('ai_gen_status', $status);
    $tone = ($tone === 'multi' || in_array($tone, array_keys(ai_tones()), true)) ? $tone : 'multi';
    set_setting('ai_gen_tone', $tone);
    $lang = in_array($lang, ['fr', 'en', 'both'], true) ? $lang : 'fr';
    set_setting('ai_gen_lang', $lang);
    set_setting('ai_gen_queue', (string) max(0, (int) get_setting('ai_gen_queue', '0') + max(0, $n)));
}

/**
 * File de BRIEFS : sujets PRÉCIS fournis par l'admin (≠ sujets auto aléatoires).
 * Idéal le jour d'un gros événement (ex. reveal GTA 6) : on colle une ligne par
 * angle observé, l'IA écrit un article complet pour chacun. Drainée par le même
 * worker en arrière-plan. @return nombre de briefs ajoutés.
 */
function ai_brief_add(array $briefs, string $status = 'published', string $lang = 'fr'): int
{
    $status = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'published';
    $lang   = in_array($lang, ['fr', 'en'], true) ? $lang : 'fr';
    $q = json_decode((string) get_setting('ai_brief_queue', '[]'), true) ?: [];
    $added = 0;
    foreach ($briefs as $b) {
        $b = trim((string) $b);
        if ($b === '') { continue; }
        $q[] = ['t' => mb_substr($b, 0, 400), 's' => $status, 'l' => $lang];
        $added++;
    }
    set_setting('ai_brief_queue', json_encode(array_slice($q, 0, 100), JSON_UNESCAPED_UNICODE));
    return $added;
}

/** Ajoute UN brief ENRICHI (image source + crédit) — utilisé par la veille concurrents. */
function ai_brief_add_rich(string $topic, string $status = 'published', string $lang = 'fr', string $img = '', string $src = ''): int
{
    $status = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'published';
    $lang   = in_array($lang, ['fr', 'en'], true) ? $lang : 'fr';
    $topic  = trim($topic);
    if ($topic === '') { return 0; }
    $q = json_decode((string) get_setting('ai_brief_queue', '[]'), true) ?: [];
    $q[] = ['t' => mb_substr($topic, 0, 1800), 's' => $status, 'l' => $lang,   // sujet + résumé source
            'img' => mb_substr($img, 0, 500), 'src' => mb_substr($src, 0, 120)];
    set_setting('ai_brief_queue', json_encode(array_slice($q, 0, 100), JSON_UNESCAPED_UNICODE));
    return 1;
}

/** Nombre de briefs en attente. */
function ai_brief_count(): int
{
    return count(json_decode((string) get_setting('ai_brief_queue', '[]'), true) ?: []);
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
    $langSel  = (string) get_setting('ai_gen_lang', 'fr');    // 'fr' / 'en' / 'both' (alterne)
    $authorId = ai_admin_author_id();
    $deadline = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $done = 0;

    // 1) D'ABORD les BRIEFS (sujets précis fournis — priorité, ex. jour du reveal GTA 6).
    $briefs = json_decode((string) get_setting('ai_brief_queue', '[]'), true) ?: [];
    while ($briefs && time() < $deadline) {
        $item = array_shift($briefs);
        // On retire le brief AVANT de générer (évite un doublon si le process est tué en cours).
        set_setting('ai_brief_queue', json_encode(array_values($briefs), JSON_UNESCAPED_UNICODE));
        try {
            $bStatus = in_array(($item['s'] ?? 'published'), ['draft', 'pending', 'published'], true) ? $item['s'] : 'published';
            $bLang   = in_array(($item['l'] ?? 'fr'), ['fr', 'en'], true) ? $item['l'] : 'fr';
            $art = ai_generate_article((string) ($item['t'] ?? ''), ai_resolve_tone($toneSel, $done), $bLang);
            if (!empty($item['img'])) { $art['source_image'] = (string) $item['img']; }   // visuel de la source (crédité)
            if (!empty($item['src'])) { $art['source_credit'] = (string) $item['src']; }
            ai_save_article($art, $bStatus, $authorId);
            $done++;
        } catch (Throwable $e) {
            break; // erreur API : on s'arrête, on reprendra plus tard
        }
        set_setting('ai_worker_lock', (string) time());
        $briefs = json_decode((string) get_setting('ai_brief_queue', '[]'), true) ?: []; // re-lit (ajouts concurrents)
    }

    // 2) PUIS la file "auto" (sujets aléatoires du pool).
    while ((int) get_setting('ai_gen_queue', '0') > 0 && time() < $deadline) {
        try {
            // Multi → chaque article change de personnalité/langue (rotation) ; sinon fixe.
            $art = ai_generate_article(null, ai_resolve_tone($toneSel, $done), ai_resolve_lang($langSel, $done));
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
    // Intervalle LENT par défaut (48 h) : on espace fortement la génération auto
    // pour que Google reprenne confiance (anti « scaled content abuse »).
    $interval = max(1, (int) get_setting('ai_auto_interval', '48'));
    $autoOn   = (int) get_setting('ai_auto_enabled', '0') === 1;
    $now      = time();

    // Auto-répare : donne une date espacée aux articles programmés sans date.
    ai_schedule_pending($interval);

    // Publie TOUS les articles PROGRAMMÉS (datés) dont l'heure est arrivée.
    // Les contributions en modération ont published_at NULL → jamais publiées ici.
    $due = db()->query(
        "SELECT id FROM articles WHERE status='pending' AND published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at ASC"
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
    $stillSched = (int) db()->query("SELECT COUNT(*) FROM articles WHERE status='pending' AND published_at IS NOT NULL")->fetchColumn();
    // Après la sortie : plus de sujets tirés au hasard dans la liste d'avant-sortie (l'IA
    // inventerait le contenu du jeu). Les articles automatiques viennent alors uniquement
    // de la veille (vraies infos sourcées). Réactivable : réglage ai_random_topics = 1.
    $randomOk = !ai_is_postlaunch() || (int) get_setting('ai_random_topics', '0') === 1;
    if ($autoOn && $randomOk && $stillSched === 0 && ($last === 0 || ($now - $last) >= $interval * 3600)) {
        $st = get_setting('ai_auto_status', 'published') === 'draft' ? 'draft' : 'published';
        $autoTone = (string) get_setting('ai_auto_tone', 'multi');
        $autoLang = (string) get_setting('ai_auto_lang', 'fr');
        try {
            $id = null; $t = 0;
            $seed = (int) get_setting('ai_auto_last', '0');
            while (!$id && $t < 3) { $id = ai_save_article(ai_generate_article(null, ai_resolve_tone($autoTone, $seed + $t), ai_resolve_lang($autoLang, $seed + $t)), $st, ai_admin_author_id()); $t++; }
            if ($id) { set_setting('ai_auto_last', (string) $now); $msgs[] = '1 article ' . ($st === 'published' ? 'publié' : 'en brouillon'); }
        } catch (Throwable $e) { $msgs[] = 'erreur API : ' . $e->getMessage(); }
    }

    // 3) TRADUCTION EN — rythme LENT (anti « contenu à grande échelle » Google).
    //    Plafond QUOTIDIEN (ai_tr_daily_max, défaut 2) : les versions EN sortent au
    //    compte-gouttes au lieu de vider le backlog d'un coup (ce qui avait créé
    //    ~1000 pages EN d'un bloc). Le bouton « Traduire » de l'admin reste illimité.
    if ((int) get_setting('ai_tr_auto', '0') === 1 && ai_untranslated_count() > 0) {
        $trToday = date('Y-m-d');
        if (get_setting('ai_tr_day', '') !== $trToday) {
            set_setting('ai_tr_day', $trToday);
            set_setting('ai_tr_count', '0');
        }
        $trMax   = max(0, min(20, (int) (get_setting('ai_tr_daily_max', '2') ?: 2)));
        $trCount = (int) get_setting('ai_tr_count', '0');
        if ($trMax > 0 && $trCount < $trMax) {
            // Petit budget : ~1 article par cycle → filet lent et régulier.
            $tr = ai_translate_missing((int) max(20, min(60, $budgetSeconds / 4)));
            if ($tr > 0) {
                set_setting('ai_tr_count', (string) ($trCount + $tr));
                $msgs[] = "{$tr} article(s) traduit(s) en anglais";
            }
        }
    }

    // 4) RÉSEAUX SOCIAUX — poste les nouveaux articles publiés sur Facebook/Instagram.
    if ((int) get_setting('social_auto', '0') === 1) {
        require_once ROOT_PATH . '/includes/social.php';
        if (social_any_ready()) {
            $sp = social_drain((int) max(15, $budgetSeconds / 4));
            if ($sp['posted'] > 0) { $msgs[] = "{$sp['posted']} publication(s) réseaux"; }
        }
    }

    if (!$msgs) {
        // Prochain article programmé (date la plus proche à venir).
        $next = db()->query("SELECT MIN(published_at) FROM articles WHERE status='pending' AND published_at IS NOT NULL AND published_at > NOW()")->fetchColumn();
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
        "SELECT COUNT(*) FROM articles WHERE status='pending' AND published_at IS NOT NULL"
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

/* ================================================================== */
/*  TRADUCTION EN ANGLAIS — versions EN des articles FR déjà créés     */
/*  (URL/slug anglais, même image & catégorie, aucun coût Higgsfield). */
/* ================================================================== */

/**
 * Garantit la colonne `source_id` sur `articles` (lie une VO anglaise à sa
 * source FR pour ne jamais retraduire). Vérifie information_schema d'abord
 * (compatible multi-BDD / droits limités : reste silencieux si impossible).
 */
function ai_ensure_source_col(): void
{
    static $done = false;
    if ($done) { return; }
    $done = true;
    try {
        $has = (int) db()->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'articles' AND COLUMN_NAME = 'source_id'"
        )->fetchColumn();
        if ($has === 0) {
            db()->exec('ALTER TABLE articles ADD COLUMN source_id INT DEFAULT NULL');
            try { db()->exec('ALTER TABLE articles ADD INDEX idx_source (source_id)'); } catch (Throwable $e) { /* index best-effort */ }
        }
    } catch (Throwable $e) {
        // Colonne déjà présente ou droits DDL limités : on continue sans bloquer.
    }
}

/**
 * La colonne `source_credit` existe-t-elle sur `articles` ? (crédit « Source : … »
 * des articles issus de la veille concurrents). Best-effort : on tente de la créer
 * si absente, mais on ne renvoie true que si elle est RÉELLEMENT présente — l'INSERT
 * ne l'inclut alors que dans ce cas, donc aucun risque de panne si l'ALTER échoue
 * (droits DDL restreints, ou MySQL qui ne connaît pas « ADD COLUMN IF NOT EXISTS »).
 */
function ai_has_source_credit_col(): bool
{
    static $has = null;
    if ($has !== null) { return $has; }
    $has = false;
    try {
        $has = ((int) db()->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'articles' AND COLUMN_NAME = 'source_credit'"
        )->fetchColumn()) > 0;
        if (!$has) {
            try { db()->exec('ALTER TABLE articles ADD COLUMN source_credit VARCHAR(120) DEFAULT NULL'); $has = true; }
            catch (Throwable $e) { /* droits DDL limités : on insérera sans cette colonne */ }
        }
    } catch (Throwable $e) {
        $has = false;
    }
    return $has;
}

/** Génère un slug UNIQUE (ajoute -2, -3… en cas de collision). URL anglaise propre. */
function ai_unique_slug(string $base): string
{
    $base = slugify($base);
    if ($base === '') { return ''; }
    $chk  = db()->prepare('SELECT 1 FROM articles WHERE slug = ? LIMIT 1');
    $slug = $base;
    for ($i = 2; $i <= 60; $i++) {
        $chk->execute([$slug]);
        if (!$chk->fetchColumn()) { return $slug; }
        $slug = $base . '-' . $i;
    }
    return $base . '-' . substr(md5($base . microtime()), 0, 6);
}

/** Articles FR (publiés ou programmés) qui n'ont pas encore leur version EN. */
function ai_untranslated_count(): int
{
    ai_ensure_source_col();
    try {
        return (int) db()->query(
            "SELECT COUNT(*) FROM articles fr
             WHERE fr.lang='fr' AND fr.status IN ('published','pending')
               AND NOT EXISTS (SELECT 1 FROM articles en WHERE en.lang='en' AND en.source_id = fr.id)"
        )->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Traduit un article FR en ANGLAIS via l'API Anthropic (titre + méta + corps).
 * Conserve les balises HTML, les faits et la structure. Aucun marqueur IA.
 * @return array{title:string,excerpt:string,body:string}
 */
function ai_translate_article(array $fr): array
{
    $title   = (string) ($fr['title'] ?? '');
    $excerpt = (string) ($fr['excerpt'] ?? '');
    $body    = (string) ($fr['body'] ?? '');

    $system = 'You are a professional FR→EN translator and native English SEO editor for ViceHub X, an '
        . 'INDEPENDENT, UNOFFICIAL fan media about GTA VI and Vice City. Translate the French article into '
        . 'flawless, natural, idiomatic ENGLISH (not word-for-word) — same meaning, same facts, same structure, '
        . 'same tone. Keep EVERY HTML tag exactly as-is; only translate the text inside tags. Do NOT add, remove '
        . 'or invent any fact. Keep proper nouns unchanged (GTA VI, GTA 6, Vice City, Leonida, Jason Duval, '
        . 'Lucia Caminos, Rockstar Games, Take-Two). Write like a passionate human: never mention AI, add NO '
        . 'technical marker and NO separator like "===END===".';
    $user = "Translate this French article into ENGLISH.\n\n"
        . "STRICT RESPONSE FORMAT (nothing else):\n"
        . 'LINE 1 = compact JSON: {"titre":"UNIQUE English SEO title <=62 chars: MAIN KEYWORD FIRST, add the year 2026 and/or a number when relevant, plus one power word (Confirmed, Revealed, Guide)","extrait":"English meta description 150-160 chars: main keyword in the first 100 chars, a number or date, and a mini call-to-action"}' . "\n"
        . "LINE 2 = exactly: ===CORPS===\n"
        . "THEN = the article body translated into English HTML (keep ALL tags identical, only translate text).\n\n"
        . "FRENCH TITLE: {$title}\n"
        . "FRENCH META: {$excerpt}\n"
        . "FRENCH BODY (HTML):\n{$body}";

    $raw  = anthropic_complete($system, $user, 5200);
    $bpos = strpos($raw, '===CORPS===');
    if ($bpos === false) {
        throw new RuntimeException('Traduction sans séparateur ===CORPS===.');
    }
    $head     = substr($raw, 0, $bpos);
    $bodyHtml = trim(substr($raw, $bpos + strlen('===CORPS===')));
    $js = strpos($head, '{'); $je = strrpos($head, '}');
    $json = ($js !== false && $je !== false && $je > $js) ? json_decode(substr($head, $js, $je - $js + 1), true) : null;
    if (!is_array($json) || empty($json['titre']) || $bodyHtml === '') {
        throw new RuntimeException('Traduction incomplète (en-tête/corps manquant).');
    }

    $enTitle   = trim((string) $json['titre']);
    $enExcerpt = clean_ai_markers(mb_substr(trim((string) ($json['extrait'] ?? '')), 0, 200));
    $enBody    = clean_ai_markers(strip_tags($bodyHtml, '<p><h2><h3><ul><ol><li><strong><em><blockquote><br>'));

    return [
        'title'   => $enTitle,
        'excerpt' => $enExcerpt !== '' ? $enExcerpt : mb_substr(strip_tags($enBody), 0, 160),
        'body'    => $enBody,
    ];
}

/**
 * Enregistre la VO anglaise d'un article FR : même catégorie, même image (0 coût
 * Higgsfield), même statut/date, mais SLUG ANGLAIS (URL anglaise) et source_id.
 * @return int|null id inséré, ou null si slug invalide.
 */
function ai_save_translation(array $fr, array $tr, ?int $authorId): ?int
{
    $slug = ai_unique_slug($tr['title']);
    if ($slug === '') { return null; }
    $status = in_array($fr['status'] ?? 'published', ['draft', 'pending', 'published'], true) ? $fr['status'] : 'published';
    $st = db()->prepare(
        'INSERT INTO articles (category_id, lang, source_id, title, slug, excerpt, body, badge, image, image_prompt, author_id, status, published_at, created_at)
         VALUES (?, "en", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $st->execute([
        $fr['category_id'] ?: ai_blog_cat_id(),
        (int) $fr['id'],
        $tr['title'], $slug, $tr['excerpt'], $tr['body'],
        $fr['badge'] ?? null,
        $fr['image'], $fr['image_prompt'],
        $authorId ?: ($fr['author_id'] ?? null),
        $status, $fr['published_at'] ?? null,
    ]);
    return (int) db()->lastInsertId();
}

/**
 * Traduit en anglais tous les articles FR sans version EN (arrière-plan).
 * Verrou anti-double-exécution. $budget=0 → traite tout (CLI). Les échecs isolés
 * sont sautés ; on stoppe si trop d'échecs d'affilée (clé/quota API).
 * @return int nombre d'articles traduits
 */
function ai_translate_missing(int $budgetSeconds = 0): int
{
    if (!ai_enabled()) { return 0; }
    ai_ensure_source_col();
    $lock = (int) get_setting('ai_tr_lock', '0');
    if (time() - $lock < 600) { return 0; }          // verrou 10 min (traduction lente)
    set_setting('ai_tr_lock', (string) time());

    $deadline = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $done = 0; $fail = 0; $failedIds = [];
    $authorId = ai_admin_author_id();
    try {
        while (time() < $deadline && $fail < 5) {
            $excl = $failedIds ? (' AND fr.id NOT IN (' . implode(',', array_map('intval', $failedIds)) . ')') : '';
            $fr = db()->query(
                "SELECT fr.* FROM articles fr
                 WHERE fr.lang='fr' AND fr.status IN ('published','pending')
                   AND NOT EXISTS (SELECT 1 FROM articles en WHERE en.lang='en' AND en.source_id = fr.id)$excl
                 ORDER BY (fr.status='published') DESC, fr.id DESC LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC);
            if (!$fr) { break; }
            try {
                $tr = ai_translate_article($fr);
                $id = ai_save_translation($fr, $tr, $authorId);
                if ($id) {
                    $done++;
                    set_setting('ai_tr_lock', (string) time());   // rafraîchit le verrou
                } else {
                    $fail++; $failedIds[] = (int) $fr['id'];
                }
            } catch (Throwable $e) {
                $fail++; $failedIds[] = (int) $fr['id'];           // saute cet article
            }
        }
    } finally {
        set_setting('ai_tr_lock', '0');                           // libère
    }
    return $done;
}

/**
 * Progression de la traduction EN (barre de %). Repère haut posé au lancement,
 * remis à zéro une fois tout traduit.
 * @return array{total:int,remaining:int,done:int,percent:int}
 */
function ai_translate_progress(): array
{
    $remaining = ai_untranslated_count();
    $total = (int) get_setting('ai_tr_total', '0');
    if ($remaining <= 0) {
        if ($total !== 0) { set_setting('ai_tr_total', '0'); }
        return ['total' => 0, 'remaining' => 0, 'done' => 0, 'percent' => 100];
    }
    if ($remaining > $total) { $total = $remaining; set_setting('ai_tr_total', (string) $total); }
    $doneN   = max(0, $total - $remaining);
    $percent = $total > 0 ? (int) round($doneN / $total * 100) : 0;
    return ['total' => $total, 'remaining' => $remaining, 'done' => $doneN, 'percent' => $percent];
}
