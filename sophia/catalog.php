<?php
/*
 * ============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/catalog.php
 * ============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `https://books.raggiesoft.com/library` (also the
 *     canonical 301 redirect target of `/catalog` and `/discover`) — the full
 *     Ocean View Archives Novel Catalog & Personal Bookshelf Organizer.
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php` & `paige.php`:
 *     1. Build Time (`paige.php`): Scans every novel's `ovab.json` and Markdown
 *        manuscripts to compile `raggiesoft-books/books/catalog.json` (with
 *        titles, 2:3 cover paths, standardized 95–120 character `short_description`
 *        synopses, and `word_stats`) plus `json/search-index.json`.
 *     2. Server Side (`isabel.php` -> `catalog.php`): When `$requestUri === '/library'`,
 *        `isabel.php` sets `$isCatalog = true`, injects `$cdnBaseUrl`, `$siteName`,
 *        and `$requestUri`, renders `header.php` + `app-header.php`, and includes
 *        this file. Here in PHP, we load `catalog.json` (checking local NVMe disk
 *        first before falling back to CDN) and render every novel as a clickable
 *        2:3 cover card inside `#rs-default-pool`.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     1. Library Header showing live aggregate volume count & total story words,
 *        a link to `/stats`, and a folder button (`#rs-organize-btn`) that enters
 *        interactive **Organize Mode**.
 *     2. Smart Progressive Web App (PWA) Install Banner (`#rs-pwa-install-banner`)
 *        tailored to iOS Safari ("Tap Share > Add to Home Screen") and Chromium
 *        browsers (`beforeinstallprompt` -> "Install Now").
 *     3. Full-width `<search>` bar (`#rs-book-search`) that lazily fetches
 *        `search-index.json` on first focus for instant client-side full-text search.
 *     4. Custom Drag-and-Drop Bookshelf Collections powered by `Sortable.min.js`:
 *        readers can create named shelves (`+ New Collection`), rename them inline
 *        via `contentEditable="true"`, drag book cards between shelves, and persist
 *        their layout privately in `window.localStorage` (`rs-library-collections`
 *        and `rs-library-order`).
 * ============================================================================
 */

// Import shared routing variables set up upstream by `eleanor/isabel.php`.
global $cdnBaseUrl, $siteName, $requestUri;

// Step 1: Load the master `catalog.json` manifest.
// `dirname(__DIR__, 4)` walks 4 folders up from `.../raggiesoft.com/subdomains/books/sophia`
// to reach the sibling `raggiesoft-assets` folder on local NVMe disk (`is_file()`).
// If not found locally (e.g., if assets are served purely remotely), it falls back to `$cdnBaseUrl`.
// The `@` error-control operator prevents PHP warnings from leaking into HTML if the network hiccups.
$localCatalogPath = dirname(__DIR__, 4) . '/raggiesoft-assets/raggiesoft-books/books/catalog.json';
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = is_file($localCatalogPath)
    ? @file_get_contents($localCatalogPath)
    : @file_get_contents($catalogUrl);
$books = [];

// Step 2: Decode the raw JSON string into a PHP associative array (`true`).
if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}

// Step 3: Sum pure story words and combined story+lore words across every volume in `$books`
// so the Library subtitle always reflects live manuscript counts compiled by `paige.php`.
$catalogTotalStoryWords = 0;
$catalogTotalCombinedWords = 0;
foreach ($books as $bItem) {
    $ws = $bItem['word_stats'] ?? [];
    $catalogTotalStoryWords += (int)($ws['story_word_count'] ?? 0);
    $catalogTotalCombinedWords += (int)($ws['combined_word_count'] ?? ($ws['story_word_count'] ?? 0));
}
?>

