<?php
/**
 * BOOKMARKS VIEW (bookmarks.php)
 * ---------------------------------------------------------
 * Architectural Block Comment:
 * File: bookmarks.php
 * Purpose:
 *     This file renders the "Bookmarks" tab in the bottom navigation of the Stardust Engine reading interface.
 *     It provides a UI shell for displaying chapters that a user has bookmarked. Because bookmarks are stored 
 *     locally in the user's browser (localStorage) to respect privacy and support offline functionality, 
 *     this file relies heavily on client-side JavaScript to actually populate the list.
 * 
 * Design Decisions & Future Maintenance:
 *     - Architecture: PHP handles the server-side layout and attempts to preload the global catalog JSON. 
 *       The JavaScript uses this preloaded catalog to match local bookmark records (which only store slugs) 
 *       with rich metadata (like cover images and proper titles).
 *     - Error Suppression: `@file_get_contents($catalogUrl)` is used intentionally. If the CDN goes down, 
 *       we don't want a PHP fatal error breaking the whole page; instead, `$books` defaults to an empty array 
 *       and JS falls back to placeholder data.
 *     - Layout: Uses flexbox and Mobile-first UI principles, ensuring the `stardust-mobile-scroll` container 
 *       clears the fixed iOS-style bottom tab bar.
 */

// Import global variables from the Stardust routing engine
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN to get cover images and titles
// We suppress warnings with @ in case the CDN is temporarily unreachable
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = @file_get_contents($catalogUrl);
$books = [];

// Decode the JSON catalog into a PHP array so we can look up book data later in JS.
if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}
?>

<!-- 
  MAIN CONTAINER
  We use flex: 1 and a bottom padding to ensure the content doesn't get 
  hidden behind the fixed bottom navigation bar on mobile devices.
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    <!-- Container wrapper with max-width for desktop, and safe-area adjustments for mobile -->
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- HEADER SECTION -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <!-- Subtitle / Eyebrow text -->
                <p style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Your Collection
                </p>
                <!-- Main Page Title -->
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Bookmarks
                </h1>
            </div>
        </div>

        <!-- BOOKMARKS LIST CONTAINER -->
        <!-- This empty div will be populated dynamically by the JavaScript below -->
        <div id="rs-bookmarks-list">
            <!-- Fallback message shown if no bookmarks are found in localStorage -->
            <p id="rs-bookmarks-empty" style="opacity: 0.7; font-size: 1rem;">No bookmarks saved yet. While reading, tap the bookmark icon in the sidebar to save your place.</p>
        </div>

    </div>
</div>

<!-- 
  BOTTOM NAVIGATION COMPONENT
  This includes the iOS-style tab bar at the bottom of the screen.
-->
<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>

<!-- 
  CLIENT-SIDE LOGIC 
  We must use JavaScript to render the bookmarks because bookmarks are 
  saved in the user's browser (localStorage) for privacy and offline support. 
  The PHP backend does not know what the user has bookmarked.
-->
<script>
(function() {
    // Get references to our DOM elements
    const bookmarksList = document.getElementById('rs-bookmarks-list');
    const emptyMsg = document.getElementById('rs-bookmarks-empty');
    
    // Attempt to load the bookmarks array from localStorage
    let bookmarks = [];
    try {
        // Parse the JSON string into a JavaScript array of objects
        bookmarks = JSON.parse(localStorage.getItem('rs-bookmarks')) || [];
    } catch (e) {
        // If JSON parsing fails, log the error and default to an empty array
        console.error("Error parsing bookmarks from localStorage:", e);
    }
    
    // If we have bookmarks, hide the empty message and render them
    if (bookmarks.length > 0) {
        emptyMsg.style.display = 'none';
        
        // Pass the PHP catalog array into JavaScript so we can look up cover images
        // Safely encoded via json_encode to prevent JS injection vulnerabilities.
        const catalog = <?php echo json_encode($books); ?>;
        
        // Iterate through each bookmark and build the UI
        bookmarks.forEach(bm => {
            // Find the corresponding book in the catalog using the series slug
            // If not found, create a fallback object to prevent errors
            const seriesData = catalog.find(b => b.slug === bm.series) || { title: bm.series, image: '/raggiesoft-books/images/book-placeholder.jpg' };
            
            // Create a clickable anchor tag for the bookmark
            const div = document.createElement('a');
            div.href = bm.url; // The URL saved when the user clicked bookmark
            div.style = "display: flex; gap: 1rem; padding: 1rem; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; text-decoration: none; color: inherit; margin-bottom: 1rem; align-items: center;";
            
            // Inject the HTML structure for the bookmark card, applying the PHP CDN base URL.
            div.innerHTML = `
                <!-- Book Cover Thumbnail -->
                <div style="width: 60px; height: 90px; flex-shrink: 0; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <img src="<?php echo $cdnBaseUrl; ?>${seriesData.image}" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <!-- Bookmark Details (Series Title and Chapter/Page Name) -->
                <div>
                    <h3 style="font-size: 1.1rem; margin: 0 0 0.25rem 0; font-weight: 700;">${seriesData.title}</h3>
                    <p style="font-size: 0.9rem; opacity: 0.7; margin: 0;">${bm.title}</p>
                </div>
            `;
            
            // Append the fully constructed card to the list container
            bookmarksList.appendChild(div);
        });
    }
})();
</script>
