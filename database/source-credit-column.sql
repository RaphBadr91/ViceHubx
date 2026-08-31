-- ViceHub X — Colonne `source_credit` sur `articles`
-- =====================================================================
-- Stocke le crédit « Source : … » affiché en bas de l'illustration des
-- articles issus de la VEILLE concurrents (l'article reprend l'image de la
-- source, créditée). Le code sait fonctionner SANS cette colonne (il l'omet
-- alors de l'INSERT), mais l'appliquer ici permet d'AFFICHER le crédit et
-- d'éviter toute dépendance à un ALTER exécuté au runtime.
--
-- À exécuter UNE fois dans phpMyAdmin (onglet SQL). Idempotent.
-- Un « résultat vide (aucune ligne) » = SUCCÈS pour un ALTER.
-- =====================================================================

ALTER TABLE articles
  ADD COLUMN IF NOT EXISTS source_credit VARCHAR(120) DEFAULT NULL;

-- Vérification (doit renvoyer 1 ligne « source_credit ») :
-- SHOW COLUMNS FROM articles LIKE 'source_credit';
