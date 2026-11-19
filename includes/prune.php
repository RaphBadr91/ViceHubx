<?php
/**
 * ViceHub X — Nettoyage SEO : fusion des articles en double + pages trop minces.
 *
 * Pourquoi : la génération IA a réécrit des dizaines de fois les mêmes sujets
 * (néon de Vice City, coop, stockage, véhicules…). Google voit alors beaucoup de
 * pages quasi identiques et en refuse l'indexation (« Explorée, actuellement non
 * indexée »). Solution : regrouper chaque famille de doublons sur UN article
 * principal (301), et retirer de l'index les pages trop courtes.
 *
 * Similarité : cosinus TF-IDF sur les mots du titre (les mots présents partout,
 * comme « GTA », « 6 » ou « Vice City », pèsent automatiquement très peu).
 */

/** Mots vides FR/EN + mots génériques qui ne décrivent pas le sujet. */
function prune_stopwords(): array
{
    static $sw = null;
    if ($sw !== null) { return $sw; }
    $list = 'a an and are as at be but by can de des du d en et for from has have how in is it its l la le les ma mes no not of on or our ou par pas pour qu que qui s sa se ses son sur t the their this to un une vs was what when where which who why will with you your ce cette ces dans est sont plus tout tous toute toutes nos notre leur leurs avec sans aux au y il elle ils elles on'
        . ' gta gta6 gtavi vi 6 v 5 2025 2026 2027 jeu game games rockstar rockstars guide guides complete complet complete full ultimate'
        . ' revealed revele revelee reveles devoile devoilee confirmed confirme confirmee confirmes decoded decrypte decryptes decrypter explained explique'
        . ' analysis analyse analyses everything know need new nouveau nouvelle nouveaux savoir faut ce vraiment really actually here heres s'
        . ' look first premier premiere top best meilleur meilleure meilleurs inside behind breakdown details detail detailed deep dive';
    $sw = array_fill_keys(preg_split('/\s+/', $list), true);
    return $sw;
}

/** Normalise un titre/slug en jeu de mots-clés (minuscules, sans accents, racine simple). */
/**
 * Vocabulaire commun FR → EN (racines normalisées, sans accents, au singulier) :
 * permet de reconnaître le MÊME SUJET entre un article français et un anglais.
 */
function prune_bilingual(): array
{
    return [
        'meteo' => 'weather', 'climat' => 'weather', 'ouragan' => 'hurricane', 'tempete' => 'storm', 'orage' => 'storm',
        'voiture' => 'car', 'vehicule' => 'vehicle', 'moto' => 'motorcycle', 'motorcycle' => 'motorcycle', 'avion' => 'plane', 'aircraft' => 'plane',
        'helicoptere' => 'helicopter', 'bateau' => 'boat', 'nautique' => 'boat', 'aerien' => 'plane',
        'carte' => 'map', 'sortie' => 'release', 'prix' => 'price', 'tarif' => 'price', 'precommande' => 'preorder', 'pre' => 'pre',
        'bande' => 'soundtrack', 'musique' => 'music', 'musical' => 'music', 'personnage' => 'character', 'protagoniste' => 'protagonist',
        'antagoniste' => 'antagonist', 'mechant' => 'antagonist', 'ennemi' => 'antagonist', 'rival' => 'antagonist', 'villain' => 'antagonist',
        'conduite' => 'driving', 'pilotage' => 'driving', 'physique' => 'physic', 'physics' => 'physic', 'graphisme' => 'graphic', 'graphique' => 'graphic',
        'moteur' => 'engine', 'faune' => 'wildlife', 'animaux' => 'animal', 'creature' => 'animal', 'specie' => 'animal', 'ecosysteme' => 'ecosystem',
        'activite' => 'activity', 'loisir' => 'activity', 'cooperation' => 'coop', 'cooperatif' => 'coop', 'multijoueur' => 'multiplayer',
        'annonce' => 'trailer', 'bandeannonce' => 'trailer', 'fuite' => 'leak', 'rumeur' => 'rumor', 'rumour' => 'rumor',
        'histoire' => 'story', 'scenario' => 'story', 'intrigue' => 'story', 'ville' => 'city', 'plage' => 'beach', 'nuit' => 'night',
        'esthetique' => 'aesthetic', 'stockage' => 'storage', 'espace' => 'storage', 'taille' => 'size', 'telechargement' => 'download',
        'joueur' => 'player', 'communaute' => 'community', 'createur' => 'creator', 'chronologie' => 'timeline', 'calendrier' => 'timeline',
        'economie' => 'economy', 'economique' => 'economy', 'immobilier' => 'estate', 'vente' => 'sale', 'arme' => 'weapon',
        'braquage' => 'heist', 'mission' => 'mission', 'quete' => 'mission', 'secret' => 'secret', 'cache' => 'hidden', 'cachee' => 'hidden',
        'detail' => 'detail', 'personnalisation' => 'customization', 'customisation' => 'customization', 'tenue' => 'outfit', 'vetement' => 'outfit',
        'tatouage' => 'tattoo', 'criminel' => 'crime', 'criminal' => 'crime', 'crime' => 'crime', 'recherche' => 'wanted', 'poursuite' => 'chase',
        'fond' => 'wallpaper', 'ecran' => 'wallpaper', 'collection' => 'collectible', 'figurine' => 'collectible', 'merchandising' => 'merchandise',
        'console' => 'console', 'manette' => 'controller', 'casque' => 'headset', 'televiseur' => 'tv', 'television' => 'tv',
        'sorti' => 'release', 'lancement' => 'launch', 'compte' => 'countdown', 'rebours' => 'countdown', 'record' => 'record', 'vue' => 'view', 'reperee' => 'spotted', 'repere' => 'spotted', 'aperc' => 'spotted', 'deux' => 'two', 'reglage' => 'setting', 'parametre' => 'setting', 'penurie' => 'shortage', 'rupture' => 'shortage',
    ];
}

