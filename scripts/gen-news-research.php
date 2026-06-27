<?php
/**
 * ViceHub X — 60 articles d'actu « vraie recherche » (FR), professionnels.
 * Faits vérifiés à partir des communications officielles Rockstar et de la presse
 * spécialisée (juin 2026). Le maillage interne est posé automatiquement au rendu
 * par internal_autolink() (voir includes/functions.php) : les expressions-clés
 * (Vice City, Jason et Lucia, éditions, fonds d'écran…) deviennent des liens.
 *
 * Usage : php scripts/gen-news-research.php
 * Idempotent : saute les articles dont le slug existe déjà.
 */
require_once __DIR__ . '/../config/config.php';

$CAT = ['news' => 1, 'guides' => 2, 'leaks' => 3, 'trailers' => 4, 'blog' => 5];
$p  = fn(string $s) => '<p>' . $s . '</p>';
$h  = fn(string $s) => '<h2>' . $s . '</h2>';
$ul = fn(array $a) => '<ul>' . implode('', array_map(fn($x) => '<li>' . $x . '</li>', $a)) . '</ul>';

$A = [];
$add = function (string $cat, ?string $badge, string $title, string $excerpt, string $body, int $days) use (&$A) {
    $A[] = compact('cat', 'badge', 'title', 'excerpt', 'body', 'days');
};

/* ===================== NEWS ===================== */
$add('news', 'official', 'GTA 6 : la date du 19 novembre 2026 officialisée par Rockstar',
    'Après deux reports, Rockstar verrouille le lancement au 19 novembre 2026 sur PS5 et Xbox Series.',
    $p('C’est désormais gravé dans le marbre : <strong>GTA 6</strong> sortira le <strong>jeudi 19 novembre 2026</strong>, exclusivement sur PlayStation 5 et Xbox Series X|S. Rockstar Games a confirmé cette date de sortie après un parcours mouvementé : révélé en décembre 2023 pour 2025, le jeu avait d’abord été repoussé au 26 mai 2026, avant ce calage final à l’automne.')
    . $p('Pour les joueurs, le message est clair : plus de fenêtre vague, une date ferme. Chez ViceHub X, on suit chaque communication officielle au mot près. Retrouve tous les détails confirmés dans notre dossier complet sur GTA 6.'), 1);

$add('news', 'official', 'Précommandes GTA 6 : le prix de 79,99 $ confirmé',
    'Ouvertes le 25 juin 2026, les précommandes affichent une édition Standard à 79,99 $.',
    $p('Les précommandes de <strong>GTA 6</strong> ont ouvert le 25 juin 2026. L’<strong>édition Standard</strong> est affichée à <strong>79,99 $</strong> (environ 79,99 €), disponible en numérique ou en boîte avec code de téléchargement selon les régions. L’édition Ultimate, elle, grimpe à 99,99 $.')
    . $p('Quelle que soit l’édition choisie, toute précommande débloque le Vintage Vice City Pack au lancement. On détaille les différences entre les deux éditions dans notre guide d’achat dédié.'), 1);

$add('news', 'confirmed', 'Lucia Caminos, première héroïne jouable de l’histoire de GTA',
    'Pour la première fois dans un GTA principal, une femme tient le rôle de protagoniste.',
    $p('Son nom complet est <strong>Lucia Caminos</strong>, et elle entre dans l’histoire : c’est la <strong>première protagoniste féminine</strong> de la série principale Grand Theft Auto. Selon les éléments dévoilés, elle aurait appris à se battre dès son plus jeune âge, éduquée par son père.')
    . $p('Lucia forme avec Jason Duval un duo jouable inédit, à la fois partenaires de crime et de cœur. Une bascule narrative majeure que l’on décortique dans notre fiche personnages.'), 2);

$add('news', 'confirmed', 'Jason Duval : l’autre moitié du duo de GTA 6',
    'Ancien militaire passé par les Keys, Jason a grandi entouré d’arnaqueurs et de petites combines.',
    $p('<strong>Jason Duval</strong> a grandi au milieu des grifters et des combines. Après un passage par l’armée censé l’éloigner d’une adolescence agitée, il atterrit dans les Keys de Leonida, où il fait ce qu’il sait faire de mieux : travailler pour des trafiquants locaux.')
    . $p('Sa rencontre avec Lucia Caminos fait basculer son destin. Ensemble, ils incarnent un Bonnie &amp; Clyde moderne, fil rouge émotionnel de l’aventure.'), 2);

$add('news', 'confirmed', 'Leonida : la Floride fictive qui accueille Vice City',
    'GTA 6 s’étend à tout un État inspiré de la Floride, dont Vice City est le cœur battant.',
    $p('Vice City n’est plus seule : elle s’inscrit dans l’<strong>État de Leonida</strong>, la version fictive de la Floride imaginée par Rockstar. Plages dorées, marais, Everglades, petites villes côtières et néons urbains cohabitent dans un même terrain de jeu.')
    . $p('Cette échelle élargie promet une variété de paysages inédite pour la saga. On cartographie les régions connues dans notre Map Lab.'), 3);

$add('news', 'analysis', 'Le deuxième trailer de GTA 6 a battu des records',
    'Diffusé fin 2025, le trailer 2 s’est accompagné de plus de 70 visuels officiels.',
    $p('Le second trailer de <strong>GTA 6</strong> a enflammé Internet et accompagné la diffusion de plus de 70 captures d’écran officielles. On y découvre des braquages de commerces, des courses-poursuites, des combats clandestins et des poursuites en bateau à grande vitesse.')
    . $p('Surtout, il a confirmé l’ampleur et la direction artistique : une Vice City néon, dense et vivante. Notre Trailer Lab en propose un décryptage image par image.'), 4);

