<?php
/**
 * ViceHub X — Auto-publication RÉSEAUX SOCIAUX (Facebook Page + Instagram).
 *
 * Chaque nouvel article publié est mis en file, puis posté automatiquement sur la
 * Page Facebook et/ou le compte Instagram Business via l'API Meta Graph, avec une
 * légende courte + accrocheuse + hashtags générée par l'IA.
 *
 * DORMANT tant que les jetons ne sont pas renseignés (Réglages → Réseaux) : aucun
 * appel réseau, aucun blocage. Activation = coller l'ID de Page + le jeton (comme
 * Higgsfield).
 *
 * Instagram passe par la MÊME Page Facebook (compte IG Business relié à la Page) :
 * on utilise le Page Access Token et l'ID du compte IG Business.
 */

/** Version de l'API Graph utilisée. */
function social_graph_ver(): string
{
    return (string) (get_setting('social_graph_ver', '') ?: 'v21.0');
}

/** Base HTTPS absolue du site (les posts tournent via cron : pas de $_SERVER fiable). */
function social_base(): string
{
    $u = trim((string) (get_setting('site_public_url', '') ?: ''));
    if ($u === '') {
        $u = defined('BASE_URL') && BASE_URL !== '' ? BASE_URL : 'https://vicehubx.com';
    }
    $u = rtrim($u, '/');
    // Force HTTPS (Meta refuse les URL non sécurisées).
    return preg_replace('#^http://#i', 'https://', $u) ?: $u;
}

/** Crée la table de file d'attente si besoin (idempotent, silencieux). */
function social_ensure_table(): void
{
    static $done = false;
    if ($done) { return; }
    $done = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS social_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                article_id INT NOT NULL,
                platform VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                caption TEXT DEFAULT NULL,
                post_id VARCHAR(160) DEFAULT NULL,
                error VARCHAR(500) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                posted_at DATETIME DEFAULT NULL,
                INDEX idx_status (status),
                INDEX idx_ap (article_id, platform)
            ) ENGINE=InnoDB"
        );
    } catch (Throwable $e) {
        // droits DDL limités : le module reste inactif proprement
    }
}

function social_fb_token(): string { return trim((string) get_setting('fb_page_token', '')); }
function social_fb_page(): string  { return trim((string) get_setting('fb_page_id', '')); }
function social_ig_user(): string  { return trim((string) get_setting('ig_user_id', '')); }

/** Facebook prêt (activé + Page + jeton). */
function social_fb_ready(): bool
{
    return (int) get_setting('social_fb_enabled', '0') === 1 && social_fb_page() !== '' && social_fb_token() !== '';
}
/** Instagram prêt (activé + compte IG Business + jeton de Page). */
function social_ig_ready(): bool
{
    return (int) get_setting('social_ig_enabled', '0') === 1 && social_ig_user() !== '' && social_fb_token() !== '';
}
function social_any_ready(): bool { return social_fb_ready() || social_ig_ready(); }

/** Plateformes actuellement prêtes. */
function social_platforms(): array
{
    $p = [];
    if (social_fb_ready()) { $p[] = 'facebook'; }
    if (social_ig_ready()) { $p[] = 'instagram'; }
    return $p;
}

/** URL absolue propre de l'article. */
function social_article_url(array $a): string
{
    return social_base() . '/article/' . rawurlencode((string) $a['slug']);
}

/** URL absolue de l'illustration (publique) de l'article, pour Instagram/Facebook. */
function social_image_url(array $a): string
{
    $img = (string) ($a['image'] ?? '');
    if ($img === '') { return ''; }
    if (preg_match('#^https?://#i', $img)) { return $img; }               // déjà absolue (CDN)
    return social_base() . '/' . ltrim($img, '/');
}

/**
 * Légende COURTE + ACCROCHEUSE + HASHTAGS générée par l'IA (repli sans IA).
 * On garde la légende en base (générée une seule fois par article).
 */
