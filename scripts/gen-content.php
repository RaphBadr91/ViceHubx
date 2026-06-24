<?php
/**
 * ViceHub X — Générateur de contenu éditorial (≈70 articles FR).
 * Produit database/seed_content.sql (INSERT prêts à charger).
 *   Usage : php scripts/gen-content.php
 * Le contenu est rédigé en français, ton « média fan indépendant » (non officiel).
 */

$CAT = ['news' => 1, 'guides' => 2, 'leaks' => 3, 'trailers' => 4, 'blog' => 5];

/* p() = paragraphe, h() = sous-titre, ul() = liste. */
$p  = fn(string $s) => '<p>' . $s . '</p>';
$h  = fn(string $s) => '<h2>' . $s . '</h2>';
$ul = fn(array $a) => '<ul>' . implode('', array_map(fn($x) => '<li>' . $x . '</li>', $a)) . '</ul>';

$A = [];
$add = function (string $cat, ?string $badge, string $title, string $excerpt, string $body) use (&$A) {
    $A[] = compact('cat', 'badge', 'title', 'excerpt', 'body');
};

/* ============================ NEWS (20) ============================ */
$add('news', 'official', 'GTA VI : Rockstar vise toujours l’automne 2026',
    'Le studio confirme son cap : un lancement à l’automne 2026 sur PS5 et Xbox Series.',
    $p('Rockstar Games a réaffirmé son objectif de sortie pour l’automne 2026. Après deux bandes-annonces qui ont battu des records de vues, le studio garde le silence sur le jour exact, mais la fenêtre est claire : la fin d’année.')
    . $p('Pour les joueurs, cela signifie encore quelques mois d’attente — et de spéculation. Chez ViceHub X, on suit chaque communication officielle au mot près, sans relayer de fausses dates.'));

$add('news', 'confirmed', 'Jason et Lucia : le premier duo jouable de la saga',
    'Pour la première fois, GTA met en scène deux protagonistes liés par une histoire d’amour et de cavale.',
    $p('Vice City accueille Jason et Lucia, un duo dont la dynamique évoque autant Bonnie & Clyde que les grands films de braquage. C’est une première dans la saga principale : deux personnages jouables au cœur d’un même récit.')
    . $p('Cette structure narrative promet des missions à deux points de vue, des choix qui pèsent sur la relation, et une mise en scène plus cinématographique que jamais.'));

$add('news', 'confirmed', 'Vice City réinventée au cœur de l’État de Leonida',
    'La ville mythique revient, transposée dans une Floride fictive baptisée Leonida.',
    $p('Vice City n’est plus seule : elle s’inscrit désormais dans l’État de Leonida, une Floride de fiction qui mêle plages, marécages, banlieues et zones rurales.')
    . $p('Ce cadre élargi permet une variété de paysages inédite pour la saga : des néons de la ville aux Everglades brumeux, en passant par des petites villes côtières.'));

$add('news', 'probable', 'Une carte parmi les plus vastes jamais conçues',
    'Ville dense, arrière-pays, marais et littoral : l’échelle annoncée donne le vertige.',
    $p('Selon plusieurs analyses concordantes, la carte de GTA VI couvrirait la ville et son arrière-pays, avec des zones rurales jouables dès le lancement.')
    . $p('Au-delà de la taille brute, c’est la densité qui intrigue : circulation crédible, quartiers aux ambiances marquées et points d’intérêt nombreux. Reste à voir comment Rockstar gérera les temps de trajet.'));

$add('news', 'analysis', 'Le moteur RAGE pousse le réalisme d’un cran',
    'Physique des véhicules, eau, foule réactive : la démo technique impressionne.',
    $p('La technologie maison de Rockstar, le moteur RAGE, semble franchir un palier. Déformation des carrosseries, gestion réaliste de l’eau et IA piétonne crédible sont au programme.')
    . $p('Ces promesses techniques expliquent en partie le temps de développement. Un open-world vivant exige des milliers d’interactions cohérentes — c’est là que se joue la différence.'));

$add('news', 'probable', 'Météo dynamique : tempêtes tropicales et ouragans',
    'La Floride de Leonida vivra au rythme de son climat, parfois extrême.',
    $p('La météo ne serait pas qu’un décor : averses soudaines, orages et même ouragans pourraient transformer une partie en péril. Conduire sous la pluie, c’est déjà une autre histoire.')
    . $p('Si ces systèmes sont aussi poussés qu’annoncé, ils ajouteront une couche de tension et d’imprévu bienvenue aux balades comme aux courses-poursuites.'));

$add('news', 'analysis', 'Une faune sauvage pour une Floride vivante',
    'Alligators, flamants roses, dauphins : la nature s’invite dans l’open-world.',
    $p('Les marécages et les côtes de Leonida grouilleraient de vie. Alligators tapis dans l’eau, flamants roses au lever du soleil, dauphins au large : autant de détails qui renforcent l’immersion.')
    . $p('Cette faune n’est pas qu’esthétique. Elle participe à l’écosystème du jeu et pourrait réserver quelques mauvaises surprises aux joueurs imprudents.'));

