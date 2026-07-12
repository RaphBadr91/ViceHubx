<?php
/**
 * ViceHub X — Librairie de « voix » du forum (sans API).
 * Fournit : archétypes de membres, briques de pseudos, et un générateur de
 * réponses naturelles en français, contextualisées selon le titre du sujet.
 * Utilisé par gen-forum-users.php (seed) et forum-life.php (battement de cœur).
 */

/** Archétypes de membres : [emojis, bio, jeu favori]. */
function fv_archetypes(): array
{
    return [
        ['🌴🔥',  'Fan de synthwave et de virées nocturnes, toujours hypé.', 'GTA6'],
        ['💖',    'Team Lucia à fond, analyse chaque trailer.', 'GTA6'],
        ['😎',    'Préfère Jason, aime l’action brute, parle cash.', 'GTA6'],
        ['🕹️',   'Joue depuis Vice City (2002), nostalgique des anciens opus.', 'VC'],
        ['🗺️',   'Obsédé par la carte de Leonida et ses quartiers cachés.', 'GTA6'],
        ['💰',    'Reine/roi des braquages sur GTA Online, conseils stratégie.', 'GTA5'],
        ['🏎️',   'Passionné de bagnoles, physique et tuning des véhicules.', 'GTA6'],
        ['🧐',    'Sceptique pro : démonte les faux leaks, demande des sources.', 'GTA6'],
        ['📸',    'Photographe in-game, parle ambiance et lumière.', 'GTA5'],
        ['⏱️',   'Speedrunner, pense optimisation et raccourcis.', 'GTA5'],
        ['🎭',    'Joueur RP (FiveM), adore l’immersion et les histoires de perso.', 'GTA5'],
        ['🚀',    'Hype absolue, compte les jours avant la sortie.', 'GTA6'],
        ['🦉',    'Théoricien posé, relie les indices et easter eggs.', 'GTA6'],
        ['🎮',    'Joueur console, demande specs, 60fps, PS5 vs Xbox.', 'GTA6'],
        ['🖥️',   'Team PC, mods et graphismes ultra, patient pour la version PC.', 'GTA5'],
        ['😂',    'Balance des memes et des blagues, jamais sérieux trop longtemps.', 'GTA6'],
        ['🌊',    'Aime les plages, jet-skis et la vie tropicale de Leonida.', 'GTA6'],
        ['🧭',    'Explorateur, veut fouiller chaque recoin de la map.', 'GTA6'],
        ['🎧',    'Fan des radios GTA, hype sur la BO et les licences.', 'GTA6'],
        ['💸',    'Pragmatique : compare prix, éditions et bons plans.', 'GTA6'],
    ];
}

/** Briques de pseudos (gamer + univers Vice City / GTA). */
function fv_roots(): array
{
    return ['vice','neon','leonida','jason','lucia','miami','palm','ocean','drift','heist',
        'synth','retro','cartel','gator','sunset','chrome','turbo','vinewood','grove','sabre',
        'flamingo','boulevard','riptide','sunshine','everglade','keys','marina','nitro','outlaw',
        'cruiser','solaris','wavedash','badlands','downtown','speedo','tropic','dusk','asphalt',
        'highway','neonwave','vicecity','leo','pixel','combat','shadow','crimson','velvet','dune'];
}

/** Suffixes / particules de pseudos. */
function fv_suffixes(): array
{
    return ['','_x','_yt','_ttv','_fr','_vc','_gg','_06','_84','_99','_2026','x','z','_off',
        '_real','_hd','_pro','_og','_77','_312','_one','_main','_max','_king','_queen','_jr'];
}

/** Prénoms courts pour display_name (mix FR/EN crédible). */
function fv_first_names(): array
{
    return ['Léo','Maxime','Sarah','Tom','Lucas','Emma','Hugo','Chloé','Nathan','Inès',
        'Théo','Manon','Enzo','Jade','Noah','Lola','Ethan','Camille','Gabriel','Zoé',
        'Ryan','Mia','Kevin','Anaïs','Dylan','Eva','Jordan','Lina','Mike','Sofia',
        'Alex','Nina','Sam','Yanis','Rayan','Maël','Clara','Adam','Louna','Eden'];
}

