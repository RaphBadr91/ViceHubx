# Stratégie SEO & Marketing — ViceHub X (analyse Search Console, août 2026)

> Produit par un panel de 5 experts (SEO technique, SEO éditorial, CTR/SERP, Growth, E-commerce) + directeur de stratégie, à partir des **vraies données Search Console** (28 j : 9 744 impressions, 69 clics, CTR 0,71 %).

## 🔎 Diagnostic
Le problème central de vicehubx.com n'est PAS le classement mais le CTR. Sur 9744 impressions / 69 clics (CTR 0,71%), 558 impressions sont en position 1-6 et 521 en position 7-10 (page 1) : le site est déjà VISIBLE mais ses titres/meta ne déclenchent pas le clic. Preuve interne : l'article merch 'goodies-gta-6' fait 14,71% de CTR à pos 6, soit ~20x la moyenne du site — à position égale, un bon titre multiplie les clics. Les grosses pages page-1 à 0 clic (driving-physics 222 impr/0%, game-pass 142/0%, neon-queen 141/0%, heists 134/0%, mods-pc 115/0%) sont donc un pur problème de snippet, pas de rang. Trois plaies structurelles amplifient la perte : (1) la fuite www — 1067 impr / 10 clics (~14% des clics) partent encore par www.vicehubx.com car le 301 www->non-www codé dans .htaccess n'est PAS déployé en prod ; (2) la cannibalisation co-op — 8 URLs se partagent ~1300 impr pour ~5 clics, dont DEUX masters EN qui se volent le rang (707 impr pos9,07 vs 398 impr pos7,96), l'autorité étant diluée par 8 au lieu d'être concentrée ; (3) 99 URLs ?lang=en indexées via une contradiction canonical/hreflang qui sabote le marché EN pourtant dominant (USA 2416 impr = 1re source, marché international ~76%). S'y ajoute une notoriété de marque nulle ('vicehub' pos5,9 / 0 clic, 'vice hub' pos5,7 / 0 clic) parasitée par l'homonyme vicehub.net cité dans Google AI. Conclusion : réparer les snippets + arrêter les fuites (www, ?lang=en, cannibalisation) convertit une visibilité DÉJÀ acquise (~1080 impr en pos 1-10 sous-exploitées) sans dépendre d'un seul backlink. C'est >80% du gisement, à coût de ranking nul. La boutique merch, déjà meilleur convertisseur du site, doit ensuite capter ce trafic éditorial réparé avant le pic garanti du 19 nov 2026.

## ⚡ Quick wins (dans l'ordre)
1. **Deployer et VERIFIER le .htaccess en prod (301 www->non-www), curl -sI https://www.vicehubx.com/ doit renvoyer 301 vers https://vicehubx.com/, puis vider le cache LiteSpeed et forcer le recrawl GSC**  
   _1067 impr / 10 clics (~14% des clics totaux) fuient vers un hote non canonique. La regle existe deja dans .htaccess (l.17-18) et BASE_URL est fige non-www : il ne manque QUE le deploiement. Zero risque, gain immediat._
2. **Reecrire meta_title/meta_description des 15-17 pages page-1 a CTR ~0, en front-loadant la requete exacte + annee 2026 (colonnes meta_title 90 / meta_description 200 deja lues par article.php)**  
   _521 impr en pos 7-10 a ~0,4% de CTR. Passer ce pool a 3-5% = 130-220 clics/mois vs ~40 aujourd'hui (x3 a x5). Aucun code, aucune modif de contenu, aucun gain de ranking necessaire. Modele prouve : page merch a 14,71%._
3. **Consolider les 8 URLs co-op en 1 master EN + 1 master FR via la table redirects (301 deja cable dans article.php)**  
   _~1300 impr eclatees sur 8 pages pour ~5 clics, deux masters EN qui se cannibalisent. Recombiner l'autorite sur 1 URL = saut mecanique probable de pos ~8 vers pos 3-5 sur 'gta 6 co op' (pos7,8 actuel)._
4. **Retitrer l'article nine1nine (311 impr pos8,89) pour inclure le mot 'hypercar' que tape l'internaute : 'GTA 6 Nine1Nine & Hypercar Leak Explained (2026)'**  
   _Le site classe deja pos 8-12 pour ~250 impr de requetes 'nine1nine'/'hypercar part 1'/'gta 6 hypercar' a ~0 clic car le titre parle de 'nightclub' et non de l'intent leak/vehicule. Concurrence quasi nulle (terme forge par les fans)._
