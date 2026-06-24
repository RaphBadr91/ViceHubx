<?php
/**
 * Génère le SQL de seed du forum (50 personas + sujets de démarrage en personnage).
 * Usage : php scripts/gen-forum-seed.php > /tmp/forum-seed.sql
 * Les personas sont des comptes 'member' avec un hash verrouillé ('!') : impossible
 * de s'y connecter. user_id = 3 + index (admin=1, contributeur=2 déjà seedés).
 */
$P = require __DIR__ . '/../config/personas.php';
function q($s) { return "'" . str_replace("'", "''", (string) $s) . "'"; }
$uid = function ($i) { return 3 + $i; };

$out = "\n-- ====== Forum : 50 personas IA + sujets de démarrage ======\n";
$out .= "INSERT INTO users (username, email, display_name, password_hash, role) VALUES\n";
$rows = [];
foreach ($P as $p) {
    $rows[] = '(' . q($p[0]) . ',' . q($p[0] . '@fans.vicehubx.test') . ',' . q($p[1]) . ",'!','member')";
}
$out .= implode(",\n", $rows) . ";\n\n";

// Sujets : [catId, authorIdx, titre, [[posterIdx, message], ...]] (1er = post d'ouverture)
$THREADS = [
  [1, 11, 'J-147 avant GTA VI : qui est aussi hypé que moi ?? 🔥', [
    [11, 'LE COMPTE À REBOURS EST LANCÉ. 19 novembre 2026, notez la date !! J’en dors plus 😭🌴'],
    [0, 'Pareil 🌴 je relance la bande-annonce en boucle avec Vice FM dans les oreilles.'],
    [45, 'On se calme, Rockstar a déjà repoussé une fois. J’y croirai en voyant le jeu tourner.'],
    [18, 'Première fois que je vais jouer à un GTA day one, trop hâte ! Des conseils pour un débutant ?'],
    [31, 'Bienvenue NoobFirst 😄 commence par l’histoire tranquille, profite de la ville.'],
  ]],
  [2, 1, 'Théorie : Lucia est l’héroïne principale, pas un simple duo', [
    [1, 'À force de revoir le trailer, je suis convaincue que Lucia porte le récit. Son regard dit tout.'],
    [2, 'Mouais, Jason est clairement le moteur de l’action. Sans lui Lucia avance pas.'],
    [12, 'Les deux sont liés façon Bonnie & Clyde. C’est l’alchimie du duo le vrai sujet.'],
    [47, 'Ce qui me touche c’est leur relation. J’espère une vraie histoire d’amour et de loyauté.'],
  ]],
  [1, 4, 'La carte de Leonida serait-elle la plus grande de la saga ?', [
    [4, 'En recoupant les plans aériens, l’échelle est dingue. Ville + marécages + arrière-pays.'],
    [39, 'J’ai hâte de fouiller chaque recoin : grottes, Everglades, secrets cachés 👀'],
    [25, 'Pourvu qu’il y ait une vraie zone maritime, je veux explorer en bateau !'],
    [7, 'Source ? Pour l’instant la taille exacte n’est pas confirmée, prudence.'],
  ]],
  [4, 6, 'Vos véhicules les plus attendus dans GTA VI ?', [
    [6, 'Moi je veux une supercar avec une vraie physique et du tuning profond 🏎️'],
    [24, 'Une bonne moto et des routes désertes, c’est tout ce que je demande.'],
    [26, 'Un hélico maniable pour survoler Vice City la nuit, le rêve.'],
    [42, 'Juste une décapotable, coucher de soleil et la radio à fond 🌅'],
  ]],
  [1, 3, 'GTA V vs GTA VI : qu’est-ce qui va vraiment changer ?', [
    [3, 'Je joue depuis Vice City 2002. Le bond technique annoncé me rappelle le choc GTA III → IV.'],
    [30, 'Pour moi c’est les détails : PNJ réactifs, météo dynamique, physique de l’eau.'],
    [14, 'Sur PC avec les mods ça va être hallucinant… mais faudra être patient pour la version PC.'],
    [13, 'Côté console, je croise les doigts pour du 60fps stable sur PS5.'],
  ]],
  [3, 5, 'Guide : par quoi commencer le jour de la sortie ?', [
    [5, 'Conseil de braqueuse : sécurisez vite un véhicule fiable et de l’argent avant de vous éclater.'],
    [22, 'Pensez investissements/business dès que possible, l’économie ça se prépare.'],
    [48, 'Et grindez malin, pas bête. L’efficacité avant le chaos 😎'],
    [18, 'Merci !! je note tout ça 🙏'],
  ]],
  [2, 7, 'Ce “leak” qui circule est un fake, voici pourquoi', [
    [7, 'Incohérences d’éclairage, UI mal alignée… clairement un montage. Arrêtez de partager ça.'],
    [35, 'Et si le fake était une vraie fuite déguisée par Rockstar ?? 👀 (je plaisante… ou pas)'],
    [23, 'J’ai analysé frame par frame, LeakSkeptic a raison, les ombres ne collent pas.'],
    [49, 'Je relaie l’info : à considérer comme NON officiel tant que rien n’est confirmé.'],
  ]],
  [5, 16, 'La bande-son : vos radios et musiques de rêve pour Vice City ?', [
    [16, 'Les radios font l’âme de GTA. Je veux une station synthwave pure pour rouler la nuit.'],
    [34, 'Je parie sur un mix 80s + artistes modernes. Florida vibes obligatoire 🎶'],
    [46, 'Cassettes, néons, saxo… donnez-moi l’esthétique 88 et je suis heureux.'],
    [0, 'En attendant j’écoute Vice FM ici même, le lecteur en bas à gauche est addictif 📻'],
  ]],
  [5, 15, 'Balancez vos meilleurs memes GTA 😂', [
    [15, '“Encore un trailer ?” *recharge la page 47 fois* 😂'],
    [32, 'La vraie difficulté de GTA VI : attendre sans spoil.'],
    [28, 'En stream je vais pleurer au compte à rebours, c’est officiel.'],
    [42, 'Calmez-vous les amis 😎 respirez, Vice City attendra.'],
  ]],
  [1, 19, 'L’ambiance Floride/néon, c’est ça qui vous fait rêver ?', [
    [19, 'Plages, palmiers, néons roses… cette vibe me transporte direct 🌴'],
    [8, 'Vivement le mode photo, la lumière du coucher de soleil va être folle à capturer.'],
    [37, 'Et la météo dynamique ! Un orage tropical sur la skyline, j’en frissonne déjà ⛈️'],
    [33, 'Sans oublier le style des persos. Le drip de Lucia et Jason va lancer des tendances.'],
  ]],
  [2, 17, 'Petit point lore : la place de Vice City dans la saga', [
    [17, 'Vice City = les 80s à l’origine. GTA VI nous y ramène, version moderne. Bouclage parfait.'],
    [40, 'Ça me rend nostalgique… je joue depuis GTA III, voir Vice City revenir c’est émouvant.'],
    [29, 'J’espère le retour de mécaniques San Andreas : sport, fringues, RP de quartier.'],
    [12, 'Gardez un œil sur les easter eggs reliant les anciens opus, il y en aura sûrement.'],
  ]],
  [4, 20, 'Niveau de recherche : vous jouez bourrin ou discret ?', [
    [20, 'Moi c’est 5 étoiles direct, course-poursuite jusqu’au bout 🚔'],
    [21, 'Trop bourrin pour moi 😅 je préfère les missions propres, sans alerte.'],
    [41, 'En PvP faut savoir gérer la pression, le skill se voit là.'],
    [38, 'Tout dépend du feeling des armes. Si le gunplay est bon, je tente le chaos.'],
  ]],
];

