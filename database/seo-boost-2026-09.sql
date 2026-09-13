-- ViceHub X — Boost SEO fondé sur Search Console (13 sept. 2026)
-- =====================================================================
-- CONSTAT : 16 604 impressions / 28 j mais CTR 0,79% (position ~10-12).
-- Les impressions sont là ; ce sont les TITRES qui ne font pas cliquer.
-- Ce script (1) réécrit meta_title/meta_description des articles à fortes
-- impressions pour un CTR maximal, et (2) fusionne les doublons « co-op »
-- (cannibalisation) via une redirection 301.
--
-- À exécuter UNE fois dans phpMyAdmin (onglet SQL). Idempotent.
-- « Résultat vide (aucune ligne) » = SUCCÈS. Effet immédiat (pas de déploiement).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) ANTI-CANNIBALISATION : fusionne les 2 articles co-op EN.
--    On garde le plus fort (2112 impr) et on redirige l'autre (609 impr)
--    → toute l'autorité se concentre sur une seule page = elle grimpe.
-- ---------------------------------------------------------------------
INSERT INTO redirects (from_slug, to_slug) VALUES
('gta-6-co-op-what-we-actually-know-about-two-player-gameplay',
 'gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city')
ON DUPLICATE KEY UPDATE to_slug = VALUES(to_slug);

-- ---------------------------------------------------------------------
-- 2) CTR : titres + meta descriptions optimisés (mots-clés en tête,
--    année, question directe, promesse claire). Longueurs SEO-safe.
-- ---------------------------------------------------------------------

-- Co-op / multijoueur EN (2112 impr, 0,24% CTR, pos 9) — la plus grosse réserve
UPDATE articles SET
  meta_title = 'GTA 6 Co-Op & Multiplayer: Is It Confirmed? (2026)',
  meta_description = 'Will GTA 6 have co-op? Everything we actually know about GTA 6 multiplayer and 2-player gameplay in Vice City — confirmed facts vs rumors, updated 2026.'
WHERE slug = 'gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city';

-- Streamers / créateurs EN (1336 impr, pos 8) — requête « what streamers are in gta 6 »
UPDATE articles SET
  meta_title = 'Which Streamers Are Playing GTA 6? Full Creator List (2026)',
  meta_description = 'Which streamers and YouTubers get GTA 6 first? The creators fueling the viral wait, who to follow, and what to expect at launch. Updated 2026.'
WHERE slug = 'gta-6-content-creators-streamers-youtubers-the-viral-wait';

-- Driving physics EN (535 impr, 0,37% CTR, pos 8.7)
UPDATE articles SET
  meta_title = 'GTA 6 Driving Physics: RAGE Engine Grip & Suspension Explained',
  meta_description = 'How GTA 6 driving really feels: the RAGE engine''s new suspension, grip and handling revealed frame-by-frame. Everything drivers need to know, 2026.'
WHERE slug = 'gta-6-driving-physics-revealed-suspension-grip-rage-engine-mechanics';

-- Aircraft EN (497 impr, pos 9.1)
UPDATE articles SET
  meta_title = 'GTA 6 Aircraft: Every Helicopter, Jet & Drone in Leonida',
  meta_description = 'The complete GTA 6 aircraft guide — every helicopter, jet and drone spotted in Leonida, what they do and how flying works. Updated 2026.'
WHERE slug = 'gta-6-aircraft-guide-all-helicopters-jets-drones-of-leonida-explained';

-- Supercars / hypercars EN (241 impr, pos 19) — requêtes « gta 6 hypercar »
UPDATE articles SET
  meta_title = 'GTA 6 Supercars & Hypercars: Every Fast Car Identified (2026)',
  meta_description = 'Every supercar and hypercar in the GTA 6 trailers, identified and broken down — real-world inspirations, top performers and what to expect. 2026.'
WHERE slug = 'gta-6-supercars-identified-complete-breakdown-of-trailers-high-performance-vehicles';

-- Nine1Nine nightclub EN (310 impr, pos 8.8) — requêtes « nine1nine »
UPDATE articles SET
  meta_title = 'Nine1Nine: GTA 6''s Premier Vice City Nightclub Explained',
  meta_description = 'Nine1Nine, the neon nightclub at the heart of GTA 6''s Vice City music scene — location, DJs, the soundtrack and everything we know. 2026.'
WHERE slug = 'nine1nine-vice-city-s-premier-nightclub-at-the-heart-of-gta-6-s-music-scene';

-- PS5 vs Xbox EN (201 impr, pos 9.6)
UPDATE articles SET
  meta_title = 'GTA 6: PS5 vs Xbox Series X — Which Runs It Best? (2026)',
  meta_description = 'PS5 vs Xbox Series X for GTA 6: performance, load times and the technical verdict. Which console should you play GTA 6 on? Full comparison, 2026.'
WHERE slug = 'ps5-vs-xbox-series-x-for-gta-6-performance-load-times-technical-verdict';

-- Customization EN (248 impr)
UPDATE articles SET
  meta_title = 'GTA 6 Customization: Clothes, Tattoos & Car Tuning Guide',
  meta_description = 'GTA 6 customization explained — unlimited outfits, tattoos and deep vehicle tuning. Everything you can personalize in Vice City, updated 2026.'
WHERE slug = 'gta-6-customization-system-unlimited-clothes-tattoos-vehicle-tuning';

-- Replay GTA 5 EN (249 impr)
UPDATE articles SET
  meta_title = 'Should You Replay GTA 5 While Waiting for GTA 6? (2026)',
  meta_description = 'Replaying GTA 5 before GTA 6? What still holds up, what changes, and how to prep for Vice City. The honest verdict for the wait. Updated 2026.'
WHERE slug = 'should-you-replay-gta-5-while-waiting-for-gta-6-here-s-what-changes';

-- Musique / radios EN (130 impr)
UPDATE articles SET
  meta_title = 'GTA 6 Radio Stations & Soundtrack: V-Rock, Synthwave & More',
  meta_description = 'GTA 6 music revealed — radio stations, V-Rock, synthwave and the full Vice City soundtrack we know so far. Every confirmed track and artist, 2026.'
WHERE slug = 'gta-6-music-radio-stations-v-rock-synthwave-and-vice-city-s-complete-soundtrack';

-- Co-op FR (238 impr, pos 8.3) — on garde la VF (langue différente = pas un doublon)
UPDATE articles SET
  meta_title = 'GTA 6 en coop : le multijoueur est-il confirmé ? (2026)',
  meta_description = 'GTA 6 aura-t-il un mode coop ? Tout ce qu''on sait vraiment sur le multijoueur et le jeu à deux dans Vice City : faits confirmés vs rumeurs. Maj 2026.'
WHERE slug = 'multijoueur-cooperatif-gta-6-ce-qu-il-faut-savoir-sur-le-mode-coop';

-- Vérification (facultatif) :
-- SELECT slug, meta_title FROM articles WHERE meta_title LIKE '%2026%' ORDER BY id DESC LIMIT 20;
