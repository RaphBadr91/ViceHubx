# ✅ TO-DO LIST COMPLÈTE — ViceHub X (objectif TOP en 2 mois, → 19 nov 2026)

Consolidation des **4 audits experts** (fonctionnalité/QA, SEO technique, contenu/images, marketing/conversion) + la stratégie Search Console. 

**Légende :**
- ✅ **FAIT** (par Claude, déjà poussé sur la branche `claude/clever-lamport-5z2he9`)
- 🚀 **DÉPLOYER** (mettre le code en prod O2Switch via cPanel Git « Update from Remote »)
- 🗄️ **SQL** (exécuter dans phpMyAdmin)
- 🎨 **GÉNÉRER** (visuels Higgsfield — je peux le faire sur demande)
- 👤 **TOI** (décision / action manuelle : Printful, comptes, handles…)

---

## 🔥 SECTION 0 — À FAIRE EN PREMIER (active tout le reste)

1. 🚀 **Déployer le code** (cPanel → Git → Update from Remote) puis **vider le cache LiteSpeed**. Ça active : le fix hreflang `?lang=en`, les fix 404 breadcrumb, les tableaux dans les articles, les boutons de partage, la sécurité make-admin.
2. 🚀 **Vérifier le 301 www** : après déploiement, `www.vicehubx.com` doit rediriger en 301 vers `vicehubx.com`. Si ce n'est pas le cas → vérifier que le certificat SSL couvre **www** (AutoSSL cPanel). C'est **14 % de tes clics** qui fuient (P0).
3. 🗄️ **Exécuter les 3 SQL** dans phpMyAdmin (rejouables sans risque) :
   - `database/seo-strategy-2026-08.sql` — 17 réécritures titres/meta + consolidation co-op (301).
   - `database/forum-gameplay-27aout-2026.sql` — colonne `updated_at` + 10 sujets forum gameplay.
   - (plus tard) `docs/merch-kit/sql/vicehubx-merch-produits.sql` — les 60 produits (quand la boutique est prête).
4. 👤 **Supprimer `make-admin.php`** du serveur (sécurité — le fichier le demande lui-même).
5. 👤 **Search Console** : re-soumettre le sitemap, demander l'indexation des 4-5 pages retitrées les plus fortes (co-op, streamers, nine1nine, driving-physics, game-pass).

---

## 🔎 SECTION 1 — SEO

**Fait ✅**
- ✅ Titres/meta réécrits (17 pages) — levier CTR n°1 (SQL prêt).
- ✅ Consolidation co-op : 8 URLs → 1 master EN + 1 master FR (301, SQL prêt).
- ✅ Fix hreflang/canonical généralisé `?lang=en` (les 99 pages EN deviennent auto-canoniques).
- ✅ Fix 404 : breadcrumb + lien catégorie pour les articles *leaks* et *trailers*.
- ✅ Maillage interne bilingue (les articles EN avaient **zéro** lien interne).
- ✅ `priceValidUntil` sur les fiches produit ; doublon WebSite JSON-LD retiré (accueil).
- ✅ `dateModified` réel via `updated_at` (colonne à créer via SQL).

**À faire**
- 🚀 Déployer + vérifier www (Section 0).
- 🗄️ Créer la colonne `updated_at` (dans le SQL forum).
- 👤 **Rich snippets produits (pos 55)** : pour les étoiles ⭐ en SERP il faut de **vrais avis** (aucun avis inventé). → activer un système d'avis produit après le lancement boutique, puis ajouter `aggregateRating`. (P1, post-boutique.)
- 👤 **Auteur nommé (E-E-A-T / Discover)** : décider d'un pseudo éditorial (ex. « Rédaction ViceHub X » ou un prénom) → rendre `author` de type `Person`. (À valider avec toi — je ne mets pas de fausse identité sans ton accord.)
- 🗄️ (option) Sortir les fiches produit vides du sitemap tant que la boutique n'est pas lancée.

---

## 🛠️ SECTION 2 — FONCTIONNALITÉS / BUGS (audit QA : 0 bug bloquant 🎉)