$add('news', 'official', 'GTA 6 sortira un jeudi : pourquoi le 19 novembre est stratégique',
    'Un lancement en pleine fenêtre des fêtes, idéal pour viser des ventes records.',
    $p('En visant le <strong>19 novembre 2026</strong>, un jeudi, Rockstar place <strong>GTA 6</strong> à l’entrée de la période commerciale la plus chargée de l’année. Une fenêtre taillée pour les cadeaux de fin d’année et les records de ventes.')
    . $p('Le choix d’un jeudi n’est pas anodin : il laisse le week-end suivant aux joueurs pour s’immerger. Notre compte à rebours suit le décompte en direct sur la page d’accueil.'), 3);

$add('news', 'confirmed', 'V-Rock de retour : la radio culte de Vice City confirmée',
    'La station rock emblématique de 2002 réapparaît, aperçue sur un t-shirt de Jason.',
    $p('Bonne nouvelle pour les nostalgiques : <strong>V-Rock</strong>, la station rock culte du Vice City de 2002, fait son retour. Elle a été repérée sur un t-shirt porté par Jason Duval dans le trailer, un clin d’œil assumé à l’héritage de la saga.')
    . $p('De quoi nourrir les espoirs sur une bande-son taillée pour l’ambiance 80s de Vice City. On fait le point sur les radios et musiques dans un article dédié.'), 5);

$add('news', 'analysis', 'Port Gellhorn et Mount Kalaga : les nouveaux lieux de Leonida',
    'Docks industriels au sud, parc national sauvage : Leonida ne se limite pas à la ville.',
    $p('Deux lieux officiellement nommés intriguent déjà. <strong>Port Gellhorn</strong>, au sud de Vice City, aligne porte-conteneurs, docks et activités criminelles industrielles. <strong>Mount Kalaga</strong>, parc national, suggère reliefs, sentiers et hors-piste.')
    . $p('Ces décors confirment une carte de Leonida très variée, bien au-delà de la seule métropole. On recense les zones connues dans notre Map Lab.'), 4);

$add('news', 'confirmed', 'Édition Ultimate de GTA 6 : tout le contenu exclusif détaillé',
    'Cinq véhicules, quatre variantes d’armes, cinq boutiques, un garage et une mission en plus.',
    $p('L’<strong>édition Ultimate</strong> (99,99 $) empile le contenu par-dessus la Standard : <strong>5 véhicules exclusifs</strong>, <strong>4 variantes d’armes</strong>, plusieurs packs cosmétiques, <strong>5 boutiques</strong> supplémentaires, un <strong>garage dédié</strong> et une <strong>mission annexe</strong> absente de l’édition Standard au lancement.')
    . $p('Bon à savoir : on pourra passer à l’Ultimate même après la sortie. Notre guide t’aide à choisir l’édition adaptée à ton budget.'), 6);

$add('news', 'official', 'Le Vintage Vice City Pack offert à toute précommande',
    'Stanier ’55, garage Shore Court, tenues rétro pour Jason et Lucia : le bonus est généreux.',
    $p('Toute <strong>précommande</strong> de <strong>GTA 6</strong>, quelle que soit l’édition, débloque le <strong>Vintage Vice City Pack</strong> au lancement. Au menu : la <strong>’55 Vapid Stanier</strong> (berline vintage deux tons) et le garage Shore Court près d’Ocean Beach.')
    . $p('Le pack ajoute aussi des tenues et coiffures rétro pour Jason et Lucia, ainsi qu’un motif d’arme inspiré de la chemise à palmiers de Tommy Vercetti. Disponible pour les achats numériques avant le 20 novembre 2026.'), 5);

$add('news', 'analysis', 'GTA 6 : deux reports, une attente devenue mythique',
    'De 2025 au 19 novembre 2026, retour sur un calendrier qui a tenu le monde en haleine.',
    $p('Révélé en décembre 2023 avec une fenêtre 2025, <strong>GTA 6</strong> a connu deux reports : d’abord vers le 26 mai 2026, puis vers le <strong>19 novembre 2026</strong>. Une patience mise à rude épreuve, mais qui a transformé chaque annonce en événement planétaire.')
    . $p('Rockstar a toujours privilégié la finition à la précipitation. À la lumière de RDR2, ce choix paraît cohérent. On compare les ambitions de GTA 6 à celles de GTA V dans un dossier dédié.'), 7);

$add('news', 'confirmed', 'NINE1NINE : le nightclub au cœur de la scène musicale de Vice City',
    'Le club apparaît comme un lieu clé, lié aux personnages du milieu musical.',
    $p('Le nightclub <strong>NINE1NINE</strong> s’impose comme un lieu important de <strong>GTA 6</strong>. Il est associé à des personnages liés à la scène musicale, ce qui laisse entrevoir des missions autour des clubs, du business et du divertissement de Vice City.')
    . $p('La musique sera clairement au centre de l’expérience, dans la lignée d’une saga qui a fait des radios une signature.'), 6);

