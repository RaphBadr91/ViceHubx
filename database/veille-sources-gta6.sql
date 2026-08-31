-- ViceHub X — Veille concurrents : ~50 sources GTA 6 (FR + EN) + colonne langue.
-- Idempotent. A lancer dans phpMyAdmin. Apres : Admin -> Veille -> Rafraichir, puis elaguer
-- les flux qui renvoient 0 (URL de flux differente selon le site).

CREATE TABLE IF NOT EXISTS competitor_sources (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, url VARCHAR(500) NOT NULL,
  type ENUM('rss','sitemap') NOT NULL DEFAULT 'rss', lang ENUM('fr','en') NOT NULL DEFAULT 'en',
  active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS competitor_items (
  id INT AUTO_INCREMENT PRIMARY KEY, source_id INT, title VARCHAR(300) NOT NULL,
  url VARCHAR(500) NOT NULL UNIQUE, published_at DATETIME NULL,
  status ENUM('new','ignored','written') NOT NULL DEFAULT 'new', lang ENUM('fr','en') NOT NULL DEFAULT 'en',
  seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_status (status)) ENGINE=InnoDB;
ALTER TABLE competitor_sources ADD COLUMN IF NOT EXISTS lang ENUM('fr','en') NOT NULL DEFAULT 'en';
ALTER TABLE competitor_items   ADD COLUMN IF NOT EXISTS lang ENUM('fr','en') NOT NULL DEFAULT 'en';

-- ===== SITES EN (26) =====
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'IGN Games','https://feeds.ign.com/ign/games-all','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://feeds.ign.com/ign/games-all');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'GameSpot News','https://www.gamespot.com/feeds/news/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gamespot.com/feeds/news/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Kotaku','https://kotaku.com/rss','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://kotaku.com/rss');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Polygon','https://www.polygon.com/rss/index.xml','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.polygon.com/rss/index.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Eurogamer','https://www.eurogamer.net/feed','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.eurogamer.net/feed');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'PC Gamer','https://www.pcgamer.com/rss/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.pcgamer.com/rss/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Rock Paper Shotgun','https://www.rockpapershotgun.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.rockpapershotgun.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'VG247','https://www.vg247.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.vg247.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'GamesRadar','https://www.gamesradar.com/rss/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gamesradar.com/rss/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Push Square','https://www.pushsquare.com/feeds/latest','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.pushsquare.com/feeds/latest');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Video Games Chronicle','https://www.videogameschronicle.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.videogameschronicle.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'TheGamer','https://www.thegamer.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.thegamer.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Dexerto','https://www.dexerto.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.dexerto.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Game Rant','https://gamerant.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://gamerant.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Wccftech','https://wccftech.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://wccftech.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Destructoid','https://www.destructoid.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.destructoid.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Insider Gaming','https://insider-gaming.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://insider-gaming.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Screen Rant Games','https://screenrant.com/feed/game-news/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://screenrant.com/feed/game-news/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Gematsu','https://www.gematsu.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gematsu.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Attack of the Fanboy','https://attackofthefanboy.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://attackofthefanboy.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'The Loadout','https://www.theloadout.com/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.theloadout.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Game Informer','https://www.gameinformer.com/rss.xml','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gameinformer.com/rss.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Sportskeeda GTA','https://www.sportskeeda.com/feed/gta','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.sportskeeda.com/feed/gta');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'GTABase','https://www.gtabase.com/rss/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gtabase.com/rss/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Rockstar Newswire','https://www.rockstargames.com/newswire.rss','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.rockstargames.com/newswire.rss');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'TweakTown Gaming','https://www.tweaktown.com/gaming/feed/','rss','en' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.tweaktown.com/gaming/feed/');

-- ===== SITES FR (24) =====
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Jeuxvideo.com','https://www.jeuxvideo.com/rss/rss.xml','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.jeuxvideo.com/rss/rss.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Gamekult','https://www.gamekult.com/feed.xml','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gamekult.com/feed.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'IGN France','https://fr.ign.com/feed.xml','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://fr.ign.com/feed.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'ActuGaming','https://www.actugaming.net/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.actugaming.net/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Gameblog','https://www.gameblog.fr/feed','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gameblog.fr/feed');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'GamerGen','https://www.gamergen.com/feed','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gamergen.com/feed');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Millenium','https://www.millenium.org/feed','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.millenium.org/feed');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Rockstar Mag (GTA FR)','https://rockstarmag.fr/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://rockstarmag.fr/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Numerama','https://www.numerama.com/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.numerama.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Frandroid','https://www.frandroid.com/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.frandroid.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Clubic','https://www.clubic.com/feed/news.rss','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.clubic.com/feed/news.rss');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Tom''s Guide FR','https://www.tomsguide.fr/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.tomsguide.fr/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'JeuxActu','https://www.jeuxactu.com/rss.xml','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.jeuxactu.com/rss.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Journal du Geek','https://www.journaldugeek.com/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.journaldugeek.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Gamewave','https://www.gamewave.fr/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.gamewave.fr/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Xboxygen','https://www.xboxygen.com/rss.xml','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.xboxygen.com/rss.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Begeek','https://www.begeek.fr/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.begeek.fr/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Presse-citron','https://www.presse-citron.net/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.presse-citron.net/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Hitek','https://hitek.fr/rss','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://hitek.fr/rss');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'JVFrance','https://www.jvfrance.com/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.jvfrance.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Gamosaurus','https://gamosaurus.com/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://gamosaurus.com/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'ConsoleFun','https://www.consolefun.fr/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.consolefun.fr/feed/');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'Jeux Video Live','https://www.jeuxvideo-live.com/rss.xml','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.jeuxvideo-live.com/rss.xml');
INSERT INTO competitor_sources (name, url, type, lang) SELECT 'GeeksLine','https://www.geeksline.fr/feed/','rss','fr' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM competitor_sources WHERE url='https://www.geeksline.fr/feed/');

-- Optionnel : activer l'auto-publication directement en SQL (sinon bouton dans l'admin) :
-- INSERT INTO settings (`key`,value) VALUES ('veille_auto','1') ON DUPLICATE KEY UPDATE value='1';