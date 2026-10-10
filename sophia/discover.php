<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: discover.php
 * =============================================================================
 * Purpose:
 *     This file serves as the main discovery hub for the Ocean View Archives.
 *     It dynamically fetches the global catalog from the Content Delivery 
 *     Network (CDN) and renders a randomized grid of featured books.
 *
 * Design Principles:
 *     - External State: Relies heavily on the CDN (`catalog.json`) to decouple 
 *       application logic from content storage.
 *     - Progressive Web App (PWA): Includes inline detection and prompting 
 *       for PWA installation to support offline caching.
 *     - Fault Tolerance: Uses `@file_get_contents` and null-coalescing (`??`) 
 *       to gracefully handle CDN outages or malformed JSON data.
 *
 * Maintenance Notes:
 *     - PWA installation logic is deeply tied to `rs-pwa-install-banner`. If 
 *       the app structure changes, ensure these DOM IDs remain consistent.
 *     - Currently, the "Featured" algorithm just shuffles all books. If performance 
 *       becomes an issue with a large catalog, shift randomization to the backend.
 * =============================================================================
 */

// Import necessary variables provided by the Stardust routing engine
global $cdnBaseUrl, $siteName, $requestUri;

// Retrieve the master catalog directly from the CDN to populate the discovery grid
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';

// Supress warnings if the CDN is unreachable and default to an empty string
$catalogData = @file_get_contents($catalogUrl);
$books = [];

// Parse the catalog data if successfully retrieved, defaulting to an empty array on failure
if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}

?>

<!-- 
  MAIN CONTAINER
  Utilizes 'stardust-mobile-scroll' to allow natural scrolling while reserving 
  bottom space (6rem) for the fixed navigation bar on mobile interfaces.
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    
    <!-- 
      NATIVE APP STYLE HEADER
      Provides safe area insets to prevent UI overlap with mobile notches 
      and constrains the layout width on desktop environments.
    -->
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- Top Navigation Bar: Displays dynamic greeting and settings toggle -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <!-- Client-side script updates this greeting based on time of day -->
                <p id="rs-home-greeting" style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Explore the Archives.
                </p>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Home
                </h1>
            </div>
            
        </div>

        <!-- 
          PWA INSTALL BANNER
          Hidden by default. Triggered via JavaScript if the app is not already 
          running in standalone mode and the user hasn't dismissed it.
        -->
        <div id="rs-pwa-install-banner" style="display: none; background: var(--rs-primary, #0056b3); color: white; border-radius: 12px; padding: 1rem; margin-bottom: 2rem; align-items: flex-start; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: flex-start; gap: 1rem; flex: 1;">
                <div style="width: 48px; height: 48px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                    <img src="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/icon-192.png" alt="App Icon" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Install the App</h3>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.9;">Tap Share &gt; Add to Home Screen</p>
                    <button id="rs-pwa-install-btn" style="display: none; margin-top: 0.5rem; background: white; color: var(--rs-primary, #0056b3); border: none; padding: 0.4rem 1rem; border-radius: 6px; font-weight: bold; font-size: 0.85rem; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Install Now</button>
                </div>
            </div>
            <!-- Dismiss banner button; saves preference to localStorage -->
            <button id="rs-pwa-close-banner" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 0.5rem;">
                <i class="ph ph-x" style="font-weight: bold;"></i>
            </button>
        </div>

        <!-- NOTE: Existing malformed stray HTML tags were present here in the original file. 
             Preserving exact structure but wrapping them to note the oddity. -->
                <div style="flex: 1;">
                    <h3 id="rs-jump-series" style="font-size: 1.1rem; margin: 0 0 0.25rem 0; font-weight: 700;">Loading...</h3>
                    <p id="rs-jump-part" style="font-size: 0.9rem; opacity: 0.7; margin: 0 0 1rem 0;"></p>
                    <a id="rs-jump-btn" href="#" class="rs-btn rs-btn-brand" style="display: inline-block; text-decoration: none; font-size: 0.9rem; padding: 0.5rem 1rem;">Resume Reading</a>
                </div>
            </div>
        </div>
        </div>

        <!-- 
          DISCOVERY GRID
          Renders a subset of the catalog to highlight random narratives.
        -->
        <div style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-books"></i> Discover
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">
                <?php 
                // Randomize the catalog to present a fresh selection on each load
                $featuredBooks = $books;
                shuffle($featuredBooks);
                
                // Limit the display to 4 items to keep the UI uncluttered
                $featuredBooks = array_slice($featuredBooks, 0, 4);
                
                foreach ($featuredBooks as $book): 
                    // Safely extract required metadata, providing fallbacks
                    $slug = $book['slug'] ?? '';
                    $title = $book['title'] ?? 'Unknown Archive';
                    // Prepend CDN base URL if an image exists, else use a local placeholder
                    $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                ?>
                <!-- Individual Book Card -->
                <a href="/<?php echo htmlspecialchars($slug); ?>/" style="text-decoration: none; color: inherit; display: block;">
                    <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 0.5rem;">
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($title); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    </div>
                    <h3 style="font-size: 0.9rem; font-weight: 600; margin: 0; line-height: 1.2; text-align: center;"><?php echo htmlspecialchars($title); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div>

</div>

<!-- Include the global bottom navigation component -->
<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>

<!-- 
  CLIENT-SIDE LOGIC
  Manages dynamic UI state such as time-based greetings and PWA install prompts.
-->
<script>
(function() {
    // Determine the appropriate greeting based on local client time
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 18) greeting = 'Good afternoon';
    const greetingEl = document.getElementById('rs-home-greeting');
    if (greetingEl) greetingEl.textContent = greeting + ', Reader.';

    // Initialize PWA Installation Banner logic
    // Check if the app is already running in standalone (installed) mode
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const banner = document.getElementById('rs-pwa-install-banner');
    const closeBtn = document.getElementById('rs-pwa-close-banner');
    const installBtn = document.getElementById('rs-pwa-install-btn');
    
    let deferredPrompt;

    // For iOS devices, we manually show the banner since iOS Safari doesn't support 'beforeinstallprompt'
    if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
        let isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        if (isIos()) {
            banner.style.display = 'flex';
        }
    }

    // Intercept standard PWA prompt event for Chromium-based browsers
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default automatic prompting
        e.preventDefault();
        // Stash the event for programmatic triggering later
        deferredPrompt = e;
        
        // Display our custom UI banner instead
        if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
            banner.style.display = 'flex';
            banner.querySelector('p').textContent = 'Install the web app for offline reading.';
            if (installBtn) {
                installBtn.style.display = 'block';
            }
        }
    });

    // Handle user interaction with the install button
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                // Await user's response to the native prompt
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    if (banner) banner.style.display = 'none';
                }
                deferredPrompt = null;
            }
        });
    }

    // Handle user dismissal of the banner
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (banner) banner.style.display = 'none';
            // Persist dismissal state to prevent annoying the user on subsequent visits
            localStorage.setItem('rs-pwa-banner-dismissed', 'true');
        });
    }
})();
</script>