$add('news', 'analysis', 'Boobie Ike, Dre’Quan Priest, Real Dimez : le casting secondaire de GTA 6',
    'Mogul de clubs, producteur, duo de rap viral : Leonida grouille de personnalités.',
    $p('Au-delà de Jason et Lucia, le trailer 2 a présenté un casting secondaire savoureux : <strong>Boobie Ike</strong>, magnat des clubs et de l’immobilier ; <strong>Dre’Quan Priest</strong>, producteur de musique ; et <strong>Real Dimez</strong>, duo de rap viral.')
    . $p('On a aussi croisé Cal Hampton, l’ami complotiste de Jason, et Brian Heder, contrebandier à l’ancienne. Un écosystème de personnages qui annonce une histoire dense.'), 8);

$add('news', 'probable', 'Version PC de GTA 6 : pourquoi il faudra patienter',
    'Aucune version PC n’est confirmée au lancement ; l’histoire de Rockstar invite à la prudence.',
    $p('À ce jour, <strong>aucune version PC</strong> de <strong>GTA 6</strong> n’est officiellement confirmée. Le jeu sort d’abord sur PS5 et Xbox Series X|S. Si l’on se fie aux habitudes de Rockstar (GTA V, RDR2), une édition PC enrichie arrive généralement plusieurs mois à un an après les consoles.')
    . $p('Notre conseil : si tu veux jouer day-one, vise la console ; si tu attends les mods et le 4K, prends ton mal en patience. On suit la moindre annonce dans nos actualités.'), 9);

$add('news', 'analysis', 'GTA 6, jeu le plus attendu de la décennie : d’où vient cette hype',
    'Records de vues, attente de plus de dix ans, retour à Vice City : un cocktail unique.',
    $p('Aucun jeu n’avait suscité une telle attente. Plus de dix ans après GTA V, le retour à <strong>Vice City</strong> avec une nouvelle génération de matériel cristallise les espoirs de toute une communauté. Les trailers ont pulvérisé des records de vues en quelques heures.')
    . $p('Entre nostalgie 80s, ambition technique et duo de héros inédit, <strong>GTA 6</strong> coche toutes les cases d’un événement culturel. La communauté s’enflamme déjà sur notre forum.'), 5);

/* ===================== GUIDES ===================== */
$add('guides', null, 'Standard ou Ultimate : quelle édition de GTA 6 choisir ?',
    'Comparatif clair des deux éditions pour décider sereinement, selon ton budget et tes envies.',
    $p('Deux éditions, deux philosophies. L’<strong>édition Standard</strong> (79,99 $) offre le jeu complet et le bonus de précommande. L’<strong>édition Ultimate</strong> (99,99 $) ajoute 5 véhicules exclusifs, 4 variantes d’armes, des packs cosmétiques, 5 boutiques, un garage dédié et une mission annexe.')
    . $p('Notre conseil : si tu veux un démarrage plus garni et soutenir le studio, l’Ultimate vaut ses 20 $ de plus. Si tu privilégies l’histoire brute, la Standard reste un excellent choix. Et comme la mise à niveau est possible plus tard, rien ne presse.')
    . $p('Tous les détails et la FAQ se trouvent dans notre dossier complet sur GTA 6.'), 6);

$add('guides', null, 'Comment précommander GTA 6 et décrocher le Vintage Vice City Pack',
    'Le guide pas à pas pour précommander au bon endroit et ne pas rater le bonus.',
    $p('La <strong>précommande</strong> de <strong>GTA 6</strong> est ouverte depuis le 25 juin 2026 sur les boutiques PlayStation et Xbox. Pour garantir le Vintage Vice City Pack, privilégie un achat numérique avant le 20 novembre 2026 ; en boîte, le bonus est offert dans la limite des stocks.')
    . $p('Étapes : choisis ta plateforme, sélectionne l’édition (Standard ou Ultimate), vérifie que le Vintage Vice City Pack figure bien dans le récapitulatif, puis valide. Garde la tête froide : 80 $ ou 100 $, ça se décide à tête reposée.'), 6);

$add('guides', null, 'GTA 6 sur PS5 ou Xbox Series : comment choisir sa plateforme',
    'Manette, écosystème, communauté : les vrais critères pour trancher.',
    $p('<strong>GTA 6</strong> sort sur PS5 et Xbox Series X|S, et il sera magnifique sur les deux. Le choix tient surtout à ton écosystème : reste là où sont tes amis et tes jeux. La manette DualSense et son retour haptique sont un argument pour la conduite côté PlayStation.')
    . $p('Tu veux la boîte physique avec code ? C’est possible avec l’édition Standard. Dans tous les cas, vise la version optimisée nouvelle génération. Compare aussi avec ce que proposait GTA V pour mesurer le saut.'), 7);

$add('guides', null, 'Le Vintage Vice City Pack en détail : Stanier ’55, tenues, garage',
    'Tour complet du bonus de précommande, voiture, cosmétiques et garage compris.',
    $p('Le <strong>Vintage Vice City Pack</strong> est le bonus de toute <strong>précommande</strong>. Il comprend la <strong>’55 Vapid Stanier</strong>, une berline vintage deux tons, et le garage Shore Court à deux pas d’Ocean Beach.')
    . $p('Côté style, le pack ajoute des tenues et coiffures rétro pour Jason et Lucia — costume en lin pastel pour lui, robe à sequins rouge pour elle — et un motif d’arme inspiré de la fameuse chemise à palmiers de Tommy Vercetti. Un clin d’œil direct au Vice City d’origine.'), 6);