5. **Rendre les pages ?lang=en auto-canoniques et corriger le hreflang des piliers (header.php L29-40) au lieu de retirer 'lang' du canonical**  
   _99 URLs ?lang=en se canonicalisent vers la version FR alors que sitemap.php les declare en EN : Google deduplique/deprecie l'EN, on saborde le marche USA/UK qui est 60%+ des impressions._
6. **Remplacer les cibles perimees de seo-meta-fixes.sql (pages a 208/191/129 impr) par les slugs du top-20 REEL (707/477/398...)**  
   _Le bon levier (reecriture meta) a ete applique aux mauvaises priorites. Un seul passage phpMyAdmin recentre l'effort sur les pages qui saignent vraiment aujourd'hui._
7. **Passer les 60 fiches produits vides (active=0, sans prix/stock/image, schema Product thin) en noindex ou hors sitemap tant que la boutique n'est pas lancee**  
   _Elles polluent l'index a pos ~55 / 0 clic (129 impr) et diluent le crawl. A l'inverse, dupliquer le format article-GUIDE merch (CTR 14,71%) qui, lui, convertit deja._
8. **Blinder le title homepage contre l'homonyme : 'ViceHub X - GTA 6 News, Leaks & Vice City Guide' + suffixe '| ViceHub X' systematique sur tous les titres d'articles**  
   _'vicehub'/'vice hub' classent pos 5-6 mais 0 clic (confusion avec vicehub.net). Marteler le nom + qualificatif GTA 6 leve l'ambiguite et installe l'entite meme chez les non-cliqueurs._

## 🛠️ Correctifs structurels
- **[P0] Fuite www : 1067 impr / 10 clics (~14% des clics) passent encore par www.vicehubx.com. Le 301 www->non-www est code dans .htaccess (l.17-18) mais PAS actif en prod.** → Uploader/verifier que le .htaccess deploye sur O2Switch = celui du repo, s'assurer qu'AutoSSL est actif (sinon boucle HTTPS), vider le cache LiteSpeed. Verif : curl -sI https://www.vicehubx.com/ doit renvoyer 301 + Location: https://vicehubx.com/. Puis GSC : Inspection URL + reindexation des principales URLs www, surveiller leur disparition.
- **[P0] 99 URLs ?lang=en indexees : contradiction canonical/hreflang. sitemap.php (L96-98) declare l'anglais des piliers en ?lang=en (hreflang en + x-default) mais header.php (L33-40) retire 'lang' du canonical -> la page EN se canonicalise vers la FR. Google deduplique/deprecie l'EN alors que USA/UK = marche dominant.** → Dans header.php (L29-40) : quand lang=en et aucun CANONICAL impose, NE PAS retirer 'lang' -> canonical auto-referent /page?lang=en + generer HREFLANG_ALT [fr=>URL propre, en=>URL?lang=en, x-default=en]. Aligne enfin la page avec ce que sitemap.php annonce. A terme, migrer les piliers vers des slugs EN distincts comme les articles.
- **[P1] Fiches produits en pos ~55 (129 impr, 0 clic) : boutique non lancee, schema Product 'thin' (sans prix/stock/avis reels) qui pollue l'index et le crawl. Disclosure shop.php parle de 'liens affilies/Amazon' alors que le merch est du POD Stripe en propre.** → Passer les produits sans prix/stock reel en noindex (override ROBOTS) ou les sortir du sitemap.php tant que la boutique n'est pas lancee. Au lancement : schema Product complet (prix, dispo, images, avis). Scinder le disclosure : affiliation uniquement sur les lignes 'external' ; pour le merch afficher les signaux de confiance (merch officiel fan, paiement Stripe, imprime a la demande, retours).
- **[P1] E-E-A-T faible pour Discover/News : article.php (L129) met author=Organization, pas de byline Person, a l'approche du pic du 19 nov 2026. La fondation technique Discover est prete (max-image-preview:large, og 1200x630, JSON-LD NewsArticle, news-sitemap 48h) mais editorialement inexploitee.** → Remplacer author=Organization par author @type=Person (name + url vers page /membre), garder publisher=Organization ViceHub X, preciser le statut 'media de fans non officiel'. Lancer la cadence 1-2 news/jour reactives (leaks, trailers, dates) avec 1 image hero horizontale nette >1200px + regle QDF : 1-3 actus le jour meme de chaque annonce Rockstar. S'assurer que seuls les vrais articles alimentent news-sitemap.xml (pas la boutique).
- **[P2] Entite de marque inexistante : 'vicehub' pos5,9 / 0 clic, 'vice hub' pos5,7 / 0 clic. Meme bien classee la marque n'est pas cliquee, et l'homonyme vicehub.net est cite par Google AI. Aucun Knowledge Panel, sameAs non consolide.** → Title homepage + suffixe '| ViceHub X' systematique avec qualificatif GTA 6. Verifier Organization + WebSite JSON-LD avec sameAs vers TOUS les profils sociaux. Reserver les handles @vicehubx identiques sur TikTok/X/YouTube/Instagram/Reddit. Page About solide + auteurs nommes. Creer une fiche Wikidata 'ViceHub X (media fan GTA VI)' pour desambiguiser de vicehub.net. Obtenir des co-citations du nom exact dans des articles tiers.

