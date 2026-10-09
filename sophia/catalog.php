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
    <div style="background: linear-gradient(135deg, var(--rs-surface) 0%, var(--rs-bg) 100%); border-bottom: 1px solid var(--rs-border); padding: 4rem 2rem; text-align: center;">
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
                    $readLink = !empty($book['first_route']) ? $book['first_route'] : '/' . $slug;
                ?>
                
                <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                    <!-- Cover Image Container -->
                    <div style="width: 100%; aspect-ratio: 16/9; background-color: var(--rs-bg); overflow: hidden; position: relative;">
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

</main>
