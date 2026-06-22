-- ==================================================================
--  ViceHub X — Schéma de base de données + contenu de démonstration
--  MySQL / MariaDB · utf8mb4
--  Importer :  mysql -u root -p < database/schema.sql
-- ==================================================================

CREATE DATABASE IF NOT EXISTS vicehubx
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vicehubx;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS poll_votes, poll_options, polls, article_tags, tags,
    comments, articles, categories, newsletter_subscribers, media, ads,
    affiliate_links, seo_pages, settings, vehicles, characters, map_zones,
    trailer_analyses, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Utilisateurs (admin) ----------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','editor') NOT NULL DEFAULT 'admin',
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
    status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
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
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$12$KSEQS1g76KeHzzgEt6Gn8OJ0vDPkwLHTNKMo2Y9.58Vgp0VZXrYzi', 'admin');

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
INSERT INTO vehicles (name, type, speed, use_case, rarity, lang) VALUES
('Infernus Neo','Supercar','340 km/h','Courses et fuites rapides','legendary','fr'),
('Sabre Coastline','Muscle car','255 km/h','Polyvalente, idéale en ville','rare','fr'),
('Marina Cruiser','Bateau','120 km/h','Exploration des zones côtières','epic','fr'),
('Swamp Runner','4x4','165 km/h','Terrains boueux et marécages','common','fr'),
('Skyline VTOL','Aéronef','420 km/h','Déplacements rapides longue distance','legendary','fr');

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

-- Sondage communautaire
INSERT INTO polls (question, lang, active) VALUES
('Quel protagoniste vous attire le plus ?', 'fr', 1);
INSERT INTO poll_options (poll_id, label) VALUES
(1, 'Lucia'), (1, 'Jason'), (1, 'Les deux !');

-- Réglages & SEO
INSERT INTO settings (`key`, value) VALUES
('site_tagline_fr', 'Entrez dans la nouvelle génération de Vice City.'),
('site_tagline_en', 'Enter The Next Generation Of Vice City.'),
('adsense_client', ''),
('adsense_slot', ''),
('hero_video', ''),
('trailer_url', ''),
('release_date', '2026-11-19T00:00:00');

INSERT INTO seo_pages (path, title, description) VALUES
('/index.php', 'ViceHub X — GTA6 News', 'News, guides, leaks et analyses de trailers GTA VI.'),
('/pages/news.php', 'News GTA6 — ViceHub X', 'Toute l’actualité GTA VI en continu.');
