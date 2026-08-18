# ViceHub X — Kit Merch 60 produits (LISEZ-MOI)

Bienvenue. Ce dossier contient **tout** pour lancer une boutique merch professionnelle
dans la niche **GTA VI / Miami synthwave néon**, conçue par un panel de 5 stylistes
(Fashion Week / MODE / print-on-demand). **60 produits**, tous **100 % originaux**
(aucun nom/logo/personnage GTA, Rockstar ou Take-Two — uniquement l'esthétique Miami néon
+ la marque **VICEHUB X**).

---

## 🎯 Pour qui (audience visée, recherche 2026)
Cœur **18–34 ans** (fort segment 25–42 nostalgiques), à dominante masculine mais mixte,
gamers matures fans d'esthétique **80s / Miami néon**, sneakerheads, culture drop/hype
(TikTok, Reddit). Ils **repèrent le bootleg** → il faut de l'**original authentique**.
Ce qui vend le mieux : **noir + accents néon**, typo tech, nostalgie outrun, clins d'œil
d'initié, pièces statement plein-dos + accessoires (**coques n°1**, hoodies top CA,
beanies/casquettes brodées).

---

## 🧩 Les 6 collections (60 produits)
1. **AFTER MIDNIGHT** (8) — la capsule fondatrice : une nuit à Miami, wordmark chrome +
   artworks plein-dos. *(designs déjà prêts)*
2. **GOLDEN HOUR** (8) — le drop premium pré-lancement : « one last score » à l'heure dorée,
   pistolet doré, chrome liquide.
3. **TWO OF A KIND** (10) — casino + heist + loyauté : duo ride-or-die, cartes, dés,
   étoile wanted, or chrome.
4. **HIGH SCORE CITY** (10) — l'arcade rencontre la nuit criminelle : press start, continue?,
   kill screen, scanlines CRT.
5. **GETAWAY SEASON** (12) — la cavale et la route ouverte : coupé rétro, grille outrun,
   cassette du voyage.
6. **NEON NOIR** (12) — la ligne minimaliste « everyday tonal » : une icône néon sur beaucoup
   de noir, portable au quotidien (élargit l'audience).

Types couverts : **T-shirt, T-shirt oversize, Hoodie, Hoodie zippé, Sweat col rond, Vest,
Débardeur, Coque de téléphone, Bonnet, Casquette, Tote, Mug, Sticker, Poster, Tapis de souris.**

---

## 📁 Contenu du dossier
- **`00-LISEZ-MOI.md`** — ce fichier.
- **`01-catalogue-60-produits.csv`** — la liste maîtresse (id, collection, type, prix, slug,
  design devant/derrière, titre SEO, meta). Ouvre-le dans Excel / Google Sheets.
- **`fiches-produits/PXX.md`** — **1 fiche par produit** : nom FR/EN, prix, blank conseillé,
  **titre SEO + meta + tags + alt**, **description FR + EN**, description courte site (≤400),
  et **les designs à imprimer avec leur lien de téléchargement** + placement.
- **`designs-manifest/manifest-designs.csv`** — les **49 fichiers designs** (35 nouveaux, tous
  générés en 2K + 14 AFTER MIDNIGHT déjà produits) : type, transparent ou non, **lien de
  téléchargement**, et sur quels produits chacun est utilisé.
- **`sql/vicehubx-merch-produits.sql`** — insertion en masse des 60 produits sur le site (brouillon).
- **`guides/03-guide-printful-complet.md`** — Printful pas à pas (placements par type, mockups, prix).
- **`guides/04-ajouter-au-site-vicehubx.md`** — ajouter au site (méthode SQL ou admin).

---

## ▶️ Comment procéder (pas à pas)
1. **Lis** ce fichier + ouvre `01-catalogue-60-produits.csv` pour la vue d'ensemble.
2. **Télécharge les designs** depuis Higgsfield (liens 📥 dans les fiches / le manifest).
   Un design sert plusieurs produits → tu ne télécharges chaque fichier qu'une fois.
3. **Printful** : suis `guides/03-guide-printful-complet.md`. Commence par **1 produit**
   (le Tee After Midnight P01) de A à Z, vérifie le rendu, puis enchaîne.
4. **Site** : lance `sql/vicehubx-merch-produits.sql` (phpMyAdmin) pour créer les 60 fiches
   en brouillon, PUIS complète chaque produit (`printful_variant_id` + image mockup + `active=1`)
   au fur et à mesure. Détails dans `guides/04-ajouter-au-site-vicehubx.md`.
5. **Active la vente** : Stripe actif + clé API Printful dans Admin → Réglages (auto-fulfillment).

---

## ⚖️ Règle légale (déjà respectée partout)
100 % original : **aucune** propriété de GTA / Rockstar / Take-Two (noms, logos, persos, lieux).
On capte le *vibe* Miami-crime-néon via des motifs génériques (palmiers, coucher de soleil,
coupé rétro, cassette, flamant, étoile wanted, pistolet stylisé) + la marque VICEHUB X et des
slogans anglais originaux. Voitures et armes sont **fictives**, sans marque réelle.

---

## 💡 Conseils de lancement
- Sors les collections en **drops** (une par semaine) → crée de la hype (l'audience adore ça).
- 1 Short/Reel par pièce forte (le dos filmé en gros plan) → lien bio vers le produit.
- Mets 2–3 pièces en **Vedette** sur la home. Garde AFTER MIDNIGHT + NEON NOIR comme socle
  permanent, GOLDEN HOUR en drop pré-lancement du 19 novembre 2026.

Bon lancement 🌴🌆
