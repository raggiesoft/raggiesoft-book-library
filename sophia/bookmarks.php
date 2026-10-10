<?php
// Sophia's Bookmarks View
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
    <div style="max-width: 1200px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <p style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Your Collection
                </p>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Bookmarks
                </h1>
            </div>
        </div>

        <div id="rs-bookmarks-list">
            <!-- Populated by JS -->
            <p id="rs-bookmarks-empty" style="opacity: 0.7; font-size: 1rem;">No bookmarks saved yet. While reading, tap the bookmark icon to save your place.</p>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const bookmarksList = document.getElementById('rs-bookmarks-list');
    const emptyMsg = document.getElementById('rs-bookmarks-empty');
    
    // In reader.js, bookmarks are saved in localStorage under 'rs-bookmarks' as a JSON array of objects
    // Example: { url: '/alex-chloe/book-1/chapter-1', title: 'Chapter 1', series: 'alex-chloe' }
    // Wait, let's check how bookmarks are saved by reader.js. We'll query localStorage.
    
    // Fallback simple logic: look for all keys or specific bookmark array
    let bookmarks = [];
    try {
        bookmarks = JSON.parse(localStorage.getItem('rs-bookmarks')) || [];
    } catch (e) {
        console.error(e);
    }
    
    if (bookmarks.length > 0) {
        emptyMsg.style.display = 'none';
        
        const catalog = <?php echo json_encode($books); ?>;
        
        bookmarks.forEach(bm => {
            const seriesData = catalog.find(b => b.slug === bm.series) || { title: bm.series, image: '/raggiesoft-books/images/book-placeholder.jpg' };
            
            const div = document.createElement('a');
            div.href = bm.url;
            div.style = "display: flex; gap: 1rem; padding: 1rem; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; text-decoration: none; color: inherit; margin-bottom: 1rem; align-items: center;";
            div.innerHTML = `
                <div style="width: 60px; height: 90px; flex-shrink: 0; border-radius: 4px; overflow: hidden;">
                    <img src="<?php echo $cdnBaseUrl; ?>${seriesData.image}" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; margin: 0 0 0.25rem 0; font-weight: 700;">${seriesData.title}</h3>
                    <p style="font-size: 0.9rem; opacity: 0.7; margin: 0;">${bm.title}</p>
                </div>
            `;
            bookmarksList.appendChild(div);
        });
    }
});
</script>