/** Briques de réponses : ouvertures, fermetures, et cœur par thème. */
function fv_openers(): array
{
    return ['', '', '', 'Franchement, ', 'Perso, ', 'Honnêtement, ', 'Clairement, ', 'Bon, ',
        'Ah ça, ', '+1, ', 'Pas faux, ', 'Je dis ça je dis rien mais ', 'Mdr ', 'Carrément, ',
        'Sérieux, ', 'Avis perso : ', 'Pour moi, ', 'Sans déconner, ', 'Tellement, ', 'Bref, '];
}

function fv_closers(): array
{
    return ['', '', '', ' Hâte de voir ça.', ' Vivement novembre.', ' On en reparle à la sortie.',
        ' Qui est chaud ?', ' Je précommande pas tout de suite par contre.', ' Wait and see.',
        ' Ça va être énorme.', ' J’y crois à fond.', ' Faut rester prudent quand même.',
        ' Quelqu’un a une source ?', ' On verra bien.', ' Trop hâte 🌴', ' Compte à rebours lancé.'];
}

/** Cœur de réponse par thème détecté. */
function fv_cores(): array
{
    return [
        'edition' => [
            'l’Ultimate vaut le coup juste pour les véhicules exclusifs et le garage',
            'la Standard suffit largement pour 90% des joueurs',
            '20$ de plus pour l’Ultimate, je trouve ça correct vu le contenu',
            'le Vintage Vice City Pack en précommande, c’est cadeau',
            'le mois de GTA+ inclus dans l’Ultimate, c’est un détail mais c’est sympa',
            'perso je prends la Standard en boîte pour la collec',
            'je comprends pas ceux qui hésitent, l’Ultimate c’est clairement le meilleur deal',
            'Standard ou Ultimate, l’essentiel c’est qu’il n’y a que 2 éditions, simple',
            'à 79,99$ la Standard reste dans les prix du marché next-gen',
            'les boutiques exclusives de l’Ultimate vont être un game changer pour le RP',
        ],
        'map' => [
            'la carte de Leonida a l’air énorme, j’ai hâte de l’explorer à fond',
            'Vice City revisitée en next-gen, c’est le rêve depuis 20 ans',
            'les Everglades et les marais vont ajouter une vraie variété',
            'j’espère qu’on pourra entrer dans plein de bâtiments cette fois',
            'la densité de la ville dans le trailer 2 était hallucinante',
            'team exploration ici, je vais passer des heures juste à rouler',
            'la map a l’air bien plus vivante que celle de GTA V',
            'les petites villes rétro autour de Vice City m’intriguent à mort',
            'si la map est aussi grande que les rumeurs, on en a pour des années',
            'hâte de voir la zone côtière en jet-ski au coucher du soleil',
        ],
        'perso' => [
            'Lucia en première héroïne jouable, c’était attendu et c’est mérité',
            'le duo Jason & Lucia façon Bonnie & Clyde, ça promet niveau histoire',
            'l’alchimie entre les deux persos dans le trailer était folle',
            'team Lucia, son charisme crève l’écran',
            'Jason a l’air plus posé, j’aime bien ce contraste avec Lucia',
            'jouer un duo, ça va changer la narration de la saga',
            'j’espère qu’on pourra switcher entre Jason et Lucia comme dans GTA V',
            'le doublage a l’air ultra soigné, gros niveau',
            'leur relation va clairement être le cœur émotionnel du jeu',
            'enfin une protagoniste féminine principale, il était temps',
        ],
        'pc' => [
            'la version PC va encore se faire attendre, comme d’hab avec Rockstar',
            'console d’abord, PC genre un an après, on connaît la chanson',
            'je vais prendre sur PS5 au lancement plutôt que d’attendre le PC',
            'patience team PC, ça vaudra le coup avec les mods',
            'aucune version PC confirmée pour l’instant, faut pas rêver trop vite',
            'RDR2 et GTA V ont eu leur version PC bien plus tard, ça sera pareil',
            'j’attends le PC pour les graphismes ultra et le 4K 60fps',
            'le pire c’est l’attente PC, mais les mods derrière ça compense',
        ],
        'precommande' => [
            'précommande lancée pour le Vintage Vice City Pack, j’allais pas rater ça',
            'je précommande jamais d’habitude mais là c’est GTA 6 quoi',
            'le bonus de précommande est sympa mais je vais attendre les tests',
            'attention aux dates limites du bonus selon les boutiques',
            'précommande digitale pour jouer à minuit pile le 19 novembre',
            'j’hésite encore entre boîte et digital pour la précommande',
            'le pack précommande pour Jason et Lucia, ça donne envie',
        ],
        'trailer' => [
            'le trailer 2 a mis une claque, le niveau de détail est dingue',
            'j’ai re-regardé le trailer image par image, y’a plein d’indices',
            'la direction artistique néon est exactement ce que j’espérais',
            'la bande-son du trailer collait parfaitement à l’ambiance',
            'chaque seconde du trailer fourmille de détails, c’est fou',
            'Rockstar maîtrise l’art du trailer comme personne',
            'les PNJ et la foule dans le trailer avaient l’air ultra réactifs',
        ],
        'vehicule' => [
            'la physique des voitures a l’air bien plus réaliste que sur GTA V',
            'j’espère un gros catalogue de tuning dès la sortie',
            'les supercars et les muscle cars rétro, mon combo préféré',
            'hâte de voir le comportement des motos et des jet-skis',
            'le tuning façon Los Santos Customs mais en mieux, je signe',
            'les reflets sur la carrosserie dans le trailer, sérieux quoi',
            'team muscle car américaine, le diner + V8 c’est tout un mood',
        ],
        'hype' => [
            'plus que quelques mois, je tiens plus en place',
            'GTA 6 c’est LE jeu de la décennie, point',
            'je pose des congés pour la sortie, assumé',
            'l’attente est insoutenable mais ça va valoir chaque seconde',
            'on va vivre un moment historique du jeu vidéo le 19 novembre',
            'jamais été aussi hypé pour un jeu de ma vie',
            'le compte à rebours tourne, mon corps est prêt',
            'rien que d’en parler ça me remet la hype 🌴',
        ],
        'general' => [
            'gros sujet, content de voir le forum aussi actif',
            'd’accord avec ce qui se dit ici, bonne analyse',
            'intéressant ce point de vue, j’y avais pas pensé',
            'je suis ce topic de près, continuez',
            'sujet passionnant, Vice City va tous nous rendre accros',
            'merci pour le partage, ça alimente bien le débat',
            'on est tous d’accord sur un point : ça va être énorme',
            'belle communauté ici, ça fait plaisir de discuter GTA',
        ],
    ];
}

