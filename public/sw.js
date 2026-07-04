const CACHE_VERSION = 'v2';
const CACHE_PREFIX = 'storefront-pwa';
const OFFLINE_CACHE = `${CACHE_PREFIX}-offline-${CACHE_VERSION}`;
const STATIC_CACHE = `${CACHE_PREFIX}-static-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';
const STATIC_CACHE_MAX_ENTRIES = 80;

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/assets/pwa/icon-192.png',
    '/assets/pwa/badge-72.png',
    '/assets/css/pjojikhhoyutyrtd.css',
    '/assets/css/barrsopaosocas.css',
    '/assets/css/owihdagowdhqo.css',
    '/assets/css/seasonal-themes.css',
    '/assets/js/oo324ddod2323sd2dd.js',
];

const STATIC_ASSET_PATTERN = /\.(?:css|js|mjs|map|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2|ttf|eot)$/i;
const NETWORK_ONLY_PREFIXES = [
    '/admin',
    '/filament',
    '/livewire',
    '/api',
    '/ajax',
    '/callback',
    '/wejizy',
    '/login',
    '/logout',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/email',
    '/sanctum',
    '/csrf-cookie',
    '/senangpay/callback',
    '/id/invoices',
    '/id/deposit',
    '/id/dashboard',
    '/id/settings',
    '/id/reseller',
    '/id/harga',
    '/id/konfirmasi-data',
    '/check-voucher',
    '/available-voucher',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(OFFLINE_CACHE)
                .then((cache) => cache.addAll([OFFLINE_URL])),
            caches.open(STATIC_CACHE)
                .then((cache) => cache.addAll(
                    PRECACHE_URLS.filter((url) => url !== OFFLINE_URL)
                )),
        ])
    );
});

self.addEventListener('activate', (event) => {
    const expectedCaches = new Set([OFFLINE_CACHE, STATIC_CACHE]);

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName.startsWith(CACHE_PREFIX) && !expectedCaches.has(cacheName))
                    .map((cacheName) => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'PING_CONNECTION_STATUS' && event.source) {
        event.source.postMessage({
            type: 'CONNECTION_STATUS',
            online: true,
        });
    }
});

self.addEventListener('push', (event) => {
    const payload = safelyParseJson(event.data);
    const title = payload?.title || 'Notifikasi Baru';
    const options = {
        body: payload?.body || 'Ada promo atau update baru untuk aplikasi top up Anda.',
        icon: payload?.icon || '/assets/pwa/icon-192.png',
        badge: payload?.badge || '/assets/pwa/badge-72.png',
        tag: payload?.tag || 'public-pwa-push',
        data: {
            url: payload?.url || '/id',
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const rawTargetUrl = event.notification?.data?.url || '/id';
    const target = normalizeNotificationTarget(rawTargetUrl);

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (client.url === target.href && 'focus' in client) {
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(target.href);
            }

            return undefined;
        })
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || shouldUseNetworkOnly(url.pathname)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (STATIC_ASSET_PATTERN.test(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

function normalizeNotificationTarget(rawTargetUrl) {
    try {
        const target = new URL(rawTargetUrl, self.location.origin);

        if (target.origin !== self.location.origin) {
            return new URL('/id', self.location.origin);
        }

        return target;
    } catch (error) {
        return new URL('/id', self.location.origin);
    }
}

function shouldUseNetworkOnly(pathname) {
    return NETWORK_ONLY_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`));
}

function networkFirstNavigation(request) {
    return fetch(request)
        .catch(() => caches.match(OFFLINE_URL, { cacheName: OFFLINE_CACHE }));
}

function staleWhileRevalidate(request) {
    const cacheRequest = getNormalizedStaticCacheRequest(request);

    return caches.open(STATIC_CACHE).then((cache) => {
        return cache.match(cacheRequest).then((cachedResponse) => {
            const fetchPromise = fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.ok) {
                        cache.put(cacheRequest, networkResponse.clone());
                        trimCache(cache, STATIC_CACHE_MAX_ENTRIES);
                    }

                    return networkResponse;
                })
                .catch(() => cachedResponse);

            return cachedResponse || fetchPromise;
        });
    });
}

function getNormalizedStaticCacheRequest(request) {
    const url = new URL(request.url);
    return new Request(`${url.origin}${url.pathname}`, {
        method: 'GET',
        headers: request.headers,
    });
}

function trimCache(cache, maxEntries) {
    cache.keys().then((keys) => {
        if (keys.length <= maxEntries) {
            return;
        }

        cache.delete(keys[0]).then(() => trimCache(cache, maxEntries));
    });
}

function safelyParseJson(data) {
    if (!data) {
        return null;
    }

    try {
        return data.json();
    } catch (error) {
        return null;
    }
}
