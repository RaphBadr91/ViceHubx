# Audit SEO — ViceHub X 🌴

> Objectif visé : **15 000 visites/jour**. Cet audit fait l'état des lieux,
> liste ce qui a été corrigé, et trace la feuille de route restante.
> Dernière mise à jour : juin 2026.

---

## 🟢 Note globale : solide (≈ 90/100 en SEO technique)

La base technique est désormais **très saine**. Le facteur limitant pour
atteindre 15 000 visites/jour n'est plus le code, mais le **contenu + les
backlinks + le temps d'indexation** (voir §4).

| Volet | État |
|---|---|
| SEO technique (balises, schema, sitemap, vitesse) | 🟢 Excellent |
| Contenu (volume, fraîcheur, maillage) | 🟢 Très bon (161 articles, maillage auto) |
| Autorité / backlinks | 🟠 À construire (hors-code) |
| Indexation (Search Console) | 🟠 À lancer au déploiement |

**Volume indexable actuel : ~510 URL** (161 articles, 88 produits, 29 sujets de
forum, 421 profils, + pages piliers), toutes dans le sitemap.

---

## 1. ✅ Points forts déjà en place

- **Balises méta** : `title` + `description` uniques par page, `theme-color`,
  viewport mobile, `lang` correct.
- **Open Graph + Twitter Card** sur toutes les pages (partage social propre).
- **hreflang** FR/EN/ES/DE + `x-default` (site multilingue bien signalé).
- **Données structurées JSON-LD** : `Organization`, `WebSite` + `SearchAction`
  (sitelinks search box), `NewsArticle` sur les articles, `Product`/`Offer` sur
  la boutique, `FAQPage` sur les pages piliers (rich snippets).
- **Sitemap dynamique** (`sitemap.php`) : pages + articles + produits + forum +
  profils, avec `changefreq`/`priority`/`lastmod`.
- **robots.txt** : crawl autorisé, zones sensibles bloquées.
- **Performance** : `preconnect`/`dns-prefetch` (fonts + CDN), `lazy loading`
  des images, cache navigateur (`.htaccess`), CSS unique, JS en `defer`.
- **Mobile-first** responsive, PWA (`manifest` + service worker).
- **Accessibilité** : `skip-link`, attributs `alt` sur les images, ARIA nav.
- **Sécurité** (signaux de confiance Google) : en-têtes `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`, HTTPS attendu.

## 2. 🔧 Corrections critiques appliquées (cette session)

1. **Canonical / og:url / hreflang préservent enfin la query string.**
   ⚠️ Avant : `?slug=…`, `?id=…`, `?cat=…` étaient **supprimés** de l'URL
   canonique → Google voyait les 161 articles (et tous les produits/sujets)
   comme **une seule page dupliquée**. C'était le **blocage n°1** à
   l'indexation. Désormais on garde les paramètres identifiants et on retire
   seulement le bruit (`utm_*`, `fbclid`, `gclid`, `lang`…).
2. **Schema `NewsArticle` enrichi** (image, `dateModified`, `mainEntityOfPage`,
   logo éditeur, `articleSection`) + **`BreadcrumbList`** (fil d'Ariane Google).
3. **`og:type=article`** + `article:published_time` sur les articles.
4. **Maillage interne automatique** (`internal_autolink`) : les expressions-clés
   (Vice City, Jason et Lucia, éditions, fonds d'écran, GTA V…) deviennent des
   liens vers les pages piliers sur **tous** les articles. C'est un signal SEO
   majeur et il s'applique rétroactivement.
4. **Pages piliers SEO** créées : `GTA 6 : tout savoir`, `GTA 6 vs GTA 5`,
   `Fonds d'écran GTA 6` (requêtes à très fort volume + FAQ schema).
5. **robots.txt dynamique** (`robots.php`) avec **Sitemap en URL absolue**
   (la version relative était ignorée par Google) ; ne bloque plus `?lang=`
   (qui entrait en conflit avec les hreflang).
6. **404 en `noindex`** + vrai code HTTP 404 (pas de soft-404).

## 3. 🟠 Recommandations techniques restantes (faciles, à la marge)

- **Open Graph image par défaut** : déjà la bannière de marque — ✅ ok.
- **Pagination** des listes (news, forum) : si tu ajoutes `?page=2`, pense à
  `rel="next/prev"` ou à un canonical auto-référent (le code actuel le gère déjà
  via la préservation de la query).
- **Images** : les `alt` ont un repli générique ; pour les articles importants,
  un `alt` plus descriptif (avec le mot-clé) gagnerait quelques points.
- **Vitesse** : pense à servir les images en WebP (déjà supporté via
  `media_html`) et à activer la compression Gzip/Brotli côté O2Switch.
- **Maillage** : envisager des liens piliers ↔ piliers supplémentaires et des
  articles « cluster » qui pointent vers les piliers (déjà amorcé).

## 4. 🚀 Atteindre 15 000 visites/jour — la vraie feuille de route

Le SEO technique est prêt. **15 000 visites/jour, c'est désormais une question
de contenu, d'autorité et de temps.** Plan d'attaque :

**a) Indexation (semaine 1 après mise en ligne) — indispensable**
- Crée une propriété **Google Search Console** (et **Bing Webmaster Tools**).
- Soumets le sitemap : `https://TONDOMAINE/sitemap.php`.
- Demande l'indexation des pages piliers et des 10 meilleurs articles.

**b) Surfer la vague GTA 6 (le moteur de trafic)**
- L'avantage : un **pic de recherche colossal** est garanti autour du
  19 novembre 2026. Publie **régulièrement** (le bot d'actu + tes articles) pour
  être déjà indexé et faire autorité **avant** le pic.
- Cible les requêtes à fort volume déjà couvertes par les piliers : « GTA 6 date
  de sortie », « GTA 6 prix / éditions », « GTA 6 vs GTA 5 », « fond d'écran
  GTA 6 / Vice City », « carte Leonida », « Jason et Lucia ».
- Crée 1 à 3 articles d'actu **le jour même** de chaque annonce Rockstar
  (Google adore la fraîcheur sur les sujets chauds = « QDF »).

**c) Autorité / backlinks (le nerf de la guerre, hors-code)**
- Partage chaque article sur tes réseaux (Instagram, TikTok, Facebook, le forum
  Reddit r/GTA6, Discord). Les vidéos que tu produis ramènent du trafic **et**
  des signaux sociaux.
- Vise quelques backlinks de qualité (sites gaming FR, annuaires de fans).
- Le **forum vivant** (1000 membres) génère du contenu frais en continu = pages
  indexables supplémentaires + temps passé sur le site (bon signal).

**d) Engagement / rétention (signaux comportementaux)**
- Le compte à rebours, les fonds d'écran, le quiz, BAWSAQ, Vice FM augmentent le
  temps passé et les pages/session — Google le remarque.
- Le maillage interne (auto) fait circuler les visiteurs entre les pages.

### Ordre de grandeur réaliste
Avec ce socle, un site de fans bien tenu peut viser **quelques milliers de
visites/jour** en régime de croisière, **puis exploser** au moment de la sortie
(des dizaines de milliers/jour possibles sur le pic de novembre 2026) **si** le
contenu est régulier et l'indexation lancée tôt. 15 000/jour est atteignable
**autour de la sortie** ; en amont, l'objectif est de **construire l'autorité**.

---

*ViceHub X — média de fans indépendant, non affilié à Rockstar Games.*
