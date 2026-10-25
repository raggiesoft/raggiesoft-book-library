<?php
/**
 * Architectural Block Comment:
 * File: chap-toc.php
 * Purpose:
 *     Renders a scoped Table of Contents for a specific single chapter within an era.
 *     It displays the list of scenes (markdown files) associated with the targeted chapter.
 * 
 * Design Decisions & Future Maintenance:
 *     - Global State: Relies on `$toc_manifest` (JSON metadata), `$specialPageIndex1` (Era index), 
 *       `$specialPageIndex2` (Chapter index), and `$fileToUrl` (URL routing map).
 *     - Fast Failure: Evaluates both the era and the chapter. If either fails to resolve, 
 *       it aborts rendering with a graceful error message.
 *     - Inline Styles: Like `era-toc.php`, this file utilizes inline CSS with CSS variables (`var(--rs-*)`)
 *       for rapid theming. This should eventually be refactored into classes.
 *     - Security: Enforces `htmlspecialchars()` on all dynamic data fields (titles, URLs) to prevent XSS.
 */

// Import variables populated by the router.
global $toc_manifest, $specialPageIndex1, $specialPageIndex2, $fileToUrl;

// Extract the requested era and chapter using the provided indices.
$era = $toc_manifest['eras'][$specialPageIndex1] ?? null;
$chap = $era['chapters'][$specialPageIndex2] ?? null;

// Ensure the chapter actually exists before attempting to render it.
if (!$chap) {
    echo '<p>Chapter not found.</p>';
    return;
}
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <!-- Chapter Header block, providing context via the parent Era title -->
    <div style="margin-bottom: 2rem; border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">
        <div style="font-size: 0.9rem; opacity: 0.7; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($era['era_title']); ?></div>
        <h1 style="margin: 0; color: var(--rs-primary);"><?php echo htmlspecialchars($chap['chap_title']); ?></h1>
    </div>

    <!-- Parts List Container -->
    <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem;">
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
            <?php if (!empty($chap['parts'])): ?>
                <!-- Iterate through all parts assigned to this chapter -->
                <?php foreach ($chap['parts'] as $part): 
                    // Resolve the frontend URL using the file path mapping
                    $href = $fileToUrl[$part['file_path']] ?? ($part['url'] ?? $part['file_path']);
                    $partHasImage = !empty($part['image']) || !empty($part['images']);
                ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 1.1rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <i class="ph ph-file-text" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($part['part_title']); ?></span>
                            <?php if ($partHasImage): ?>
                                <span title="Illustrated Scene" style="font-size: 0.75rem; font-weight: 600; padding: 0.12rem 0.45rem; border-radius: 999px; background: rgba(227, 178, 124, 0.16); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.22rem;">
                                    <i class="ph ph-image" aria-hidden="true"></i>
                                    <span>Illustrated</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Graceful fallback if a chapter is defined but contains no parts -->
                <li style="opacity: 0.5;">No parts available yet.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