$add('news', 'probable', 'La radio de retour avec des dizaines de stations',
    'Synthwave, hip-hop, latino : la bande-son s’annonce monumentale.',
    $p('La radio est l’âme de GTA. Pour Vice City, on attend une sélection musicale gigantesque, des stations synthwave aux sonorités latino qui collent à l’ambiance floridienne.')
    . $p('En attendant la vraie tracklist, branche Vice FM sur ViceHub X : notre radio synthwave maison te met dans l’ambiance dès maintenant.'));

$add('news', 'analysis', 'Réseaux sociaux in-game : un miroir de notre époque',
    'GTA a toujours satirisé son temps. Cette fois, la cible, ce sont nos écrans.',
    $p('Des séquences évoquent des plateformes sociales internes, des vidéos virales et une culture du buzz omniprésente. La satire, marque de fabrique de la série, viserait notre rapport aux écrans.')
    . $p('Au-delà de l’humour, ces mécaniques pourraient servir le gameplay : réputation, notoriété, missions déclenchées par un post devenu viral.'));

$add('news', 'analysis', 'Économie criminelle : braquages, trafics et magot',
    'Monter un coup, blanchir l’argent, gravir les échelons : le cœur de GTA bat toujours.',
    $p('Le crime organisé resterait le moteur du jeu. Préparer un braquage, recruter, choisir son approche puis écouler le butin : la boucle classique de GTA devrait gagner en profondeur.')
    . $p('La grande question : jusqu’où l’économie sera-t-elle simulée ? Un marché réactif, des prix qui fluctuent et des conséquences durables feraient toute la différence.'));

$add('news', 'analysis', 'Des forces de l’ordre plus malignes que jamais',
    'Le système d’étoiles évolue : la police s’adapte, coordonne et traque.',
    $p('Fini la fuite en ligne droite : la police de Leonida coordonnerait barrages, hélicoptères et recherches de zone. Semer ses poursuivants demanderait davantage de ruse.')
    . $p('Sur ViceHub X, on a même intégré un petit niveau de recherche clin d’œil en bas de l’écran. Tape un code de triche pour voir tes étoiles grimper.'));

$add('news', 'confirmed', 'Vice City de nuit : le néon comme signature visuelle',
    'Roses et cyans, reflets sur l’asphalte mouillé : l’identité visuelle est posée.',
    $p('La nuit, Vice City s’embrase de néons. Les reflets sur l’asphalte mouillé, les enseignes et les phares composent une signature visuelle immédiatement reconnaissable.')
    . $p('Cette direction artistique assume l’héritage des années 80 tout en le modernisant avec un rendu photoréaliste. C’est sans doute l’un des plus beaux jeux à venir.'));

$add('news', null, 'Rockstar recrute massivement pour le volet en ligne',
    'Les offres d’emploi laissent entrevoir l’ampleur du futur mode multijoueur.',
    $p('Plusieurs vagues de recrutement pointent vers un mode en ligne ambitieux. Difficile d’en connaître la forme exacte, mais l’investissement humain est colossal.')
    . $p('L’héritage de GTA Online, devenu une machine économique, pèse lourd. Rockstar voudra capitaliser sur cette base tout en évitant ses travers.'));

$add('news', 'analysis', 'GTA V à GTA VI : mesurer le grand saut',
    'Densité, IA, physique, narration : où se situent les vrais progrès ?',
    $p('Entre 2013 et 2026, la technologie a énormément progressé. Le bond le plus visible touche la densité de la foule, la finesse de l’animation et la simulation de l’environnement.')
    . $p('Mais le vrai saut pourrait être narratif : un duo jouable, des personnages plus écrits, et une mise en scène qui se rapproche du cinéma.'));

$add('news', 'analysis', 'Pourquoi GTA VI peut battre tous les records',
    'Attente colossale, hype mondiale, base installée énorme : les planètes s’alignent.',
    $p('Aucun lancement de jeu n’a jamais réuni autant d’attente. La base installée des consoles est énorme, la notoriété de la franchise est mondiale, et la hype est entretenue depuis des années.')
    . $p('Tous les indicateurs pointent vers un raz-de-marée commercial. La seule inconnue, c’est l’ampleur exacte du phénomène.'));

$add('news', 'probable', 'Personnalisation des véhicules : le retour des ateliers ?',
    'Carrosserie, moteur, look : on espère un garage digne des meilleurs opus.',
    $p('Tuning esthétique et mécanique, plaques, livrées : la personnalisation des véhicules est une attente forte. Les ateliers pourraient revenir en force.')
    . $p('Reste à savoir si cette profondeur sera réservée au mode en ligne ou pleinement présente en solo. Les fans de belles caisses guettent.'));

$add('news', 'analysis', 'Le mode photo, futur terrain de jeu des créateurs',
    'Vice City est si photogénique qu’un mode photo complet semble incontournable.',
    $p('Avec une direction artistique pareille, un mode photo riche s’impose. Filtres, profondeur de champ, réglages d’heure : de quoi nourrir des millions de captures.')
    . $p('Sur ViceHub X, on prépare déjà une galerie de fan-arts et de captures. Les plus belles images de la communauté y auront leur place.'));

