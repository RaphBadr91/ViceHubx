-- ViceHub X — Forum : sujets « gameplay reveal 27 août 2026 » + colonne updated_at + template sameAs.
-- À exécuter dans phpMyAdmin. Idempotent (rejouable). Prérequis forum : seed_forum_gta6.sql (catégorie 'gta-vi').

-- ============ 1. Colonne updated_at (fraîcheur / dateModified pour Discover) ============
-- (Si la colonne existe déjà, cette commande renverra une erreur inoffensive : ignore-la.)
ALTER TABLE articles ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- ============ 2. sameAs : renseigne tes VRAIS comptes (ou via Admin > Réglages) ============
-- Décommente et remplace par tes URLs réelles ; active le sameAs (signal de marque anti-homonyme).
-- INSERT INTO settings (`key`,value) VALUES ('profile_instagram','https://www.instagram.com/vicehubx') ON DUPLICATE KEY UPDATE value=VALUES(value);
-- INSERT INTO settings (`key`,value) VALUES ('profile_tiktok','https://www.tiktok.com/@vicehubx') ON DUPLICATE KEY UPDATE value=VALUES(value);
-- INSERT INTO settings (`key`,value) VALUES ('profile_youtube','https://www.youtube.com/@vicehubx') ON DUPLICATE KEY UPDATE value=VALUES(value);
-- INSERT INTO settings (`key`,value) VALUES ('profile_x','https://x.com/vicehubx') ON DUPLICATE KEY UPDATE value=VALUES(value);
-- INSERT INTO settings (`key`,value) VALUES ('profile_facebook','https://www.facebook.com/vicehubx') ON DUPLICATE KEY UPDATE value=VALUES(value);
-- INSERT INTO settings (`key`,value) VALUES ('profile_discord','https://discord.gg/xxxxxxx') ON DUPLICATE KEY UPDATE value=VALUES(value);

