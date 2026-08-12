-- ============================================================================
--  ViceHub X — Correctifs SEO : titres + meta descriptions optimisés (CTR)
--  Cible : les 7 pages à fort volume d'impressions repérées dans Search Console.
--  Objectif : tripler le CTR (0,4-1,3% → 3-5%) sur des pages déjà en page 1.
--
--  À LANCER UNE FOIS dans phpMyAdmin (cPanel → phpMyAdmin → base vicehubx → SQL).
--  Sans risque : ne touche QUE meta_title / meta_description (jamais le contenu).
--  Les WHERE ... LIKE ciblent le début du slug ; si un UPDATE affiche
--  « 0 ligne modifiée », ajuste le motif au vrai slug (Admin → Articles).
-- ============================================================================

-- 1) Garantit les colonnes SEO dédiées (idempotent).
ALTER TABLE articles ADD COLUMN IF NOT EXISTS meta_title       VARCHAR(90)  DEFAULT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS meta_description VARCHAR(200) DEFAULT NULL;

-- 2) RAGE / moteur graphique — 208 impressions, position ~18,8
UPDATE articles SET
  meta_title = 'GTA 6 Engine: Is Rockstar Using RAGE? (2026 Confirmed)',
  meta_description = 'GTA 6 runs on Rockstar''s RAGE engine — here''s what''s confirmed about GTA 6 graphics, physics and next-gen tech in 2026, and what''s still rumor.'
WHERE slug LIKE 'gta-6-graphics-rage-engine%' LIMIT 1;

-- 3) Co-op / multijoueur campagne — 191 impressions, position ~11,3
UPDATE articles SET
  meta_title = 'Is GTA 6 Campaign Co-Op? Everything We Know (2026)',
  meta_description = 'Is GTA 6''s campaign co-op or multiplayer? Here''s everything confirmed and rumored about GTA 6 co-op in 2026 — and what Rockstar has actually said.'
WHERE slug LIKE 'gta-6-co-op-campaign-what-we-know%' LIMIT 1;

-- 4) PC release date — 129 impressions, position ~52 (aussi couvert par le pilier /gta6-pc)
UPDATE articles SET
  meta_title = 'GTA 6 PC Release Date: When Is It Coming? (2026)',
  meta_description = 'GTA 6 hits PC after PS5/Xbox — here''s the expected GTA 6 PC release date, why Rockstar delays PC, and everything confirmed as of 2026.'
WHERE slug LIKE 'gta-6-pc-release-date%' LIMIT 1;

-- 5) Aircraft / hélicoptères — 69 impressions, position ~14,8
UPDATE articles SET
  meta_title = 'GTA 6 Aircraft: All Helicopters & Planes (2026 Guide)',
  meta_description = 'Every helicopter and plane spotted in GTA 6 so far — full aircraft guide with confirmed vehicles and leaks across Vice City and Leonida (2026).'
WHERE slug LIKE 'gta-6-aircraft-guide%' LIMIT 1;

-- 6) Wanted level / police — 69 impressions, position ~20,2
UPDATE articles SET
  meta_title = 'GTA 6 Wanted Level & Police: How It Works (2026)',
  meta_description = 'How GTA 6''s wanted level and police mechanics work — everything confirmed about cops, stars and evading the law in Vice City (2026 update).'
WHERE slug LIKE 'gta-6-wanted-level-police%' LIMIT 1;

-- 7) Driving physics — 61 impressions, position ~13,3 (meilleure page actuelle)
UPDATE articles SET
  meta_title = 'GTA 6 Driving Physics: What''s New & Confirmed (2026)',
  meta_description = 'GTA 6''s driving physics overhaul explained — suspension, handling and everything Rockstar changed since GTA 5, with confirmed details (2026).'
WHERE slug LIKE 'gta-6-driving-physics%' LIMIT 1;

-- 8) Content creators / streamers — 53 impressions, position ~8,5
UPDATE articles SET
  meta_title = 'GTA 6 Streamers & Creators: Who''s In? (2026)',
  meta_description = 'Which YouTubers and streamers will be in GTA 6? Everything known about content creators, cameos and the GTA 6 creator scene in 2026.'
WHERE slug LIKE 'gta-6-content-creators-streamers%' LIMIT 1;

-- Vérification : voir les lignes mises à jour
-- SELECT slug, meta_title, meta_description FROM articles WHERE meta_title IS NOT NULL;
