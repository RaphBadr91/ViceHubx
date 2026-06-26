<?php
/**
 * ViceHub X — Articles « précommandes GTA VI » (actualité chaude).
 * Produit database/seed_preorder.sql.  Usage : php scripts/gen-preorder.php
 */
$CAT = ['news' => 1, 'guides' => 2, 'leaks' => 3, 'trailers' => 4, 'blog' => 5];
$p = fn($s) => '<p>' . $s . '</p>';
$h = fn($s) => '<h2>' . $s . '</h2>';
$ul = fn($a) => '<ul>' . implode('', array_map(fn($x) => '<li>' . $x . '</li>', $a)) . '</ul>';

$A = [];
$add = function ($cat, $badge, $title, $excerpt, $body) use (&$A) { $A[] = compact('cat', 'badge', 'title', 'excerpt', 'body'); };

$add('news', 'official', 'GTA VI : les précommandes sont ouvertes !',
    'C’est officiel : on peut enfin réserver GTA VI. Voici l’essentiel à savoir avant de craquer.',
    $p('Le moment que des millions de joueurs attendaient est arrivé : les <strong>précommandes de GTA VI</strong> sont ouvertes. Après deux bandes-annonces record, Rockstar permet enfin de réserver le jeu le plus attendu de la décennie.')
    . $p('Avant de foncer, prends une minute : vérifie la plateforme, l’édition, et la politique de remboursement de ton revendeur. Une précommande, ça s’annule, mais autant partir sur de bonnes bases.')
    . $p('Sur ViceHub X, on suivra chaque évolution officielle — prix, éditions, bonus — sans relayer de fausses informations. Reste connecté.'));

$add('guides', null, 'Éditions de GTA VI : Standard, Deluxe ou Collector ?',
    'Trois éditions, trois budgets. On t’aide à choisir celle qui te correspond vraiment.',
    $p('Comme souvent chez Rockstar, GTA VI se décline probablement en plusieurs éditions. Voici comment les départager selon ton profil.')
    . $ul([
        '<strong>Standard</strong> : le jeu, point. Parfait si tu veux l’essentiel au meilleur prix.',
        '<strong>Deluxe</strong> : le bon compromis, avec des bonus in-game sans exploser le budget.',
        '<strong>Collector</strong> : pour les passionnés — goodies physiques, steelbook, objets exclusifs.',
    ])
    . $p('Notre conseil : si tu joues surtout pour l’histoire, la Standard suffit. Si tu es fan absolu de Vice City, le Collector se réserve vite… et part vite.'));

$add('blog', null, 'Précommander GTA VI : faut-il craquer maintenant ?',
    'L’éternel débat refait surface. Précommande maligne ou patience récompensée ?',
    $p('Précommander, c’est s’assurer le jeu (et le Collector) dès la sortie. Mais c’est aussi payer avant d’avoir vu le produit final. Alors, on craque ou on attend ?')
    . $p('Notre position : précommande si tu veux une édition limitée ou si tu es certain de jouer day-one. Sinon, rien ne t’empêche d’attendre les premiers retours. Un bon jeu reste un bon jeu une semaine après.')
    . $p('Dans tous les cas, garde la tête froide : la hype est immense, mais ton portefeuille mérite une décision réfléchie.'));

$add('guides', null, 'Où précommander GTA VI au meilleur prix ?',
    'Boutiques officielles, revendeurs, versions régionales : nos repères pour payer le juste prix.',
    $p('Le prix d’une précommande peut varier selon l’endroit. Quelques réflexes pour éviter de payer trop cher — sans tomber dans les pièges.')
    . $ul([
        'Compare boutique officielle et revendeurs sérieux.',
        'Méfie-toi des offres trop belles : clés régionales, sites douteux, faux bonus.',
        'Vérifie la date de débit et les conditions d’annulation.',
        'Surveille les codes promo des grandes enseignes à l’approche de la sortie.',
    ])
    . $p('Un bon plan, c’est un prix correct chez un vendeur fiable. La sécurité de ta commande prime sur quelques euros.'));