$add('guides', null, 'Bien préparer la sortie de GTA 6 : la checklist avant le 19 novembre',
    'Espace disque, plateforme, précommande, sauvegarde de ton temps : on s’organise.',
    $p('À l’approche du 19 novembre 2026, autant être prêt. Notre checklist : choisis et précommande ton édition, libère de l’espace de stockage, vérifie ta connexion pour le téléchargement, et pose éventuellement un jour de congé.')
    . $p('Profite des derniers mois pour (re)découvrir l’univers : explore la carte de Leonida, relis les fiches personnages et garde un œil sur nos actualités. Et pourquoi pas habiller ton écran avec nos fonds d’écran Vice City.'), 7);

$add('guides', null, 'Combien d’espace disque prévoir pour GTA 6 ?',
    'Rockstar n’a pas communiqué de chiffre officiel : voici une estimation raisonnée.',
    $p('Rockstar n’a pas encore communiqué la taille d’installation de <strong>GTA 6</strong>. À titre de repère, GTA V dépassait les 90 Go et RDR2 frôlait les 150 Go. Pour un titre next-gen de cette ampleur, prévoir <strong>150 à 200 Go</strong> semble prudent.')
    . $p('Notre conseil : libère de la place dès maintenant, voire envisage un SSD supplémentaire. Ce n’est qu’une estimation ; on mettra l’article à jour dès l’annonce officielle dans nos actualités.'), 8);

$add('guides', null, 'Jason et Lucia : tout ce que l’on sait du duo jouable',
    'Origines, personnalités, dynamique : le portrait des deux héros de GTA 6.',
    $p('Pour la première fois, on incarne un duo. <strong>Jason Duval</strong>, ex-militaire passé par les Keys, et <strong>Lucia Caminos</strong>, première héroïne jouable de la saga, forment un couple à la Bonnie &amp; Clyde. Leur relation est le cœur émotionnel de l’histoire.')
    . $p('On ignore encore l’étendue exacte du switch entre les deux personnages, mais la mise en scène à deux voix s’annonce inédite. Plus de détails dans notre fiche personnages.'), 6);

$add('guides', null, 'La carte de Leonida : régions, villes et points d’intérêt connus',
    'Vice City, Port Gellhorn, Mount Kalaga, les Keys : tour d’horizon de ce qui est confirmé.',
    $p('La <strong>carte de Leonida</strong> mêle métropole et arrière-pays. Au centre, Vice City et sa vie nocturne néon. Au sud, <strong>Port Gellhorn</strong> et ses docks industriels. Plus loin, <strong>Mount Kalaga</strong> et son parc national, et les Leonida Keys où la plongée sous-marine a été confirmée.')
    . $p('On y ajoute plages, marais et Everglades : une variété de décors rare pour la saga. Explore tout ça dans notre Map Lab.'), 6);

$add('guides', null, 'Les activités confirmées de GTA 6 : plongée, pêche, combats…',
    'Au-delà des braquages, Leonida promet de quoi s’occuper en dehors des missions.',
    $p('Les trailers ont confirmé un éventail d’activités : braquages de commerces, courses-poursuites en voiture, <strong>combats clandestins</strong> (fight clubs), poursuites en bateau, mais aussi <strong>pêche</strong>, <strong>chasse</strong> et <strong>plongée sous-marine</strong> dans les Leonida Keys.')
    . $p('De quoi nourrir des dizaines d’heures hors scénario. On mettra cette liste à jour à chaque nouvelle confirmation officielle.'), 7);

$add('guides', null, 'Vice City vs la vraie Miami : les inspirations de Leonida',
    'Ocean Drive, Art déco, Everglades : comment Rockstar transpose la Floride.',
    $p('<strong>Vice City</strong> est la relecture de Miami par Rockstar, et <strong>Leonida</strong> celle de la Floride. On y retrouve l’Art déco d’Ocean Drive, les néons des clubs, les marécages des Everglades et l’ambiance moite des Keys.')
    . $p('Comme toujours chez Rockstar, l’inspiration réelle se double d’une satire mordante. Un terrain de jeu crédible et caricatural à la fois, que l’on explore dans notre Dossier.'), 8);

$add('guides', null, 'GTA 6 : faut-il (re)jouer à GTA 5 en attendant ?',
    'Excellent moyen de patienter, à condition de savoir ce qui va changer.',
    $p('En attendant le 19 novembre, relancer <strong>GTA V</strong> reste un excellent plan : toujours superbe sur PS5 et PC, et idéal pour se remettre dans l’ambiance. Mais attention aux attentes : <strong>GTA 6</strong> change beaucoup, du duo jouable au retour à Vice City.')
    . $p('On a comparé les deux jeux point par point — carte, héros, technique, prix — dans un dossier dédié pour savoir à quoi t’attendre.'), 7);

$add('guides', null, 'Les radios et la bande-son de GTA 6 : ce qui est confirmé',
    'V-Rock de retour, morceaux des trailers : le point sur la musique de Leonida.',
    $p('La musique est une signature de la saga. Pour <strong>GTA 6</strong>, <strong>V-Rock</strong> est confirmée de retour. Les trailers ont fait entendre « Love Is a Long Road » de Tom Petty (trailer 1) et « Hot Together » des Pointer Sisters (trailer 2), entre autres pépites 70s-80s.')
    . $p('Le nightclub NINE1NINE et les personnages du milieu musical laissent présager une scène très développée. Reste à découvrir la liste complète des stations à la sortie.'), 7);

