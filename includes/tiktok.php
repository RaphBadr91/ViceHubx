<?php
/**
 * ViceHub X — Auto-publication TIKTOK (Content Posting API v2).
 *
 * Publie des vidéos courtes (15-25 s, verticales 9:16) sur le compte TikTok de
 * ViceHub X, en ANGLAIS (audience maximale). Les vidéos sont générées via
 * Higgsfield (URL CDN), mises en file, puis envoyées automatiquement à TikTok.
 *
 * Deux modes :
 *   - « draft »  → /post/publish/inbox/video/init/  (scope video.upload)
 *                  La vidéo arrive dans la boîte de réception TikTok du créateur,
 *                  qui la finalise/poste en 1 tap. FONCTIONNE SANS AUDIT.
 *   - « public » → /post/publish/video/init/        (scope video.publish)
 *                  Publication publique 100% automatique. NÉCESSITE l'audit TikTok.
 *
 * Hébergement vidéo : TikTok (PULL_FROM_URL) exige un domaine VÉRIFIÉ. On
 * rapatrie donc la vidéo Higgsfield en local (/uploads/tiktok/<id>.mp4) et on
 * fournit une URL https://vicehubx.com/... (domaine vérifié dans le portail).
 *
 * DORMANT tant que la clé/le secret/le jeton ne sont pas renseignés : aucun
 * appel réseau, aucun blocage (comme le module Réseaux/Meta).
 */

/* ------------------------------------------------------------------ */
/*  Identifiants & réglages                                            */
/* ------------------------------------------------------------------ */
function tiktok_client_key(): string    { return trim((string) get_setting('tiktok_client_key', '')); }
function tiktok_client_secret(): string { return trim((string) get_setting('tiktok_client_secret', '')); }

/** Base HTTPS absolue du site (partagée avec le module Réseaux). */
function tiktok_base(): string
{
    if (function_exists('social_base')) { return social_base(); }
    $u = trim((string) (get_setting('site_public_url', '') ?: ''));
    if ($u === '') { $u = defined('BASE_URL') && BASE_URL !== '' ? BASE_URL : 'https://vicehubx.com'; }
    return rtrim(preg_replace('#^http://#i', 'https://', rtrim($u, '/')) ?: $u, '/');
}

/** URI de redirection OAuth (doit être IDENTIQUE à celle déclarée dans le portail TikTok). */
function tiktok_redirect_uri(): string { return tiktok_base() . '/tiktok-callback.php'; }

/** Scopes demandés lors de l'autorisation. */
function tiktok_scopes(): string { return 'user.info.basic,video.upload,video.publish'; }

/** Mode de publication : « draft » (sans audit) ou « public » (après audit). */
function tiktok_mode(): string
{
    $m = (string) get_setting('tiktok_mode', 'draft');
    return $m === 'public' ? 'public' : 'draft';
}

/* ------------------------------------------------------------------ */
/*  File d'attente                                                     */
/* ------------------------------------------------------------------ */
function tiktok_ensure_table(): void
{
    static $done = false;
    if ($done) { return; }
    $done = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS tiktok_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source_url VARCHAR(1000) NOT NULL,
                title VARCHAR(500) DEFAULT NULL,
                article_id INT DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                publish_id VARCHAR(200) DEFAULT NULL,
                mode VARCHAR(12) DEFAULT NULL,
                error VARCHAR(500) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                posted_at DATETIME DEFAULT NULL,
                INDEX idx_status (status)
            ) ENGINE=InnoDB"
        );
    } catch (Throwable $e) {
        // droits DDL limités : le module reste inactif proprement
    }
}

/* ------------------------------------------------------------------ */
/*  État de connexion                                                  */
/* ------------------------------------------------------------------ */
/** Connecté = clé/secret présents + jeton de rafraîchissement stocké. */
function tiktok_connected(): bool
{
    return tiktok_client_key() !== '' && tiktok_client_secret() !== ''
        && trim((string) get_setting('tiktok_refresh_token', '')) !== '';
}
/** Prêt = connecté ET activé. */
function tiktok_ready(): bool
{
    return tiktok_connected() && (int) get_setting('tiktok_enabled', '0') === 1;
}

