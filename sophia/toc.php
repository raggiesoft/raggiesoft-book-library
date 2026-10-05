<?php
global $katie, $cdnBaseUrl, $seriesSlug;
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 2rem; color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">Table of Contents</h1>
    
    <?php if (!empty($katie['books'])): ?>
        <div class="toc-accordion" style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($katie['books'] as $bIndex => $book): ?>
                <details class="toc-book" style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; overflow: hidden;">
                    <summary style="padding: 1.5rem; cursor: pointer; list-style: none; display: flex; flex-direction: column; gap: 0.5rem; user-select: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--rs-text);"><?php echo htmlspecialchars($book['book_title']); ?></h2>
                            <i class="ph ph-caret-down toc-caret" style="font-size: 1.25rem;"></i>
                        </div>
                        <?php if (!empty($book['book_description'])): ?>
                            <p style="margin: 0; font-size: 1rem; opacity: 0.8;"><?php echo htmlspecialchars($book['book_description']); ?></p>
                        <?php endif; ?>
                    </summary>
                    
                    <div class="toc-chapters" style="padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--rs-border); background: var(--rs-surface);">
                        <?php if (!empty($book['chapters'])): ?>
                            <?php foreach ($book['chapters'] as $cIndex => $chap): ?>
                                <details class="toc-chapter" style="margin-top: 1rem;">
                                    <summary style="cursor: pointer; list-style: none; font-size: 1.2rem; font-weight: 600; padding: 0.5rem 0; border-bottom: 1px dashed var(--rs-border); display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="ph ph-folder"></i> <?php echo htmlspecialchars($chap['chap_title']); ?>
                                    </summary>
                                    <ul style="list-style: none; padding: 0.5rem 0 0 1.5rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                                        <?php if (!empty($chap['parts'])): ?>
                                            <?php foreach ($chap['parts'] as $part): 
                                                global $fileToUrl;
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
                                </details>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="margin-top: 1rem; opacity: 0.5;">No chapters available.</p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
        <style>
            details > summary::-webkit-details-marker { display: none; }
            details[open] > summary .toc-caret { transform: rotate(180deg); }
            .toc-caret { transition: transform 0.2s ease; }
        </style>
    <?php else: ?>
        <p>No content available.</p>
    <?php endif; ?>
</div>