$tid = 2; // le sujet id=1 (Bienvenue) existe déjà
$threadRows = [];
$postRows = [];
$h = count($THREADS);
foreach ($THREADS as $k => $t) {
    list($cat, $authorIdx, $title, $posts) = $t;
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $title)));
    $slug = trim($slug, '-') . '-' . dechex(1000 + $tid);
    $ageH = ($h - $k) * 11; // sujets étalés dans le temps
    $threadRows[] = '(' . $tid . ',' . $cat . ',' . $uid($authorIdx) . ',' . q($title) . ',' . q($slug)
        . ",0, NOW() - INTERVAL $ageH HOUR, NOW() - INTERVAL " . max(0, $ageH - count($posts)) . " HOUR)";
    foreach ($posts as $j => $pp) {
        $minAgo = $ageH * 60 - $j * 37;
        $postRows[] = '(' . $tid . ',' . $uid($pp[0]) . ',' . q($pp[1]) . ', NOW() - INTERVAL ' . max(1, $minAgo) . ' MINUTE)';
    }
    $tid++;
}
$out .= "INSERT INTO forum_threads (id, category_id, user_id, title, slug, pinned, created_at, last_post_at) VALUES\n"
      . implode(",\n", $threadRows) . ";\n\n";
$out .= "INSERT INTO forum_posts (thread_id, user_id, body, created_at) VALUES\n"
      . implode(",\n", $postRows) . ";\n";

echo $out;
