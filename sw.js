const CACHE_NAME = 'ova-cache-v3';
const DYNAMIC_CACHE = 'ova-dynamic-v3';
const IMAGE_CACHE = 'ova-images-v3';

const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/scripts/pwa.js',
    '/favicon.ico',
    '/icons/icon.svg'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(name => {
                    if (name !== CACHE_NAME && name !== DYNAMIC_CACHE && name !== IMAGE_CACHE) {
                        return caches.delete(name);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // 1. Cache First for Images and Assets (assets.raggiesoft.com, fonts)
    if (url.hostname.includes('assets.raggiesoft.com') || url.hostname.includes('fonts.googleapis.com') || url.hostname.includes('fonts.gstatic.com') || event.request.destination === 'image' || event.request.destination === 'style' || event.request.destination === 'script') {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then(response => {
                    const cacheName = event.request.destination === 'image' ? IMAGE_CACHE : DYNAMIC_CACHE;
                    const responseClone = response.clone();
                    caches.open(cacheName).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                }).catch(() => {
                    // Ignore network failure for assets
                });
            })
        );
        return;
    }

    // 2. Network First for HTML and JSON Data
    event.respondWith(
        fetch(event.request).then(response => {
            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then(cache => {
                cache.put(event.request, responseClone);
            });
            return response;
        }).catch(() => {
            return caches.match(event.request);
        })
    );
});