$add('news', null, 'Pas de version PC au lancement : l’hypothèse qui agace',
    'Comme souvent chez Rockstar, le PC pourrait arriver après les consoles.',
    $p('L’historique du studio inquiète les joueurs PC : GTA V était sorti d’abord sur consoles. Rien n’est confirmé, mais l’hypothèse d’un PC différé revient sans cesse.')
    . $p('On préfère rester prudents : tant que Rockstar n’a rien officialisé, ce n’est qu’une probabilité fondée sur le passé, pas une certitude.'));

$add('news', 'analysis', 'Bande-annonce 2 : une production qui assume le mélodrame',
    'Le second trailer mise sur l’émotion, l’humour et le chaos — un cocktail GTA.',
    $p('La deuxième bande-annonce équilibre tendresse et folie : scènes intimes entre Jason et Lucia, gags absurdes et explosions. Le ton de la série est intact, en plus mûr.')
    . $p('Ce mélange des registres est une signature de Rockstar. Il laisse présager une campagne riche en contrastes, du road-trip romantique au braquage qui dérape.'));

$add('news', 'analysis', 'Vice City, capitale culturelle du jeu vidéo en 2026',
    'Avant même sa sortie, le jeu inspire mèmes, théories et créations sans fin.',
    $p('GTA VI est déjà un phénomène culturel. Chaque image est analysée, chaque seconde de trailer décortiquée, chaque détail transformé en mème ou en théorie.')
    . $p('Cette effervescence, c’est exactement ce qui fait vivre une communauté. ViceHub X est né pour la rassembler et l’alimenter jusqu’au lancement — et après.'));

/* ============================ GUIDES (16) ============================ */
$add('guides', null, 'Débuter dans Vice City : nos 10 conseils essentiels',
    'Tout ce qu’il faut savoir pour bien commencer dès les premières heures de jeu.',
    $p('À la sortie, beaucoup voudront tout faire en même temps. Voici nos repères pour profiter de Vice City sans se disperser.')
    . $ul([
        'Suis d’abord la trame principale pour débloquer les bases.',
        'Explore à pied de nuit : c’est là que la ville est la plus belle.',
        'Repère les planques et points de sauvegarde.',
        'Garde de l’argent de côté avant de te lancer dans un gros coup.',
        'Apprends à semer la police avant de chercher l’embrouille.',
    ])
    . $p('Le reste viendra naturellement. GTA récompense la curiosité : prends ton temps.'));

$add('guides', null, 'Bien préparer la sortie de GTA VI',
    'Espace disque, précommande, sauvegardes : la checklist avant le jour J.',
    $p('Un lancement aussi attendu se prépare. Quelques réflexes éviteront les mauvaises surprises le soir de la sortie.')
    . $ul([
        'Libère de l’espace de stockage : le jeu sera volumineux.',
        'Vérifie l’état de ta manette et de ta connexion.',
        'Méfie-toi des fausses précommandes et des arnaques.',
        'Planifie ta soirée : tu ne voudras plus la lâcher.',
    ])
    . $p('On publiera un rappel complet à l’approche de la date officielle.'));

$add('guides', null, 'Configurer sa manette comme un pro',
    'Sensibilité, gâchettes, vibrations : les réglages qui changent la conduite.',
    $p('Une bonne config de manette fait gagner en précision, surtout en conduite et en visée. Quelques pistes générales valables sur la plupart des jeux Rockstar.')
    . $ul([
        'Baisse légèrement la sensibilité de visée pour plus de stabilité.',
        'Active la visée assistée si tu débutes, désactive-la ensuite.',
        'Teste la conduite à la première et à la troisième personne.',
        'Ajuste les vibrations selon ton confort.',
    ])
    . $p('L’essentiel : prends 10 minutes pour personnaliser avant de te lancer.'));

$add('guides', null, 'Se faire de l’argent rapidement (nos théories)',
    'En attendant le vrai jeu, voici les pistes les plus crédibles pour remplir ses poches.',
    $p('Impossible de donner des méthodes exactes avant la sortie. Mais l’historique de la saga permet d’anticiper les sources de revenus probables.')
    . $ul([
        'Les braquages bien préparés resteront la meilleure source de gains.',
        'Les missions secondaires et défis offriront un revenu régulier.',
        'L’investissement (immobilier, business) pourrait rapporter gros.',
        'Évite les dépenses inutiles tant que tu débutes.',
    ])
    . $p('On mettra ce guide à jour avec des méthodes concrètes dès le lancement.'));

$add('guides', null, 'Maîtriser la conduite sous la pluie',
    'Adhérence réduite, visibilité en baisse : adapte ton pilotage à la météo.',
    $p('Si la météo dynamique tient ses promesses, la pluie transformera chaque trajet. Anticiper, c’est survivre à la prochaine course-poursuite.')
    . $ul([
        'Freine plus tôt : l’adhérence chute sur asphalte mouillé.',
        'Évite les accélérations brutales en sortie de virage.',
        'Privilégie les véhicules lourds par gros temps.',
        'Coupe à travers la ville plutôt que sur autoroute détrempée.',
    ])
    . $p('La maîtrise de la conduite fera la différence entre l’évasion et l’arrestation.'));

