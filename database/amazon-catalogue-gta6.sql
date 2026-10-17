-- =====================================================================
-- ViceHub X — CATALOGUE AMAZON complet (affiliation) pour la page Bons plans
-- =====================================================================
-- Sélection pro « spécial GTA 6 » : consoles, manettes, audio, STOCKAGE (le jeu
-- sera lourd !), setup, cartes cadeaux, jeux et goodies / déco Vice City.
--
-- ⚠️ NE PAS mettre ton tag d'affiliation dans les URL : le site l'ajoute TOUT SEUL
--    au rendu, depuis Admin → Réglages → « Tag Amazon » (ex. vicehubx-21).
--    Renseigne ce tag et chaque clic→achat te rapporte une commission (sans
--    surcoût pour le visiteur). Les liens pointent vers amazon.fr (ton tag est FR).
--
-- À exécuter UNE fois dans phpMyAdmin (onglet SQL). Idempotent (remplace la
-- sélection Amazon). ⚠️ Sélectionne TA BASE à gauche AVANT d'exécuter.
-- « Résultat vide (aucune ligne) » = SUCCÈS.
-- =====================================================================

-- Nettoie les placeholders de démo + l'ancienne sélection Amazon (anti-doublon).
-- Préserve tout autre lien d'affiliation ajouté à la main (non-amazon).
DELETE FROM affiliate_links WHERE url LIKE '%example.com%';
DELETE FROM affiliate_links WHERE url LIKE '%amazon.%/s?k=%';
DELETE FROM affiliate_links WHERE url LIKE '%amazon.%/dp/%';

INSERT INTO affiliate_links (title, description, url, platform, badge, active) VALUES
-- ===== CONSOLES =====
('PlayStation 5 (modèle Slim)',        'La console idéale pour jouer à GTA 6 en 4K dès le jour J. Vérifie le prix et le stock du jour.',                 'https://www.amazon.fr/s?k=PlayStation+5+console+slim', 'Amazon', 'Console',      1),
('PlayStation 5 Pro',                  'La version la plus puissante pour profiter de GTA 6 avec le meilleur rendu possible.',                           'https://www.amazon.fr/s?k=PlayStation+5+Pro',          'Amazon', 'Premium',      1),
('Xbox Series X',                      'L''autre console next-gen pour GTA 6 : 4K, SSD rapide. Compare les offres du moment.',                          'https://www.amazon.fr/s?k=Xbox+Series+X+console',      'Amazon', 'Console',      1),
('Xbox Series S',                      'La console next-gen la moins chère pour découvrir GTA 6 sans se ruiner.',                                        'https://www.amazon.fr/s?k=Xbox+Series+S',              'Amazon', 'Petit prix',   1),

-- ===== MANETTES =====
('Manette DualSense (PS5)',            'Une manette de rechange pour les longues sessions à Vice City (retour haptique, gâchettes adaptatives).',       'https://www.amazon.fr/s?k=manette+DualSense+PS5',      'Amazon', 'Manette',      1),
('Manette DualSense Edge (pro)',       'La manette pro PS5 : palettes arrière et réglages personnalisés pour dominer GTA 6 Online.',                     'https://www.amazon.fr/s?k=DualSense+Edge',             'Amazon', 'Pro',          1),
('Manette Xbox sans fil',              'Manette Xbox ergonomique, compatible Series X|S et PC, pour jouer à GTA 6 sans fil.',                            'https://www.amazon.fr/s?k=manette+Xbox+sans+fil',      'Amazon', 'Manette',      1),

-- ===== AUDIO =====
('Casque PULSE 3D (PS5)',              'Le son 3D officiel PS5 pour s''immerger dans la bande-son synthwave de Vice City.',                              'https://www.amazon.fr/s?k=casque+PULSE+3D+PS5',        'Amazon', 'Audio',        1),
('Casque gaming sans fil',             'Confort longue durée + micro clair pour GTA 6 Online. Marques HyperX, SteelSeries, Razer…',                      'https://www.amazon.fr/s?k=casque+gaming+sans+fil',     'Amazon', 'Audio',        1),

-- ===== STOCKAGE (le jeu + le patch day-one seront lourds) =====
('SSD NVMe pour PS5 (avec dissipateur)','GTA 6 va prendre beaucoup de place : ajoute un SSD NVMe PS5 (WD Black, Samsung…) pour ne jamais manquer d''espace.','https://www.amazon.fr/s?k=SSD+NVMe+PS5+dissipateur',    'Amazon', 'Stockage',     1),
('Carte d''extension Xbox Series X|S', 'Double le stockage de ta Xbox en un clic pour installer GTA 6 et ton patch day-one.',                            'https://www.amazon.fr/s?k=carte+extension+Xbox+Series', 'Amazon', 'Stockage',    1),
('SSD externe gaming',                 'Pour archiver tes autres jeux et garder de la place pour GTA 6 le jour de la sortie.',                          'https://www.amazon.fr/s?k=SSD+externe+gaming',         'Amazon', 'Stockage',     1),

