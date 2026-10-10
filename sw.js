/**
 * Architectural Block Comment:
 * File: sw.js
 * Purpose:
 *     This is the core Service Worker script for the Progressive Web App. 
 *     It acts as a network proxy, intercepting HTTP requests to provide offline capabilities, 
 *     accelerate load times, and manage caching strategies for assets and narrative data.
 * 
 * Design Decisions & Future Maintenance:
 *     - Cache Versioning: Constants (`CACHE_NAME`, `DYNAMIC_CACHE`, `IMAGE_CACHE`) include a version string (`v4`). 
 *       When major changes are deployed, these versions MUST be incremented to force the service worker to clear 
 *       old caches during the `activate` phase.
 *     - Pre-caching: Critical app shells and UI assets are pre-cached during the `install` phase.
 *     - Caching Strategies:
 *         1. Cache First (Assets/Images): Static assets like fonts, scripts, stylesheets, and images are retrieved 
 *            from the cache immediately to save bandwidth. If missing, they are fetched from the network and cached.
 *         2. Network First (HTML/JSON): Narrative content and structural metadata are fetched from the network to ensure 
 *            the user always gets the latest story updates. If offline, it falls back to previously cached versions.
 */

// Cache partition identifiers
const CACHE_NAME = 'ova-cache-v4';
const DYNAMIC_CACHE = 'ova-dynamic-v4';
const IMAGE_CACHE = 'ova-images-v4';

// Minimum required assets to render the offline application shell
const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/scripts/pwa.js',
    '/favicon.ico',
    'https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/icon.svg'
];

/**
 * INSTALL EVENT
 * Triggered when the service worker is first registered or updated.
 * We use this phase to pre-cache the critical shell assets.
 */
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
});

/**
 * ACTIVATE EVENT
 * Triggered when the new service worker takes control of the page.
 * We use this phase to purge old/stale caches from previous versions.
 */
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(name => {
                    // Delete any cache bin that does not match our current version signatures
                    if (name !== CACHE_NAME && name !== DYNAMIC_CACHE && name !== IMAGE_CACHE) {
                        return caches.delete(name);
                    }
                })
            );
        })
    );
});

/**
 * FETCH EVENT
 * Intercepts all network requests initiated by the application.
 */
self.addEventListener('fetch', event => {
    // Only intercept GET requests. POST/PUT/DELETE requests should pass directly to the network.
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // 1. Cache First Strategy for Static Resources (Images, Fonts, Scripts, Styles)
    if (url.hostname.includes('assets.raggiesoft.com') || url.hostname.includes('fonts.googleapis.com') || url.hostname.includes('fonts.gstatic.com') || event.request.destination === 'image' || event.request.destination === 'style' || event.request.destination === 'script') {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                // Return immediately if found in cache
                if (cachedResponse) {
                    return cachedResponse;
                }
                // Otherwise fetch from network
                return fetch(event.request).then(response => {
                    // Segregate heavy images into their own cache bin
                    const cacheName = event.request.destination === 'image' ? IMAGE_CACHE : DYNAMIC_CACHE;
                    const responseClone = response.clone();
                    caches.open(cacheName).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                }).catch(() => {
                    // Ignore network failure for assets (will simply fail to load the image/font)
                });
            })
        );
        return;
    }

    // 2. Network First Strategy for Dynamic Data (HTML Pages, JSON APIs)
    event.respondWith(
        fetch(event.request).then(response => {
            // If the network succeeds, cache the fresh response for future offline use.
            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then(cache => {
                cache.put(event.request, responseClone);
            });
            return response;
        }).catch(() => {
            // If the network fails (offline), attempt to serve the last known good response from cache.
            return caches.match(event.request);
        })
    );
});