## ✍️ Réécritures titres/meta (17 pages) — appliquées via `database/seo-strategy-2026-08.sql`
| Slug | Nouveau title | Requête cible |
|---|---|---|
| `gta-6-co-op-campaign-what-we-know-about-` | GTA 6 Co-Op Campaign: What We Know (2026) | does gta 6 have co op campaign |
| `gta-6-content-creators-streamers-youtube` | GTA 6 & Streamers: Which Creators Are Waiting? 2026 | what streamers are in gta 6 |
| `nine1nine-vice-city-s-premier-nightclub-` | GTA 6 Nine1Nine & Hypercar Leak Explained (2026) | gta 6 hypercar part 1 |
| `gta-6-graphics-rage-engine-how-rockstar-` | GTA 6 Engine: How RAGE Pushes Graphics in 2026 | gta 6 engine |
| `gta-6-supercars-identified-complete-brea` | GTA 6 Supercars: Every Fast Car Found (2026) | gta 6 supercars |
| `gta-6-driving-physics-revealed-suspensio` | GTA 6 Driving Physics: Suspension & Grip 2026 | gta 6 driving physics |
| `ps5-vs-xbox-series-x-for-gta-6-performan` | GTA 6: PS5 vs Xbox Series X - Which Runs Best? | gta 6 ps5 vs xbox |
| `gta-6-on-game-pass-access-timeline-micro` | Is GTA 6 on Game Pass? Xbox Timeline 2026 | is gta 6 on game pass |
| `vice-city-in-gta-6-the-neon-queen-makes-` | Is Neon in GTA 6? Vice City's Return Explained | is neon in gta 6 |
| `gta-6-aircraft-guide-all-helicopters-jet` | GTA 6 Aircraft: All Helicopters, Jets & Drones | gta 6 aircraft |
| `gta-6-heists-explained-what-rockstar-is-` | GTA 6 Heists: What Rockstar Plans for Jason & Lucia | gta 6 heists |
| `gta-6-edition-guide-standard-vs-ultimate` | GTA 6 Editions: Standard vs Ultimate - Worth It? | gta 6 editions standard vs ultimate |
| `gta-6-pc-release-date-when-why-rockstar-` | GTA 6 PC Release Date: When & Why the Delay? | gta 6 pc release date |
| `gta-6-mods-on-pc-what-might-be-possible-` | GTA 6 PC Mods: What Might Be Possible (2026) | gta 6 pc mods |
| `gta-6-map-vs-gta-5-complete-size-density` | GTA 6 Map vs GTA 5: How Much Bigger Is Leonida? | gta 5 vs gta 6 |
| `multijoueur-cooperatif-gta-6-ce-qu-il-fa` | GTA 6 en coop ? Ce que l'on sait du multi 2026 | multijoueur cooperatif gta 6 |
| `reconnaitre-un-vrai-leak-gta-6-d-une-int` | GTA 6 : reconnaitre un vrai leak d'une intox 2026 | reconnaitre un vrai leak gta 6 |

