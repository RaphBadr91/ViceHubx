/* ViceHub X — Service Worker (PWA). Network-first avec repli cache hors-ligne. */
const CACHE = 'vicehubx-v1';
const SHELL = [
  '/', '/index.php',
  '/public/assets/css/style.css',
  '/public/assets/js/app.js',
  '/public/assets/js/vicefm.js',
  '/public/assets/img/icon-192.png'
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET' || !req.url.startsWith(self.location.origin)) return;
  // Ne pas mettre en cache l'admin ni les endpoints dynamiques
  if (/\/(admin|like|checkout|download|stripe-webhook)\b/.test(req.url)) return;
  e.respondWith(
    fetch(req).then((res) => {
      const copy = res.clone();
      caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
      return res;
    }).catch(() => caches.match(req).then((m) => m || caches.match('/index.php')))
  );
});
