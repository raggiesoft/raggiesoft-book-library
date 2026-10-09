<?php
// Sophia's Catalog View (Standalone Library View)
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = @file_get_contents($catalogUrl);
$books = [];

if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}
?>

<div class="stardust-mobile-scroll" style="flex: 1; overflow-y: auto; background: var(--rs-bg); padding-bottom: 4rem; -webkit-overflow-scrolling: touch;">
    
    <!-- Native App Style Header -->
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- Top Bar: Title & Settings -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem;">
            <div>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Library
                </h1>
                <p style="font-size: 1rem; color: var(--rs-primary); margin: 0.25rem 0 0 0; font-weight: 600;">
                    Ocean View Archives
                </p>
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

        <!-- Full Width Search Bar -->
        <div style="position: relative; width: 100%; margin-bottom: 2rem;">
            <div style="position: relative; display: flex; align-items: center;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 1rem; color: var(--rs-text); opacity: 0.5; font-size: 1.25rem;"></i>
                <input type="text" id="rs-book-search" placeholder="Search titles, characters, or lore..." style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border-radius: 12px; border: none; background: var(--rs-surface); color: var(--rs-text); font-size: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); outline: none; -webkit-appearance: none;">
            </div>
            <div id="rs-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; max-height: 350px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 30px rgba(0,0,0,0.12); margin-top: 8px;"></div>
        </div>
        
    </div>

    <!-- Catalog Grid -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        
        <?php if (empty($books)): ?>
            <div style="text-align: center; padding: 4rem 0; opacity: 0.7;">
                <i class="ph ph-books" style="font-size: 4rem; color: var(--rs-primary); margin-bottom: 1rem;"></i>
                <h2>Library Offline</h2>
                <p>The system is currently compiling the archives. Please check back later.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.5rem 1rem;">
                
                <?php foreach ($books as $book): 
                    $slug = $book['slug'] ?? '';
                    $title = $book['title'] ?? 'Unknown Archive';
                    $desc = $book['description'] ?? '';
                    $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                    $readLink = '/' . $slug . '/';
                ?>
                
                <a href="<?php echo htmlspecialchars($readLink); ?>" class="rs-book-card" data-slug="<?php echo htmlspecialchars($slug); ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: transform 0.2s ease;">
                    <!-- Cover Image -->
                    <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: relative; margin-bottom: 0.75rem; border: 1px solid rgba(255,255,255,0.05);">
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($title); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    </div>
                    
                    <!-- Title & Subtitle -->
                    <div style="padding: 0 0.25rem;">
                        <h2 style="font-size: 1rem; font-weight: 700; margin: 0 0 0.25rem 0; color: var(--rs-heading); line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo htmlspecialchars($title); ?>
                        </h2>
                        <p style="font-size: 0.8rem; opacity: 0.6; margin: 0; line-height: 1.3;">
                            <?php echo htmlspecialchars($desc); ?>
                        </p>
                    </div>
                </a>
                
                <?php endforeach; ?>
                
            </div>
        <?php endif; ?>

    </div>

<!-- Instant Search JS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('rs-book-search');
    const resultsContainer = document.getElementById('rs-search-results');
    let searchIndex = null;
    let isFetching = false;

    const searchIndexUrl = 'https://assets.raggiesoft.com/raggiesoft-books/json/search-index.json';

    if (searchInput) {
        searchInput.addEventListener('focus', async function() {
            if (!searchIndex && !isFetching) {
                isFetching = true;
                try {
                    const response = await fetch(searchIndexUrl);
                    searchIndex = await response.json();
                } catch (err) {
                    console.error("Failed to load search index", err);
                }
            }
        });

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            resultsContainer.innerHTML = '';

            if (query.length < 3 || !searchIndex) {
                resultsContainer.style.display = 'none';
                return;
            }

            const results = searchIndex.filter(item => {
                return (item.title && item.title.toLowerCase().includes(query)) || 
                       (item.content && item.content.toLowerCase().includes(query)) ||
                       (item.book && item.book.toLowerCase().includes(query)) ||
                       (item.series && item.series.toLowerCase().includes(query));
            }).slice(0, 10); // Show top 10

            if (results.length > 0) {
                results.forEach(item => {
                    const div = document.createElement('a');
                    div.href = item.url.replace('/raggiesoft-books/books/', '/');
                    div.style = "display: block; padding: 1rem; border-bottom: 1px solid var(--rs-border); text-decoration: none; color: var(--rs-text); cursor: pointer;";
                    div.innerHTML = `
                        <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 4px; color: var(--rs-heading);">${item.series}: ${item.title}</div>
                        <div style="font-size: 0.8rem; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.book} &middot; ${item.chapter}</div>
                    `;
                    div.addEventListener('mouseenter', () => div.style.background = 'var(--rs-bg)');
                    div.addEventListener('mouseleave', () => div.style.background = 'transparent');
                    
                    resultsContainer.appendChild(div);
                });
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = '<div style="padding: 1rem; font-size: 0.9rem; opacity: 0.7; text-align: center;">No lore found matching your search.</div>';
                resultsContainer.style.display = 'block';
            }
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });
    }

    // PWA Banner Logic & Install Prompt
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const banner = document.getElementById('rs-pwa-install-banner');
    const closeBtn = document.getElementById('rs-pwa-close-banner');
    const installBtn = document.getElementById('rs-pwa-install-btn');
    
    let deferredPrompt;

    if (isStandalone) {
        // If launched as PWA, redirect to last read location if available
        const lastRead = localStorage.getItem('rs-last-read');
        // Only redirect if there is no hash or specific path loaded, and we are exactly on the root home page
        if (lastRead && window.location.pathname === '/' && !sessionStorage.getItem('rs-prevent-auto-redirect')) {
            sessionStorage.setItem('rs-prevent-auto-redirect', 'true'); // Prevent infinite loops if they click "Library" button to come back
            window.location.replace(lastRead);
        }
    } else if (banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
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
</div>

<!-- Bottom Navigation Bar for Mobile PWA Feel (Apple HIG) -->
<div style="position: fixed; bottom: 0; left: 0; right: 0; background: color-mix(in srgb, var(--rs-surface) 85%, transparent); backdrop-filter: saturate(180%) blur(20px); -webkit-backdrop-filter: saturate(180%) blur(20px); border-top: 0.5px solid color-mix(in srgb, var(--rs-text) 20%, transparent); display: flex; justify-content: space-around; padding-bottom: env(safe-area-inset-bottom); z-index: 100;">
    <a href="/" style="text-decoration: none; color: color-mix(in srgb, var(--rs-text) 50%, transparent); display: flex; flex-direction: column; align-items: center; justify-content: center; width: 50%; padding: 6px 0 4px 0;">
        <i class="ph ph-house" style="font-size: 24px; margin-bottom: 2px;"></i>
        <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 10px; font-weight: 500; letter-spacing: 0;">Home</span>
    </a>
    <a href="/library" style="text-decoration: none; color: var(--rs-primary); display: flex; flex-direction: column; align-items: center; justify-content: center; width: 50%; padding: 6px 0 4px 0;">
        <i class="ph-fill ph-books" style="font-size: 24px; margin-bottom: 2px;"></i>
        <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 10px; font-weight: 500; letter-spacing: 0;">Library</span>
    </a>
</div>
