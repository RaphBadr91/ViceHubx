-- =====================================================================
-- ViceHub X — CATALOGUE AMAZON « GTA 6 » — VAGUE 2 (+30 produits geek/PlayStation)
-- =====================================================================
-- S'AJOUTE aux 50 produits de amazon-catalogue-gta6.sql (ne les touche pas).
-- Cible : joueurs PS5 & geeks — conduite (volants, GTA 6 = bagnoles !), streaming,
-- VR, accessoires PS5, périphériques PC et lifestyle geek.
--
-- ⚠️ Ton tag d'affiliation est ajouté AUTOMATIQUEMENT au rendu (Réglages → Tag
--    Amazon). Ne le mets pas dans les URL. Liens amazon.fr.
--
-- À exécuter dans phpMyAdmin (onglet SQL), après avoir sélectionné TA BASE.
-- Idempotent : ne supprime QUE ses propres lignes (sort 100-199) avant réinsertion,
-- donc tu peux le relancer sans créer de doublon et sans écraser tes liens /dp/.
-- =====================================================================

ALTER TABLE affiliate_links ADD COLUMN IF NOT EXISTS sort INT NOT NULL DEFAULT 0;

-- Nettoie uniquement la vague 2 (liens « recherche » dans la plage sort 100-199).
DELETE FROM affiliate_links WHERE sort >= 100 AND sort < 200 AND url LIKE '%amazon.%/s?k=%';

