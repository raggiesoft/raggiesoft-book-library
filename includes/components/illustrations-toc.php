<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: includes/components/illustrations-toc.php
 * App:  RaggieSoft Book Reader (`https://books.raggiesoft.com`)
 *
 * WHAT IS THIS FILE?
 *   Many Ocean View Archives novels feature dozens (or 100+) of full-color
 *   scene illustrations across their Eras and Chapters. Just like classic
 *   hardcover books include a "List of Illustrations" near the front, this
 *   reusable PHP component builds an interactive, searchable **Table of
 *   Illustrations** gallery for any novel!
 *
 * WHERE IS IT INCLUDED? (Dual-Mode Reusability)
 *   1. **Standalone Page Mode** (`$illustrationsTocMode = 'page'`, default):
 *      Included by `sophia/illustrations.php` at `/<seriesSlug>/illustrations`
 *      (e.g. `https://books.raggiesoft.com/rachel/illustrations`).
 *   2. **Embedded Gallery Mode** (`$illustrationsTocMode = 'embedded'`):
 *      Included inside a collapsible section on `/<seriesSlug>/toc` (`sophia/toc.php`).
 *
 * WHAT FEEDS INTO IT?
 *   - `$toc_manifest`: The parsed `toc.json` array loaded by `eleanor/isabel.php`
 *     (containing `eras -> chapters -> parts`, plus any Story Crossroads `paths`).
 *   - `$cdnBaseUrl`:   CDN origin (`https://assets.raggiesoft.com`).
 *   - `$seriesSlug`:   Current book slug (e.g. `'rachel'`, `'katie'`, `'aubrie'`).
 *   - `$fileToUrl`:    Optional map converting `.md` file paths to clean reader URLs.
 *
 * KEY MODERN WEB TECHNIQUES DEMONSTRATED IN THIS FILE:
 *   1. **Responsive CSS Grid + 3-Row Card Sub-Alignment**:
 *      `grid-template-columns: repeat(auto-fill, minmax(min(100%, 285px), 1fr))`
 *      automatically fits 1, 2, or 3+ columns without media-query breakpoints,
 *      while `grid-template-rows: auto 1fr auto` keeps every card's thumbnail,
 *      body text, and footer action bar neatly aligned.
 *   2. **Zero-Layout-Shift Thumbnails (`aspect-ratio: 16 / 9`)**:
 *      Reserves the exact 16:9 box before images finish downloading so the page
 *      never jumps around (Cumulative Layout Shift `CLS = 0`).
 *   3. **Off-Screen Rendering Deferral (`content-visibility: auto`)**:
 *      On novels with 100+ illustrations across 20 Eras, rendering every off-screen
 *      card on initial paint would slow down mobile phones. Putting `content-visibility: auto`
 *      on below-the-fold `<details>` sections tells the browser to skip layout/paint
 *      for off-screen Eras until the user scrolls near them—and our Era quick-jump
 *      script unlocks preceding sections before smooth-scrolling so anchor jumps
 *      land on the exact pixel!
 *   4. **Instant Client-Side Search + Shared Native `<dialog>` Lightbox**:
 *      Includes `illustration-lightbox-dialog.php` at the bottom for full-screen zoom.
 * =============================================================================
 */

// Default to standalone `'page'` mode unless the caller (`sophia/toc.php`) set `'embedded'`
if (!isset($illustrationsTocMode)) {
    $illustrationsTocMode = 'page';
}

$cdnBase = isset($cdnBaseUrl) ? rtrim($cdnBaseUrl, '/') : 'https://assets.raggiesoft.com';
$activeSeriesSlug = $seriesSlug ?? ($toc_manifest['series_slug'] ?? '');
$activeSeriesTitle = $toc_manifest['series_title'] ?? ucwords(str_replace('-', ' ', $activeSeriesSlug));

/**
 * Helper: Normalize an illustration source path into a full CDN or absolute URL.
 * Why wrap in `if (!function_exists(...))`?
 *   In PHP, declaring a named `function` twice in the same request throws a fatal error.
 *   Checking `!function_exists(...)` makes this component safe even if included multiple times!
 */
