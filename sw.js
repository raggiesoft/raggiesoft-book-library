/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: sw.js
 * URL:  https://books.raggiesoft.com/sw.js
 *
 * WHAT IS A SERVICE WORKER?
 *   Normally, JavaScript runs inside a web page's tab and stops running the
 *   moment you close the tab—and if your Wi-Fi or cellular signal drops on a
 *   subway or airplane, clicking a link shows the browser's offline error screen.
 *
 *   A **Service Worker** (`sw.js`) is a special background JavaScript file that
 *   the browser installs and runs on its own background thread, separate from
 *   the web page DOM (which is why this file uses `self` instead of `window`,
 *   and never touches `document`). It acts like a smart local traffic controller
 *   sitting between the browser tab and the internet!
 *
 * WHY DOES THIS FILE EXIST?
 *   1. **Offline Reading**: Using the browser's built-in **Cache Storage API**
 *      (`caches.open(...)`), it saves previously visited chapters, stylesheets,
 *      fonts, and scene illustrations on the reader's device so they can keep
 *      reading offline.
 *   2. **Instant Asset Loading**: Fonts, CSS, scripts, and CDN images load
 *      almost instantaneously from disk ("Cache First") without waiting on the network.
 *   3. **PWA Installability**: Having a root-scoped Service Worker (`/sw.js`) paired
 *      with `/manifest.json` allows iOS, Android, macOS, and Windows to install
 *      RaggieSoft Book Reader to the Home Screen / Dock like a native app.
 *
 * WHAT FEEDS INTO IT & WHAT LIFECYCLE EVENTS RUN HERE?
 *   Registered by `scripts/pwa.js` and `includes/components/footers/footer.php`,
 *   every Service Worker goes through three core browser events:
 *     1. `'install'`  -> Downloads the minimum app shell (`PRECACHE_ASSETS`) into
 *                        `CACHE_NAME` and calls `self.skipWaiting()` to activate immediately.
 *     2. `'activate'` -> Deletes outdated cache bins from older deployments (`v20`, `v19`, etc.)
 *                        and calls `self.clients.claim()` to take control of open tabs right away.
 *     3. `'fetch'`    -> Fires every time the app requests a URL (`<a>` navigation, `fetch()`,
 *                        `<img src>`, `<link rel="stylesheet">`, `<audio src>`), choosing whether
 *                        to bypass, serve Cache-First, or serve Network-First!
 * =============================================================================
 */

// =============================================================================
// 1. VERSIONED CACHE STORAGE BINS
// =============================================================================
// Think of `caches` (the Cache Storage API) like a miniature file cabinet inside
// the browser where each named drawer stores `Request -> Response` pairs.
// Whenever we deploy breaking changes to cached assets, incrementing `v21` -> `v22`
// causes the `activate` listener below to automatically delete old `v21` drawers!
// (Incremented to `v21` to purge legacy partial-range audio caches across Safari/Chromium PWAs.)
const CACHE_NAME = 'ova-cache-v21';       // Core offline app shell (`/`, `/library`, icons)
const DYNAMIC_CACHE = 'ova-dynamic-v21';  // Visited HTML chapters, JSON manifests, CSS, & JS
const IMAGE_CACHE = 'ova-images-v21';     // Book covers & scene illustrations

// Minimum required URLs pre-downloaded during `'install'` so the app shell boots offline.
// Note: `/manifest.json` is intentionally excluded from `PRECACHE_ASSETS` and bypassed in `fetch`
// so Chrome/Safari's `ManifestUpdateManager` always reads live app metadata and icons directly.
const PRECACHE_ASSETS = [
    '/',
    '/library',
    '/arcs',
    '/scripts/pwa.js?v=20261118d',
    '/favicon.ico?v=4',
    '/icons/icon-192.png?v=4',
    '/icons/icon-512.png?v=4',
    '/icons/icon.svg?v=4'
];

// =============================================================================
// 2. LIFECYCLE PHASE 1: `install`
// =============================================================================
// Runs once when the browser first downloads `sw.js` (or detects byte-level changes in `sw.js`).
self.addEventListener('install', event => {
    // By default, an updated Service Worker waits until ALL open tabs of the site are closed.
    // `self.skipWaiting()` tells the browser: "Promote this new worker to active right now!"
    self.skipWaiting();

    // `event.waitUntil(promise)` tells the browser: "Don't mark installation finished
    // (and don't put this worker to sleep) until `cache.addAll(...)` finishes downloading
    // every URL in `PRECACHE_ASSETS`."
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
});

// =============================================================================
// 3. LIFECYCLE PHASE 2: `activate`
// =============================================================================
// Runs right after `install` finishes and the new Service Worker takes over.
self.addEventListener('activate', event => {
    event.waitUntil(
        // `caches.keys()` returns a Promise resolving to an array of all existing drawer names
        // (e.g. `['ova-cache-v20', 'ova-cache-v21', ...]`).
        caches.keys().then(cacheNames => {
            // `Promise.all(...)` waits for every `caches.delete(...)` Promise to finish in parallel.
            return Promise.all(
                cacheNames.map(name => {
                    // Delete any cache drawer that doesn't match our three active `v21` constants!
                    if (name !== CACHE_NAME && name !== DYNAMIC_CACHE && name !== IMAGE_CACHE) {
                        return caches.delete(name);
                    }
                })
            );
        // `self.clients.claim()` tells all currently open RaggieSoft Book Reader tabs to start
        // routing their `fetch` requests through THIS newly activated worker immediately.
        }).then(() => self.clients.claim())
    );
});