INSERT INTO affiliate_links (title, description, url, platform, badge, active, sort) VALUES
-- ===== CONDUITE (GTA 6, c'est avant tout des voitures) =====
('Volant Logitech G29 (PS5/PS4)',            'Prends le volant à Vice City comme jamais : retour de force réaliste, pédalier inclus.',                   'https://www.amazon.fr/s?k=volant+Logitech+G29+PS5',           'Amazon', 'Conduite',     1, 100),
('Volant Thrustmaster T248 (PS5)',           'Volant force feedback hybride pour une conduite immersive dans GTA 6.',                                    'https://www.amazon.fr/s?k=volant+Thrustmaster+T248+PS5',      'Amazon', 'Conduite',     1, 101),
('Support volant / cockpit Playseat',        'Un support stable pour ton volant : setup course digne de Leonida.',                                       'https://www.amazon.fr/s?k=support+volant+cockpit+Playseat',   'Amazon', 'Conduite',     1, 102),
('Levier de vitesse Logitech Driving Force', 'Ajoute un vrai levier à ton volant G29 pour des courses plus réalistes.',                                  'https://www.amazon.fr/s?k=Logitech+Driving+Force+Shifter',    'Amazon', 'Conduite',     1, 103),
-- ===== STREAMING / CRÉATEUR (stream tes parties GTA 6) =====
('Elgato Stream Deck MK.2',                  'Contrôle ton stream GTA 6 en un bouton : la référence des créateurs.',                                     'https://www.amazon.fr/s?k=Elgato+Stream+Deck+MK.2',           'Amazon', 'Streaming',    1, 104),
('Carte de capture Elgato HD60 X',           'Capture et streame tes parties PS5 en 4K60 / 1080p120 sans latence.',                                      'https://www.amazon.fr/s?k=Elgato+HD60+X',                     'Amazon', 'Streaming',    1, 105),
('Webcam Logitech Brio 4K',                  'Une webcam 4K nette pour ta facecam pendant tes lives GTA 6.',                                             'https://www.amazon.fr/s?k=webcam+Logitech+Brio+4K',           'Amazon', 'Streaming',    1, 106),
('Éclairage Elgato Key Light',               'Un éclairage pro pour un stream GTA 6 qui en jette.',                                                      'https://www.amazon.fr/s?k=Elgato+Key+Light',                  'Amazon', 'Streaming',    1, 107),
('Bras articulé pour micro',                 'Positionne ton micro parfaitement pour commenter tes parties.',                                            'https://www.amazon.fr/s?k=bras+articule+micro+streaming',     'Amazon', 'Streaming',    1, 108),
-- ===== VR & PÉRIPHÉRIQUES (geeks) =====
('PlayStation VR2',                          'Le casque VR next-gen de Sony : l''immersion ultime en attendant GTA 6.',                                  'https://www.amazon.fr/s?k=PlayStation+VR2',                   'Amazon', 'VR',           1, 109),
('Casque VR Meta Quest 3',                   'La VR sans fil la plus populaire : des centaines de jeux pour les geeks.',                                 'https://www.amazon.fr/s?k=Meta+Quest+3',                      'Amazon', 'VR',           1, 110),
('Clavier mécanique Logitech G Pro',         'Un clavier mécanique réactif pour GTA 6 sur PC et le multitâche.',                                         'https://www.amazon.fr/s?k=clavier+mecanique+Logitech+G+Pro',  'Amazon', 'PC',           1, 111),
('Souris gaming Logitech G502 X',            'La souris gaming culte, précise et personnalisable, pour GTA 6 PC.',                                       'https://www.amazon.fr/s?k=souris+Logitech+G502+X',            'Amazon', 'PC',           1, 112),
-- ===== ACCESSOIRES PS5 =====
('Support vertical + ventilateur PS5',       'Pose ta PS5 à la verticale et garde-la au frais pendant GTA 6.',                                           'https://www.amazon.fr/s?k=support+vertical+ventilateur+PS5',  'Amazon', 'Accessoire',   1, 113),
('Station de refroidissement PS5',           'Refroidit ta PS5 lors des longues sessions GTA 6 Online.',                                                 'https://www.amazon.fr/s?k=station+refroidissement+PS5',       'Amazon', 'Accessoire',   1, 114),
('Télécommande multimédia PS5',              'Contrôle Netflix, YouTube & Cie sur ta PS5 sans la manette.',                                              'https://www.amazon.fr/s?k=telecommande+multimedia+PS5',       'Amazon', 'Accessoire',   1, 115),
('Caméra HD PS5',                            'La caméra officielle pour streamer tes parties GTA 6 depuis la PS5.',                                      'https://www.amazon.fr/s?k=camera+HD+PS5',                     'Amazon', 'Streaming',    1, 116),
('Housse anti-poussière PS5',                'Protège ta PS5 de la poussière entre deux sessions à Vice City.',                                          'https://www.amazon.fr/s?k=housse+anti+poussiere+PS5',         'Amazon', 'Accessoire',   1, 117),
('Grips & capuchons sticks DualSense',       'Améliore la prise en main de ta manette pour viser et conduire plus précis.',                              'https://www.amazon.fr/s?k=grips+capuchons+sticks+DualSense',  'Amazon', 'Accessoire',   1, 118),
('Câble HDMI 2.1 (4K 120Hz / 8K)',           'Le câble qu''il faut pour profiter de GTA 6 en 4K 120Hz sur ta TV.',                                       'https://www.amazon.fr/s?k=cable+HDMI+2.1+8K',                 'Amazon', 'Accessoire',   1, 119),
('Hub USB pour PS5',                         'Ajoute des ports USB à ta PS5 pour manettes, volant et stockage.',                                         'https://www.amazon.fr/s?k=hub+USB+PS5',                       'Amazon', 'Accessoire',   1, 120),
('Manette arcade / fightstick',              'Pour les fans de jeux de combat et de rétro, en attendant GTA 6.',                                         'https://www.amazon.fr/s?k=arcade+fightstick+PS5',             'Amazon', 'Manette',      1, 121),
-- ===== IMMERSION / CONFORT / LIFESTYLE GEEK =====
('Casque Sony WH-1000XM5 (anti-bruit)',      'Réduction de bruit ultime : coupe le monde et plonge dans Vice City.',                                     'https://www.amazon.fr/s?k=Sony+WH-1000XM5',                   'Amazon', 'Audio',        1, 122),
('Lunettes anti-lumière bleue gaming',       'Moins de fatigue oculaire lors des marathons GTA 6 nocturnes.',                                            'https://www.amazon.fr/s?k=lunettes+anti+lumiere+bleue+gaming','Amazon', 'Confort',      1, 123),
('Mini-frigo gaming RGB',                    'Garde tes boissons au frais à portée de main, lumière RGB façon Vice City.',                               'https://www.amazon.fr/s?k=mini+frigo+gaming+RGB',             'Amazon', 'Déco',         1, 124),
('Lampe LED Lune / déco geek',               'Une lampe d''ambiance pour une chambre gaming stylée.',                                                    'https://www.amazon.fr/s?k=lampe+LED+lune+deco',               'Amazon', 'Déco',         1, 125),
('Multiprise parasurtenseur',                'Protège ta PS5, ta TV et ton setup des surtensions.',                                                      'https://www.amazon.fr/s?k=multiprise+parasurtenseur+gaming',  'Amazon', 'Accessoire',   1, 126),
('Coussin lombaire & repose-nuque',          'Le combo confort pour tenir des heures sur GTA 6 sans douleur.',                                           'https://www.amazon.fr/s?k=coussin+lombaire+repose+nuque+gaming','Amazon','Confort',     1, 127),
('Art book « The Art of Grand Theft Auto »', 'Le livre d''art pour les vrais fans : coulisses et visuels de la saga.',                                   'https://www.amazon.fr/s?k=art+of+Grand+Theft+Auto+book',      'Amazon', 'Collector',    1, 128),
('Carte cadeau Amazon',                      'La carte cadeau idéale pour (se) faire plaisir avant la sortie de GTA 6.',                                 'https://www.amazon.fr/s?k=carte+cadeau+Amazon',               'Amazon', 'Carte cadeau', 1, 129),
('Abonnement PlayStation Plus Extra 12 mois','Accède à un catalogue de jeux en plus du multijoueur, en attendant GTA 6.',                                'https://www.amazon.fr/s?k=PlayStation+Plus+Extra+12+mois',    'Amazon', 'Abonnement',   1, 130);

-- Vérification (optionnel) :
-- SELECT COUNT(*) AS total_actifs FROM affiliate_links WHERE active=1;
