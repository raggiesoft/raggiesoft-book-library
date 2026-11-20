<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/toc.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This sub-view renders the full Table of Contents for a single book series
 *     at `https://books.raggiesoft.com/<series>/toc` (for example, `/rachel/toc`
 *     or `/stardust-engine/toc`).
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php`, `sophia/sidebar.php`, & `sophia/oliver.php`:
 *     1. In `data/routes/<series>.json`, `/<series>/toc` maps to `'__TOC__'`.
 *     2. `eleanor/isabel.php` loads the book's `toc.json` into `$toc_manifest`
 *        AND runs the Multi-Crossroads Narrative Graph Resolver, walking from
 *        `'__root__'` through any saved Story Crossroads choices (stored in the
 *        `rs_cx_<series>` cookie) to produce:
 *          - `$effectiveTocEras`: Merged Eras/Chapters/Parts along the active route!
 *          - `$activeCrossroadsChain`: Ordered history of Crossroads decisions made so far.
 *          - `$pendingFrontierCrossroads`: The next unchosen Crossroads waiting at the
 *            end ("frontier") of the current Table of Contents, if any!
 *     3. `sophia/sidebar.php` builds `$fileToUrl` (mapping `e001/c001/p001.md` to
 *        clean semantic URLs), and `sophia/oliver.php` renders this file inside
 *        `#stardust-reading-pane` via PHP output buffering (`ob_start()`).
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     1. Header bar with live story word count, estimated print pages (`words / 275`),
 *        and a button to the dedicated Table of Illustrations (`/<series>/illustrations`).
 *     2. An optional collapsible "Table of Illustrations — Visual Scene Index" drawer.
 *     3. Active Story Crossroads Decision History bars (`#rs-toc-crossroads`), allowing
 *        the reader to switch routes or reset a choice (`?path=reset&cx=<id>`) anytime.
 *     4. Native HTML `<details>` / `<summary>` accordions for every Era and Chapter,
 *        automatically collapsing 1-part chapters into direct one-click links.
 *     5. A Frontier Story Crossroads selector at the bottom if the book is an
 *        interactive Story Crossroads novel and the reader has reached a branching point!
 *
 * 100-LEVEL HTML & JS CONCEPTS TO NOTICE HERE:
 *     - Why use `<span class="toc-era-heading">` instead of `<h2>` inside `<summary>`?
 *       Many screen readers strip heading semantics inside `<summary>` buttons, or
 *       announce awkward nested interactive controls. Styling a `<span>` keeps the
 *       accordion button announcement clean and predictable!
 *     - Notice `data-story-path`, `data-story-crossroads`, and `data-story-book` on
 *       Crossroads links: `reader.js` intercepts clicks in the DOM *capture* phase
 *       to write the updated `rs_cx_<book>` cookie *before* the SPA `fetch()` fires,
 *       so the PHP server immediately renders the newly chosen branch!
 * =============================================================================
 */

// Import shared manifest, Story Crossroads graph state, and routing globals from `isabel.php`.
global $toc_manifest, $effectiveTocEras, $activeStoryPathId, $activeStoryPathObj, $activeCrossroadsChain, $pendingFrontierCrossroads, $cdnBaseUrl, $seriesSlug;
?>
<!-- 
  MAIN CONTAINER:
  Constrains the Table of Contents width to 960px for comfortable scanning on desktop displays.
