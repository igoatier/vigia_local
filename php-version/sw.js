const CACHE_NAME = 'vigiacheck-static-v2';
const STATIC_ASSETS = [
  './assets/app.css',
  './assets/icon.svg',
  './manifest.webmanifest',
  './assets/registro-offline.js'
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
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('sync', (event) => {
  if (event.tag !== 'vigia-registros') return;
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientes) => clientes.forEach((cliente) => cliente.postMessage({ type: 'vigia-sync' })))
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  if (request.destination === 'style' || request.destination === 'image' || url.pathname.endsWith('.webmanifest')) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        const copy = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        return response;
      }))
    );
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => new Response(`<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VigiaCheck offline</title><style>body{margin:0;padding:32px;background:#0b0e14;color:#f4f1ea;font:16px system-ui}main{max-width:420px;margin:15vh auto}h1{color:#f5b841}p{color:#8b95a6;line-height:1.6}</style><main><h1>Você está offline</h1><p>O VigiaCheck precisa de conexão para abrir esta tela com segurança. Verifique sua internet e tente novamente.</p></main>`, { headers: { 'Content-Type': 'text/html; charset=utf-8' } }))
    );
  }
});
