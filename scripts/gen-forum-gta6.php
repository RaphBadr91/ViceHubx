<?php
/**
 * ViceHub X — Seed « GTA VI » du forum : 50 nouveaux personas (id 53-102),
 * une catégorie dédiée GTA VI, et des sujets vivants sur la précommande.
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
    [50, 'ÇA Y EST LES AMIS, les précommandes sont LÀ 😭🌴 j’ai pris le Collector direct, mon cœur n’a pas tenu une seconde !'],
    [72, 'Enfin… j’attends ça depuis 2002. Vice City qui revient, et maintenant je peux réserver. Je suis ému 🥲'],
    [56, 'Pareil DAY ONE confirmé, j’ai posé mon congé pour la sortie 😎'],
    [6, 'Direct vérifié la fiche, vivement de savoir ce qu’il y a dans le Collector côté goodies 🏎️'],
    [95, 'Précommande validée à la première minute, fier de mon numéro de commande 🔥'],
    [7, 'On se calme, lisez bien les conditions de remboursement avant de foncer 👀'],
  ]],
  [6, 1, 'Quelle édition vous prenez ? Standard, Deluxe ou Collector ?', 0, [
    [1, 'Je suis tentée par le Collector mais le prix… vous partez sur quoi vous ?'],
    [54, 'Collector évidemment, je les prends toujours. Steelbook + statuette = bonheur.'],
    [62, 'Standard pour moi, je préfère mettre le budget dans le online plus tard.'],
    [60, 'Deluxe le bon compromis je pense : les bonus sans exploser le budget.'],
    [89, 'Je lis tout avant de décider, mais je penche Deluxe aussi.'],
  ]],
  [6, 50, 'J’ai précommandé le Collector, posez-moi vos questions 😎', 0, [
    [50, 'Voilà c’est fait, AMA ! Je partage tout ce que je sais sur l’édition.'],
    [85, 'Le prix exact tu l’as eu où ? je compare les régions.'],
    [50, 'Sur la boutique officielle, mais vérifie chez les revendeurs aussi, parfois moins cher.'],
    [40, 'Tu crains pas les microtransactions derrière ? moi je reste prudent.'],
    [91, 'Même question, le Collector c’est bien mais j’espère pas un online blindé de boutique.'],
  ]],
  [6, 52, 'Précommande PS5 ou Xbox Series, vous faites quoi ?', 0, [
    [52, 'Team PlayStation ici, hâte de sentir le DualSense sur la conduite 🎮'],
    [53, 'Xbox Series X pour moi, et si ça arrive un jour sur Game Pass je signe direct.'],
    [86, 'Vous allez pas recommencer la console-war 😅 prenez ce que vous avez, le jeu sera ouf partout.'],
    [13, 'Côté perf je croise les doigts pour un 60fps stable, peu importe la machine.'],
    [14, 'Team PC qui attend dans le silence… patience, notre heure viendra 🕯️'],
  ]],
  [6, 51, 'Où précommander au meilleur prix ? (balancez vos bons plans)', 0, [
    [51, 'Je compare tout depuis ce matin. Certains revendeurs cassent déjà les prix.'],
    [85, 'Pense aux versions régionales, mais attention aux restrictions d’activation.'],
    [97, 'Je traque les codes promo, je vous tiens au courant si je trouve mieux.'],
    [57, 'Perso j’attends une petite baisse, rien ne presse, la sortie est pas demain.'],
    [50, 'Le Collector part vite par contre, si vous le voulez, tardez pas trop 👀'],
  ]],
  [6, 64, 'Faut-il vraiment précommander ? Le débat 🍿', 0, [
    [64, 'Honnêtement, précommander un jeu pas sorti, c’est risqué non ? « wait and see ».'],
    [57, 'D’accord à 100%. J’attends les retours, surtout sur l’optimisation.'],
    [50, 'Mais pour le Collector, si tu précommandes pas, tu l’auras jamais 😭'],
    [92, 'C’est Rockstar, ils nous ont jamais déçus sur la qualité. Je fonce, confiance totale.'],
    [40, 'Confiance oui, mais vigilance sur la boutique online. On a déjà vu les dérives.'],
  ]],
  [6, 11, 'J-XXX : c’est quoi VOTRE plan pour le jour de la sortie ?', 0, [
    [11, 'CONGÉ POSÉ. Frigo plein. Téléphone en avion. JE NE RÉPONDS À PERSONNE 😤🌴'],
    [56, 'Soirée de lancement avec les potes, on lance tous en même temps à minuit.'],
    [66, 'Moi je vais juste me balader tranquille la première heure, profiter de la ville 🌅'],
    [18, 'Mon tout premier GTA day one, je sais même pas par quoi commencer haha.'],
    [99, 'Conseil de vétéran : savoure l’intro, te précipite pas. Vice City se mérite.'],
  ]],
  [6, 50, 'Les bonus de précommande, ça vaut le coup selon vous ?', 0, [
    [50, 'Cash in-game, tenues exclusives… vous pensez quoi des bonus annoncés ?'],
    [71, 'Le cash de départ peut aider à lancer l’économie, mais faut pas que ça déséquilibre.'],
    [82, 'Moi tant que c’est cosmétique je valide, l’important c’est le 100% du jeu de base.'],
    [62, 'Tant que c’est pas pay-to-win en online, ça me va.'],
    [59, 'Je résume pour ceux qui suivent : rien d’officiel à 100% encore, prudence sur les listes.'],
  ]],
  [6, 18, 'Première fois que je précommande un jeu… des conseils ?', 0, [
    [18, 'Je débute, GTA VI sera mon premier GTA ET ma première précommande. Aidez-moi 🙏'],
    [57, 'Garde ta preuve d’achat, vérifie la date de débit et la politique de remboursement.'],
    [50, 'Et choisis bien ta plateforme, c’est le plus important. Bienvenue dans la hype 😄'],
    [73, 'BIENVENUE 🌴✨ tu vas A-DO-RER, prépare-toi à ne plus dormir.'],
    [89, 'Pose toutes tes questions ici, la communauté est top pour ça.'],
  ]],
  [6, 1, 'Le Collector contiendrait quoi selon vous ? (vos rêves)', 0, [
    [1, 'Statuette de Lucia & Jason, steelbook, carte de Leonida en tissu… je rêve déjà.'],
    [90, 'Un art book avec les concepts arts, ce serait le pied pour les fans 🎨'],
    [54, 'Une vraie cassette audio collector avec des morceaux des radios, là je meurs.'],
    [78, 'Un petit flamant rose néon à poser sur le bureau et je signe à vie 🦩.'],
    [50, 'Vos idées sont géniales, j’espère que Rockstar lit ce forum 😂'],
  ]],
  [6, 56, 'On se fait une soirée de lancement ViceHub X ? 🌴', 0, [
    [56, 'Idée : on se retrouve tous ici le soir de la sortie, on partage nos premières impressions en direct !'],
    [73, 'OUI carrément, avec Vice FM en fond et le compte à rebours du site 📻'],
    [28, 'En stream je serai là, on pourra réagir ensemble au lancement.'],
    [95, 'Présent ! Je ramène les memes pour patienter pendant l’installation 😂'],
    [80, 'Belle idée. Les soirées de lancement, c’est ça la magie d’une communauté.'],
  ]],
  [6, 57, 'Team patience vs team day-one : qui a raison ? 😅', 0, [
    [57, 'Moi j’attends les patchs et les avis. Un jeu se bonifie après le lancement.'],
    [56, 'Team day-one sans hésiter ! Vivre le truc en même temps que tout le monde, ça n’a pas de prix.'],
    [64, 'Les deux camps ont raison en vrai, question de tempérament.'],
    [40, 'Patience aussi pour voir comment ils gèrent l’économie online avant de dépenser.'],
    [92, 'Day-one les yeux fermés, c’est Rockstar 🤩'],
  ]],
  [6, 75, 'Côté technique, vous attendez quoi de plus ? (RAGE, fps…)', 0, [
    [75, 'Moi c’est le moteur RAGE qui m’obsède : eau, météo, foule réactive. Le bond doit être énorme.'],
    [93, 'Je vais data-miner le moindre détail dès que possible 👀 la tech me passionne.'],
    [13, 'Surtout un framerate stable. 60fps console et je suis comblé.'],
    [30, 'Les détails des PNJ et la physique, c’est là que se joue l’immersion pour moi.'],
    [14, 'Et sur PC, le ray tracing… le jour où ça sort, préparez vos cartes graphiques.'],
  ]],
  [6, 96, 'GTA VI sera mon tout premier GTA, je suis sur un nuage ☁️', 0, [
    [96, 'Jamais touché un GTA de ma vie, et là je précommande le premier. Je flippe un peu et j’ai trop hâte.'],
    [99, 'Tu vas vivre un grand moment. Prends ton temps, explore, parle aux gens dans le jeu.'],
    [66, 'Pas de pression, GTA c’est aussi le plaisir de juste se balader. Profite 🌴'],
    [73, 'BIENVENUE dans la famille 🥹 on est tous passés par un premier GTA un jour.'],
    [50, 'Et viens nous raconter tes premières impressions ici, on adore ça !'],
  ]],
];

$tid = 14;
$threadRows = [];
$postRows = [];
$h = count($T);
foreach ($T as $k => $t) {
    [$cat, $authorIdx, $title, $pinned, $posts] = $t;
    $slug = slugify($title) . '-' . dechex(1000 + $tid);
    $ageH = ($h - $k) * 8 + 2; // étalé dans le temps, récent en haut
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
