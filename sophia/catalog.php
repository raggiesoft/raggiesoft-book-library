<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: catalog.php
 * ============================================================================
 * Purpose:
 * This file renders the standalone "Library" view (internally named Sophia).
 * It fetches the master catalog JSON directly from the CDN and provides an 
 * interface for users to browse, search, and organize their narrative 
 * collections using a drag-and-drop UI.
 * 
 * Architectural Role:
 * Serves as the primary entry point for users to discover and organize content.
 * It operates independently of the core reading engine (Oliver) but relies on 
 * the same CDN backend for data. It includes offline support prompts (PWA) 
 * and local storage persistence for custom collections.
 * 
 * Key Components:
 * 1. Data Fetching: Retrieves `catalog.json` from the CDN.
 * 2. UI Layout: A mobile-first, native-app-styled grid layout.
 * 3. Search functionality: Client-side searching via `search-index.json`.
 * 4. PWA Integration: Banner and logic to prompt "Add to Home Screen".
 * 5. Collection Management: Uses SortableJS to allow drag-and-drop book 
 *    organization, saved entirely client-side in `localStorage`.
 * 
 * Maintenance Notes:
 * - SortableJS logic and DOM manipulation are tightly coupled. Changes to the 
 *   HTML structure (like `.rs-collection-block` or `.rs-book-card`) require 
 *   updating the JavaScript selectors.
 * - LocalStorage is the sole source of truth for custom collections. Clearing 
 *   browser data will reset the user's library.
 * ============================================================================
 */

// Sophia's Catalog View (Standalone Library View)
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN
// Utilizing error suppression (@) to prevent catastrophic UI failure on network issues.
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = @file_get_contents($catalogUrl);
$books = [];

if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}
?>

<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 4rem;">
    
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
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <!-- Collection Organization Toggle Buttons -->
                <button id="rs-organize-btn" class="rs-btn" style="background: transparent; color: var(--rs-primary); border: none; padding: 0.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Organize Library">
                    <i class="ph ph-folder" style="font-size: 1.75rem;"></i>
                </button>
                <button id="rs-organize-done-btn" class="rs-btn" style="display: none; background: var(--rs-primary); color: white; border: none; padding: 0.4rem 1rem; border-radius: 6px; font-weight: bold; font-size: 0.85rem; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    Done
                </button>
                <button id="reader-settings-toggle" class="rs-btn" style="background: transparent; color: var(--rs-primary); border: none; padding: 0.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <i class="ph ph-gear" style="font-size: 1.75rem;"></i>
                </button>
            </div>
        </div>

        <!-- Add to Home Screen Banner -->
        <!-- Displayed specifically for iOS/Mobile users not running in standalone mode -->
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
        <!-- Drives the instant client-side search component -->
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
            <!-- Fallback state when CDN is unreachable or catalog is empty -->
            <div style="text-align: center; padding: 4rem 0; opacity: 0.7;">
                <i class="ph ph-books" style="font-size: 4rem; color: var(--rs-primary); margin-bottom: 1rem;"></i>
                <h2>Library Offline</h2>
                <p>The system is currently compiling the archives. Please check back later.</p>
            </div>
        <?php else: ?>
            <div id="rs-collections-container">
                <!-- Template for new collections -->
                <!-- Cloned via JS when a user creates a new organizational tier -->
                <template id="rs-collection-template">
                    <div class="rs-collection-block" style="margin-bottom: 2rem;">
                        <div class="rs-collection-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <h2 class="rs-collection-title" style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--rs-heading);">New Collection</h2>
                            <button class="rs-btn rs-delete-collection-btn" style="background: transparent; color: var(--rs-red, #ff3b30); border: none; padding: 0.2rem; cursor: pointer; display: none;"><i class="ph ph-trash"></i></button>
                        </div>
                        <div class="rs-book-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.5rem 1rem; min-height: 240px; border: 2px dashed transparent; border-radius: 12px; padding: 0.5rem; transition: border-color 0.2s ease;">
                            <!-- Books go here -->
                        </div>
                    </div>
                </template>

                <!-- Default Collection Pool -->
                <!-- Contains all books by default before user organization -->
                <div class="rs-collection-block" data-collection-id="default" style="margin-bottom: 2rem;">
                    <div class="rs-collection-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <h2 class="rs-collection-title" style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--rs-heading); display: none;">Uncategorized</h2>
                        <button id="rs-add-collection-btn" class="rs-btn" style="background: transparent; color: var(--rs-primary); border: 1px solid var(--rs-primary); padding: 0.3rem 0.75rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer; display: none;">+ New Collection</button>
                    </div>
                    <div id="rs-default-pool" class="rs-book-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.5rem 1rem; min-height: 240px; border: 2px dashed transparent; border-radius: 12px; padding: 0.5rem; margin: -0.5rem; transition: border-color 0.2s ease;">
                        
                        <?php foreach ($books as $book): 
                            $slug = $book['slug'] ?? '';
                            $title = $book['title'] ?? 'Unknown Archive';
                            $desc = $book['description'] ?? '';
                            $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                            $readLink = '/' . $slug . '/';
                        ?>
                        
                        <!-- Individual Book Card -->
                        <a href="<?php echo htmlspecialchars($readLink); ?>" class="rs-book-card" data-slug="<?php echo htmlspecialchars($slug); ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: transform 0.2s ease;">
                            <!-- Cover Image -->
                            <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: relative; margin-bottom: 0.75rem; border: 1px solid rgba(255,255,255,0.05);">
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($title); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none;">
                            </div>
                            
                            <!-- Title & Subtitle -->
                            <div style="padding: 0 0.25rem; pointer-events: none;">
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
                </div>
            </div>
        <?php endif; ?>

    </div>

