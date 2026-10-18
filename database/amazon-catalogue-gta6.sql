-- =====================================================================
-- ViceHub X — CATALOGUE AMAZON « spécial GTA 6 » : 50 PRODUITS PRÉCIS
-- =====================================================================
-- 50 produits sélectionnés (marque + modèle exact) pour les joueurs de GTA 6 :
-- consoles, manettes, audio, STOCKAGE, écrans/TV, setup, jeux, cartes & goodies.
--
-- ⚠️ TON TAG : ne le mets PAS dans les URL — le site l'ajoute AUTOMATIQUEMENT au
--    rendu (Admin → Réglages → « Tag Amazon », ex. vicehubx-21). Sans tag = pas
--    de commission. Les liens pointent vers amazon.fr (ton compte est FR).
--
-- 🔎 Chaque lien cible le produit par son NOM EXACT (marque + modèle) → il tombe
--    sur le bon produit, toujours en stock. Pour transformer un lien en page
--    produit unique (/dp/…), va dans Admin → « 🛒 Deals Amazon », ouvre le produit
--    sur Amazon, copie son lien et colle-le : c'est fait en 1 clic, sans SQL.
--
-- À exécuter UNE fois dans phpMyAdmin (onglet SQL). Idempotent (remplace la
-- sélection Amazon). ⚠️ Sélectionne TA BASE à gauche AVANT d'exécuter.
-- « Résultat vide (aucune ligne) » = SUCCÈS.
-- =====================================================================

-- Colonne d'ordre d'affichage (MariaDB : IF NOT EXISTS supporté).
ALTER TABLE affiliate_links ADD COLUMN IF NOT EXISTS sort INT NOT NULL DEFAULT 0;

-- Nettoie les placeholders + l'ancienne sélection Amazon (anti-doublon) ; préserve
-- tout autre lien d'affiliation ajouté à la main (hors amazon).
DELETE FROM affiliate_links WHERE url LIKE '%example.com%';
DELETE FROM affiliate_links WHERE url LIKE '%amazon.%/s?k=%';

