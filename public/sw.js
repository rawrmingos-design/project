const CACHE_VERSION = 'v1';
const CACHE_PREFIX = 'storefront-pwa';
const OFFLINE_CACHE = `${CACHE_PREFIX}-offline-${CACHE_VERSION}`;
const STATIC_CACHE = `${CACHE_PREFIX}-static-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

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
        caches.open(OFFLINE_CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL]))
            .then(() => self.skipWaiting())
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

function shouldUseNetworkOnly(pathname) {
    return NETWORK_ONLY_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`));
}

function networkFirstNavigation(request) {
    return fetch(request)
        .catch(() => caches.match(OFFLINE_URL, { cacheName: OFFLINE_CACHE }));
}

function staleWhileRevalidate(request) {
    return caches.open(STATIC_CACHE).then((cache) => {
        return cache.match(request).then((cachedResponse) => {
            const fetchPromise = fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.ok) {
                        cache.put(request, networkResponse.clone());
                    }

                    return networkResponse;
                })
                .catch(() => cachedResponse);

            return cachedResponse || fetchPromise;
        });
    });
}