$add('guides', null, 'Explorer la carte sans se perdre',
    'Méthode simple pour découvrir Leonida quartier par quartier.',
    $p('Face à une carte immense, mieux vaut une exploration organisée qu’une errance. Voici comment couvrir Leonida intelligemment.')
    . $ul([
        'Découpe la carte en zones et coche-les une à une.',
        'Note les points d’intérêt et planques au fil de tes trajets.',
        'Alterne ville, côte et arrière-pays pour varier les ambiances.',
        'Utilise les hauteurs pour repérer de nouveaux lieux.',
    ])
    . $p('L’exploration est souvent là où GTA cache ses meilleurs moments.'));

$add('guides', null, 'Les meilleurs spots photo de Vice City',
    'Lever de soleil sur la plage, néons du centre, marais brumeux : nos coups de cœur.',
    $p('Vice City est un studio photo à ciel ouvert. En attendant d’y entrer, repérons les ambiances qui feront les plus belles captures.')
    . $ul([
        'La plage à l’aube, pour les dégradés roses et orangés.',
        'Le centre de nuit, pour les reflets de néons.',
        'Les Everglades au petit matin, pour la brume.',
        'Les toits, pour les panoramas urbains.',
    ])
    . $p('Partage tes clichés dans notre galerie : les meilleurs seront mis en avant.'));

$add('guides', null, 'Survivre à une course-poursuite 5 étoiles',
    'Quand tout le département est à tes trousses, la panique est ton pire ennemi.',
    $p('Atteindre le niveau maximal de recherche, c’est inévitable un jour. Garder la tête froide est la clé pour s’en sortir.')
    . $ul([
        'Quitte les grands axes : la police t’y attend.',
        'Change de véhicule pour casser la traque.',
        'Profite des tunnels et parkings pour disparaître.',
        'Ne t’arrête jamais à découvert.',
    ])
    . $p('Avec un peu de sang-froid, même cinq étoiles finissent par s’éteindre.'));

$add('guides', null, 'Comprendre le système de réputation',
    'Notoriété, respect, conséquences : comment ton comportement façonne ta partie.',
    $p('GTA récompense le style autant que l’efficacité. Un système de réputation lierait probablement tes actions à la manière dont le monde te traite.')
    . $p('Plus tu montes, plus les opportunités — et les ennuis — s’accumulent. Jouer la discrétion ou la démesure deviendra un vrai choix de style.'));

$add('guides', null, 'Jason ou Lucia : adapter son duo aux missions',
    'Chaque personnage aura ses forces. Savoir alterner sera un atout.',
    $p('Avec deux protagonistes, on peut imaginer des missions où alterner les rôles change l’approche : infiltration d’un côté, force de l’autre.')
    . $p('Notre conseil : expérimente les deux points de vue avant de choisir ta tactique. Le duo est sans doute là pour être exploité, pas subi.'));

$add('guides', null, 'Optimiser sa connexion pour le mode en ligne',
    'Latence, NAT, stabilité : prépare ton réseau pour jouer en ligne sans accroc.',
    $p('Le online récompense une connexion stable. Quelques réglages réduisent les déconnexions et la latence dès le départ.')
    . $ul([
        'Privilégie un câble Ethernet au Wi-Fi quand c’est possible.',
        'Ferme les téléchargements en arrière-plan pendant tes sessions.',
        'Redémarre ta box avant les grosses soirées de jeu.',
        'Vérifie le type de NAT dans les réglages réseau de ta console.',
    ])
    . $p('Un réseau soigné, c’est moins de frustration et plus de braquages réussis.'));

$add('guides', null, 'Personnaliser son personnage avec style',
    'Look, garde-robe, attitude : exprime ta personnalité dans Vice City.',
    $p('La personnalisation est devenue un pilier de l’expérience. On s’attend à une garde-robe étoffée et à de nombreuses options de style.')
    . $p('Soigne ton look dès le début : dans une ville aussi visuelle, l’allure compte presque autant que le talent au volant.'));

$add('guides', null, 'Les véhicules à débloquer en priorité',
    'Vitesse, maniabilité, polyvalence : quels bolides viser en premier.',
    $p('Tous les véhicules ne se valent pas. En attendant la liste complète, voici les profils à privilégier selon ton style de jeu.')
    . $ul([
        'Une sportive maniable pour les fuites en ville.',
        'Un 4x4 ou pick-up pour l’arrière-pays et les marais.',
        'Une moto pour se faufiler dans le trafic.',
        'Un bateau rapide pour le littoral.',
    ])
    . $p('Constitue-toi un garage polyvalent : Leonida varie autant que ses routes.'));