**Fait ✅**
- ✅ `make-admin.php` sécurisé (promotion réservée à l'e-mail fondateur).
- ✅ Sélecteur de langue limité à FR/EN (ES/DE affichaient des pages vides).

**À faire (mineurs, non bloquants)**
- 👤 **Contenu véhicules/personnages/zones/trailers en français sur le site EN** : ces fiches ne sont pas traduites (getters sans filtre langue). → soit les traduire en EN (seed EN), soit accepter le FR temporairement. (Impact moyen car marché EN dominant.)
- 👤 Message « Merci pour votre vote » perdu (sondage `community.php`) — cosmétique.
- 👤 Panier→checkout perd la langue (retour en FR pour un acheteur EN) — cosmétique.

---

## ✍️ SECTION 3 — CONTENU / ARTICLES « ENCORE PLUS POUSSÉS »

**Fait ✅**
- ✅ **Déblocage des tableaux** (`<table>`, `<figure>`, `<h4>`) dans le générateur ET le rendu — c'était LE verrou : aucun tableau comparatif ne pouvait s'afficher avant.

**À faire (je peux l'implémenter)**
- ✅/🚀 Cahier des charges « article premium » à imposer au générateur (`includes/ai.php`) :
  1. Encadré **TL;DR** « L'essentiel en 5 points » après le chapô (featured snippet / AI Overview).
  2. Encadré **statut fiabilité** ✅ Confirmé / ⚠️ Rumeur par section sensible (déjà stylable).
  3. **≥ 1 tableau comparatif** quand le sujet s'y prête (prix, éditions, PS5 vs Xbox, GTA5 vs GTA6).
  4. **1 timeline** (frise) pour les sujets date/trailer/chronologie.
  5. Mini-section **« Sources & officiel »** en fin d'article.
  6. Densité factuelle imposée (chiffres, exemples) — la cible 2000 mots n'est pas tenue aujourd'hui (~670 mots en réalité).
- 👤 **Modèle IA** : passer les articles **piliers** en Sonnet + budget ~7000 tokens (Haiku garde le volume/longue traîne). *(À valider — léger surcoût.)*
- Ces points nécessitent une évolution de `ai.php` : **dis-moi « go contenu »** et je réécris le prompt + le cahier des charges dans le code.

---

## 🎨 SECTION 4 — IMAGES « 100 % GAMING » (à générer sur Higgsfield)

Aujourd'hui : **1 seule image (cover) par article**, banque limitée (~30 scènes datées juin 2026), **rien sur le gameplay du 27 août**. Plan :

- 🎨 **Cover prioritaire « gameplay reveal 27 août »** (l'article existe sans image sur-mesure).
- 🎨 **10 covers thématiques 16:9** (véhicules/supercars, co-op duo, moteur RAGE, Vice City néon, éditions/prix, PC, map/Leonida, police/wanted, nautique/Keys, reveal) → à câbler dans `ai_image_bank()` + `config/cdn_map.php`.
- 🎨 **Images in-article** pour les piliers (1 par grande section) — nécessite le support `<figure>` (✅ débloqué).
- Format : WebP 16:9, 1600×900 pour les covers piliers.

👉 **Dis-moi « go images »** et je génère la cover reveal + les 10 covers thématiques (2K), je te les montre, puis on les câble.

---

## 💬 SECTION 5 — FORUM (relancer avec l'actu GTA 6)

**Fait ✅**
- ✅ **10 sujets forum** sur le gameplay du 27 août (SQL prêt : `database/forum-gameplay-27aout-2026.sql`), animables ensuite par le bot `forum-life.php`.

**À faire**
- 🗄️ Exécuter le SQL forum (Section 0).
- 👤 (option) Lier chaque sujet forum à l'article correspondant (maillage).

---

## 📈 SECTION 6 — MARKETING / CONVERSION / MARQUE

**Fait ✅**
- ✅ **Boutons de partage** (X, Facebook, WhatsApp, Reddit, copier/partage natif) sur tous les articles — ils étaient **absents**.

**À faire (fort impact, effort faible)**
- 👤 **`sameAs` = ta marque** : renseigner tes vrais comptes sociaux dans **Admin → Réglages** (`profile_instagram`, `profile_tiktok`, `profile_youtube`, `profile_x`, `profile_facebook`, `profile_discord`) — template SQL fourni. C'est **le** signal qui distingue ViceHub X de l'homonyme vicehub.net.
- 🚀/👤 **Newsletter jamais exploitée** : la capture d'e-mails existe mais aucun envoi possible. → ajouter un bouton « Envoyer aux abonnés » en admin (réutilise Resend déjà intégré). *(Je peux le coder — dis « go newsletter ».)*
- 👤 **Opt-in newsletter** dans les articles + au checkout-success (rétention).
- 👤 **Bug devise EUR/USD** : les prix EUR sont affichés/encaissés « 44,90 » en USD 1:1 pour les visiteurs EN. → décider : conversion réelle ou prix psychologiques par devise. *(À trancher avant la boutique.)*
- 🚀/👤 **Auto-post social** FB/IG/TikTok : câblé mais dormant → coller les jetons → chaque article/drop poussé automatiquement.

---

## 🛍️ SECTION 7 — BOUTIQUE / PRINTFUL (après le SEO)

Le tunnel (Stripe, panier, webhook, Printful) est **prêt**. Il manque le **remplissage** :

- 👤 **Printful** (on le fait ensemble, collection AFTER MIDNIGHT d'abord) : créer chaque produit → mockups → récupérer `sync_variant_id` (guide `docs/merch-kit/guides/03-guide-printful-complet.md`).
- 🗄️ **Importer les 60 produits** : `docs/merch-kit/sql/vicehubx-merch-produits.sql` (brouillons), puis par produit `UPDATE … SET printful_variant_id=…, image=…, active=1`.
- 👤 **Traduire les 60 fiches en EN** (`lang='en'`) — marché EN = 60-76 % des impressions.
- 🚀/👤 **Filtre par collection** dans `shop.php` (un chip par collection : AFTER MIDNIGHT, GOLDEN HOUR…) + pages collection SEO. *(Je peux le coder.)*
- 👤 Cocher `cta=1` sur 8-12 best-sellers (hoodies, tees hero) pour que les articles poussent le merch.
- 👤 Activer Stripe + clé API Printful (Admin → Réglages).
- 🚀/👤 (option) Code promo / offre de lancement « -X% pré-lancement » (Stripe gère les coupons).

---

## 🗓️ SECTION 8 — PLANNING 8 SEMAINES (→ 19 novembre 2026)

**Semaines 1-2 — Réparer & activer (impact fort, effort faible)**
- Déployer le code + vérifier www + lancer les 2 SQL (SEO + forum). *(Section 0)*
- Renseigner `sameAs` (comptes sociaux). Générer la cover reveal + retitrages en ligne.
- Suivre le CTR dans GSC.

**Semaines 3-4 — Contenu premium & images**
- Upgrade générateur (TL;DR, tableaux, timeline, sources) + piliers en Sonnet.
- Générer + câbler les 10 covers thématiques ; images in-article des piliers.
- Newsletter : fonction d'envoi + opt-in.

**Semaines 4-6 — Lancer la boutique**
- Printful (collection par collection) → import 60 produits + EN + `active=1`.
- Filtre collection + `cta=1` best-sellers + teaser boutique remonté sur la home.
- Activer auto-post social (jetons).

**Semaines 6-7 — Offre de lancement & captation**
- Code promo pré-lancement + séquence e-mail (bienvenue → drop → compte à rebours).

**Semaine 8 (autour du 19 nov) — LE PIC**
- Cadence 1-3 news/jour (« Articles express » IA déjà prêt).
- Campagnes e-mail + posts auto sur chaque annonce Rockstar.
- Bandeau boutique + offre lancement en haut de page.

---

## ⚡ Prochaines actions immédiates (dis-moi lesquelles)
- **« go déploiement »** : tu déploies + lances les SQL, je te guide pas à pas.
- **« go images »** : je génère la cover reveal + les 10 covers thématiques.
- **« go contenu »** : j'upgrade le générateur d'articles (premium).
- **« go newsletter »** / **« go collection »** : je code la fonction d'envoi / le filtre boutique.
- **« go Printful »** : on reprend la création produit ensemble.