function social_caption(array $a, string $platform = 'facebook'): string
{
    $title   = (string) ($a['title'] ?? '');
    $excerpt = (string) ($a['excerpt'] ?? '');
    $url     = social_article_url($a);

    $fallback = function () use ($title, $url, $platform) {
        $tags = '#GTA6 #GTAVI #ViceCity #Rockstar #GTA6News #Leonida #Gaming';
        $base = mb_substr($title, 0, 150);
        // Instagram : pas de lien cliquable → « lien en bio ». Facebook : lien direct.
        return $platform === 'instagram'
            ? $base . " 🌴🔥\n\n👉 Article complet : lien en bio\n\n" . $tags
            : $base . " 🌴🔥\n\n👉 " . $url . "\n\n" . $tags;
    };

    if (!function_exists('ai_enabled') || !ai_enabled()) {
        return $fallback();
    }
    try {
        $sys = 'Tu es community manager pour ViceHub X, média fan FR sur GTA VI / Vice City. '
            . 'Tu écris des légendes réseaux sociaux COURTES, punchy, qui donnent envie de cliquer. '
            . 'Style : 1 à 2 phrases accrocheuses + 1-3 emojis pertinents + 5 à 8 hashtags ciblés '
            . '(GTA6, GTAVI, ViceCity, Rockstar, gaming…). Jamais de mention d\'IA. Français.';
        $ig = $platform === 'instagram';
        $usr = "Article : « {$title} »\nRésumé : {$excerpt}\n\n"
            . 'Rédige UNIQUEMENT la légende (rien d\'autre), '
            . ($ig ? 'sans lien (Instagram : termine par « 👉 Lien en bio »), ' : "avec l'appel à l'action « 👉 {$url} », ")
            . 'puis les hashtags sur une nouvelle ligne. Max 380 caractères hors hashtags.';
        $out = trim(anthropic_complete($sys, $usr, 400));
        $out = function_exists('clean_ai_markers') ? clean_ai_markers($out) : $out;
        if ($out === '') { return $fallback(); }
        // Facebook : garantit la présence du lien.
        if (!$ig && strpos($out, $url) === false) { $out .= "\n\n👉 " . $url; }
        return mb_substr($out, 0, 2000);
    } catch (Throwable $e) {
        return $fallback();
    }
}

/** POST HTTP simple (form-urlencoded) → [int status, array body]. */
function social_http_post(string $url, array $params): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false) { return [0, ['error' => ['message' => 'cURL : ' . $err]]]; }
    $json = json_decode((string) $raw, true);
    return [$code, is_array($json) ? $json : ['raw' => $raw]];
}

/** Extrait un message d'erreur lisible d'une réponse Graph. */
function social_err(array $body): string
{
    if (isset($body['error']['message'])) {
        $m = (string) $body['error']['message'];
        if (isset($body['error']['error_user_msg'])) { $m .= ' — ' . $body['error']['error_user_msg']; }
        return mb_substr($m, 0, 480);
    }
    return mb_substr(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'erreur inconnue', 0, 480);
}

/** Poste sur la Page Facebook (lien → carte OG auto). @return [ok, postId|error] */
function social_post_facebook(array $a, string $caption): array
{
    $ver = social_graph_ver();
    [$code, $body] = social_http_post(
        "https://graph.facebook.com/{$ver}/" . social_fb_page() . '/feed',
        ['message' => $caption, 'link' => social_article_url($a), 'access_token' => social_fb_token()]
    );
    if ($code === 200 && !empty($body['id'])) { return [true, (string) $body['id']]; }
    return [false, social_err($body)];
}

/** Poste sur Instagram (2 étapes : conteneur média puis publication). @return [ok, postId|error] */
function social_post_instagram(array $a, string $caption): array
{
    $ver = social_graph_ver();
    $img = social_image_url($a);
    if ($img === '') { return [false, 'Article sans image : Instagram exige une image publique (JPG/PNG).']; }
    $token = social_fb_token();
    $ig = social_ig_user();

    // 1) Conteneur média
    [$c1, $b1] = social_http_post(
        "https://graph.facebook.com/{$ver}/{$ig}/media",
        ['image_url' => $img, 'caption' => $caption, 'access_token' => $token]
    );
    if ($c1 !== 200 || empty($b1['id'])) { return [false, 'création média : ' . social_err($b1)]; }

    // 2) Publication du conteneur
    [$c2, $b2] = social_http_post(
        "https://graph.facebook.com/{$ver}/{$ig}/media_publish",
        ['creation_id' => (string) $b1['id'], 'access_token' => $token]
    );
    if ($c2 === 200 && !empty($b2['id'])) { return [true, (string) $b2['id']]; }
    return [false, 'publication : ' . social_err($b2)];
}