function prune_tokens(string $text, bool $canonical = false): array
{
    $text = preg_replace('/\bco[\s-]?op\b/iu', 'coop', $text) ?? $text;
    $text = preg_replace('/\bbande[\s-]annonces?\b/iu', 'bandeannonce', $text) ?? $text;
    $t = mb_strtolower($text, 'UTF-8');
    if (function_exists('iconv')) {
        $x = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
        if ($x !== false) { $t = $x; }
    }
    $t = preg_replace('/[^a-z0-9]+/', ' ', $t) ?? '';
    $sw = prune_stopwords();
    $out = [];
    foreach (preg_split('/\s+/', trim($t)) as $w) {
        if ($w === '' || isset($sw[$w]) || ctype_digit($w) && strlen($w) < 3) { continue; }
        if (strlen($w) > 4 && substr($w, -1) === 's') { $w = substr($w, 0, -1); }   // pluriel simple
        if (strlen($w) < 3 || isset($sw[$w])) { continue; }
        if ($canonical) { static $bi = null; $bi = $bi ?? prune_bilingual(); $w = $bi[$w] ?? $w; }
        $out[$w] = true;
    }
    return array_keys($out);
}

/**
 * Regroupe les articles similaires (même langue).
 * @param array $arts [ ['id','slug','title','words','published_at','impr'], ... ]
 * @return array liste de groupes : ['master' => art, 'dups' => [art, ...], 'score' => float]
 */
