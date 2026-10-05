<?php
global $katie, $cdnBaseUrl, $seriesSlug, $specialPageIndex1, $specialPageIndex2, $fileToUrl;

if (isset($katie['books'][$specialPageIndex1]['chapters'][$specialPageIndex2])) {
    $book = $katie['books'][$specialPageIndex1];
    $chap = $katie['books'][$specialPageIndex1]['chapters'][$specialPageIndex2];
} else {
    echo '<div class="book-page"><h1>Error</h1><p>Chapter not found.</p></div>';
    return;
}

$bookHref = isset($book['book_url']) ? str_replace('/raggiesoft-books/books', '', $book['book_url']) : '#';
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <a href="<?php echo htmlspecialchars($bookHref); ?>" class="rs-btn" style="margin-bottom: 1rem; display: inline-block;">&larr; Back to <?php echo htmlspecialchars($book['book_title']); ?></a>
        <h1 style="color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 0.5rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($chap['chap_title']); ?></h1>
        <p style="opacity: 0.8; font-style: italic; margin-top: 0;">from <?php echo htmlspecialchars($book['book_title']); ?></p>
    </div>
    
    <?php if (!empty($chap['parts'])): ?>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($chap['parts'] as $part): 
                $href = $fileToUrl[$part['file_path']] ?? '#';
            ?>
                <li style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1rem; transition: transform 0.2s, box-shadow 0.2s;">
                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: inherit; display: block; font-size: 1.1rem;">
                        <?php echo htmlspecialchars($part['part_title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No parts available in this chapter.</p>
    <?php endif; ?>
</div>