$add('guides', null, 'GTA 6 et GTA Online : ce que l’on sait du futur multijoueur',
    'Rockstar travaille sur l’avenir du online, mais sans détails au lancement solo.',
    $p('Rockstar a confirmé travailler sur l’avenir du multijoueur de la saga. Toutefois, les détails du mode en ligne de <strong>GTA 6</strong> seront communiqués plus tard : il ne fait pas partie de l’expérience solo décrite au lancement.')
    . $p('Au vu du succès durable de GTA Online (soutenu plus de dix ans), les attentes sont énormes. On relaiera la moindre annonce officielle dans nos actualités.'), 8);

$add('guides', null, 'Pourquoi GTA 6 n’a pas d’édition collector physique',
    'Deux éditions seulement, pas de boîte collector : on t’explique.',
    $p('Contrairement à beaucoup de blockbusters, <strong>GTA 6</strong> ne propose pas d’édition collector physique. Il existe seulement deux éditions : <strong>Standard</strong> et <strong>Ultimate</strong>. La Standard peut s’acheter en boîte, mais celle-ci contient un code de téléchargement plutôt qu’un disque dans certaines régions.')
    . $p('Pas de figurine ni de steelbook à chasser, donc : l’essentiel se joue côté contenu numérique. Notre guide d’achat fait le tri.'), 8);

$add('guides', null, 'Habiller son setup aux couleurs de Vice City',
    'Fonds d’écran, posters et goodies pour une ambiance néon avant la sortie.',
    $p('En attendant <strong>GTA 6</strong>, pourquoi ne pas mettre ton setup à l’heure de Vice City ? Nos <strong>fonds d’écran</strong> HD déclinent supercars, skylines néon, plages et nuits électriques, pour PC comme pour téléphone, livrés sans filigrane.')
    . $p('Côté déco, la boutique propose aussi posters et goodies façon Leonida. De quoi patienter avec style jusqu’au 19 novembre.'), 9);

$add('guides', null, 'Reconnaître un vrai leak GTA 6 d’une intox',
    'Sources, recoupements, cohérence : la méthode pour ne pas se faire avoir.',
    $p('À l’approche de la sortie, les faux leaks <strong>GTA 6</strong> se multiplient. La règle d’or : pas de source vérifiable, pas de crédit. Méfie-toi des images trop nettes sorties de nulle part, des « dates fuitées » et des comptes anonymes qui réclament de l’attention.')
    . $p('Croise toujours avec les communications officielles de Rockstar. Notre Leaks Lab attribue un indice de fiabilité à chaque rumeur pour t’aider à faire le tri.'), 8);

/* ===================== LEAKS ===================== */
$add('leaks', 'probable', 'Rumeur : des radios animées par de vrais artistes dans GTA 6',
    'En 2025, un producteur a évoqué des stations co-créées avec des artistes. Non confirmé.',
    $p('Une rumeur insistante évoque des <strong>radios animées par de vrais artistes</strong> dans <strong>GTA 6</strong>, avec des morceaux inédits publiés directement dans le jeu. L’idée a notamment été soulevée par un producteur connu en 2025.')
    . $p('À ce stade, rien n’est officiel : on classe l’info en « probable mais non confirmé ». Ça collerait à l’importance de la scène musicale (NINE1NINE, producteurs) déjà entrevue dans les trailers.'), 9);

$add('leaks', 'rumor', 'Taille de la carte de GTA 6 : ce que disent les analyses',
    'Les estimations vont bon train, mais aucun chiffre officiel n’a été communiqué.',
    $p('Combien mesurera la <strong>carte de Leonida</strong> ? Les analyses de fans, à partir des trailers, suggèrent une surface supérieure à celle de GTA V, ville et arrière-pays compris. Mais aucun chiffre officiel n’existe à ce jour.')
    . $p('Plus que la taille brute, c’est la densité qui intrigue : circulation crédible, quartiers marqués, faune réactive. On reste prudents tant que Rockstar ne communique pas.'), 10);

$add('leaks', 'probable', 'Métro, avions, sous-marins : les moyens de transport supposés de GTA 6',
    'Entre confirmations et déductions, le tour des déplacements possibles dans Leonida.',
    $p('Bateaux, jet-skis et avions semblent acquis au vu des trailers de <strong>GTA 6</strong> ; la plongée est confirmée dans les Keys. Restent des suppositions : transports en commun étendus, petits sous-marins de loisir, hydravions pour rejoindre les îles.')
    . $p('Rien d’officiel sur ces derniers points : prudence. On suit les indices et on met à jour notre Map Lab au fil des confirmations.'), 11);

$add('leaks', 'rumor', 'Économie et propriétés : les rumeurs de business dans GTA 6',
    'Achat de biens, commerces à gérer : des pistes crédibles mais non confirmées.',
    $p('Plusieurs rumeurs évoquent une <strong>économie</strong> plus poussée dans <strong>GTA 6</strong> : achat de propriétés, gestion de commerces, voire empire criminel à bâtir. Les personnages comme Boobie Ike, magnat de l’immobilier et des clubs, alimentent ces hypothèses.')
    . $p('À prendre avec des pincettes : rien n’est confirmé côté gestion. Mais l’idée collerait à l’ADN de la série.'), 10);