function prune_clusters(array $arts, float $threshold = 0.55): array
{
    $n = count($arts);
    if ($n < 2) { return []; }
    // Vecteurs + fréquences documentaires.
    $tok = []; $df = [];
    foreach ($arts as $i => $a) {
        $tok[$i] = prune_tokens((string) $a['title']);
        foreach ($tok[$i] as $w) { $df[$w] = ($df[$w] ?? 0) + 1; }
    }
    $idf = [];
    foreach ($df as $w => $c) { $idf[$w] = log(($n + 1) / ($c + 0.5)); }
    $norm = []; $inv = [];
    foreach ($tok as $i => $ws) {
        $s = 0.0;
        foreach ($ws as $w) { $s += $idf[$w] ** 2; $inv[$w][] = $i; }
        $norm[$i] = sqrt($s);
    }
    // Ordre de priorité du « principal » : impressions Google, puis longueur, puis ancienneté.
    $order = array_keys($arts);
    usort($order, static function ($x, $y) use ($arts) {
        return [(int) ($arts[$y]['impr'] ?? 0), (int) $arts[$y]['words'], (string) $arts[$x]['published_at']]
           <=> [(int) ($arts[$x]['impr'] ?? 0), (int) $arts[$x]['words'], (string) $arts[$y]['published_at']];
    });
    $taken = []; $groups = [];
    foreach ($order as $i) {
        if (isset($taken[$i]) || $norm[$i] <= 0) { continue; }
        // Candidats : articles partageant au moins un mot-clé.
        $dot = [];
        foreach ($tok[$i] as $w) {
            foreach ($inv[$w] as $j) {
                if ($j === $i || isset($taken[$j])) { continue; }
                $dot[$j] = ($dot[$j] ?? 0) + $idf[$w] ** 2;
            }
        }
        $dups = []; $best = 0.0;
        foreach ($dot as $j => $d) {
            if ($norm[$j] <= 0) { continue; }
            $sim = $d / ($norm[$i] * $norm[$j]);
            if ($sim >= $threshold) { $dups[] = $j; $best = max($best, $sim); }
        }
        if (!$dups) { continue; }
        $taken[$i] = true;
        foreach ($dups as $j) { $taken[$j] = true; }
        $groups[] = [
            'master' => $arts[$i],
            'dups'   => array_map(static fn($j) => $arts[$j] + ['sim' => round($dot[$j] / ($norm[$i] * $norm[$j]), 2)], $dups),
            'score'  => $best,
        ];
    }
    usort($groups, static fn($a, $b) => count($b['dups']) <=> count($a['dups']));
    return $groups;
}