$add('guides', null, 'Réussir ses braquages en équipe',
    'Préparation, rôles, plan B : l’art du casse parfait se travaille.',
    $p('Le braquage est l’ADN de GTA. En solo comme en équipe, la préparation prime sur l’improvisation.')
    . $ul([
        'Repère les lieux avant de passer à l’action.',
        'Répartis les rôles selon les forces de chacun.',
        'Prévois toujours une voie de repli.',
        'Garde ton sang-froid si le plan dérape.',
    ])
    . $p('Le meilleur magot, c’est celui dont on repart vivant.'));

$add('guides', null, 'Météo et environnement : transforme-les en avantage',
    'Pluie, brouillard, nuit : utilise les conditions à ton profit.',
    $p('Un environnement réactif n’est pas qu’un obstacle : c’est une opportunité. Savoir lire la météo, c’est prendre l’ascendant.')
    . $ul([
        'La nuit et la brume facilitent l’infiltration.',
        'La pluie ralentit la police autant que toi : à toi d’en profiter.',
        'Le brouillard masque tes déplacements.',
        'Les marais offrent des raccourcis que peu osent emprunter.',
    ])
    . $p('Les meilleurs joueurs ne subissent pas Leonida : ils l’exploitent.'));

$add('guides', null, 'Bien gérer son temps de jeu sans s’épuiser',
    'Un open-world immense peut vite dévorer les soirées : nos conseils d’équilibre.',
    $p('Un jeu aussi vaste mérite d’être savouré sur la durée. Pas besoin de tout terminer la première semaine.')
    . $ul([
        'Fixe-toi un objectif par session plutôt que de tout enchaîner.',
        'Alterne missions principales et exploration libre.',
        'Fais des pauses : Vice City sera toujours là demain.',
        'Profite aussi de la communauté entre deux sessions.',
    ])
    . $p('Le plaisir dure plus longtemps quand on ne se précipite pas.'));

/* ============================ LEAKS (14) ============================ */
$add('leaks', 'confirmed', 'Le leak de 2022 : ce que les vidéos volées avaient révélé',
    'Retour sur la fuite historique qui avait dévoilé des séquences de développement.',
    $p('En 2022, une fuite massive avait exposé des dizaines de vidéos de développement. Rockstar avait confirmé l’authenticité de l’incident, tout en rappelant qu’il s’agissait de versions très précoces.')
    . $p('Avec le recul, ces images montraient surtout du travail en cours : mécaniques de test, animations brutes, environnements non finalisés. Juger un jeu sur ces bases serait une erreur.'));

$add('leaks', 'rumor', 'La carte s’étendrait-elle au-delà de Vice City ?',
    'Des rumeurs évoquent des zones supplémentaires ajoutées après le lancement.',
    $p('Une rumeur tenace suggère que la carte pourrait s’étendre avec le temps, au-delà de la région de départ. L’idée séduit, mais rien ne l’étaye officiellement.')
    . $p('On la classe en « rumeur » : crédible sur le principe — Rockstar fait vivre ses jeux longtemps — mais sans preuve à ce stade.'));

$add('leaks', 'rumor', 'Un mode en ligne dès le lancement : info ou intox ?',
    'Le multijoueur arriverait-il en même temps que le solo ? Les avis divergent.',
    $p('Certains affirment que le mode en ligne sera disponible dès la sortie ; d’autres parient sur un déploiement différé, comme pour GTA V.')
    . $p('Faute de confirmation, prudence. L’historique du studio penche plutôt pour un online qui suit le solo de quelques mois.'));

$add('leaks', 'rumor', 'Des avions de ligne pilotables ?',
    'La rumeur revient à chaque opus. Cette fois encore, méfiance.',
    $p('Piloter de gros porteurs fait fantasmer depuis des années. La rumeur ressurgit, mais elle relève surtout du vœu pieux des fans.')
    . $p('Rien n’indique une telle fonctionnalité. On la garde en « rumeur » par honnêteté, sans y croire plus que de raison.'));

$add('leaks', 'probable', 'Crossplay PS5 / Xbox : les indices s’accumulent',
    'Le jeu cross-plateforme est devenu un standard. GTA VI suivrait la tendance.',
    $p('Le crossplay entre PS5 et Xbox Series semble l’hypothèse la plus probable pour le futur online, tant il est devenu une norme de l’industrie.')
    . $p('Aucune confirmation officielle, mais la direction du marché et les déclarations passées du studio rendent ce scénario très crédible.'));

$add('leaks', 'probable', 'Le retour des propriétés à acheter',
    'Acquérir des biens, générer des revenus passifs : un classique qui reviendrait.',
    $p('L’achat de propriétés — appartements, commerces, planques — fait partie de l’ADN moderne de GTA. Son retour paraît très probable.')
    . $p('Au-delà du statut, ces biens pourraient générer des revenus et débloquer des missions. Une mécanique attendue par la communauté.'));

$add('leaks', 'leak', 'Un système de crime organisé dynamique',
    'Des éléments suggèrent des activités criminelles qui évoluent dans le temps.',
    $p('Plusieurs indices pointent vers un crime organisé plus vivant : territoires, rivalités et opportunités qui changeraient au fil de la partie.')
    . $p('Si la mécanique est réelle, elle rapprocherait GTA d’une simulation de pègre où chaque choix a des répercussions durables.'));

