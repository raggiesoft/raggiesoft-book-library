<?php
/**
 * DISCOVER VIEW (discover.php)
 * ---------------------------------------------------------
 * This file serves as the main discovery hub for the Ocean View Archives.
 * It reads the global catalog from the CDN and renders a grid of featured
 * or random books, allowing users to browse available narratives.
 */

// Sophia's Discover View
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN to get cover data
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = @file_get_contents($catalogUrl);
$books = [];

if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}

?>

<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    
    <!-- NATIVE APP STYLE HEADER
       This wrapper ensures the content does not overlap with safe areas (notches) 
       and provides a clean max-width layout for tablet/desktop.
    -->
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- Top Bar: Title & Settings -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <p id="rs-home-greeting" style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Explore the Archives.
                </p>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Home
                </h1>
            </div>
            
            <button id="reader-settings-toggle" class="rs-btn" style="background: transparent; color: var(--rs-primary); border: none; padding: 0.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="ph ph-gear" style="font-size: 1.75rem;"></i>
            </button>
        </div>

        <!-- Add to Home Screen Banner -->
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
            <button id="rs-pwa-close-banner" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 0.5rem;">
                <i class="ph ph-x" style="font-weight: bold;"></i>
            </button>
        </div>

        
                <div style="flex: 1;">
                    <h3 id="rs-jump-series" style="font-size: 1.1rem; margin: 0 0 0.25rem 0; font-weight: 700;">Loading...</h3>
                    <p id="rs-jump-part" style="font-size: 0.9rem; opacity: 0.7; margin: 0 0 1rem 0;"></p>
                    <a id="rs-jump-btn" href="#" class="rs-btn rs-btn-brand" style="display: inline-block; text-decoration: none; font-size: 0.9rem; padding: 0.5rem 1rem;">Resume Reading</a>
                </div>
            </div>
        </div>
        
        
        </div>

        <!-- Featured / All Narratives -->
        <div style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-books"></i> Discover
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">
                <?php 
                // Show a few random or featured books
                $featuredBooks = $books;
                shuffle($featuredBooks);
                $featuredBooks = array_slice($featuredBooks, 0, 4);
                foreach ($featuredBooks as $book): 
                    $slug = $book['slug'] ?? '';
                    $title = $book['title'] ?? 'Unknown Archive';
                    $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                ?>
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

<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 0. Update Greeting
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 18) greeting = 'Good afternoon';
    const greetingEl = document.getElementById('rs-home-greeting');
    if (greetingEl) greetingEl.textContent = greeting + ', Reader.';

    // PWA Banner & Install Prompt
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const banner = document.getElementById('rs-pwa-install-banner');
    const closeBtn = document.getElementById('rs-pwa-close-banner');
    const installBtn = document.getElementById('rs-pwa-install-btn');
    
    let deferredPrompt;

    if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
        let isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        if (isIos()) {
            banner.style.display = 'flex';
        }
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt = e;
        
        // Show the install button and banner
        if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
            banner.style.display = 'flex';
            banner.querySelector('p').textContent = 'Install the web app for offline reading.';
            if (installBtn) {
                installBtn.style.display = 'block';
            }
        }
    });

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    if (banner) banner.style.display = 'none';
                }
                deferredPrompt = null;
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (banner) banner.style.display = 'none';
            localStorage.setItem('rs-pwa-banner-dismissed', 'true');
        });
    }
});
</script>

