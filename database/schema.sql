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
    category        ENUM('poster','game','console','apparel','accessory','collectible') NOT NULL DEFAULT 'accessory',
    price           DECIMAL(8,2),
    currency        VARCHAR(8) NOT NULL DEFAULT 'EUR',
    image           VARCHAR(255),
    -- sale_type : 'stripe' = vendu directement (paiement Stripe) ; 'external' = lien revendeur/affilié (Amazon…)
    sale_type       ENUM('external','stripe') NOT NULL DEFAULT 'external',
    url             VARCHAR(500),            -- lien externe (si sale_type='external')
    stripe_price_id VARCHAR(120),            -- ID de prix Stripe (optionnel ; sinon price+currency)
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
('Trailers', 'trailers');

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
('shop_currency', 'EUR');

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
