<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/sidebar.php
 * =============================================================================
 * WHAT COMPONENT THIS FILE RENDERS:
 *     This file renders `<aside id="stardust-sidebar" class="reader-sidebar">`
 *     — the persistent left-hand navigation drawer (permanent sidebar on desktop,
 *     slide-out drawer with `#reader-sidebar-backdrop` on phones/tablets) shown
 *     across every book route (`/<series>`, `/<series>/toc`, `/<series>/...`).
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php` & `sophia/oliver.php`:
 *     1. Whenever `$hasValidBookSeries === true`, `eleanor/isabel.php` includes
 *        `sophia/sidebar.php` *immediately before* `sophia/oliver.php`!
 *     2. Because `sidebar.php` runs first in the same PHP scope, the reverse
 *        lookup dictionary `$fileToUrl` created right here (mapping physical
 *        Markdown paths like `e001/c001/p001.md` -> clean URLs like `/rachel/...`)
 *        becomes available to `oliver.php`, `landing.php`, `toc.php`,
 *        `era-toc.php`, `chap-toc.php`, and `illustrations.php`!
 *     3. `sidebar.php` also calculates sequential `$sbPrevUrl` and `$sbNextUrl`
 *        buttons—filtering out non-story index pages, respecting Story Crossroads
 *        branches (`$activeSegmentIdSet`), and rewiring Prev/Next to follow
 *        Story Arc playlists whenever `?arc=<slug>` is active!
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     1. Co-branded RaggieSoft Book Reader + Ocean View Archives home badge.
 *     2. Current Book Series title and clamped 1-line active chapter subtitle.
 *     3. 4-button Quick Access Toolbar: `Home`, `Library`, `Save` (`#reader-bookmark-btn`,
 *        wired by `reader.js` to toggle bookmarks in `localStorage['rs-bookmarks']`),
 *        and `Settings` (`/settings`).
 *     4. Client-Side Instant Search (`#rs-book-search`), which lazily fetches
 *        `search-index.json` via `async/await fetch()` only when focused!
 *     5. Contextual Book Links: Series Overview, Table of Contents, Table of
 *        Illustrations (with count pill), Story Arcs, and Story Crossroads switcher.
 *     6. Side-by-side `Prev Part` / `Next Part` buttons + Offline EPUB/PDF download strip.
 * =============================================================================
 */

// Import shared routing, manifest, frontmatter, and eBook size variables from `eleanor/isabel.php`.
global $seriesSlug, $routeData, $cdnBaseUrl, $requestUri, $toc_manifest, $pageConfig, $fmTitle, $ebookSizes;

// Step 1: Normalize `$requestUri` by stripping any trailing slash (except root `'/'`)
// so lookups against `$routeData` keys always match cleanly.
$normalizedRequestUri = rtrim($requestUri, '/');
if ($normalizedRequestUri === '') {
    $normalizedRequestUri = '/';
}

// Step 2: Build `$fileToUrl` — a reverse hash map (`filePath => cleanUrl`).
// `$routeData` (loaded from `data/routes/<series>.json`) is keyed by URL (`/rachel/... => ['filePath' => 'e001/...']`),
// so inverting it once in `O(N)` time lets `toc.php` / `era-toc.php` / `chap-toc.php` turn file paths into URLs in `O(1)` time!
$fileToUrl = [];
if (!empty($routeData)) {
    foreach ($routeData as $url => $data) {
        if ($url === 'common') continue; // Skip the shared series metadata block
        if (isset($data['filePath'])) {
            $fileToUrl[$data['filePath']] = $url;
        }
    }
}

// Step 3: Initialize sidebar Previous / Next / Up navigation variables.
$sbPrevUrl = null;
$sbNextUrl = null;
$routeConfig = [];

// Upward navigation always returns to the current book's Series Overview (`/<seriesSlug>`).
$segments = explode('/', trim($normalizedRequestUri, '/'));
$sbUpUrl = '/' . $seriesSlug; 
$sbUpLabel = "Up to Series";