-- ============ 3. Sujets de forum sur le gameplay (catégorie GTA VI) ============
INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='hype_helena' LIMIT 1),'🔴 GAMEPLAY GTA 6 — vos réactions à chaud après la présentation du 27 août !','gameplay-gta-6-vos-reactions-a-chaud-apres-la-presentation-du-27-aout-gp',1, NOW() - INTERVAL 120 HOUR, NOW() - INTERVAL 0 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='gameplay-gta-6-vos-reactions-a-chaud-apres-la-presentation-du-27-aout-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='hype_helena' LIMIT 1),'ÇA Y EST on l''a vu 😭🌴 la présentation gameplay est enfin là ! Balancez vos réactions à chaud : la scène qui vous a le plus marqué ? Moi je m''en remets pas des reflets néon la nuit…',t.created_at
  FROM forum_threads t WHERE t.slug='gameplay-gta-6-vos-reactions-a-chaud-apres-la-presentation-du-27-aout-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='street_racer' LIMIT 1),'Les véhicules montrés dans le gameplay : votre top ?','les-vehicules-montres-dans-le-gameplay-votre-top-gp',1, NOW() - INTERVAL 110 HOUR, NOW() - INTERVAL 1 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='les-vehicules-montres-dans-le-gameplay-votre-top-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='street_racer' LIMIT 1),'Supercars, motos, bateaux, muscle cars… on a vu pas mal de bagnoles dans la vidéo. C''est quoi VOTRE préférée ? Perso la décapotable rose au coucher de soleil, game over pour moi.',t.created_at
  FROM forum_threads t WHERE t.slug='les-vehicules-montres-dans-le-gameplay-votre-top-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='lore_lucie' LIMIT 1),'Jason & Lucia en action : ce que la vidéo révèle du duo','jason-lucia-en-action-ce-que-la-video-revele-du-duo-gp',0, NOW() - INTERVAL 100 HOUR, NOW() - INTERVAL 2 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='jason-lucia-en-action-ce-que-la-video-revele-du-duo-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='lore_lucie' LIMIT 1),'On en apprend enfin plus sur le duo. Leur dynamique, les braquages, la relation… qu''est-ce que vous avez retenu de Jason et Lucia dans cette présentation ?',t.created_at
  FROM forum_threads t WHERE t.slug='jason-lucia-en-action-ce-que-la-video-revele-du-duo-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='florida_man' LIMIT 1),'Leonida en vrai : les lieux et quartiers repérés dans le gameplay','leonida-en-vrai-les-lieux-et-quartiers-reperes-dans-le-gameplay-gp',0, NOW() - INTERVAL 90 HOUR, NOW() - INTERVAL 3 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='leonida-en-vrai-les-lieux-et-quartiers-reperes-dans-le-gameplay-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='florida_man' LIMIT 1),'Plage, centre-ville néon, marécages, boîtes de nuit… on a eu un vrai tour de Leonida. Vous avez repéré quels lieux ? On dirait Miami sous stéroïdes 🌴',t.created_at
  FROM forum_threads t WHERE t.slug='leonida-en-vrai-les-lieux-et-quartiers-reperes-dans-le-gameplay-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='tech_tina' LIMIT 1),'Graphismes RAGE : la scène qui vous a scié (pluie, foules, eau, éclairage)','graphismes-rage-la-scene-qui-vous-a-scie-pluie-foules-eau-eclairage-gp',0, NOW() - INTERVAL 80 HOUR, NOW() - INTERVAL 4 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='graphismes-rage-la-scene-qui-vous-a-scie-pluie-foules-eau-eclairage-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='tech_tina' LIMIT 1),'Techniquement c''est une claque. La pluie, les foules, l''eau, l''éclairage néon… quelle scène vous a le plus impressionné niveau moteur RAGE ?',t.created_at
  FROM forum_threads t WHERE t.slug='graphismes-rage-la-scene-qui-vous-a-scie-pluie-foules-eau-eclairage-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='skeptik_sam' LIMIT 1),'Netflix d''abord, YouTube à 3h : bon ou mauvais choix de Rockstar ?','netflix-d-abord-youtube-a-3h-bon-ou-mauvais-choix-de-rockstar-gp',0, NOW() - INTERVAL 70 HOUR, NOW() - INTERVAL 5 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='netflix-d-abord-youtube-a-3h-bon-ou-mauvais-choix-de-rockstar-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='skeptik_sam' LIMIT 1),'Diffusion Netflix en exclu puis YouTube gratuit quelques heures après. Stratégie maligne ou prise de tête pour rien ? Débat 🍿',t.created_at
  FROM forum_threads t WHERE t.slug='netflix-d-abord-youtube-a-3h-bon-ou-mauvais-choix-de-rockstar-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='pessimist_pat' LIMIT 1),'Ce que la présentation n''a PAS montré (carte complète, PC, online)','ce-que-la-presentation-n-a-pas-montre-carte-complete-pc-online-gp',0, NOW() - INTERVAL 60 HOUR, NOW() - INTERVAL 6 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='ce-que-la-presentation-n-a-pas-montre-carte-complete-pc-online-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='pessimist_pat' LIMIT 1),'On a eu du gameplay, mais toujours rien sur la carte complète, la version PC ou le online. Ça vous a manqué quoi dans cette présentation ?',t.created_at
  FROM forum_threads t WHERE t.slug='ce-que-la-presentation-n-a-pas-montre-carte-complete-pc-online-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='patient_paul' LIMIT 1),'Toujours le 19 novembre 2026 ? On récapitule après le reveal','toujours-le-19-novembre-2026-on-recapitule-apres-le-reveal-gp',0, NOW() - INTERVAL 50 HOUR, NOW() - INTERVAL 7 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='toujours-le-19-novembre-2026-on-recapitule-apres-le-reveal-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='patient_paul' LIMIT 1),'Petit rappel utile après la hype : la date reste le 19 novembre 2026 (PS5 / Xbox Series X|S). Rien n''a bougé côté sortie. On compte les jours ⏳',t.created_at
  FROM forum_threads t WHERE t.slug='toujours-le-19-novembre-2026-on-recapitule-apres-le-reveal-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='coop_dreamer' LIMIT 1),'Co-op / online : les indices aperçus dans le gameplay','co-op-online-les-indices-apercus-dans-le-gameplay-gp',0, NOW() - INTERVAL 40 HOUR, NOW() - INTERVAL 8 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='co-op-online-les-indices-apercus-dans-le-gameplay-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='coop_dreamer' LIMIT 1),'J''ai scruté chaque seconde pour des indices sur le co-op / online… vous avez vu des trucs qui pourraient teaser un mode à deux ? On peut rêver 👀',t.created_at
  FROM forum_threads t WHERE t.slug='co-op-online-les-indices-apercus-dans-le-gameplay-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

INSERT INTO forum_threads (category_id,user_id,title,slug,pinned,created_at,last_post_at)
  SELECT (SELECT id FROM forum_categories WHERE slug='gta-vi' LIMIT 1),(SELECT id FROM users WHERE username='screenshot_sky' LIMIT 1),'Vos captures préférées du gameplay 📸','vos-captures-preferees-du-gameplay-gp',0, NOW() - INTERVAL 30 HOUR, NOW() - INTERVAL 9 HOUR
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forum_threads WHERE slug='vos-captures-preferees-du-gameplay-gp');
INSERT INTO forum_posts (thread_id,user_id,body,created_at)
  SELECT t.id,(SELECT id FROM users WHERE username='screenshot_sky' LIMIT 1),'Postez vos plus belles captures / arrêts sur image de la présentation ! On se fait une petite galerie communautaire 🌆',t.created_at
  FROM forum_threads t WHERE t.slug='vos-captures-preferees-du-gameplay-gp' AND NOT EXISTS (SELECT 1 FROM forum_posts p WHERE p.thread_id=t.id);

-- Fin. Le bot forum-life.php fera vivre ces sujets ensuite.