if (!function_exists('resolveNarrativeIllustrationUrl')) {
    function resolveNarrativeIllustrationUrl(string $rawSrc, string $cdnBase): string {
        $src = trim($rawSrc);
        if ($src === '') {
            return '';
        }
        // Strip Markdown layout fragments such as `#fullwidth` or `#inline`
        $src = (string)preg_replace('/#.*$/', '', $src);
        // Strip `{{CDN}}`, `{{cms}}`, or leading CDN origin so we can normalize relative paths cleanly
        $src = str_replace(['{{CDN}}', '{{cms}}', $cdnBase, 'https://assets.raggiesoft.com'], '', $src);
        if (preg_match('/^https?:\/\//i', $src)) {
            return $src;
        }
        $cleanRel = ltrim($src, '/');
        if ($cleanRel === '') {
            return '';
        }
        // Prepend the default `raggiesoft-books/images/scenes/` prefix when a short scene path was given
        if (
            strpos($cleanRel, 'raggiesoft-books/') !== 0 &&
            strpos($cleanRel, 'raggiesoft-lore/') !== 0 &&
            strpos($cleanRel, 'engine-room-records/') !== 0
        ) {
            $cleanRel = 'raggiesoft-books/images/scenes/' . $cleanRel;
        }
        return $cdnBase . '/' . $cleanRel;
    }
}

// ============================================================================
// STEP 1: WALK `toc.json` & COLLECT ILLUSTRATED PARTS GROUPED BY ERA
// ============================================================================
$illustratedEras = [];
$totalIllustrationsCount = 0;
$totalIllustratedPartsCount = 0;
$globalIllustrationNumber = 0;

// Combine mainline `eras` with any interactive Story Crossroads branch `paths`
// so readers browsing the Illustrations Gallery can discover artwork from every route.
$allGalleryEras = is_array($toc_manifest['eras'] ?? null) ? $toc_manifest['eras'] : [];
if (!empty($toc_manifest['paths']) && is_array($toc_manifest['paths'])) {
    foreach ($toc_manifest['paths'] as $pDef) {
        if (!empty($pDef['eras']) && is_array($pDef['eras'])) {
            foreach ($pDef['eras'] as $pEra) {
                $pEraCopy = $pEra;
                if (!empty($pDef['label'])) {
                    $pEraCopy['era_title'] = ($pEra['era_title'] ?? 'Story Path') . ' (' . $pDef['label'] . ')';
                }
                $allGalleryEras[] = $pEraCopy;
            }
        }
    }
}
// Track seen `.md` paths in a hash map (`['e001/c001/p001.md' => true]`) so shared parts aren't listed twice
$seenIllustratedFilePaths = [];