/* ------------------------------------------------------------------ */
/*  OAuth 2.0                                                          */
/* ------------------------------------------------------------------ */
/** URL d'autorisation (l'admin y est redirigé pour connecter le compte TikTok). */
function tiktok_auth_url(string $state): string
{
    return 'https://www.tiktok.com/v2/auth/authorize/?' . http_build_query([
        'client_key'    => tiktok_client_key(),
        'scope'         => tiktok_scopes(),
        'response_type' => 'code',
        'redirect_uri'  => tiktok_redirect_uri(),
        'state'         => $state,
    ]);
}

/** POST x-www-form-urlencoded vers l'endpoint OAuth → [int code, array body]. */
function tiktok_oauth_post(array $params): array
{
    $ch = curl_init('https://open.tiktokapis.com/v2/oauth/token/');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false) { return [0, ['error' => 'curl', 'error_description' => $err]]; }
    $json = json_decode((string) $raw, true);
    return [$code, is_array($json) ? $json : ['raw' => $raw]];
}

/** Stocke un jeu de jetons (réponse OAuth). */
function tiktok_store_tokens(array $t): void
{
    if (!empty($t['access_token']))  { set_setting('tiktok_access_token', (string) $t['access_token']); }
    if (!empty($t['refresh_token'])) { set_setting('tiktok_refresh_token', (string) $t['refresh_token']); }
    if (isset($t['expires_in']))     { set_setting('tiktok_token_expires', (string) (time() + (int) $t['expires_in'] - 60)); }
    if (!empty($t['open_id']))       { set_setting('tiktok_open_id', (string) $t['open_id']); }
    if (!empty($t['scope']))         { set_setting('tiktok_scope', (string) $t['scope']); }
}

/** Échange le code d'autorisation contre des jetons. @return array{ok:bool,msg:string} */
function tiktok_exchange_code(string $code): array
{
    if (tiktok_client_key() === '' || tiktok_client_secret() === '') {
        return ['ok' => false, 'msg' => 'Client key/secret manquant.'];
    }
    [$c, $b] = tiktok_oauth_post([
        'client_key'    => tiktok_client_key(),
        'client_secret' => tiktok_client_secret(),
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'redirect_uri'  => tiktok_redirect_uri(),
    ]);
    if ($c === 200 && !empty($b['access_token'])) {
        tiktok_store_tokens($b);
        return ['ok' => true, 'msg' => 'Compte TikTok connecté ✅ (open_id ' . mb_substr((string) ($b['open_id'] ?? ''), 0, 10) . '…).'];
    }
    $m = (string) ($b['error_description'] ?? $b['error'] ?? 'échec inconnu');
    return ['ok' => false, 'msg' => 'Connexion refusée : ' . mb_substr($m, 0, 300)];
}

/** Rafraîchit le jeton d'accès à partir du refresh token. */
function tiktok_refresh(): bool
{
    $rt = trim((string) get_setting('tiktok_refresh_token', ''));
    if ($rt === '' || tiktok_client_key() === '' || tiktok_client_secret() === '') { return false; }
    [$c, $b] = tiktok_oauth_post([
        'client_key'    => tiktok_client_key(),
        'client_secret' => tiktok_client_secret(),
        'grant_type'    => 'refresh_token',
        'refresh_token' => $rt,
    ]);
    if ($c === 200 && !empty($b['access_token'])) { tiktok_store_tokens($b); return true; }
    return false;
}

/** Jeton d'accès VALIDE (rafraîchi automatiquement s'il est proche de l'expiration). */
function tiktok_access_token(): string
{
    $tok = trim((string) get_setting('tiktok_access_token', ''));
    $exp = (int) get_setting('tiktok_token_expires', '0');
    if ($tok === '' || time() >= $exp) {
        if (tiktok_refresh()) { $tok = trim((string) get_setting('tiktok_access_token', '')); }
    }
    return $tok;
}