-- ===== SETUP & CONFORT =====
('Chaise gaming',                      'Le confort qu''il faut pour vivre GTA 6 des heures durant, sans mal de dos.',                                    'https://www.amazon.fr/s?k=chaise+gaming',              'Amazon', 'Setup',        1),
('Écran 4K 120 Hz',                    'Un écran next-gen pour voir GTA 6 en pleine résolution et en fluidité maximale.',                                'https://www.amazon.fr/s?k=ecran+gaming+4K+120Hz',      'Amazon', 'Écran',        1),
('Tapis de souris XXL',                'Grand tapis pour un setup propre façon Vice City (PC / confort bureau).',                                       'https://www.amazon.fr/s?k=tapis+souris+XXL+gaming',    'Amazon', 'Setup',        1),
('Ruban LED néon (déco Vice City)',    'Recrée l''ambiance néon de Vice City dans ta chambre / ton setup gaming.',                                       'https://www.amazon.fr/s?k=ruban+LED+neon+gaming+RGB',  'Amazon', 'Déco',         1),

-- ===== CARTES CADEAUX & ABONNEMENTS =====
('Carte PlayStation Store (PSN)',      'Recharge ton compte PSN pour précommander GTA 6 ou acheter des bonus. Livrée par e-mail.',                       'https://www.amazon.fr/s?k=carte+PlayStation+Store',    'Amazon', 'Carte cadeau', 1),
('Carte cadeau Xbox',                  'Du crédit Xbox pour GTA 6 et le Store, livré par e-mail en quelques minutes.',                                   'https://www.amazon.fr/s?k=carte+cadeau+Xbox',          'Amazon', 'Carte cadeau', 1),
('Abonnement PlayStation Plus',        'Indispensable pour GTA 6 Online sur PS5 : multijoueur, avantages et jeux offerts.',                              'https://www.amazon.fr/s?k=PlayStation+Plus+abonnement', 'Amazon', 'Abonnement',  1),
('Abonnement Xbox Game Pass',          'Des centaines de jeux en attendant GTA 6, et le multijoueur sur Xbox.',                                          'https://www.amazon.fr/s?k=Xbox+Game+Pass',             'Amazon', 'Abonnement',   1),

-- ===== JEUX (en attendant GTA 6 / culture Rockstar) =====
('GTA V (PS5 / Xbox)',                 'En attendant GTA 6 : (re)joue à GTA V nouvelle génération au meilleur prix.',                                    'https://www.amazon.fr/s?k=GTA+V+PS5',                  'Amazon', 'Jeu',          1),
('GTA : The Trilogy — Definitive',     'Revis Vice City, San Andreas et Liberty City avant GTA 6 avec la Trilogy remasterisée.',                        'https://www.amazon.fr/s?k=GTA+Trilogy+Definitive+Edition', 'Amazon', 'Jeu',       1),
('Red Dead Redemption 2',              'Le chef-d''œuvre Rockstar à (re)découvrir en attendant GTA 6.',                                                  'https://www.amazon.fr/s?k=Red+Dead+Redemption+2',      'Amazon', 'Jeu',          1),

-- ===== GOODIES & MERCH (GTA / Vice City) =====
('Sweat à capuche GTA',                'Porte les couleurs de Vice City : sweats et hoodies inspirés de GTA.',                                           'https://www.amazon.fr/s?k=sweat+GTA+hoodie',           'Amazon', 'Goodies',      1),
('T-shirt Vice City',                  'T-shirts rétro néon Vice City / GTA pour afficher ta passion.',                                                  'https://www.amazon.fr/s?k=t-shirt+Vice+City+GTA',      'Amazon', 'Goodies',      1),
('Casquette GTA',                      'La casquette parfaite pour compléter ton style Vice City.',                                                      'https://www.amazon.fr/s?k=casquette+GTA',              'Amazon', 'Goodies',      1),
('Figurines & collectibles GTA',       'Figurines, statues et objets de collection GTA pour les vrais fans.',                                            'https://www.amazon.fr/s?k=figurine+GTA+collector',     'Amazon', 'Collector',    1),
('Poster / affiche Vice City',         'Affiches néon et posters Vice City pour décorer ton mur gaming.',                                                'https://www.amazon.fr/s?k=poster+Vice+City+neon',      'Amazon', 'Déco',         1),
('Mug GTA',                            'Le mug GTA pour tes sessions nocturnes à Vice City.',                                                            'https://www.amazon.fr/s?k=mug+GTA',                    'Amazon', 'Goodies',      1);

-- Vérification (optionnel) :
-- SELECT COUNT(*) AS total, platform FROM affiliate_links WHERE active=1 GROUP BY platform;
-- SELECT title, platform, badge FROM affiliate_links WHERE active=1 ORDER BY id;
