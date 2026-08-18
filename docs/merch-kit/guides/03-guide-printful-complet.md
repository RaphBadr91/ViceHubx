# Guide Printful complet — Catalogue ViceHub X (60 produits)

Objectif : mettre en ligne chaque produit avec un rendu **100 % professionnel**.
Tout est en **DTG / broderie / all-over sur base NOIRE** (le néon ressort au maximum).

---

## 0) Récupérer les fichiers (une seule fois)
Les visuels sont dans ton compte **Higgsfield → Generations**. Deux façons :
- clique une image → **Download → version originale** ;
- ou ouvre le lien 📥 indiqué dans chaque fiche produit (`fiches-produits/PXX.md`) et
  dans `designs-manifest/manifest-designs.csv` (colonne `download_url`).

Range par design (`D01.png`, `D02.png`, …). **Un design sert souvent plusieurs produits**
(colonne `utilise_sur` du manifest) → tu ne télécharges chaque fichier qu'une fois.

> ⚠️ Je (l'assistant) ne peux pas te fournir les binaires dans le ZIP (le CDN Higgsfield est
> bloqué de mon côté). Le ZIP contient **tout le texte + les liens de téléchargement directs**.
> Toi, depuis ton navigateur, tu y accèdes normalement.

---

## 1) Choisir le bon blank (filtre à chaque fois)
Dans Printful → **Add product** → filtre **Technique** + **Couleur = Black** + trie heavyweight.
La fiche produit indique le **blank conseillé**. Correspondances techniques :

| Type de produit | Technique Printful | Base |
|---|---|---|
| tee, oversized-tee, tank, crewneck, hoodie, zip-hoodie, vest | **DTG** | Noir |
| casquette (cap), bonnet (beanie) | **Broderie** | Noir |
| coque de téléphone, mug, tapis de souris, chaussettes | **All-over (sublimation)** | fond noir imprimé |
| sticker | **Sticker die-cut (kiss-cut)** | vinyle |
| poster | **Poster papier mat** | — |

---

## 2) Placer les designs selon leur TYPE
Chaque design a un **type** (voir fiche + manifest). Règle de placement :

- **front-text / front-emblem** (fond transparent) → onglet **Left chest**, ~8–10 cm, à ~19 cm du col.
  (slogan large type « NEON NEVER SLEEPS » → onglet **Front** centré, ~26–28 cm.)
- **back-artwork** (fond transparent) → onglet **Back**, centré, ~30 × 40 cm, haut sous la couture d'épaule.
- **all-over** (fond plein) → onglet **All-over / Sublimation** : étire **bord à bord** (coque, mug,
  tapis). Ces designs sont volontairement **NON détourés** (le fond noir fait partie du wrap).
- **embroidery** (fond transparent, 3–4 couleurs) → produit casquette/bonnet → onglet **Front**,
  ~6 × 6 cm. La broderie ne fait PAS de dégradé : nos fichiers sont déjà simplifiés.
- **sticker-die-cut** (fond transparent, contour blanc) → produit sticker, laisse Printful suivre
  le contour (kiss-cut).

> **Impression sur noir :** en DTG, Printful pose une **sous-couche blanche** automatique →
> les néons ressortent. Le **fond transparent** de nos devant/dos = aucune encre autour =
> pas de rectangle, rendu net.

---

## 3) DPI & qualité
Nos fichiers sont générés en **2K** (et les dos AFTER MIDNIGHT en **4K**). Vérifie que
l'indicateur **DPI reste vert** sur chaque placement (il l'est pour tous les placements
recommandés). Pour un plein-dos très grand, garde le design centré sans le sur-étirer.

---

## 4) Mockups (photos produit)
Onglet **Mockups** → coche **2–3 vues par produit** : un flat lay + un modèle porté fond sombre,
et pour les garments recto-verso **ajoute la vue de dos** (c'est l'argument de vente).
Télécharge-les : ils deviennent les **images produit** sur le site.

---

## 5) Détails & prix
Onglet **Details** : copie **Titre**, **Description** et **Tags** depuis la fiche produit
(`fiches-produits/PXX.md`). Prix = celui de la fiche (marge saine déjà calculée).
Enregistre d'abord en **brouillon** le temps de tout régler.

---

## 6) Connecter au site (auto-fulfillment)
1. Crée le produit sur Printful → récupère son **`sync_variant_id`**.
2. Site → **Admin → Réglages → Printful** → colle ta **clé API** (Printful → Settings →
   Developers → API → token *store*) → **Activer**.
3. **« Lister mes produits »** vérifie/récupère les variantes.
4. Sur chaque produit du site, renseigne `printful_variant_id` + l'image (mockup) + `active=1`.
5. **Stripe doit être actif** : le client paie sur le site, Printful te débite le coût de prod,
   ta marge = différence.

---

## 7) Ordre conseillé (méthodique)
1. Commence par la collection **AFTER MIDNIGHT** (P01→P08, designs déjà prêts).
2. Puis **GOLDEN HOUR**, **TWO OF A KIND**, **HIGH SCORE CITY**, **GETAWAY SEASON**, **NEON NOIR**.
3. Dans chaque collection : d'abord les tees/hoodies (grosses marges), puis accessoires
   (coques, casquettes, bonnets, totes, mugs, stickers, posters, tapis).
4. Un design sert plusieurs produits → mutualise le travail (télécharge/prépare une fois).

## ✅ Checklist par produit
- [ ] Blank **Noir**, bonne technique (DTG / broderie / all-over / sticker / poster)
- [ ] Design bien placé selon son **type** (chest / back / all-over / embroidery)
- [ ] DPI **vert**
- [ ] Devant/dos garments = **fond transparent** ; coque/mug = **all-over plein**
- [ ] Titre + description + tags copiés depuis la fiche
- [ ] 2–3 mockups (dont **vue de dos** pour les garments)
- [ ] Prix conforme à la fiche
- [ ] `sync_variant_id` + image + `active=1` sur le site
