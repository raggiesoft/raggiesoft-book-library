<?php
global $katie, $specialPageIndex1, $specialPageIndex2, $fileToUrl;

$book = $katie['books'][$specialPageIndex1] ?? null;
$chap = $book['chapters'][$specialPageIndex2] ?? null;

if (!$chap) {
    echo '<p>Chapter not found.</p>';
    return;
}
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem; border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">
        <div style="font-size: 0.9rem; opacity: 0.7; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($book['book_title']); ?></div>
        <h1 style="margin: 0; color: var(--rs-primary);"><?php echo htmlspecialchars($chap['chap_title']); ?></h1>
    </div>

    <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem;">
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
            <?php if (!empty($chap['parts'])): ?>
                <?php foreach ($chap['parts'] as $part): 
                    $href = $fileToUrl[$part['file_path']] ?? '#';
                ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <i class="ph ph-file-text"></i> <?php echo htmlspecialchars($part['part_title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li style="opacity: 0.5;">No parts available.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
