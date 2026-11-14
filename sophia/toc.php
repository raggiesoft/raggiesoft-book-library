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
 *       Specifically, it expects `['eras'] -> ['chapters'] -> ['parts']`.
 * =============================================================================
 */

// Import required global state provided by the routing controller
global $toc_manifest, $cdnBaseUrl, $seriesSlug;
?>
<!-- 
  MAIN CONTAINER
  Constrains the TOC width for better readability on desktop displays.
-->
<?php
$tocTotalIllustrations = (int)($toc_manifest['illustration_count'] ?? 0);
$tocWordStats = $toc_manifest['word_stats'] ?? [];
$tocStoryWords = (int)($tocWordStats['story_word_count'] ?? 0);
$tocStoryPages = (int)($tocWordStats['estimated_story_pages'] ?? ($tocStoryWords > 0 ? ceil($tocStoryWords / 275) : 0));
?>
<div class="book-page" style="max-width: 960px; margin: 0 auto;">
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">
        <div>
            <h1 style="margin: 0; color: var(--rs-primary);">Table of Contents</h1>
            <?php if ($tocStoryWords > 0): ?>
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--rs-text-muted); margin-top: 0.25rem; font-variant-numeric: tabular-nums;">
                    <?php echo number_format($tocStoryWords); ?> story words &bull; ~<?php echo number_format($tocStoryPages); ?> print pages &bull; <a href="/stats" style="color: var(--rs-primary); text-decoration: none; font-weight: 700;">Library Scale Stats &rarr;</a>
                </div>
            <?php endif; ?>
        </div>
        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/illustrations" class="rs-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; padding: 0.55rem 1rem;">
            <i class="ph ph-images" aria-hidden="true"></i>
            <span>Table of Illustrations<?php echo $tocTotalIllustrations > 0 ? ' (' . $tocTotalIllustrations . ')' : ''; ?></span>
        </a>
    </div>

    <?php if ($tocTotalIllustrations > 0): ?>
        <!-- Reusable Embedded Table of Illustrations Component -->
        <details style="margin-bottom: 1.75rem; background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; overflow: hidden;">
            <summary style="padding: 1.15rem 1.5rem; cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between; gap: 1rem; user-select: none; background: rgba(227, 178, 124, 0.08);">
                <span style="display: inline-flex; align-items: center; gap: 0.65rem; font-size: 1.15rem; font-weight: 700; color: var(--rs-primary);">
                    <i class="ph ph-images" aria-hidden="true" style="font-size: 1.35rem;"></i>
                    <span>Table of Illustrations &mdash; Visual Scene Index (<?php echo $tocTotalIllustrations; ?>)</span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--rs-text-muted);">
                    <span>Browse Artwork</span>
                    <i class="ph ph-caret-down toc-caret" style="font-size: 1.15rem;"></i>
                </span>
            </summary>
            <div style="padding: 0 1.25rem 1.25rem 1.25rem; border-top: 1px solid var(--rs-border);">
                <?php
                $illustrationsTocMode = 'embedded';
                require __DIR__ . '/../includes/components/illustrations-toc.php';
                ?>
            </div>
        </details>
    <?php endif; ?>
    
    <?php if (!empty($toc_manifest['eras'])): ?>
        <div class="toc-accordion" style="display: flex; flex-direction: column; gap: 1rem;">
            
            <!-- Iterate over top-level eras in the novel -->
            <?php foreach ($toc_manifest['eras'] as $eIndex => $era):
                $eraIllusCount = (int)($era['illustration_count'] ?? 0);
                $eraWordCount = (int)($era['word_count'] ?? 0);
            ?>
                <details class="toc-book" style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; overflow: hidden;">
                    <!-- Native accordion header for the Era (uses span instead of h2 inside summary so screen-reader heading navigation stays intact) -->
                    <summary style="padding: 1.5rem; cursor: pointer; list-style: none; display: flex; flex-direction: column; gap: 0.5rem; user-select: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                            <span class="toc-era-heading" style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--rs-text);"><?php echo htmlspecialchars($era['era_title']); ?></span>
                            <div style="display: inline-flex; align-items: center; gap: 0.65rem;">
                                <?php if ($eraWordCount > 0): ?>
                                    <span style="font-size: 0.76rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; background: var(--rs-surface); border: 1px solid var(--rs-border); color: var(--rs-text); opacity: 0.85; white-space: nowrap; font-variant-numeric: tabular-nums;">
                                        <?php echo number_format($eraWordCount); ?> words
                                    </span>
                                <?php endif; ?>
                                <?php if ($eraIllusCount > 0): ?>
                                    <span style="font-size: 0.78rem; font-weight: 600; padding: 0.2rem 0.55rem; border-radius: 999px; background: rgba(227, 178, 124, 0.16); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                                        <i class="ph ph-image" aria-hidden="true"></i>
                                        <span><?php echo $eraIllusCount; ?></span>
                                    </span>
                                <?php endif; ?>
                                <i class="ph ph-caret-down toc-caret" style="font-size: 1.25rem;"></i>
                            </div>
                        </div>
                        <?php if (!empty($era['era_description'])): ?>
                            <p style="margin: 0; font-size: 1rem; color: var(--rs-text-muted);"><?php echo htmlspecialchars($era['era_description']); ?></p>
                        <?php endif; ?>
                    </summary>
                    
                    <!-- Chapter container -->
                    <?php $eraOverviewHref = !empty($era['era_url']) ? str_replace('/raggiesoft-books/books', '', (string)$era['era_url']) : ''; ?>
                    <div class="toc-chapters" style="padding: 0 1.5rem 1.5rem 1.5rem; border-top: 1px solid var(--rs-border); background: var(--rs-surface);">
                        <?php if ($eraOverviewHref !== ''): ?>
                            <div style="padding-top: 0.85rem; display: flex; justify-content: flex-end;">
                                <a href="<?php echo htmlspecialchars($eraOverviewHref); ?>" style="font-size: 0.84rem; font-weight: 700; color: var(--rs-primary); text-decoration: none;">
                                    Open Era Overview: <?php echo htmlspecialchars($era['era_title']); ?> &rarr;
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($era['chapters'])): ?>
                            
                            <!-- Iterate over chapters within the current era -->
                            <?php foreach ($era['chapters'] as $cIndex => $chap): 
                                global $fileToUrl;
                                $partList = !empty($chap['parts']) && is_array($chap['parts']) ? $chap['parts'] : [];
                                $partCount = count($partList);
                                $chapIllusCount = (int)($chap['illustration_count'] ?? 0);
                            ?>
                                <?php if ($partCount === 1): 
                                    /**
                                     * IDEA 3: SMART SINGLE-PART CHAPTER COLLAPSE
                                     * When a Chapter (`cXXX`) contains only 1 Part (`p001.md`), bypass the
                                     * redundant nested `<details>` accordion and render the Chapter as a
                                     * direct single-click link.
                                     */
                                    $singlePart = $partList[0];
                                    $singleHref = $fileToUrl[$singlePart['file_path']] ?? ($singlePart['url'] ?? $singlePart['file_path']);
                                    $rawChapTitle = (string)($chap['chap_title'] ?? '');
                                    $rawPartTitle = (string)($singlePart['part_title'] ?? '');
                                    $cleanPartSub = trim((string)preg_replace('/^Part\s+\d+\s*:\s*/i', '', $rawPartTitle));
                                    $showPartSub = ($cleanPartSub !== '' && stripos($rawChapTitle, $cleanPartSub) === false && strcasecmp($cleanPartSub, 'Untitled Chapter') !== 0 && !preg_match('/^Part\s+\d+$/i', $cleanPartSub));
                                    $singleHasImage = !empty($singlePart['image']) || !empty($singlePart['images']);
                                    $singlePartWords = (int)($singlePart['word_count'] ?? 0);
                                    $singlePartMins = (int)($singlePart['reading_minutes'] ?? ($singlePartWords > 0 ? max(1, ceil($singlePartWords / 250)) : 0));
                                ?>
                                <div class="toc-chapter toc-chapter-single" style="margin-top: 1rem; border-bottom: 1px dashed var(--rs-border);">
                                    <a href="<?php echo htmlspecialchars($singleHref); ?>"
                                       style="text-decoration: none; color: var(--rs-primary); font-size: 1.2rem; font-weight: 600; padding: 0.55rem 0; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; transition: opacity 0.2s;"
                                       onmouseover="this.style.opacity='0.82'"
                                       onmouseout="this.style.opacity='1'">
                                        <span style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                            <i class="ph ph-file-text" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($rawChapTitle); ?></span>
                                            <?php if ($showPartSub): ?>
                                                <span style="font-weight: 500; font-size: 0.98rem; color: var(--rs-text-muted);">&mdash; <?php echo htmlspecialchars($cleanPartSub); ?></span>
                                            <?php endif; ?>
                                            <?php if ($singleHasImage): ?>
                                                <span title="Illustrated Scene" style="font-size: 0.75rem; font-weight: 600; padding: 0.12rem 0.45rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.25rem;">
                                                    <i class="ph ph-image" aria-hidden="true"></i>
                                                    <span>Illustrated</span>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <span style="font-size: 0.78rem; font-weight: 600; color: var(--rs-text-muted); white-space: nowrap; display: inline-flex; align-items: center; gap: 0.45rem; font-variant-numeric: tabular-nums;">
                                            <?php if ($singlePartWords > 0): ?>
                                                <span><?php echo number_format($singlePartWords); ?>w &bull; <?php echo $singlePartMins; ?>m</span>
                                            <?php else: ?>
                                                <span>Read</span>
                                            <?php endif; ?>
                                            <i class="ph ph-arrow-right" aria-hidden="true"></i>
                                        </span>
                                    </a>
                                </div>
                                <?php else: ?>
                                <details class="toc-chapter" style="margin-top: 1rem;">
                                    <!-- Native accordion header for multi-part Chapter -->
                                    <summary style="cursor: pointer; list-style: none; font-size: 1.2rem; font-weight: 600; padding: 0.5rem 0; border-bottom: 1px dashed var(--rs-border); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                        <span style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                            <i class="ph ph-folder" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($chap['chap_title']); ?></span>
                                            <?php if ($chapIllusCount > 0): ?>
                                                <span title="<?php echo $chapIllusCount; ?> Illustrated <?php echo $chapIllusCount === 1 ? 'Scene' : 'Scenes'; ?>" style="font-size: 0.74rem; font-weight: 600; padding: 0.12rem 0.45rem; border-radius: 999px; background: rgba(227, 178, 124, 0.16); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.25rem;">
                                                    <i class="ph ph-image" aria-hidden="true"></i>
                                                    <span><?php echo $chapIllusCount; ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <span style="font-size: 0.78rem; font-weight: 600; color: var(--rs-text-muted); white-space: nowrap;">
                                            <?php echo $partCount > 0 ? ((int)$partCount . ' Parts') : 'Coming Soon'; ?>
                                        </span>
                                    </summary>
                                    
                                    <?php $chapOverviewHref = !empty($chap['chap_url']) ? str_replace('/raggiesoft-books/books', '', (string)$chap['chap_url']) : ''; ?>
                                    <ul role="list" style="list-style: none; padding: 0.5rem 0 0 1.5rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                                        <?php if ($chapOverviewHref !== ''): ?>
                                            <li>
                                                <a href="<?php echo htmlspecialchars($chapOverviewHref); ?>" style="font-size: 0.82rem; font-weight: 700; color: var(--rs-text-muted); text-decoration: underline; text-underline-offset: 3px;">
                                                    Chapter Overview: <?php echo htmlspecialchars($chap['chap_title']); ?> &rarr;
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($partList)): ?>
                                            
                                            <!-- Iterate over individual parts (pXXX.md files) -->
                                            <?php foreach ($partList as $part): 
                                                $href = $fileToUrl[$part['file_path']] ?? ($part['url'] ?? $part['file_path']);
                                                $partHasImage = !empty($part['image']) || !empty($part['images']);
                                                $partWords = (int)($part['word_count'] ?? 0);
                                                $partMins = (int)($part['reading_minutes'] ?? ($partWords > 0 ? max(1, ceil($partWords / 250)) : 0));
                                            ?>
                                                <li>
                                                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                                        <span style="display: inline-flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                                            <i class="ph ph-file-text" aria-hidden="true"></i>
                                                            <span><?php echo htmlspecialchars($part['part_title']); ?></span>
                                                            <?php if ($partHasImage): ?>
                                                                <span title="Illustrated Scene" style="font-size: 0.72rem; font-weight: 600; padding: 0.1rem 0.42rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.22rem;">
                                                                    <i class="ph ph-image" aria-hidden="true"></i>
                                                                    <span>Illustrated</span>
                                                                </span>
                                                            <?php endif; ?>
                                                        </span>
                                                        <?php if ($partWords > 0): ?>
                                                            <span style="font-size: 0.74rem; font-weight: 600; color: var(--rs-text-muted); white-space: nowrap; font-variant-numeric: tabular-nums;">
                                                                <?php echo number_format($partWords); ?>w &bull; <?php echo $partMins; ?>m
                                                            </span>
                                                        <?php endif; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li style="color: var(--rs-text-muted);">No parts available yet.</li>
                                        <?php endif; ?>
                                    </ul>
                                </details>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="margin-top: 1rem; color: var(--rs-text-muted);">No chapters available.</p>
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