/** Met un article en file pour les plateformes voulues (dédoublonné). */
function social_enqueue(int $articleId, ?array $platforms = null): int
{
    social_ensure_table();
    $platforms = $platforms ?: social_platforms();
    if (!$platforms || $articleId <= 0) { return 0; }
    $chk = db()->prepare("SELECT 1 FROM social_queue WHERE article_id=? AND platform=? AND status IN ('pending','posted') LIMIT 1");
    $ins = db()->prepare('INSERT INTO social_queue (article_id, platform, status) VALUES (?, ?, "pending")');
    $n = 0;
    foreach ($platforms as $p) {
        $chk->execute([$articleId, $p]);
        if ($chk->fetchColumn()) { continue; }
        $ins->execute([$articleId, $p]);
        $n++;
    }
    return $n;
}

/**
 * Synchronise : met en file les NOUVEAUX articles publiés (au-delà d'un repère),
 * si l'auto-publication est active. Évite d'inonder avec tout le back-catalogue.
 */
function social_sync(): void
{
    if ((int) get_setting('social_auto', '0') !== 1 || !social_any_ready()) { return; }
    social_ensure_table();
    $since = (int) get_setting('social_since_id', '0');
    if ($since === 0) {
        // Baseline : on ne poste pas l'existant, seulement ce qui vient APRÈS l'activation.
        $max = (int) db()->query('SELECT COALESCE(MAX(id),0) FROM articles')->fetchColumn();
        set_setting('social_since_id', (string) $max);
        return;
    }
    // Articles FR publiés, plus récents que le repère (les VO EN ne sont pas repostées).
    $rows = db()->query(
        "SELECT id FROM articles WHERE status='published' AND lang='fr' AND id > {$since} ORDER BY id ASC LIMIT 30"
    )->fetchAll(PDO::FETCH_COLUMN);
    $newMax = $since;
    foreach ($rows as $id) {
        social_enqueue((int) $id);
        $newMax = max($newMax, (int) $id);
    }
    if ($newMax > $since) { set_setting('social_since_id', (string) $newMax); }
}

/**
 * Vide la file : poste les éléments « pending » sur leur plateforme. Verrou
 * anti-double-exécution. Les échecs isolés sont marqués « error » et sautés.
 * @return array{posted:int,failed:int}
 */
