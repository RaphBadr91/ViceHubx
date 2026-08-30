-- ViceHub X — Stratégie SEO (Search Console 28j, panel 5 experts) — À exécuter dans phpMyAdmin.
-- 1) Réécritures titres/meta (levier CTR n°1)  2) Consolidation co-op (301)  3) Sortie sitemap des fusionnés.
-- Idempotent / rejouable sans risque.

-- ============ 1. TABLE redirects (si absente) ============
CREATE TABLE IF NOT EXISTS redirects (
  from_slug  VARCHAR(220) PRIMARY KEY,
  to_slug    VARCHAR(220) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============ 2. RÉÉCRITURES meta_title / meta_description (17 pages) ============
UPDATE articles SET meta_title='GTA 6 Co-Op Campaign: What We Know (2026)', meta_description='Does GTA 6 have a co-op or 2-player campaign? Every confirmed detail and rumor about Vice City co-op, sorted from fact to fiction. Read more.' WHERE slug='gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city';
UPDATE articles SET meta_title='GTA 6 & Streamers: Which Creators Are Waiting? 2026', meta_description='Which YouTubers and streamers are counting down to GTA 6? Inside the creator hype, the viral wait and who''s ready for launch day. See the list.' WHERE slug='gta-6-content-creators-streamers-youtubers-the-viral-wait';
UPDATE articles SET meta_title='GTA 6 Nine1Nine & Hypercar Leak Explained (2026)', meta_description='Nine1Nine, GTA 6''s Vice City nightclub, and the ''hypercar part 1'' leak explained: what the club, music scene and rumored supercar mean.' WHERE slug='nine1nine-vice-city-s-premier-nightclub-at-the-heart-of-gta-6-s-music-scene';
UPDATE articles SET meta_title='GTA 6 Engine: How RAGE Pushes Graphics in 2026', meta_description='What engine powers GTA 6? Inside Rockstar''s RAGE engine: the graphics, lighting and physics tech behind the trailers. See how it works.' WHERE slug='gta-6-graphics-rage-engine-how-rockstar-pushes-visuals-to-the-limit';
UPDATE articles SET meta_title='GTA 6 Supercars: Every Fast Car Found (2026)', meta_description='Every GTA 6 supercar spotted in the trailers, identified and broken down with top speeds and real-world inspirations. Full list inside.' WHERE slug='gta-6-supercars-identified-complete-breakdown-of-trailers-high-performance-vehicles';
UPDATE articles SET meta_title='GTA 6 Driving Physics: Suspension & Grip 2026', meta_description='How GTA 6 driving really feels: suspension, grip and RAGE engine handling that makes every car different. See what Rockstar changed.' WHERE slug='gta-6-driving-physics-revealed-suspension-grip-rage-engine-mechanics';
UPDATE articles SET meta_title='GTA 6: PS5 vs Xbox Series X - Which Runs Best?', meta_description='GTA 6 on PS5 vs Xbox Series X: frame rate, load times and the technical verdict. See which console wins before launch day.' WHERE slug='ps5-vs-xbox-series-x-for-gta-6-performance-load-times-technical-verdict';
UPDATE articles SET meta_title='Is GTA 6 on Game Pass? Xbox Timeline 2026', meta_description='Will GTA 6 hit Xbox Game Pass? What Microsoft''s 2026 strategy means for day-one access, pricing and timing. Get the full breakdown.' WHERE slug='gta-6-on-game-pass-access-timeline-microsoft-s-2026-strategy';
UPDATE articles SET meta_title='Is Neon in GTA 6? Vice City''s Return Explained', meta_description='Is neon coming back in GTA 6? Inside Vice City''s neon-soaked return: the map, districts and 80s vibe Rockstar is reviving. See it all.' WHERE slug='vice-city-in-gta-6-the-neon-queen-makes-her-return';
UPDATE articles SET meta_title='GTA 6 Aircraft: All Helicopters, Jets & Drones', meta_description='Every GTA 6 aircraft explained: helicopters, jets and drones of Leonida. Full list, uses and what to expect at launch. Read the guide.' WHERE slug='gta-6-aircraft-guide-all-helicopters-jets-drones-of-leonida-explained';
UPDATE articles SET meta_title='GTA 6 Heists: What Rockstar Plans for Jason & Lucia', meta_description='GTA 6 heists explained: how Jason and Lucia''s robberies could work, planning, crews and payouts. Everything we know so far.' WHERE slug='gta-6-heists-explained-what-rockstar-is-planning-for-jason-lucia';
UPDATE articles SET meta_title='GTA 6 Editions: Standard vs Ultimate - Worth It?', meta_description='GTA 6 Standard vs Ultimate compared: bonuses, price and which edition is actually worth your money. Pick the right one before pre-order.' WHERE slug='gta-6-edition-guide-standard-vs-ultimate-which-edition-delivers-real-value';
UPDATE articles SET meta_title='GTA 6 PC Release Date: When & Why the Delay?', meta_description='When is GTA 6 coming to PC? Why Rockstar delays the computer version, the likely timing and what to expect. Get the full answer.' WHERE slug='gta-6-pc-release-date-when-why-rockstar-delays-the-computer-version';
UPDATE articles SET meta_title='GTA 6 PC Mods: What Might Be Possible (2026)', meta_description='Will GTA 6 support PC mods? What''s realistically possible, the rumors and Rockstar''s likely stance. See the odds before launch.' WHERE slug='gta-6-mods-on-pc-what-might-be-possible-rumors';
UPDATE articles SET meta_title='GTA 6 Map vs GTA 5: How Much Bigger Is Leonida?', meta_description='GTA 6 map vs GTA 5: how much bigger is Leonida vs Los Santos? Full size, density and scale comparison, with the numbers.' WHERE slug='gta-6-map-vs-gta-5-complete-size-density-analysis-leonida-vs-los-santos';
UPDATE articles SET meta_title='GTA 6 en coop ? Ce que l''on sait du multi 2026', meta_description='GTA 6 aura-t-il un mode cooperatif ou 2 joueurs ? Tout ce qui est confirme et rumeur sur la campagne coop a Vice City. Le point complet.' WHERE slug='multijoueur-cooperatif-gta-6-ce-qu-il-faut-savoir-sur-le-mode-coop';
UPDATE articles SET meta_title='GTA 6 : reconnaitre un vrai leak d''une intox 2026', meta_description='Comment distinguer un vrai leak GTA 6 d''une fausse rumeur ? Les signes qui ne trompent pas pour ne plus se faire avoir. A lire avant de partager.' WHERE slug='reconnaitre-un-vrai-leak-gta-6-d-une-intox';

-- ============ 3. CONSOLIDATION co-op — redirections 301 (from -> to) ============
INSERT INTO redirects (from_slug,to_slug) VALUES ('gta-6-co-op-what-we-actually-know-about-two-player-gameplay','gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city') ON DUPLICATE KEY UPDATE to_slug=VALUES(to_slug);
INSERT INTO redirects (from_slug,to_slug) VALUES ('gta-vi-multiplayer-cooperation-confirmed-features-limits-rumors-explained','gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city') ON DUPLICATE KEY UPDATE to_slug=VALUES(to_slug);
INSERT INTO redirects (from_slug,to_slug) VALUES ('gta-vi-leak-could-a-co-op-campaign-launch-at-release','gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city') ON DUPLICATE KEY UPDATE to_slug=VALUES(to_slug);
INSERT INTO redirects (from_slug,to_slug) VALUES ('story-co-op-mode-the-fan-dream-for-gta-vi-s-jason-lucia','gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city') ON DUPLICATE KEY UPDATE to_slug=VALUES(to_slug);
INSERT INTO redirects (from_slug,to_slug) VALUES ('cooperation-multijoueur-gta-6-modes-limites-et-rumeurs-expliquees','multijoueur-cooperatif-gta-6-ce-qu-il-faut-savoir-sur-le-mode-coop') ON DUPLICATE KEY UPDATE to_slug=VALUES(to_slug);
INSERT INTO redirects (from_slug,to_slug) VALUES ('leak-mode-cooperatif-au-lancement','multijoueur-cooperatif-gta-6-ce-qu-il-faut-savoir-sur-le-mode-coop') ON DUPLICATE KEY UPDATE to_slug=VALUES(to_slug);

-- ============ 4. Sortir les articles fusionnés du sitemap (le 301 reste actif via la table redirects) ============
UPDATE articles SET status='draft' WHERE slug='gta-6-co-op-what-we-actually-know-about-two-player-gameplay';
UPDATE articles SET status='draft' WHERE slug='gta-vi-multiplayer-cooperation-confirmed-features-limits-rumors-explained';
UPDATE articles SET status='draft' WHERE slug='gta-vi-leak-could-a-co-op-campaign-launch-at-release';
UPDATE articles SET status='draft' WHERE slug='story-co-op-mode-the-fan-dream-for-gta-vi-s-jason-lucia';
UPDATE articles SET status='draft' WHERE slug='cooperation-multijoueur-gta-6-modes-limites-et-rumeurs-expliquees';
UPDATE articles SET status='draft' WHERE slug='leak-mode-cooperatif-au-lancement';

-- 17 réécritures, 6 redirections 301. Fin.