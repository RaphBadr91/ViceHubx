-- ==================================================================
--  ViceHub X — Schéma de base de données + contenu de démonstration
--  MySQL / MariaDB · utf8mb4
--  Importer :  mysql -u root -p < database/schema.sql
-- ==================================================================

CREATE DATABASE IF NOT EXISTS vicehubx
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vicehubx;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS forum_posts, forum_threads, forum_categories,
    fanarts, events, likes, notifications,
    poll_votes, poll_options, polls, article_tags, tags,
    comments, articles, categories, newsletter_subscribers, media, ads,
    affiliate_links, products, orders, seo_pages, settings, vehicles, characters, map_zones,
    trailer_analyses, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Utilisateurs (admin + contributeurs + membres) ----------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64) NOT NULL UNIQUE,
    email         VARCHAR(190) UNIQUE,
    display_name  VARCHAR(80),
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','editor','contributor','member') NOT NULL DEFAULT 'member',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Catégories ----------
CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------- Articles (news, guides, leaks) ----------
CREATE TABLE articles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    category_id  INT,
    lang         ENUM('fr','en') NOT NULL DEFAULT 'fr',
    title        VARCHAR(200) NOT NULL,
    slug         VARCHAR(220) NOT NULL UNIQUE,
    excerpt      VARCHAR(400),
    body         MEDIUMTEXT,
    badge        VARCHAR(20) DEFAULT NULL,
    image        VARCHAR(255) DEFAULT NULL,
    author_id    INT DEFAULT NULL,
    status       ENUM('draft','pending','published') NOT NULL DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_articles_cat FOREIGN KEY (category_id)
        REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_lang (lang)
) ENGINE=InnoDB;

