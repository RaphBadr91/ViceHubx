<?php
/**
 * ViceHub X — Articles « précommandes GTA VI » (infos officielles vérifiées).
 * 2 éditions seulement : Standard (79,99 $) et Ultimate (99,99 $).
 * Bonus de précommande : Vintage Vice City Pack (+ mois de GTA+ sur PS/Xbox).
 * Produit database/seed_preorder.sql.  Usage : php scripts/gen-preorder.php
 */
$CAT = ['news' => 1, 'guides' => 2, 'leaks' => 3, 'trailers' => 4, 'blog' => 5];
$p = fn($s) => '<p>' . $s . '</p>';
$h = fn($s) => '<h2>' . $s . '</h2>';
$ul = fn($a) => '<ul>' . implode('', array_map(fn($x) => '<li>' . $x . '</li>', $a)) . '</ul>';

$A = [];
$add = function ($cat, $badge, $title, $excerpt, $body) use (&$A) { $A[] = compact('cat', 'badge', 'title', 'excerpt', 'body'); };

$add('news', 'official', 'GTA VI : les précommandes sont ouvertes !',
    'C’est officiel : on peut précommander GTA VI. Deux éditions, un bonus collector pour les nostalgiques.',
    $p('Le moment que des millions de joueurs attendaient est arrivé : les <strong>précommandes de GTA VI</strong> sont ouvertes. Rockstar propose <strong>deux éditions</strong> : la <strong>Standard</strong> (79,99 $) et l’<strong>Ultimate</strong> (99,99 $).')
    . $p('La Standard, c’est le jeu complet : toute l’histoire de Jason et Lucia dans l’État de Leonida. L’Ultimate ajoute un ensemble de contenus exclusifs répartis tout au long de l’aventure.')
    . $p('Et surtout : toute précommande numérique donne droit au <strong>Vintage Vice City Pack</strong>, un clin d’œil à Vice City (2002). On détaille tout ça dans nos guides ci-dessous. Sortie prévue le <strong>19 novembre 2026</strong>.'));

$add('guides', null, 'Standard ou Ultimate : quelle édition de GTA VI choisir ?',
    'Deux éditions, deux budgets (79,99 $ vs 99,99 $). On t’aide à trancher selon ton profil.',
    $p('Contrairement aux rumeurs, il n’y a <strong>pas d’édition Collector physique</strong> : GTA VI se décline en seulement deux éditions. Voici comment les départager.')
    . $h('Standard Edition — 79,99 $')
    . $p('Le jeu complet, point. Toute l’histoire et l’open-world de Leonida avec Jason et Lucia. Disponible en <strong>numérique ou en boîte (code in box)</strong>. Parfait si tu viens d’abord pour la campagne.')
    . $h('Ultimate Edition — 99,99 $ (numérique)')
    . $p('La Standard + un paquet de contenus exclusifs disséminés dans l’aventure :')
    . $ul([
        '<strong>5 véhicules exclusifs</strong> et <strong>4 variantes d’armes</strong>',
        'Plusieurs <strong>packs cosmétiques</strong> pour Jason et Lucia',
        '<strong>5 boutiques</strong> : Rideout Customs, Sara’s Unisex Salon, Stock 305, Electric Fang Tattoo, One-Eyed Willie’s',
        'Un <strong>garage dédié</strong> et une <strong>mission annexe</strong> en plus',
    ])
    . $p('Notre conseil : si tu veux tout débloquer day-one et soutenir au max, l’Ultimate vaut ses 20 $ de plus. Sinon la Standard suffit largement pour vivre l’histoire.'));

$add('news', 'official', 'Le Vintage Vice City Pack : le bonus de précommande expliqué',
    'Un clin d’œil à Vice City 2002 offert à toute précommande numérique avant le 20 novembre 2026.',
    $p('Le gros bonus de précommande, c’est le <strong>Vintage Vice City Pack</strong> : un hommage direct à <em>Grand Theft Auto: Vice City</em> (2002). Il regroupe un <strong>véhicule</strong>, un <strong>garage</strong> et des <strong>cosmétiques</strong> pour Jason et Lucia.')
    . $p('Bonne nouvelle : il est inclus dans <strong>tous les achats numériques</strong> de GTA VI réalisés <strong>avant le 20 novembre 2026</strong>, quelle que soit l’édition. Pas besoin de prendre l’Ultimate pour l’obtenir.')
    . $p('Pour les nostalgiques de la première Vice City, c’est le détail qui fait plaisir. Un pont symbolique entre 2002 et 2026.'));

