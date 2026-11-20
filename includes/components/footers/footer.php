<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: includes/components/footers/footer.php
 * App:  RaggieSoft Book Reader (`https://books.raggiesoft.com`)
 *
 * WHAT IS THIS FILE? ("THE BOTTOM SLICE OF THE HTML SANDWICH")
 *   Every page rendered by `eleanor/isabel.php` is assembled like a 3-layer
 *   sandwich:
 *     1. Top slice:    `includes/components/headers/header.php`
 *                      (opens `<!DOCTYPE html>`, `<head>`, `<body>`, `<audio id="stardust-bgm">`,
 *                      and `<div id="stardust-app">`)
 *     2. Middle layer: One of the `sophia/` views (plus `sophia/sidebar.php` & `sophia/oliver.php`)
 *     3. Bottom slice: **THIS FILE (`footer.php`)**, which closes `<div id="stardust-app">`,
 *                      loads the SPA router (`stardust-spa.min.js`), ensures the
 *                      offline Service Worker (`/sw.js?v=21`) is registered, and
 *                      closes `</body></html>`!
 *
 * WHY ARE THESE `<script>` TAGS PLACED *OUTSIDE* `</div> <!-- Close #stardust-app -->`?
 *   Remember how `stardust-spa.js` works: every time a reader clicks a chapter link,
 *   `stardust-spa.js` replaces `<div id="stardust-app">` with the new page's `#stardust-app`.
 *   Placing `<script src=".../stardust-spa.min.js">` AFTER `</div>` means the SPA router
 *   itself loads ONCE on initial page boot and is never torn down or re-downloaded
 *   during soft navigations!
 * =============================================================================
 */
?>
    </div> <!-- Close #stardust-app (opened at the bottom of includes/components/headers/header.php) -->

    <!--
      1. Stardust Engine Vanilla ES2024 SPA Router (`stardust-spa.min.js` compiled from `library/js/src/stardust-spa.js`):
         Intercepts internal `<a href>` clicks, fetches next HTML via `fetch()` + `DOMParser`,
         and swaps `#stardust-app` via `.replaceWith()` while keeping `<audio id="stardust-bgm">` playing!
    -->
    <script src="https://assets.raggiesoft.com/raggiesoft-books/library/js/stardust-spa.min.js?v=20261118d"></script>

    <!--
      2. Progressive Web App (PWA) Service Worker Registration Safety Net:
         - Checks `'serviceWorker' in navigator` so older or restricted browsers never throw errors.
         - Waits for `window` `'load'` so initial page rendering gets 100% network priority.
         - Registers `/sw.js?v=21` at root `scope: '/'` (so it can cache ANY route on `books.raggiesoft.com`)
           with `updateViaCache: 'none'` (forcing the browser to bypass HTTP disk cache when checking for SW updates).
    -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js?v=21', { scope: '/', updateViaCache: 'none' }).catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>