// Step 4: Dynamically compute sequential "Prev Part" and "Next Part" URLs across the active reading path.
if (!empty($routeData)) {
    $routeConfig = $routeData[$normalizedRequestUri] ?? ($routeData[$requestUri] ?? []);
    
    // Honor explicit `prevUrl` / `nextUrl` overrides if defined in `data/routes/<series>.json`
    $sbPrevUrl = $routeConfig['prevUrl'] ?? null;
    $sbNextUrl = $routeConfig['nextUrl'] ?? null;
    
    // Grab all route keys for this book series
    $sbRouteKeys = array_keys($routeData);
    $sbSeriesFilter = '/' . ($seriesSlug ?? '');
    
    // Check whether the reader is currently viewing a real narrative Markdown scene (not a TOC/Landing page)
    $currentFilePath = (string)($routeConfig['filePath'] ?? '');
    $isReadingScene = (
        $currentFilePath !== '' &&
        $currentFilePath !== '__SERIES_LANDING__' &&
        $currentFilePath !== '__TOC__' &&
        $currentFilePath !== '__ILLUSTRATIONS__' &&
        strpos($currentFilePath, '__ERA_TOC__|') !== 0 &&
        strpos($currentFilePath, '__CHAP_TOC__|') !== 0
    );

    // Filter `$sbRouteKeys` down to ONLY sequential chapters along the reader's active Story Crossroads path:
    //   1. Exclude `'common'` metadata and the root landing page `'/<series>'`.
    //   2. Exclude scenes belonging to unchosen Story Crossroads branches (`$candidatePathId` not in `$activeSegmentIdSet`).
    //   3. When reading a story scene, exclude intermediate TOC/Gallery pages so Prev/Next steps smoothly from story scene to story scene!
    $sbFilteredKeys = array_filter($sbRouteKeys, function($k) use ($sbSeriesFilter, $routeData, $isReadingScene, $activeStoryPathId, $activeSegmentIdSet) { 
        if ($k === 'common' || $k === $sbSeriesFilter) {
            return false;
        }
        $candidatePathId = (string)($routeData[$k]['pathId'] ?? '');
        if ($candidatePathId !== '' && empty($activeSegmentIdSet[$candidatePathId]) && $candidatePathId !== (string)$activeStoryPathId) {
            return false;
        }
        if ($isReadingScene) {
            $fp = (string)($routeData[$k]['filePath'] ?? '');
            if (
                $fp === '__SERIES_LANDING__' ||
                $fp === '__TOC__' ||
                $fp === '__ILLUSTRATIONS__' ||
                strpos($fp, '__ERA_TOC__|') === 0 ||
                strpos($fp, '__CHAP_TOC__|') === 0
            ) {
                return false;
            }
        }
        return true; 
    });
    
    // `array_filter()` preserves old array keys (leaving gaps like 0, 2, 5), so `array_values()`
    // re-indexes the list cleanly to `0, 1, 2, ...` before we look up `$sbCurrentIndex - 1` and `+ 1`!
    $sbFilteredKeys = array_values($sbFilteredKeys);
    
    // Locate the current URL in the filtered sequence and assign Prev/Next (unless halted by a Story Crossroads gate)
    $sbCurrentIndex = array_search($normalizedRequestUri, $sbFilteredKeys);
    if ($sbCurrentIndex !== false) {
        if ($sbCurrentIndex > 0 && empty($sbPrevUrl) && empty($routeConfig['noPrev'])) {
            $sbPrevUrl = $sbFilteredKeys[$sbCurrentIndex - 1];
        }
        if ($sbCurrentIndex < count($sbFilteredKeys) - 1 && empty($sbNextUrl) && empty($routeConfig['noNext']) && empty($routeConfig['crossroads'])) {
            $sbNextUrl = $sbFilteredKeys[$sbCurrentIndex + 1];
        }
    }
}

