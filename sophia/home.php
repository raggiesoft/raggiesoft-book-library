<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: home.php
 * =============================================================================
 * Purpose:
 *     This file serves as the primary dashboard for returning users of the 
 *     Ocean View Archives. It dynamically renders personalized "Jump Back In" 
 *     (Resume Reading) shelves and "Available Offline" collections based on 
 *     the user's local reading history.
 *
 * Design Principles:
 *     - Client-Side Rendering (CSR) Mix: User history is strictly kept in 
 *       the browser (`localStorage`). PHP fetches the master catalog, and 
 *       JS merges the two to build personalized views securely without server state.
 *     - PWA Support: Identical to `discover.php`, includes standard offline 
 *       install prompts.
 *
 * Maintenance Notes:
 *     - If the `localStorage` key structure (`rs-last-read`, `rs-offline-*`) 
 *       changes in the core reader script, the JS block here must be updated 
 *       to match.
 *     - Ensure catalog data structure changes don't break the JS `find()` logic 
 *       used to map slugs to titles and covers.
 * =============================================================================
 */

// Import necessary variables provided by the Stardust routing engine
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN to populate cover data for local history
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';

// Suppress network errors; fallback to empty data if the CDN is unreachable
$catalogData = @file_get_contents($catalogUrl);
$books = [];

// Parse the returned JSON, defaulting safely to an empty array
if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}

$discoverConfigUrl = $cdnBaseUrl . "/raggiesoft-books/books/discover.json";
$discoverConfigData = @file_get_contents($discoverConfigUrl);
$discoverConfig = [];
if ($discoverConfigData) {
    $discoverConfig = json_decode($discoverConfigData, true) ?? [];
}

?>

