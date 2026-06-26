# Déployer ViceHub X sur O2Switch

Guide pas-à-pas pour mettre le site en ligne sur un hébergement **O2Switch**
(mutualisé cPanel, PHP 8 + MySQL/MariaDB). Stratégie recommandée :
**déployer d'abord sur un sous-domaine de préversion** (`dev.tondomaine.fr`),
peaufiner dessus, puis basculer sur le domaine public quand c'est prêt.

---

## 0. Les médias IA se chargent tout seuls depuis le CDN ✅

Bonne nouvelle : **rien à générer pour que les images s'affichent**. Le site
résout automatiquement chaque visuel depuis le CDN (`config/cdn_map.php`,
`config/wallpapers.php`). Les articles, produits, scènes et wallpapers
s'affichent dès la mise en ligne (le serveur récupère l'image du CDN au besoin).

> 🖼️ **Wallpapers** : à la première demande, le serveur télécharge le fichier propre
> depuis le CDN vers `storage/wallpapers/` (privé), puis sert un **aperçu filigrané**
> (`preview.php`). À l'achat, l'acheteur reçoit le fichier **sans filigrane** par
> e-mail (PNG+JPEG+PDF) et via `download.php` — lien valide après paiement Stripe.
>
> Pré-requis : que le serveur O2Switch puisse faire des requêtes HTTPS sortantes
> (c'est le cas par défaut). Les dossiers `storage/wallpapers/` et
> `public/assets/img/shop/cache/` doivent être **inscriptibles** (voir étape 6).

*(Optionnel : `bash scripts/make-wallpapers.sh` sur ton Mac pour vendre des fichiers
locaux au lieu de les récupérer du CDN — non nécessaire pour le lancement.)*

---

## 1. Choisir PHP 8 (cPanel → MultiPHP Manager)

1. Connecte-toi à cPanel O2Switch.
2. **MultiPHP Manager** → sélectionne le domaine/sous-domaine → **PHP 8.1+**.

## 2. Créer la base de données (cPanel → Bases de données MySQL)

1. **Créer une base** : ex. `moncompte_vicehubx`.
2. **Créer un utilisateur** : ex. `moncompte_vice` + mot de passe fort.
3. **Ajouter l'utilisateur à la base** avec **TOUS les privilèges**.
4. Note bien : nom de base, utilisateur, mot de passe (ils iront dans `.env`).

## 3. Mettre les fichiers en ligne

**Option A — Git (recommandé)** depuis le Terminal cPanel ou SSH :
```bash
cd ~/dev.tondomaine.fr            # dossier racine du sous-domaine
git clone <URL_DU_DEPOT> .
```

**Option B — FTP / Gestionnaire de fichiers** : uploade tout le contenu du
projet dans le dossier racine du (sous-)domaine (`index.php` doit être à la
racine web).

> ⚠️ La racine web doit contenir `index.php` directement (pas un sous-dossier).

## 4. Créer le fichier `.env`

À la racine du site, copie `.env.example` en `.env` et remplis :

```bash
cp .env.example .env
```
```env
APP_ENV=prod
VICEHUB_BASE_URL=https://dev.tondomaine.fr
DB_HOST=localhost
DB_NAME=moncompte_vicehubx
DB_USER=moncompte_vice
DB_PASS=ton-mot-de-passe
VICEHUB_CSRF_KEY=une-longue-chaine-aleatoire-unique
```

## 5. Importer la base de données

**Option A — phpMyAdmin (cPanel)** : ouvre la base → onglet **Importer** →
sélectionne `database/schema.sql` → **Exécuter**.

**Option B — installateur web** : ouvre `https://dev.tondomaine.fr/install.php`
puis clique « Installer maintenant ». **⚠️ Supprime `install.php` juste après.**

## 6. Permissions des dossiers inscriptibles

Ces dossiers doivent être inscriptibles par le serveur :
```bash
chmod 755 uploads                          # images téléversées (admin)
chmod 755 storage/wallpapers               # cache des wallpapers propres (CDN)
chmod 755 public/assets/img/shop/cache     # cache des aperçus filigranés
```

## 7. Sécurité — à vérifier

- [ ] `install.php` **supprimé** après l'import.
- [ ] `.env` présent et **non accessible** depuis le web
      (teste : `https://dev.tondomaine.fr/.env` doit renvoyer 403).
- [ ] `https://dev.tondomaine.fr/database/schema.sql` → 403.
- [ ] `APP_ENV=prod` (les erreurs PHP ne s'affichent pas aux visiteurs).
- [ ] Sous-domaine de préversion : décommente la ligne `X-Robots-Tag noindex`
      dans `.htaccess` pour rester invisible sur Google.

## 8. Connexion admin

- URL : `https://dev.tondomaine.fr/admin/`
- Identifiant : **admin** · mot de passe : **vicehubx**
- 👉 **Change le mot de passe immédiatement** (panneau admin).

## 9. Passage en production (plus tard)

Quand le site est prêt :
1. Pointe le domaine public sur le dossier du site (ou recopie dessus).
2. Mets `VICEHUB_BASE_URL=https://tondomaine.fr` dans `.env`.
3. **Recommente** la ligne `X-Robots-Tag noindex` du `.htaccess` (réautorise
   l'indexation Google).
4. Vérifie `robots.txt` et `sitemap.xml`.

---

## 10. Activer la boutique (paiement Stripe)

La boutique gère **deux types de produits** :
- **Stripe** (vente directe) : tes affiches IA, goodies… → paiement par carte sur le site.
- **Revendeur / affilié** (Amazon…) : simple lien sortant (commission).

Pour activer le paiement Stripe :
1. Crée un compte sur **[dashboard.stripe.com](https://dashboard.stripe.com)**.
2. Récupère tes clés API : **Développeurs → Clés API** (`pk_live_…` et `sk_live_…`).
3. Dans l'admin ViceHub : **Réglages → Boutique** → colle la clé publiable, la clé
   secrète et choisis la devise. (Ou via `.env` : `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`.)
4. **Webhook** (confirme les paiements) : Stripe → **Développeurs → Webhooks → Ajouter** :
   - URL : `https://tondomaine.fr/stripe-webhook.php`
   - Événement : `checkout.session.completed`
   - Copie le **Signing secret** (`whsec_…`) dans **Réglages → Boutique**.
5. Teste en mode **Test** (clés `pk_test_`/`sk_test_`, carte `4242 4242 4242 4242`).

Les commandes payées apparaissent dans **Admin → Commandes**. Chaque produit se
règle dans **Admin → Boutique** (type de vente, prix, lien, image, mise en avant).

> 💡 Tu peux aussi lier un **ID de prix Stripe** (`price_…`) par produit si tu
> préfères gérer les prix dans le Dashboard Stripe.

---

## 11. Activer les e-mails automatiques (Resend)

Pour la **livraison automatique des wallpapers** (sans filigrane, par e-mail) et
le formulaire de contact :

1. Crée un compte sur **[resend.com](https://resend.com)** et **vérifie ton domaine**.
2. Génère une **clé API** (`re_…`).
3. Dans l'admin ViceHub : **Réglages → E-mails automatiques (Resend)** → colle la clé,
   l'**adresse d'expéditeur** (sur le domaine vérifié) et l'e-mail de contact.
   *(Ou via `.env` : `RESEND_API_KEY=re_…`.)*
4. Clique **« Envoyer un test »** pour vérifier. Sans Resend, le site utilise le
   `mail()` du serveur en repli.

---

## 12. CTA produits & rotation (immersion)

Dans **Admin → Boutique** :
- Coche 🚀 les produits à **propulser** (t-shirts, mugs, wallpapers…).
- Règle le **nombre de produits en rotation** : ce nombre tourne au hasard chaque
  jour dans les encarts des articles. Laisse vide les 🚀 = tout le catalogue tourne.

---

### Dépannage rapide

| Symptôme | Cause probable | Solution |
|---|---|---|
| « Connexion base impossible » | Identifiants `.env` faux | Vérifie DB_NAME/USER/PASS (préfixe `moncompte_`) |
| Page blanche | `APP_ENV=dev` puis erreur, ou PHP < 8 | Passe en PHP 8.1+, regarde les logs cPanel |
| Images/vidéo absentes | Médias non commités | Refais l'étape 0 sur ton Mac |
| `.env` visible en 403→200 | `.htaccess` non pris en compte | Vérifie qu'Apache lit le `.htaccess` racine |