if (!empty($allGalleryEras)) {
    foreach ($allGalleryEras as $eIdx => $era) {
        $eraNum = $era['era_num'] ?? ($eIdx + 1);
        $eraTitle = $era['era_title'] ?? "Era {$eraNum}";
        $eraUrl = !empty($era['era_url'])
            ? str_replace('/raggiesoft-books/books', '', $era['era_url'])
            : "/{$activeSeriesSlug}";

        $eraItems = [];

        if (!empty($era['chapters']) && is_array($era['chapters'])) {
            foreach ($era['chapters'] as $cIdx => $chap) {
                $chapNum = $chap['chap_num'] ?? ($cIdx + 1);
                $chapTitle = $chap['chap_title'] ?? "Chapter {$chapNum}";
                $chapUrl = !empty($chap['chap_url'])
                    ? str_replace('/raggiesoft-books/books', '', $chap['chap_url'])
                    : $eraUrl;

                if (!empty($chap['parts']) && is_array($chap['parts'])) {
                    foreach ($chap['parts'] as $part) {
                        $partFpKey = (string)($part['file_path'] ?? ($part['url'] ?? ''));
                        if ($partFpKey !== '') {
                            if (isset($seenIllustratedFilePaths[$partFpKey])) {
                                continue;
                            }
                            $seenIllustratedFilePaths[$partFpKey] = true;
                        }
                        $partImages = [];

                        // Support both multi-image parts (`images: [{src, alt}, ...]`) and single-image parts (`image`, `image_alt`)
                        if (!empty($part['images']) && is_array($part['images'])) {
                            $partImages = $part['images'];
                        } elseif (!empty($part['image'])) {
                            $partImages[] = [
                                'src' => $part['image'],
                                'alt' => $part['image_alt'] ?? ''
                            ];
                        }

                        if (empty($partImages)) {
                            continue;
                        }

                        // Deduplicate images within the same part after normalizing URLs
                        $normalizedPartImages = [];
                        $seenUrls = [];
                        foreach ($partImages as $imgMeta) {
                            $rawSrc = is_array($imgMeta) ? ($imgMeta['src'] ?? '') : (string)$imgMeta;
                            $rawAlt = is_array($imgMeta) ? ($imgMeta['alt'] ?? '') : '';
                            $fullImgUrl = resolveNarrativeIllustrationUrl($rawSrc, $cdnBase);
                            if ($fullImgUrl === '' || in_array($fullImgUrl, $seenUrls, true)) {
                                continue;
                            }
                            $seenUrls[] = $fullImgUrl;
                            $normalizedPartImages[] = [
                                'src' => $fullImgUrl,
                                'alt' => $rawAlt
                            ];
                        }

                        if (empty($normalizedPartImages)) {
                            continue;
                        }

                        $totalIllustratedPartsCount++;
                        $partFilePath = $part['file_path'] ?? '';
                        $partUrl = $part['url'] ?? ($fileToUrl[$partFilePath] ?? '#');
                        $partTitle = $part['part_title'] ?? 'Untitled Part';
                        $partDate = $part['date'] ?? '';
                        $partStartTime = $part['start_time'] ?? '';
                        $partLocation = $part['location'] ?? '';

                        // Flatten each image into a structured card record (`#1`, `#2`, ...)
                        foreach ($normalizedPartImages as $imgIdx => $normImg) {
                            $globalIllustrationNumber++;
                            $totalIllustrationsCount++;

                            $eraItems[] = [
                                'number'       => $globalIllustrationNumber,
                                'img_index'    => $imgIdx + 1,
                                'img_total'    => count($normalizedPartImages),
                                'src'          => $normImg['src'],
                                'alt'          => $normImg['alt'] !== '' ? $normImg['alt'] : "{$partTitle} ({$chapTitle})",
                                'caption'      => $normImg['alt'],
                                'part_title'   => $partTitle,
                                'part_url'     => $partUrl,
                                'chap_num'     => $chapNum,
                                'chap_title'   => $chapTitle,
                                'chap_url'     => $chapUrl,
                                'era_num'      => $eraNum,
                                'era_title'    => $eraTitle,
                                'era_url'      => $eraUrl,
                                'date'         => $partDate,
                                'start_time'   => $partStartTime,
                                'location'     => $partLocation
                            ];
                        }
                    }
                }
            }
        }

        // Only emit an Era `<details>` section if at least 1 illustration exists in that Era
        if (!empty($eraItems)) {
            $illustratedEras[] = [
                'era_index'  => $eIdx,
                'era_num'    => $eraNum,
                'era_title'  => $eraTitle,
                'era_url'    => $eraUrl,
                'era_anchor' => 'illus-era-' . intval($eraNum),
                'items'      => $eraItems
            ];
        }
    }
}
?>

<style>
/* ============================================================================
 * STEP 2: TABLE OF ILLUSTRATIONS COMPONENT STYLES (Dark & Light Mode + WCAG 2.1 AA)
 * Uses Ocean View Archives CSS custom properties (`--rs-primary`, `--rs-bg`,
 * `--rs-surface`, `--rs-border`, `--rs-text`) so colors switch automatically
 * across Light, Dark, Sepia, and Dark-Sepia reading themes!
 * ============================================================================ */
.illus-toc-wrapper {
    margin-block: 1.25rem 2.5rem;
}

.illus-toc-summary-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    background: var(--rs-surface, rgba(255, 255, 255, 0.04));
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.22));
    margin-block-end: 1.25rem;
}

.illus-toc-stats {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1.25rem;
    font-size: 0.92rem;
    color: var(--rs-text, #f4efe6);
}

.illus-toc-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 600;
}

