# Guide — Ajouter les produits sur le site ViceHub X

Il y a **2 méthodes**. La méthode B (SQL) est la plus rapide pour 60 produits.

---

## Méthode A — À la main (Admin, recommandée pour tester 1 produit)

1. Connecte-toi à l'admin → **Boutique → Nouveau produit**.
2. Remplis, en te servant de la **fiche produit** du ZIP (`fiches-produits/PXX-...md`) :
   - **Nom** = `name_fr` (ou EN selon la langue du produit)
   - **Slug** = laisse vide (auto) ou copie le slug de la fiche
   - **Description** = la version courte **≤ 400 caractères** (champ limité à 400) — fournie dans la fiche
   - **Catégorie** = `Vêtements` (tee/hoodie/vest/tank/bonnet/casquette) ou `Accessoires`
     (coque/tote/mug/tapis/sticker) ou `Affiches` (poster)
   - **Prix** = `price_eur` · **Devise** = EUR
   - **Type de vente** = **Stripe (vente directe)** ← important pour vendre + imprimer via Printful
   - **Variante Printful** = le `sync_variant_id` récupéré sur Printful (voir guide Printful §7)
   - **Image** = upload du **mockup** téléchargé depuis Printful (photo produit)
   - **Badge** (optionnel) = `Nouveau`, `Best-seller`, `Édition limitée`…
   - **Visible** = coché quand tout est prêt (sinon laisse décoché = brouillon)
3. Enregistre. Le produit apparaît dans la boutique `/shop`.

> ⚠️ **Prérequis paiement :** Stripe doit être **actif** (Admin → Réglages) pour encaisser le
> client. Printful te débite ensuite le coût de production ; ta marge = différence.

---

## Méthode B — En masse par SQL (60 produits d'un coup)

Le ZIP contient `sql/vicehubx-merch-produits.sql` : il insère les 60 produits d'un coup
(idempotent — ré-exécutable sans doublon grâce à un test sur le slug).

1. Ouvre **cPanel → phpMyAdmin** → sélectionne ta base ViceHub.
2. Onglet **SQL** → colle le contenu de `vicehubx-merch-produits.sql` → **Exécuter**.
3. Les 60 produits sont créés en **brouillon** (`active = 0`) avec nom, description courte,
   catégorie, prix, badge et `sale_type='stripe'`.
4. Ensuite, pour CHAQUE produit (au fil de tes créations Printful) tu complètes :
   - `printful_variant_id` = le `sync_variant_id` Printful
   - `image` = le chemin/URL du mockup
   - `active = 1` quand il est prêt à être vendu
   Tu peux le faire produit par produit dans **Admin → Boutique → Modifier**, OU par un
   petit UPDATE SQL (exemples fournis en commentaire dans le fichier .sql).

> Pourquoi `active = 0` au départ ? Pour ne PAS publier des produits sans image ni lien
> d'impression. On les active un par un quand ils sont finis = boutique toujours propre.

---

## Ordre de fabrication conseillé (méthodique)

1. **Printful d'abord** : crée le produit, uploade devant (+ dos), choisis les mockups,
   récupère le `sync_variant_id` (guide Printful).
2. **Site ensuite** : soit tu avais lancé le SQL (méthode B) et tu complètes variant_id +
   image + active=1 ; soit tu crées la fiche à la main (méthode A).
3. **Répète** collection par collection. Commence par la collection AFTER MIDNIGHT (déjà
   designée) puis les nouvelles.

## Connexion API Printful (auto-fulfillment)

Une fois quelques produits liés : Admin → **Réglages → Printful** → colle la **clé API**
(Printful → Settings → Developers → API → token type *store*) → *Activer* → **« Lister mes
produits »** pour récupérer/vérifier les `sync_variant_id`. À chaque commande payée sur le
site, Printful imprime et expédie automatiquement.