$add('leaks', 'rumor', 'Des extensions de contenu prévues après la sortie',
    'Le support post-lancement pourrait être plus ambitieux que jamais.',
    $p('La rumeur d’extensions régulières circule. Rockstar a montré, avec GTA Online, sa capacité à enrichir un jeu pendant des années.')
    . $p('Probable sur le principe, mais à confirmer. On évite de présenter un calendrier inventé comme une vérité.'));

$add('leaks', 'rumor', 'Un mode coopératif scénarisé : le rêve des fans',
    'Vivre la campagne à deux, en ligne : l’idée enflamme les discussions.',
    $p('Beaucoup espèrent une coopération scénarisée, qui collerait parfaitement au duo Jason/Lucia. L’idée est belle, mais rien ne la confirme.')
    . $p('On la garde au conditionnel : séduisante, logique, mais purement spéculative à ce jour.'));

$add('leaks', 'fake', 'Fausse date d’avril : pourquoi c’était une intox',
    'Une prétendue date avait circulé. Démontage d’une rumeur sans fondement.',
    $p('Une fausse date de sortie au printemps avait circulé, relayée sans source fiable. Elle contredisait les communications officielles et ne reposait sur rien de solide.')
    . $p('Notre règle est simple : tant que Rockstar n’annonce pas un jour précis, toute date « exacte » est à considérer comme fausse.'));

$add('leaks', 'analysis', 'Des PNJ à mémoire : mythe ou réalité ?',
    'Et si les passants se souvenaient de vous ? Analyse d’une promesse séduisante.',
    $p('L’idée de PNJ qui réagissent à votre réputation, voire se souviennent de vous, revient souvent. Quelques séquences l’ont laissé entrevoir.')
    . $p('Techniquement coûteuse, une telle mémoire serait probablement limitée à certains contextes. Spectaculaire sur le papier, à tempérer en pratique.'));

$add('leaks', 'leak', 'Le nom de code des serveurs en ligne',
    'Des bribes techniques alimentent les spéculations sur l’infrastructure online.',
    $p('Des références techniques, repérées au fil des fuites, nourrissent les théories sur l’architecture du futur mode en ligne.')
    . $p('Intéressant pour les curieux, mais sans portée concrète pour les joueurs. On le mentionne par souci d’exhaustivité, pas comme une révélation majeure.'));

$add('leaks', 'rumor', 'Une édition collector aperçue chez un revendeur',
    'Une fiche fugace aurait évoqué un coffret collector. À prendre avec des pincettes.',
    $p('Le bruit d’une édition collector circule, appuyé par une prétendue fiche revendeur. Ce genre d’indice est souvent un placeholder, pas une annonce.')
    . $p('On attend une confirmation officielle avant d’y croire. Les faux listings de précommande sont monnaie courante.'));

$add('leaks', 'analysis', 'La bodycam des policiers : un indice du trailer',
    'Un plan en caméra-épaule a relancé les théories sur la police de Leonida.',
    $p('Une séquence en vue caméra-épaule, façon bodycam, a marqué les esprits. Elle suggère une police modernisée et une mise en scène réaliste.')
    . $p('Difficile d’en tirer des conclusions définitives, mais l’intention est claire : ancrer Leonida dans une Amérique contemporaine et filmée.'));

/* ============================ BLOG (10) ============================ */
$add('blog', null, 'Pourquoi Vice City nous obsède depuis 20 ans',
    'Néons, années 80, liberté totale : anatomie d’un mythe vidéoludique.',
    $p('Vice City, ce n’est pas qu’une carte : c’est une promesse. Celle d’un terrain de jeu où tout est possible, baigné de néons et de nostalgie eighties.')
    . $p('Vingt ans après l’original, l’aura intacte de la ville en dit long. Certaines œuvres ne vieillissent pas : elles deviennent des repères.'));

$add('blog', null, 'La nostalgie des années 80 dans l’ADN de GTA',
    'Synthwave, pastel, excès : comment une décennie est devenue une esthétique.',
    $p('Les années 80 ne sont pas qu’un décor : c’est une grammaire visuelle et sonore. GTA s’en empare pour créer un imaginaire immédiatement reconnaissable.')
    . $p('Cette esthétique parle même à ceux qui n’ont pas connu l’époque. C’est la force d’un style devenu intemporel.'));

$add('blog', null, 'Ce que GTA VI doit éviter pour réussir',
    'Hype démesurée, microtransactions, promesses non tenues : les pièges à déjouer.',
    $p('L’attente est telle qu’aucun jeu ne pourra la combler à 100 %. Le vrai risque n’est pas la qualité, mais la gestion des attentes et des dérives commerciales.')
    . $p('Un online trop gourmand ou des promesses non tenues pourraient ternir le tableau. On croise les doigts pour que le studio garde le cap.'));