// Step 5: Story Arc Playlist Mode Override (`?arc=<slug>`).
// If the reader is following a curated Story Arc playlist, override `$sbPrevUrl` and `$sbNextUrl`
// so the sidebar buttons step through the playlist's `'part'` stops ("Prev in Arc" / "Next in Arc")!
$sbStoryArcs = is_array($toc_manifest['story_arcs'] ?? null)
    ? $toc_manifest['story_arcs']
    : (is_array($routeData['common']['story_arcs'] ?? null) ? $routeData['common']['story_arcs'] : []);
$sbActiveArc = null;
$sbPrevLabel = 'Prev Part';
$sbNextLabel = 'Next Part';
if (!empty($sbStoryArcs) && !empty($_GET['arc']) && !empty($currentFilePath) && $isReadingScene) {
    $requestedSbArcSlug = preg_replace('/[^a-z0-9_-]/i', '', (string)$_GET['arc']);
    foreach ($sbStoryArcs as $candidateArc) {
        if (($candidateArc['slug'] ?? '') !== $requestedSbArcSlug) {
            continue;
        }
        // Keep only `'part'` stops (chapters) when wiring Previous/Next chapter links
        $arcPartStops = array_values(array_filter(
            $candidateArc['stops'] ?? [],
            static fn($st) => ($st['kind'] ?? '') === 'part'
        ));
        foreach ($arcPartStops as $idx => $pStop) {
            if (($pStop['file_path'] ?? '') === $currentFilePath) {
                $sbActiveArc = $candidateArc;
                $sbPrevUrl   = ($idx > 0) ? ($arcPartStops[$idx - 1]['arc_url'] ?? null) : null;
                $sbNextUrl   = ($idx < count($arcPartStops) - 1) ? ($arcPartStops[$idx + 1]['arc_url'] ?? null) : null;
                $sbPrevLabel = 'Prev in Arc';
                $sbNextLabel = 'Next in Arc';
                break 2; // Exit both nested `foreach` loops once matched
            }
        }
    }
}

// Step 6: Format the Series Title and active Part Subtitle for the compact sidebar header,
// stripping any redundant trailing `" - <Series Title>"` suffix via `preg_replace()`.
$sidebarSeriesTitle = $toc_manifest['series_title'] ?? (ucfirst($seriesSlug) . ' Narrative');
$sidebarPageTitle = $routeConfig['title'] ?? ($pageConfig['title'] ?? (!empty($fmTitle) ? $fmTitle : 'Navigation'));
$sidebarPageTitle = preg_replace('/\s*-\s*' . preg_quote($sidebarSeriesTitle, '/') . '$/i', '', (string)$sidebarPageTitle);
?>

