<?php
/**
 * ViceHub X — Seed « GTA VI » du forum : 50 nouveaux personas (id 53-102),
 * une catégorie dédiée GTA VI, et des sujets vivants sur la précommande.
 * Infos officielles : 2 éditions (Standard 79,99 $ / Ultimate 99,99 $),
 * Vintage Vice City Pack (bonus précommande numérique avant le 20/11/2026),
 * 1 mois de GTA+ offert sur PS/Xbox. Sortie le 19/11/2026.
 * Usage : php scripts/gen-forum-gta6.php > database/seed_forum_gta6.sql
 *
 * IDs : personas vague 2 = index 50..99 -> user_id 53..102.
 *       catégorie GTA VI = id 6. Sujets à partir de l'id 14 (13 déjà seedés).
 */
$P = require __DIR__ . '/../config/personas.php';
function q($s) { return "'" . str_replace("'", "''", (string) $s) . "'"; }
function slugify($s) {
    $s = strtr($s, ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','’'=>' ','\''=>' ','œ'=>'oe']);
    $s = preg_replace('/[^a-z0-9]+/', '-', strtolower($s));
    return trim($s, '-');
}
$uid = fn($i) => 3 + $i;

$out = "\n-- ====== Forum GTA VI : 50 personas de plus + catégorie + sujets ======\n";

// 1) Nouveaux personas (index 50..99 = user_id 53..102)
$rows = [];
for ($i = 50; $i < count($P); $i++) {
    $p = $P[$i];
    $rows[] = '(' . q($p[0]) . ',' . q($p[0] . '@fans.vicehubx.test') . ',' . q($p[1]) . ",'!','member')";
}
$out .= "INSERT INTO users (username, email, display_name, password_hash, role) VALUES\n" . implode(",\n", $rows) . ";\n\n";

// 2) Catégorie GTA VI (id 6)
$out .= "INSERT INTO forum_categories (id, name, slug, description, icon, sort) VALUES\n"
      . "(6, 'GTA VI', 'gta-vi', 'Précommandes, éditions, hype et compte à rebours : tout sur GTA VI.', '🎮', 5);\n\n";

