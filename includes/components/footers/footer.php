<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: footer.php
 * ============================================================================
 * Purpose:
 * The global structural footer for the Stardust Engine Library interface. 
 * It closes out the main application wrapper and initializes core client-side 
 * scripts required for the Single Page Application (SPA) experience and 
 * offline capabilities.
 * 
 * Architectural Role:
 * Ensures proper DOM closure and script bootstrapping. Every view rendered 
 * by the engine must conclude by requiring this file.
 * 
 * Key Components:
 * 1. DOM Closure: Closes `#stardust-app` and the `body`/`html` tags.
 * 2. SPA Initialization: Loads `stardust-spa.min.js` from the CDN to hijack 
 *    internal links and enable smooth, AJAX-driven page transitions without 
 *    full reloads.
 * 3. PWA Bootstrapping: Registers the Service Worker (`sw.js`) to enable 
 *    asset caching, offline reading, and installation prompts.
 * 
 * Maintenance Notes:
 * - Ensure `sw.js` remains at the root directory of the domain; otherwise, 
 *   its scope will be limited, breaking offline functionality for higher-level 
 *   routes.
 * ============================================================================
 */
// Stardust Engine Library: Global Footer Component
?>
    </div> <!-- Close #stardust-app -->

    <!-- The hyper-optimized Stardust SPA Router -->
    <!-- Handles pushState routing and seamless DOM swapping -->
    <script src="https://assets.raggiesoft.com/stardust-engine-library/js/stardust-spa.min.js"></script>

    <!-- PWA Service Worker Registration -->
    <!-- Essential for offline reading and the "Add to Home Screen" native feel -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>
