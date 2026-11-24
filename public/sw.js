const CACHE_NAME = 'aurapoultry-static-v5';
const STATIC_ASSETS = [
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/images/poultry-house.jpg',
  '/assets/icons/logo-transparent.png',
  '/assets/icons/favicon-32.png',
  '/assets/icons/apple-touch-icon.png',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Only intercept static /assets/ files; let HTML & partial navigations go direct without SW overhead
  if (!url.pathname.startsWith('/assets/')) return;

  event.respondWith(
    caches.match(req).then((cached) => cached || fetch(req).then((res) => {
      const copy = res.clone();
      caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
      return res;
    }))
  );
});