function social_drain(int $budgetSeconds = 0): array
{
    social_ensure_table();
    social_sync(); // enfile d'abord les nouveaux publiés
    if (!social_any_ready()) { return ['posted' => 0, 'failed' => 0]; }

    $lock = (int) get_setting('social_lock', '0');
    if (time() - $lock < 300) { return ['posted' => 0, 'failed' => 0]; }
    set_setting('social_lock', (string) time());

    // ⚠️ PLAFOND QUOTIDIEN par réseau : poste en douceur (anti-spam / anti-bannissement).
    $cap   = max(1, (int) get_setting('social_daily_max', '10'));
    $today = ['facebook' => social_posted_today('facebook'), 'instagram' => social_posted_today('instagram')];

    $deadline = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $posted = 0; $failed = 0;
    try {
        while (time() < $deadline) {
            // Réseaux encore autorisés aujourd'hui : prêts ET sous le plafond quotidien.
            $allowed = [];
            if (social_fb_ready() && $today['facebook'] < $cap) { $allowed[] = 'facebook'; }
            if (social_ig_ready() && $today['instagram'] < $cap) { $allowed[] = 'instagram'; }
            if (!$allowed) { break; } // tout est désactivé ou plafond atteint → on s'arrête

            $in  = "'" . implode("','", $allowed) . "'"; // liste blanche (jamais d'entrée user)
            $row = db()->query(
                "SELECT q.id, q.article_id, q.platform, q.caption, a.title, a.slug, a.excerpt, a.image
                 FROM social_queue q JOIN articles a ON a.id = q.article_id
                 WHERE q.status='pending' AND q.platform IN ($in) ORDER BY q.id ASC LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC);
            if (!$row) { break; }

            $caption = (string) ($row['caption'] ?? '');
            if ($caption === '') {
                $caption = social_caption($row, (string) $row['platform']);
                db()->prepare('UPDATE social_queue SET caption=? WHERE id=?')->execute([$caption, (int) $row['id']]);
            }

            [$ok, $res] = $row['platform'] === 'instagram'
                ? social_post_instagram($row, $caption)
                : social_post_facebook($row, $caption);

            if ($ok) {
                db()->prepare("UPDATE social_queue SET status='posted', post_id=?, posted_at=NOW(), error=NULL WHERE id=?")
                    ->execute([$res, (int) $row['id']]);
                $posted++;
                $today[(string) $row['platform']]++;   // compte pour le plafond quotidien
            } else {
                db()->prepare("UPDATE social_queue SET status='error', error=? WHERE id=?")
                    ->execute([$res, (int) $row['id']]);
                $failed++;
            }
            set_setting('social_lock', (string) time());
            if ($failed >= 5) { break; } // clé/quota probablement en cause
        }
    } finally {
        set_setting('social_lock', '0');
    }
    return ['posted' => $posted, 'failed' => $failed];
}

/** Poste TOUT DE SUITE le dernier article publié (bouton de test admin). */
function social_test(string $platform): array
{
    if ($platform === 'facebook' && !social_fb_ready()) { return ['ok' => false, 'msg' => 'Facebook non configuré (Page + jeton + activé).']; }
    if ($platform === 'instagram' && !social_ig_ready()) { return ['ok' => false, 'msg' => 'Instagram non configuré (compte IG Business + jeton + activé).']; }
    $a = db()->query("SELECT id, title, slug, excerpt, image FROM articles WHERE status='published' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$a) { return ['ok' => false, 'msg' => 'Aucun article publié à poster.']; }
    $caption = social_caption($a, $platform);
    [$ok, $res] = $platform === 'instagram' ? social_post_instagram($a, $caption) : social_post_facebook($a, $caption);
    return $ok
        ? ['ok' => true, 'msg' => '✅ Posté sur ' . ucfirst($platform) . ' (id ' . $res . ').']
        : ['ok' => false, 'msg' => '❌ ' . ucfirst($platform) . ' : ' . $res];
}

/** Nombre de posts déjà publiés AUJOURD'HUI sur une plateforme (plafond quotidien). */
function social_posted_today(string $platform): int
{
    try {
        $st = db()->prepare("SELECT COUNT(*) FROM social_queue WHERE platform=? AND status='posted' AND DATE(posted_at)=CURDATE()");
        $st->execute([$platform]);
        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** Compteurs pour le tableau de bord. */
function social_stats(): array
{
    social_ensure_table();
    try {
        return [
            'pending' => (int) db()->query("SELECT COUNT(*) FROM social_queue WHERE status='pending'")->fetchColumn(),
            'posted'  => (int) db()->query("SELECT COUNT(*) FROM social_queue WHERE status='posted'")->fetchColumn(),
            'error'   => (int) db()->query("SELECT COUNT(*) FROM social_queue WHERE status='error'")->fetchColumn(),
        ];
    } catch (Throwable $e) {
        return ['pending' => 0, 'posted' => 0, 'error' => 0];
    }
}

/** Derniers éléments de la file (pour l'affichage admin). */
function social_recent(int $limit = 12): array
{
    social_ensure_table();
    try {
        $st = db()->prepare(
            "SELECT q.platform, q.status, q.post_id, q.error, q.posted_at, q.created_at, a.title
             FROM social_queue q LEFT JOIN articles a ON a.id = q.article_id
             ORDER BY q.id DESC LIMIT ?"
        );
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
