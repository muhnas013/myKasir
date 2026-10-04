// Service Worker F6 — cache app-shell saja, BUKAN Background Sync API (docs/09_STACK.md).
// Strategi: network-first same-origin GET, fallback ke cache saat offline. Sinkronisasi
// antrean transaksi ditangani di halaman (resources/js/offline/sync.js), bukan di sini.

const CACHE = 'mykasir-shell-v1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            try {
                const response = await fetch(request);
                if (response.ok) {
                    cache.put(request, response.clone());
                }

                return response;
            } catch (err) {
                const cached = await cache.match(request);
                if (cached) {
                    return cached;
                }
                if (request.mode === 'navigate') {
                    const shell = await cache.match('/pos');
                    if (shell) {
                        return shell;
                    }
                }
                throw err;
            }
        }),
    );
});