$add('news', 'analysis', 'Précommandes GTA VI : la folie mondiale est lancée',
    'Serveurs saturés, files d’attente, éditions collector qui s’arrachent : le phénomène dépasse tout.',
    $p('À peine ouvertes, les précommandes de GTA VI ont déclenché une ruée mondiale. Sites ralentis, collectors en rupture, réseaux sociaux en ébullition : l’ampleur est à la hauteur de l’attente.')
    . $p('Ce raz-de-marée confirme ce que tout le monde pressentait : GTA VI s’annonce comme le plus gros lancement de l’histoire du jeu vidéo. Et on n’en est qu’à la précommande.')
    . $p('Chez ViceHub X, on vit ce moment avec vous. Racontez-nous votre précommande sur le forum — la communauté s’enflamme déjà.'));

$add('guides', null, 'PS5 ou Xbox Series : sur quelle console précommander GTA VI ?',
    'Manette, écosystème, perspective d’abonnement : les critères pour choisir sereinement.',
    $p('GTA VI sortira d’abord sur consoles de nouvelle génération. PS5 ou Xbox Series : sur laquelle réserver ? Tout dépend de ton écosystème et de tes habitudes.')
    . $ul([
        'Tu as déjà une console et des amis dessus ? Reste où est ta communauté.',
        'Tu aimes le retour haptique ? La manette PS5 est un argument.',
        'Tu mises sur la flexibilité d’un abonnement ? Surveille l’écosystème Xbox.',
        'Dans tous les cas, vise une version optimisée nouvelle génération.',
    ])
    . $p('Le plus important : le jeu sera magnifique sur les deux. Choisis le confort, pas la guéguerre.'));

$add('leaks', 'rumor', 'Édition Collector de GTA VI : ce qu’elle pourrait contenir',
    'Statuette, steelbook, art book, carte de Leonida… tour d’horizon des hypothèses (non officielles).',
    $p('Une édition Collector fait rêver tous les fans. En attendant le contenu officiel, voici ce que la communauté espère y trouver — à prendre comme des hypothèses, pas des certitudes.')
    . $ul([
        'Une statuette du duo Lucia & Jason.',
        'Un steelbook exclusif et un art book.',
        'Une carte de Leonida en tissu, façon « feelies ».',
        'Des objets bonus dans le thème néon de Vice City.',
    ])
    . $p('Tant que Rockstar n’a rien dévoilé, ces idées restent spéculatives. Mais avoue que ça donne envie.'));

$add('blog', null, 'Les bonus de précommande de GTA VI valent-ils le coup ?',
    'Cash in-game, tenues, accès anticipés : on fait le point sur ce qui compte vraiment.',
    $p('Les bonus de précommande font partie du jeu marketing. Mais lesquels valent vraiment le détour, et lesquels sont du bonus de confort ?')
    . $p('Notre avis : les bonus cosmétiques sont sympas mais rarement décisifs. Un peu de cash de départ peut aider, à condition de ne pas déséquilibrer l’économie. L’essentiel reste le jeu de base, pas la carotte.')
    . $p('Bref, précommande pour le jeu et l’édition que tu veux — pas seulement pour un bonus. Le plaisir durera bien plus longtemps qu’une tenue exclusive.'));

/* ---- SQL ---- */
function slugify($s) {
    $s = strtr($s, ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','’'=>' ','\''=>' ','œ'=>'oe']);
    $s = preg_replace('/[^a-z0-9]+/', '-', strtolower($s));
    return trim($s, '-');
}
function q($s) { return "'" . str_replace("'", "''", (string) $s) . "'"; }

global $CAT;
$rows = [];
$seen = [];
$ts = strtotime('2026-06-26 12:00:00');
foreach ($A as $i => $a) {
    $slug = slugify($a['title']);
    while (isset($seen[$slug])) { $slug .= '-' . substr(md5($a['title'] . $i), 0, 4); }
    $seen[$slug] = true;
    $rows[] = sprintf('(%d,%s,%s,%s,%s,%s,%s,%s,%s)',
        $CAT[$a['cat']], q('fr'), q($a['title']), q($slug), q($a['excerpt']), q($a['body']),
        $a['badge'] ? q($a['badge']) : 'NULL', q('published'), q(date('Y-m-d H:i:s', $ts)));
    $ts -= mt_rand(6, 14) * 3600;
}
$sql = "-- ViceHub X — Articles précommandes GTA VI (généré par scripts/gen-preorder.php)\n"
     . "INSERT INTO articles (category_id, lang, title, slug, excerpt, body, badge, status, published_at) VALUES\n"
     . implode(",\n", $rows) . ";\n";
file_put_contents(dirname(__DIR__) . '/database/seed_preorder.sql', $sql);
echo "OK : " . count($rows) . " articles précommande écrits.\n";
