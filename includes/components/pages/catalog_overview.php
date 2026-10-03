<main id="stardust-reading-pane" tabindex="-1">
    <div class="book-page catalog-page" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <header class="catalog-hero text-center mb-5">
            <h1 class="display-4 fw-bold">The Archives Catalog</h1>
            <p class="lead text-muted">Browse our entire collection of published narratives.</p>
        </header>

        <section class="stardust-grid">
            <?php
            // Fetch the master catalog directly from the local assets mount
            $catalogFile = $basePath . '/../raggiesoft-assets/raggiesoft-books/books/catalog.json';
            $books = [];
            
            if (file_exists($catalogFile)) {
                $catalogData = file_get_contents($catalogFile);
                $books = json_decode($catalogData, true) ?? [];
            }

            if (empty($books)):
            ?>
                <div class="text-center w-100 py-5">
                    <i class="ph ph-books" style="font-size: 3rem; color: var(--text-muted);"></i>
                    <h3 class="mt-3 text-muted">Library Catalog Offline</h3>
                    <p>The system is currently compiling the archives. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($books as $book): 
                    $slug = $book['slug'] ?? '';
                    $title = $book['title'] ?? 'Unknown Archive';
                    $desc = $book['description'] ?? '';
                    $route = !empty($book['first_route']) ? $book['first_route'] : '/' . $slug;
                ?>
                    <a href="<?php echo htmlspecialchars($route); ?>" class="stardust-card">
                        <div class="stardust-card-cover theme-quantum">
                            <h3><?php echo htmlspecialchars($title); ?></h3>
                        </div>
                        <div class="stardust-card-info">
                            <h4><?php echo htmlspecialchars($title); ?></h4>
                            <p><?php echo htmlspecialchars($desc); ?></p>
                            <span class="stardust-card-action"><i class="ph ph-book-open"></i> Read Series &rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
.theme-quantum { background: #1e3a8a !important; }
</style>
