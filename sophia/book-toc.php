<?php
global $katie, $cdnBaseUrl, $seriesSlug, $specialPageIndex1;

if (isset($katie['books'][$specialPageIndex1])) {
    $book = $katie['books'][$specialPageIndex1];
} else {
    echo '<div class="book-page"><h1>Error</h1><p>Book not found.</p></div>';
    return;
}
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <a href="/<?php echo htmlspecialchars($katie['series_slug']); ?>/toc" class="rs-btn" style="margin-bottom: 1rem; display: inline-block;">&larr; Back to Main TOC</a>
        <h1 style="color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;"><?php echo htmlspecialchars($book['book_title']); ?></h1>
    </div>
    
    <?php if (!empty($book['chapters'])): ?>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($book['chapters'] as $cIndex => $chap): 
                $chapHref = isset($chap['chap_url']) ? str_replace('/raggiesoft-books/books', '', $chap['chap_url']) : '#';
            ?>
                <li style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem; transition: transform 0.2s, box-shadow 0.2s;">
                    <a href="<?php echo htmlspecialchars($chapHref); ?>" style="text-decoration: none; color: inherit; display: block;">
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--rs-text);"><?php echo htmlspecialchars($chap['chap_title']); ?></h2>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No chapters available in this book.</p>
    <?php endif; ?>
</div>
