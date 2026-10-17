-- ViceHub X — Correctif CTR ciblé (Search Console 17 oct. 2026)
-- Constat : 9 468 impressions / 28 j, mais CTR 0,77 % seulement. 699 pages en
-- page 1 (pos <= 10) — le problème n'est PLUS la visibilité mais le CLIC.
-- Ces 11 pages cumulent des centaines d'impressions à ~0 % de CTR (pos 7-9).
-- On réécrit titre + meta en « aimant à clic » : mot-clé en premier, chiffre/année,
-- bénéfice clair, mini appel à l'action. À coller dans phpMyAdmin (SQL). Idempotent.
-- ⚠️ Pense à sélectionner TA BASE dans la colonne de gauche AVANT d'exécuter.

-- 1) Storage (900 impr, 0 clic !) — la plus grosse fuite du site
UPDATE articles SET
  meta_title = 'GTA 6 Install Size: How Many GB on PS5 & Xbox? (2026)',
  meta_description = 'How much storage does GTA 6 need on PS5 and Xbox Series X|S? The expected install size in GB, how much free space to clear now, and SSD tips for launch day.'
WHERE slug = 'gta-6-storage-requirements-space-needed-on-ps5-xbox-series-x-s';

-- 2) Co-op (554 impr)
UPDATE articles SET
  meta_title = 'GTA 6 Co-op: Is 2-Player Mode Confirmed? (2026)',
  meta_description = 'Will GTA 6 have co-op? Everything we actually know about 2-player and online gameplay in Leonida: confirmed facts, leaks and what Rockstar has hinted so far.'
WHERE slug = 'gta-6-co-op-what-we-actually-know-about-two-player-gameplay';

-- 3) Character customization (312 impr)
UPDATE articles SET
  meta_title = 'GTA 6 Character Customization: Skins, Clothes & Tattoos',
  meta_description = 'How deep is GTA 6 character customization? Jason and Lucia skins, clothes, tattoos and everything known about personalizing your Vice City look in 2026.'
WHERE slug = 'gta-6-character-customization-jason-lucia-skins-clothes-tattoos-decoded';

-- 4) Aircraft (216 impr)
UPDATE articles SET
  meta_title = 'GTA 6 Aircraft: All Helicopters, Jets & Drones (2026)',
  meta_description = 'Every GTA 6 aircraft decoded: helicopters, jets, seaplanes and drones spotted across Leonida. The full flying-vehicle list from the trailers, explained.'
WHERE slug = 'gta-6-aircraft-guide-all-helicopters-jets-drones-of-leonida-explained';

-- 5) Replay GTA 5 (155 impr)
UPDATE articles SET
  meta_title = 'Should You Replay GTA 5 Before GTA 6? Our Verdict',
  meta_description = 'Is it worth replaying GTA 5 while waiting for GTA 6? What still holds up, what feels dated, and how to prepare for Vice City 2026 — our honest take.'
WHERE slug = 'should-you-replay-gta-5-while-waiting-for-gta-6-here-s-what-changes';

-- 6) Vice City 40 ans (152 impr)
UPDATE articles SET
  meta_title = 'Vice City 1986 to 2026: 40 Years of GTA''s Iconic City',
  meta_description = 'From GTA Vice City (1986) to GTA 6 (2026): 40 years of Vice City''s transformation, map evolution and 80s identity — how Rockstar rebuilt its iconic city.'
WHERE slug = 'vice-city-1986-2026-forty-years-of-urban-transformation-between-gta-iii-and-gta-vi';

-- 7) RAGE engine graphics (136 impr)
UPDATE articles SET
  meta_title = 'GTA 6 RAGE Engine: Graphics, Physics & Next-Gen Power',
  meta_description = 'Inside GTA 6''s RAGE engine: next-gen graphics, physics and performance on PS5 and Xbox. How Rockstar pushes visuals further than any game before in 2026.'
WHERE slug = 'rage-engine-gta-6-graphics-architecture-physics-next-gen-performance';

-- 8) Driving physics (134 impr)
UPDATE articles SET
  meta_title = 'GTA 6 Driving Physics: Suspension, Grip & Car Feel',
  meta_description = 'GTA 6 driving physics revealed: realistic suspension, grip and RAGE engine car handling spotted in the trailers. How driving feels different from GTA 5.'
WHERE slug = 'gta-6-driving-physics-revealed-suspension-grip-rage-engine-mechanics';

-- 9) Content creators / streamers (132 impr)
UPDATE articles SET
  meta_title = 'GTA 6 & Streamers: The Biggest Twitch Launch Ever?',
  meta_description = 'How GTA 6 will break Twitch and YouTube: streamers, creators and the viral wait before launch. Why 2026 could be the biggest gaming moment ever online.'
WHERE slug = 'gta-6-content-creators-streamers-youtubers-the-viral-wait';

-- 10) Neon & synthwave (100 impr, pos 7.0 — presque le TOP 5)
UPDATE articles SET
  meta_title = 'GTA 6 Neon & Synthwave: Vice City''s 80s Aesthetic',
  meta_description = 'How GTA 6 nails the 80s: neon, synthwave and the retro Vice City aesthetic Rockstar rebuilt for 2026. A deep dive into the colors, light and mood of Leonida.'
WHERE slug = 'neon-and-synthwave-aesthetics-how-vice-city-1986-redefines-the-80s-vibe-in-gta-6';

-- 11) Pre-order guide (93 impr)
UPDATE articles SET
  meta_title = 'GTA 6 Pre-order Guide: Prices, Editions & Best Deals 2026',
  meta_description = 'GTA 6 pre-order guide: every edition, a clear price comparison and the best deals to lock in the Vintage Vice City Pack. Where to pre-order GTA 6 in 2026.'
WHERE slug = 'gta-6-preorder-guide-complete-price-comparison-best-deals-2025';