/** Déconnexion : efface les jetons (garde la clé/le secret). */
function tiktok_disconnect(): void
{
    foreach (['tiktok_access_token', 'tiktok_refresh_token', 'tiktok_token_expires', 'tiktok_open_id', 'tiktok_scope'] as $k) {
        set_setting($k, '');
    }
}

/* ------------------------------------------------------------------ */
/*  Appels API (Content Posting)                                       */
/* ------------------------------------------------------------------ */
/** POST JSON avec Bearer → [int code, array body]. */
function tiktok_api_post(string $url, array $json): array
{
    $tok = tiktok_access_token();
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $tok,
            'Content-Type: application/json; charset=UTF-8',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false) { return [0, ['error' => ['message' => 'cURL : ' . $err]]]; }
    $body = json_decode((string) $raw, true);
    return [$code, is_array($body) ? $body : ['raw' => $raw]];
}

/** Message d'erreur lisible d'une réponse TikTok. */
function tiktok_err(array $body): string
{
    if (isset($body['error']) && is_array($body['error'])) {
        $msg  = (string) ($body['error']['message'] ?? '');
        $code = (string) ($body['error']['code'] ?? '');
        if ($msg !== '' && $code !== '' && $code !== 'ok') { return mb_substr($code . ' — ' . $msg, 0, 480); }
        if ($msg !== '') { return mb_substr($msg, 0, 480); }
    }
    return mb_substr((string) json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 480);
}

/** Interroge les infos du créateur (obligatoire avant un direct post). @return array{ok:bool,data?:array,msg?:string} */
function tiktok_creator_info(): array
{
    [$c, $b] = tiktok_api_post('https://open.tiktokapis.com/v2/post/publish/creator_info/query/', []);
    if ($c === 200 && (($b['error']['code'] ?? '') === 'ok' || isset($b['data']))) {
        return ['ok' => true, 'data' => (array) ($b['data'] ?? [])];
    }
    return ['ok' => false, 'msg' => tiktok_err($b)];
}

/* ------------------------------------------------------------------ */
/*  Transfert vidéo (FILE_UPLOAD : octets envoyés directement à TikTok) */
/*  Pas de PULL_FROM_URL → pas de vérification de propriété d'URL.      */
/* ------------------------------------------------------------------ */
/** Dossier local des vidéos TikTok. */
function tiktok_dir(): string
{
    $d = UPLOAD_DIR . '/tiktok';
    if (!is_dir($d)) { @mkdir($d, 0775, true); }
    return $d;
}

/**
 * Rapatrie la vidéo (URL Higgsfield/CDN) en local et renvoie son CHEMIN local.
 * Si le fichier existe déjà, ne re-télécharge pas.
 * @return string chemin absolu du fichier .mp4, ou '' en cas d'échec.
 */
function tiktok_localize_path(array $row): string
{
    $id  = (int) $row['id'];
    $src = (string) $row['source_url'];
    if ($id <= 0 || $src === '') { return ''; }

    $path = tiktok_dir() . '/' . $id . '.mp4';
    if (!is_file($path) || filesize($path) < 1024) {
        if (!preg_match('#^https?://#i', $src)) { return ''; }
        $fp = @fopen($path, 'wb');
        if (!$fp) { return ''; }
        $ch = curl_init($src);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);
        $ok = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        if (!$ok || !is_file($path) || filesize($path) < 1024) { @unlink($path); return ''; }
    }
    return $path;
}

/**
 * Envoie (PUT) les octets d'un fichier vidéo vers l'URL d'upload fournie par
 * TikTok lors du init FILE_UPLOAD. Un seul « chunk » (vidéos courtes ≤ 64 Mo).
 * @return array{ok:bool,msg?:string}
 */
function tiktok_put_file(string $uploadUrl, string $path, int $size): array
{
    $fp = @fopen($path, 'rb');
    if (!$fp) { return ['ok' => false, 'msg' => 'lecture du fichier impossible']; }
    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_UPLOAD         => true,               // méthode PUT
        CURLOPT_INFILE         => $fp,
        CURLOPT_INFILESIZE     => $size,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: video/mp4',
            'Content-Range: bytes 0-' . ($size - 1) . '/' . $size,
            'Expect:',                                 // désactive le 100-continue
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_CONNECTTIMEOUT => 20,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    if ($code >= 200 && $code < 300) { return ['ok' => true]; }
    return ['ok' => false, 'msg' => 'HTTP ' . $code . ' ' . ($err ?: '') . ' ' . mb_substr((string) $raw, 0, 200)];
}

