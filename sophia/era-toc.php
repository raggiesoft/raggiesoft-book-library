<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/era-toc.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This sub-view renders the scoped Table of Contents for a single Story Era
 *     (or "Book" / "Episode" volume, e.g. `https://books.raggiesoft.com/rachel/<era-slug>`)
 *     inside a larger Ocean View Archives novel.
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php` & `sophia/oliver.php`:
 *     1. In `data/routes/<series>.json`, every Era URL maps to an internal token:
 *          - Linear Era: `__ERA_TOC__|<eraIndex>`
 *          - Story Crossroads Branch Era: `__ERA_TOC__|<pathId>|<eraIndex>`
 *     2. `eleanor/isabel.php` loads `$toc_manifest` (`toc.json`), resolves active
 *        Story Crossroads choices into `$effectiveTocEras`, and loads
 *        `sophia/sidebar.php` (which populates `$fileToUrl`, mapping `e001/c001/p001.md`
 *        to its clean semantic URL).
 *     3. `sophia/oliver.php` unpacks the `__ERA_TOC__|...` token into
 *        `$specialPagePathId` and `$specialPageIndex1` (the 0-based Era index),
 *        captures this file's output via `ob_start()`, and renders it inside
 *        `#stardust-reading-pane` complete with breadcrumbs and Schema.org JSON-LD.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - The Era `<h1>` title and optional multi-sentence `era_description`.
 *     - Every Chapter in that Era, using a smart UX rule ("Single-Part Chapter Collapse"):
 *         * When a Chapter has ONLY 1 Part (`$partCount === 1`), it renders as a
 *           clean, one-click card ("Read Chapter →") so the reader doesn't have
 *           to click through a redundant sub-list of 1 item!
 *         * When a Chapter has 2+ Parts, it renders a Chapter `<h2>` card with a
 *           clickable link to `chap-toc.php` plus the full list of individual Parts.
 * =============================================================================
 */

// Import shared routing and manifest variables from `isabel.php`, `sidebar.php`, and `oliver.php`.
global $toc_manifest, $effectiveTocEras, $specialPagePathId, $specialPageIndex1, $fileToUrl;

// Step 1: Resolve the requested Era array (`$era`) from either:
//   (a) A specific Story Crossroads path segment (`$toc_manifest['paths'][$specialPagePathId]['eras']`), or
//   (b) The active merged Era list (`$effectiveTocEras`), falling back to `$toc_manifest['eras']`.
if (!empty($specialPagePathId) && isset($toc_manifest['paths'][$specialPagePathId]['eras'][$specialPageIndex1])) {
    $era = $toc_manifest['paths'][$specialPagePathId]['eras'][$specialPageIndex1];
} else {
    $eraSourceList = (!empty($effectiveTocEras) && is_array($effectiveTocEras)) ? $effectiveTocEras : ($toc_manifest['eras'] ?? []);
    $era = $eraSourceList[$specialPageIndex1] ?? ($toc_manifest['eras'][$specialPageIndex1] ?? null);
}

