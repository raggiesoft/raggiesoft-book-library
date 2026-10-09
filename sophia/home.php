<?php
// Sophia's Home View
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN to get cover data
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = @file_get_contents($catalogUrl);
$books = [];

if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}

?>

<div style="flex: 1; overflow-y: auto; background: var(--rs-bg); padding-bottom: 6rem; -webkit-overflow-scrolling: touch;">
    
    <!-- Native App Style Header -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 3rem 1.5rem 1rem;">
        
        <!-- Top Bar: Title & Settings -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <p id="rs-home-greeting" style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Welcome, Reader.
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

        <!-- Jump Back In Section (Client Side Rendered) -->
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
        
        <!-- Offline Shelf (Client Side Rendered) -->
        <div id="rs-home-offline" style="display: none; margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-cloud-check"></i> Available Offline
            </h2>
            <div id="rs-home-offline-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">
                <!-- Populated by JS -->
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

<!-- Bottom Navigation Bar for Mobile PWA Feel -->
<div style="position: fixed; bottom: 0; left: 0; right: 0; background: var(--rs-surface); border-top: 1px solid var(--rs-border); display: flex; justify-content: space-around; padding: 0.5rem 0; padding-bottom: env(safe-area-inset-bottom, 0.5rem); z-index: 100;">
    <a href="/" style="text-decoration: none; color: var(--rs-primary); display: flex; flex-direction: column; align-items: center; gap: 2px;">
        <i class="ph-fill ph-house" style="font-size: 1.5rem;"></i>
        <span style="font-size: 0.7rem; font-weight: 600;">Home</span>
    </a>
    <a href="/library" style="text-decoration: none; color: var(--rs-text); opacity: 0.7; display: flex; flex-direction: column; align-items: center; gap: 2px;">
        <i class="ph ph-books" style="font-size: 1.5rem;"></i>
        <span style="font-size: 0.7rem; font-weight: 600;">Library</span>
    </a>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 0. Update Greeting
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 18) greeting = 'Good afternoon';
    const greetingEl = document.getElementById('rs-home-greeting');
    if (greetingEl) greetingEl.textContent = greeting + ', Reader.';

    // 1. Load Jump Back In
    const lastRead = localStorage.getItem('rs-last-read');
    if (lastRead) {
        // We can extract series from URL e.g., /alex-chloe/book-1/chapter-1
        const parts = lastRead.split('/').filter(p => p.length > 0);
        if (parts.length >= 1) {
            const seriesSlug = parts[0];
            
            // Fetch catalog to find the book cover and title
            const catalog = <?php echo json_encode($books); ?>;
            const seriesData = catalog.find(b => b.slug === seriesSlug);
            
            if (seriesData) {
                document.getElementById('rs-home-jump-back').style.display = 'block';
                document.getElementById('rs-jump-series').textContent = seriesData.title;
                document.getElementById('rs-jump-part').textContent = "Continue your progress";
                document.getElementById('rs-jump-cover').src = "<?php echo $cdnBaseUrl; ?>" + seriesData.image;
                document.getElementById('rs-jump-btn').href = lastRead;
            }
        }
    }

    // 2. Load Offline Shelf
    const offlineBooks = [];
    const catalog = <?php echo json_encode($books); ?>;
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key.startsWith('rs-offline-')) {
            const seriesSlug = key.replace('rs-offline-', '');
            if (localStorage.getItem(key) === 'true') {
                const seriesData = catalog.find(b => b.slug === seriesSlug);
                if (seriesData) {
                    offlineBooks.push(seriesData);
                }
            }
        }
    }

    if (offlineBooks.length > 0) {
        const offlineShelf = document.getElementById('rs-home-offline');
        const offlineGrid = document.getElementById('rs-home-offline-grid');
        offlineShelf.style.display = 'block';
        
        offlineBooks.forEach(book => {
            const a = document.createElement('a');
            a.href = '/' + book.slug + '/';
            a.style = "text-decoration: none; color: inherit; display: block;";
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