/* ------------------------------------------------------------------ */
/*  Légende (ANGLAIS, courte + hashtags)                               */
/* ------------------------------------------------------------------ */
function tiktok_caption(array $row): string
{
    $title = trim((string) ($row['title'] ?? ''));
    // Hashtags SPÉCIFIQUES uniquement. On BANNIT #fyp/#foryou/#viral/#fypage et
    // les tags « reach-bait » génériques : TikTok/Instagram les traitent comme de
    // la manipulation et BRIDENT la vidéo (« non éligible au fil Pour Toi »).
    $fallbackTags = '#GTA6 #GTAVI #ViceCity #Rockstar #GTA6News #gaming';

    // Légende FOURNIE telle quelle : si le champ « titre » contient déjà des
    // hashtags (#), on la publie VERBATIM — l'admin garde le contrôle total du
    // texte et des hashtags (pas de réécriture par l'IA).
    if ($title !== '' && strpos($title, '#') !== false) {
        return mb_substr($title, 0, 2200);
    }

    $fallback = function () use ($title, $fallbackTags) {
        $base = $title !== '' ? mb_substr($title, 0, 120) : 'GTA 6 — Vice City is coming 🌴🔥';
        return $base . "\n\n" . $fallbackTags;
    };

    if (!function_exists('ai_enabled') || !ai_enabled()) { return $fallback(); }
    try {
        $sys = 'You are the TikTok manager for ViceHub X, an independent GTA VI / Vice City fan media. '
            . 'Write a SHORT, punchy TikTok caption in ENGLISH: 1 catchy but HONEST hook line (these are '
            . 'stylised fan/AI concept visuals — do NOT imply real leaked GTA 6 footage) + 1-3 emojis + '
            . 'exactly 4-6 SPECIFIC relevant hashtags such as #GTA6 #GTAVI #ViceCity #Rockstar #gaming. '
            . 'STRICTLY FORBIDDEN hashtags: #fyp #fypage #foryou #viral #trending #gamingcommunity #gtafans '
            . 'or any generic reach-bait tag (they get the video flagged and throttled). '
            . 'Never mention AI in the text. Max 150 characters before hashtags.';
        $usr = 'Video topic: "' . ($title !== '' ? $title : 'GTA 6 Vice City hype') . '". '
            . 'Return ONLY the caption text (hook then hashtags on a new line), nothing else.';
        $out = trim(anthropic_complete($sys, $usr, 300));
        if (function_exists('clean_ai_markers')) { $out = clean_ai_markers($out); }
        if ($out === '') { return $fallback(); }
        // Garantit la présence de hashtags.
        if (strpos($out, '#') === false) { $out .= "\n\n" . $fallbackTags; }
        return mb_substr($out, 0, 2000);
    } catch (Throwable $e) {
        return $fallback();
    }
}

/* ------------------------------------------------------------------ */
/*  Publication d'une vidéo                                            */
/* ------------------------------------------------------------------ */
/**
 * Publie une entrée de la file sur TikTok (mode draft=inbox / public=direct).
 * @return array{0:bool,1:string} [ok, publish_id | message d'erreur]
 */