// Guard clause: stop early with a friendly message if `$specialPageIndex1` is out of range.
if (!$era) {
    echo '<p>Era not found.</p>';
    return;
}
?>
<!-- Scoped Era Overview wrapper rendered inside `#stardust-reading-pane` -->
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <!-- Main Era primary h1 Heading -->
    <h1 style="margin-bottom: 2rem; color: var(--rs-primary); border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;"><?php echo htmlspecialchars($era['era_title']); ?></h1>
    
    <!-- Optional Era synopsis paragraph (from the Era folder's `ovab.json`) -->
    <?php if (!empty($era['era_description'])): ?>
        <p style="margin-bottom: 2rem; font-size: 1.1rem; opacity: 0.9;"><?php echo htmlspecialchars($era['era_description']); ?></p>
    <?php endif; ?>

    <!-- Vertical stack of Chapter cards inside this Era -->
    <div class="toc-chapters" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (!empty($era['chapters'])): ?>
            <!-- Loop through every Chapter belonging to this Era -->
            <?php foreach ($era['chapters'] as $chap): 
                // Safely normalize `parts` to an array and count how many Parts this Chapter has
                $partList = !empty($chap['parts']) && is_array($chap['parts']) ? $chap['parts'] : [];
                $partCount = count($partList);
            ?>
                <?php if ($partCount === 1):
                    /*
                     * SMART SINGLE-PART CHAPTER COLLAPSE:
                     * When a Chapter (`cXXX`) contains only 1 Part (`p001.md`), render the entire
                     * chapter card as a direct one-click link to the story scene rather than a nested list.
                     * We also check whether the Part's subtitle adds new information beyond `$rawChapTitle`
                     * so we never repeat the exact same title twice on one line!
                     */
                    $singlePart = $partList[0];
                    $singleHref = $fileToUrl[$singlePart['file_path']] ?? ($singlePart['url'] ?? $singlePart['file_path']);
                    $rawChapTitle = (string)($chap['chap_title'] ?? '');
                    $rawPartTitle = (string)($singlePart['part_title'] ?? '');
                    // Strip leading "Part 1: " prefix to compare the core subtitle text
                    $cleanPartSub = trim((string)preg_replace('/^Part\s+\d+\s*:\s*/i', '', $rawPartTitle));
                    $showPartSub = ($cleanPartSub !== '' && stripos($rawChapTitle, $cleanPartSub) === false && strcasecmp($cleanPartSub, 'Untitled Chapter') !== 0 && !preg_match('/^Part\s+\d+$/i', $cleanPartSub));
                    $singleHasImage = !empty($singlePart['image']) || !empty($singlePart['images']);
                ?>
                <!-- Single-Part Chapter Card: Direct 1-click link straight into `oliver.php` -->
                <div style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.25rem 1.5rem;">
                    <a href="<?php echo htmlspecialchars($singleHref); ?>"
                       style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; justify-content: space-between; gap: 1rem; font-size: 1.25rem; font-weight: 700; transition: opacity 0.2s;"
                       onmouseover="this.style.opacity='0.82'"
                       onmouseout="this.style.opacity='1'">
                        <span style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <i class="ph ph-file-text" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($rawChapTitle); ?></span>
                            <?php if ($showPartSub): ?>
                                <span style="font-weight: 500; font-size: 1rem; color: var(--rs-text-muted);">&mdash; <?php echo htmlspecialchars($cleanPartSub); ?></span>
                            <?php endif; ?>
                            <?php if ($singleHasImage): ?>
                                <span title="Illustrated Scene" style="font-size: 0.75rem; font-weight: 600; padding: 0.12rem 0.45rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="ph ph-image" aria-hidden="true"></i>
                                    <span>Illustrated</span>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--rs-text-muted); white-space: nowrap; display: inline-flex; align-items: center; gap: 0.3rem;">
                            <span>Read Chapter</span>
                            <i class="ph ph-arrow-right" aria-hidden="true"></i>
                        </span>
                    </a>
                </div>
                <?php else:
                    // Multi-Part Chapter (or 0-part placeholder): strip legacy `/raggiesoft-books/books` prefix
                    // to build the clean Chapter Overview route (`/<series>/<era>/<chapter>`).
                    $chapOverviewHref = !empty($chap['chap_url']) ? str_replace('/raggiesoft-books/books', '', (string)$chap['chap_url']) : '';
                ?>
                <!-- Multi-Part Chapter Card: Folder Heading + Bulleted List of Scene Parts -->
                <div style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem;">
                    <!-- Chapter `<h2>` header with link to `chap-toc.php` and a Part count pill on the right -->
                    <h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.3rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="ph ph-folder" aria-hidden="true"></i>
                            <?php if ($chapOverviewHref !== ''): ?>
                                <a href="<?php echo htmlspecialchars($chapOverviewHref); ?>" style="color: var(--rs-text); text-decoration: none;"><?php echo htmlspecialchars($chap['chap_title']); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($chap['chap_title']); ?>
                            <?php endif; ?>
                        </span>
                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--rs-text-muted);"><?php echo $partCount > 0 ? ((int)$partCount . ' Parts') : 'Coming Soon'; ?></span>
                    </h2>
                    
                    <!--Accessible `role="list"` of every Part (`p001.md`, `p002.md`, ...) inside this Chapter -->
                    <ul role="list" style="list-style: none; padding: 0 0 0 1rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if (!empty($partList)): ?>
                            <!-- Loop through all Parts belonging to this Chapter -->
                            <?php foreach ($partList as $part): 
                                // Map internal file path (`e001/c001/p001.md`) -> clean URL via `$fileToUrl`
                                $href = $fileToUrl[$part['file_path']] ?? ($part['url'] ?? $part['file_path']);
                                $partHasImage = !empty($part['image']) || !empty($part['images']);
                            ?>
                                <li>
                                    <!-- Clickable Part link opening `oliver.php` -->
                                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="ph ph-file-text" aria-hidden="true"></i>
                                        <span><?php echo htmlspecialchars($part['part_title']); ?></span>
                                        <?php if ($partHasImage): ?>
                                            <span title="Illustrated Scene" style="font-size: 0.72rem; font-weight: 600; padding: 0.1rem 0.42rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.22rem;">
                                                <i class="ph ph-image" aria-hidden="true"></i>
                                                <span>Illustrated</span>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback when a Chapter folder has no published Parts yet -->
                            <li style="color: var(--rs-text-muted);">No parts available yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback when an Era has no Chapters defined yet -->
            <p style="color: var(--rs-text-muted);">No chapters available.</p>
        <?php endif; ?>
    </div>
</div>