<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 4rem;">
    
    <!-- Native App Style Header -->
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- Top Bar: Title & Settings -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Library
                </h1>
                <p style="font-size: 1rem; color: var(--rs-primary); margin: 0.25rem 0 0 0; font-weight: 600;">
                    Ocean View Archives<?php if ($catalogTotalStoryWords > 0): ?> &bull; <span style="font-weight: 700; color: var(--rs-text-muted);"><?= count($books) ?> Volumes &bull; <?= number_format($catalogTotalStoryWords) ?> Story Words</span><?php endif; ?>
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <a href="/stats" class="rs-btn" style="text-decoration: none; background: var(--rs-surface); color: var(--rs-heading); border: 1px solid var(--rs-border); padding: 0.45rem 0.85rem; border-radius: 8px; font-weight: 700; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="ph-fill ph-chart-bar" style="color: var(--rs-primary);"></i>
                    <span>Word Counts &amp; Scale</span>
                </a>
                <!-- Collection Organization Toggle Buttons -->
                <button id="rs-organize-btn" type="button" aria-label="Organize library collections" class="rs-btn" style="background: transparent; color: var(--rs-primary); border: none; padding: 0.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Organize Library">
                    <i class="ph ph-folder" aria-hidden="true" style="font-size: 1.75rem;"></i>
                </button>
                <button id="rs-organize-done-btn" class="rs-btn" style="display: none; background: var(--rs-primary); color: var(--rs-on-primary, #ffffff); border: none; padding: 0.4rem 1rem; border-radius: 6px; font-weight: bold; font-size: 0.85rem; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    Done
                </button>
            </div>
        </div>

        <!-- Add to Home Screen Banner -->
        <!-- Displayed specifically for iOS/Mobile users not running in standalone mode -->
        <div id="rs-pwa-install-banner" style="display: none; background: var(--rs-primary, #0F4C5C); color: var(--rs-on-primary, #ffffff); border-radius: 12px; padding: 1rem; margin-bottom: 2rem; align-items: flex-start; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: flex-start; gap: 1rem; flex: 1;">
                <div style="width: 48px; height: 48px; background: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                    <img src="/icons/icon-192.png?v=4" alt="RaggieSoft Book Reader App Icon" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--rs-on-primary, #ffffff);">Install the App</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--rs-on-primary, #ffffff);">Tap Share &gt; Add to Home Screen</p>
                    <button id="rs-pwa-install-btn" style="display: none; margin-top: 0.5rem; background: var(--rs-surface); color: var(--rs-heading); border: 1px solid var(--rs-border); padding: 0.4rem 1rem; border-radius: 6px; font-weight: bold; font-size: 0.85rem; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Install Now</button>
                </div>
            </div>
            <button id="rs-pwa-close-banner" aria-label="Dismiss install banner" style="background: rgba(0,0,0,0.22); border: none; color: var(--rs-on-primary, #ffffff); width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 0.5rem;">
                <i class="ph ph-x" style="font-weight: bold;"></i>
            </button>
        </div>

        <!--
          HTML5 `<search>` LANDMARK:
          Introduced in modern HTML to semantically mark a search region for screen readers
          without needing `<form role="search">`. Typing 3+ characters triggers the instant
          client-side search `<script>` at the bottom of this file.
        -->
        <search style="position: relative; width: 100%; margin-bottom: 2rem; display: block;">
            <div style="position: relative; display: flex; align-items: center;">
                <i class="ph ph-magnifying-glass" aria-hidden="true" style="position: absolute; left: 1rem; color: var(--rs-text-muted); font-size: 1.25rem;"></i>
                <input type="search" id="rs-book-search" aria-label="Search titles, characters, or lore" placeholder="Search titles, characters, or lore..." style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border-radius: 12px; border: 1px solid var(--rs-border); background: var(--rs-surface); color: var(--rs-text); font-size: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); outline: none; -webkit-appearance: none;">
            </div>
            <div id="rs-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; max-height: 350px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 30px rgba(0,0,0,0.12); margin-top: 8px;"></div>
        </search>
        
    </div>

    <!-- MAIN CATALOG BOOKSHELF GRID -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        
        <?php if (empty($books)): ?>
            <!-- Graceful Offline / Empty Catalog Fallback -->
            <div style="text-align: center; padding: 4rem 0; color: var(--rs-text-muted);">
                <i class="ph ph-books" style="font-size: 4rem; color: var(--rs-primary); margin-bottom: 1rem;"></i>
                <h2 style="color: var(--rs-heading);">Library Offline</h2>
                <p>The system is currently compiling the archives. Please check back later.</p>
            </div>
        <?php else: ?>
            <div id="rs-collections-container">
                <!--
                  HTML `<template id="rs-collection-template">`:
                  100-Level HTML Lesson: Markup inside a `<template>` tag is parsed by the browser
                  but NEVER rendered on screen until JavaScript clones it via
                  `template.content.cloneNode(true)`! We use this blueprint whenever a reader
                  creates or restores a custom bookshelf collection.
                -->
                <template id="rs-collection-template">
                    <div class="rs-collection-block" style="margin-bottom: 2rem;">
                        <div class="rs-collection-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <h2 class="rs-collection-title" style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--rs-heading);">New Collection</h2>
                            <button class="rs-btn rs-delete-collection-btn" aria-label="Delete collection" style="background: transparent; color: var(--rs-primary); border: none; padding: 0.2rem; cursor: pointer; display: none;"><i class="ph ph-trash"></i></button>
                        </div>
                        <div class="rs-book-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.5rem 1rem; min-height: 240px; border: 2px dashed transparent; border-radius: 12px; padding: 0.5rem; transition: border-color 0.2s ease;">
                            <!-- SortableJS drops `.rs-book-card` elements into this grid -->
                        </div>
                    </div>
                </template>

                <!--
                  DEFAULT COLLECTION POOL (`data-collection-id="default"`):
                  Server-side PHP renders ALL books into `#rs-default-pool` first. Immediately
                  afterward, the SortableJS `<script>` below checks `localStorage['rs-library-collections']`
                  and moves any categorized `.rs-book-card[data-slug="..."]` DOM nodes into the
                  reader's custom collection shelves!
                -->
                <div class="rs-collection-block" data-collection-id="default" style="margin-bottom: 2rem;">
                    <div class="rs-collection-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <h2 class="rs-collection-title" style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--rs-heading); display: none;">Uncategorized</h2>
                        <button id="rs-add-collection-btn" class="rs-btn" style="background: transparent; color: var(--rs-primary); border: 1px solid var(--rs-primary); padding: 0.3rem 0.75rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer; display: none;">+ New Collection</button>
                    </div>
                    <div id="rs-default-pool" class="rs-book-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.5rem 1rem; min-height: 240px; border: 2px dashed transparent; border-radius: 12px; padding: 0.5rem; margin: -0.5rem; transition: border-color 0.2s ease;">
                        
                        <?php foreach ($books as $bookIdx => $book): 
                            // Extract per-book fields from `catalog.json` with safe defaults
                            $slug = $book['slug'] ?? '';
                            $title = $book['title'] ?? 'Unknown Archive';
                            // Prefer the standardized 95–120 character `short_description` so every card wraps evenly to 4–5 lines
                            $desc = !empty($book['short_description'])
                                ? $book['short_description']
                                : ($book['description'] ?? '');
                            $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                            // Root-level series overview URL (e.g., `/rachel`), routed by `isabel.php` -> `oliver.php` -> `landing.php`
                            $readLink = '/' . $slug;
                            $bookWordStats = $book['word_stats'] ?? [];
                            $bookStoryWords = (int)($bookWordStats['story_word_count'] ?? 0);
                            $bookStoryPages = (int)($bookWordStats['estimated_story_pages'] ?? ($bookStoryWords > 0 ? ceil($bookStoryWords / 275) : 0));
                            $bookScaleBadge = (string)($bookWordStats['scale_badge'] ?? '');
                        ?>
                        
                        <!--
                          INDIVIDUAL BOOK CARD (`data-slug` identifies this book to SortableJS):
                          Core Web Vitals Performance Note:
                          - Card 0 (`$bookIdx === 0`) gets `fetchpriority="high"` so the browser prioritizes
                            the Largest Contentful Paint (LCP) cover image immediately.
                          - Cards 0–5 load eagerly above the fold; Card 6+ (`$bookIdx >= 6`) get `loading="lazy"`
                            so off-screen covers only download as the reader scrolls down!
                        -->
                        <a href="<?php echo htmlspecialchars($readLink); ?>" class="rs-book-card" data-slug="<?php echo htmlspecialchars($slug); ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: transform 0.2s ease;">
                            <!-- 2:3 Cover Image Frame (`aspect-ratio: 2/3` reserves exact layout space before the image loads, preventing layout shift) -->
                            <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: relative; margin-bottom: 0.75rem; border: 1px solid rgba(255,255,255,0.05);">
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($title); ?>" width="400" height="600" <?php if ($bookIdx === 0): ?>fetchpriority="high"<?php elseif ($bookIdx >= 6): ?>loading="lazy"<?php endif; ?> decoding="async" style="width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none;">
                                <?php if ($bookStoryWords > 0): ?>
                                <!-- Frosted-glass word-count badge (`backdrop-filter: blur(6px)`) anchored to the bottom-right corner of the cover -->
                                <div style="position: absolute; bottom: 0.45rem; right: 0.45rem; background: rgba(9, 20, 32, 0.86); color: #f8fafc; border: 1px solid rgba(227, 178, 124, 0.55); border-radius: 6px; padding: 0.16rem 0.42rem; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.02em; font-variant-numeric: tabular-nums; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); pointer-events: none;">
                                    <?= $bookStoryWords >= 1000 ? number_format($bookStoryWords / 1000, 1) . 'k' : number_format($bookStoryWords) ?> words
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Book Title, Page/Scale Metadata, & Clamped 5-Line Synopsis -->
                            <div style="padding: 0 0.25rem; pointer-events: none;">
                                <h2 style="font-size: 1rem; font-weight: 700; margin: 0 0 0.2rem 0; color: var(--rs-heading); line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2; overflow: clip;">
                                    <?php echo htmlspecialchars($title); ?>
                                </h2>
                                <?php if ($bookStoryWords > 0): ?>
                                <div style="font-size: 0.72rem; font-weight: 700; color: var(--rs-primary); margin-bottom: 0.25rem; font-variant-numeric: tabular-nums;">
                                    ~<?= number_format($bookStoryPages) ?> pages<?= $bookScaleBadge !== '' ? ' &bull; ' . htmlspecialchars($bookScaleBadge) : '' ?>
                                </div>
                                <?php endif; ?>
                                <p style="font-size: 0.8rem; color: var(--rs-text-muted); margin: 0; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; line-clamp: 5; overflow: clip;">
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