## 🔗 Consolidation anti-cannibalisation (301)
- **Garder** `gta-6-co-op-campaign-what-we-know-about-multiplayer-in-vice-city` — PRIORITE ABSOLUE. Master EN = 707 impr, le plus d'equite, slug qui matche 'does gta 6 have co op campaign' (pos7,4). Le doublon 'two-player-gameplay' (398 impr, pos7,96) se cannibalise directement avec lui : DEUX masters EN quasi identiques sur ~1300 impr / ~5 clics. Concentrer l'autorite = passage probable de pos ~8 vers top 3 sur 'gta 6 co op'.
  - 301 ← `gta-6-co-op-what-we-actually-know-about-two-player-gameplay`
  - 301 ← `gta-vi-multiplayer-cooperation-confirmed-features-limits-rumors-explained`
  - 301 ← `gta-vi-leak-could-a-co-op-campaign-launch-at-release`
  - 301 ← `story-co-op-mode-the-fan-dream-for-gta-vi-s-jason-lucia`
- **Garder** `multijoueur-cooperatif-gta-6-ce-qu-il-faut-savoir-sur-le-mode-coop` — Master FR co-op = 95 impr, meilleur CTR marche FR. Les 2 variantes FR (10 impr et 1 impr pos95) diluent. REGLE STRICTE : jamais de 301 FR->EN, on garde une paire hreflang FR<->EN reciproque.
  - 301 ← `cooperation-multijoueur-gta-6-modes-limites-et-rumeurs-expliquees`
  - 301 ← `leak-mode-cooperatif-au-lancement`
- **Garder** `gta-6-vehicle-guide-supercars-muscle-cars-boats-motorcycles-explained` — PILIER (pas de 301, de-cannibalisation par architecture). vehicle-guide (104 impr pos11,71) devient le pilier 'GTA 6 Vehicles'. supercars-identified (241 impr, CTR2,49%), aircraft-guide (138 impr, CTR2,9%), customization-system (112 impr) et le nouvel article nine1nine-hypercar restent des ARTICLES ENFANTS specialises qui pointent vers le pilier via internal_autolink.
  - (pilier : pas de 301, dé-cannibalisation par maillage interne)

## 📚 Plan de contenu (clusters)
- **P1 Co-op & Multiplayer** : Consolider 8 URLs en 1 master EN (gta-6-co-op-campaign, 707 impr) + 1 master FR, reconstruire le master en page FAQPage repondant a chaque variante. Enfants : split-screen/2-joueurs, online/successeur GTA Online, story co-op rumeurs, distinguer vrai leak.  
  Mots-clés : _gta 6 co op, does gta 6 have co op campaign, is gta 6 co op campaign, will gta 6 be co op, gta 6 coop_
- **P2 Vehicles (nine1nine/hypercar + supercars)** : Pilier = vehicle-guide. Retitrer nine1nine pour capter l'intent hypercar/leak ET creer un article dedie 'GTA 6 Nine1Nine Hypercar (Part 1): What the Leak Shows'. Enfants : supercars-identified, aircraft-guide, customization-system (tuning). Concurrence quasi nulle sur nine1nine/hypercar (terme forge par les fans), striking distance immediat.  
  Mots-clés : _gta 6 hypercar, nine1nine, gta 6: hypercar part 1, gta 6 supercars, gta 6 nine1nine_
- **P3 Tech & Graphics / RAGE Engine** : Pilier = graphics-rage-engine. De-cannibaliser vs driving-physics en clarifiant le focus (visuels vs physique) et cross-link. Enfants : driving-physics, ps5-vs-xbox, mods-on-pc. Capte 'gta 6 engine' (pos11,7).  
  Mots-clés : _gta 6 engine, gta 6 driving physics, gta 6 ps5 vs xbox, gta 6 pc mods_
- **P4 Editions, Prix & Ou acheter (money -> boutique)** : Reconstruire edition-guide (pos61,95 anormal) en pilier editions/prix : Standard vs Ultimate, prix par pays pour capter 'combien a coute gta 6' (pos12,6), precommande, lien Game Pass. Enfant : on-game-pass. Lien direct vers la boutique merch.  
  Mots-clés : _gta 6 editions, gta 6 standard vs ultimate, combien a coute gta 6, is gta 6 on game pass_
- **P4b PC (cluster a fort volume bloque)** : Promouvoir gta-6-pc-release-date (pos43,3) en pilier PC actualise : date, raisons du delai, config requise, lien mods. 'gta 6 pc' a un volume enorme.  
  Mots-clés : _gta 6 pc, gta 6 pc release date, when is gta 6 on pc_