<!-- SortableJS and Collections Logic -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const container = document.getElementById('rs-collections-container');
    if (!container) return; // Empty library state

    const defaultPool = document.getElementById('rs-default-pool');
    const defaultBlock = defaultPool.closest('.rs-collection-block');
    const defaultTitle = defaultBlock.querySelector('.rs-collection-title');
    const addCollectionBtn = document.getElementById('rs-add-collection-btn');
    const organizeBtn = document.getElementById('rs-organize-btn');
    const doneBtn = document.getElementById('rs-organize-done-btn');
    const template = document.getElementById('rs-collection-template');
    
    let isOrganizeMode = false;
    let sortables = [];

    // Load from LocalStorage
    let savedCollections = [];
    try {
        const stored = localStorage.getItem('rs-library-collections');
        if (stored) savedCollections = JSON.parse(stored);
    } catch (e) {}

    // Apply saved collections
    if (savedCollections.length > 0) {
        // We will move items out of defaultPool to new grids
        savedCollections.forEach(col => {
            const block = template.content.cloneNode(true).querySelector('.rs-collection-block');
            block.dataset.collectionId = col.id;
            block.querySelector('.rs-collection-title').textContent = col.name;
            const grid = block.querySelector('.rs-book-grid');
            
            // Move books that belong here based on slug
            col.books.forEach(slug => {
                const bookCard = defaultPool.querySelector(`.rs-book-card[data-slug="${slug}"]`);
                if (bookCard) {
                    grid.appendChild(bookCard);
                }
            });
            
            // Insert before default block
            container.insertBefore(block, defaultBlock);
        });

        // Restore order of default pool if saved
        try {
            const storedOrder = localStorage.getItem('rs-library-order');
            if (storedOrder) {
                const defaultOrder = JSON.parse(storedOrder);
                defaultOrder.forEach(slug => {
                    const bookCard = defaultPool.querySelector(`.rs-book-card[data-slug="${slug}"]`);
                    if (bookCard) {
                        defaultPool.appendChild(bookCard); // append at end in correct order
                    }
                });
            }
        } catch(e) {}
    }

    // Toggle Organize Mode
    // Switches UI state between reading and dragging/dropping.
    function toggleOrganizeMode() {
        isOrganizeMode = !isOrganizeMode;
        
        if (isOrganizeMode) {
            organizeBtn.style.display = 'none';
            doneBtn.style.display = 'block';
            addCollectionBtn.style.display = 'block';
            defaultTitle.style.display = 'block';
            
            // Enable editing titles inline
            document.querySelectorAll('.rs-collection-title').forEach(title => {
                title.contentEditable = "true";
                title.style.borderBottom = "1px solid var(--rs-primary)";
                title.style.outline = "none";
                title.style.minWidth = "100px";
                title.style.display = "block"; // Make sure default shows up
            });

            // Show delete buttons on custom collections
            document.querySelectorAll('.rs-collection-block:not([data-collection-id="default"]) .rs-delete-collection-btn').forEach(btn => {
                btn.style.display = 'block';
            });
            
            // Prevent link clicks on book cards while sorting to avoid navigation
            document.querySelectorAll('.rs-book-card').forEach(card => {
                card.style.pointerEvents = 'none';
                card.style.opacity = '0.8';
                card.style.transform = 'scale(0.98)';
                card.style.cursor = 'grab';
            });

            // Show drop zones
            document.querySelectorAll('.rs-book-grid').forEach(grid => {
                grid.style.borderColor = 'color-mix(in srgb, var(--rs-primary) 30%, transparent)';
            });

            // Initialize Sortable logic
            initSortable();
        } else {
            // Save state upon exiting organize mode
            saveCollections();

            organizeBtn.style.display = 'flex';
            doneBtn.style.display = 'none';
            addCollectionBtn.style.display = 'none';
            
            // Disable editing titles
            document.querySelectorAll('.rs-collection-title').forEach(title => {
                title.contentEditable = "false";
                title.style.borderBottom = "none";
            });
            
            // Hide default pool title if empty or not organize mode
            const defaultGrid = defaultBlock.querySelector('.rs-book-grid');
            if (defaultGrid.children.length > 0) {
                // Keep it visible if there are other collections to distinguish them
                if (document.querySelectorAll('.rs-collection-block').length > 1) {
                    defaultTitle.style.display = 'block';
                } else {
                    defaultTitle.style.display = 'none';
                }
            } else {
                defaultTitle.style.display = 'none';
            }

            // Hide delete buttons
            document.querySelectorAll('.rs-delete-collection-btn').forEach(btn => {
                btn.style.display = 'none';
            });

            // Re-enable links for reading
            document.querySelectorAll('.rs-book-card').forEach(card => {
                card.style.pointerEvents = 'auto';
                card.style.opacity = '1';
                card.style.transform = 'none';
                card.style.cursor = 'pointer';
            });

            // Hide drop zones
            document.querySelectorAll('.rs-book-grid').forEach(grid => {
                grid.style.borderColor = 'transparent';
            });

            // Destroy Sortable instances to free resources and prevent glitches
            sortables.forEach(s => s.destroy());
            sortables = [];
        }
    }

    function initSortable() {
        sortables.forEach(s => s.destroy());
        sortables = [];
        
        document.querySelectorAll('.rs-book-grid').forEach(grid => {
            sortables.push(new Sortable(grid, {
                group: 'library',
                animation: 150,
                ghostClass: 'rs-sortable-ghost',
                dragClass: 'rs-sortable-drag',
                delay: 100, // Small delay for mobile tap vs drag
                delayOnTouchOnly: true
            }));
        });
    }

    function saveCollections() {
        const collectionsToSave = [];
        let defaultOrder = [];

        document.querySelectorAll('.rs-collection-block').forEach(block => {
            const id = block.dataset.collectionId;
            const grid = block.querySelector('.rs-book-grid');
            const slugs = Array.from(grid.querySelectorAll('.rs-book-card')).map(card => card.dataset.slug);

            if (id === 'default') {
                defaultOrder = slugs;
            } else {
                const title = block.querySelector('.rs-collection-title').textContent.trim() || 'Untitled Collection';
                collectionsToSave.push({ id, name: title, books: slugs });
            }
        });

        localStorage.setItem('rs-library-collections', JSON.stringify(collectionsToSave));
        localStorage.setItem('rs-library-order', JSON.stringify(defaultOrder));
    }

    // Event Listeners
    organizeBtn.addEventListener('click', toggleOrganizeMode);
    doneBtn.addEventListener('click', toggleOrganizeMode);

    addCollectionBtn.addEventListener('click', () => {
        const block = template.content.cloneNode(true).querySelector('.rs-collection-block');
        block.dataset.collectionId = 'col_' + Date.now();
        const title = block.querySelector('.rs-collection-title');
        title.contentEditable = "true";
        title.style.borderBottom = "1px solid var(--rs-primary)";
        title.style.outline = "none";
        
        // Show delete btn
        block.querySelector('.rs-delete-collection-btn').style.display = 'block';

        const grid = block.querySelector('.rs-book-grid');
        grid.style.borderColor = 'color-mix(in srgb, var(--rs-primary) 30%, transparent)';
        
        container.insertBefore(block, defaultBlock);
        
        // Focus the new title
        setTimeout(() => { title.focus(); }, 10);
        
        initSortable();
    });

    container.addEventListener('click', (e) => {
        if (e.target.closest('.rs-delete-collection-btn')) {
            const btn = e.target.closest('.rs-delete-collection-btn');
            const block = btn.closest('.rs-collection-block');
            const grid = block.querySelector('.rs-book-grid');
            
            // Move remaining books back to default pool to prevent data loss
            Array.from(grid.children).forEach(child => {
                defaultPool.appendChild(child);
            });
            
            block.remove();
            initSortable();
        }
    });
    
    // Initial UI fixup
    if (savedCollections.length > 0) {
        defaultTitle.style.display = 'block'; // Ensure it's shown if there are other collections
    }
});
</script>
<style>
.rs-sortable-ghost {
    opacity: 0.4;
    background-color: var(--rs-surface);
}
.rs-sortable-drag {
    cursor: grabbing !important;
}
</style>

<!-- Instant Search JS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('rs-book-search');
    const resultsContainer = document.getElementById('rs-search-results');
    let searchIndex = null;
    let isFetching = false;

    // Search index loaded dynamically on focus
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

            // Require minimum 3 chars to prevent lag
            if (query.length < 3 || !searchIndex) {
                resultsContainer.style.display = 'none';
                return;
            }

            const results = searchIndex.filter(item => {
                return (item.title && item.title.toLowerCase().includes(query)) || 
                       (item.content && item.content.toLowerCase().includes(query)) ||
                       (item.book && item.book.toLowerCase().includes(query)) ||
                       (item.series && item.series.toLowerCase().includes(query));
            }).slice(0, 10); // Limit to top 10 for UI performance

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
<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>