-- ---------- Tags ----------
CREATE TABLE tags (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE article_tags (
    article_id INT NOT NULL,
    tag_id     INT NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)     REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Commentaires ----------
CREATE TABLE comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    article_id  INT NOT NULL,
    author_name VARCHAR(80) NOT NULL,
    body        TEXT NOT NULL,
    status      ENUM('pending','approved','spam') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Newsletter ----------
CREATE TABLE newsletter_subscribers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(190) NOT NULL UNIQUE,
    lang       ENUM('fr','en') NOT NULL DEFAULT 'fr',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Médias ----------
CREATE TABLE media (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    filename   VARCHAR(255) NOT NULL,
    mime       VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Publicités (emplacements) ----------
CREATE TABLE ads (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    title  VARCHAR(120) NOT NULL,
    slot   VARCHAR(60) NOT NULL,
    html   TEXT,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------- Liens d'affiliation (Deals Gaming) ----------
CREATE TABLE affiliate_links (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(160) NOT NULL,
    description VARCHAR(300),
    url         VARCHAR(400) NOT NULL,
    platform    VARCHAR(60),
    badge       VARCHAR(40),
    active      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------- Boutique (produits : Stripe + affiliation / revendeur) ----------
CREATE TABLE products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(160) NOT NULL,
    slug            VARCHAR(180) NOT NULL UNIQUE,
    description     VARCHAR(400),
    category        ENUM('poster','wallpaper','game','console','apparel','accessory','collectible') NOT NULL DEFAULT 'accessory',
    price           DECIMAL(8,2),
    currency        VARCHAR(8) NOT NULL DEFAULT 'EUR',
    image           VARCHAR(255),
    -- sale_type : 'stripe' = vendu directement (paiement Stripe) ; 'external' = lien revendeur/affilié (Amazon…)
    sale_type       ENUM('external','stripe') NOT NULL DEFAULT 'external',
    url             VARCHAR(500),            -- lien externe (si sale_type='external')
    stripe_price_id VARCHAR(120),            -- ID de prix Stripe (optionnel ; sinon price+currency)
    digital_file    VARCHAR(255),            -- fichier livré après achat (produit numérique)
    merchant        VARCHAR(60),
    badge           VARCHAR(40),
    featured        TINYINT(1) NOT NULL DEFAULT 0,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    sort            INT NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lang            ENUM('fr','en') NOT NULL DEFAULT 'fr'
) ENGINE=InnoDB;

-- ---------- Commandes (paiements Stripe) ----------
CREATE TABLE orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    stripe_session VARCHAR(190) UNIQUE,
    email          VARCHAR(190),
    amount_total   DECIMAL(10,2),
    currency       VARCHAR(8),
    status         VARCHAR(40) NOT NULL DEFAULT 'pending',
    items          TEXT,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Forum communautaire ----------
CREATE TABLE forum_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(140) NOT NULL UNIQUE,
    description VARCHAR(300),
    icon        VARCHAR(12),
    sort        INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE forum_threads (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id     INT,
    title       VARCHAR(200) NOT NULL,
    slug        VARCHAR(220) NOT NULL,
    pinned      TINYINT(1) NOT NULL DEFAULT 0,
    locked      TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_post_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_cat (category_id)
) ENGINE=InnoDB;

CREATE TABLE forum_posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    thread_id  INT NOT NULL,
    user_id    INT,
    body       TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_thread (thread_id)
) ENGINE=InnoDB;

-- ---------- Galerie de fan-arts ----------
CREATE TABLE fanarts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    title      VARCHAR(160) NOT NULL,
    image      VARCHAR(255) NOT NULL,
    status     ENUM('pending','approved') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Événements & comptes à rebours ----------
CREATE TABLE events (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(160) NOT NULL,
    description VARCHAR(300),
    icon        VARCHAR(12),
    event_date  DATETIME NOT NULL,
    link        VARCHAR(300),
    lang        ENUM('fr','en') NOT NULL DEFAULT 'fr'
) ENGINE=InnoDB;

-- ---------- Likes / réactions ----------
CREATE TABLE likes (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL,
    kind     ENUM('post','fanart') NOT NULL,
    item_id  INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_like (user_id, kind, item_id),
    INDEX idx_item (kind, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Notifications membres ----------
CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    body       VARCHAR(255) NOT NULL,
    link       VARCHAR(300),
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- SEO ----------
CREATE TABLE seo_pages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    path        VARCHAR(190) NOT NULL UNIQUE,
    title       VARCHAR(200),
    description VARCHAR(300)
) ENGINE=InnoDB;

-- ---------- Sondages ----------
CREATE TABLE polls (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(200) NOT NULL,
    lang     ENUM('fr','en') NOT NULL DEFAULT 'fr',
    active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE poll_options (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    label   VARCHAR(160) NOT NULL,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE poll_votes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    poll_id    INT NOT NULL,
    option_id  INT NOT NULL,
    ip_hash    CHAR(64),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id)   REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Réglages ----------
CREATE TABLE settings (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(80) NOT NULL UNIQUE,
    value TEXT
) ENGINE=InnoDB;

-- ---------- Véhicules ----------
CREATE TABLE vehicles (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(120) NOT NULL,
    type     VARCHAR(80),
    speed    VARCHAR(60),
    use_case VARCHAR(160),
    rarity   ENUM('common','rare','epic','legendary') NOT NULL DEFAULT 'common',
    image    VARCHAR(255),
    lang     ENUM('fr','en') NOT NULL DEFAULT 'fr'
) ENGINE=InnoDB;

-- ---------- Personnages ----------
CREATE TABLE characters (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    role        VARCHAR(120),
    description TEXT,
    theories    TEXT,
    lang        ENUM('fr','en') NOT NULL DEFAULT 'fr'
) ENGINE=InnoDB;

-- ---------- Zones de carte ----------
CREATE TABLE map_zones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    description VARCHAR(200),
    info        TEXT,
    lang        ENUM('fr','en') NOT NULL DEFAULT 'fr'
) ENGINE=InnoDB;

-- ---------- Analyses de trailer ----------
CREATE TABLE trailer_analyses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    timecode    VARCHAR(20) NOT NULL,
    description VARCHAR(300),
    clue        VARCHAR(300),
    importance  TINYINT NOT NULL DEFAULT 1,
    lang        ENUM('fr','en') NOT NULL DEFAULT 'fr'
) ENGINE=InnoDB;

-- ==================================================================
--  DONNÉES DE DÉMONSTRATION
-- ==================================================================

-- Admin : identifiant "admin" / mot de passe "vicehubx" (à changer !)
INSERT INTO users (username, email, display_name, password_hash, role) VALUES
('admin', 'admin@vicehubx.test', 'Admin', '$2y$12$KSEQS1g76KeHzzgEt6Gn8OJ0vDPkwLHTNKMo2Y9.58Vgp0VZXrYzi', 'admin'),
-- Contributeur de démo — identifiant : contributeur / mot de passe : vicehubx
('contributeur', 'contrib@vicehubx.test', 'Tommy V.', '$2y$12$KSEQS1g76KeHzzgEt6Gn8OJ0vDPkwLHTNKMo2Y9.58Vgp0VZXrYzi', 'contributor');

INSERT INTO categories (name, slug) VALUES
('News', 'news'),
('Guides', 'guides'),
('Leaks', 'leaks'),
('Trailers', 'trailers'),
('Blog', 'blog');

-- Articles News (3 FR + 3 EN)
INSERT INTO articles (category_id, lang, title, slug, excerpt, body, badge, status, published_at) VALUES
(1,'fr','Vice City revient : tout ce que l’on sait','vice-city-revient-tout-ce-que-lon-sait','Le retour tant attendu dans une Floride néon réinventée.','<p>La nouvelle génération de Vice City s’annonce comme un saut technologique majeur. Météo dynamique, foule réactive et cycle jour/nuit photoréaliste sont au programme.</p>','confirmed','published','2026-05-01 09:00:00'),
(1,'fr','Une map plus grande que jamais','une-map-plus-grande-que-jamais','Des marécages aux gratte-ciels, l’échelle impressionne.','<p>Selon plusieurs sources concordantes, la carte couvrirait la ville et son arrière-pays, avec des zones rurales jouables dès le lancement.</p>','probable','published','2026-05-03 10:30:00'),
(1,'fr','Le moteur RAGE pousse les curseurs','le-moteur-rage-pousse-les-curseurs','Physique des véhicules et destruction au cœur de l’expérience.','<p>La démo technique met en avant une gestion de la physique inédite : déformation des carrosseries, eau réaliste et IA piétonne crédible.</p>','analysis','published','2026-05-04 14:00:00'),
(1,'en','Vice City Is Back: Everything We Know','vice-city-is-back-everything-we-know','The long-awaited return to a reimagined neon Florida.','<p>The next generation of Vice City promises a major technological leap, with dynamic weather, reactive crowds and a photorealistic day/night cycle.</p>','confirmed','published','2026-05-01 09:00:00'),
(1,'en','A Bigger Map Than Ever','a-bigger-map-than-ever','From swamps to skyscrapers, the scale is staggering.','<p>Multiple sources agree the map will cover the city and its hinterland, with rural zones playable from launch day.</p>','probable','published','2026-05-03 10:30:00'),
(1,'en','The RAGE Engine Raises The Bar','the-rage-engine-raises-the-bar','Vehicle physics and destruction at the core of the experience.','<p>The tech demo highlights unprecedented physics handling: body deformation, realistic water and believable pedestrian AI.</p>','analysis','published','2026-05-04 14:00:00');

-- Guides (3 FR)
INSERT INTO articles (category_id, lang, title, slug, excerpt, body, status, published_at) VALUES
(2,'fr','Guide : bien démarrer dans Vice City','guide-bien-demarrer-dans-vice-city','Nos conseils pour vos premières heures de jeu.','<p>Priorisez les missions d’histoire pour débloquer rapidement la carte, puis investissez dans un véhicule fiable.</p>','published','2026-05-06 09:00:00'),
(2,'fr','Guide : gagner de l’argent rapidement','guide-gagner-de-largent-rapidement','Les méthodes les plus efficaces, sans triche.','<p>Braquages, missions secondaires et investissements immobiliers : voici la rotation optimale pour remplir vos poches.</p>','published','2026-05-07 09:00:00'),
(2,'fr','Guide : maîtriser la conduite','guide-maitriser-la-conduite','Domptez la nouvelle physique des véhicules.','<p>Le freinage moteur et le transfert de masse changent tout. Entraînez-vous sur l’autoroute côtière avant les courses.</p>','published','2026-05-08 09:00:00');

-- Articles Véhicules (fiches détaillées, SEO + photos IA)
INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, status, published_at) VALUES
(2,'fr','Infernus Neo : la supercar reine de Vice City','infernus-neo-supercar-vice-city','Tout sur l’Infernus Neo : vitesse, maniabilité et meilleurs usages dans GTA VI.','<p>L’<strong>Infernus Neo</strong> s’impose comme la supercar de référence de Vice City. Avec une vitesse de pointe estimée à <strong>340 km/h</strong>, elle domine les courses urbaines et les fuites à haut risque.</p><h2>Points forts</h2><ul><li>Accélération foudroyante, idéale pour semer la police.</li><li>Tenue de route précise sur asphalte mouillé.</li><li>Silhouette néon emblématique, parfaite pour le style.</li></ul><h2>Nos conseils</h2><p>Réservez l’Infernus Neo aux trajets sur autoroute et aux courses : son châssis bas la rend moins à l’aise hors des routes. Travaillez le freinage moteur avant chaque virage serré.</p>','/public/assets/img/scenes/veh-supercar.png','published','2026-05-12 09:00:00'),
(2,'fr','Sabre Coastline : la muscle car polyvalente','sabre-coastline-muscle-car','La Sabre Coastline, une muscle car parfaite pour la ville et les virées côtières.','<p>La <strong>Sabre Coastline</strong> est la muscle car la plus polyvalente du jeu. À <strong>255 km/h</strong>, elle combine couple généreux et look rétro pour briller en ville comme sur le front de mer.</p><h2>Pourquoi la choisir</h2><ul><li>Excellent compromis vitesse / contrôle.</li><li>Drifts faciles à déclencher pour le show.</li><li>Coffre et robustesse appréciables en mission.</li></ul><h2>Nos conseils</h2><p>Parfaite pour débuter : la Sabre pardonne les erreurs de pilotage. Idéale pour les missions de transport et les balades golden hour.</p>','/public/assets/img/scenes/veh-muscle.png','published','2026-05-13 09:00:00'),
(2,'fr','Marina Cruiser : explorer les côtes de Leonida','marina-cruiser-bateau-leonida','Le Marina Cruiser, le bateau idéal pour explorer marinas, îles et planques côtières.','<p>Le <strong>Marina Cruiser</strong> ouvre tout un pan de la carte : les zones côtières. À <strong>120 km/h</strong> sur l’eau, il relie marinas, îles barrières et planques inaccessibles par la route.</p><h2>Atouts</h2><ul><li>Accès aux secrets maritimes et collectibles cachés.</li><li>Stabilité par mer agitée.</li><li>Échappatoire idéale lors des poursuites.</li></ul><h2>Nos conseils</h2><p>Gardez toujours un Marina Cruiser amarré près de votre planque côtière : c’est la meilleure carte de sortie quand la police bloque les ponts.</p>','/public/assets/img/scenes/veh-boat.png','published','2026-05-14 09:00:00'),
(2,'fr','Swamp Runner : dompter les marécages','swamp-runner-4x4-marecages','Le Swamp Runner, le 4x4 taillé pour les marais et les terrains boueux de GTA VI.','<p>Le <strong>Swamp Runner</strong> est le tout-terrain à privilégier dès que le bitume disparaît. Ses gros pneus et sa garde au sol surélevée avalent boue et marais à <strong>165 km/h</strong>.</p><h2>Points forts</h2><ul><li>Franchissement exceptionnel hors route.</li><li>Idéal pour les courses tout-terrain et les raccourcis.</li><li>Résistant aux chocs et aux terrains accidentés.</li></ul><h2>Nos conseils</h2><p>Sur route, il manque de vitesse de pointe : réservez-le aux Everglades et aux missions rurales. Un must pour explorer l’arrière-pays.</p>','/public/assets/img/scenes/veh-swamp.png','published','2026-05-15 09:00:00'),
(2,'fr','Skyline VTOL : la liberté du ciel','skyline-vtol-aeronef','Le Skyline VTOL, l’aéronef ultime pour traverser Leonida à 420 km/h.','<p>Le <strong>Skyline VTOL</strong> est l’aéronef le plus prisé de Vice City. À <strong>420 km/h</strong>, il transforme la carte : décollage vertical, vol stationnaire et déplacements éclair d’un bout à l’autre de Leonida.</p><h2>Pourquoi il change tout</h2><ul><li>Voyage rapide longue distance sans temps de chargement.</li><li>Décollage et atterrissage sur les toits.</li><li>Vue imprenable pour repérer collectibles et planques.</li></ul><h2>Nos conseils</h2><p>Pièce rare et coûteuse : sécurisez un héliport privé. En vol, attention aux zones aériennes surveillées qui font grimper le niveau de recherche.</p>','/public/assets/img/scenes/veh-vtol.png','published','2026-05-16 09:00:00');

-- Leaks (3, avec badges de fiabilité)
INSERT INTO articles (category_id, lang, title, slug, excerpt, body, badge, status, published_at) VALUES
(3,'fr','Leak : un mode coopératif au lancement ?','leak-mode-cooperatif-au-lancement','Une source interne évoque une campagne à deux.','<p>L’information reste à confirmer mais plusieurs indices pointent vers un mode coop scénarisé.</p>','rumor','published','2026-05-09 12:00:00'),
(3,'fr','Leak : la bande-son partiellement dévoilée','leak-bande-son-partiellement-devoilee','Des titres synthwave au programme.','<p>Une playlist provisoire aurait fuité, mêlant synthwave moderne et classiques des années 80.</p>','leak','published','2026-05-10 12:00:00'),
(3,'fr','Leak : ce visuel serait un fake','leak-visuel-serait-un-fake','Attention aux fausses captures qui circulent.','<p>Après analyse, l’image partagée massivement présente des incohérences d’éclairage : très probablement un montage.</p>','fake','published','2026-05-11 12:00:00');

-- Trailer Lab
INSERT INTO trailer_analyses (timecode, description, clue, importance, lang) VALUES
('00:12','Plan d’ouverture sur la plage au coucher du soleil','Reflet d’un panneau « Leaf Links » dans une vitrine',3,'fr'),
('00:47','Course-poursuite sur l’autoroute côtière','Plaque d’immatriculation « VC-2026 » visible 2 frames',4,'fr'),
('01:23','Intérieur d’un nightclub néon','Logo d’une station radio inédite au mur',2,'fr');

-- Véhicules (5)
INSERT INTO vehicles (name, type, speed, use_case, rarity, image, lang) VALUES
('Infernus Neo','Supercar','340 km/h','Courses et fuites rapides','legendary','/public/assets/img/scenes/veh-supercar.png','fr'),
('Sabre Coastline','Muscle car','255 km/h','Polyvalente, idéale en ville','rare','/public/assets/img/scenes/veh-muscle.png','fr'),
('Marina Cruiser','Bateau','120 km/h','Exploration des zones côtières','epic','/public/assets/img/scenes/veh-boat.png','fr'),
('Swamp Runner','4x4','165 km/h','Terrains boueux et marécages','common','/public/assets/img/scenes/veh-swamp.png','fr'),
('Skyline VTOL','Aéronef','420 km/h','Déplacements rapides longue distance','legendary','/public/assets/img/scenes/veh-vtol.png','fr');

-- Blog (6 articles SEO, catégorie 5 = blog)
INSERT INTO articles (category_id, lang, title, slug, excerpt, body, image, author_id, status, published_at) VALUES
(5,'fr','GTA VI : date de sortie, plateformes et tout ce que l’on sait','gta-vi-date-de-sortie-plateformes','Date, consoles, prix : le récap complet avant la sortie de Grand Theft Auto VI.','<p><strong>Grand Theft Auto VI</strong> est attendu pour le <strong>19 novembre 2026</strong>. Le jeu sortira d’abord sur consoles next-gen, une version PC étant fortement pressentie quelques mois plus tard.</p><h2>Ce que l’on sait</h2><ul><li>Retour à <strong>Vice City</strong> et dans l’État fictif de Leonida.</li><li>Deux protagonistes jouables : <strong>Lucia</strong> et <strong>Jason</strong>.</li><li>Une carte gigantesque mêlant ville, plages et marécages.</li></ul><p>On fait le point régulièrement sur ViceHub X : gardez un œil sur le compte à rebours en page d’accueil. 🕒</p>','/public/assets/img/scenes/aerial.png',1,'published','2026-05-20 09:00:00'),
(5,'fr','Vice City & Leonida : le guide des quartiers','vice-city-leonida-guide-quartiers','Plages, centre-ville, marina, marécages : tour d’horizon de la carte de GTA VI.','<p>La carte de <strong>Leonida</strong> s’annonce comme la plus vaste de la saga. Tour d’horizon des zones emblématiques que vous arpenterez dans <strong>GTA VI</strong>.</p><h2>Les zones clés</h2><ul><li><strong>Beachfront</strong> : plages, néons et vie nocturne.</li><li><strong>Downtown</strong> : gratte-ciels et missions à enjeux.</li><li><strong>Marina</strong> : yachts, planques et virées en mer.</li><li><strong>Everglades</strong> : marécages sauvages et courses tout-terrain.</li></ul><p>Explorez la carte interactive directement sur notre page d’accueil. 🗺️</p>','/public/assets/img/scenes/marina.png',1,'published','2026-05-21 09:00:00'),
(5,'fr','Lucia & Jason : ce que l’on sait des protagonistes','lucia-jason-protagonistes-gta6','Pour la première fois, GTA met en scène un duo. Découvrez Lucia et Jason.','<p>Pour la première fois dans la saga, <strong>GTA VI</strong> propose un <strong>duo de protagonistes</strong> : Lucia et Jason. Une dynamique inédite qui rappelle les grands duos du cinéma.</p><p><strong>Lucia</strong>, ancienne détenue, cherche un nouveau départ. <strong>Jason</strong>, débrouillard et impulsif, l’accompagne dans une spirale d’ambition et de danger. Leur relation sera au cœur du scénario.</p><p>De nombreuses théories circulent déjà : on les décrypte dans notre Leaks Lab. 🕵️</p>','/public/assets/img/scenes/night.png',1,'published','2026-05-22 09:00:00'),
(5,'fr','Top 10 des fonctionnalités qu’on espère dans GTA VI','top-10-fonctionnalites-gta6','Météo dynamique, intérieurs, IA piétonne… notre liste de souhaits pour GTA VI.','<p>À l’approche de la sortie, voici les <strong>10 fonctionnalités</strong> que la communauté espère le plus voir dans <strong>GTA VI</strong>.</p><ol><li>Météo dynamique et ouragans.</li><li>Intérieurs accessibles sans chargement.</li><li>IA piétonne crédible et réactive.</li><li>Économie et propriétés à gérer.</li><li>Personnalisation poussée des véhicules.</li><li>Sous-marins et exploration maritime.</li><li>Mode photo avancé.</li><li>Radios et bande-son cultes.</li><li>Activités annexes variées.</li><li>Un mode en ligne ambitieux.</li></ol><p>Et vous, qu’attendez-vous le plus ? Dites-le sur le forum ! 💬</p>','/public/assets/img/scenes/downtown.png',1,'published','2026-05-23 09:00:00'),
(5,'fr','GTA VI vs GTA V : tout ce qui change','gta-vi-vs-gta-v-ce-qui-change','Graphismes, carte, gameplay : les différences majeures entre GTA V et GTA VI.','<p>Plus de dix ans séparent <strong>GTA V</strong> de <strong>GTA VI</strong>. Voici les évolutions majeures attendues.</p><h2>Les grandes différences</h2><ul><li><strong>Moteur</strong> : un RAGE nouvelle génération, physique et destruction repensées.</li><li><strong>Carte</strong> : Leonida, plus vaste et plus vivante que Los Santos.</li><li><strong>Narration</strong> : un duo jouable plutôt qu’un trio.</li><li><strong>Immersion</strong> : foule réactive, météo dynamique, cycle jour/nuit photoréaliste.</li></ul><p>Le bond technologique s’annonce spectaculaire. 🚀</p>','/public/assets/img/scenes/beach-cruise.png',1,'published','2026-05-24 09:00:00'),
(5,'fr','Bien préparer la sortie de GTA VI','bien-preparer-la-sortie-gta6','Console, précommande, espace de stockage : la checklist avant le jour J.','<p>Le <strong>19 novembre 2026</strong> approche. Voici notre checklist pour être prêt le jour du lancement de <strong>GTA VI</strong>.</p><h2>La checklist</h2><ul><li>Choisir sa plateforme (console next-gen, PC plus tard).</li><li>Précommander pour éviter les ruptures.</li><li>Libérer de l’espace de stockage (le jeu sera volumineux).</li><li>Vérifier sa connexion pour le téléchargement du day one.</li></ul><p>Retrouvez nos sélections (jeu, console, goodies) dans la <a href="/pages/shop.php">Boutique</a>. 🛍️</p>','/public/assets/img/scenes/sunset-cruise.png',1,'published','2026-05-25 09:00:00');

-- Personnages (4)
INSERT INTO characters (name, role, description, theories, lang) VALUES
('Lucia','Protagoniste','Ancienne détenue cherchant un nouveau départ à Vice City.','Pourrait être liée à un cartel local selon plusieurs analyses.','fr'),
('Jason','Protagoniste','Partenaire de Lucia, débrouillard et impulsif.','Son passé militaire reste un mystère savamment entretenu.','fr'),
('Le Maire','Antagoniste','Figure politique corrompue contrôlant la ville.','Certains pensent qu’il tire les ficelles de la pègre.','fr'),
('DJ Solaris','Secondaire','Voix culte de la radio Vice FM.','Référence possible à un personnage des opus précédents.','fr');

-- Zones de carte (3+)
INSERT INTO map_zones (name, description, info, lang) VALUES
('Beachfront','Plage et front de mer','Boutiques, casinos et activités nautiques. Point de départ idéal.','fr'),
('Downtown','Centre-ville futuriste','Gratte-ciels, sièges sociaux et missions à enjeux.','fr'),
('Marina','Port de plaisance','Accès aux bateaux et planques côtières.','fr'),
('Nightclub District','Quartier des clubs','Vie nocturne, contacts et opportunités douteuses.','fr'),
('Swamp Area','Marécages','Zone sauvage propice aux courses tout-terrain et aux secrets.','fr');

-- Deals Gaming (3) — liens d’affiliation configurables
INSERT INTO affiliate_links (title, description, url, platform, badge, active) VALUES
('Manette next-gen — édition Vice','Précommande recommandée pour les fans.','https://example.com/deal/controller','Amazon','-15%',1),
('Casque audio immersif','Pour profiter de la bande-son synthwave.','https://example.com/deal/headset','PS5','Top vente',1),
('Abonnement VPN gaming','Réduisez le ping et protégez votre connexion.','https://example.com/deal/vpn','PC','Partenaire',1);

-- Boutique — catalogue de démonstration.
--  • sale_type 'stripe'   = produits vendus en direct (paiement Stripe) — affiches IA, goodies.
--  • sale_type 'external' = liens revendeur / affilié (Amazon…) — jeu, console, manette.
-- ⚠️ Pour Stripe : renseignez vos clés dans l'admin (Réglages) ; pour l'affiliation : remplacez les URL par vos liens.
INSERT INTO products (name, slug, description, category, price, currency, image, sale_type, url, stripe_price_id, merchant, badge, featured, active, sort, lang) VALUES
('GTA VI — Édition Standard (PS5 / Xbox)', 'gta-vi-edition-standard', 'Précommandez Grand Theft Auto VI au meilleur prix. Livraison suivie via notre partenaire.', 'game', 69.99, 'EUR', '/public/assets/img/shop/game-case.png', 'external', 'https://www.amazon.fr/s?k=GTA+VI&tag=vicehubx-21', NULL, 'Amazon', 'Précommande', 1, 1, 10, 'fr'),
('Console Next-Gen — Pack Édition', 'console-next-gen-pack', 'La console nouvelle génération idéale pour profiter de GTA VI en 4K. Pack manette incluse.', 'console', 549.99, 'EUR', '/public/assets/img/shop/console.png', 'external', 'https://www.amazon.fr/s?k=PlayStation+5&tag=vicehubx-21', NULL, 'Amazon', 'Best-seller', 1, 1, 20, 'fr'),
('Manette sans fil Next-Gen', 'manette-sans-fil-next-gen', 'Manette ergonomique compatible next-gen pour des sessions Vice City sans fil.', 'accessory', 74.99, 'EUR', '/public/assets/img/shop/console.png', 'external', 'https://www.amazon.fr/s?k=manette+sans+fil&tag=vicehubx-21', NULL, 'Amazon', NULL, 0, 1, 30, 'fr'),
('Affiche Néon Skyline — Édition IA', 'affiche-neon-skyline', 'Affiche premium générée par IA : skyline néon synthwave. Impression haute qualité.', 'poster', 24.90, 'EUR', '/public/assets/img/shop/poster-synthwave.png', 'stripe', NULL, NULL, 'ViceHub Store', 'Édition IA', 1, 1, 40, 'fr'),
('Affiche Palmiers Sunset', 'affiche-palmiers-sunset', 'Affiche minimaliste palmiers et coucher de soleil. Idéale pour un setup gaming.', 'poster', 24.90, 'EUR', '/public/assets/img/shop/poster-palmsunset.png', 'stripe', NULL, NULL, 'ViceHub Store', NULL, 0, 1, 50, 'fr'),
('Affiche Supercar Néon', 'affiche-supercar-neon', 'Affiche supercar sur asphalte mouillé, ambiance néon magenta. Édition IA.', 'poster', 24.90, 'EUR', '/public/assets/img/shop/poster-supercar.png', 'stripe', NULL, NULL, 'ViceHub Store', 'Populaire', 0, 1, 60, 'fr'),
('Affiche Rétro Vice City', 'affiche-retro-vice-city', 'Affiche style poster de voyage rétro, skyline tropicale au crépuscule.', 'poster', 24.90, 'EUR', '/public/assets/img/shop/poster-skyline.png', 'stripe', NULL, NULL, 'ViceHub Store', NULL, 0, 1, 70, 'fr'),
('Affiche Flamant Néon', 'affiche-flamant-neon', 'Affiche flamant rose néon sur fond tropical. Touche déco fun et vibrante.', 'poster', 19.90, 'EUR', '/public/assets/img/shop/poster-flamingo.png', 'stripe', NULL, NULL, 'ViceHub Store', NULL, 0, 1, 80, 'fr'),
('T-shirt « Vice Vibes »', 't-shirt-vice-vibes', 'T-shirt premium 100% coton, graphique palmier néon. Coupe unisexe.', 'apparel', 24.90, 'EUR', '/public/assets/img/shop/tshirt.png', 'stripe', NULL, NULL, 'ViceHub Store', 'Nouveau', 1, 1, 90, 'fr'),
('Hoodie « Neon City »', 'hoodie-neon-city', 'Sweat à capuche confortable, skyline néon brodé. Parfait pour les soirées gaming.', 'apparel', 44.90, 'EUR', '/public/assets/img/shop/hoodie.png', 'stripe', NULL, NULL, 'ViceHub Store', NULL, 0, 1, 100, 'fr'),
('Casquette « Palm »', 'casquette-palm', 'Casquette snapback noire, palmier néon brodé. Style streetwear Vice City.', 'apparel', 19.90, 'EUR', '/public/assets/img/shop/cap.png', 'stripe', NULL, NULL, 'ViceHub Store', NULL, 0, 1, 110, 'fr'),
('Mug « Synthwave »', 'mug-synthwave', 'Mug céramique 350 ml, design synthwave. Pour vos cafés avant une virée nocturne.', 'accessory', 14.90, 'EUR', '/public/assets/img/shop/mug.png', 'stripe', NULL, NULL, 'ViceHub Store', NULL, 0, 1, 120, 'fr'),
('Tapis de souris XL « Neon City »', 'tapis-souris-xl-neon-city', 'Grand tapis de souris gaming (900×400 mm), surface néon, base antidérapante.', 'accessory', 29.90, 'EUR', '/public/assets/img/shop/mousepad.png', 'stripe', NULL, NULL, 'ViceHub Store', 'Gaming', 0, 1, 130, 'fr');

-- Wallpapers numériques (5 €) — aperçu filigrané, fichier HD livré après paiement.
INSERT INTO products (name, slug, description, category, price, currency, image, sale_type, digital_file, merchant, badge, featured, active, sort, lang) VALUES
('Wallpaper « Vice City Skyline »', 'wallpaper-vice-city-skyline', 'Fond d''écran HD néon skyline. Fichier PNG livré immédiatement après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-skyline', 'stripe', 'storage/wallpapers/wall-skyline.png', 'ViceHub Store', 'Téléchargement', 1, 1, 31, 'fr'),
('Wallpaper « Sunset Beach »', 'wallpaper-sunset-beach', 'Fond d''écran HD plage au coucher de soleil. PNG livré après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-beach', 'stripe', 'storage/wallpapers/wall-beach.png', 'ViceHub Store', 'Téléchargement', 0, 1, 32, 'fr'),
('Wallpaper « Neon Supercar »', 'wallpaper-neon-supercar', 'Fond d''écran HD supercar néon sous la pluie. PNG livré après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-supercar', 'stripe', 'storage/wallpapers/wall-supercar.png', 'ViceHub Store', 'Téléchargement', 0, 1, 33, 'fr'),
('Wallpaper « Leonida Aerial »', 'wallpaper-leonida-aerial', 'Fond d''écran HD vue aérienne de Leonida. PNG livré après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-aerial', 'stripe', 'storage/wallpapers/wall-aerial.png', 'ViceHub Store', 'Téléchargement', 0, 1, 34, 'fr'),
('Wallpaper « Synthwave Dream »', 'wallpaper-synthwave-dream', 'Fond d''écran HD synthwave rétro. PNG livré après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-synthwave', 'stripe', 'storage/wallpapers/wall-synthwave.png', 'ViceHub Store', 'Téléchargement', 1, 1, 35, 'fr'),
('Wallpaper « Nightlife »', 'wallpaper-nightlife', 'Fond d''écran HD quartier des clubs néon. PNG livré après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-nightlife', 'stripe', 'storage/wallpapers/wall-nightlife.png', 'ViceHub Store', 'Téléchargement', 0, 1, 36, 'fr'),
('Wallpaper « Marina Lights »', 'wallpaper-marina-lights', 'Fond d''écran HD marina au crépuscule. PNG livré après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-marina', 'stripe', 'storage/wallpapers/wall-marina.png', 'ViceHub Store', 'Téléchargement', 0, 1, 37, 'fr'),
('Wallpaper « Neon Flamingo »', 'wallpaper-neon-flamingo', 'Fond d''écran HD flamant néon. PNG livré après paiement.', 'wallpaper', 5.00, 'EUR', '/preview.php?p=wall-flamingo', 'stripe', 'storage/wallpapers/wall-flamingo.png', 'ViceHub Store', 'Téléchargement', 0, 1, 38, 'fr');

-- Sondage communautaire
INSERT INTO polls (question, lang, active) VALUES
('Quel protagoniste vous attire le plus ?', 'fr', 1);
INSERT INTO poll_options (poll_id, label) VALUES
(1, 'Lucia'), (1, 'Jason'), (1, 'Les deux !');

-- Forum : catégories + sujet de démonstration
INSERT INTO forum_categories (name, slug, description, icon, sort) VALUES
('Discussions générales', 'general', 'Parlez de tout autour de GTA VI et de Vice City.', '💬', 10),
('Théories & Leaks', 'theories-leaks', 'Débattez des rumeurs, indices et théories de la communauté.', '🕵️', 20),
('Guides & Astuces', 'guides-astuces', 'Partagez vos conseils, builds et bons plans.', '🧭', 30),
('Véhicules & Customs', 'vehicules-customs', 'Vos voitures préférées, réglages et tunings.', '🏎️', 40),
('Hors-sujet', 'hors-sujet', 'Tout le reste : jeux, musique, vie du serveur.', '🎲', 50);

INSERT INTO forum_threads (id, category_id, user_id, title, slug, pinned, created_at, last_post_at) VALUES
(1, 1, 1, 'Bienvenue sur le forum ViceHub X ! 🌴', 'bienvenue-sur-le-forum-vicehubx', 1, NOW(), NOW());
INSERT INTO forum_posts (thread_id, user_id, body) VALUES
(1, 1, 'Bienvenue dans la communauté ViceHub X ! Présentez-vous, partagez vos théories sur GTA VI et restez courtois. Bon jeu à Vice City. 🩷'),
(1, 2, 'Hâte d’y être ! Le compte à rebours tourne, on se retrouve à Leonida le 19 novembre 2026. 🔥');

-- Réglages & SEO
INSERT INTO settings (`key`, value) VALUES
('site_tagline_fr', 'Entrez dans la nouvelle génération de Vice City.'),
('site_tagline_en', 'Enter The Next Generation Of Vice City.'),
('adsense_client', ''),
('adsense_slot', ''),
('hero_video', ''),
('trailer_url', ''),
('map_url', 'https://map.stateofleonida.net/?map=vi&lat=3904.00&lng=-10452.00'),
('release_date', '2026-11-19T00:00:00'),
('stripe_publishable_key', ''),
('stripe_secret_key', ''),
('stripe_webhook_secret', ''),
('shop_currency', 'EUR'),
('shop_currency_en', 'USD');

INSERT INTO seo_pages (path, title, description) VALUES
('/index.php', 'ViceHub X — GTA6 News', 'News, guides, leaks et analyses de trailers GTA VI.'),
('/pages/news.php', 'News GTA6 — ViceHub X', 'Toute l’actualité GTA VI en continu.'),
('/pages/shop.php', 'Boutique GTA6 — ViceHub X', 'Affiches IA, jeux, consoles et goodies GTA VI. Boutique officielle ViceHub X.'),
('/pages/forum.php', 'Forum GTA6 — ViceHub X', 'Forum communautaire GTA VI : discussions, théories, guides et entraide entre fans.');

-- Images réelles (visuels générés par IA, récupérés via scripts/make-hero-video.sh)
UPDATE articles SET image='/public/assets/img/scenes/aerial.png'       WHERE slug IN ('vice-city-revient-tout-ce-que-lon-sait','vice-city-is-back-everything-we-know');
UPDATE articles SET image='/public/assets/img/scenes/marina.png'       WHERE slug IN ('une-map-plus-grande-que-jamais','a-bigger-map-than-ever');
UPDATE articles SET image='/public/assets/img/scenes/night.png'        WHERE slug IN ('le-moteur-rage-pousse-les-curseurs','the-rage-engine-raises-the-bar');
UPDATE articles SET image='/public/assets/img/scenes/beach-cruise.png' WHERE slug='guide-bien-demarrer-dans-vice-city';
UPDATE articles SET image='/public/assets/img/scenes/police.png'       WHERE slug='guide-gagner-de-largent-rapidement';
UPDATE articles SET image='/public/assets/img/scenes/plane.png'        WHERE slug='guide-maitriser-la-conduite';
UPDATE articles SET image='/public/assets/img/scenes/downtown.png'     WHERE slug='leak-mode-cooperatif-au-lancement';
UPDATE articles SET image='/public/assets/img/scenes/nightlife.png'    WHERE slug='leak-bande-son-partiellement-devoilee';
UPDATE articles SET image='/public/assets/img/scenes/graffiti.png'     WHERE slug='leak-visuel-serait-un-fake';

-- ====== Forum : 50 personas IA + sujets de démarrage ======
INSERT INTO users (username, email, display_name, password_hash, role) VALUES
('neon_rider84','neon_rider84@fans.vicehubx.test','NeonRider84','!','member'),
('lucia_stan','lucia_stan@fans.vicehubx.test','LuciaStan','!','member'),
('jasonmains','jasonmains@fans.vicehubx.test','JasonMains','!','member'),
('vice_veteran','vice_veteran@fans.vicehubx.test','ViceVeteran','!','member'),
('mapnerd_leo','mapnerd_leo@fans.vicehubx.test','MapNerdLeo','!','member'),
('heistqueen','heistqueen@fans.vicehubx.test','HeistQueen','!','member'),
('carlover_v8','carlover_v8@fans.vicehubx.test','CarLoverV8','!','member'),
('leak_skeptic','leak_skeptic@fans.vicehubx.test','LeakSkeptic','!','member'),
('snapmatic_sara','snapmatic_sara@fans.vicehubx.test','SnapmaticSara','!','member'),
('speedrun_max','speedrun_max@fans.vicehubx.test','SpeedrunMax','!','member'),
('rp_storyteller','rp_storyteller@fans.vicehubx.test','RPStoryteller','!','member'),
('hype_machine','hype_machine@fans.vicehubx.test','HypeMachine','!','member'),
('theory_owl','theory_owl@fans.vicehubx.test','TheoryOwl','!','member'),
('console_andy','console_andy@fans.vicehubx.test','ConsoleAndy','!','member'),
('pc_masterkey','pc_masterkey@fans.vicehubx.test','PCMasterKey','!','member'),
('meme_lord_t','meme_lord_t@fans.vicehubx.test','MemeLordT','!','member'),
('radio_fm_fan','radio_fm_fan@fans.vicehubx.test','RadioFMfan','!','member'),
('lore_keeper','lore_keeper@fans.vicehubx.test','LoreKeeper','!','member'),
('noob_first','noob_first@fans.vicehubx.test','NoobFirst','!','member'),
('miami_vibes','miami_vibes@fans.vicehubx.test','MiamiVibes','!','member'),
('cop_chaser','cop_chaser@fans.vicehubx.test','CopChaser','!','member'),
('stealth_kira','stealth_kira@fans.vicehubx.test','StealthKira','!','member'),
('economy_geek','economy_geek@fans.vicehubx.test','EconomyGeek','!','member'),
('trailer_frame','trailer_frame@fans.vicehubx.test','TrailerFrame','!','member'),
('biker_road','biker_road@fans.vicehubx.test','BikerRoad','!','member'),
('boat_captain','boat_captain@fans.vicehubx.test','BoatCaptain','!','member'),
('pilot_sky','pilot_sky@fans.vicehubx.test','PilotSky','!','member'),
('collector_99','collector_99@fans.vicehubx.test','Collector99','!','member'),
('streamer_vix','streamer_vix@fans.vicehubx.test','StreamerVix','!','member'),
('old_school_cj','old_school_cj@fans.vicehubx.test','OldSchoolCJ','!','member'),
('detail_freak','detail_freak@fans.vicehubx.test','DetailFreak','!','member'),
('casual_dad','casual_dad@fans.vicehubx.test','CasualDad','!','member'),
('edgy_gamer','edgy_gamer@fans.vicehubx.test','EdgyGamer','!','member'),
('fashion_vc','fashion_vc@fans.vicehubx.test','FashionVC','!','member'),
('music_digger','music_digger@fans.vicehubx.test','MusicDigger','!','member'),
('conspiracy_neo','conspiracy_neo@fans.vicehubx.test','ConspiracyNeo','!','member'),
('budget_gamer','budget_gamer@fans.vicehubx.test','BudgetGamer','!','member'),
('weather_watch','weather_watch@fans.vicehubx.test','WeatherWatch','!','member'),
('gunsmith_ray','gunsmith_ray@fans.vicehubx.test','GunsmithRay','!','member'),
('explorer_ann','explorer_ann@fans.vicehubx.test','ExplorerAnn','!','member'),
('nostalgia_3','nostalgia_3@fans.vicehubx.test','Nostalgia3','!','member'),
('competitive_z','competitive_z@fans.vicehubx.test','CompetitiveZ','!','member'),
('chill_surfer','chill_surfer@fans.vicehubx.test','ChillSurfer','!','member'),
('data_miner_q','data_miner_q@fans.vicehubx.test','DataMinerQ','!','member'),
('roleplay_mayor','roleplay_mayor@fans.vicehubx.test','RoleplayMayor','!','member'),
('hype_skeptic','hype_skeptic@fans.vicehubx.test','HypeSkeptic','!','member'),
('retro_wave_88','retro_wave_88@fans.vicehubx.test','RetroWave88','!','member'),
('family_lucia','family_lucia@fans.vicehubx.test','FamilyLucia','!','member'),
('grinder_pro','grinder_pro@fans.vicehubx.test','GrinderPro','!','member'),
('newsdrop_lia','newsdrop_lia@fans.vicehubx.test','NewsDropLia','!','member');

INSERT INTO forum_threads (id, category_id, user_id, title, slug, pinned, created_at, last_post_at) VALUES
(2,1,14,'J-147 avant GTA VI : qui est aussi hypé que moi ?? 🔥','j-147-avant-gta-vi-qui-est-aussi-hype-que-moi-3ea',0, NOW() - INTERVAL 132 HOUR, NOW() - INTERVAL 127 HOUR),
(3,2,4,'Théorie : Lucia est l’héroïne principale, pas un simple duo','theorie-lucia-est-l-heroine-principale-pas-un-simple-duo-3eb',0, NOW() - INTERVAL 121 HOUR, NOW() - INTERVAL 117 HOUR),
(4,1,7,'La carte de Leonida serait-elle la plus grande de la saga ?','la-carte-de-leonida-serait-elle-la-plus-grande-de-la-saga-3ec',0, NOW() - INTERVAL 110 HOUR, NOW() - INTERVAL 106 HOUR),
(5,4,9,'Vos véhicules les plus attendus dans GTA VI ?','vos-vehicules-les-plus-attendus-dans-gta-vi-3ed',0, NOW() - INTERVAL 99 HOUR, NOW() - INTERVAL 95 HOUR),
(6,1,6,'GTA V vs GTA VI : qu’est-ce qui va vraiment changer ?','gta-v-vs-gta-vi-qu-est-ce-qui-va-vraiment-changer-3ee',0, NOW() - INTERVAL 88 HOUR, NOW() - INTERVAL 84 HOUR),
(7,3,8,'Guide : par quoi commencer le jour de la sortie ?','guide-par-quoi-commencer-le-jour-de-la-sortie-3ef',0, NOW() - INTERVAL 77 HOUR, NOW() - INTERVAL 73 HOUR),
(8,2,10,'Ce “leak” qui circule est un fake, voici pourquoi','ce-leak-qui-circule-est-un-fake-voici-pourquoi-3f0',0, NOW() - INTERVAL 66 HOUR, NOW() - INTERVAL 62 HOUR),
(9,5,19,'La bande-son : vos radios et musiques de rêve pour Vice City ?','la-bande-son-vos-radios-et-musiques-de-reve-pour-vice-city-3f1',0, NOW() - INTERVAL 55 HOUR, NOW() - INTERVAL 51 HOUR),
(10,5,18,'Balancez vos meilleurs memes GTA 😂','balancez-vos-meilleurs-memes-gta-d-3f2',0, NOW() - INTERVAL 44 HOUR, NOW() - INTERVAL 40 HOUR),
(11,1,22,'L’ambiance Floride/néon, c’est ça qui vous fait rêver ?','l-ambiance-floride-neon-c-est-ca-qui-vous-fait-rever-3f3',0, NOW() - INTERVAL 33 HOUR, NOW() - INTERVAL 29 HOUR),
(12,2,20,'Petit point lore : la place de Vice City dans la saga','petit-point-lore-la-place-de-vice-city-dans-la-saga-3f4',0, NOW() - INTERVAL 22 HOUR, NOW() - INTERVAL 18 HOUR),
(13,4,23,'Niveau de recherche : vous jouez bourrin ou discret ?','niveau-de-recherche-vous-jouez-bourrin-ou-discret-3f5',0, NOW() - INTERVAL 11 HOUR, NOW() - INTERVAL 7 HOUR);

INSERT INTO forum_posts (thread_id, user_id, body, created_at) VALUES
(2,14,'LE COMPTE À REBOURS EST LANCÉ. 19 novembre 2026, notez la date !! J’en dors plus 😭🌴', NOW() - INTERVAL 7920 MINUTE),
(2,3,'Pareil 🌴 je relance la bande-annonce en boucle avec Vice FM dans les oreilles.', NOW() - INTERVAL 7883 MINUTE),
(2,48,'On se calme, Rockstar a déjà repoussé une fois. J’y croirai en voyant le jeu tourner.', NOW() - INTERVAL 7846 MINUTE),
(2,21,'Première fois que je vais jouer à un GTA day one, trop hâte ! Des conseils pour un débutant ?', NOW() - INTERVAL 7809 MINUTE),
(2,34,'Bienvenue NoobFirst 😄 commence par l’histoire tranquille, profite de la ville.', NOW() - INTERVAL 7772 MINUTE),
(3,4,'À force de revoir le trailer, je suis convaincue que Lucia porte le récit. Son regard dit tout.', NOW() - INTERVAL 7260 MINUTE),
(3,5,'Mouais, Jason est clairement le moteur de l’action. Sans lui Lucia avance pas.', NOW() - INTERVAL 7223 MINUTE),
(3,15,'Les deux sont liés façon Bonnie & Clyde. C’est l’alchimie du duo le vrai sujet.', NOW() - INTERVAL 7186 MINUTE),
(3,50,'Ce qui me touche c’est leur relation. J’espère une vraie histoire d’amour et de loyauté.', NOW() - INTERVAL 7149 MINUTE),
(4,7,'En recoupant les plans aériens, l’échelle est dingue. Ville + marécages + arrière-pays.', NOW() - INTERVAL 6600 MINUTE),
(4,42,'J’ai hâte de fouiller chaque recoin : grottes, Everglades, secrets cachés 👀', NOW() - INTERVAL 6563 MINUTE),
(4,28,'Pourvu qu’il y ait une vraie zone maritime, je veux explorer en bateau !', NOW() - INTERVAL 6526 MINUTE),
(4,10,'Source ? Pour l’instant la taille exacte n’est pas confirmée, prudence.', NOW() - INTERVAL 6489 MINUTE),
(5,9,'Moi je veux une supercar avec une vraie physique et du tuning profond 🏎️', NOW() - INTERVAL 5940 MINUTE),
(5,27,'Une bonne moto et des routes désertes, c’est tout ce que je demande.', NOW() - INTERVAL 5903 MINUTE),
(5,29,'Un hélico maniable pour survoler Vice City la nuit, le rêve.', NOW() - INTERVAL 5866 MINUTE),
(5,45,'Juste une décapotable, coucher de soleil et la radio à fond 🌅', NOW() - INTERVAL 5829 MINUTE),
(6,6,'Je joue depuis Vice City 2002. Le bond technique annoncé me rappelle le choc GTA III → IV.', NOW() - INTERVAL 5280 MINUTE),
(6,33,'Pour moi c’est les détails : PNJ réactifs, météo dynamique, physique de l’eau.', NOW() - INTERVAL 5243 MINUTE),
(6,17,'Sur PC avec les mods ça va être hallucinant… mais faudra être patient pour la version PC.', NOW() - INTERVAL 5206 MINUTE),
(6,16,'Côté console, je croise les doigts pour du 60fps stable sur PS5.', NOW() - INTERVAL 5169 MINUTE),
(7,8,'Conseil de braqueuse : sécurisez vite un véhicule fiable et de l’argent avant de vous éclater.', NOW() - INTERVAL 4620 MINUTE),
(7,25,'Pensez investissements/business dès que possible, l’économie ça se prépare.', NOW() - INTERVAL 4583 MINUTE),
(7,51,'Et grindez malin, pas bête. L’efficacité avant le chaos 😎', NOW() - INTERVAL 4546 MINUTE),
(7,21,'Merci !! je note tout ça 🙏', NOW() - INTERVAL 4509 MINUTE),
(8,10,'Incohérences d’éclairage, UI mal alignée… clairement un montage. Arrêtez de partager ça.', NOW() - INTERVAL 3960 MINUTE),
(8,38,'Et si le fake était une vraie fuite déguisée par Rockstar ?? 👀 (je plaisante… ou pas)', NOW() - INTERVAL 3923 MINUTE),
(8,26,'J’ai analysé frame par frame, LeakSkeptic a raison, les ombres ne collent pas.', NOW() - INTERVAL 3886 MINUTE),
(8,52,'Je relaie l’info : à considérer comme NON officiel tant que rien n’est confirmé.', NOW() - INTERVAL 3849 MINUTE),
(9,19,'Les radios font l’âme de GTA. Je veux une station synthwave pure pour rouler la nuit.', NOW() - INTERVAL 3300 MINUTE),
(9,37,'Je parie sur un mix 80s + artistes modernes. Florida vibes obligatoire 🎶', NOW() - INTERVAL 3263 MINUTE),
(9,49,'Cassettes, néons, saxo… donnez-moi l’esthétique 88 et je suis heureux.', NOW() - INTERVAL 3226 MINUTE),
(9,3,'En attendant j’écoute Vice FM ici même, le lecteur en bas à gauche est addictif 📻', NOW() - INTERVAL 3189 MINUTE),
(10,18,'“Encore un trailer ?” *recharge la page 47 fois* 😂', NOW() - INTERVAL 2640 MINUTE),
(10,35,'La vraie difficulté de GTA VI : attendre sans spoil.', NOW() - INTERVAL 2603 MINUTE),
(10,31,'En stream je vais pleurer au compte à rebours, c’est officiel.', NOW() - INTERVAL 2566 MINUTE),
(10,45,'Calmez-vous les amis 😎 respirez, Vice City attendra.', NOW() - INTERVAL 2529 MINUTE),
(11,22,'Plages, palmiers, néons roses… cette vibe me transporte direct 🌴', NOW() - INTERVAL 1980 MINUTE),
(11,11,'Vivement le mode photo, la lumière du coucher de soleil va être folle à capturer.', NOW() - INTERVAL 1943 MINUTE),
(11,40,'Et la météo dynamique ! Un orage tropical sur la skyline, j’en frissonne déjà ⛈️', NOW() - INTERVAL 1906 MINUTE),
(11,36,'Sans oublier le style des persos. Le drip de Lucia et Jason va lancer des tendances.', NOW() - INTERVAL 1869 MINUTE),
(12,20,'Vice City = les 80s à l’origine. GTA VI nous y ramène, version moderne. Bouclage parfait.', NOW() - INTERVAL 1320 MINUTE),
(12,43,'Ça me rend nostalgique… je joue depuis GTA III, voir Vice City revenir c’est émouvant.', NOW() - INTERVAL 1283 MINUTE),
(12,32,'J’espère le retour de mécaniques San Andreas : sport, fringues, RP de quartier.', NOW() - INTERVAL 1246 MINUTE),
(12,15,'Gardez un œil sur les easter eggs reliant les anciens opus, il y en aura sûrement.', NOW() - INTERVAL 1209 MINUTE),
(13,23,'Moi c’est 5 étoiles direct, course-poursuite jusqu’au bout 🚔', NOW() - INTERVAL 660 MINUTE),
(13,24,'Trop bourrin pour moi 😅 je préfère les missions propres, sans alerte.', NOW() - INTERVAL 623 MINUTE),
(13,44,'En PvP faut savoir gérer la pression, le skill se voit là.', NOW() - INTERVAL 586 MINUTE),
(13,41,'Tout dépend du feeling des armes. Si le gunplay est bon, je tente le chaos.', NOW() - INTERVAL 549 MINUTE);

-- ====== Galerie de fan-arts (seed communautaire) ======
INSERT INTO fanarts (user_id, title, image, status) VALUES
(3,'Vice City by night','/public/assets/img/scenes/nightlife.png','approved'),
(20,'Course-poursuite à Leonida','/public/assets/img/scenes/police.png','approved'),
(11,'Skyline au crépuscule','/public/assets/img/scenes/downtown.png','approved'),
(12,'Drift néon','/public/assets/img/scenes/drift.png','approved'),
(31,'Marina dorée','/public/assets/img/scenes/marina-aerial.png','approved'),
(43,'Orage tropical sur la ville','/public/assets/img/scenes/storm.png','approved'),
(22,'Coucher de soleil sur la plage','/public/assets/img/scenes/beach-sunset.png','approved'),
(50,'Ambiance Ocean Drive','/public/assets/img/scenes/ocean-drive.png','approved'),
(25,'Route déserte au sunset','/public/assets/img/scenes/desert-road.png','approved'),
(48,'Casino néon','/public/assets/img/scenes/casino.png','approved');

-- ====== Événements & comptes à rebours ======
INSERT INTO events (title, description, icon, event_date, link, lang) VALUES
('Sortie de GTA VI','Le grand jour : Grand Theft Auto VI débarque enfin à Vice City.','🎮','2026-11-19 00:00:00','/index.php','fr'),
('Watch Party — veille de sortie','On se retrouve pour fêter le lancement ensemble sur le forum.','📺','2026-11-18 20:00:00','/pages/forum.php','fr'),
('Black Friday Boutique','Les meilleurs deals GTA de l''année dans la boutique ViceHub X.','🛍️','2026-11-28 09:00:00','/pages/shop.php','fr'),
('2 ans du 1er trailer','On reregarde et on décortique le trailer culte image par image.','🎬','2026-12-05 18:00:00','/pages/trailer-lab.php','fr'),
('Tournoi communautaire','Défis et classement spécial entre membres du forum.','🏆','2026-10-15 18:00:00','/pages/classement.php','fr');

-- ====== Sondages supplémentaires + votes personas ======
INSERT INTO polls (id, question, lang, active) VALUES
(2,'Quel véhicule veux-tu conduire en premier ?','fr',1),
(3,'Ton style de jeu sur GTA VI ?','fr',1);
INSERT INTO poll_options (id, poll_id, label) VALUES
(4,2,'Supercar'),(5,2,'Muscle car'),(6,2,'Moto'),(7,2,'Bateau'),(8,2,'Aéronef'),
(9,3,'Histoire solo'),(10,3,'En ligne / braquages'),(11,3,'Roleplay'),(12,3,'100% / collectibles');
INSERT INTO poll_votes (poll_id, option_id, ip_hash) VALUES
(1,1,SHA2('s1',256)),(1,1,SHA2('s2',256)),(1,2,SHA2('s3',256)),(1,3,SHA2('s4',256)),(1,1,SHA2('s5',256)),(1,3,SHA2('s6',256)),(1,2,SHA2('s7',256)),(1,3,SHA2('s8',256)),(1,1,SHA2('s9',256)),
(2,4,SHA2('s10',256)),(2,4,SHA2('s11',256)),(2,5,SHA2('s12',256)),(2,8,SHA2('s13',256)),(2,6,SHA2('s14',256)),(2,4,SHA2('s15',256)),(2,7,SHA2('s16',256)),(2,8,SHA2('s17',256)),(2,4,SHA2('s18',256)),
(3,9,SHA2('s19',256)),(3,10,SHA2('s20',256)),(3,9,SHA2('s21',256)),(3,11,SHA2('s22',256)),(3,10,SHA2('s23',256)),(3,12,SHA2('s24',256)),(3,9,SHA2('s25',256)),(3,10,SHA2('s26',256)),(3,11,SHA2('s27',256));

-- ====== Likes de demarrage (personas) ======
INSERT INTO likes (user_id, kind, item_id) VALUES
(45,'post',29),
(17,'post',24),
(23,'fanart',1),
(16,'post',23),
(13,'post',19),
(25,'post',11),
(20,'post',26),
(29,'post',3),
(52,'post',45),
(22,'post',29),
(25,'fanart',6),
(32,'post',26),
(42,'fanart',3),
(13,'post',13),
(30,'post',34),
(26,'post',47),
(46,'post',43),
(12,'post',37),
(39,'post',47),
(37,'post',40),
(19,'post',45),
(13,'post',3),
(26,'fanart',2),
(14,'post',22),
(37,'post',38),
(12,'post',38),
(11,'post',10),
(44,'fanart',4),
(35,'post',32),
(31,'post',14),
(30,'post',4),
(42,'post',30),
(14,'post',36),
(17,'post',4),
(40,'post',23),
(24,'post',41),
(48,'post',16),
(37,'post',7),
(43,'post',3),
(32,'post',13),
(42,'fanart',8),
(49,'fanart',10),
(18,'fanart',10),
(4,'post',36),
(39,'fanart',7),
(11,'fanart',9),
(13,'fanart',10),
(3,'post',51),
(38,'post',51),
(21,'fanart',8),
(25,'post',31),
(51,'fanart',8),
(19,'post',34),
(36,'post',45),
(16,'post',2),
(31,'post',3),
(5,'post',8),
(27,'post',33),
(40,'post',12),
(49,'fanart',3),
(51,'post',31),
(43,'fanart',7),
(28,'post',11),
(22,'post',39),
(45,'post',17),
(26,'post',39),
(9,'post',17),
(12,'post',45),
(12,'fanart',6),
(22,'post',33),
(27,'post',13),
(14,'fanart',8),
(43,'post',35),
(52,'post',15),
(4,'fanart',2),
(43,'fanart',6),
(4,'post',19),
(52,'post',14),
(40,'post',34),
(34,'post',25),
(29,'post',28),
(11,'fanart',3),
(51,'post',28),
(28,'fanart',5),
(41,'post',45),
(4,'post',15),
(46,'fanart',3),
(45,'post',48),
(30,'post',1);
