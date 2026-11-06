-- ViceHub X — Ralentissement de la publication IA (2026-11-06)
-- =====================================================================
-- Objectif : sortir du filtre Google « scaled content abuse » (contenu IA
-- produit en masse) qui bloque l'indexation (~5 700 pages non indexées).
-- On publie PEU mais MIEUX, le temps que Google reprenne confiance.
--
-- À coller tel quel dans phpMyAdmin (base ViceHub) → onglet SQL → Exécuter.
-- Réversible : il suffira de remonter ces valeurs plus tard dans l'admin.
-- =====================================================================

INSERT INTO settings (`key`, value) VALUES
  ('veille_auto_max',  '1'),   -- veille auto : 1 article/jour max (au lieu de 3)
  ('ai_auto_interval', '48'),  -- génération auto : 1 article toutes les 48 h (au lieu de 6 h)
  ('ai_tr_daily_max',  '2')    -- traductions EN auto : 2/jour max (au lieu de « tout le backlog »)
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- --- OPTION « PAUSE TOTALE » (à n'utiliser que si tu veux stopper net) -----
-- Décommente les lignes ci-dessous pour mettre toute la génération IA en pause.
-- La traduction du backlog EN restera, elle, plafonnée à 2/jour.
--
-- UPDATE settings SET value = '0' WHERE `key` = 'ai_auto_enabled';
-- UPDATE settings SET value = '0' WHERE `key` = 'veille_auto_max';