// =============================================================================
// 4. LIFECYCLE PHASE 3: `fetch` (NETWORK REQUEST INTERCEPTOR)
// =============================================================================
// Runs every single time the web app requests any URL. If we return early WITHOUT
// calling `event.respondWith(...)`, the browser performs a normal network request
// as if no Service Worker existed.
self.addEventListener('fetch', event => {
    // Guard A: Only cache read-only `GET` requests (never intercept `POST`, `PUT`, or `DELETE`).
    if (event.request.method !== 'GET') return;

    // Parse the request string into a structured `URL` object (`url.hostname`, `url.pathname`, etc.).
    const url = new URL(event.request.url);

    // Guard B: Never cache `/sw.js` itself, `/manifest.json`, `/robots.txt`, or XML sitemaps
    // so browsers and search crawlers always receive the live files directly from Nginx.
    if (
        url.pathname === '/sw.js' ||
        url.pathname === '/manifest.json' ||
        url.pathname === '/robots.txt' ||
        url.pathname === '/sitemap.xml' ||
        url.pathname.startsWith('/sitemaps/')
    ) {
        return;
    }

    // Guard C: CRITICAL SAFARI / WEBKIT AUDIO STREAMING & LARGE BINARY BYPASS!
    // Why is this here?
    //   When `<audio id="stardust-bgm">` plays a chapter soundtrack `.mp3`, Apple's Safari/WebKit
    //   CoreMedia engine (especially in iOS/macOS standalone PWAs) sends an HTTP `Range: bytes=0-1`
    //   probe and strictly requires a native `206 Partial Content` streaming response from the CDN.
    //   If a Service Worker intercepts or caches `.mp3` streams, Safari audio silently refuses to play!
    //   We also bypass large `.epub`, `.pdf`, `.7z`, and `.zip` downloads so they stream directly
    //   to the user's Downloads folder without bloating browser Cache Storage quota.
    if (
        event.request.destination === 'audio' ||
        event.request.destination === 'video' ||
        event.request.headers.has('range') ||
        url.pathname.includes('/web-mp3/') ||
        /\.(mp3|wav|ogg|flac|m4a|aac|mp4|webm|epub|pdf|7z|zip)$/i.test(url.pathname)
    ) {
        return;
    }

    // =========================================================================
    // STRATEGY 1: "CACHE FIRST, FALLBACK TO NETWORK" (Static Assets)
    // =========================================================================
    // Used for CDN assets (`assets.raggiesoft.com`), Google Fonts, images, CSS, and JS.
    // Since static asset URLs use `?v=...` cache-busting query strings when updated,
    // serving a cached hit immediately gives zero-latency rendering and saves mobile data!
    if (url.hostname.includes('assets.raggiesoft.com') || url.hostname.includes('fonts.googleapis.com') || url.hostname.includes('fonts.gstatic.com') || event.request.destination === 'image' || event.request.destination === 'style' || event.request.destination === 'script') {
        event.respondWith(
            // Step 1: Check if we already saved a copy of this exact request in Cache Storage.
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse; // Instant cache hit! No network trip needed.
                }
                // Step 2: Cache miss — fetch it from the internet.
                return fetch(event.request).then(response => {
                    // Only save complete `200 OK` responses or valid cross-origin `'opaque'` responses
                    // (never save `206 Partial Content`, `404`, or `500` error pages!).
                    if (response && response.status !== 206 && (response.status === 200 || response.type === 'opaque')) {
                        const cacheName = event.request.destination === 'image' ? IMAGE_CACHE : DYNAMIC_CACHE;
                        // Why `response.clone()`?
                        // In the Fetch API, an HTTP Response body is a one-time readable stream:
                        // once read by the browser to display the image/CSS, the stream is consumed!
                        // Cloning creates two independent streams—one for `cache.put()` and one returned to the page.
                        const responseClone = response.clone();
                        caches.open(cacheName).then(cache => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                }).catch(() => {
                    // Offline & uncached static asset: fail quietly without throwing uncaught Promise errors.
                });
            })
        );
        return;
    }

    // =========================================================================
    // STRATEGY 2: "NETWORK FIRST, FALLBACK TO CACHE" (HTML Chapters & JSON Data)
    // =========================================================================
    // Used for narrative HTML routes (`/katie`, `/rachel/...`) and JSON manifests.
    // Why Network First here?
    //   When a reader is online, we ALWAYS want them to see newly published chapters
    //   or prose edits right away (`{ cache: 'no-cache' }` bypasses stale browser disk cache),
    //   while quietly saving a fresh copy in `DYNAMIC_CACHE`. If their internet connection
    //   is down (`.catch(...)`), we seamlessly serve their cached offline copy!
    event.respondWith(
        fetch(event.request, { cache: 'no-cache' }).then(response => {
            // Network succeeded: clone & save the fresh HTML/JSON for future offline reading.
            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then(cache => {
                cache.put(event.request, responseClone);
            });
            return response;
        }).catch(() => {
            // Network failed (reader is offline): serve the last saved copy from Cache Storage!
            return caches.match(event.request);
        })
    );
});