<!--
  SCRIPT 1: DRAG-AND-DROP BOOKSHELF COLLECTIONS (`Sortable.min.js` + `localStorage`)
  100-Level JavaScript Walkthrough:
  - Loads `Sortable.min.js` from the self-hosted CDN (`assets.raggiesoft.com/common/vendor/sortablejs/`).
  - On page load, reads `'rs-library-collections'` (array of `{ id, name, books: [slug, ...] }`)
    and `'rs-library-order'` (slug order of the remaining Uncategorized pool) from `localStorage`.
  - Notice `grid.appendChild(bookCard)`: in the DOM, calling `appendChild()` on an element that is
    ALREADY on the page *moves* it out of `#rs-default-pool` and into the new collection grid
    without cloning or duplicating it!
-->
<script src="<?= htmlspecialchars($cdnBaseUrl ?? 'https://assets.raggiesoft.com') ?>/common/vendor/sortablejs/Sortable.min.js"></script>
<script>
(function() {
    const container = document.getElementById('rs-collections-container');
    if (!container) return; // Guard clause if `$books` was empty (Library Offline state)

    // Cache DOM references to the default pool, header buttons, and `<template>`
    const defaultPool = document.getElementById('rs-default-pool');
    const defaultBlock = defaultPool.closest('.rs-collection-block');
    const defaultTitle = defaultBlock.querySelector('.rs-collection-title');
    const addCollectionBtn = document.getElementById('rs-add-collection-btn');
    const organizeBtn = document.getElementById('rs-organize-btn');
    const doneBtn = document.getElementById('rs-organize-done-btn');
    const template = document.getElementById('rs-collection-template');
    
    let isOrganizeMode = false;
    let sortables = []; // Holds active `Sortable` instances so we can `.destroy()` them when exiting Organize Mode

    // Step 1: Read saved custom collections from `localStorage`
    let savedCollections = [];
    try {
        const stored = localStorage.getItem('rs-library-collections');
        if (stored) savedCollections = JSON.parse(stored);
    } catch (e) {}

    // Step 2: Reconstruct custom shelf blocks and move matching `.rs-book-card` nodes out of `defaultPool`
    if (savedCollections.length > 0) {
        savedCollections.forEach(col => {
            // Clone a fresh `.rs-collection-block` from `<template id="rs-collection-template">`
            const block = template.content.cloneNode(true).querySelector('.rs-collection-block');
            block.dataset.collectionId = col.id;
            block.querySelector('.rs-collection-title').textContent = col.name;
            const grid = block.querySelector('.rs-book-grid');
            
            // Move each saved book card into this collection's `.rs-book-grid` in exact saved order
            col.books.forEach(slug => {
                const bookCard = defaultPool.querySelector(`.rs-book-card[data-slug="${slug}"]`);
                if (bookCard) {
                    grid.appendChild(bookCard);
                }
            });
            
            // Insert custom shelves ABOVE the "Uncategorized" default block
            container.insertBefore(block, defaultBlock);
        });

        // Also restore the custom drag order inside `#rs-default-pool` if the user reordered uncategorized books
        try {
            const storedOrder = localStorage.getItem('rs-library-order');
            if (storedOrder) {
                const defaultOrder = JSON.parse(storedOrder);
                defaultOrder.forEach(slug => {
                    const bookCard = defaultPool.querySelector(`.rs-book-card[data-slug="${slug}"]`);
                    if (bookCard) {
                        defaultPool.appendChild(bookCard); // Re-appending moves the card to the end in saved sequence
                    }
                });
            }
        } catch(e) {}
    }

    /**
     * Toggles between normal Reading Mode (tapping a cover opens the book) and
     * Organize Mode (shelves can be renamed via `contentEditable`, deleted, or rearranged via drag-and-drop).
     */
    function toggleOrganizeMode() {
        isOrganizeMode = !isOrganizeMode;
        
        if (isOrganizeMode) {
            organizeBtn.style.display = 'none';
            doneBtn.style.display = 'block';
            addCollectionBtn.style.display = 'block';
            defaultTitle.style.display = 'block';
            
            // Turn every `<h2>` shelf title into an inline editable text field (`contentEditable = "true"`)
            document.querySelectorAll('.rs-collection-title').forEach(title => {
                title.contentEditable = "true";
                title.style.borderBottom = "1px solid var(--rs-primary)";
                title.style.outline = "none";
                title.style.minWidth = "100px";
                title.style.display = "block";
            });

            // Reveal trash-can delete buttons on custom shelves (never on `[data-collection-id="default"]`)
            document.querySelectorAll('.rs-collection-block:not([data-collection-id="default"]) .rs-delete-collection-btn').forEach(btn => {
                btn.style.display = 'block';
            });
            
            // Disable `<a>` link navigation (`pointerEvents = 'none'`) while dragging so dropping a book doesn't open it!
            document.querySelectorAll('.rs-book-card').forEach(card => {
                card.style.pointerEvents = 'none';
                card.style.opacity = '0.8';
                card.style.transform = 'scale(0.98)';
                card.style.cursor = 'grab';
            });

            // Highlight dashed drop-zone borders around every shelf grid
            document.querySelectorAll('.rs-book-grid').forEach(grid => {
                grid.style.borderColor = 'color-mix(in srgb, var(--rs-primary) 30%, transparent)';
            });

            // Boot SortableJS across all `.rs-book-grid` containers
            initSortable();
        } else {
            // Persist the new shelf layout to `localStorage` as soon as the reader clicks "Done"
            saveCollections();

            organizeBtn.style.display = 'flex';
            doneBtn.style.display = 'none';
            addCollectionBtn.style.display = 'none';
            
            // Lock shelf titles back to read-only headings
            document.querySelectorAll('.rs-collection-title').forEach(title => {
                title.contentEditable = "false";
                title.style.borderBottom = "none";
            });
            
            // Only show the "Uncategorized" heading if custom shelves exist AND uncategorized books remain
            const defaultGrid = defaultBlock.querySelector('.rs-book-grid');
            if (defaultGrid.children.length > 0) {
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

            // Re-enable normal `<a>` click navigation on every book card
            document.querySelectorAll('.rs-book-card').forEach(card => {
                card.style.pointerEvents = 'auto';
                card.style.opacity = '1';
                card.style.transform = 'none';
                card.style.cursor = 'pointer';
            });

            // Hide dashed drop-zone borders
            document.querySelectorAll('.rs-book-grid').forEach(grid => {
                grid.style.borderColor = 'transparent';
            });

            // Tear down SortableJS listeners cleanly so touch scrolling works at full native speed
            sortables.forEach(s => s.destroy());
            sortables = [];
        }
    }

    /**
     * Attaches `Sortable` instances sharing `group: 'library'` so cards can be dragged
     * freely between different `.rs-book-grid` shelves.
     */
    function initSortable() {
        sortables.forEach(s => s.destroy());
        sortables = [];
        
        document.querySelectorAll('.rs-book-grid').forEach(grid => {
            sortables.push(new Sortable(grid, {
                group: 'library',
                animation: 150,
                ghostClass: 'rs-sortable-ghost',
                dragClass: 'rs-sortable-drag',
                delay: 100, // 100ms touch hold distinguishes vertical finger scrolling from dragging a card
                delayOnTouchOnly: true
            }));
        });
    }

    /**
     * Reads the current DOM order of `.rs-book-card` slugs inside each `.rs-collection-block`
     * and writes `'rs-library-collections'` and `'rs-library-order'` to `localStorage`.
     */
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

    // Wire up Organize / Done buttons
    organizeBtn.addEventListener('click', toggleOrganizeMode);
    doneBtn.addEventListener('click', toggleOrganizeMode);

    // Wire up "+ New Collection": clones `<template>`, assigns a timestamp ID (`'col_' + Date.now()`), and focuses the title
    addCollectionBtn.addEventListener('click', () => {
        const block = template.content.cloneNode(true).querySelector('.rs-collection-block');
        block.dataset.collectionId = 'col_' + Date.now();
        const title = block.querySelector('.rs-collection-title');
        title.contentEditable = "true";
        title.style.borderBottom = "1px solid var(--rs-primary)";
        title.style.outline = "none";
        
        block.querySelector('.rs-delete-collection-btn').style.display = 'block';

        const grid = block.querySelector('.rs-book-grid');
        grid.style.borderColor = 'color-mix(in srgb, var(--rs-primary) 30%, transparent)';
        
        container.insertBefore(block, defaultBlock);
        
        setTimeout(() => { title.focus(); }, 10);
        
        initSortable();
    });

    // Event Delegation: listen once on `container` for clicks on any `.rs-delete-collection-btn`
    // (even on newly created collections!), returning any books inside that shelf safely to `defaultPool`.
    container.addEventListener('click', (e) => {
        if (e.target.closest('.rs-delete-collection-btn')) {
            const btn = e.target.closest('.rs-delete-collection-btn');
            const block = btn.closest('.rs-collection-block');
            const grid = block.querySelector('.rs-book-grid');
            
            Array.from(grid.children).forEach(child => {
                defaultPool.appendChild(child);
            });
            
            block.remove();
            initSortable();
        }
    });
    
    // Show "Uncategorized" label on initial load if custom shelves already exist
    if (savedCollections.length > 0) {
        defaultTitle.style.display = 'block';
    }
})();
</script>
<style>
/* Visual feedback classes applied automatically by SortableJS while dragging a book card */
.rs-sortable-ghost {
    opacity: 0.4;
    background-color: var(--rs-surface);
}
.rs-sortable-drag {
    cursor: grabbing !important;
}
</style>

<!--
  SCRIPT 2: CLIENT-SIDE INSTANT SEARCH & PWA INSTALL BANNER CONTROLLER
  1. Lazy-loads `search-index.json` when `#rs-book-search` receives focus and filters
     all novels/chapters locally on every `input` event (`>= 3` characters).
  2. Detects whether the reader is running inside an installed standalone PWA
     (`display-mode: standalone` or `navigator.standalone === true`). If not, shows
     platform-appropriate "Add to Home Screen" / `beforeinstallprompt` install guidance!
-->
<script>
(function() {
    const searchInput = document.getElementById('rs-book-search');
    const resultsContainer = document.getElementById('rs-search-results');
    let searchIndex = null;
    let isFetching = false;

    const searchIndexUrl = 'https://assets.raggiesoft.com/raggiesoft-books/json/search-index.json';

    if (searchInput) {
        // Lazy-fetch `search-index.json` on first focus so initial `/library` load stays lightweight
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

        // Filter search results locally on each keystroke
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            resultsContainer.innerHTML = '';

            // Require at least 3 characters before rendering dropdown matches
            if (query.length < 3 || !searchIndex) {
                resultsContainer.style.display = 'none';
                return;
            }

            const results = searchIndex.filter(item => {
                return (item.title && item.title.toLowerCase().includes(query)) || 
                       (item.content && item.content.toLowerCase().includes(query)) ||
                       (item.book && item.book.toLowerCase().includes(query)) ||
                       (item.series && item.series.toLowerCase().includes(query));
            }).slice(0, 10); // Cap at top 10 matches for snappy DOM rendering

            if (results.length > 0) {
                results.forEach(item => {
                    const div = document.createElement('a');
                    div.href = item.url.replace('/raggiesoft-books/books/', '/');
                    div.style = "display: block; padding: 1rem; border-bottom: 1px solid var(--rs-border); text-decoration: none; color: var(--rs-text); cursor: pointer;";
                    div.innerHTML = `
                        <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 4px; color: var(--rs-heading);">${item.series}: ${item.title}</div>
                        <div style="font-size: 0.8rem; color: var(--rs-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.book} &middot; ${item.chapter}</div>
                    `;
                    div.addEventListener('mouseenter', () => div.style.background = 'var(--rs-bg)');
                    div.addEventListener('mouseleave', () => div.style.background = 'transparent');
                    
                    resultsContainer.appendChild(div);
                });
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = '<div style="padding: 1rem; font-size: 0.9rem; color: var(--rs-text-muted); text-align: center;">No lore found matching your search.</div>';
                resultsContainer.style.display = 'block';
            }
        });

        // Light-dismiss search dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });
    }

    // --- PROGRESSIVE WEB APP (PWA) INSTALL PROMPT CONTROLLER ---
    // `display-mode: standalone` (standard) and `navigator.standalone` (iOS Safari) detect if already installed
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const banner = document.getElementById('rs-pwa-install-banner');
    const closeBtn = document.getElementById('rs-pwa-close-banner');
    const installBtn = document.getElementById('rs-pwa-install-btn');
    
    let deferredPrompt;

    if (isStandalone) {
        // Already running as an installed Home Screen / Desktop app — keep install banner hidden
    } else if (banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
        // iOS Safari does not fire `beforeinstallprompt`, so show manual "Tap Share > Add to Home Screen" instructions
        let isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        if (isIos()) {
            banner.style.display = 'flex';
        }
    }

    // Chromium browsers (Chrome, Edge, Brave, Android) fire `beforeinstallprompt` when PWA criteria are met
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default browser mini-infobar and stash the event in `deferredPrompt` for our custom "Install Now" button
        e.preventDefault();
        deferredPrompt = e;
        
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

    // Persist banner dismissal in `localStorage` so we never nag the reader again
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (banner) banner.style.display = 'none';
            localStorage.setItem('rs-pwa-banner-dismissed', 'true');
        });
    }
})();
</script>
</div>

<!-- Persistent Bottom Tab Navigation Bar (`Home`, `Library`, `Bookmarks`, `Settings`) -->
<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>
