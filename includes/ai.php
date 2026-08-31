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

/** Tons rédactionnels (pour toucher un maximum de lecteurs). Bilingue FR/EN. */
function ai_tones(string $lang = 'fr'): array
{
    if ($lang === 'en') {
        return [
            'journalistique' => 'JOURNALISTIC TONE: factual, structured and credible, like a major gaming outlet (IGN, GameSpot). Put facts in perspective, cite numbers, stay neutral and professional.',
            'joueur'         => 'GAMER TONE: direct, enthusiastic, "controller-level". Talk about gameplay, fun and hype, with a few natural gaming expressions.',
            'connaisseur'    => 'CONNOISSEUR TONE: GTA saga expert. Precise references to previous entries, sharp analysis, mastered vocabulary, historical context.',
            'passionne'      => 'PASSIONATE TONE: vibrant and nostalgic about Vice City, emotional, conveying love for the franchise and building anticipation.',
            'geek'           => 'GEEK TONE: technical depth (RAGE engine, 60 fps, physics, NPC AI, ray tracing), easter eggs, theories and hidden trailer details.',
        ];
    }
    return [
        'journalistique' => 'TON JOURNALISTIQUE : factuel, structuré et crédible, façon grand média gaming (IGN, JVC). Mets les faits en perspective, cite des chiffres, reste neutre et pro.',
        'joueur'         => 'TON JOUEUR : direct, enthousiaste, « à hauteur de manette ». Parle d\'expérience de jeu, de fun, de hype, avec quelques expressions gaming naturelles.',
        'connaisseur'    => 'TON CONNAISSEUR : expert de la saga GTA. Références précises aux opus précédents, analyse fine, vocabulaire maîtrisé, mise en contexte historique.',
        'passionne'      => 'TON PASSIONNÉ : vibrant et nostalgique de Vice City, chargé d\'émotion, qui transmet l\'amour de la licence et fait monter l\'attente.',
        'geek'           => 'TON GEEK : pointu sur la technique (moteur RAGE, 60 fps, physique, IA des PNJ, ray tracing), les easter eggs, les théories et les détails cachés des trailers.',
    ];
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

    $facts = 'game developed by Rockstar Games (publisher Take-Two); 1st trailer December 2023, 2nd trailer 2025; '
        . 'release announced November 19, 2026 on PS5 and Xbox Series X|S; Standard ($79.99) and Ultimate ($99.99) editions; '
        . 'dual protagonists Jason Duval & Lucia Caminos (Lucia = first female lead of the main series); fictional state of '
        . 'Leonida (inspired by Florida) and return of Vice City; RAGE engine; radio stations incl. V-Rock. Everything else '
        . '(detailed map, exact vehicles, PC date, online content) is NOT confirmed: treat it as rumor.';

    if ($lang === 'en') {
        $avoid = '';
        if ($titles) {
            $avoid = "\n\n⛔ ALREADY USED ON THE SITE — do NOT reuse or paraphrase (find a 100% different TITLE and META, targeting OTHER keywords):\n"
                . "Existing titles:\n- " . implode("\n- ", $titles) . "\n"
                . ($metas ? "Existing metas:\n- " . implode("\n- ", $metas) . "\n" : '');
        }
        $system = 'You are a SENIOR SEO writer for ViceHub X, an INDEPENDENT, UNOFFICIAL fan media about GTA VI and Vice City. '
            . 'You write flawless, rich, fluent ENGLISH. '
            . "\n\nGOLDEN RULE — RELIABILITY: publish ONLY VERIFIED information. NEVER invent a date, price, name, feature or "
            . 'statement. ALWAYS clearly separate CONFIRMED FACTS from rumors: any unofficial info must be explicitly framed '
            . 'as "rumor", "unconfirmed" or "according to leaks". No speculation presented as fact. When in doubt, stay '
            . 'general or present it as community discussion. '
            . "\n\nCONFIRMED FACTS (use as-is): " . $facts
            . "\n\nGoal: a REFERENCE article, accurate and useful, worthy of Google's #1 spot and an AI Overview. "
            . 'Write like a PASSIONATE HUMAN (never mention AI). ' . $tones[$toneKey];
        $user = "Write a COMPLETE, ORIGINAL article of about 2000 words on: \"{$topic}\".\n\n"
            . "Rules:\n"
            . "- ABSOLUTE RELIABILITY: state only VERIFIED facts. Any unofficial info = framed as rumor/leak (\"according to rumors…\", \"unconfirmed\"). Never invent a date, price, name or feature.\n"
            . "- 100% UNIQUE TITLE and META DESCRIPTION: never the same as another article. Each article targets DIFFERENT keywords/angle to capture maximum Google searches (long tail).\n"
            . "- ~2000 words, rich, no filler, SEO-optimized (natural keywords: GTA 6, GTA VI, Vice City, Leonida).\n"
            . "- Structure: strong hook, then 5-7 <h2> sections (with <h3> if useful), <ul>/<ol> lists, and a final <h2>FAQ</h2> section with 3-4 questions as <h3>Question?</h3><p>Answer</p> (ideal for Google AI Overview).\n"
            . "- ALLOWED tags only: <p> <h2> <h3> <ul> <ol> <li> <strong> <em> <blockquote>. NO <a>, NO <h1>, NO markdown.\n"
            . "- Write like a PASSIONATE HUMAN: NEVER say you are an AI, add NO technical marker. Do NOT end with \"===END===\" or any separator: the last FAQ <p> ends the article.\n"
            . $avoid . "\n"
            . "STRICT RESPONSE FORMAT (nothing else):\n"
            . 'LINE 1 = compact JSON: {"categorie":"news|guides|leaks|blog|trailers","titre":"unique catchy title <=90 chars with the main keyword (IN ENGLISH)","extrait":"meta description <=180 chars (IN ENGLISH)","theme_image":"one word among: night, city, beach, car, police, heli, marina, storm, casino, nightlife, drift, sunset, market, plane, swamp","prompt_image":"ENGLISH prompt for a photorealistic Higgsfield illustration, GTA VI Vice City neon cinematic 16:9, no text"}' . "\n"
            . "LINE 2 = exactly: ===CORPS===\n"
            . "THEN = the article body in HTML (~2000 words, IN ENGLISH).";
    } else {
        $avoid = '';
        if ($titles) {
            $avoid = "\n\n⛔ DÉJÀ UTILISÉS SUR LE SITE — INTERDIT de reprendre ou paraphraser (trouve un TITRE et une META 100% différents, ciblant d'AUTRES mots-clés) :\n"
                . "Titres existants :\n- " . implode("\n- ", $titles) . "\n"
                . ($metas ? "Metas existantes :\n- " . implode("\n- ", $metas) . "\n" : '');
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
            . 'LIGNE 1 = JSON compact : {"categorie":"news|guides|leaks|blog|trailers","titre":"titre SEO UNIQUE <=62 car. : MOT-CLÉ PRINCIPAL EN TÊTE, + si pertinent l\'année 2026 et/ou un chiffre, + un mot fort (Confirmé, Révélé, Guide, Tout ce qu\'on sait)","extrait":"meta description 150-160 car. : mot-clé principal dans les 100 premiers caractères, avec un chiffre ou une date, et un mini appel à l\'action (Découvrez, On fait le point)","theme_image":"un mot parmi: night, city, beach, car, police, heli, marina, storm, casino, nightlife, drift, sunset, market, plane, swamp","prompt_image":"prompt EN ANGLAIS pour une illustration photorealiste Higgsfield, GTA VI Vice City neon cinematic 16:9, no text"}' . "\n"
            . "LIGNE 2 = exactement : ===CORPS===\n"
            . "PUIS = le corps de l'article en HTML (~2000 mots).";
    }

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

    $lang = (($data['lang'] ?? 'fr') === 'en') ? 'en' : 'fr';
    $st = db()->prepare(
        'INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, image_prompt, author_id, status, published_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $st->execute([
        ai_blog_cat_id(), $lang, $data['title'], $slug, $data['excerpt'], $data['body'],
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
    $interval = max(1, (int) get_setting('ai_auto_interval', '6'));
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
    if ($autoOn && $stillSched === 0 && ($last === 0 || ($now - $last) >= $interval * 3600)) {
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

    // 3) TRADUCTION EN — si activée, traduit quelques articles FR encore sans VO
    //    anglaise (les nouveaux publiés gardent ainsi une version EN au fil de l'eau).
    if ((int) get_setting('ai_tr_auto', '0') === 1 && ai_untranslated_count() > 0) {
        $tr = ai_translate_missing((int) max(20, $budgetSeconds / 3));
        if ($tr > 0) { $msgs[] = "{$tr} article(s) traduit(s) en anglais"; }
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
        . 'LINE 1 = compact JSON: {"titre":"unique English title <=90 chars, catchy, SEO, keeps the main keyword","extrait":"English meta description <=180 chars"}' . "\n"
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
