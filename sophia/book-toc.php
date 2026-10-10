<?php
/**
 * Architectural Block Comment:
 * File: book-toc.php
 * Purpose:
 *     This template renders the Table of Contents (TOC) specifically for a single book within a larger series.
 *     It dynamically generates a nested list of chapters and their constituent parts based on the global JSON metadata.
 * 
 * Design Decisions & Future Maintenance:
 *     - Global State Dependency: Relies heavily on globally scoped variables (`$katie`, `$specialPageIndex1`, `$fileToUrl`).
 *       `$katie` holds the parsed series JSON. `$specialPageIndex1` is expected to be a valid 0-based index targeting a specific book.
 *       `$fileToUrl` is an array mapping internal markdown file paths to their routable frontend URLs.
 *     - Inline Styling: Currently utilizes hardcoded inline CSS rather than external stylesheet classes. For a long-term UI overhaul,
 *       these should be migrated to a dedicated CSS file.
 *     - Error Handling: Implements basic graceful degradation (e.g., "Book not found", "No parts available") if the JSON structure is incomplete or if an invalid index is passed.
 *     - Security: Enforces `htmlspecialchars()` aggressively on all output (titles, descriptions, URLs) to mitigate Cross-Site Scripting (XSS) attacks.
 */

// Import necessary variables populated by the upstream router/controller.
global $katie, $specialPageIndex1, $fileToUrl;

// Extract the requested book using the provided index.
$book = $katie['books'][$specialPageIndex1] ?? null;

// Halt rendering early if the book does not exist in the structure.
if (!$book) {
    echo '<p>Book not found.</p>';
    return;
}
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <!-- Render the main book title -->
    <h1 style="margin-bottom: 2rem; color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;"><?php echo htmlspecialchars($book['book_title']); ?></h1>
    
    <!-- Render the book description if one is provided in the metadata -->
    <?php if (!empty($book['book_description'])): ?>
        <p style="margin-bottom: 2rem; font-size: 1.1rem; opacity: 0.9;"><?php echo htmlspecialchars($book['book_description']); ?></p>
    <?php endif; ?>

    <div class="toc-chapters" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (!empty($book['chapters'])): ?>
            <!-- Iterate over each chapter within the book -->
            <?php foreach ($book['chapters'] as $chap): ?>
                <div style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem;">
                    <!-- Render the chapter title with an icon -->
                    <h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ph ph-folder"></i> <?php echo htmlspecialchars($chap['chap_title']); ?>
                    </h2>
                    
                    <ul style="list-style: none; padding: 0 0 0 1rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if (!empty($chap['parts'])): ?>
                            <!-- Iterate over all parts (e.g., markdown files) belonging to this chapter -->
                            <?php foreach ($chap['parts'] as $part): 
                                // Resolve the routable frontend URL for this part using the pre-computed map.
                                $href = $fileToUrl[$part['file_path']] ?? '#';
                            ?>
                                <li>
                                    <!-- Render the clickable link to the reading page -->
                                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="ph ph-file-text"></i> <?php echo htmlspecialchars($part['part_title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback for empty chapters -->
                            <li style="opacity: 0.5;">No parts available.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback for books without any chapters defined -->
            <p style="opacity: 0.5;">No chapters available.</p>
        <?php endif; ?>
    </div>
</div>