INSERT INTO affiliate_links (title, description, url, platform, badge, active, sort) VALUES
-- ===== CONSOLES =====
('Sony PlayStation 5 Slim (Standard)',       'La console de référence pour jouer à GTA 6 en 4K dès le jour J, avec lecteur de disque.',                 'https://www.amazon.fr/s?k=PlayStation+5+Slim+console+standard', 'Amazon', 'Console',      1, 10),
('Sony PlayStation 5 Pro',                   'La PS5 la plus puissante : le meilleur rendu possible pour GTA 6.',                                        'https://www.amazon.fr/s?k=PlayStation+5+Pro+console',          'Amazon', 'Premium',      1, 11),
('Sony PlayStation 5 Slim Digital',          'La PS5 sans lecteur, moins chère, pour jouer à GTA 6 en version dématérialisée.',                          'https://www.amazon.fr/s?k=PlayStation+5+Slim+Digital+Edition', 'Amazon', 'Console',      1, 12),
('Microsoft Xbox Series X (1 To)',           'La console next-gen Microsoft pour GTA 6 : 4K, SSD ultra-rapide.',                                         'https://www.amazon.fr/s?k=Xbox+Series+X+1To+console',          'Amazon', 'Console',      1, 13),
('Microsoft Xbox Series S (1 To)',           'La console next-gen la moins chère pour découvrir GTA 6 sans se ruiner.',                                  'https://www.amazon.fr/s?k=Xbox+Series+S+1To',                  'Amazon', 'Petit prix',   1, 14),
-- ===== MANETTES =====
('Manette Sony DualSense (PS5)',             'Manette de rechange PS5 : retour haptique et gâchettes adaptatives pour vivre Vice City.',                 'https://www.amazon.fr/s?k=manette+DualSense+PS5+blanche',      'Amazon', 'Manette',      1, 20),
('Manette Sony DualSense Edge',              'La manette pro PS5 : palettes arrière + profils personnalisés pour dominer GTA 6 Online.',                 'https://www.amazon.fr/s?k=manette+DualSense+Edge',             'Amazon', 'Pro',          1, 21),
('Manette DualSense Midnight Black',         'La DualSense en noir profond, pour un setup PS5 élégant.',                                                 'https://www.amazon.fr/s?k=manette+DualSense+Midnight+Black',   'Amazon', 'Manette',      1, 22),
('Manette Xbox sans fil Carbon Black',       'Manette Xbox ergonomique (Series X|S et PC) pour jouer à GTA 6 sans fil.',                                 'https://www.amazon.fr/s?k=manette+Xbox+sans+fil+Carbon+Black', 'Amazon', 'Manette',      1, 23),
('Manette Xbox Elite Series 2',              'La manette pro Xbox : palettes, gâchettes réglables, idéale GTA 6 Online.',                                'https://www.amazon.fr/s?k=manette+Xbox+Elite+Series+2',        'Amazon', 'Pro',          1, 24),
('Station de charge DualSense (Sony)',        'Recharge 2 manettes DualSense à la fois : fini la batterie à plat en pleine mission.',                     'https://www.amazon.fr/s?k=station+de+charge+DualSense+Sony',   'Amazon', 'Accessoire',   1, 25),
-- ===== AUDIO =====
('Casque Sony PULSE 3D (PS5)',               'Le son 3D officiel PS5 pour s''immerger dans la bande-son synthwave de Vice City.',                        'https://www.amazon.fr/s?k=casque+Sony+PULSE+3D+PS5',          'Amazon', 'Audio',        1, 30),
('Casque Sony PULSE Elite (PS5)',            'Le casque haut de gamme PlayStation : audio premium et micro rétractable pour GTA 6.',                     'https://www.amazon.fr/s?k=casque+Sony+PULSE+Elite',           'Amazon', 'Audio',        1, 31),
('Écouteurs Sony PULSE Explore (PS5)',        'Les écouteurs true wireless PlayStation pour jouer à GTA 6 partout.',                                      'https://www.amazon.fr/s?k=Sony+PULSE+Explore',                'Amazon', 'Audio',        1, 32),
('Casque SteelSeries Arctis Nova 7',         'Un casque sans fil primé : son précis et confort longue session pour GTA 6.',                              'https://www.amazon.fr/s?k=SteelSeries+Arctis+Nova+7',         'Amazon', 'Audio',        1, 33),
('Casque HyperX Cloud III',                  'Confort légendaire et micro clair à prix juste pour GTA 6 Online.',                                        'https://www.amazon.fr/s?k=casque+HyperX+Cloud+III',           'Amazon', 'Audio',        1, 34),
('Casque Razer Kraken V3',                   'Audio immersif et style gamer pour vivre les poursuites de Vice City.',                                    'https://www.amazon.fr/s?k=casque+Razer+Kraken+V3',            'Amazon', 'Audio',        1, 35),
('Micro streaming HyperX QuadCast',          'Le micro parfait pour streamer tes parties GTA 6 avec un son pro.',                                        'https://www.amazon.fr/s?k=micro+HyperX+QuadCast',             'Amazon', 'Streaming',    1, 36),
-- ===== STOCKAGE (GTA 6 sera lourd) =====
('SSD WD_BLACK SN850P 1 To (PS5)',           'Le SSD recommandé pour PS5, avec dissipateur : installe GTA 6 sans jamais manquer de place.',              'https://www.amazon.fr/s?k=SSD+WD+BLACK+SN850P+1To+PS5',       'Amazon', 'Stockage',     1, 40),
('SSD WD_BLACK SN850P 2 To (PS5)',           'Encore plus d''espace pour GTA 6 + tes autres jeux. Compatible PS5, dissipateur inclus.',                  'https://www.amazon.fr/s?k=SSD+WD+BLACK+SN850P+2To+PS5',       'Amazon', 'Stockage',     1, 41),
('SSD Samsung 990 Pro 2 To (dissipateur)',   'SSD NVMe ultra-rapide avec dissipateur, parfait pour étendre ta PS5 avant GTA 6.',                         'https://www.amazon.fr/s?k=SSD+Samsung+990+Pro+2To+dissipateur','Amazon', 'Stockage',     1, 42),
('Carte extension Seagate 1 To (Xbox)',       'Double le stockage de ta Xbox Series X|S en un clic pour installer GTA 6.',                                 'https://www.amazon.fr/s?k=carte+extension+Seagate+1To+Xbox+Series','Amazon','Stockage',    1, 43),
('SSD externe Samsung T7 Shield 2 To',       'Archive tes jeux et garde de la place pour GTA 6. Rapide, robuste, compact.',                              'https://www.amazon.fr/s?k=SSD+externe+Samsung+T7+Shield+2To', 'Amazon', 'Stockage',     1, 44),
-- ===== ÉCRANS & TV =====
('TV LG OLED C4 48" (4K 120Hz)',             'L''OLED idéale pour GTA 6 : noirs parfaits, 120Hz, HDMI 2.1 pour PS5/Xbox.',                                'https://www.amazon.fr/s?k=TV+LG+OLED+C4+48+pouces',           'Amazon', 'Écran',        1, 50),
('TV Sony Bravia 55" (4K 120Hz)',            'Grand écran 4K 120Hz pour vivre Vice City en grand, optimisé PS5.',                                        'https://www.amazon.fr/s?k=TV+Sony+Bravia+55+4K+120Hz',        'Amazon', 'Écran',        1, 51),
('Écran LG UltraGear 27" 4K 144Hz',          'Moniteur gaming 4K ultra-fluide pour GTA 6 sur PC/console.',                                               'https://www.amazon.fr/s?k=ecran+LG+UltraGear+27+4K+144Hz',    'Amazon', 'Écran',        1, 52),
('Écran Samsung Odyssey G7 27"',             'Dalle incurvée haute fréquence pour une immersion totale dans Leonida.',                                   'https://www.amazon.fr/s?k=ecran+Samsung+Odyssey+G7+27',       'Amazon', 'Écran',        1, 53),
-- ===== SETUP & CONFORT =====
('Chaise gaming Secretlab Titan Evo',        'La référence du confort pour enchaîner les heures sur GTA 6 sans mal de dos.',                             'https://www.amazon.fr/s?k=chaise+gaming+Secretlab+Titan+Evo', 'Amazon', 'Setup',        1, 60),
('Chaise gaming Noblechairs Hero',           'Assise premium et maintien parfait pour les longues sessions Vice City.',                                  'https://www.amazon.fr/s?k=chaise+gaming+Noblechairs+Hero',    'Amazon', 'Setup',        1, 61),
('Bureau gaming assis-debout',               'Un bureau réglable pour un setup GTA 6 sain et pro.',                                                      'https://www.amazon.fr/s?k=bureau+gaming+assis+debout',        'Amazon', 'Setup',        1, 62),
('Tapis de souris XXL Razer Gigantus V2',    'Grand tapis pour un setup propre façon Vice City (confort clavier/souris).',                               'https://www.amazon.fr/s?k=tapis+souris+Razer+Gigantus+V2+XXL','Amazon', 'Setup',        1, 63),
('Support casque gaming RGB',                'Range et éclaire ton casque : la touche néon qui fait le setup GTA 6.',                                    'https://www.amazon.fr/s?k=support+casque+gaming+RGB',         'Amazon', 'Accessoire',   1, 64),
('Ruban LED Govee RGBIC (déco néon)',        'Recrée l''ambiance néon de Vice City dans ta chambre ou ton setup.',                                       'https://www.amazon.fr/s?k=ruban+LED+Govee+RGBIC',             'Amazon', 'Déco',         1, 65),
('Enseigne néon LED « Vice »',               'Une enseigne néon pour une déco 100% Vice City synthwave.',                                                'https://www.amazon.fr/s?k=enseigne+neon+LED+Vice+City',       'Amazon', 'Déco',         1, 66),
-- ===== JEUX, CARTES & ABONNEMENTS =====
('GTA V (PS5)',                              'En attendant GTA 6 : (re)joue à GTA V nouvelle génération.',                                               'https://www.amazon.fr/s?k=GTA+V+PS5',                         'Amazon', 'Jeu',          1, 70),
('GTA : The Trilogy — Definitive Edition',   'Revis Vice City, San Andreas et Liberty City avant GTA 6.',                                                'https://www.amazon.fr/s?k=GTA+Trilogy+Definitive+Edition',    'Amazon', 'Jeu',          1, 71),
('Red Dead Redemption 2',                    'Le chef-d''œuvre Rockstar à (re)découvrir avant GTA 6.',                                                   'https://www.amazon.fr/s?k=Red+Dead+Redemption+2+PS4',         'Amazon', 'Jeu',          1, 72),
('Carte PlayStation Store 50 €',             'Recharge ton compte PSN pour précommander GTA 6. Livrée par e-mail.',                                      'https://www.amazon.fr/s?k=carte+PlayStation+Store+50+euros',  'Amazon', 'Carte cadeau', 1, 73),
('Carte PlayStation Store 25 €',             'Du crédit PSN pour GTA 6 et les bonus, livré par e-mail.',                                                 'https://www.amazon.fr/s?k=carte+PlayStation+Store+25+euros',  'Amazon', 'Carte cadeau', 1, 74),
('Abonnement PlayStation Plus 12 mois',      'Indispensable pour GTA 6 Online sur PS5 : multijoueur + avantages.',                                       'https://www.amazon.fr/s?k=PlayStation+Plus+12+mois',          'Amazon', 'Abonnement',   1, 75),
('Carte cadeau Xbox 50 €',                   'Du crédit Xbox pour GTA 6 et le Store, livré par e-mail.',                                                 'https://www.amazon.fr/s?k=carte+cadeau+Xbox+50+euros',        'Amazon', 'Carte cadeau', 1, 76),
('Xbox Game Pass Ultimate (3 mois)',         'Des centaines de jeux + le multijoueur Xbox en attendant GTA 6.',                                          'https://www.amazon.fr/s?k=Xbox+Game+Pass+Ultimate+3+mois',    'Amazon', 'Abonnement',   1, 77),
-- ===== GOODIES, MERCH & DÉCO =====
('Sweat à capuche GTA',                      'Porte les couleurs de Vice City : hoodie confortable inspiré de GTA.',                                     'https://www.amazon.fr/s?k=sweat+capuche+GTA',                 'Amazon', 'Goodies',      1, 80),
('T-shirt Vice City rétro',                  'T-shirt néon années 80 pour afficher ta passion GTA.',                                                     'https://www.amazon.fr/s?k=t-shirt+Vice+City+retro',           'Amazon', 'Goodies',      1, 81),
('Casquette GTA',                            'La casquette qui complète ton style Vice City.',                                                           'https://www.amazon.fr/s?k=casquette+GTA',                     'Amazon', 'Goodies',      1, 82),
('Figurine Funko Pop GTA',                   'La figurine collector à poser sur ton bureau gaming.',                                                     'https://www.amazon.fr/s?k=Funko+Pop+GTA',                     'Amazon', 'Collector',    1, 83),
('Poster encadré néon Vice City',            'Une affiche néon encadrée pour décorer ton mur gaming.',                                                   'https://www.amazon.fr/s?k=poster+encadre+Vice+City+neon',     'Amazon', 'Déco',         1, 84),
('Mug GTA',                                  'Le mug GTA pour tes sessions nocturnes à Vice City.',                                                      'https://www.amazon.fr/s?k=mug+GTA',                           'Amazon', 'Goodies',      1, 85),
('Plaid / couverture gaming',                'La couverture douce pour les longues soirées GTA 6.',                                                      'https://www.amazon.fr/s?k=plaid+gaming+couverture',           'Amazon', 'Goodies',      1, 86),
('Façade / coque PS5 colorée',               'Personnalise ta PS5 avec des façades aux couleurs de Vice City.',                                          'https://www.amazon.fr/s?k=facade+PS5+coque+couleur',          'Amazon', 'Accessoire',   1, 87);

-- Vérification (optionnel) :
-- SELECT COUNT(*) AS total FROM affiliate_links WHERE active=1;
-- SELECT title, badge, sort FROM affiliate_links WHERE active=1 ORDER BY sort, id;