// 3) Sujets : [catId, authorIdx, titre, pinned, [[posterIdx, message], ...]]
$T = [
  [6, 50, '🎉 LES PRÉCOMMANDES DE GTA VI SONT OUVERTES !!', 1, [
    [50, 'ÇA Y EST LES AMIS, les précommandes sont LÀ 😭🌴 j’ai pris l’Ultimate direct, mon cœur n’a pas tenu une seconde !'],
    [72, 'Enfin… j’attends ça depuis 2002. Et le Vintage Vice City Pack en bonus, j’en ai la larme à l’œil 🥲'],
    [56, 'DAY ONE confirmé, congé posé pour le 19 novembre 😎'],
    [6, 'Standard ou Ultimate, la vraie question… 79,99 $ vs 99,99 $, faut que je réfléchisse 🏎️'],
    [95, 'Précommande validée à la première minute, fier de mon numéro de commande 🔥'],
    [7, 'Rappel utile : le Vintage Vice City Pack est réservé aux achats NUMÉRIQUES avant le 20 novembre. Lisez bien 👀'],
  ]],
  [6, 1, 'Standard ou Ultimate, vous prenez quoi ?', 0, [
    [1, 'Du coup il n’y a que deux éditions : Standard à 79,99 $ et Ultimate à 99,99 $. Vous partez sur quoi ?'],
    [54, 'Ultimate sans hésiter. 5 véhicules exclusifs, des boutiques en plus, une mission annexe… 20 $ de plus, ça les vaut.'],
    [62, 'Standard pour moi, je veux d’abord l’histoire. Je verrai pour le reste plus tard.'],
    [82, 'L’Ultimate distribue ses contenus tout au long de l’aventure, c’est ça qui me tente le plus.'],
    [89, 'Je lis tout avant de décider, mais l’Ultimate me fait de l’œil aussi.'],
  ]],
  [6, 50, 'J’ai pris l’Ultimate Edition, posez-moi vos questions 😎', 0, [
    [50, 'Voilà c’est fait, Ultimate validée ! AMA sur ce qu’elle contient.'],
    [85, 'Elle contient quoi exactement de plus que la Standard ?'],
    [50, '5 véhicules exclusifs, 4 variantes d’armes, des packs cosmétiques, 5 boutiques (Rideout Customs, Electric Fang Tattoo…), un garage dédié et une mission annexe en plus 🔥'],
    [40, 'Et niveau online ? j’espère pas que ça déséquilibre tout.'],
    [50, 'Là-dessus on verra à la sortie, mais c’est surtout du contenu solo réparti dans l’histoire.'],
  ]],
  [6, 52, 'Précommande PS5 ou Xbox ? (et le mois de GTA+ offert 👀)', 0, [
    [52, 'Team PlayStation ici. Et le mois de GTA+ offert en précommande numérique, ça compte pas pour rien !'],
    [53, 'Pareil côté Xbox, GTA+ offert aussi sur le Microsoft Store. GTA$ 500 000 d’entrée, je dis pas non.'],
    [86, 'Vous allez pas recommencer la console-war 😅 le GTA+ marche sur les deux stores, soyez zen.'],
    [13, 'Moi c’est surtout les Shark Cards +15 % et la bibliothèque GTA+ qui m’intéressent.'],
    [14, 'Team PC qui attend dans le silence… patience, notre heure viendra 🕯️'],
  ]],
  [6, 51, 'Où précommander au meilleur prix ? (79,99 $ / 99,99 $)', 0, [
    [51, 'Je compare tout depuis ce matin. Standard 79,99 $, Ultimate 99,99 $, à voir selon les revendeurs.'],
    [85, 'Pense aux versions régionales, mais attention aux restrictions d’activation.'],
    [97, 'Si tu prends en boîte, c’est seulement la Standard (code in box). L’Ultimate est numérique only.'],
    [57, 'Perso j’attends un poil, mais le Vintage Vice City Pack expire le 20 novembre, faut pas trop traîner.'],
    [50, 'Voilà, c’est ça le piège : le bonus est limité dans le temps 👀'],
  ]],
  [6, 64, 'Faut-il vraiment précommander ? Le débat 🍿', 0, [
    [64, 'Précommander un jeu pas sorti, c’est risqué non ? « wait and see ».'],
    [57, 'D’accord en général, mais là le Vintage Vice City Pack est réservé à la précommande numérique. Ça change la donne.'],
    [50, 'Exactement. Si tu joues day-one et que le pack te parle, autant en profiter.'],
    [92, 'C’est Rockstar, confiance totale sur la qualité. Je fonce 🤩'],
    [40, 'Confiance oui, mais je reste vigilant sur l’économie online, comme toujours.'],
  ]],
  [6, 11, 'J-XXX : c’est quoi VOTRE plan pour le jour de la sortie ?', 0, [
    [11, 'CONGÉ POSÉ pour le 19 novembre. Frigo plein. Téléphone en avion. JE NE RÉPONDS À PERSONNE 😤🌴'],
    [56, 'Soirée de lancement avec les potes, on lance tous à minuit.'],
    [66, 'Moi je vais juste me balader tranquille la première heure, profiter de la ville 🌅'],
    [18, 'Mon tout premier GTA day one, je sais même pas par quoi commencer haha.'],
    [99, 'Conseil de vétéran : savoure l’intro, te précipite pas. Vice City se mérite.'],
  ]],
  [6, 72, 'Le Vintage Vice City Pack 🌴 (nostalgie ON)', 0, [
    [72, 'Un clin d’œil à Vice City 2002 en bonus de précommande : véhicule + garage + cosmétiques pour Jason et Lucia. Je suis ÉMU.'],
    [80, 'Ça boucle la boucle. De 2002 à 2026, même ville, même magie. Respect Rockstar.'],
    [78, 'Hâte de voir le style des cosmétiques, j’espère du flashy 80s 🦩.'],
    [59, 'Petit rappel : pack inclus dans tout achat numérique AVANT le 20 novembre 2026. Notez la date.'],
    [40, 'Sympa le geste, tant que ça reste cosmétique et pas un avantage online.'],
  ]],
  [6, 91, 'Pas de Collector physique cette fois… déçus ou pas ?', 0, [
    [91, 'Du coup pas de steelbook ni de statuette officielle, juste Standard et Ultimate numériques (et la Standard en boîte). Vous en pensez quoi ?'],
    [54, 'Un peu déçu en tant que collectionneur, j’aurais pris une statuette Jason & Lucia direct.'],
    [62, 'Moi ça m’arrange, je joue full numérique. Moins d’étagères à remplir 😅'],
    [82, 'L’essentiel c’est le jeu. Le contenu de l’Ultimate me suffit largement.'],
    [50, 'On peut toujours se faire plaisir avec des goodies fan (genre la boutique ici 👀🌴).'],
  ]],
  [6, 18, 'Première fois que je précommande un jeu… des conseils ?', 0, [
    [18, 'Je débute, GTA VI sera mon premier GTA ET ma première précommande. Aidez-moi 🙏'],
    [57, 'Garde ta preuve d’achat, vérifie la date de débit et la politique de remboursement.'],
    [50, 'Choisis bien Standard ou Ultimate, et numérique si tu veux le Vintage Vice City Pack. Bienvenue dans la hype 😄'],
    [73, 'BIENVENUE 🌴✨ tu vas A-DO-RER, prépare-toi à ne plus dormir.'],
    [89, 'Pose toutes tes questions ici, la communauté est top pour ça.'],
  ]],
  [6, 1, 'L’Ultimate à 99,99 $ : ça vaut les 20 $ de plus ?', 0, [
    [1, 'Je pèse le pour et le contre. 5 véhicules, 4 variantes d’armes, 5 boutiques, un garage et une mission annexe… verdict ?'],
    [50, 'Pour moi oui : un démarrage plus garni et du contenu réparti dans toute l’histoire.'],
    [57, 'Pour 20 $, ça reste raisonnable si tu joues beaucoup. Sinon la Standard fait le taf.'],
    [82, 'Les boutiques exclusives (Rideout Customs, Electric Fang Tattoo) me tentent pas mal pour le style.'],
    [40, 'Tant que c’est du contenu solo et pas un pay-to-win online, je valide.'],
  ]],
  [6, 56, 'On se fait une soirée de lancement ViceHub X ? 🌴', 0, [
    [56, 'Idée : on se retrouve tous ici le 19 novembre au soir, on partage nos premières impressions en direct !'],
    [73, 'OUI carrément, avec Vice FM en fond et le compte à rebours du site 📻'],
    [28, 'En stream je serai là, on réagira ensemble au lancement.'],
    [95, 'Présent ! Je ramène les memes pour patienter pendant l’installation 😂'],
    [80, 'Belle idée. Les soirées de lancement, c’est ça la magie d’une communauté.'],
  ]],
  [6, 57, 'Team patience vs team day-one : qui a raison ? 😅', 0, [
    [57, 'Moi j’attends les avis et les patchs. Un jeu se bonifie après le lancement.'],
    [56, 'Team day-one sans hésiter ! Vivre le truc en même temps que tout le monde, ça n’a pas de prix.'],
    [64, 'Les deux camps ont raison, question de tempérament.'],
    [40, 'Patience aussi pour voir comment ils gèrent l’économie online avant de dépenser.'],
    [92, 'Day-one les yeux fermés, c’est Rockstar 🤩'],
  ]],
  [6, 62, 'GTA+ offert : vous comptez l’utiliser pour le online ?', 0, [
    [62, 'Un mois de GTA+ offert en précommande numérique : GTA$ 500 000, Shark Cards +15 %, véhicules en rotation… vous allez en profiter ?'],
    [50, 'Carrément, autant prendre le dépôt mensuel et la bibliothèque de jeux tant que c’est offert.'],
    [40, 'Je reste prudent : tant que je sais pas comment l’online de GTA VI sera branché, je m’emballe pas.'],
    [13, 'Les Shark Cards +15 %, ça peut être utile au lancement du online si tu veux gagner du temps.'],
    [96, 'Je comprends à moitié 😅 mais ça a l’air d’un bon bonus pour débuter, non ?'],
  ]],
];