/** Détecte le thème d’un sujet à partir de son titre. */
function fv_topic_of(string $title): string
{
    $t = mb_strtolower($title);
    $has = static function (array $kw) use ($t) {
        foreach ($kw as $k) { if (mb_strpos($t, $k) !== false) return true; }
        return false;
    };
    if ($has(['édition','edition','prix','ultimate','standard','précommande','precommande','acheter'])) {
        return mb_strpos($t, 'précomm') !== false || mb_strpos($t, 'precomm') !== false ? 'precommande' : 'edition';
    }
    if ($has(['carte','map','leonida','vice city','quartier','ville','zone'])) return 'map';
    if ($has(['lucia','jason','perso','personnage','héro','duo','histoire'])) return 'perso';
    if ($has(['pc','config','steam','mods','fps'])) return 'pc';
    if ($has(['trailer','bande-annonce','bande annonce','teaser'])) return 'trailer';
    if ($has(['voiture','véhicule','vehicule','tuning','moto','bagnole','custom'])) return 'vehicule';
    if ($has(['hype','sortie','compte à rebours','date','jours','novembre'])) return 'hype';
    return 'general';
}

/** Construit une réponse naturelle pour un sujet donné. */
function fv_reply(string $title, string $emojis = '', string $replyTo = ''): string
{
    static $cores = null, $openers = null, $closers = null;
    $cores   = $cores   ?? fv_cores();
    $openers = $openers ?? fv_openers();
    $closers = $closers ?? fv_closers();

    $topic = fv_topic_of($title);
    // 70% du thème détecté, 30% d’un pool transverse (hype/general) pour varier.
    $pool = $cores[$topic];
    if (mt_rand(1, 100) > 70) {
        $alt = mt_rand(0, 1) ? 'hype' : 'general';
        $pool = $cores[$alt];
    }
    $core = $pool[array_rand($pool)];

    // Interaction : souvent, on répond DIRECTEMENT au membre précédent (par son nom)
    // → on dirait une vraie conversation entre membres.
    $rt = trim($replyTo);
    if ($rt !== '' && mt_rand(1, 100) <= 60) {
        $lead = [
            '@' . $rt . ' ', 'Bien vu ' . $rt . ', ', 'Pas faux ' . $rt . ', mais ',
            'Carrément d’accord avec ' . $rt . ' — ', 'Franchement ' . $rt . ', ',
            '+1 ' . $rt . ', ', 'Comme le dit ' . $rt . ', ', 'Perso je rejoins ' . $rt . ' : ',
            'Mouais ' . $rt . ', j’suis pas convaincu… ', 'Exactement ' . $rt . ' ! ',
        ];
        $msg = $lead[array_rand($lead)] . $core;
    } else {
        // Phrase principale (ouverture + cœur), terminée proprement.
        $msg = $openers[array_rand($openers)] . $core;
    }
    $msg = mb_strtoupper(mb_substr($msg, 0, 1)) . mb_substr($msg, 1);
    if (!preg_match('/[.!?…]$/u', $msg)) { $msg .= '.'; }
    // Chute éventuelle, ajoutée comme phrase distincte (commence déjà par un espace).
    $closer = $closers[array_rand($closers)];
    if ($closer !== '') { $msg .= $closer; }
    // Emoji d’archétype occasionnel (découpage par grappe pour ne pas casser
    // les emojis composés type 🗺️ / ⏱️).
    if ($emojis !== '' && mt_rand(1, 100) <= 35 && preg_match_all('/\X/u', $emojis, $mm)) {
        $msg .= ' ' . $mm[0][array_rand($mm[0])];
    }
    return $msg;
}