function tiktok_post(array $row): array
{
    // Rapatrie la vidéo en local (on envoie ses octets directement à TikTok).
    $path = tiktok_localize_path($row);
    if ($path === '') { return [false, 'Vidéo introuvable/inaccessible (téléchargement échoué).']; }
    $size = (int) filesize($path);
    if ($size < 1024) { return [false, 'Fichier vidéo vide ou corrompu.']; }
    if ($size > 64 * 1024 * 1024) { return [false, 'Vidéo trop lourde (> 64 Mo) : raccourcis-la.']; }

    // Un seul chunk (vidéos courtes) : chunk_size = video_size, total = 1.
    $sourceInfo = [
        'source'            => 'FILE_UPLOAD',
        'video_size'        => $size,
        'chunk_size'        => $size,
        'total_chunk_count' => 1,
    ];

    $mode = tiktok_mode();

    if ($mode === 'public') {
        // Direct Post. AVANT l'audit video.publish, TikTok REFUSE le public pour une
        // app non auditée (« unaudited_client_can_only_post_to_private_accounts ») :
        // on poste donc en SELF_ONLY (privé, visible par toi) — parfait pour la démo.
        // APRÈS l'audit, active le réglage « tiktok_public_ok » pour publier en public.
        $publicOk = (int) get_setting('tiktok_public_ok', '0') === 1;
        $privacy  = 'SELF_ONLY';
        if ($publicOk) {
            $info = tiktok_creator_info();
            if ($info['ok']) {
                $opts = (array) ($info['data']['privacy_level_options'] ?? []);
                if (in_array('PUBLIC_TO_EVERYONE', $opts, true)) { $privacy = 'PUBLIC_TO_EVERYONE'; }
            }
        }
        $caption = tiktok_caption($row);
        [$c, $b] = tiktok_api_post('https://open.tiktokapis.com/v2/post/publish/video/init/', [
            'post_info' => [
                'title'                    => mb_substr($caption, 0, 2200),
                'privacy_level'            => $privacy,
                'disable_duet'             => false,
                'disable_comment'          => false,
                'disable_stitch'           => false,
                'video_cover_timestamp_ms' => 1000,
            ],
            'source_info' => $sourceInfo,
        ]);
    } else {
        // Draft / Inbox — dépôt dans la boîte de réception (scope video.upload, sans audit).
        [$c, $b] = tiktok_api_post('https://open.tiktokapis.com/v2/post/publish/inbox/video/init/', [
            'source_info' => $sourceInfo,
        ]);
    }

    if ($c !== 200 || empty($b['data']['publish_id']) || empty($b['data']['upload_url'])) {
        return [false, tiktok_err($b)];
    }

    // Envoi des octets vers l'URL d'upload renvoyée par TikTok.
    $put = tiktok_put_file((string) $b['data']['upload_url'], $path, $size);
    if (!$put['ok']) { return [false, 'upload : ' . ($put['msg'] ?? 'échec')]; }

    return [true, (string) $b['data']['publish_id']];
}

/**
 * Interroge le statut de traitement d'une publication (après l'upload).
 * TikTok traite la vidéo de façon asynchrone : un upload OK ne veut pas dire
 * que la vidéo est publiée. @return array{ok:bool,status:string,fail:string}
 */
function tiktok_publish_status(string $publishId): array
{
    [$c, $b] = tiktok_api_post('https://open.tiktokapis.com/v2/post/publish/status/fetch/', ['publish_id' => $publishId]);
    if ($c === 200 && isset($b['data']['status'])) {
        return ['ok' => true, 'status' => (string) $b['data']['status'], 'fail' => (string) ($b['data']['fail_reason'] ?? '')];
    }
    return ['ok' => false, 'status' => '', 'fail' => tiktok_err($b)];
}

/**
 * Attend (en interrogeant TikTok) le résultat final du traitement d'une
 * publication : « done » (publiée / en boîte de réception), « failed » (+raison)
 * ou « processing » (toujours en cours après le délai). @return array{state:string,detail:string}
 */
function tiktok_wait_status(string $publishId, int $tries = 6, int $everySec = 3): array
{
    $last = '';
    for ($i = 0; $i < $tries; $i++) {
        sleep($everySec);
        $s = tiktok_publish_status($publishId);
        if (!$s['ok']) { continue; }
        $last = $s['status'];
        if (in_array($last, ['PUBLISH_COMPLETE', 'SEND_TO_USER_INBOX'], true)) {
            return ['state' => 'done', 'detail' => $last];
        }
        if ($last === 'FAILED') {
            return ['state' => 'failed', 'detail' => $s['fail'] !== '' ? $s['fail'] : 'raison non précisée'];
        }
    }
    return ['state' => 'processing', 'detail' => $last];
}

