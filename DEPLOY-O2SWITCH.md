# Déployer ViceHub X sur O2Switch

Guide pas-à-pas pour mettre le site en ligne sur un hébergement **O2Switch**
(mutualisé cPanel, PHP 8 + MySQL/MariaDB). Stratégie recommandée :
**déployer d'abord sur un sous-domaine de préversion** (`dev.tondomaine.fr`),
peaufiner dessus, puis basculer sur le domaine public quand c'est prêt.

---

## 0. Avant tout — générer et committer les médias IA (sur ton Mac)

Les vidéos/images générées par IA ne sont pas dans le dépôt (elles sont
téléchargées localement). Sans elles, le site en ligne affiche les emojis de
repli. Depuis le dossier du projet :

```bash
bash scripts/make-hero-video.sh          # vidéo hero + scènes + photos véhicules
git add public/assets && git commit -m "médias: hero + scènes + véhicules"
git push
```

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

## 6. Permissions du dossier d'upload

Le dossier `uploads/` doit être inscriptible (images de l'admin) :
```bash
chmod 755 uploads
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

### Dépannage rapide

| Symptôme | Cause probable | Solution |
|---|---|---|
| « Connexion base impossible » | Identifiants `.env` faux | Vérifie DB_NAME/USER/PASS (préfixe `moncompte_`) |
| Page blanche | `APP_ENV=dev` puis erreur, ou PHP < 8 | Passe en PHP 8.1+, regarde les logs cPanel |
| Images/vidéo absentes | Médias non commités | Refais l'étape 0 sur ton Mac |
| `.env` visible en 403→200 | `.htaccess` non pris en compte | Vérifie qu'Apache lit le `.htaccess` racine |
