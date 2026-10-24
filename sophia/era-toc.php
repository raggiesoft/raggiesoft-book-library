<?php
/**
 * Architectural Block Comment:
 * File: era-toc.php
 * Purpose:
 *     This template renders the Table of Contents (TOC) specifically for a single era within a larger novel.
 *     It dynamically generates a nested list of chapters and their constituent scenes based on the global JSON metadata.
 * 
 * Design Decisions & Future Maintenance:
 *     - Global State Dependency: Relies heavily on globally scoped variables (`$toc_manifest`, `$specialPageIndex1`, `$fileToUrl`).
 *       `$toc_manifest` holds the parsed series JSON. `$specialPageIndex1` is expected to be a valid 0-based index targeting a specific era.
 *       `$fileToUrl` is an array mapping internal markdown file paths to their routable frontend URLs.
 *     - Inline Styling: Currently utilizes hardcoded inline CSS rather than external stylesheet classes. For a long-term UI overhaul,
 *       these should be migrated to a dedicated CSS file.
 *     - Error Handling: Implements basic graceful degradation (e.g., "Era not found", "No scenes available") if the JSON structure is incomplete or if an invalid index is passed.
 *     - Security: Enforces `htmlspecialchars()` aggressively on all output (titles, descriptions, URLs) to mitigate Cross-Site Scripting (XSS) attacks.
 */

// Import necessary variables populated by the upstream router/controller.
global $toc_manifest, $specialPageIndex1, $fileToUrl;

// Extract the requested era using the provided index.
$era = $toc_manifest['eras'][$specialPageIndex1] ?? null;

// Halt rendering early if the era does not exist in the structure.
if (!$era) {
    echo '<p>Era not found.</p>';
    return;
}
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <!-- Render the main era title -->
    <h1 style="margin-bottom: 2rem; color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;"><?php echo htmlspecialchars($era['era_title']); ?></h1>
    
    <!-- Render the era description if one is provided in the metadata -->
    <?php if (!empty($era['era_description'])): ?>
        <p style="margin-bottom: 2rem; font-size: 1.1rem; opacity: 0.9;"><?php echo htmlspecialchars($era['era_description']); ?></p>
    <?php endif; ?>

    <div class="toc-chapters" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (!empty($era['chapters'])): ?>
            <!-- Iterate over each chapter within the era -->
            <?php foreach ($era['chapters'] as $chap): 
                $sceneCount = !empty($chap['scenes']) && is_array($chap['scenes']) ? count($chap['scenes']) : 0;
            ?>
                <?php if ($sceneCount === 1):
                    /**
                     * IDEA 3: SMART SINGLE-SCENE CHAPTER COLLAPSE
                     * When a Chapter (`cXXX`) contains only 1 scene (`s001.md`), render the
                     * chapter card as a direct one-click link to that scene rather than a
                     * nested list containing a single item.
                     */
                    $singleScene = $chap['scenes'][0];
                    $singleHref = $fileToUrl[$singleScene['file_path']] ?? $singleScene['file_path'];
                    $rawChapTitle = (string)($chap['chap_title'] ?? '');
                    $rawSceneTitle = (string)($singleScene['scene_title'] ?? '');
                    $cleanSceneSub = trim((string)preg_replace('/^Scene\s+\d+\s*:\s*/i', '', $rawSceneTitle));
                    $showSceneSub = ($cleanSceneSub !== '' && stripos($rawChapTitle, $cleanSceneSub) === false);
                ?>
                <div style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.25rem 1.5rem;">
                    <a href="<?php echo htmlspecialchars($singleHref); ?>"
                       style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; justify-content: space-between; gap: 1rem; font-size: 1.25rem; font-weight: 700; transition: opacity 0.2s;"
                       onmouseover="this.style.opacity='0.82'"
                       onmouseout="this.style.opacity='1'">
                        <span style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <i class="ph ph-file-text" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($rawChapTitle); ?></span>
                            <?php if ($showSceneSub): ?>
                                <span style="font-weight: 500; font-size: 1rem; color: var(--rs-text); opacity: 0.82;">&mdash; <?php echo htmlspecialchars($cleanSceneSub); ?></span>
                            <?php endif; ?>
                        </span>
                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--rs-text); opacity: 0.75; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.3rem;">
                            <span>Read Chapter</span>
                            <i class="ph ph-arrow-right" aria-hidden="true"></i>
                        </span>
                    </a>
                </div>
                <?php else: ?>
                <div style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem;">
                    <!-- Render the chapter title with an icon -->
                    <h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.3rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="ph ph-folder" aria-hidden="true"></i> <?php echo htmlspecialchars($chap['chap_title']); ?>
                        </span>
                        <span style="font-size: 0.8rem; font-weight: 600; opacity: 0.72;"><?php echo (int)$sceneCount; ?> scenes</span>
                    </h2>
                    
                    <ul style="list-style: none; padding: 0 0 0 1rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if (!empty($chap['scenes'])): ?>
                            <!-- Iterate over all scenes (e.g., markdown files) belonging to this chapter -->
                            <?php foreach ($chap['scenes'] as $scene): 
                                // Resolve the routable frontend URL for this scene using the pre-computed map.
                                $href = $fileToUrl[$scene['file_path']] ?? $scene['file_path']; // fallback to file path if mapping missing
                            ?>
                                <li>
                                    <!-- Render the clickable link to the reading page -->
                                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="ph ph-file-text" aria-hidden="true"></i> <?php echo htmlspecialchars($scene['scene_title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback for empty chapters -->
                            <li style="opacity: 0.5;">No scenes available.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback for eras without any chapters defined -->
            <p style="opacity: 0.5;">No chapters available.</p>
        <?php endif; ?>
    </div>
</div>