$add('leaks', 'probable', 'Météo dynamique et ouragans : l’hypothèse Leonida',
    'Une Floride sans tempêtes serait étonnante : la météo dynamique fait débat.',
    $p('Difficile d’imaginer une <strong>Leonida</strong> crédible sans météo capricieuse. Les fans s’attendent à des orages tropicaux, voire des ouragans, susceptibles d’influer sur la conduite et l’ambiance. Les trailers montrent déjà pluie et ciels changeants.')
    . $p('Rien d’officiel sur des événements météo scénarisés, mais l’hypothèse est solide au vu du cadre floridien.'), 12);

$add('leaks', 'rumor', 'PNJ plus intelligents : ce que laissent penser les trailers de GTA 6',
    'Foules réactives, comportements crédibles : la rumeur d’une IA next-gen.',
    $p('Les trailers de <strong>GTA 6</strong> montrent des foules et des PNJ particulièrement réactifs, ce qui a relancé l’idée d’une <strong>IA</strong> bien plus poussée que dans GTA V. Réactions aux situations, vie de quartier crédible : les espoirs sont grands.')
    . $p('Attention toutefois : un trailer est une vitrine. On attendra le jeu final avant de juger. La promesse, elle, est alléchante.'), 11);

$add('leaks', 'probable', 'Crossplay GTA Online : le serpent de mer',
    'Les joueurs réclament le crossplay ; Rockstar n’a rien confirmé pour le futur online.',
    $p('Le <strong>crossplay</strong> entre PS5 et Xbox est l’une des demandes récurrentes pour le futur mode en ligne de <strong>GTA 6</strong>. Techniquement crédible sur cette génération, il reste totalement non confirmé par Rockstar.')
    . $p('Comme les détails du online viendront plus tard, patience. On en reparlera dès qu’une info officielle tombera.'), 12);

$add('leaks', 'rumor', 'Date de la version PC de GTA 6 : les paris de la communauté',
    'Entre 6 mois et un an après les consoles : les estimations s’accordent sans certitude.',
    $p('Faute d’annonce, la communauté parie sur l’arrivée de la <strong>version PC</strong> de <strong>GTA 6</strong> entre six mois et un an après la sortie console, par analogie avec GTA V et RDR2. Rien d’officiel, donc tout reste hypothétique.')
    . $p('Notre conseil reste le même : si tu veux jouer day-one, vise la console. On relaiera la date PC dès sa confirmation.'), 13);

$add('leaks', 'probable', 'Mises à jour post-lancement : le modèle GTA 5 va-t-il se répéter ?',
    'Support au long cours, contenus réguliers : un scénario très probable pour GTA 6.',
    $p('GTA V a été soutenu plus de dix ans via GTA Online. Il est <strong>très probable</strong> que <strong>GTA 6</strong> suive un modèle de support au long cours, avec des contenus réguliers une fois le online lancé.')
    . $p('Rien d’officiel sur le calendrier, mais l’économie de la saga repose largement là-dessus. À surveiller après la sortie.'), 12);

$add('leaks', 'rumor', 'Personnalisation des persos : tatouages, fringues, coupe de cheveux',
    'Les trailers laissent entrevoir une customisation poussée de Jason et Lucia.',
    $p('Les tenues rétro du Vintage Vice City Pack et les apparences variées vues dans les trailers nourrissent l’idée d’une <strong>personnalisation</strong> poussée pour Jason et Lucia : vêtements, coiffures, peut-être tatouages.')
    . $p('Le niveau de détail exact reste à confirmer. Mais l’importance accordée au style colle parfaitement à l’univers de Vice City.'), 11);

/* ===================== TRAILERS ===================== */
$add('trailers', 'analysis', 'Décryptage image par image du trailer 2 de GTA 6',
    'Plus de 70 visuels, des dizaines de détails : on passe le trailer 2 au peigne fin.',
    $p('Le trailer 2 de <strong>GTA 6</strong> fourmille de détails : braquages, fight clubs, poursuites en bateau, scènes de plage et de club. Chaque plan trahit l’ambiance d’une <strong>Vice City</strong> dense, vivante et néon.')
    . $p('On y devine la dynamique entre Jason et Lucia, des lieux comme Port Gellhorn et NINE1NINE, et une direction artistique au sommet. Notre Trailer Lab détaille les indices clé par clé.'), 4);

$add('trailers', 'analysis', 'Trailer 1 vs Trailer 2 : ce qui a changé en deux ans',
    'Du teaser de 2023 au trailer de 2025, mesure du chemin parcouru.',
    $p('Entre le premier trailer (décembre 2023) et le second, deux ans se sont écoulés — et ça se voit. Le trailer 2 de <strong>GTA 6</strong> approfondit les personnages, élargit les décors de <strong>Leonida</strong> et muscle la mise en scène.')
    . $p('Là où le premier posait l’ambiance et présentait Lucia Caminos, le second installe une vraie dynamique de duo et dévoile l’ampleur du monde. Comparaison détaillée dans notre Trailer Lab.'), 6);

$add('trailers', 'analysis', 'Les easter eggs cachés dans les trailers de GTA 6',
    'Clins d’œil à Tommy Vercetti, V-Rock, anciens opus : Rockstar adore les détails.',
    $p('Les trailers de <strong>GTA 6</strong> regorgent de clins d’œil : le retour de <strong>V-Rock</strong>, le motif d’arme inspiré de la chemise de Tommy Vercetti, des références au Vice City de 2002… Rockstar récompense les fans attentifs.')
    . $p('Chaque visionnage révèle de nouveaux détails. On recense les meilleurs easter eggs au fil de nos analyses dans le Trailer Lab.'), 7);