.illus-toc-stat-pill i {
    color: var(--rs-primary, #e3b27c);
    font-size: 1.1rem;
}

/* Progressive enhancement: `flex-wrap: wrap` works everywhere; browsers supporting
   `flex-wrap: balance` distribute pills evenly across wrapped lines without orphan pills! */
.illus-toc-era-pills {
    display: flex;
    flex-wrap: wrap;
    flex-wrap: balance;
    gap: 0.5rem;
    margin-block-end: 1.5rem;
}

.illus-toc-era-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.42rem 0.85rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    color: var(--rs-text, #f4efe6);
    background: var(--rs-surface, rgba(255, 255, 255, 0.04));
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.25));
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.illus-toc-era-pill:hover,
.illus-toc-era-pill:focus-visible {
    border-color: var(--rs-primary, #e3b27c);
    background: rgba(227, 178, 124, 0.14);
    color: var(--rs-primary, #e3b27c);
    outline: 2px solid transparent;
}

.illus-toc-era-pill-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-inline-size: 1.4rem;
    padding-inline: 0.38rem;
    border-radius: 999px;
    font-size: 0.74rem;
    background: rgba(227, 178, 124, 0.2);
    color: var(--rs-primary, #e3b27c);
}

/* Collapsible `<details>` Era container */
.illus-toc-era-section {
    margin-block-end: 1.75rem;
    border-radius: 0.85rem;
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.2));
    background: var(--rs-surface, rgba(255, 255, 255, 0.02));
    overflow: clip;
    scroll-margin-block-start: 1.5rem;
}

/* `content-visibility: auto` skips browser layout/paint on off-screen Era blocks until scrolled nearby,
   using `--era-intrinsic-size` as a placeholder height so the scrollbar thumb stays smooth! */
.illus-toc-era-deferred {
    content-visibility: auto;
    contain-intrinsic-size: auto none auto var(--era-intrinsic-size, 420px);
}

.illus-toc-era-pill[aria-current="true"] {
    border-color: var(--rs-primary, #e3b27c);
    background: rgba(227, 178, 124, 0.18);
    color: var(--rs-primary, #e3b27c);
}

.illus-toc-era-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    cursor: pointer;
    user-select: none;
    background: var(--rs-bg, rgba(227, 178, 124, 0.07));
    border-block-end: 1px solid var(--rs-border, rgba(227, 178, 124, 0.16));
    list-style: none;
}

.illus-toc-era-summary::-webkit-details-marker {
    display: none;
}

.illus-toc-era-heading {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--rs-text, #f4efe6);
}

.illus-toc-era-badge {
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    background: rgba(227, 178, 124, 0.18);
    color: var(--rs-primary, #e3b27c);
}

/* Responsive CSS Grid card gallery (`minmax(min(100%, 285px), 1fr)` prevents overflow on narrow 320px phones!) */
.illus-toc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 285px), 1fr));
    gap: 1.25rem;
    padding: 1.25rem;
    align-items: stretch;
}

/* 3-row internal Grid (`auto` media, `1fr` flexible body, `auto` footer) keeps footers flush at the bottom */
.illus-toc-card {
    display: grid;
    grid-template-rows: auto 1fr auto;
    gap: 0;
    border-radius: 0.75rem;
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.22));
    background: var(--rs-bg, rgba(18, 14, 16, 0.75));
    overflow: clip;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.illus-toc-card:hover,
