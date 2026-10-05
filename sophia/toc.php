<?php
global $katie, $cdnBaseUrl, $seriesSlug;
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 2rem; color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">Table of Contents</h1>
    
    <?php if (!empty($katie['books'])): ?>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($katie['books'] as $bIndex => $book): 
                $bookHref = isset($book['book_url']) ? str_replace('/raggiesoft-books/books', '', $book['book_url']) : '#';
            ?>
                <li style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem; transition: transform 0.2s, box-shadow 0.2s;">
                    <a href="<?php echo htmlspecialchars($bookHref); ?>" style="text-decoration: none; color: inherit; display: block;">
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--rs-text);"><?php echo htmlspecialchars($book['book_title']); ?></h2>
                        <?php if (!empty($book['book_description'])): ?>
                            <p style="margin: 0.5rem 0 0 0; font-size: 1rem; opacity: 0.8;"><?php echo htmlspecialchars($book['book_description']); ?></p>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No content available.</p>
    <?php endif; ?>
</div>
