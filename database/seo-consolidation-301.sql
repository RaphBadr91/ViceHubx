-- ============================================================================
--  ViceHub X — Consolidation anti-cannibalisation (redirections 301)
--  Plusieurs articles quasi-identiques se volent le classement Google. On garde
--  1 article MAÎTRE par sujet et on redirige (301) les doublons vers lui.
--
--  DEUX FAÇONS DE FAIRE :
--   • SIMPLE & SÛRE (recommandée) : Admin → Articles → ouvrir le doublon →
--     champ « 🔀 Rediriger (301) vers le slug » → coller le slug du maître →
--     Enregistrer. Tu VOIS l'article et sa langue avant de rediriger.
--   • RAPIDE : lancer ce script dans phpMyAdmin (cPanel → phpMyAdmin → SQL).
--
--  RÈGLE D'OR : ne JAMAIS rediriger un article FR vers un EN (ni l'inverse).
--  Chaque bloc ci-dessous est verrouillé sur lang='en' et ne fait rien si le
--  maître est introuvable. VÉRIFIE les slugs (Admin → Articles) avant de lancer.
-- ============================================================================

-- Table des redirections (idempotent).
CREATE TABLE IF NOT EXISTS redirects (
    from_slug  VARCHAR(220) PRIMARY KEY,
    to_slug    VARCHAR(220) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- CLUSTER 1 — Co-op (EN) : maître = gta-6-co-op-campaign-what-we-know (191 imp)
--            doublon redirigé = gta-vi-multiplayer-cooperation…
-- ----------------------------------------------------------------------------
SET @m := (SELECT slug FROM articles WHERE slug LIKE 'gta-6-co-op-campaign-what-we-know%' AND lang='en' ORDER BY id LIMIT 1);
INSERT INTO redirects (from_slug, to_slug)
  SELECT a.slug, @m FROM articles a
  WHERE @m IS NOT NULL AND a.lang='en' AND a.slug <> @m
    AND a.slug LIKE 'gta-vi-multiplayer-cooperation%'
  ON DUPLICATE KEY UPDATE to_slug = VALUES(to_slug);
UPDATE articles SET status='draft'
  WHERE @m IS NOT NULL AND lang='en' AND slug <> @m AND slug LIKE 'gta-vi-multiplayer-cooperation%';

-- ----------------------------------------------------------------------------
-- CLUSTER 2 — PS5 vs Xbox (EN) : maître = la version « performance ».
--   ⚠️ Il existe DEUX variantes « performance » + une « technical ». Vérifie
--   d'abord dans Admin → Articles quel slug garder, puis dé-commente en
--   remplaçant les motifs par les VRAIS slugs.
-- ----------------------------------------------------------------------------
-- SET @m := (SELECT slug FROM articles WHERE slug LIKE 'ps5-vs-xbox-series-x-for-gta-6-performa%' AND lang='en' ORDER BY id LIMIT 1);
-- INSERT INTO redirects (from_slug, to_slug)
--   SELECT a.slug, @m FROM articles a
--   WHERE @m IS NOT NULL AND a.lang='en' AND a.slug <> @m
--     AND (a.slug LIKE 'ps5-vs-xbox-series-x-for-gta-6-technica%'
--       OR a.slug LIKE 'ps5-vs-xbox-series-x-gta-6-performance%')
--   ON DUPLICATE KEY UPDATE to_slug = VALUES(to_slug);
-- UPDATE articles SET status='draft'
--   WHERE @m IS NOT NULL AND lang='en' AND slug <> @m
--     AND (slug LIKE 'ps5-vs-xbox-series-x-for-gta-6-technica%'
--       OR slug LIKE 'ps5-vs-xbox-series-x-gta-6-performance%');

-- ----------------------------------------------------------------------------
-- CLUSTER 3 — Vintage Vice City Pack : 4+ variantes (FR ET EN mélangés).
--   ⚠️ À FAIRE VIA L'ADMIN (langue à vérifier au cas par cas). Garder 1 maître
--   par langue : celui qui capte « pack vintage vice city ». Rediriger le reste.
-- ----------------------------------------------------------------------------

-- Vérification finale : liste des redirections en place
-- SELECT * FROM redirects;