/** Sujets d’ouverture prêts à l’emploi : [titre, message]. */
function fv_starters(): array
{
    return [
        ['Standard ou Ultimate : vous prenez quelle édition de GTA 6 ?',
         'Maintenant que les précommandes sont ouvertes, le grand débat : Standard à 79,99$ ou Ultimate à 99,99$ ? Perso je penche pour l’Ultimate rien que pour le garage et les véhicules exclusifs. Et vous, vous partez sur quoi et pourquoi ?'],
        ['Vos théories sur la taille réelle de la carte de Leonida',
         'On a vu Vice City, les plages, les marais… mais jusqu’où va vraiment s’étendre Leonida ? J’ai l’impression que la map sera bien plus grande et dense que celle de GTA V. Vos estimations et vos zones les plus attendues ?'],
        ['Jason & Lucia : le meilleur duo de l’histoire de GTA ?',
         'Premier duo jouable et première héroïne principale de la saga. Leur dynamique façon Bonnie & Clyde a l’air dingue dans le trailer 2. Vous pensez qu’on pourra switcher entre les deux librement ? Qui sera votre main ?'],
        ['Compte à rebours GTA 6 : comment vous tenez jusqu’au 19 novembre ?',
         'On y est presque mais l’attente est longue 😅 Vous faites quoi en attendant ? Re-run de GTA V, RDR2, ou vous épluchez chaque leak ? Postez votre programme de survie d’ici la sortie 🌴'],
        ['Version PC de GTA 6 : on l’aura quand à votre avis ?',
         'Aucune version PC confirmée au lancement, et on connaît les habitudes de Rockstar… Vous pariez sur combien de mois d’écart après la sortie console ? Et vous attendez le PC ou vous craquez sur PS5/Xbox direct ?'],
        ['Les véhicules de GTA 6 qui vous font le plus rêver',
         'Supercars, muscle cars rétro, jet-skis, motos… le trailer a montré du beau monde. Quel type de véhicule vous voulez absolument conduire en premier dans Leonida ? Moi c’est décapotable + Ocean Drive au coucher de soleil, sans hésiter.'],
    ];
}

/**
 * BATTEMENT DE CŒUR du forum, avec un RYTHME HUMAIN (une interaction toutes les
 * 2 à 12 h, aléatoire) et des membres qui se répondent par leur nom → très réel.
 * Appelé par forum-tick.php (web) et scripts/forum-life.php (CLI). Un « verrou »
 * global (réglage forum_next_at) garantit qu'on ne poste jamais en rafale.
 *
 * Réglages : forum_gap_min_h (2) · forum_gap_max_h (12) · forum_new_chance (6).
 * @return string message de statut
 */