$add('blog', null, 'Notre bande-son de rêve pour Vice City',
    'Et si on composait la playlist idéale ? Petit exercice d’imagination musicale.',
    $p('La radio fait l’âme de GTA. On a imaginé nos stations idéales : une dédiée à la synthwave, une autre au latino, une troisième aux tubes pop de l’époque.')
    . $p('Et toi, quelle serait ta station parfaite ? Viens en débattre sur le forum : les meilleures idées finiront peut-être en article.'));

$add('blog', null, 'L’évolution des protagonistes de GTA',
    'De silhouettes muettes à personnages écrits : un genre qui a grandi.',
    $p('Les héros de GTA ont parcouru du chemin : d’avatars sans voix à des personnages complexes, faillibles et attachants. Le duo de Vice City poursuit cette montée en maturité.')
    . $p('Cette évolution raconte aussi celle du médium : le jeu vidéo s’assume comme un art narratif à part entière.'));

$add('blog', null, 'GTA VI et la culture internet',
    'Mèmes, théories, comptes à rebours : le jeu vit déjà à travers nous.',
    $p('Avant même sa sortie, GTA VI existe en ligne : dans les mèmes, les analyses image par image, les décomptes et les fan-arts. La communauté a pris les commandes.')
    . $p('Cette appropriation collective est inédite par son ampleur. ViceHub X s’inscrit dans ce mouvement : donner une maison à cette énergie.'));

$add('blog', null, 'Lettre ouverte à Rockstar',
    'Nos espoirs, nos craintes et notre confiance, en toute sincérité.',
    $p('Cher studio, on attend ce jeu comme peu d’autres. On espère une ville vivante, une histoire forte et le respect des joueurs sur la durée.')
    . $p('On vous fait confiance pour ne pas céder aux sirènes du tout-commercial. Surprenez-nous, comme vous l’avez toujours fait.'));

$add('blog', null, 'Les easter eggs qu’on espère retrouver',
    'Soucoupes, références cachées, blagues internes : la chasse au secret continue.',
    $p('Les easter eggs font partie du folklore GTA. Soucoupes mystérieuses, clins d’œil aux opus précédents, secrets bien planqués : on en redemande.')
    . $p('Sur ViceHub X, on a même caché quelques surprises. Tape un code de triche au clavier pour en découvrir une.'));

$add('blog', null, 'Comment GTA a changé le jeu vidéo',
    'Open-world, satire, liberté : l’empreinte d’une saga sur tout un médium.',
    $p('GTA a popularisé l’open-world moderne, imposé un humour grinçant et repoussé l’idée de liberté dans le jeu. Son influence dépasse largement la série.')
    . $p('Beaucoup de jeux lui doivent quelque chose. C’est l’un de ces titres qui ont déplacé les lignes pour tout le monde.'));

$add('blog', null, 'La communauté GTA, plus vivante que jamais',
    'Forums, créateurs, modeurs : un écosystème qui ne s’éteint jamais.',
    $p('Des années après chaque sortie, la communauté GTA continue de créer, débattre et jouer. C’est rare, et c’est précieux.')
    . $p('ViceHub X veut être un point de ralliement : news fiables, espaces d’échange et place pour les créateurs. Rejoins le mouvement.'));

/* ============================ TRAILERS (10) ============================ */
$add('trailers', 'analysis', 'Trailer 1 décrypté image par image',
    'On repasse la première bande-annonce au ralenti pour en extraire chaque détail.',
    $p('La première bande-annonce regorge de détails : plans de ville, ambiances, personnages entr’aperçus. Au ralenti, chaque seconde devient une mine d’indices.')
    . $p('Notre décryptage se concentre sur les éléments tangibles, sans surinterpréter. L’objectif : séparer ce qu’on voit de ce qu’on imagine.'));

$add('trailers', 'analysis', 'Trailer 2 : les détails que vous avez manqués',
    'Enseignes, véhicules, arrière-plans : la deuxième bande-annonce fourmille d’indices.',
    $p('La deuxième bande-annonce est encore plus dense. Dans les arrière-plans se cachent des enseignes, des véhicules et des lieux qui en disent long sur Leonida.')
    . $p('On a compilé les détails les plus parlants. Certains confirment des intuitions, d’autres ouvrent de nouvelles pistes de réflexion.'));

$add('trailers', 'analysis', 'Analyse audio : la musique de la bande-annonce',
    'Le choix musical d’un trailer n’est jamais anodin. Décodage du parti-pris sonore.',
    $p('La musique d’un trailer raconte une intention. Le tempo, les paroles et l’ambiance choisis donnent le ton émotionnel que le studio veut imprimer.')
    . $p('Ici, le parti-pris mêle nostalgie et tension, à l’image d’un jeu qui veut être à la fois romantique et explosif.'));

$add('trailers', 'analysis', 'Les lieux identifiés dans la bande-annonce',
    'Plages, centre-ville, marécages : on situe les décors aperçus à l’écran.',
    $p('Plusieurs décors se distinguent nettement : front de mer, artères du centre, zones humides. Chacun illustre une facette de l’État de Leonida.')
    . $p('Sans carte officielle, on reste prudents sur la géographie exacte. Mais la variété des lieux confirme l’ambition d’échelle du projet.'));