-->
<?php
// Prepare top-of-page statistics and Story Crossroads state arrays with safe defaults.
$tocTotalIllustrations = (int)($toc_manifest['illustration_count'] ?? 0);
$tocWordStats = $toc_manifest['word_stats'] ?? [];
$tocStoryWords = (int)($tocWordStats['story_word_count'] ?? 0);
// Standard publishing trade-paperback estimate: ~275 words per printed page (`ceil()` rounds up).
$tocStoryPages = (int)($tocWordStats['estimated_story_pages'] ?? ($tocStoryWords > 0 ? ceil($tocStoryWords / 275) : 0));
// Prefer `$effectiveTocEras` (which merges active Story Crossroads branches) over raw `$toc_manifest['eras']`.
$tocErasList = (isset($effectiveTocEras) && is_array($effectiveTocEras)) ? $effectiveTocEras : ($toc_manifest['eras'] ?? []);
$tocActiveChain = (isset($activeCrossroadsChain) && is_array($activeCrossroadsChain)) ? $activeCrossroadsChain : [];
$tocFrontierCrossroads = $pendingFrontierCrossroads ?? null;
?>
<div class="book-page" style="max-width: 960px; margin: 0 auto;">
    <!-- SECTION 1: TOC HEADER, WORD/PAGE TELEMETRY, & ILLUSTRATIONS GALLERY LINK -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">
        <div>
            <h1 style="margin: 0; color: var(--rs-primary);">Table of Contents</h1>
            <?php if ($tocStoryWords > 0): ?>
                <!-- `font-variant-numeric: tabular-nums` aligns digits cleanly like a ledger -->
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
        <!-- SECTION 2: EMBEDDED COLLAPSIBLE TABLE OF ILLUSTRATIONS (`$illustrationsTocMode = 'embedded'`) -->
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
                // Reuses the exact same gallery component as `sophia/illustrations.php`, configured for embedded accordion mode!
                $illustrationsTocMode = 'embedded';
                require __DIR__ . '/../includes/components/illustrations-toc.php';
                ?>
            </div>
        </details>
    <?php endif; ?>
    
    <?php if (!empty($tocActiveChain)): ?>
        <!--
          SECTION 3: STORY CROSSROADS DECISION HISTORY & ROUTE SWITCHERS
          Whenever a reader has chosen one or more branching Story Crossroads options in an
          interactive novel, `$tocActiveChain` lists every decision in sequence so the reader
          can see which route they are currently viewing, jump to its opening chapter, reset
          the choice (`?path=reset`), or click another pill to switch routes on the fly!
        -->
        <div id="rs-toc-crossroads" style="display: flex; flex-direction: column; gap: 0.9rem; margin-bottom: 1.5rem;">
        <?php foreach ($tocActiveChain as $chainIdx => $chainStep):
            $stepCx  = $chainStep['crossroads'] ?? [];
            $stepOpt = $chainStep['chosen_option'] ?? [];
            $stepCxId = (string)($stepCx['id'] ?? '');
            $stepOptId = (string)($stepOpt['id'] ?? '');
        ?>
            <section class="rs-active-path-bar" aria-label="Active Story Path Decision" style="margin-bottom: 0;">
                <div class="rs-active-path-bar-main">
                    <div class="rs-active-path-copy">
                        <span class="rs-crossroads-kicker">
                            <i class="ph-fill ph-signpost" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars((string)($stepCx['kicker'] ?? ('Story Crossroads ' . ($chainIdx + 1)))); ?></span>
                        </span>
                        <div class="rs-active-path-name"><?php echo htmlspecialchars((string)($stepOpt['label'] ?? '')); ?></div>
                        <?php if (!empty($stepOpt['subtitle'])): ?>
                            <p class="rs-active-path-sub"><?php echo htmlspecialchars((string)$stepOpt['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="rs-active-path-actions">
                        <?php if (!empty($stepOpt['entry_url'])): ?>
                            <a href="<?php echo htmlspecialchars((string)$stepOpt['entry_url']); ?>" class="rs-btn rs-btn-primary">
                                <span>Open Path Start</span>
                                <i class="ph ph-arrow-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                        <!-- `data-story-path="reset"` clears this Crossroads choice in the `rs_cx_<book>` cookie & `localStorage` -->
                        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc?path=reset&amp;cx=<?php echo rawurlencode($stepCxId); ?>#rs-toc-crossroads"
                           data-story-path="reset"
                           data-story-crossroads="<?php echo htmlspecialchars($stepCxId); ?>"
                           data-story-book="<?php echo htmlspecialchars($seriesSlug); ?>"
                           class="rs-btn">
                            <i class="ph ph-arrows-clockwise" aria-hidden="true"></i>
                            <span>Change Choice</span>
                        </a>
                    </div>
                </div>
                <?php if (!empty($stepCx['options'])): ?>
                <!-- Quick-switch route pills for all options at this Crossroads -->
                <div class="rs-active-path-switcher-pills">
                    <?php foreach ($stepCx['options'] as $cxOpt):
                        $cxOptId = (string)($cxOpt['id'] ?? '');
                        $isCurrentOpt = ($cxOptId === $stepOptId);
                    ?>
                        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc?cx=<?php echo rawurlencode($stepCxId); ?>&amp;path=<?php echo rawurlencode($cxOptId); ?>"
                           data-story-path="<?php echo htmlspecialchars($cxOptId); ?>"
                           data-story-crossroads="<?php echo htmlspecialchars($stepCxId); ?>"
                           data-story-book="<?php echo htmlspecialchars($seriesSlug); ?>"
                           class="rs-path-pill<?php echo $isCurrentOpt ? ' is-active' : ''; ?>"
                           <?php if ($isCurrentOpt): ?>aria-current="true"<?php endif; ?>>
                            <i class="ph <?php echo $isCurrentOpt ? 'ph-check-circle' : 'ph-signpost'; ?>" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars((string)($cxOpt['label'] ?? '')); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($tocErasList)): ?>
        <!-- SECTION 4: MULTI-LEVEL ERA -> CHAPTER -> PART ACCORDION -->
        <div class="toc-accordion" style="display: flex; flex-direction: column; gap: 1rem;">
            
            <!-- Outer Loop: Iterate over top-level Story Eras (`e001`, `e002`, ...) -->
            <?php foreach ($tocErasList as $eIndex => $era):
                $eraIllusCount = (int)($era['illustration_count'] ?? 0);
                $eraWordCount = (int)($era['word_count'] ?? 0);
            ?>
                <details class="toc-book" style="background: var(--rs-bg); border: 1px solid var(--rs-border); border-radius: 8px; overflow: hidden;">
                    <!-- Native `<summary>` toggle header for the Era (uses `<span>` rather than `<h2>` inside `<summary>` to keep screen-reader heading trees clean) -->
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
                    
                    <!-- Expanded Era Body: Direct Era Overview Link + Chapters List -->
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
                            
                            <!-- Middle Loop: Iterate over Chapters (`c001`, `c002`, ...) inside the current Era -->
                            <?php foreach ($era['chapters'] as $cIndex => $chap): 
                                // `$fileToUrl` was built by `sophia/sidebar.php` to map `eXXX/cXXX/pXXX.md` -> clean URL
                                global $fileToUrl;
                                $partList = !empty($chap['parts']) && is_array($chap['parts']) ? $chap['parts'] : [];
                                $partCount = count($partList);
                                $chapIllusCount = (int)($chap['illustration_count'] ?? 0);
                            ?>
                                <?php if ($partCount === 1): 
                                    /*
                                     * SMART SINGLE-PART CHAPTER COLLAPSE:
                                     * When a Chapter (`cXXX`) contains only 1 Part (`p001.md`), bypass the
                                     * redundant nested `<details>` accordion and render the Chapter as a
                                     * direct single-click link with word count & reading-minute estimate (`words / 250`).
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
                                <!-- Nested `<details>` Accordion for Multi-Part Chapters (`2+` Parts) -->
                                <details class="toc-chapter" style="margin-top: 1rem;">
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
                                            
                                            <!-- Inner Loop: Iterate over individual scene Parts (`p001.md`, `p002.md`, ...) -->
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

        <?php if ($tocFrontierCrossroads !== null):
            // SECTION 5: PENDING FRONTIER STORY CROSSROADS SELECTOR
            // Automatically picks the 2-way signpost emblem for binary Crossroads or 3-way emblem for 3+ options!
            $frontierCxId = (string)($tocFrontierCrossroads['id'] ?? '');
            $frontierOptsList = is_array($tocFrontierCrossroads['options'] ?? null) ? $tocFrontierCrossroads['options'] : [];
            $frontierDirs = (count($frontierOptsList) >= 3) ? 3 : 2;
            $frontierLogoUrl = rtrim($cdnBaseUrl, '/') . "/raggiesoft-books/images/logos/story-crossroads-logo-{$frontierDirs}way-512.png";
        ?>
            <!-- Coequal Story Crossroads Selector (Shown directly at the frontier of the active TOC whenever an unchosen Crossroads is reached) -->
            <section <?php echo empty($tocActiveChain) ? 'id="rs-toc-crossroads" ' : ''; ?>class="rs-crossroads-section" aria-labelledby="rs-toc-crossroads-heading" style="margin-top: 1.75rem;">
                <div class="rs-crossroads-header">
                    <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.5rem;">
                        <img src="<?php echo htmlspecialchars($frontierLogoUrl); ?>"
                             alt=""
                             aria-hidden="true"
                             width="52"
                             height="52"
                             loading="lazy"
                             style="width: 52px; height: 52px; border-radius: 12px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.22); border: 1px solid rgba(227, 178, 124, 0.45);">
                        <div class="rs-crossroads-kicker" style="margin-bottom: 0;">
                            <i class="ph-fill ph-signpost" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars((string)($tocFrontierCrossroads['kicker'] ?? 'RaggieSoft Story Crossroads')); ?></span>
                        </div>
                    </div>
                    <h2 id="rs-toc-crossroads-heading" class="rs-crossroads-title"><?php echo htmlspecialchars((string)($tocFrontierCrossroads['title'] ?? 'Choose How the Story Continues')); ?></h2>
                    <?php if (!empty($tocFrontierCrossroads['prompt'])): ?>
                        <p class="rs-crossroads-prompt"><?php echo htmlspecialchars((string)$tocFrontierCrossroads['prompt']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($tocFrontierCrossroads['trigger_url'])): ?>
                        <p style="margin: 0.5rem 0 0; font-size: 0.9rem;">
                            <a href="<?php echo htmlspecialchars((string)$tocFrontierCrossroads['trigger_url']); ?>" style="color: var(--rs-primary); font-weight: 700; text-decoration: underline; text-underline-offset: 3px;">
                                Read the Crossroads Scene First &rarr;
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                <!-- Option Cards: Clicking "Show Chapters for This Path" saves the route choice and reveals its Eras/Chapters -->
                <div class="rs-crossroads-grid">
                    <?php foreach (($tocFrontierCrossroads['options'] ?? []) as $cxOpt):
                        $optId = (string)($cxOpt['id'] ?? '');
                        $optLabel = (string)($cxOpt['label'] ?? '');
                        $optSub = (string)($cxOpt['subtitle'] ?? '');
                        $optImgRaw = (string)($cxOpt['image'] ?? '');
                        $optImg = $optImgRaw !== ''
                            ? ((strpos($optImgRaw, 'http') === 0) ? $optImgRaw : rtrim($cdnBaseUrl, '/') . '/' . ltrim($optImgRaw, '/'))
                            : '';
                    ?>
                    <article class="rs-crossroads-card">
                        <?php if ($optImg !== ''): ?>
                            <img src="<?php echo htmlspecialchars($optImg); ?>" alt="<?php echo htmlspecialchars($optLabel); ?>" class="rs-crossroads-card-img" loading="lazy">
                        <?php endif; ?>
                        <div class="rs-crossroads-card-body">
                            <h3 class="rs-crossroads-card-title"><?php echo htmlspecialchars($optLabel); ?></h3>
                            <?php if ($optSub !== ''): ?>
                                <p class="rs-crossroads-card-desc"><?php echo htmlspecialchars($optSub); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="rs-crossroads-card-footer">
                            <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc?cx=<?php echo rawurlencode($frontierCxId); ?>&amp;path=<?php echo rawurlencode($optId); ?>"
                               data-story-path="<?php echo htmlspecialchars($optId); ?>"
                               data-story-crossroads="<?php echo htmlspecialchars($frontierCxId); ?>"
                               data-story-book="<?php echo htmlspecialchars($seriesSlug); ?>"
                               class="rs-btn rs-btn-primary rs-crossroads-choose-btn">
                                <span>Show Chapters for This Path</span>
                                <i class="ph ph-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Inline CSS hiding default WebKit `<summary>` triangles and rotating our custom `.toc-caret` icon 180deg when `[open]` -->
        <style>
            details > summary::-webkit-details-marker { display: none; }
            details[open] > summary .toc-caret { transform: rotate(180deg); }
            .toc-caret { transition: transform 0.2s ease; }
        </style>
    <?php else: ?>
        <p>No content available.</p>
    <?php endif; ?>
</div>