$add('trailers', 'analysis', 'Hot Together, Love Is a Long Road : la BO des trailers de GTA 6',
    'Le choix musical des trailers en dit long sur l’ambiance visée.',
    $p('La musique des trailers n’est jamais anodine chez Rockstar. Le premier misait sur « Love Is a Long Road » de Tom Petty ; le second sur « Hot Together » des Pointer Sisters, avec d’autres pépites 70s-80s en fond.')
    . $p('Ces choix dessinent l’âme sonore de <strong>GTA 6</strong> : chaleur tropicale, nostalgie et énergie. De bon augure pour les radios, dont V-Rock confirmée.'), 6);

$add('trailers', 'analysis', 'Ce que les trailers révèlent (sans le dire) sur l’histoire de GTA 6',
    'Braquages, cavale, loyauté : lecture entre les lignes de la narration.',
    $p('Sans rien dévoiler frontalement, les trailers de <strong>GTA 6</strong> esquissent une trame : la rencontre de Jason et Lucia, une spirale de braquages, et une cavale façon Bonnie &amp; Clyde à travers <strong>Leonida</strong>.')
    . $p('Loyauté, survie et ascension semblent au cœur du récit. On affine ces hypothèses au fil des indices dans notre fiche personnages.'), 8);

/* ===================== BLOG ===================== */
$add('blog', null, 'Vice City, 2002–2026 : l’histoire d’un retour très attendu',
    'De la PS2 à la PS5, retour sur la ville qui a marqué une génération.',
    $p('En 2002, <strong>Vice City</strong> imposait sa Floride néon et sa bande-son 80s comme un jalon du jeu vidéo. Près d’un quart de siècle plus tard, <strong>GTA 6</strong> y revient avec une ambition décuplée et une technologie nouvelle génération.')
    . $p('Ce retour n’est pas qu’une madeleine de Proust : c’est l’occasion de réinventer un mythe pour une nouvelle génération de joueurs. Et de comparer le chemin parcouru depuis GTA V.'), 9);

$add('blog', null, 'Pourquoi une héroïne change tout pour la saga GTA',
    'Avec Lucia Caminos, Rockstar fait évoluer une formule vieille de plus de 25 ans.',
    $p('Faire de <strong>Lucia Caminos</strong> la première héroïne jouable de la saga n’est pas un détail. C’est un signal : <strong>GTA 6</strong> veut une narration plus humaine, portée par un duo et une relation, là où la série mettait surtout en scène des solitaires.')
    . $p('Ce choix ouvre des perspectives d’écriture inédites et modernise une formule iconique. On en parle plus en détail dans notre fiche personnages.'), 9);

$add('blog', null, 'Le synthwave, les néons et la nostalgie 80s de Vice City',
    'Pourquoi l’esthétique rétro de Leonida nous parle autant.',
    $p('Couchers de soleil magenta, palmiers, néons et synthwave : l’esthétique de <strong>Vice City</strong> puise dans un imaginaire 80s devenu intemporel. <strong>GTA 6</strong> en fait un argument artistique majeur.')
    . $p('Cette nostalgie soigneusement dosée explique en partie l’engouement. Elle inspire d’ailleurs nos fonds d’écran, pour emporter un morceau de Leonida sur ton écran.'), 10);

$add('blog', null, 'Bonnie & Clyde : l’ADN criminel de Jason et Lucia',
    'Le duo de GTA 6 puise dans une longue tradition de couples hors-la-loi.',
    $p('Le couple <strong>Jason et Lucia</strong> évoque immédiatement Bonnie &amp; Clyde et les grands films de cavale. Une référence assumée qui place la loyauté et la survie à deux au centre de <strong>GTA 6</strong>.')
    . $p('Ce parti pris narratif promet des missions à deux points de vue et des enjeux émotionnels inédits pour la saga. Le genre d’histoire qui marque.'), 9);

$add('blog', null, 'Ce que Rockstar a appris de RDR2 pour GTA 6',
    'Densité, détails, immersion : l’héritage de Red Dead Redemption 2.',
    $p('Red Dead Redemption 2 a placé la barre très haut en matière de densité et de détails. On retrouve dans <strong>GTA 6</strong> cette même obsession : foules crédibles, monde réactif, mise en scène cinématographique.')
    . $p('Cet héritage explique en partie le temps de développement — et les reports. Mais à la lumière de RDR2, le pari de la patience paraît justifié.'), 10);

$add('blog', null, 'La Floride comme terrain de jeu : crocos, ouragans et néons',
    'Pourquoi la Floride est un décor de rêve pour un GTA.',
    $p('Entre métropole clinquante, marécages des Everglades, Keys paradisiaques et météo extrême, la Floride — <strong>Leonida</strong> dans le jeu — offre une variété de décors idéale pour <strong>GTA 6</strong>.')
    . $p('Crocodiles, ouragans, contrebande : autant d’ingrédients pour un terrain de jeu vivant et imprévisible. On explore ces régions dans notre Map Lab.'), 11);

$add('blog', null, 'Notre top des moments du trailer 2 de GTA 6',
    'Les plans qui nous ont fait lever de notre chaise.',
    $p('Le trailer 2 de <strong>GTA 6</strong> ne manque pas de moments forts : la première vraie alchimie entre Jason et Lucia, les plans aériens sur Vice City, les courses-poursuites et l’ambiance des clubs.')
    . $p('Chacun a son plan préféré — et c’est tout l’intérêt. Viens partager le tien sur notre forum, la communauté en débat déjà.'), 8);