<!-- SLIDE-OUT / DOCKED BOOK NAVIGATION SIDEBAR (`<aside>` landmark) -->
<aside id="stardust-sidebar" class="reader-sidebar">

    <!-- 1. Co-Branded Book Reader App + Ocean View Archives Imprint Badge (Links to `/`) -->
    <a href="/" style="display: flex; align-items: center; gap: 0.6rem; text-decoration: none; padding: 0.45rem 0.6rem; margin-bottom: 0.65rem; border-radius: 10px; background: var(--rs-surface); border: 1px solid var(--rs-border); box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
        <img src="/icons/icon-192.png?v=4" alt="RaggieSoft Book Reader App Icon" style="width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0; border: 1px solid rgba(227, 178, 124, 0.55); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
        <div style="display: flex; flex-direction: column; min-width: 0; text-align: left;">
            <span style="font-size: 0.84rem; font-weight: 800; color: var(--rs-heading); line-height: 1.18; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">RaggieSoft Book Reader</span>
            <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--rs-primary); margin-top: 0.08rem;">
                <img src="<?php echo htmlspecialchars($cdnBaseUrl); ?>/raggiesoft-books/images/logos/oceanview-archives.svg" alt="" aria-hidden="true" style="width: 11px; height: 11px; object-fit: contain;">
                <span>Ocean View Archives</span>
            </span>
        </div>
    </a>
    
    <!-- 2. Contextual Series Header (`text-overflow: ellipsis` keeps long chapter titles neatly on 1 line) -->
    <div class="sidebar-series-header" style="text-align: left; padding: 0 0.15rem; margin-bottom: 0.65rem;">
        <a href="<?php echo htmlspecialchars($sbUpUrl); ?>" style="text-decoration: none; display: block; margin: 0 0 0.15rem 0; color: var(--rs-primary); font-size: 1.05rem; font-weight: 800; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php 
            echo htmlspecialchars($sidebarSeriesTitle); 
        ?></a>
        <p title="<?php echo htmlspecialchars($sidebarPageTitle); ?>" style="margin: 0; color: var(--rs-text-muted); font-size: 0.78rem; font-weight: 600; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($sidebarPageTitle); ?></p>
    </div>

    <!-- 3. 4-Column Quick Access Toolbar: Home, Library, Bookmark (`#reader-bookmark-btn`), and Settings -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.35rem; margin-bottom: 0.65rem;">
        <a href="/" class="rs-btn rs-btn-brand" title="Book Reader Home" style="text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 700; border-radius: 7px;">
            <i class="ph ph-house" style="font-size: 1rem;"></i>
            <span>Home</span>
        </a>
        <a href="/library" class="rs-btn" title="Full Book Library" style="text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 600; border-radius: 7px;">
            <i class="ph ph-books" style="font-size: 1rem;"></i>
            <span>Library</span>
        </a>
        <!-- `#reader-bookmark-btn`: `reader.js` attaches a click listener here to save/remove the current URL in `localStorage['rs-bookmarks']` -->
        <button id="reader-bookmark-btn" class="rs-btn" title="Bookmark This Part" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 600; border-radius: 7px;">
            <i class="ph ph-bookmark" style="font-size: 1rem;"></i>
            <span>Save</span>
        </button>
        <a href="/settings" class="rs-btn" title="Reader Typography & Theme Settings" style="text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 600; border-radius: 7px;">
            <i class="ph ph-gear" style="font-size: 1rem;"></i>
            <span>Settings</span>
        </a>
    </div>

    <!-- 4. Compact Instant Search Bar (Dropdown `#rs-search-results` is positioned `top: 100%` right below the input) -->
    <form action="/search" method="GET" class="search-container" style="position: relative; margin-bottom: 0.65rem;">
        <input type="text" id="rs-book-search" name="q" placeholder="Search the universe..." style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 7px; border: 1px solid var(--rs-border); background: var(--rs-surface); color: var(--rs-text); font-size: 0.86rem;">
        <div id="rs-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 6px; max-height: 320px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-top: 4px;"></div>
    </form>

    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 0 0 0.5rem 0;">
    
    <!-- 5. Primary Contextual Book Navigation Links -->
    <nav class="toc-nav" style="display: flex; flex-direction: column; gap: 0.2rem;">
        <a href="<?php echo htmlspecialchars($sbUpUrl); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.6rem; text-decoration: none; padding: 0.45rem 0.55rem; border-radius: 6px; color: var(--rs-text); font-size: 0.88rem; font-weight: 600;">
            <i class="ph ph-book-open" style="font-size: 1.1rem;"></i> Series Overview
        </a>
        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.6rem; text-decoration: none; padding: 0.45rem 0.55rem; border-radius: 6px; color: var(--rs-text); font-size: 0.88rem; font-weight: 600;">
            <i class="ph ph-list" style="font-size: 1.1rem;"></i> Table of Contents
        </a>
        <?php
        $sbIllusCount = (int)($toc_manifest['illustration_count'] ?? ($routeData['common']['illustration_count'] ?? 0));
        ?>
        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/illustrations" class="sidebar-nav-link" style="display: flex; align-items: center; justify-content: space-between; gap: 0.6rem; text-decoration: none; padding: 0.45rem 0.55rem; border-radius: 6px; color: var(--rs-text); font-size: 0.88rem; font-weight: 600;">
            <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
                <i class="ph ph-images" style="font-size: 1.1rem;"></i> Table of Illustrations
            </span>
            <?php if ($sbIllusCount > 0): ?>
                <span style="font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary);"><?php echo $sbIllusCount; ?></span>
            <?php endif; ?>
        </a>
        <?php if (!empty($sbStoryArcs)): ?>
        <!-- Shown automatically whenever `arcs.json` defines 1+ Story Arcs for this novel -->
        <a href="/arcs?book=<?php echo htmlspecialchars($seriesSlug); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; justify-content: space-between; gap: 0.6rem; text-decoration: none; padding: 0.45rem 0.55rem; border-radius: 6px; color: var(--rs-text); font-size: 0.88rem; font-weight: 600;">
            <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
                <i class="ph ph-playlist" style="font-size: 1.1rem;"></i> Story Arcs
            </span>
            <span style="font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary);"><?php echo count($sbStoryArcs); ?></span>
        </a>
        <?php endif; ?>
        <?php
        // Shown automatically whenever `crossroads.json` defines Story Crossroads branching routes for this novel.
        // `data-open-crossroads-if-present="true"` tells `reader.js` to pop open `#rs-story-crossroads-dialog`
        // right on the current chapter if the dialog is mounted in `oliver.php`, or fall back to `/<series>/toc#rs-toc-crossroads`.
        $sbCrossroads = is_array($toc_manifest['crossroads'] ?? null) ? $toc_manifest['crossroads'] : [];
        if (!empty($sbCrossroads)):
            $sbHasActivePath = !empty($activeStoryPathId) && !empty($activeStoryPathObj['label']);
        ?>
        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc#rs-toc-crossroads" data-open-crossroads-if-present="true" class="sidebar-nav-link" title="<?php echo $sbHasActivePath ? ('Active Story Path: ' . htmlspecialchars((string)$activeStoryPathObj['label']) . ' — Click to switch Story Path') : 'Choose a Story Path at the Story Crossroads'; ?>" style="display: flex; align-items: center; justify-content: space-between; gap: 0.6rem; text-decoration: none; padding: 0.45rem 0.55rem; border-radius: 6px; color: var(--rs-text); font-size: 0.88rem; font-weight: 600;">
            <span style="display: inline-flex; align-items: center; gap: 0.6rem; min-width: 0;">
                <i class="ph ph-signpost" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $sbHasActivePath ? 'Switch Story Path' : 'Story Crossroads'; ?></span>
            </span>
            <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary); flex-shrink: 0;"><?php echo $sbHasActivePath ? 'Active' : count($sbCrossroads[0]['options'] ?? []) . ' Paths'; ?></span>
        </a>
        <?php endif; ?>
        
        <!-- 6. Side-by-Side Previous / Next Part Flipper (Disabled `<span>` when at first/last chapter or Crossroads gate) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; margin-top: 0.45rem; padding-top: 0.5rem; border-top: 1px solid var(--rs-border);">
            <?php if ($sbPrevUrl): ?>
                <a href="<?php echo htmlspecialchars($sbPrevUrl); ?>" class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; text-decoration: none; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); font-size: 0.8rem; font-weight: 600;">
                    <i class="ph ph-caret-left" style="font-size: 0.95rem;"></i> <?php echo htmlspecialchars($sbPrevLabel); ?>
                </a>
            <?php else: ?>
                <span class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); opacity: 0.4; cursor: not-allowed; font-size: 0.8rem; font-weight: 600;">
                    <i class="ph ph-caret-left" style="font-size: 0.95rem;"></i> <?php echo htmlspecialchars($sbPrevLabel); ?>
                </span>
            <?php endif; ?>

            <?php if ($sbNextUrl): ?>
                <a href="<?php echo htmlspecialchars($sbNextUrl); ?>" class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; text-decoration: none; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); font-size: 0.8rem; font-weight: 600;">
                    <?php echo htmlspecialchars($sbNextLabel); ?> <i class="ph ph-caret-right" style="font-size: 0.95rem;"></i>
                </a>
            <?php else: ?>
                <span class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); opacity: 0.4; cursor: not-allowed; font-size: 0.8rem; font-weight: 600;">
                    <?php echo htmlspecialchars($sbNextLabel); ?> <i class="ph ph-caret-right" style="font-size: 0.95rem;"></i>
                </span>
            <?php endif; ?>
        </div>
    </nav>

    <!-- 7. Compact Offline E-Book Editions Strip (Full Illustrated EPUB/PDF + Lightweight Text-Only EPUB/PDF) -->
    <?php if (!empty($seriesSlug) && !empty($toc_manifest)):
        $sbSizes = !empty($ebookSizes) && is_array($ebookSizes)
            ? $ebookSizes
            : ($routeData['common']['ebook_sizes'] ?? ($toc_manifest['ebook_sizes'] ?? []));
        $sbEpubSize     = $sbSizes['epub'] ?? '';
        $sbPdfSize      = $sbSizes['pdf'] ?? '';
        $sbTextEpubSize = $sbSizes['text_epub'] ?? '';
        $sbTextPdfSize  = $sbSizes['text_pdf'] ?? '';
    ?>
    <div style="margin-top: 0.65rem; padding: 0.5rem 0.6rem; border-radius: 8px; background: var(--rs-surface); border: 1px solid var(--rs-border); display: flex; flex-direction: column; gap: 0.35rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.35rem;">
            <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--rs-text-muted);"><i class="ph ph-download-simple"></i> E-Book</span>
            <div style="display: inline-flex; gap: 0.35rem;">
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.epub"); ?>" target="_blank" title="Download Illustrated EPUB<?php echo $sbEpubSize ? ' (' . htmlspecialchars($sbEpubSize) . ')' : ''; ?>" style="text-decoration: none; font-size: 0.74rem; font-weight: 700; padding: 0.18rem 0.48rem; border-radius: 5px; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-heading);">
                    EPUB<?php echo $sbEpubSize ? ' <span style="color: var(--rs-text-muted); font-weight: 500;">(' . htmlspecialchars($sbEpubSize) . ')</span>' : ''; ?>
                </a>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.pdf"); ?>" target="_blank" title="Download Illustrated PDF<?php echo $sbPdfSize ? ' (' . htmlspecialchars($sbPdfSize) . ')' : ''; ?>" style="text-decoration: none; font-size: 0.74rem; font-weight: 700; padding: 0.18rem 0.48rem; border-radius: 5px; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-heading);">
                    PDF<?php echo $sbPdfSize ? ' <span style="color: var(--rs-text-muted); font-weight: 500;">(' . htmlspecialchars($sbPdfSize) . ')</span>' : ''; ?>
                </a>
            </div>
        </div>
        <div style="font-size: 0.68rem; color: var(--rs-text-muted); display: flex; align-items: center; justify-content: space-between; gap: 0.3rem; border-top: 1px dashed var(--rs-border); padding-top: 0.28rem;">
            <span>Text-Only:</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.epub"); ?>" target="_blank" style="color: var(--rs-primary); font-weight: 600; text-decoration: none;">EPUB<?php echo $sbTextEpubSize ? ' (' . htmlspecialchars($sbTextEpubSize) . ')' : ''; ?></a>
                <span aria-hidden="true" style="color: var(--rs-text-muted);">&bull;</span>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.pdf"); ?>" target="_blank" style="color: var(--rs-primary); font-weight: 600; text-decoration: none;">PDF<?php echo $sbTextPdfSize ? ' (' . htmlspecialchars($sbTextPdfSize) . ')' : ''; ?></a>
            </span>
        </div>
    </div>
    <?php endif; ?>