- **P5 Monde / Vice City & Leonida** : Pilier = map-vs-gta-5 (pos8,01, angle data linkable pour digital PR). Enfants : vice-city-neon-queen retitre pour 'is neon in gta 6' (86 impr pos9,5), nine1nine club, heists (Jason & Lucia), content-creators/streamers.  
  Mots-clés : _gta 5 vs gta 6, is neon in gta 6, gta 6 map size, gta 6 heists, what streamers are in gta 6_

## 🛍️ Lancement boutique (convertir le trafic éditorial)
- **Creer 6 pages Collection SEO indexables (/boutique/<collection>)** — Les 60 produits ont leur collection stockee seulement dans le champ 'badge' (AFTER MIDNIGHT, GOLDEN HOUR, TWO OF A KIND, HIGH SCORE CITY, GETAWAY SEASON, NEON NOIR) mais shop.php ne filtre que par les 7 categories codees en dur : tout le merch est noye dans 'Vetements'. Ajouter une route 'collection' a shop.php (filtrer sur badge ou colonne dediee), chaque page = H1 collection + intro storytelling FR+EN + grille + schema ItemList/Product + fil d'Ariane. Ces pages deviennent cibles de maillage et landings de drop. Priorite : AFTER MIDNIGHT et NEON NOIR (socle permanent), GOLDEN HOUR (drop pre-lancement 19 nov).
- **Remplacer le CTA merch aleatoire par un mapping contextuel sujet->collection** — article_shop_cta()/cta_pool() tirent aujourd'hui un produit AU HASARD avec texte generique ('soutiens le site'). Router selon les mots-cles de l'article : neon|nightlife|nine1nine|night -> NEON NOIR + AFTER MIDNIGHT ; supercar|hypercar|vehicle|driving -> GETAWAY SEASON + posters ; heist|co-op|casino -> TWO OF A KIND ; arcade|gaming|graphics|engine -> HIGH SCORE CITY. Etoffer internal_autolink avec des ancres EN (aujourd'hui une seule ancre FR 'boutique') pointant vers /boutique et les pages collection. Un article co-op ou nine1nine doit afficher une piece THEMATIQUE, pas un wallpaper aleatoire.
- **Repliquer le patron de l'article-guide merch (CTR 14,71% pos6) sur les 15 reecritures et connecter les articles a fort trafic aux produits** — Analyser la formule titre/meta de goodies-gta-6 (meilleur convertisseur, ~20x la moyenne) et la porter. Au lancement, mailler : editions/prix -> merch, vehicules -> posters/vetements (GETAWAY SEASON), customization -> goodies, neon/nine1nine -> NEON NOIR. Transformer l'article-guide merch en hub qui lie explicitement chaque collection. Les rich snippets produits (pos~55) remonteront avec le maillage + le lancement effectif.
- **Publier les fiches produits EN + prix par devise reels et passer les pieces heros en active=1** — 76% du trafic est EN (US 2416, UK 917) mais les 60 produits sont seedes lang='fr' uniquement (les nom_en/meta EN existent deja dans docs/merch-kit/01-catalogue-60-produits.csv, non exploites) et le prix affiche la valeur EUR brute avec un symbole $ (active_currency()=USD mais price=44.90 EUR). Inserer les lignes EN + de vrais prix USD/GBP. Ajouter mockups Printful + printful_variant_id sur les pieces a forte valeur (tee oversize, hoodies, coques, beanies, posters A2), passer active=1, marquer 2-3 pieces featured=1. Sans image la grille n'affiche qu'un emoji = invendable.
- **Lancer une offre de lancement + calendrier de drops cale sur le pic GTA VI** — Code Stripe de lancement (ex. LAUNCH15 / -15% 1re commande) + seuil de franco de port, surface via bandeau home + encart bas d'article. Drop GOLDEN HOUR cale sur le pic de trafic du reveal/sortie (19 nov 2026), puis 1 collection/semaine. Compte a rebours 'edition limitee/drop' pour l'urgence attendue par l'audience 18-34. Cross-sell meme COLLECTION (au lieu de meme category) sur product.php pour monter l'AOV + add-on impulsion bas prix (sticker, mug) au panier.
- **Capturer une audience possedee avant la falaise post-sortie : newsletter 'GTA 6 Countdown' + relance panier** — Newsletter hebdo compte a rebours jusqu'au 19/11/2026, capture via CTA inline + exit-intent sur les pages fortes (co-op, supercars, nine1nine) et surtout la page merch (14,71% CTR = forte intention). Le checkout collecte deja l'e-mail : ajouter relance panier abandonne + confirmation avec upsell meme collection. Ajouter avis/UGC (photos portees, compteur de ventes) sur fiches et pages collection pour lever le frein 'petite marque inconnue' face a vicehub.net.

## 🗓️ Plan 90 jours
### Semaines 1-2 : Fondations, arret des fuites (ROI immediat, zero backlink)
- Deployer + VERIFIER le 301 www->non-www en prod (curl -sI), recuperer 1067 impr / 10 clics
- Patcher header.php pour rendre les ?lang=en auto-canoniques + hreflang reciproque FR/EN (debloquer le marche EN dominant)
- Consolider les 8 URLs co-op : 1 master EN + 1 master FR via la table redirects (301), fusion du contenu, micro-fix chaine de 301
- Reecrire meta_title/meta_description des 15-17 pages page-1 a CTR ~0 (vague 1 = les 10 plus grosses impressions), remplacer les cibles perimees de seo-meta-fixes.sql, puis Inspection URL/reindex
- Retitrer nine1nine pour capter l'intent hypercar, requalifier le disclosure boutique
### Semaines 3-6 : Architecture, entite de marque, amorce Discover
- Formaliser les 5 piliers/clusters et etendre le maillage interne auto aux nouveaux piliers
- Creer l'article nine1nine-hypercar-part1 (striking distance quasi sans concurrence) + pilier Vehicles
- Reconstruire le pilier Editions/Prix (edition-guide pos61,95) + promouvoir le pilier PC (pos43,3) : clusters money connectes a la boutique
- Etablir l'entite de marque : Organization+WebSite sameAs, handles @vicehubx sur toutes les plateformes, page About + auteurs nommes (byline Person), fiche Wikidata
- Demarrer la cadence Discover : 1-2 news/jour, byline auteur, image hero >1200px, QDF sur chaque annonce Rockstar
- Lancer les 6 pages Collection SEO + mapping CTA contextuel sujet->collection, fiches EN + prix par devise
### Semaines 7-12 : Diffusion a l'echelle + boutique + couverture post-pic
- De-cannibaliser Tech/RAGE (graphics vs driving-physics) + reecriture meta vague 2, pilier Monde/Vice City (neon, carte, heists)
- Lancer la boutique en partiel : mockups + variant_id sur les pieces heros active=1, offre LAUNCH15, drops GOLDEN HOUR cales sur le 19 nov
- Moteur TikTok/Reels/Shorts adosse aux articles a fort signal (supercars, aircraft, nine1nine, neon), 1/jour, meme handle @vicehubx
- Newsletter 'GTA 6 Countdown' + relance panier abandonne = audience possedee avant la falaise post-sortie
- Seeding Reddit (r/GTA6, r/GamingLeaksAndRumours) / X + digital PR : packager map-vs-gta-5 / supercars en asset linkable, pitcher 5-10 liens DR40+
- Revue GSC complete : CTR global 0,71%->3%+, clics 69->200+, 'gta 6 co op' pos7,8->top3, impr www 1067->0, pages ?lang=en 99->baisse, CTR pages reecrites avant/apres

## ✅ Comment appliquer (checklist)
1. **[P0] Déployer `.htaccess` en prod** (O2Switch) : la règle 301 www→non-www existe déjà dans le repo mais n'est pas active en prod (www fuit 1 067 impr). Déployer le repo via cPanel Git « Update from Remote », vider le cache LiteSpeed, puis vérifier : `curl -sI https://www.vicehubx.com/` doit renvoyer `301` vers `https://vicehubx.com/`.
2. **Déployer le code** (mêmes fichiers) pour que `article.php` lise `meta_title`/`meta_description` et applique les 301 de la table `redirects` (déjà codé).
3. **Exécuter `database/seo-strategy-2026-08.sql`** dans phpMyAdmin (17 réécritures + 6 redirections + sortie sitemap des fusionnés). Rejouable sans risque.
4. **Search Console** : soumettre le sitemap à nouveau, demander l'indexation des 3-4 pages retitrées les plus fortes, et surveiller le CTR à 2-3 semaines.
5. **Boutique** : enchaîner sur la mise en ligne des 60 produits (kit déjà prêt) + maillage articles→produits.
