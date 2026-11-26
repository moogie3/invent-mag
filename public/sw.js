/*
 * INVENT-MAG mobile PWA service worker.
 *
 * Bump VERSION on every deploy that changes cached shells or assets —
 * activate() drops the caches from older versions.
 *
 * Strategy per request (only for pages under the /m scope, which is the
 * only scope this worker controls):
 *   - /api/*            network only — tokens and stock numbers must never
 *                       be served from cache.
 *   - navigations       network first, cached shell as offline fallback
 *                       (the shells carry no data; the API fills them in).
 *   - /build/, /pwa/     cache first — Vite hashes make these immutable.
 *   - fonts.googleapis / fonts.gstatic
 *                       stale-while-revalidate, so the PWA keeps its
 *                       typography once it has been opened online.
 */
const VERSION = 'invent-mag-v2';
const STATIC_CACHE = `${VERSION}-static`;
const PAGE_CACHE = `${VERSION}-pages`;
const FONT_CACHE = `${VERSION}-fonts`;

const PRECACHE = [
    '/m/login',
    '/manifest.json',
    '/pwa/icon-192.png',
    '/pwa/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) =>
                // One failed precache must not abort the whole install.
                Promise.all(
                    PRECACHE.map((url) =>
                        cache.add(new Request(url, { cache: 'reload' })).catch(() => null)
                    )
                )
            )
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => !key.startsWith(VERSION))
                        .map((key) => caches.delete(key))
                )
            )
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    /*
     * Web fonts, handled before the same-origin gate: Google ships the CSS
     * per User-Agent, but a service worker cache is per-device, so what lands
     * here is exactly what this browser asked for. Serve from cache first so
     * an offline launch still renders in Inter/Manrope, and refresh in the
     * background so the cached copy does not go stale forever.
     */
    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(FONT_CACHE).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    })
            )
        );
        return;
    }

    if (url.origin !== self.location.origin) {
        return;
    }

    // Live data (auth, KPIs, POS stock) is never cached.
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const copy = response.clone();
                    caches.open(PAGE_CACHE).then((cache) => cache.put(request, copy));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) => cached || caches.match('/m/login'))
                )
        );
        return;
    }

    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/pwa/')) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    })
            )
        );
    }
});