/* ------------------------------------------------------------------ */
/*  Tables : pages exclues de l'index, journal (annulation), données GSC */
/* ------------------------------------------------------------------ */
function prune_ensure_tables(): void
{
    static $done = false;
    if ($done) { return; }
    $done = true;
    if (function_exists('redirects_ensure_table')) { redirects_ensure_table(); }
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS article_noindex (
            article_id INT PRIMARY KEY,
            reason     VARCHAR(40) NOT NULL DEFAULT 'thin',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        db()->exec("CREATE TABLE IF NOT EXISTS prune_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            batch       VARCHAR(20) NOT NULL,
            article_id  INT NOT NULL,
            slug        VARCHAR(220) NOT NULL,
            action      VARCHAR(10) NOT NULL,
            prev_status VARCHAR(20) DEFAULT NULL,
            to_slug     VARCHAR(220) DEFAULT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_batch (batch)
        ) ENGINE=InnoDB");
        db()->exec("CREATE TABLE IF NOT EXISTS gsc_pages (
            slug        VARCHAR(220) PRIMARY KEY,
            clicks      INT NOT NULL DEFAULT 0,
            impressions INT NOT NULL DEFAULT 0,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {
        // droits DDL limités : les fonctions ci-dessous restent tolérantes
    }
}

/** IDs d'articles exclus de l'index (vide si la table n'existe pas encore). */
function articles_noindex_ids(): array
{
    static $ids = null;
    if ($ids !== null) { return $ids; }
    $ids = [];
    try {
        foreach (db()->query('SELECT article_id FROM article_noindex')->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[(int) $id] = true;
        }
    } catch (Throwable $e) {
        $ids = [];
    }
    return $ids;
}

/** Importe l'export Search Console « Pages.csv » (Performance) : impressions par article. */
function prune_import_gsc(string $csv): int
{
    prune_ensure_tables();
    $rows = array_map('str_getcsv', preg_split('/\R/', trim($csv)));
    $agg = [];
    foreach ($rows as $r) {
        if (count($r) < 3 || !preg_match('#/article/([a-z0-9-]+)#i', (string) $r[0], $m)) { continue; }
        $slug = strtolower($m[1]);
        $agg[$slug]['c'] = ($agg[$slug]['c'] ?? 0) + (int) $r[1];
        $agg[$slug]['i'] = ($agg[$slug]['i'] ?? 0) + (int) $r[2];
    }
    if (!$agg) { return 0; }
    $st = db()->prepare('INSERT INTO gsc_pages (slug, clicks, impressions, updated_at) VALUES (?, ?, ?, NOW())
                         ON DUPLICATE KEY UPDATE clicks = VALUES(clicks), impressions = VALUES(impressions), updated_at = NOW()');
    foreach ($agg as $slug => $v) { $st->execute([$slug, $v['c'], $v['i']]); }
    return count($agg);
}

/**
 * Charge les articles publiés d'une langue avec leur longueur approximative
 * (nombre d'espaces du corps : sans charger les textes en mémoire PHP).
 */
function prune_load_articles(string $lang): array
{
    $gsc = [];
    try {
        foreach (db()->query('SELECT slug, impressions, clicks FROM gsc_pages')->fetchAll(PDO::FETCH_ASSOC) as $g) {
            $gsc[$g['slug']] = (int) $g['impressions'] + 100 * (int) $g['clicks'];
        }
    } catch (Throwable $e) { /* pas encore d'import GSC */ }
    $st = db()->prepare(
        "SELECT id, slug, title, lang, source_id, published_at,
                (CHAR_LENGTH(COALESCE(body,'')) - CHAR_LENGTH(REPLACE(COALESCE(body,''), ' ', ''))) AS words
         FROM articles WHERE status = 'published' AND lang = ?"
    );
    $st->execute([$lang]);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $a['impr']  = $gsc[$a['slug']] ?? 0;
        $a['words'] = (int) $a['words'];
        $out[] = $a;
    }
    return $out;
}

/**
 * Plan de nettoyage (simulation, n'écrit rien).
 * @return array ['langs' => [lang => ['total','groups','merge','thin']], 'groups' => [...], 'thin' => [...]]
 */
function prune_plan(float $threshold = 0.5, int $thinWords = 300): array
{
    $plan = ['langs' => [], 'groups' => [], 'thin' => []];
    $noidx = articles_noindex_ids();
    foreach (['fr', 'en'] as $lang) {
        $arts   = prune_load_articles($lang);
        $groups = prune_clusters($arts, $threshold);
        $merged = [];
        foreach ($groups as $g) {
            foreach ($g['dups'] as $d) { $merged[(int) $d['id']] = true; }
            $plan['groups'][] = $g + ['lang' => $lang];
        }
        // Pages minces restantes (non fusionnées, jamais vues sur Google) → hors index.
        $thin = [];
        foreach ($arts as $a) {
            if ($a['words'] < $thinWords && !isset($merged[(int) $a['id']]) && (int) $a['impr'] === 0 && !isset($noidx[(int) $a['id']])) {
                $thin[] = $a + ['lang' => $lang];
            }
        }
        $plan['thin'] = array_merge($plan['thin'], $thin);
        $plan['langs'][$lang] = [
            'total'  => count($arts),
            'groups' => count($groups),
            'merge'  => count($merged),
            'thin'   => count($thin),
        ];
    }
    return $plan;
}

/** Applique le plan : fusion 301 des doublons + exclusion d'index des pages minces. */
function prune_apply(array $plan): array
{
    prune_ensure_tables();
    $batch = date('YmdHis');
    $pdo = db();
    $log   = $pdo->prepare('INSERT INTO prune_log (batch, article_id, slug, action, prev_status, to_slug) VALUES (?, ?, ?, ?, ?, ?)');
    $redir = $pdo->prepare('INSERT INTO redirects (from_slug, to_slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE to_slug = VALUES(to_slug)');
    $chain = $pdo->prepare('UPDATE redirects SET to_slug = ? WHERE to_slug = ?');           // A→B + B→C ⇒ A→C
    $draft = $pdo->prepare("UPDATE articles SET status = 'draft' WHERE id = ? AND status = 'published'");
    $noidx = $pdo->prepare('INSERT IGNORE INTO article_noindex (article_id, reason) VALUES (?, ?)');
    $merged = 0; $thin = 0;
    $pdo->beginTransaction();
    try {
        foreach ($plan['groups'] as $g) {
            $to = (string) $g['master']['slug'];
            foreach ($g['dups'] as $d) {
                $redir->execute([(string) $d['slug'], $to]);
                $chain->execute([$to, (string) $d['slug']]);
                $draft->execute([(int) $d['id']]);
                $log->execute([$batch, (int) $d['id'], (string) $d['slug'], 'merge', 'published', $to]);
                $merged++;
            }
        }
        foreach ($plan['thin'] as $a) {
            $noidx->execute([(int) $a['id'], 'thin']);
            $log->execute([$batch, (int) $a['id'], (string) $a['slug'], 'noindex', null, null]);
            $thin++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    set_setting('prune_last_batch', $batch);
    return ['batch' => $batch, 'merged' => $merged, 'thin' => $thin];
}

/** Annule un nettoyage (remet en ligne, supprime redirections et exclusions). */
function prune_undo(string $batch): int
{
    prune_ensure_tables();
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM prune_log WHERE batch = ? ORDER BY id DESC');
    $st->execute([$batch]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $pdo->beginTransaction();
    try {
        foreach ($rows as $r) {
            if ($r['action'] === 'merge') {
                $pdo->prepare('DELETE FROM redirects WHERE from_slug = ?')->execute([$r['slug']]);
                $pdo->prepare("UPDATE articles SET status = 'published' WHERE id = ? AND status = 'draft'")->execute([(int) $r['article_id']]);
            } else {
                $pdo->prepare('DELETE FROM article_noindex WHERE article_id = ?')->execute([(int) $r['article_id']]);
            }
        }
        $pdo->prepare('DELETE FROM prune_log WHERE batch = ?')->execute([$batch]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return count($rows);
}

/**
 * Garde-fou du générateur IA : l'article le plus proche déjà publié/programmé.
 * $crossLang = true → compare à l'AUTRE langue (vocabulaire commun FR→EN), pour qu'un
 * article français ne traite pas le même sujet qu'un article anglais, et inversement.
 * @return array [similarité 0-1, slug]
 */
function prune_closest_title(string $title, string $lang, bool $crossLang = false): array
{
    $target = $crossLang ? ($lang === 'en' ? 'fr' : 'en') : $lang;
    try {
        $st = db()->prepare("SELECT slug, title FROM articles WHERE lang = ? AND status IN ('published','pending')");
        $st->execute([$target]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [0.0, ''];
    }
    $n = count($rows);
    if ($n === 0) { return [0.0, '']; }
    $df = []; $toks = [];
    foreach ($rows as $k => $r) {
        $toks[$k] = prune_tokens((string) $r['title'], $crossLang);
        foreach ($toks[$k] as $w) { $df[$w] = ($df[$w] ?? 0) + 1; }
    }
    $idf = static fn(string $w): float => log(($n + 1) / (($df[$w] ?? 0) + 0.5));
    $q = prune_tokens($title, $crossLang);
    $qn = sqrt(array_sum(array_map(static fn($w) => $idf($w) ** 2, $q)));
    if ($qn <= 0) { return [0.0, '']; }
    $qset = array_fill_keys($q, true);
    $best = [0.0, ''];
    foreach ($toks as $k => $ws) {
        $dot = 0.0; $nn = 0.0;
        foreach ($ws as $w) {
            $v = $idf($w) ** 2;
            $nn += $v;
            if (isset($qset[$w])) { $dot += $v; }
        }
        if ($dot <= 0 || $nn <= 0) { continue; }
        $sim = $dot / ($qn * sqrt($nn));
        if ($sim > $best[0]) { $best = [$sim, (string) $rows[$k]['slug']]; }
    }
    return $best;
}

/** Le sujet est-il déjà traité (même langue OU autre langue) ? → [bool, similarité, slug, 'same'|'cross'] */
function prune_topic_taken(string $title, string $lang): array
{
    $max = (float) (get_setting('ai_dup_threshold', '0.5') ?: 0.5);
    [$s1, $u1] = prune_closest_title($title, $lang, false);
    if ($s1 >= $max) { return [true, $s1, $u1, 'same']; }
    [$s2, $u2] = prune_closest_title($title, $lang, true);
    if ($s2 >= $max) { return [true, $s2, $u2, 'cross']; }
    return [false, max($s1, $s2), $s1 >= $s2 ? $u1 : $u2, ''];
}