$add('guides', null, 'Précommande GTA VI : le mois de GTA+ offert sur PS5 et Xbox',
    'Précommander en numérique sur PlayStation Store ou Microsoft Store débloque un mois de GTA+.',
    $p('Si tu précommandes une édition <strong>numérique</strong> de GTA VI sur le <strong>PlayStation Store</strong> ou le <strong>Microsoft Store</strong>, tu obtiens un <strong>mois de GTA+ offert</strong>. Concrètement, ça donne :')
    . $ul([
        'Un dépôt mensuel de <strong>GTA$ 500 000</strong> sur ton compte GTA Online',
        'Des <strong>Shark Cards spéciales</strong> avec +15 % de GTA$ bonus',
        'Des <strong>véhicules gratuits et réduits</strong> en rotation',
        'L’accès à la <strong>bibliothèque de jeux GTA+</strong> (classiques Rockstar et autres)',
    ])
    . $p('Un argument de plus pour la précommande numérique, surtout si tu comptes mettre les pieds dans le online. À voir selon ta plateforme.'));

$add('blog', null, 'Précommander GTA VI : faut-il craquer maintenant ?',
    'L’éternel débat. Avec seulement deux éditions et un bonus limité dans le temps, on fait le point.',
    $p('Précommander, c’est s’assurer le jeu dès la sortie et décrocher le <strong>Vintage Vice City Pack</strong> (réservé aux achats numériques avant le 20 novembre 2026). Mais c’est aussi payer avant d’avoir vu le produit final.')
    . $p('Notre position : si tu es certain de jouer day-one et que le bonus te parle, fonce — il est limité dans le temps. Si tu hésites, rien ne presse vraiment côté édition : pas de collector physique en rupture à craindre.')
    . $p('Garde la tête froide : la hype est immense, mais 80 $ (ou 100 $), ça se décide à tête reposée.'));

$add('news', 'analysis', 'Précommandes GTA VI : la folie mondiale est lancée',
    'Serveurs saturés, files d’attente : la ruée sur GTA VI a commencé dès l’ouverture.',
    $p('À peine ouvertes, les précommandes de GTA VI ont déclenché une ruée mondiale. Sites ralentis, réseaux sociaux en ébullition : l’ampleur est à la hauteur de l’attente.')
    . $p('Avec une Standard à 79,99 $ et une Ultimate à 99,99 $, chacun choisit son niveau d’engagement — mais tout le monde vise le même rendez-vous : le 19 novembre 2026.')
    . $p('Chez ViceHub X, on vit ce moment avec vous. Raconte-nous ta précommande sur le forum — la communauté s’enflamme déjà dans la section GTA VI.'));

$add('guides', null, 'PS5 ou Xbox Series : sur quelle console précommander GTA VI ?',
    'Manette, écosystème, bonus GTA+ : les critères pour choisir sereinement.',
    $p('GTA VI sort sur consoles de nouvelle génération. PS5 ou Xbox Series ? Tout dépend de ton écosystème — et le <strong>mois de GTA+ offert</strong> s’applique aux deux stores en précommande numérique.')
    . $ul([
        'Tu as déjà des amis sur une plateforme ? Reste où est ta communauté.',
        'Tu aimes le retour haptique ? La manette PS5 est un argument pour la conduite.',
        'Tu veux la boîte physique (code in box) ? C’est possible avec la Standard.',
        'Dans tous les cas, vise une version optimisée nouvelle génération.',
    ])
    . $p('Le plus important : le jeu sera magnifique sur les deux. Choisis le confort, pas la guéguerre.'));

$add('blog', null, 'Ce que l’Ultimate Edition change vraiment dans ta partie',
    'Cinq véhicules, des boutiques exclusives, une mission en plus : décryptage de la valeur réelle.',
    $p('L’<strong>Ultimate Edition</strong> n’est pas qu’un pack de skins. Elle distribue ses contenus <strong>tout au long de l’histoire</strong> de Jason et Lucia, ce qui change un peu l’expérience day-one.')
    . $p('Concrètement : <strong>5 véhicules</strong> et <strong>4 variantes d’armes</strong> en plus, des <strong>packs cosmétiques</strong>, <strong>5 boutiques</strong> supplémentaires (dont Rideout Customs et Electric Fang Tattoo), un <strong>garage dédié</strong> et une <strong>mission annexe</strong> que les joueurs Standard ne verront pas au lancement.')
    . $p('Vaut-elle ses 20 $ de plus ? Si tu veux un démarrage plus garni et soutenir le studio, oui. Si tu privilégies l’histoire brute, la Standard reste un excellent choix. À toi de voir.'));

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
$sql = "-- ViceHub X — Articles précommandes GTA VI (infos officielles) — scripts/gen-preorder.php\n"
     . "INSERT INTO articles (category_id, lang, title, slug, excerpt, body, badge, status, published_at) VALUES\n"
     . implode(",\n", $rows) . ";\n";
file_put_contents(dirname(__DIR__) . '/database/seed_preorder.sql', $sql);
echo "OK : " . count($rows) . " articles précommande (infos officielles) écrits.\n";
