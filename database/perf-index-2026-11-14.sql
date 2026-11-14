-- ViceHub X — Index de performance (2026-11-14)
-- =====================================================================
-- Objectif : baisser le temps de réponse serveur (444 ms dans la Search Console).
-- Les listes d'articles (News, Guides, Leaks, accueil, « À lire aussi »…) trient
-- des milliers d'articles par date. Sans index adapté, MariaDB les parcourt TOUS
-- à chaque page vue. Ces index rendent ces requêtes quasi instantanées.
--
-- À coller dans phpMyAdmin (base ViceHub) → onglet SQL → Exécuter.
-- Sans risque : n'efface rien, ne modifie aucune donnée. Peut être relancé.
-- =====================================================================

-- Listes par catégorie + langue, triées par date (News / Guides / Leaks / accueil).
CREATE INDEX IF NOT EXISTS idx_art_list_cat
    ON articles (category_id, lang, status, published_at);

-- Listes toutes catégories confondues, par langue (flux, sitemap, « À lire aussi »).
CREATE INDEX IF NOT EXISTS idx_art_list_lang
    ON articles (lang, status, published_at);

-- Publication programmée (tâche auto : status='pending' AND published_at <= NOW()).
CREATE INDEX IF NOT EXISTS idx_art_status_pub
    ON articles (status, published_at);

-- Met à jour les statistiques de l'optimiseur après la création des index.
ANALYZE TABLE articles;
