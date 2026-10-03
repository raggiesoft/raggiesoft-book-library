<main id="stardust-reading-pane" tabindex="-1">
    <div class="book-page catalog-page" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <header class="catalog-hero text-center mb-5">
            <h1 class="display-4 fw-bold" style="font-family: 'Playfair Display', serif, system-ui; color: var(--rs-primary);">The Archives Catalog</h1>
            <p class="lead" style="color: var(--rs-text); opacity: 0.8;">Browse our entire collection of published narratives.</p>
        </header>

        <section class="stardust-grid">
            <?php
            // Fetch the master catalog directly from the CDN
            $catalogUrl = 'https://assets.raggiesoft.com/raggiesoft-books/books/catalog.json';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $catalogUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
            $catalogData = curl_exec($ch);
            curl_close($ch);

            $books = [];
            
            if ($catalogData !== false) {
                $books = json_decode($catalogData, true) ?? [];
            }

            if (empty($books)):
            ?>
                <div class="text-center w-100 py-5">
                    <i class="ph ph-books" style="font-size: 3rem; color: var(--rs-text); opacity: 0.5;"></i>
                    <h3 class="mt-3" style="color: var(--rs-text); opacity: 0.5;">Library Catalog Offline</h3>
                    <p style="color: var(--rs-text); opacity: 0.5;">The system is currently compiling the archives. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($books as $book): 
                    $slug = $book['slug'] ?? '';
                    $title = $book['title'] ?? 'Unknown Archive';
                    $desc = $book['description'] ?? '';
                    // The old publish script outputs first_route as /raggiesoft-books/books/{slug}
                    $route = !empty($book['first_route']) ? str_replace('/raggiesoft-books/books', '', $book['first_route']) : '/' . $slug;
                    $image = !empty($book['image']) ? 'https://assets.raggiesoft.com' . $book['image'] : '';
                ?>
                    <a href="<?php echo htmlspecialchars($route); ?>" class="stardust-card">
                        <div class="stardust-card-cover theme-oceanview" style="position: relative; padding-top: 150%;">
                            <?php if($image): ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="stardust-card-cover-fallback">
                                    <h3 style="margin: 0; color: #fff; font-size: 1.5rem;"><?php echo htmlspecialchars($title); ?></h3>
                                </div>
                            <?php endif; ?>
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