$add('blog', null, 'GTA 6 et la musique : V-Rock, hip-hop et héritage 80s',
    'La bande-son comme personnage à part entière de Vice City.',
    $p('Chez Rockstar, la musique est un personnage. Pour <strong>GTA 6</strong>, le retour de <strong>V-Rock</strong>, la présence d’une scène hip-hop (Dre’Quan Priest, Real Dimez) et l’héritage 80s annoncent une bande-son riche et éclectique.')
    . $p('Le nightclub NINE1NINE pourrait même devenir un lieu central de l’expérience. Vivement la liste complète des radios à la sortie.'), 9);

$add('blog', null, 'Collectionner Vice City : posters, fonds d’écran et goodies',
    'Comment vivre sa passion GTA 6 en attendant le 19 novembre.',
    $p('La passion <strong>GTA 6</strong> se vit aussi hors écran. Entre posters néon, <strong>fonds d’écran</strong> HD et goodies façon Leonida, il y a de quoi s’entourer de Vice City en attendant la sortie.')
    . $p('Nos packs de fonds d’écran permettent même d’habiller tous tes écrans d’un coup, à prix réduit. Direction la boutique pour faire ton choix.'), 10);

$add('blog', null, 'Communauté GTA 6 : pourquoi le forum s’enflamme déjà',
    'À cinq mois de la sortie, les débats n’ont jamais été aussi vifs.',
    $p('Éditions, carte, théories sur l’histoire : à l’approche de <strong>GTA 6</strong>, notre forum bouillonne. Chaque trailer, chaque rumeur déclenche des dizaines de discussions passionnées.')
    . $p('C’est aussi ça, l’attente : une communauté qui partage sa hype. Rejoins le débat sur le forum et donne ton avis avant le grand jour.'), 7);

/* ===================== NEWS (compléments) ===================== */
$add('news', 'analysis', 'GTA 6 et l’action Take-Two : un enjeu boursier colossal',
    'Le lancement de GTA 6 est scruté bien au-delà des joueurs, jusqu’à Wall Street.',
    $p('<strong>GTA 6</strong> n’est pas qu’un jeu : c’est un événement économique. Les analystes attendent des ventes records, et le titre pèse lourd sur les perspectives de Take-Two Interactive, l’éditeur de Rockstar.')
    . $p('Chaque annonce de date influence la perception du marché. Pour s’amuser de cette dimension, on a même imaginé notre parodie boursière BAWSAQ. Côté faits, on s’en tient aux chiffres officiels.'), 6);

$add('news', 'confirmed', 'Mise à niveau vers l’Ultimate possible après la sortie de GTA 6',
    'Pas besoin de payer 100 $ tout de suite : l’upgrade restera accessible plus tard.',
    $p('Bonne nouvelle pour les indécis : il sera possible de passer à l’<strong>édition Ultimate</strong> de <strong>GTA 6</strong> même après la sortie. Inutile donc de débourser 99,99 $ immédiatement si tu hésites.')
    . $p('Tu peux démarrer avec la Standard, puis débloquer les récompenses Ultimate quand tu le souhaites. On détaille la stratégie d’achat dans notre guide des éditions.'), 5);

$add('news', 'analysis', 'Compte à rebours GTA 6 : où en est l’attente à cinq mois de la sortie',
    'À l’été 2026, l’excitation monte d’un cran à chaque semaine qui passe.',
    $p('À cinq mois du <strong>19 novembre 2026</strong>, l’attente autour de <strong>GTA 6</strong> atteint des sommets. Précommandes ouvertes, trailers décortiqués, théories en pagaille : la communauté vit au rythme du décompte.')
    . $p('Notre compte à rebours sur la page d’accueil suit le temps restant en direct. Et pour patienter, il y a de quoi faire : actualités, guides, forum et fonds d’écran.'), 4);

/* ===================== Insertion ===================== */
$pdo = db();
$now = time();
$find = $pdo->prepare('SELECT 1 FROM articles WHERE slug = ? LIMIT 1');
$ins = $pdo->prepare(
    "INSERT INTO articles (category_id, lang, title, slug, excerpt, body, badge, status, published_at, created_at)
     VALUES (?, 'fr', ?, ?, ?, ?, ?, 'published', ?, NOW())"
);

$made = 0; $skip = 0;
foreach ($A as $a) {
    $slug = slugify($a['title']);
    $find->execute([$slug]);
    if ($find->fetchColumn()) { $skip++; continue; }
    $pub = date('Y-m-d H:i:s', $now - $a['days'] * 86400 - random_int(0, 50000));
    $ins->execute([$CAT[$a['cat']], $a['title'], $slug, $a['excerpt'], $a['body'], $a['badge'], $pub]);
    $made++;
}

echo "✓ Articles créés : {$made} · ignorés (déjà présents) : {$skip}.\n";
$tot = $pdo->query("SELECT c.slug, COUNT(*) n FROM articles a JOIN categories c ON c.id=a.category_id WHERE a.status='published' GROUP BY c.slug")->fetchAll(PDO::FETCH_KEY_PAIR);
echo '→ Articles publiés par rubrique : ' . json_encode($tot, JSON_UNESCAPED_UNICODE) . "\n";