$tid = 14;
$threadRows = [];
$postRows = [];
$h = count($T);
foreach ($T as $k => $t) {
    [$cat, $authorIdx, $title, $pinned, $posts] = $t;
    $slug = slugify($title) . '-' . dechex(1000 + $tid);
    $ageH = ($h - $k) * 8 + 2;
    $threadRows[] = '(' . $tid . ',' . $cat . ',' . $uid($authorIdx) . ',' . q($title) . ',' . q($slug) . ',' . (int) $pinned
        . ", NOW() - INTERVAL $ageH HOUR, NOW() - INTERVAL " . max(0, $ageH - count($posts)) . " HOUR)";
    foreach ($posts as $j => $pp) {
        $minAgo = $ageH * 60 - $j * 29;
        $postRows[] = '(' . $tid . ',' . $uid($pp[0]) . ',' . q($pp[1]) . ', NOW() - INTERVAL ' . max(1, $minAgo) . ' MINUTE)';
    }
    $tid++;
}
$out .= "INSERT INTO forum_threads (id, category_id, user_id, title, slug, pinned, created_at, last_post_at) VALUES\n"
      . implode(",\n", $threadRows) . ";\n\n";
$out .= "INSERT INTO forum_posts (thread_id, user_id, body, created_at) VALUES\n"
      . implode(",\n", $postRows) . ";\n";

echo $out;
