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

<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; overflow-y: auto; background-color: var(--rs-bg); color: var(--rs-text);">
    
    <!-- Hero / Header Section -->
    <div style="background: linear-gradient(135deg, var(--rs-surface) 0%, var(--rs-bg) 100%); border-bottom: 1px solid var(--rs-border); padding: 4rem 2rem; text-align: center; position: relative;">
        <!-- Top Controls -->
        <div style="position: absolute; top: 1.5rem; right: 2rem; display: flex; gap: 1rem; align-items: center; z-index: 50;">
            <div style="position: relative; width: 300px; text-align: left;">
                <input type="text" id="rs-book-search" placeholder="Search the universe..." style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid var(--rs-border); background: var(--rs-bg); color: var(--rs-text); font-size: 0.95rem;">
                <div id="rs-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 6px; max-height: 350px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-top: 4px;"></div>
            </div>
            <button id="reader-settings-toggle" class="rs-btn rs-btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 0.75rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                <i class="ph ph-gear" style="font-size: 1.2rem;"></i> Settings
            </button>
        </div>

        <h1 style="font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 700; color: var(--rs-primary); margin-bottom: 1rem;">
            Contemporary Fiction Library
        </h1>
        <p style="font-size: 1.25rem; opacity: 0.8; max-width: 600px; margin: 0 auto; line-height: 1.6;">
            The grounded, real-world archives of RaggieSoft Media.
        </p>
    </div>

    <!-- Catalog Grid -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 4rem 2rem;">
        
        <?php if (empty($books)): ?>
            <div style="text-align: center; padding: 4rem 0; opacity: 0.7;">
                <i class="ph ph-books" style="font-size: 4rem; color: var(--rs-primary); margin-bottom: 1rem;"></i>
                <h2>Library Catalog Offline</h2>
                <p>The system is currently compiling the archives. Please check back later.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
                
                <?php foreach ($books as $book): 
                    $slug = $book['slug'] ?? '';
                    $title = $book['title'] ?? 'Unknown Archive';
                    $desc = $book['description'] ?? '';
                    $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                    $readLink = '/' . $slug . '/';
                ?>
                
                <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                    <!-- Cover Image Container -->
                    <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-bg); overflow: hidden; position: relative;">
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($title); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    
                    <!-- Content Details -->
                    <div style="padding: 1.5rem; flex: 1; display: flex; flex-direction: column;">
                        <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--rs-heading); line-height: 1.3;">
                            <?php echo htmlspecialchars($title); ?>
                        </h2>
                        <p style="font-size: 0.95rem; opacity: 0.8; line-height: 1.5; margin-bottom: 1.5rem; flex: 1;">
                            <?php echo htmlspecialchars($desc); ?>
                        </p>
                        
                        <a href="<?php echo htmlspecialchars($readLink); ?>" class="rs-btn rs-btn-primary" style="display: block; text-align: center; text-decoration: none; padding: 0.75rem; border-radius: 6px; font-weight: 600;">
                            <i class="ph ph-book-open" style="margin-right: 0.5rem;"></i> Read Series
                        </a>
                    </div>
                </div>
                
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
                    div.style = "display: block; padding: 0.75rem 1rem; border-bottom: 1px solid var(--rs-border); text-decoration: none; color: var(--rs-text); cursor: pointer;";
                    div.innerHTML = `
                        <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px;">${item.series}: ${item.title}</div>
                        <div style="font-size: 0.75rem; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.book} &middot; ${item.chapter}</div>
                    `;
                    div.addEventListener('mouseenter', () => div.style.background = 'var(--rs-border)');
                    div.addEventListener('mouseleave', () => div.style.background = 'transparent');
                    
                    resultsContainer.appendChild(div);
                });
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = '<div style="padding: 0.75rem 1rem; font-size: 0.85rem; opacity: 0.7;">No results found in the archives.</div>';
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
});
</script>
</main>


