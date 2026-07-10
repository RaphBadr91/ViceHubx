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

    $system = 'Tu es rédacteur SEO SENIOR pour ViceHub X, média de fans INDÉPENDANT et NON OFFICIEL sur GTA VI '
        . 'et Vice City. Tu écris un français impeccable, riche et fluide. Tu N\'INVENTES JAMAIS d\'information '
        . 'officielle non confirmée. Faits connus : sortie 19 novembre 2026 (PS5 / Xbox Series X|S), éditions '
        . 'Standard (79,99$) et Ultimate (99,99$), duo Jason Duval & Lucia Caminos, État de Leonida, Vice City, '
        . 'moteur RAGE, V-Rock. Objectif : un article de RÉFÉRENCE qui mérite la 1re place Google et une AI Overview. '
        . $tones[$toneKey];

    $user = "Rédige un ARTICLE COMPLET et ORIGINAL d'environ 2000 mots sur : « {$topic} ».\n\n"
        . "Règles :\n"
        . "- ~2000 mots, riche, sans remplissage, optimisé SEO (mots-clés naturels : GTA 6, GTA VI, Vice City, Leonida).\n"
        . "- Structure : accroche forte, puis 5 à 7 sections <h2> (avec des <h3> si utile), des listes <ul>/<ol>, "
        . "et une section finale <h2>FAQ</h2> avec 3-4 questions au format <h3>Question ?</h3><p>Réponse</p> (idéal Google AI Overview).\n"
        . "- Balises AUTORISÉES uniquement : <p> <h2> <h3> <ul> <ol> <li> <strong> <em> <blockquote>. AUCUN <a>, AUCUN <h1>, AUCUN markdown.\n\n"
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
    $excerpt = mb_substr(trim((string) ($json['extrait'] ?? '')), 0, 200);
    $body    = strip_tags($bodyHtml, '<p><h2><h3><ul><ol><li><strong><em><blockquote><br>');
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

/** ID de l'auteur admin (pour attribuer les articles générés). */
function ai_admin_author_id(): ?int
{
    return (int) (db()->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0) ?: null;
}

/** Ajoute N articles à la FILE de génération en arrière-plan, avec un statut cible. */
function ai_queue_add(int $n, string $status): void
{
    $status = in_array($status, ['draft', 'pending', 'published'], true) ? $status : 'draft';
    set_setting('ai_gen_status', $status);
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
    $authorId = ai_admin_author_id();
    $tones    = array_keys(ai_tones());
    $deadline = $budgetSeconds > 0 ? time() + $budgetSeconds : PHP_INT_MAX;
    $done = 0;
    while ((int) get_setting('ai_gen_queue', '0') > 0 && time() < $deadline) {
        try {
            $art = ai_generate_article(null, $tones[array_rand($tones)]);
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

/** Lance le worker de génération en ARRIÈRE-PLAN (best-effort, sans bloquer la page). */
function ai_spawn_worker(): bool
{
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (!function_exists('shell_exec') || in_array('shell_exec', $disabled, true)) { return false; }
    $php = null;
    foreach (['/usr/local/bin/php', '/usr/bin/php', '/opt/cpanel/ea-php81/root/usr/bin/php', 'php'] as $c) {
        if ($c === 'php' || @is_file($c)) { $php = $c; break; }
    }
    if ($php === null) { return false; }
    // nohup + & → le worker survit à la fin de la requête web (arrière-plan réel).
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg(ROOT_PATH . '/ai-worker.php') . ' >/dev/null 2>&1';
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

    // 2) DRIP de publication.
    $interval  = max(1, (int) get_setting('ai_auto_interval', '6'));
    $autoOn    = (int) get_setting('ai_auto_enabled', '0') === 1;
    $last      = (int) get_setting('ai_auto_last', '0');
    $now       = time();
    $hasPending = (int) db()->query("SELECT COUNT(*) FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> ''")->fetchColumn();

    if (($autoOn || $hasPending > 0) && ($last === 0 || ($now - $last) >= $interval * 3600)) {
        if ($hasPending > 0) {
            // Publie le plus ancien article programmé (Programmée CRON).
            $pid = (int) db()->query("SELECT id FROM articles WHERE status='pending' AND image_prompt IS NOT NULL AND image_prompt <> '' ORDER BY id ASC LIMIT 1")->fetchColumn();
            db()->prepare("UPDATE articles SET status='published', published_at=COALESCE(published_at, NOW()) WHERE id=?")->execute([$pid]);
            set_setting('ai_auto_last', (string) $now);
            $msgs[] = "1 article programmé publié";
        } elseif ($autoOn) {
            $st = get_setting('ai_auto_status', 'published') === 'draft' ? 'draft' : 'published';
            $tones = array_keys(ai_tones());
            try {
                $id = null; $t = 0;
                while (!$id && $t < 3) { $id = ai_save_article(ai_generate_article(null, $tones[array_rand($tones)]), $st, ai_admin_author_id()); $t++; }
                if ($id) { set_setting('ai_auto_last', (string) $now); $msgs[] = '1 article ' . ($st === 'published' ? 'publié' : 'en brouillon'); }
            } catch (Throwable $e) { $msgs[] = 'erreur API : ' . $e->getMessage(); }
        }
    }

    if (!$msgs) {
        $nextIn = ($last > 0) ? max(0, $interval * 3600 - ($now - $last)) : 0;
        return ['generated' => 0, 'message' => '✓ À jour.' . ($autoOn || $hasPending ? ' Prochain dans ~' . max(1, (int) ceil($nextIn / 60)) . ' min.' : '')];
    }
    return ['generated' => $drained + 1, 'message' => '✅ ' . implode(' · ', $msgs) . '.'];
}