</aside>

<!-- Hover Highlight Rule for Sidebar Navigation Rows -->
<style>
    .sidebar-nav-link:hover {
        background: var(--rs-bg-alt);
        color: var(--rs-primary) !important;
    }
</style>

<!-- Mobile Backdrop: Semi-transparent overlay toggled by `reader.js` when the off-canvas sidebar opens on phones/tablets -->
<div id="reader-sidebar-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px);"></div>

<!-- 
  CLIENT-SIDE INSTANT FULL-TEXT SEARCH (`#rs-book-search`):
  100-Level JavaScript Walkthrough:
  - Instead of downloading a large search index on every single page load, we wait
    until the user actually clicks (`focus` event) inside the search box ("lazy loading")!
  - `async / await` lets us pause cleanly while `fetch(searchIndexUrl)` downloads and
    parses `search-index.json` in the background without freezing the browser UI.
  - On every keystroke (`input` event), `.filter()` scans all chapters in memory and
    `.slice(0, 10)` caps the dropdown to the top 10 matches so DOM updates stay instant.
-->
<script>
(function() {
    // Grab DOM references to the search `<input>` and floating `#rs-search-results` container
    const searchInput = document.getElementById('rs-book-search');
    const resultsContainer = document.getElementById('rs-search-results');
    // Cached array of search entries once fetched, plus a boolean lock (`isFetching`) so rapid clicks never trigger duplicate downloads
    let searchIndex = null;
    let isFetching = false;

    // Pre-compiled library-wide JSON search index generated by `paige.php` and hosted on the CDN
    const searchIndexUrl = 'https://assets.raggiesoft.com/raggiesoft-books/json/search-index.json';

    // Step 1: Lazy-load `search-index.json` the first time the reader focuses the search box
    searchInput.addEventListener('focus', async function() {
        if (!searchIndex && !isFetching) {
            isFetching = true;
            try {
                const response = await fetch(searchIndexUrl);
                searchIndex = await response.json();
            } catch (err) {
                console.error("Failed to load search index", err);
            }
        }
    });

    // Step 2: Filter the in-memory JSON array in real time as the reader types
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        resultsContainer.innerHTML = '';

        // Require at least 3 characters (e.g., `"cat"` or `"bus"`) so 1-letter queries don't match thousands of chapters
        if (query.length < 3 || !searchIndex) {
            resultsContainer.style.display = 'none';
            return;
        }

        // Case-insensitive substring search across chapter title, full body prose, era/book, and series name
        const results = searchIndex.filter(item => {
            return (item.title && item.title.toLowerCase().includes(query)) || 
                   (item.content && item.content.toLowerCase().includes(query)) ||
                   (item.book && item.book.toLowerCase().includes(query)) ||
                   (item.series && item.series.toLowerCase().includes(query));
        }).slice(0, 10); // Keep only the first 10 matches so the dropdown renders in < 1ms

        if (results.length > 0) {
            results.forEach(item => {
                const div = document.createElement('a');
                // Normalize any legacy `/raggiesoft-books/books/<slug>/...` path to root-level `/<slug>/...`
                div.href = item.url.replace('/raggiesoft-books/books/', '/');
                div.style = "display: block; padding: 0.75rem 1rem; border-bottom: 1px solid var(--rs-border); text-decoration: none; color: var(--rs-text); cursor: pointer;";
                
                div.innerHTML = `
                    <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px;">${item.series}: ${item.title}</div>
                    <div style="font-size: 0.75rem; color: var(--rs-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.book} &middot; ${item.chapter}</div>
                `;
                
                // Highlight hovered result row using CSS custom properties
                div.addEventListener('mouseenter', () => div.style.background = 'var(--rs-bg-alt)');
                div.addEventListener('mouseleave', () => div.style.background = 'transparent');
                
                resultsContainer.appendChild(div);
            });
            resultsContainer.style.display = 'block';
        } else {
            // Friendly empty state when zero chapters match the typed query
            resultsContainer.innerHTML = '<div style="padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--rs-text-muted);">No results found in the archives.</div>';
            resultsContainer.style.display = 'block';
        }
    });

    // Step 3: Light-dismiss the search results popover whenever the reader clicks anywhere outside the input or dropdown
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
})();
</script>