<!-- 
  MAIN CONTAINER
  Uses mobile scrolling rules, appending padding to avoid collision 
  with the sticky bottom navigation menu.
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    
    <!-- 
      NATIVE APP STYLE HEADER
      Provides safe area insets to avoid mobile OS notch overlap and constrains max-width.
    -->
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- Top Navigation Bar: Displays greeting and quick access to settings -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <p id="rs-home-greeting" style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Welcome, Reader.
                </p>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Home
                </h1>
            </div>
            

        </div>

        <!-- 
          PWA INSTALL BANNER
          Prompt injected for supported browsers to encourage offline caching installation.
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
            <button id="rs-pwa-close-banner" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 0.5rem;">
                <i class="ph ph-x" style="font-weight: bold;"></i>
            </button>
        </div>

        <!-- 
          JUMP BACK IN SECTION
          Hidden natively; revealed via JS if a valid 'rs-last-read' entry exists in localStorage.
        -->
        <div id="rs-home-jump-back" style="display: none; margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-book-open"></i> Jump Back In
            </h2>
            <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1rem; display: flex; gap: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); align-items: center;">
                <div style="width: 80px; height: 120px; flex-shrink: 0; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <img id="rs-jump-cover" src="" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex: 1;">
                    <h3 id="rs-jump-series" style="font-size: 1.1rem; margin: 0 0 0.25rem 0; font-weight: 700;">Loading...</h3>
                    <p id="rs-jump-part" style="font-size: 0.9rem; opacity: 0.7; margin: 0 0 1rem 0;"></p>
                    <a id="rs-jump-btn" href="#" class="rs-btn rs-btn-brand" style="display: inline-block; text-decoration: none; font-size: 0.9rem; padding: 0.5rem 1rem;">Resume Reading</a>
                </div>
            </div>
        </div>
        
        <!-- 
          OFFLINE SHELF SECTION
          Hidden natively; populated and revealed via JS by scanning localStorage 
          for cached narrative flags.
        -->
        <!-- 
          EMPTY STATE
          Shown when the user has no recent reading history.
        -->
        <div id="rs-home-empty" style="display: none; background: var(--rs-surface); border: 1px dashed var(--rs-border); border-radius: 12px; padding: 2.5rem 1.5rem; text-align: center; margin-bottom: 2.5rem;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
                <i class="ph ph-books" style="font-size: 2rem; color: var(--rs-primary);"></i>
            </div>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 0.5rem;">Welcome to the Archives!</h2>
            <p style="color: var(--rs-text); opacity: 0.8; font-size: 0.95rem; margin: 0 0 1.5rem 0; line-height: 1.5;">Your reading shelf is currently empty. Browse the library to find your next great story.</p>
            <a href="/catalog" class="rs-btn rs-btn-brand" style="display: inline-block; text-decoration: none; font-size: 1rem; padding: 0.75rem 1.5rem; font-weight: 600;">Browse Library</a>
        </div>
        
        <div id="rs-home-offline" style="display: none; margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-cloud-check"></i> Available Offline
            </h2>
            <div id="rs-home-offline-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">
                <!-- Content dynamically generated by JS iteration over offline store -->
            </div>
        </div>

        <!-- 
          FEATURED HIGHLIGHTS
          Displays curated carousels from discover.json to make the home screen 
          feel alive even when the user has no history or offline books.
        -->
        <?php if (!empty($books)): ?>
        <div id="rs-home-featured" style="margin-bottom: 2.5rem;">
            <?php 
            // Helper function to render a horizontal carousel of books
            if (!function_exists('renderCarousel')) {
                function renderCarousel($title, $icon, $bookList, $cdnBaseUrl) {
                    if (empty($bookList)) return;
                    echo '<div style="margin-bottom: 2.5rem;">';
                    echo '<h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">';
                    echo '<i class="ph ' . htmlspecialchars($icon) . '"></i> ' . htmlspecialchars($title);
                    echo '</h2>';
                    echo '<div class="rs-carousel-container" style="display: flex; gap: 1.25rem; overflow-x: auto; padding-bottom: 1rem; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;">';
                    foreach ($bookList as $book) {
                        $slug = $book['slug'] ?? '';
                        $bookTitle = $book['title'] ?? 'Unknown Archive';
                        $author = 'Michael Ragsdale'; 
                        $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                        echo '<a href="/' . htmlspecialchars($slug) . '/" style="text-decoration: none; color: inherit; display: block; flex: 0 0 140px; scroll-snap-align: start;">';
                        echo '  <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 0.75rem;">';
                        echo '      <img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($bookTitle) . '" style="width: 100%; height: 100%; object-fit: cover; display: block;">';
                        echo '  </div>';
                        echo '  <h3 style="font-size: 0.9rem; font-weight: 700; margin: 0 0 0.25rem 0; line-height: 1.2; color: var(--rs-heading); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">' . htmlspecialchars($bookTitle) . '</h3>';
                        echo '  <p style="font-size: 0.75rem; opacity: 0.7; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">' . htmlspecialchars($author) . '</p>';
                        echo '</a>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }

            // Fetch Carousel Configuration from CDN
            $discoverConfigUrl = $cdnBaseUrl . '/raggiesoft-books/books/discover.json';
            $discoverData = @file_get_contents($discoverConfigUrl);
            $carousels = [];
            
            if ($discoverData) {
                $carousels = json_decode($discoverData, true) ?? [];
            }
            
            $catalogMap = [];
            foreach ($books as $b) {
                if (isset($b['slug'])) {
                    $catalogMap[$b['slug']] = $b;
                }
            }
            
            // Render first two carousels on the Home Screen
            if (!empty($carousels)) {
                $count = 0;
                foreach ($carousels as $carousel) {
                    if ($count >= 2) break; // Only show top 2 carousels
                    $carouselTitle = $carousel['title'] ?? 'Featured';
                    $carouselIcon = $carousel['icon'] ?? 'ph-books';
                    $slugs = $carousel['books'] ?? [];
                    
                    $carouselBooks = [];
                    foreach ($slugs as $s) {
                        if (isset($catalogMap[$s])) {
                            $carouselBooks[] = $catalogMap[$s];
                        }
                    }
                    
                    renderCarousel($carouselTitle, $carouselIcon, $carouselBooks, $cdnBaseUrl);
                    $count++;
                }
            } else {
                // Fallback
                $fallbackBooks = $books;
                shuffle($fallbackBooks);
                renderCarousel("Featured Archives", "ph-star", array_slice($fallbackBooks, 0, 6), $cdnBaseUrl);
            }
            ?>
        </div>
        <style>
        /* Hide scrollbar for carousels to maintain clean native look */
        .rs-carousel-container::-webkit-scrollbar {
            display: none;
        }
        .rs-carousel-container {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        </style>
        <?php endif; ?>

        <!-- NOTE: Existing stray div preserved for structural integrity. -->
        </div>
</div>

</div>

<!-- Render bottom tab bar component -->
<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>

<!-- 
  CLIENT-SIDE DATA RESOLUTION LOGIC
  Extracts user data from localStorage and matches it against the PHP-injected catalog.
-->
<script>
(function() {
    // Safely inject PHP catalog into JS context once
    const catalog = <?php echo json_encode($books, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;

    // 0. Update Greeting text based on the user's current local hour
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 18) greeting = 'Good afternoon';
    const greetingEl = document.getElementById('rs-home-greeting');
    if (greetingEl) greetingEl.textContent = greeting + ', Reader.';

    // 1. Process "Jump Back In" State
    // Check localStorage for the exact path the user was last on
    const lastRead = localStorage.getItem('rs-last-read');
    const launchBehavior = localStorage.getItem('rs-launch-behavior') || 'home';
    if (launchBehavior === 'resume' && lastRead && window.location.pathname === '/' && !sessionStorage.getItem('rs-prevent-auto-redirect')) {
        sessionStorage.setItem('rs-prevent-auto-redirect', 'true');
        window.location.replace(lastRead);
    }
    let matched = false;
    if (lastRead) {
        // Extract the root narrative slug from the saved path (e.g., /alex-chloe/...)
        const parts = lastRead.split('/').filter(p => p.length > 0);
        if (parts.length >= 1) {
            const seriesSlug = parts[0];
            
            // Match the slug to extract specific metadata (title/cover)
            const seriesData = catalog.find(b => b.slug === seriesSlug);
            
            if (seriesData) {
                // Unhide the section and inject the data
                document.getElementById('rs-home-jump-back').style.display = 'block';
                document.getElementById('rs-jump-series').textContent = seriesData.title;
                document.getElementById('rs-jump-part').textContent = "Continue your progress";
                // Prepend base URL to image path
                document.getElementById('rs-jump-cover').src = "<?php echo $cdnBaseUrl; ?>" + seriesData.image;
                document.getElementById('rs-jump-btn').href = lastRead;
                matched = true;
            }
        }
    }
    
    if (!matched) {
        document.getElementById('rs-home-empty').style.display = 'block';
    }

    // 2. Process "Offline Shelf" State
    // Scan through all localStorage keys for active offline caches
    const offlineBooks = [];
    
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        // Identify offline indicator keys
        if (key.startsWith('rs-offline-')) {
            const seriesSlug = key.replace('rs-offline-', '');
            // Only push if the cache flag is explicitly enabled ('true')
            if (localStorage.getItem(key) === 'true') {
                const seriesData = catalog.find(b => b.slug === seriesSlug);
                if (seriesData) {
                    offlineBooks.push(seriesData);
                }
            }
        }
    }

    // If matches found, render the offline grid dynamically
    if (offlineBooks.length > 0) {
        const offlineShelf = document.getElementById('rs-home-offline');
        const offlineGrid = document.getElementById('rs-home-offline-grid');
        // Unhide container
        offlineShelf.style.display = 'block';
        
        // Generate DOM elements for each offline narrative
        offlineBooks.forEach(book => {
            const a = document.createElement('a');
            a.href = '/' + book.slug + '/';
            a.style = "text-decoration: none; color: inherit; display: block;";
            
            // Render card UI featuring an offline checkmark indicator
            a.innerHTML = `
                <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 0.5rem; position: relative;">
                    <img src="<?php echo $cdnBaseUrl; ?>${book.image}" alt="${book.title}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    <div style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.6); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                        <i class="ph-fill ph-check-circle" style="font-size: 1rem;"></i>
                    </div>
                </div>
                <h3 style="font-size: 0.85rem; font-weight: 600; margin: 0; line-height: 1.2; text-align: center;">${book.title}</h3>
            `;
            offlineGrid.appendChild(a);
        });
    }

    // 3. Initialize PWA Install Banner
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const banner = document.getElementById('rs-pwa-install-banner');
    const closeBtn = document.getElementById('rs-pwa-close-banner');
    const installBtn = document.getElementById('rs-pwa-install-btn');
    
    let deferredPrompt;

    // Manually display banner on iOS due to lacking browser API support
    if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
        let isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        if (isIos()) {
            banner.style.display = 'flex';
        }
    }

    // Capture standard install prompt events on supported platforms
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default native banner popups
        e.preventDefault();
        deferredPrompt = e;
        
        // Show our themed banner
        if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
            banner.style.display = 'flex';
            banner.querySelector('p').textContent = 'Install the web app for offline reading.';
            if (installBtn) {
                installBtn.style.display = 'block';
            }
        }
    });

    // Execute native prompt when user interacts with our button
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                // Auto-dismiss banner if user accepts
                if (outcome === 'accepted') {
                    if (banner) banner.style.display = 'none';
                }
                deferredPrompt = null;
            }
        });
    }

    // Persist user dismissal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (banner) banner.style.display = 'none';
            localStorage.setItem('rs-pwa-banner-dismissed', 'true');
        });
    }
})();
</script>
