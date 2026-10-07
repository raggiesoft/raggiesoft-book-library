<?php
global $katie, $specialPageIndex1, $fileToUrl;

$book = $katie['books'][$specialPageIndex1] ?? null;

if (!$book) {
    echo '<p>Book not found.</p>';
    return;
}
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 2rem; color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;"><?php echo htmlspecialchars($book['book_title']); ?></h1>
    
    <?php if (!empty($book['book_description'])): ?>
        <p style="margin-bottom: 2rem; font-size: 1.1rem; opacity: 0.9;"><?php echo htmlspecialchars($book['book_description']); ?></p>
    <?php endif; ?>

    <div class="toc-chapters" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (!empty($book['chapters'])): ?>
            <?php foreach ($book['chapters'] as $chap): ?>
                <div style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem;">
                    <h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ph ph-folder"></i> <?php echo htmlspecialchars($chap['chap_title']); ?>
                    </h2>
                    <ul style="list-style: none; padding: 0 0 0 1rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if (!empty($chap['parts'])): ?>
                            <?php foreach ($chap['parts'] as $part): 
                                $href = $fileToUrl[$part['file_path']] ?? '#';
                            ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="ph ph-file-text"></i> <?php echo htmlspecialchars($part['part_title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li style="opacity: 0.5;">No parts available.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="opacity: 0.5;">No chapters available.</p>
        <?php endif; ?>
    </div>
</div>