function fv_heartbeat(array $opts = []): string
{
    $pdo = db();
    try { $pdo->query('SELECT 1 FROM forum_bot_agents LIMIT 1'); }
    catch (Throwable $e) { return "Table forum_bot_agents absente. Lance gen-forum-users.php."; }

    $force  = !empty($opts['force']);
    $minH   = max(1, (int) get_setting('forum_gap_min_h', '2'));
    $maxH   = max($minH, (int) get_setting('forum_gap_max_h', '12'));
    $now    = time();
    $nextAt = (int) get_setting('forum_next_at', '0');

    // Verrou de rythme : tant que l'heure n'est pas venue, on ne poste rien.
    if (!$force && $now < $nextAt) {
        return "⏳ Pas encore l'heure. Prochaine interaction dans ~" . max(1, (int) ceil(($nextAt - $now) / 60)) . " min.";
    }

    // 1 membre IA : « dû » selon sa cadence en priorité, sinon un actif au hasard.
    $agent = $pdo->query('SELECT user_id, cadence_days, emojis FROM forum_bot_agents WHERE active=1 AND next_post_at <= NOW() ORDER BY next_post_at ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC)
        ?: $pdo->query('SELECT user_id, cadence_days, emojis FROM forum_bot_agents WHERE active=1 ORDER BY RAND() LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$agent) { return "Aucun membre IA actif."; }
    $uid = (int) $agent['user_id'];

    // Sujets « refroidis » : dernier message il y a AU MOINS minH h → on espace les
    // réponses dans un même fil (2-12 h entre deux prises de parole).
    $threads = $pdo->query(
        "SELECT t.id, t.title,
            (SELECT p.user_id FROM forum_posts p WHERE p.thread_id=t.id ORDER BY p.id DESC LIMIT 1) AS last_uid,
            (SELECT COALESCE(u.display_name,u.username) FROM forum_posts p LEFT JOIN users u ON u.id=p.user_id WHERE p.thread_id=t.id ORDER BY p.id DESC LIMIT 1) AS last_name
         FROM forum_threads t
         WHERE t.locked=0 AND (t.last_post_at IS NULL OR t.last_post_at <= (NOW() - INTERVAL {$minH} HOUR))
         ORDER BY t.last_post_at DESC LIMIT 30"
    )->fetchAll(PDO::FETCH_ASSOC);

    $posted = 0; $newThread = 0;
    if ($threads) {
        shuffle($threads);
        $t = null;
        foreach ($threads as $c) { if ((int) $c['last_uid'] !== $uid) { $t = $c; break; } } // pas répondre juste après soi
        if (!$t) { $t = $threads[0]; }
        $replyTo = ((int) $t['last_uid'] !== $uid) ? (string) $t['last_name'] : '';
        $body = fv_reply((string) $t['title'], (string) ($agent['emojis'] ?? ''), $replyTo);
        try {
            $pdo->prepare('INSERT INTO forum_posts (thread_id, user_id, body, created_at) VALUES (?,?,?,NOW())')->execute([(int) $t['id'], $uid, $body]);
            $pdo->prepare('UPDATE forum_threads SET last_post_at = NOW() WHERE id=?')->execute([(int) $t['id']]);
            $cad = max(1.0, (float) ($agent['cadence_days'] ?? 5));
            $pdo->prepare('UPDATE forum_bot_agents SET last_post_at=NOW(), next_post_at=? WHERE user_id=?')
                ->execute([date('Y-m-d H:i:s', $now + (int) ($cad * 86400 * (mt_rand(75, 125) / 100))), $uid]);
            $posted = 1;
        } catch (Throwable $e) { /* on ignore et on reprogramme quand même */ }
    }

    // De temps en temps, un membre lance un NOUVEAU sujet (contenu SEO frais).
    if (mt_rand(1, 100) <= max(0, (int) get_setting('forum_new_chance', '6'))) {
        $starters = fv_starters();
        shuffle($starters);
        $exists = $pdo->prepare('SELECT 1 FROM forum_threads WHERE title=? LIMIT 1');
        foreach ($starters as $st) {
            $exists->execute([$st[0]]);
            if ($exists->fetchColumn()) { continue; }
            try { create_thread(6, $uid, $st[0], $st[1]); $newThread = 1; } catch (Throwable $e) { /* ignore */ }
            break;
        }
    }

    // Prochaine interaction dans 2 à 12 h (aléatoire) → cadence humaine, jamais en rafale.
    $gap = mt_rand($minH * 60, $maxH * 60) * 60;
    set_setting('forum_next_at', (string) ($now + $gap));

    return "OK : {$posted} réponse(s)" . ($newThread ? " + 1 sujet" : '') . ". Prochaine interaction dans ~" . round($gap / 3600, 1) . " h.";
}