.illus-toc-card:focus-within {
    border-color: var(--rs-primary, #e3b27c);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.illus-toc-media-wrap {
    position: relative;
    background: rgba(0, 0, 0, 0.35);
    border-block-end: 1px solid var(--rs-border, rgba(227, 178, 124, 0.16));
    overflow: clip;
    aspect-ratio: 16 / 9;
}

.illus-toc-media-link {
    display: block;
    inline-size: 100%;
    block-size: 100%;
    text-decoration: none;
}

/* `display: block` removes the tiny inline baseline gap under `<img>`; `aspect-ratio: 16 / 9` prevents CLS */
.illus-toc-thumb {
    display: block;
    inline-size: 100%;
    block-size: 100%;
    aspect-ratio: 16 / 9;
    object-fit: cover;
    transition: transform 0.25s ease;
}

@media (prefers-reduced-motion: no-preference) {
    .illus-toc-card:hover .illus-toc-thumb {
        transform: scale(1.025);
    }
}

.illus-toc-num-badge {
    position: absolute;
    inset-block-start: 0.6rem;
    inset-inline-start: 0.6rem;
    padding: 0.2rem 0.55rem;
    border-radius: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    background: rgba(15, 12, 14, 0.84);
    color: #f8e3c8;
    border: 1px solid rgba(227, 178, 124, 0.4);
    backdrop-filter: blur(4px);
}

.illus-toc-zoom-btn {
    position: absolute;
    inset-block-end: 0.6rem;
    inset-inline-end: 0.6rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.6rem;
    border-radius: 0.45rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(15, 12, 14, 0.84);
    color: #ffffff;
    border: 1px solid rgba(227, 178, 124, 0.45);
    cursor: pointer;
    backdrop-filter: blur(4px);
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.illus-toc-zoom-btn:hover,
.illus-toc-zoom-btn:focus-visible {
    background: var(--rs-primary, #e3b27c);
    color: var(--rs-on-primary, #1a1215);
    border-color: #ffffff;
    outline: 2px solid transparent;
}

.illus-toc-card-body {
    padding: 0.95rem 1.05rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.illus-toc-chap-eyebrow {
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rs-primary, #e3b27c);
}

.illus-toc-part-title {
    margin: 0;
    font-size: 1.02rem;
    font-weight: 700;
    line-height: 1.35;
}

.illus-toc-part-title a {
    color: var(--rs-text, #f4efe6);
    text-decoration: none;
}

.illus-toc-part-title a:hover,
.illus-toc-part-title a:focus-visible {
    color: var(--rs-primary, #e3b27c);
    text-decoration: underline;
}

.illus-toc-meta-line {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    font-size: 0.76rem;
    color: var(--rs-text-muted);
}

/* Clamp long alt-text captions neatly to 3 lines (`line-clamp: 3`) with an automatic ellipsis */
.illus-toc-caption {
    margin: 0.25rem 0 0;
    font-size: 0.83rem;
    line-height: 1.48;
    color: var(--rs-text-muted);
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    overflow: clip;
}

.illus-toc-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.7rem 1.05rem;
    border-block-start: 1px solid var(--rs-border, rgba(227, 178, 124, 0.14));
    background: var(--rs-surface, rgba(255, 255, 255, 0.02));
}

.illus-toc-read-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--rs-primary, #e3b27c);
    text-decoration: none;
}

.illus-toc-read-link:hover,
.illus-toc-read-link:focus-visible {
    text-decoration: underline;
}
</style>

<!-- STEP 3: RENDER SEMANTIC GALLERY HTML -->
<section class="illus-toc-wrapper" aria-label="Table of Illustrations for <?= htmlspecialchars($activeSeriesTitle) ?>">
    <?php if ($totalIllustrationsCount === 0): ?>
        <!-- Empty-state banner for text-only novels with 0 published scene plates -->
        <div class="illus-toc-summary-bar" role="status">
            <div class="illus-toc-stats">
                <span class="illus-toc-stat-pill">
                    <i class="ph ph-image" aria-hidden="true"></i>
                    <span>No scene illustrations have been published for <strong><?= htmlspecialchars($activeSeriesTitle) ?></strong> yet.</span>
                </span>
            </div>
            <a href="/<?= htmlspecialchars($activeSeriesSlug) ?>/toc" class="illus-toc-read-link">
                <i class="ph ph-list-bullets" aria-hidden="true"></i>
                <span>Return to Table of Contents</span>
            </a>
        </div>
    <?php else: ?>
        <!-- Summary Metrics & Cross-Navigation Action Bar -->
        <div class="illus-toc-summary-bar">
            <div class="illus-toc-stats">
                <span class="illus-toc-stat-pill">
                    <i class="ph ph-images" aria-hidden="true"></i>
                    <span><?= intval($totalIllustrationsCount) ?> <?= $totalIllustrationsCount === 1 ? 'Illustration' : 'Illustrations' ?></span>
                </span>
                <span class="illus-toc-stat-pill">
                    <i class="ph ph-book-open-text" aria-hidden="true"></i>
                    <span><?= intval($totalIllustratedPartsCount) ?> Illustrated <?= $totalIllustratedPartsCount === 1 ? 'Scene' : 'Scenes' ?></span>
                </span>
                <span class="illus-toc-stat-pill">
                    <i class="ph ph-folders" aria-hidden="true"></i>
                    <span><?= count($illustratedEras) ?> <?= count($illustratedEras) === 1 ? 'Era' : 'Eras' ?></span>
                </span>
            </div>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.85rem;">
                <a href="/illustrations" class="illus-toc-read-link" style="opacity: 0.92;">
                    <i class="ph ph-books" aria-hidden="true"></i>
                    <span>All-Novel Illustrations Index</span>
                </a>
                <?php if ($illustrationsTocMode === 'embedded'): ?>
                    <a href="/<?= htmlspecialchars($activeSeriesSlug) ?>/illustrations" class="illus-toc-read-link">
                        <i class="ph ph-arrows-out" aria-hidden="true"></i>
                        <span>Open Dedicated Illustrations Gallery &rarr;</span>
                    </a>
                <?php else: ?>
                    <a href="/<?= htmlspecialchars($activeSeriesSlug) ?>/toc" class="illus-toc-read-link">
                        <i class="ph ph-list-bullets" aria-hidden="true"></i>
                        <span>View Full Chapter Table of Contents &rarr;</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Instant Search Input (shown whenever the book has > 8 illustrations) -->
        <?php if ($totalIllustrationsCount > 8): ?>
            <div style="margin-block-end: 1rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                <div style="position: relative; flex: 1; min-width: 240px;">
                    <i class="ph ph-magnifying-glass" aria-hidden="true" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--rs-text-muted);"></i>
                    <input
                        type="search"
                        id="illus-toc-filter-input"
                        placeholder="Filter <?= intval($totalIllustrationsCount) ?> illustrations in <?= htmlspecialchars($activeSeriesTitle) ?> by scene, date, location, or caption..."
                        aria-label="Filter illustrations in <?= htmlspecialchars($activeSeriesTitle) ?>"
                        style="width: 100%; padding: 0.6rem 0.9rem 0.6rem 2.35rem; border-radius: 0.6rem; border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.28)); background: var(--rs-surface, rgba(255, 255, 255, 0.04)); color: var(--rs-text, #f4efe6); font-size: 0.88rem;"
                    >
                </div>
                <!-- `aria-live="polite"` announces `"12 matches"` to screen readers as the user types -->
                <span id="illus-toc-filter-status" style="font-size: 0.82rem; font-weight: 600; color: var(--rs-text-muted);" aria-live="polite"></span>
            </div>
        <?php endif; ?>

        <!-- Era Quick-Jump Filter Pills (`data-no-spa="true"` lets our custom scroll script handle them) -->
        <?php if (count($illustratedEras) > 1): ?>
            <nav class="illus-toc-era-pills" aria-label="Jump to Era Illustrations">
                <?php foreach ($illustratedEras as $eraGroup): ?>
                    <a href="#<?= htmlspecialchars($eraGroup['era_anchor']) ?>"
                       class="illus-toc-era-pill"
                       data-no-spa="true"
                       data-era-target="<?= htmlspecialchars($eraGroup['era_anchor']) ?>">
                        <span><?= htmlspecialchars($eraGroup['era_title']) ?></span>
                        <span class="illus-toc-era-pill-count"><?= count($eraGroup['items']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <!-- Per-Era Collapsible Illustration Grids (`<details open><summary>...</summary>`) -->
        <?php foreach ($illustratedEras as $eraGalleryIndex => $eraGroup): ?>
            <?php
            // Core Web Vitals optimization: keep Era 0 (`$eraGalleryIndex === 0`) immediately rendered
            // for fast Largest Contentful Paint (LCP), and defer off-screen Eras (`> 0`) via `content-visibility: auto`.
            $deferredClass = ($eraGalleryIndex > 0) ? ' illus-toc-era-deferred' : '';
            $estimatedRows = max(1, (int)ceil(count($eraGroup['items']) / 2));
            $estimatedHeightPx = 84 + ($estimatedRows * 410);
            ?>
            <details class="illus-toc-era-section<?= $deferredClass ?>"
                     id="<?= htmlspecialchars($eraGroup['era_anchor']) ?>"
                     style="--era-intrinsic-size: <?= $estimatedHeightPx ?>px;"
                     open>
                <summary class="illus-toc-era-summary">
                    <h2 class="illus-toc-era-heading">
                        <i class="ph ph-image" aria-hidden="true" style="color: var(--rs-primary);"></i>
                        <span><?= htmlspecialchars($eraGroup['era_title']) ?></span>
                    </h2>
                    <span class="illus-toc-era-badge">
                        <?= count($eraGroup['items']) ?> <?= count($eraGroup['items']) === 1 ? 'Illustration' : 'Illustrations' ?>
                    </span>
                </summary>

                <div class="illus-toc-grid">
                    <?php foreach ($eraGroup['items'] as $item): ?>
                        <?php
                        // Load the first 4 visible thumbnails eagerly (`loading="eager"`) for instant LCP,
                        // and lazy-load (`loading="lazy"`) all lower thumbnails as the user scrolls!
                        $isAboveFoldCard = ($eraGalleryIndex === 0 && $item['number'] <= 4);
                        $loadingAttr = $isAboveFoldCard ? 'eager' : 'lazy';
                        // Pre-compute a lowercase searchable text string stored in `data-illus-search`
                        $cardSearchHaystack = strtolower(
                            $eraGroup['era_title'] . ' ' .
                            $item['chap_title'] . ' ' .
                            $item['part_title'] . ' ' .
                            $item['date'] . ' ' .
                            $item['location'] . ' ' .
                            $item['caption'] . ' ' .
                            $item['alt']
                        );
                        ?>
                        <article class="illus-toc-card" data-illus-search="<?= htmlspecialchars($cardSearchHaystack) ?>">
                            <!-- Row 1: 16:9 Thumbnail Header + Number Badge + Lightbox "Inspect" Button -->
                            <div class="illus-toc-media-wrap">
                                <a href="<?= htmlspecialchars($item['part_url']) ?>" class="illus-toc-media-link" aria-label="Read <?= htmlspecialchars($item['part_title']) ?>">
                                    <img
                                        src="<?= htmlspecialchars($item['src']) ?>"
                                        alt="<?= htmlspecialchars($item['alt']) ?>"
                                        width="640"
                                        height="360"
                                        loading="<?= $loadingAttr ?>"
                                        decoding="async"
                                        class="illus-toc-thumb"
                                    >
                                </a>
                                <span class="illus-toc-num-badge" title="Illustration #<?= intval($item['number']) ?> in <?= htmlspecialchars($activeSeriesTitle) ?>">
                                    #<?= intval($item['number']) ?><?php if ($item['img_total'] > 1): ?> (<?= intval($item['img_index']) ?>/<?= intval($item['img_total']) ?>)<?php endif; ?>
                                </span>
                                <button
                                    type="button"
                                    class="illus-toc-zoom-btn"
                                    data-illus-src="<?= htmlspecialchars($item['src']) ?>"
                                    data-illus-title="<?= htmlspecialchars($item['part_title']) ?>"
                                    data-illus-chap="<?= htmlspecialchars($item['era_title'] . ' • ' . $item['chap_title']) ?>"
                                    data-illus-caption="<?= htmlspecialchars($item['alt']) ?>"
                                    data-illus-url="<?= htmlspecialchars($item['part_url']) ?>"
                                    aria-label="Inspect full-size illustration for <?= htmlspecialchars($item['part_title']) ?>"
                                >
                                    <i class="ph ph-magnifying-glass-plus" aria-hidden="true"></i>
                                    <span>Inspect</span>
                                </button>
                            </div>

                            <!-- Row 2: Card Body (Chapter Eyebrow, Part Title, Date/Location, Caption) -->
                            <div class="illus-toc-card-body">
                                <div class="illus-toc-chap-eyebrow">
                                    <?= htmlspecialchars($item['chap_title']) ?>
                                </div>
                                <h3 class="illus-toc-part-title">
                                    <a href="<?= htmlspecialchars($item['part_url']) ?>">
                                        <?= htmlspecialchars($item['part_title']) ?>
                                    </a>
                                </h3>
                                <?php if ($item['date'] !== '' || $item['location'] !== ''): ?>
                                    <div class="illus-toc-meta-line">
                                        <?php if ($item['date'] !== ''): ?>
                                            <span><i class="ph ph-calendar-blank" aria-hidden="true"></i> <?= htmlspecialchars($item['date']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['location'] !== ''): ?>
                                            <span><i class="ph ph-map-pin" aria-hidden="true"></i> <?= htmlspecialchars($item['location']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($item['caption'] !== ''): ?>
                                    <p class="illus-toc-caption" title="<?= htmlspecialchars($item['caption']) ?>">
                                        <?= htmlspecialchars($item['caption']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Row 3: Card Footer CTA -->
                            <div class="illus-toc-card-footer">
                                <a href="<?= htmlspecialchars($item['part_url']) ?>" class="illus-toc-read-link">
                                    <span>Read Scene</span>
                                    <i class="ph ph-arrow-right" aria-hidden="true"></i>
                                </a>
                                <a href="<?= htmlspecialchars($item['chap_url']) ?>" class="illus-toc-meta-line" style="text-decoration: none;">
                                    <span>Chapter <?= intval($item['chap_num']) ?></span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>

        <!-- STEP 4: INCLUDE SHARED NATIVE `<dialog>` LIGHTBOX MODAL -->
        <?php require __DIR__ . '/illustration-lightbox-dialog.php'; ?>

        <!-- STEP 5: ERA QUICK-JUMP SCROLLING & INSTANT KEYWORD FILTER SCRIPT -->
        <script>
        (function() {
            const eraSections = Array.from(document.querySelectorAll('.illus-toc-era-section'));
            const eraPills = Array.from(document.querySelectorAll('.illus-toc-era-pill'));

            /**
             * Smoothly scrolls to `#illus-era-<N>` with pixel-perfect accuracy!
             * Why unlock `content-visibility: visible` on preceding sections first?
             *   While `content-visibility: auto` is active, off-screen Era blocks use an
             *   estimated height (`--era-intrinsic-size`). If we scrolled across 15 deferred
             *   Eras without unlocking them first, their heights would expand mid-scroll and
             *   cause `scrollIntoView` to land above or below the target Era!
             */
            function scrollToEraSection(targetId, updateHistory) {
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;

                // 1. Unlock `content-visibility` and open `<details>` on all sections up to `targetEl`
                for (let i = 0; i < eraSections.length; i++) {
                    const sec = eraSections[i];
                    sec.open = true;
                    sec.classList.remove('illus-toc-era-deferred');
                    sec.style.contentVisibility = 'visible';
                    if (sec === targetEl) break;
                }

                // 2. Highlight the clicked Era pill (`aria-current="true"`)
                eraPills.forEach(function(pill) {
                    pill.setAttribute('aria-current', pill.getAttribute('data-era-target') === targetId ? 'true' : 'false');
                });

                // 3. Force the browser to recalculate real layout heights synchronously before scrolling
                void targetEl.getBoundingClientRect();
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // 4. 420ms verification pass in case any image decoded during the smooth scroll animation
                setTimeout(function() {
                    const rect = targetEl.getBoundingClientRect();
                    if (Math.abs(rect.top) > 120) {
                        targetEl.scrollIntoView({ behavior: 'instant', block: 'start' });
                    }
                }, 420);

                if (updateHistory && history.replaceState) {
                    history.replaceState(null, '', '#' + targetId);
                }
            }

            eraPills.forEach(function(pill) {
                pill.addEventListener('click', function(e) {
                    const targetId = pill.getAttribute('data-era-target') || (pill.getAttribute('href') || '').replace(/^#/, '');
                    if (!targetId) return;
                    e.preventDefault();
                    e.stopPropagation();
                    scrollToEraSection(targetId, true);
                });
            });

            // Auto-scroll if the visitor arrived via a deep link like `/rachel/illustrations#illus-era-12`
            if (window.location.hash && window.location.hash.indexOf('#illus-era-') === 0) {
                const initialId = window.location.hash.substring(1);
                setTimeout(function() {
                    scrollToEraSection(initialId, false);
                }, 60);
            }

            // Instant client-side keyword search across `data-illus-search` on every card
            const filterInput = document.getElementById('illus-toc-filter-input');
            const filterStatus = document.getElementById('illus-toc-filter-status');
            if (filterInput) {
                filterInput.addEventListener('input', function() {
                    const q = (filterInput.value || '').trim().toLowerCase();
                    let matchedCount = 0;
                    eraSections.forEach(function(sec) {
                        sec.classList.remove('illus-toc-era-deferred');
                        sec.style.contentVisibility = 'visible';
                        const cards = Array.from(sec.querySelectorAll('.illus-toc-card'));
                        let eraMatches = 0;
                        cards.forEach(function(card) {
                            const hay = card.getAttribute('data-illus-search') || '';
                            const isMatch = (q === '' || hay.indexOf(q) !== -1);
                            card.style.display = isMatch ? '' : 'none';
                            if (isMatch) {
                                eraMatches++;
                                matchedCount++;
                            }
                        });
                        // Hide entire Era `<details>` blocks that have 0 matching cards
                        sec.style.display = eraMatches > 0 ? '' : 'none';
                        if (eraMatches > 0) sec.open = true;
                    });
                    if (filterStatus) {
                        filterStatus.textContent = q === ''
                            ? ''
                            : (matchedCount + ' ' + (matchedCount === 1 ? 'match' : 'matches'));
                    }
                });
            }
        })();
        </script>
    <?php endif; ?>
</section>
