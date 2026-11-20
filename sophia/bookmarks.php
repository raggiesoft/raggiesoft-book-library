<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/bookmarks.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `/bookmarks` on `books.raggiesoft.com` — the
 *     "Bookmarks" screen accessible from the bottom tab bar and home gateway.
 *
 * HOW IT CONNECTS TO THE MASTER ROUTER (`eleanor/isabel.php`):
 *     1. When the visitor opens `/bookmarks`, `eleanor/isabel.php` sets
 *        `$isBookmarks = true` and `$robotsMeta = 'noindex, follow'` (because
 *        bookmarks are personal to each reader's browser rather than a public
 *        search-engine page).
 *     2. In the PHP block right below, the server fetches `catalog.json` from
 *        `$cdnBaseUrl` so we have an array (`$books`) containing every novel's
 *        official `slug`, `title`, and `image` cover path.
 *     3. PHP renders the page shell with an empty `<div id="rs-bookmarks-list">`
 *        and passes `$books` into an inline `<script>` block using `json_encode()`.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - If the reader hasn't saved any chapters yet, they see a friendly prompt
 *       explaining how to tap the "Save" bookmark icon in the reading sidebar.
 *     - If they HAVE saved chapters while reading in `oliver.php` / `sidebar.php`,
 *       JavaScript hides the empty-state message and builds a clickable card
 *       (with 2:3 book cover art, book title, and chapter title) for each saved
 *       bookmark so one click jumps straight back to that chapter!
 *
 * KEY 100-LEVEL JAVASCRIPT LESSON — SERVER (PHP) VS. BROWSER (`localStorage`):
 *     - Why doesn't PHP render the bookmark cards directly with a `foreach` loop?
 *       Because RaggieSoft Book Reader uses a zero-account, privacy-first design:
 *       bookmarks are stored in `window.localStorage` under the key `'rs-bookmarks'`
 *       right on the reader's own phone or laptop—NEVER sent to a server database!
 *     - PHP runs on the web server *before* the page reaches the browser, so PHP
 *       cannot see `window.localStorage`. Instead, PHP hands the book catalog to
 *       JavaScript (`const catalog = <?php echo json_encode($books); ?>;`), and
 *       JavaScript joins `localStorage` + `catalog` right inside the browser!
 * =============================================================================
 */

// Import global variables populated by `eleanor/isabel.php`
global $cdnBaseUrl, $siteName, $requestUri;

// Step 1 (Server-Side PHP): Fetch the master catalog JSON from the CDN so JavaScript
// can look up each bookmarked novel's cover image and display title by its `slug`.
// Note: The `@` operator suppresses PHP warnings if the CDN is momentarily offline,
// allowing `$books` to fall back cleanly to an empty array `[]` instead of crashing.
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = @file_get_contents($catalogUrl);
$books = [];

// Step 2 (Server-Side PHP): Convert the raw JSON string into a PHP associative array (`true`).
// The null-coalescing operator (`?? []`) guarantees `$books` is always an array even if JSON is invalid.
if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}
?>

<!-- 
  MAIN SCROLLABLE CONTAINER:
  `flex: 1` fills the screen height; `padding-bottom: 6rem` keeps the bottom card
  above the fixed mobile tab bar (`bottom-nav.php`).
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    <!-- Centered content wrapper (`max-width: 1200px`) with notch-safe top padding -->
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- PAGE HEADER -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <!-- Eyebrow label -->
                <p style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Your Collection
                </p>
                <!-- Primary H1 heading -->
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Bookmarks
                </h1>
            </div>
        </div>

        <!-- BOOKMARKS MOUNT POINT -->
        <!-- Starts with only the empty-state `<p>` element; JavaScript appends `<a>` cards below if bookmarks exist -->
        <div id="rs-bookmarks-list">
            <!-- Empty-state fallback message (hidden via `emptyMsg.style.display = 'none'` once bookmarks are found) -->
            <p id="rs-bookmarks-empty" style="color: var(--rs-text-muted); font-size: 1rem;">No bookmarks saved yet. While reading, tap the bookmark icon in the sidebar to save your place.</p>
        </div>

    </div>
</div>

<!-- 
  PERSISTENT BOTTOM TAB BAR:
  Renders Home, Library, Bookmarks, and Settings tabs on mobile/tablet viewports.
-->
<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>

<!-- 
  CLIENT-SIDE BOOKMARK HYDRATION SCRIPT:
  Wrapped in an Immediately Invoked Function Expression `(function() { ... })();` (IIFE)
  so local variables like `bookmarksList` and `bookmarks` stay private and never leak
  into the global `window` namespace during SPA page transitions.
-->
<script>
(function() {
    // 1. Grab DOM references to the list container and the empty-state paragraph
    const bookmarksList = document.getElementById('rs-bookmarks-list');
    const emptyMsg = document.getElementById('rs-bookmarks-empty');
    
    // 2. Read the saved bookmarks JSON string from the browser's `localStorage`.
    // Each bookmark item saved by `reader.js` looks like:
    //   `{ url: "/rachel/...", title: "Part 1: ...", series: "rachel" }`
    // We wrap `JSON.parse()` in `try { ... } catch (e)` so corrupted storage data never crashes the page.
    let bookmarks = [];
    try {
        bookmarks = JSON.parse(localStorage.getItem('rs-bookmarks')) || [];
    } catch (e) {
        console.error("Error parsing bookmarks from localStorage:", e);
    }
    
    // 3. Only manipulate the DOM if the reader has at least one saved bookmark
    if (bookmarks.length > 0) {
        // Hide the "No bookmarks saved yet" message
        emptyMsg.style.display = 'none';
        
        // Bridge PHP -> JavaScript: PHP's `json_encode($books)` outputs a valid JS array literal
        // right into the `<script>` source before the browser executes it!
        const catalog = <?php echo json_encode($books); ?>;
        
        // 4. Loop over every saved bookmark object (`bm`) and create a clickable `<a>` card
        bookmarks.forEach(bm => {
            // Look up the book's full metadata in `catalog` by matching `b.slug === bm.series`.
            // If offline or missing from `catalog`, fall back to a safe placeholder object.
            const seriesData = catalog.find(b => b.slug === bm.series) || { title: bm.series, image: '/raggiesoft-books/images/book-placeholder.jpg' };
            
            // Create an `<a>` anchor element so clicking anywhere on the card navigates to `bm.url`
            const div = document.createElement('a');
            div.href = bm.url; // Semantic chapter route saved when the reader clicked "Save" in `sidebar.php`
            div.style = "display: flex; gap: 1rem; padding: 1rem; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; text-decoration: none; color: inherit; margin-bottom: 1rem; align-items: center;";
            
            // Populate the card's inner HTML using an ES6 template literal (backticks `` `...` ``)
            // which lets us interpolate `${seriesData.image}`, `${seriesData.title}`, and `${bm.title}`.
            div.innerHTML = `
                <!-- Book Cover Thumbnail -->
                <div style="width: 60px; height: 90px; flex-shrink: 0; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <img src="<?php echo $cdnBaseUrl; ?>${seriesData.image}" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <!-- Bookmark Details (Series Title and Chapter/Page Name) -->
                <div>
                    <h3 style="font-size: 1.1rem; margin: 0 0 0.25rem 0; font-weight: 700; color: var(--rs-heading);">${seriesData.title}</h3>
                    <p style="font-size: 0.9rem; color: var(--rs-text-muted); margin: 0;">${bm.title}</p>
                </div>
            `;
            
            // Attach the finished `<a>` card to `<div id="rs-bookmarks-list">`
            bookmarksList.appendChild(div);
        });
    }
})();
</script>
