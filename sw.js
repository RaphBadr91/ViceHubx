/* ViceHub X — Service Worker (PWA).
 *
 * RÈGLE D'OR : on ne touche JAMAIS aux navigations ni au HTML.
 *   - Les pages (login, inscription, compte, articles…) sont TOUJOURS servies
 *     directement par le serveur, en frais. Cela évite deux bugs graves :
 *       1) Chrome interdit à un SW de servir une réponse « redirigée » à une
 *          navigation → ERR_INVALID_URL (ex. /login quand on est déjà connecté).
 *       2) un HTML mis en cache garde un jeton CSRF périmé → « Session expirée »
 *          à chaque connexion/inscription.
 *   - On met seulement en cache les ASSETS STATIQUES (CSS/JS/images/polices)
 *     pour la vitesse et un repli hors-ligne, sans jamais gêner l'auth.
 */
const CACHE = 'vicehubx-v2';
const SHELL = [
  '/public/assets/css/style.css',
  '/public/assets/js/app.js',
  '/public/assets/js/vicefm.js',
  '/public/assets/img/icon-192.png'
];

self.addEventListener('install', (e) => {
  // addAll() ne doit pas faire échouer l'install si un asset manque : on tolère.
  e.waitUntil(
    caches.open(CACHE)
      .then((c) => Promise.all(SHELL.map((u) => c.add(u).catch(() => {}))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  // Purge les anciens caches (dont v1 qui contenait du HTML) et prend la main.
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

// Extensions d'assets statiques qu'on a le droit de mettre en cache / servir.
const STATIC_RE = /\.(css|js|mjs|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|otf|mp4|webm|mp3)(\?.*)?$/i;

self.addEventListener('fetch', (e) => {
  const req = e.request;

  // 1) On ne gère QUE les GET same-origin.
  if (req.method !== 'GET') return;
  if (!req.url.startsWith(self.location.origin)) return;

  // 2) On laisse le navigateur gérer NATIVEMENT toute navigation / document HTML.
  //    (indispensable pour que les redirections serveur et le CSRF fonctionnent)
  if (req.mode === 'navigate') return;
  const accept = req.headers.get('accept') || '';
  if (accept.includes('text/html')) return;

  // 3) Jamais l'admin ni les endpoints dynamiques.
  if (/\/(admin|like|checkout|download|stripe-webhook|radio-playlist)\b/.test(req.url)) return;

  // 4) Seulement les vrais assets statiques ; le reste passe en direct au réseau.
  if (!STATIC_RE.test(req.url)) return;

  // Réseau d'abord (assets toujours frais), avec repli cache hors-ligne.
  e.respondWith(
    fetch(req).then((res) => {
      // Ne met en cache qu'une réponse propre, complète et NON redirigée.
      if (res && res.ok && !res.redirected && res.type === 'basic') {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
      }
      return res;
    }).catch(() => caches.match(req))
  );
});