/* ------------------------------------------------------------------ */
/*  File : ajout, traitement, suivi                                   */
/* ------------------------------------------------------------------ */
/** Ajoute une vidéo à la file. @return int id inséré (0 si échec/doublon). */
function tiktok_enqueue(string $sourceUrl, string $title = '', ?int $articleId = null): int
{
    tiktok_ensure_table();
    $sourceUrl = trim($sourceUrl);
    if ($sourceUrl === '' || !preg_match('#^https?://#i', $sourceUrl)) { return 0; }
    // Dédoublonnage sur l'URL source (pending/posted).
    try {
        $chk = db()->prepare("SELECT id FROM tiktok_queue WHERE source_url=? AND status IN ('pending','posted') LIMIT 1");
        $chk->execute([$sourceUrl]);
        if ($chk->fetchColumn()) { return 0; }
        $ins = db()->prepare('INSERT INTO tiktok_queue (source_url, title, article_id, status) VALUES (?, ?, ?, "pending")');
        $ins->execute([$sourceUrl, ($title !== '' ? mb_substr($title, 0, 500) : null), $articleId]);
        return (int) db()->lastInsertId();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Vide la file : poste les vidéos « pending ». Verrou anti-double-exécution +
 * plafond quotidien. @return array{posted:int,failed:int}
 */
function tiktok_drain(int $budgetSeconds = 0): array
{
    tiktok_ensure_table();
    if (!tiktok_ready()) { return ['posted' => 0, 'failed' => 0]; }

    $lock = (int) get_setting('tiktok_lock', '0');
    if (time() - $lock < 300) { return ['posted' => 0, 'failed' => 0]; }
    set_setting('tiktok_lock', (string) time());

    $cap      = max(1, (int) get_setting('tiktok_daily_max', '3'));
    $today    = tiktok_posted_today();
    $deadline = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $posted = 0; $failed = 0;
    try {
        while (time() < $deadline && $today < $cap) {
            $row = db()->query("SELECT * FROM tiktok_queue WHERE status='pending' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!$row) { break; }

            [$ok, $res] = tiktok_post($row);
            if ($ok) {
                db()->prepare("UPDATE tiktok_queue SET status='posted', publish_id=?, mode=?, posted_at=NOW(), error=NULL WHERE id=?")
                    ->execute([$res, tiktok_mode(), (int) $row['id']]);
                $posted++; $today++;
                // Cross-post Instagram (Reel) si activé — même vidéo, même légende.
                if ((int) get_setting('tiktok_also_ig', '0') === 1) {
                    try { tiktok_post_ig_reel($row); } catch (Throwable $e) { /* n'affecte pas TikTok */ }
                }
            } else {
                db()->prepare("UPDATE tiktok_queue SET status='error', error=? WHERE id=?")
                    ->execute([$res, (int) $row['id']]);
                $failed++;
                if ($failed >= 3) { break; } // jeton/quota probablement en cause
            }
            set_setting('tiktok_lock', (string) time());
        }
    } finally {
        set_setting('tiktok_lock', '0');
    }
    return ['posted' => $posted, 'failed' => $failed];
}

/** Poste TOUT DE SUITE la première vidéo en attente (bouton de test admin). */
function tiktok_test(): array
{
    tiktok_ensure_table();
    if (!tiktok_connected()) { return ['ok' => false, 'msg' => 'TikTok non connecté (clé/secret + autorisation du compte).']; }
    $row = db()->query("SELECT * FROM tiktok_queue WHERE status='pending' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) { return ['ok' => false, 'msg' => '❌ Aucune vidéo en file. Ajoute d\'abord une URL de vidéo (ou demande-moi d\'en générer une via Higgsfield).']; }
    @set_time_limit(0);
    [$ok, $res] = tiktok_post($row);
    if (!$ok) {
        db()->prepare("UPDATE tiktok_queue SET status='error', error=? WHERE id=?")->execute([$res, (int) $row['id']]);
        return ['ok' => false, 'msg' => '❌ TikTok : ' . $res];
    }

    // Upload OK — on vérifie le VRAI résultat du traitement chez TikTok.
    $st = tiktok_wait_status($res);

    if ($st['state'] === 'failed') {
        db()->prepare("UPDATE tiktok_queue SET status='error', error=? WHERE id=?")
            ->execute(['traitement TikTok refusé : ' . $st['detail'], (int) $row['id']]);
        return ['ok' => false, 'msg' => '❌ TikTok a refusé la vidéo au traitement : ' . $st['detail']];
    }

    db()->prepare("UPDATE tiktok_queue SET status='posted', publish_id=?, mode=?, posted_at=NOW(), error=NULL WHERE id=?")
        ->execute([$res, tiktok_mode(), (int) $row['id']]);

    if ($st['state'] === 'done') {
        return $st['detail'] === 'SEND_TO_USER_INBOX'
            ? ['ok' => true, 'msg' => '✅ Vidéo envoyée dans ta boîte de réception TikTok (ouvre l\'app pour finaliser).']
            : ['ok' => true, 'msg' => '✅ Vidéo publiée sur ton profil (en privé) ! Rafraîchis TikTok Studio → Publications.'];
    }
    // Toujours en traitement après le délai d'attente.
    return ['ok' => true, 'msg' => '✅ Vidéo acceptée par TikTok — ⏳ traitement en cours (publish_id ' . mb_substr($res, 0, 12) . '…). Vérifie ton profil dans 2-3 min.'];
}

/**
 * Cross-poste une vidéo de la file en Reel Instagram (même vidéo + même légende).
 * @return array{ok:bool,msg:string}
 */
function tiktok_post_ig_reel(array $row): array
{
    if (!function_exists('social_post_instagram_reel') && defined('ROOT_PATH') && is_file(ROOT_PATH . '/includes/social.php')) {
        require_once ROOT_PATH . '/includes/social.php';
    }
    if (!function_exists('social_ig_ready') || !social_ig_ready()) {
        return ['ok' => false, 'msg' => 'Instagram non configuré (Admin → 📣 Réseaux).'];
    }
    $url = (string) ($row['source_url'] ?? '');
    $cap = tiktok_caption($row);
    [$ok, $res] = social_post_instagram_reel($url, $cap);
    return ['ok' => $ok, 'msg' => $ok ? '✅ Reel Instagram publié (id ' . mb_substr($res, 0, 14) . '…).' : '❌ Instagram : ' . $res];
}

/** Teste le Reel Instagram avec la dernière vidéo de la file (bouton admin). */
function tiktok_test_ig(): array
{
    tiktok_ensure_table();
    $row = db()->query("SELECT * FROM tiktok_queue ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) { return ['ok' => false, 'msg' => '❌ Aucune vidéo en file pour tester le Reel Instagram.']; }
    @set_time_limit(0);
    return tiktok_post_ig_reel($row);
}

/** Nombre de vidéos postées AUJOURD'HUI (plafond quotidien). */
function tiktok_posted_today(): int
{
    try {
        return (int) db()->query("SELECT COUNT(*) FROM tiktok_queue WHERE status='posted' AND DATE(posted_at)=CURDATE()")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** Compteurs pour le tableau de bord. */
function tiktok_stats(): array
{
    tiktok_ensure_table();
    try {
        return [
            'pending' => (int) db()->query("SELECT COUNT(*) FROM tiktok_queue WHERE status='pending'")->fetchColumn(),
            'posted'  => (int) db()->query("SELECT COUNT(*) FROM tiktok_queue WHERE status='posted'")->fetchColumn(),
            'error'   => (int) db()->query("SELECT COUNT(*) FROM tiktok_queue WHERE status='error'")->fetchColumn(),
        ];
    } catch (Throwable $e) {
        return ['pending' => 0, 'posted' => 0, 'error' => 0];
    }
}

/** Dernières entrées de la file (affichage admin). */
function tiktok_recent(int $limit = 12): array
{
    tiktok_ensure_table();
    try {
        $st = db()->prepare("SELECT id, title, status, publish_id, mode, error, posted_at, created_at FROM tiktok_queue ORDER BY id DESC LIMIT ?");
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
