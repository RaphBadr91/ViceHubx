-- ViceHub X — Deals d'affiliation Amazon GTA 6 (bons plans)
-- =====================================================================
-- Remplace les liens de démo (example.com) par de vrais deals gaming GTA 6.
-- IMPORTANT : NE PAS mettre le tag d'affiliation dans les URL ci-dessous —
-- le site l'ajoute AUTOMATIQUEMENT au rendu depuis Réglages → « Tag Amazon ».
-- Renseigne donc ton tag (ex. vicehubx-21) dans l'admin, et chaque clic → vente
-- te rapporte une commission, sans surcoût pour le visiteur.
--
-- À exécuter UNE fois dans phpMyAdmin (onglet SQL). Idempotent (remplace).
-- « Résultat vide (aucune ligne) » = SUCCÈS.
-- =====================================================================

-- Nettoie les placeholders de démonstration et les deals Amazon gérés ici
-- (préserve tout autre lien d'affiliation que tu aurais ajouté à la main).
DELETE FROM affiliate_links WHERE url LIKE '%example.com%';
DELETE FROM affiliate_links WHERE url LIKE 'https://www.amazon.fr/s?k=%';

INSERT INTO affiliate_links (title, description, url, platform, badge, active) VALUES
('PlayStation 5 (Slim)',        'La console idéale pour jouer à GTA 6 en 4K le jour J. Vérifie le prix du jour.', 'https://www.amazon.fr/s?k=PlayStation+5+console',        'Amazon', 'Console',      1),
('Xbox Series X',               'L''autre console next-gen pour GTA 6. Compare les offres et le stock.',          'https://www.amazon.fr/s?k=Xbox+Series+X',                'Amazon', 'Console',      1),
('Manette DualSense PS5',       'Une manette de rechange pour les longues sessions à Vice City.',                'https://www.amazon.fr/s?k=manette+DualSense+PS5',        'Amazon', 'Manette',      1),
('Casque gaming immersif',      'Pour profiter à fond de la bande-son synthwave de GTA 6.',                       'https://www.amazon.fr/s?k=casque+gaming',                'Amazon', 'Audio',        1),
('Chaise gaming',               'Le confort qu''il faut pour vivre GTA 6 des heures durant.',                    'https://www.amazon.fr/s?k=chaise+gaming',                'Amazon', 'Setup',        1),
('GTA V (PS5 / Xbox)',          'En attendant GTA 6 : (re)joue à GTA V remasterisé au meilleur prix.',            'https://www.amazon.fr/s?k=GTA+V+PS5',                    'Amazon', 'Jeu',          1),
('Carte PlayStation Store',     'Recharge ton compte PSN pour précommander GTA 6 le moment venu.',                'https://www.amazon.fr/s?k=carte+PlayStation+Store',      'Amazon', 'Carte cadeau', 1),
('Carte Xbox',                  'Du crédit Xbox pour GTA 6 et le Store, livré par e-mail.',                       'https://www.amazon.fr/s?k=carte+cadeau+Xbox',            'Amazon', 'Carte cadeau', 1);

-- Vérification :
-- SELECT title, platform, badge FROM affiliate_links WHERE active=1 ORDER BY id;
