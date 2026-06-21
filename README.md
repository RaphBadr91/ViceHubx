# ViceHub X — GTA6 News

> **Enter The Next Generation Of Vice City.**
> Média communautaire **indépendant non officiel** autour de GTA VI : news, guides,
> leaks, analyses de trailers, carte stylisée, véhicules, personnages et communauté —
> dans une interface immersive **Vice City OS** (Liquid Glass / néons Miami).

> ⚖️ **ViceHub X est un média indépendant non affilié à Rockstar Games,
> Take-Two Interactive ou Grand Theft Auto.**

---

## 🧱 Stack

- **PHP 8+** (PDO, sessions, sans framework)
- **MySQL / MariaDB**
- **HTML5 / CSS3 avancé** (glassmorphism, néons, effets 3D légers)
- **JavaScript vanilla** (zéro dépendance obligatoire)
- **Python 3** pour scripts internes optionnels (`scripts-python/`)
- **Motion** en amélioration progressive optionnelle (CDN + fallback CSS)
- Aucune API externe obligatoire

## 📁 Arborescence

```
config/        Configuration + connexion PDO
includes/      header / footer / functions / auth (public & admin)
lang/          Traductions FR / EN
pages/         News, Guides, Leaks Lab, Trailer Lab, Map, Vehicles, Characters, Community, Deals, Contact, Legal
admin/         Panel admin sécurisé (login, dashboard, CRUD articles)
database/      schema.sql (tables + contenu de démo)
public/assets/ css / js / img
uploads/       Images téléversées (protégé)
seo/           robots.txt / sitemap.xml (références)
scripts-python/ Scripts internes optionnels
agents/        Anthropic Managed Agent "Web Scraper" (optionnel)
index.php      Page d'accueil immersive
```

---

## 🚀 Installation locale (FR)

1. **Cloner** le projet et se placer à la racine.
2. **Créer la base** et importer le schéma + données de démo :
   ```bash
   mysql -u root -p < database/schema.sql
   ```
3. **Configurer** les accès dans `config/config.php` (ou via variables d'env
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. **Lancer** le serveur PHP intégré depuis la racine du projet :
   ```bash
   php -S localhost:8000
   ```
5. Ouvrir **http://localhost:8000**.
6. **Admin** : http://localhost:8000/admin/login.php
   - Identifiant : `admin` · Mot de passe : `vicehubx` *(à changer immédiatement)*

### Régénérer le sitemap (optionnel)
```bash
python3 scripts-python/generate_sitemap.py
```

---

## 🚀 Local install (EN)

1. Import the schema + demo data:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. Set DB credentials in `config/config.php` (or env vars `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
3. Start PHP's built-in server from the project root:
   ```bash
   php -S localhost:8000
   ```
4. Open **http://localhost:8000** — admin at `/admin/login.php` (`admin` / `vicehubx`).

---

## ✅ Fonctionnalités

- Homepage premium : hero immersif, modules **Vice City OS**, breaking news, guides, leaks, trailer lab, carte, véhicules, newsletter, deals.
- **Leaks Lab** avec badges de fiabilité (Confirmé / Probable / Rumeur / Analyse / Leak / Officiel / Faux).
- **Trailer Lab** : timecodes, indices, niveaux d'importance.
- **Map Lab** : carte stylisée non officielle, zones cliquables.
- **Véhicules** & **Personnages** : fiches détaillées.
- **Communauté** : sondages + commentaires modérés.
- **Deals Gaming** : liens affiliés configurables en base.
- **Admin** : sessions, mot de passe **bcrypt**, **CSRF**, upload image sécurisé (JPG/PNG/WebP, validation MIME), CRUD articles, statut draft/published, badges.
- **Bilingue FR/EN** (switch manuel) · **SEO** (title, meta, Open Graph, JSON-LD, sitemap, robots) · **responsive mobile-first**.

## 🔐 Sécurité

Requêtes préparées PDO · échappement anti-XSS (`e()`) · jetons CSRF sur tous les
formulaires admin · sessions durcies (`HttpOnly`, `SameSite`) · upload limité et
validé par type MIME réel · exécution de scripts désactivée dans `/uploads`.

## 🧩 Évolutions prévues (structure déjà en place)

IA d'agrégation de news · vraies API (YouTube, X, cartes) · géolocalisation auto ·
boutique · guides premium · newsletter sponsorisée. Toutes optionnelles : le socle
fonctionne sans elles.

## 🤖 Agent Anthropic (optionnel)

Un **Managed Agent** de web scraping est fourni dans `agents/` (création via la
CLI `ant`, exécution via le SDK). Voir [`agents/README.md`](agents/README.md).
