<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: toc.php
 * =============================================================================
 * Purpose:
 *     This file renders the Table of Contents (TOC) for a specific narrative series.
 *     It parses the `$toc_manifest` manifest structure (injected by the Stardust Engine) 
 *     and maps underlying physical file paths to clean semantic URLs.
 *
 * Design Principles:
 *     - Semantic HTML: Uses native `<details>` and `<summary>` tags for lightweight, 
 *       no-JS accordion functionality.
 *     - Data Binding: Relies on a reverse-lookup array (`$fileToUrl`) to bridge 
 *       legacy file references (`p001.md`) to Stardust routes (`/part-1`).
 *
 * Maintenance Notes:
 *     - If the accordion styling breaks, check the `<style>` block at the bottom; 
 *       it overrides default webkit behaviors for summary markers.
 *     - Ensure the `$toc_manifest` array shape remains consistent with `raggiesoft-deployment-tools/paige.php`. 
 *       Specifically, it expects `['eras'] -> ['chapters'] -> ['scenes']`.
 * =============================================================================
 */

// Import required global state provided by the routing controller
global $toc_manifest, $cdnBaseUrl, $seriesSlug;
?>
<!-- 
  MAIN CONTAINER
  Constrains the TOC width for better readability on desktop displays.
-->
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 2rem; color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">Table of Contents</h1>
    
    <?php if (!empty($toc_manifest['eras'])): ?>
        <div class="toc-accordion" style="display: flex; flex-direction: column; gap: 1rem;">
            
            <!-- Iterate over top-level eras in the novel -->
            <?php foreach ($toc_manifest['eras'] as $eIndex => $era): ?>
                <details class="toc-book" style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; overflow: hidden;">
                    <!-- Native accordion header for the Era -->
                    <summary style="padding: 1.5rem; cursor: pointer; list-style: none; display: flex; flex-direction: column; gap: 0.5rem; user-select: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--rs-text);"><?php echo htmlspecialchars($era['era_title']); ?></h2>
                            <i class="ph ph-caret-down toc-caret" style="font-size: 1.25rem;"></i>
                        </div>
                        <?php if (!empty($era['era_description'])): ?>
                            <p style="margin: 0; font-size: 1rem; opacity: 0.8;"><?php echo htmlspecialchars($era['era_description']); ?></p>
                        <?php endif; ?>
                    </summary>
                    
                    <!-- Chapter container -->
                    <div class="toc-chapters" style="padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--rs-border); background: var(--rs-surface);">
                        <?php if (!empty($era['chapters'])): ?>
                            
                            <!-- Iterate over chapters within the current era -->
                            <?php foreach ($era['chapters'] as $cIndex => $chap): 
                                global $fileToUrl;
                                $sceneCount = !empty($chap['scenes']) && is_array($chap['scenes']) ? count($chap['scenes']) : 0;
                            ?>
                                <?php if ($sceneCount === 1): 
                                    /**
                                     * IDEA 3: SMART SINGLE-SCENE CHAPTER COLLAPSE
                                     * When a Chapter (`cXXX`) contains only 1 scene (`s001.md`), bypass the
                                     * redundant nested `<details>` accordion and render the Chapter as a
                                     * direct single-click link to that scene.
                                     */
                                    $singleScene = $chap['scenes'][0];
                                    $singleHref = $fileToUrl[$singleScene['file_path']] ?? $singleScene['file_path'];
                                    $rawChapTitle = (string)($chap['chap_title'] ?? '');
                                    $rawSceneTitle = (string)($singleScene['scene_title'] ?? '');
                                    $cleanSceneSub = trim((string)preg_replace('/^Scene\s+\d+\s*:\s*/i', '', $rawSceneTitle));
                                    $showSceneSub = ($cleanSceneSub !== '' && stripos($rawChapTitle, $cleanSceneSub) === false);
                                ?>
                                <div class="toc-chapter toc-chapter-single" style="margin-top: 1rem; border-bottom: 1px dashed var(--rs-border);">
                                    <a href="<?php echo htmlspecialchars($singleHref); ?>"
                                       style="text-decoration: none; color: var(--rs-primary); font-size: 1.2rem; font-weight: 600; padding: 0.55rem 0; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; transition: opacity 0.2s;"
                                       onmouseover="this.style.opacity='0.82'"
                                       onmouseout="this.style.opacity='1'">
                                        <span style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                            <i class="ph ph-file-text" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($rawChapTitle); ?></span>
                                            <?php if ($showSceneSub): ?>
                                                <span style="font-weight: 500; font-size: 0.98rem; color: var(--rs-text); opacity: 0.82;">&mdash; <?php echo htmlspecialchars($cleanSceneSub); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <span style="font-size: 0.78rem; font-weight: 600; color: var(--rs-text); opacity: 0.72; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <span>Read</span>
                                            <i class="ph ph-arrow-right" aria-hidden="true"></i>
                                        </span>
                                    </a>
                                </div>
                                <?php else: ?>
                                <details class="toc-chapter" style="margin-top: 1rem;">
                                    <!-- Native accordion header for multi-scene Chapter -->
                                    <summary style="cursor: pointer; list-style: none; font-size: 1.2rem; font-weight: 600; padding: 0.5rem 0; border-bottom: 1px dashed var(--rs-border); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="ph ph-folder" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($chap['chap_title']); ?></span>
                                        </span>
                                        <span style="font-size: 0.78rem; font-weight: 600; opacity: 0.72; white-space: nowrap;">
                                            <?php echo (int)$sceneCount; ?> scenes
                                        </span>
                                    </summary>
                                    
                                    <ul style="list-style: none; padding: 0.5rem 0 0 1.5rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                                        <?php if (!empty($chap['scenes'])): ?>
                                            
                                            <!-- Iterate over individual scenes (markdown files) -->
                                            <?php foreach ($chap['scenes'] as $scene): 
                                                // Resolve the raw file path to the Stardust semantic URL
                                                // Default to file_path if the mapping is missing
                                                $href = $fileToUrl[$scene['file_path']] ?? $scene['file_path'];
                                            ?>
                                                <li>
                                                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                                        <i class="ph ph-file-text" aria-hidden="true"></i> <?php echo htmlspecialchars($scene['scene_title']); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li style="opacity: 0.5;">No scenes available.</li>
                                        <?php endif; ?>
                                    </ul>
                                </details>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="margin-top: 1rem; opacity: 0.5;">No chapters available.</p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
        
        <!-- Inline CSS to manage the native `<details>` chevron animations -->
        <style>
            details > summary::-webkit-details-marker { display: none; }
            details[open] > summary .toc-caret { transform: rotate(180deg); }
            .toc-caret { transition: transform 0.2s ease; }
        </style>
    <?php else: ?>
        <p>No content available.</p>
    <?php endif; ?>
</div>