$add('trailers', 'analysis', 'Les véhicules aperçus dans le trailer',
    'Sportives, pick-ups, bateaux : tour d’horizon du parc automobile entrevu.',
    $p('Le trailer laisse entrevoir un parc varié : sportives rutilantes, pick-ups poussiéreux, deux-roues et embarcations. De quoi rouler partout dans Leonida.')
    . $p('Difficile d’identifier des modèles précis — GTA crée ses propres marques — mais la diversité annonce un garage riche.'));

$add('trailers', 'analysis', 'Lucia derrière les barreaux : ce que ça implique',
    'L’ouverture sur la prison pose les bases du récit. Décryptage narratif.',
    $p('Voir Lucia en détention dès l’ouverture n’est pas anodin. Cela ancre le duo dans une histoire de seconde chance, de risque et de liberté reconquise.')
    . $p('Ce point de départ promet une trajectoire de personnages forte, où chaque choix de la cavale aura un poids émotionnel.'));

$add('trailers', 'analysis', 'Le ton du jeu d’après les bandes-annonces',
    'Entre romance, humour noir et chaos : la tonalité se précise.',
    $p('Les trailers dessinent un ton singulier : une romance criminelle, traversée d’humour noir et de pics de chaos. C’est du GTA, en plus intime.')
    . $p('Cet équilibre est délicat à tenir sur des dizaines d’heures. S’il est réussi, il pourrait offrir l’un des récits les plus marquants de la saga.'));

$add('trailers', 'analysis', 'Comparer les deux bandes-annonces',
    'Ce que le second trailer ajoute, précise ou réoriente par rapport au premier.',
    $p('D’un trailer à l’autre, l’accent se déplace : la première posait l’ambiance, la seconde creuse les personnages et la mécanique du duo.')
    . $p('Cette progression est cohérente avec une campagne de communication maîtrisée. Chaque bande-annonce dévoile juste ce qu’il faut.'));

$add('trailers', 'analysis', 'Les clins d’œil cachés au lore GTA',
    'Marques, références, hommages : la série se cite elle-même.',
    $p('GTA aime se faire des clins d’œil. Marques fictives récurrentes, références aux opus passés, hommages discrets : les fans attentifs en repèrent partout.')
    . $p('Ces détails tissent une continuité d’univers. Ils récompensent la connaissance du lore sans jamais exclure les nouveaux venus.'));

$add('trailers', 'analysis', 'Ce que le trailer ne montre pas (et c’est voulu)',
    'Les silences d’une bande-annonce sont aussi éloquents que ses images.',
    $p('Un bon trailer cache autant qu’il montre. Les absences — gameplay détaillé, structure des missions, online — sont des choix délibérés, pas des oublis.')
    . $p('Rockstar garde ses cartes maîtresses pour plus tard. C’est aussi ça, l’art d’entretenir l’attente jusqu’au bout.'));

/* ====================== Génération du SQL ====================== */
function slugify(string $s): string
{
    $s = strtr($s, [
        'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','å'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'î'=>'i','ï'=>'i','í'=>'i','ô'=>'o','ö'=>'o','ó'=>'o','õ'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
        'ÿ'=>'y','ñ'=>'n','’'=>' ','\''=>' ','œ'=>'oe','æ'=>'ae',
    ]);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
function q(?string $s): string
{
    if ($s === null) return 'NULL';
    return "'" . str_replace("'", "''", $s) . "'";
}

$rows = [];
$seen = [];
// Dates décroissantes depuis le 23/06/2026, ~tous les 1-2 jours
$ts = strtotime('2026-06-23 18:00:00');
foreach ($A as $i => $a) {
    $slug = slugify($a['title']);
    while (isset($seen[$slug])) { $slug .= '-' . substr(md5($a['title'] . $i), 0, 4); }
    $seen[$slug] = true;
    $cat = $CAT[$a['cat']];
    $date = date('Y-m-d H:i:s', $ts);
    $ts -= (mt_rand(20, 40) * 3600); // recule de ~1 à 1.7 jour
    $rows[] = sprintf(
        '(%d,%s,%s,%s,%s,%s,%s,%s,%s)',
        $cat, q('fr'), q($a['title']), q($slug), q($a['excerpt']), q($a['body']),
        $a['badge'] ? q($a['badge']) : 'NULL', q('published'), q($date)
    );
}

$sql = "-- ViceHub X — Contenu éditorial enrichi (≈" . count($rows) . " articles FR)\n"
     . "-- Généré par scripts/gen-content.php — ne pas éditer à la main.\n"
     . "INSERT INTO articles (category_id, lang, title, slug, excerpt, body, badge, status, published_at) VALUES\n"
     . implode(",\n", $rows) . ";\n";

$out = dirname(__DIR__) . '/database/seed_content.sql';
file_put_contents($out, $sql);
echo "OK : " . count($rows) . " articles écrits dans $out\n";
