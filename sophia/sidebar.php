<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: sidebar.php
 * =============================================================================
 * Purpose:
 *     This file renders the contextual slide-out navigation menu inside the 
 *     reading environment. It provides quick access to global sections (Home, 
 *     Library, Settings) and contextual links (Next/Previous part).
 *
 * Design Principles:
 *     - Stardust Route Calculation: Dynamically calculates sequential "Next" 
 *       and "Previous" navigation paths by parsing the active route against 
 *       the complete narrative route map (`$routeData`).
 *     - Progressive Search: Implements a client-side "Instant Search" that 
 *       lazily fetches a JSON index from the CDN when focused, allowing 
 *       real-time typeahead without backend querying.
 *
 * Maintenance Notes:
 *     - Next/Prev Logic: It explicitly filters out 'common' and landing page 
 *       routes (`/$seriesSlug`) when building the sequential array map. If 
 *       new non-sequential routes are added, filter them out here.
 *     - Search Index: The search relies on `search-index.json`. If the CDN 
 *       structure changes, update `searchIndexUrl` in the JS block.
 * =============================================================================
 */

// Import legacy global variables required for view rendering
global $seriesSlug, $routeData, $cdnBaseUrl, $requestUri, $toc_manifest, $pageConfig, $fmTitle, $ebookSizes;

// Normalize request URI to match route keys even if a trailing slash is present
$normalizedRequestUri = rtrim($requestUri, '/');
if ($normalizedRequestUri === '') {
    $normalizedRequestUri = '/';
}

// Build a reverse lookup array mapping physical file paths to clean Stardust URLs
$fileToUrl = [];
if (!empty($routeData)) {
    foreach ($routeData as $url => $data) {
        if ($url === 'common') continue; // Skip metadata block
        if (isset($data['filePath'])) {
            $fileToUrl[$data['filePath']] = $url;
        }
    }
}

// Initialize sequence navigation variables
$sbPrevUrl = null;
$sbNextUrl = null;
$routeConfig = [];

// Determine "Up" navigation based on URL segments
// Defaults back to the series landing page
$segments = explode('/', trim($normalizedRequestUri, '/'));
$sbUpUrl = '/' . $seriesSlug; 
$sbUpLabel = "Up to Series";

// Dynamically calculate sequential "Previous" and "Next" buttons based on current route
if (!empty($routeData)) {
    $routeConfig = $routeData[$normalizedRequestUri] ?? ($routeData[$requestUri] ?? []);
    
    // First, check if explicit overrides exist in the route definition
    $sbPrevUrl = $routeConfig['prevUrl'] ?? null;
    $sbNextUrl = $routeConfig['nextUrl'] ?? null;
    
    // Extract all registered route URLs
    $sbRouteKeys = array_keys($routeData);
    $sbSeriesFilter = '/' . ($seriesSlug ?? '');
    
    // Filter out non-sequential pages (like the root common block, landing page, or intermediate TOC views when reading a scene)
    $currentFilePath = (string)($routeConfig['filePath'] ?? '');
    $isReadingScene = (
        $currentFilePath !== '' &&
        $currentFilePath !== '__SERIES_LANDING__' &&
        $currentFilePath !== '__TOC__' &&
        $currentFilePath !== '__ILLUSTRATIONS__' &&
        strpos($currentFilePath, '__ERA_TOC__|') !== 0 &&
        strpos($currentFilePath, '__CHAP_TOC__|') !== 0
    );
    $sbFilteredKeys = array_filter($sbRouteKeys, function($k) use ($sbSeriesFilter, $routeData, $isReadingScene) { 
        if ($k === 'common' || $k === $sbSeriesFilter) {
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
    
    // Re-index array to guarantee numerical progression
    $sbFilteredKeys = array_values($sbFilteredKeys);
    
    // Locate the current page in the sequence
    $sbCurrentIndex = array_search($normalizedRequestUri, $sbFilteredKeys);
    if ($sbCurrentIndex !== false) {
        // Assign Previous if not explicitly overridden and not the first page
        if ($sbCurrentIndex > 0 && empty($sbPrevUrl)) $sbPrevUrl = $sbFilteredKeys[$sbCurrentIndex - 1];
        // Assign Next if not explicitly overridden and not the last page
        if ($sbCurrentIndex < count($sbFilteredKeys) - 1 && empty($sbNextUrl)) $sbNextUrl = $sbFilteredKeys[$sbCurrentIndex + 1];
    }
}

// Resolve the active page title to display beneath the series header in mixed case (NOT all caps)
$sidebarSeriesTitle = $toc_manifest['series_title'] ?? (ucfirst($seriesSlug) . ' Narrative');
$sidebarPageTitle = $routeConfig['title'] ?? ($pageConfig['title'] ?? (!empty($fmTitle) ? $fmTitle : 'Navigation'));
$sidebarPageTitle = preg_replace('/\s*-\s*' . preg_quote($sidebarSeriesTitle, '/') . '$/i', '', (string)$sidebarPageTitle);
?>

<!-- Slide-out sidebar container (Optimized for zero-scroll fit on iPad Landscape & Desktop) -->
<aside id="stardust-sidebar" class="reader-sidebar">

    <!-- 1. Co-Branded Book Reader App + Ocean View Archives Imprint Badge -->
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
    
    <!-- 2. Contextual Series Header (Compact 1-Line Clamped Subtitle) -->
    <div class="sidebar-series-header" style="text-align: left; padding: 0 0.15rem; margin-bottom: 0.65rem;">
        <a href="<?php echo htmlspecialchars($sbUpUrl); ?>" style="text-decoration: none; display: block; margin: 0 0 0.15rem 0; color: var(--rs-primary); font-size: 1.05rem; font-weight: 800; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php 
            echo htmlspecialchars($sidebarSeriesTitle); 
        ?></a>
        <p title="<?php echo htmlspecialchars($sidebarPageTitle); ?>" style="margin: 0; color: var(--rs-text-muted); font-size: 0.78rem; font-weight: 600; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($sidebarPageTitle); ?></p>
    </div>

    <!-- 3. Single-Row Quick Access Toolbar (Home, Library, Bookmark, Settings) -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.35rem; margin-bottom: 0.65rem;">
        <a href="/" class="rs-btn rs-btn-brand" title="Book Reader Home" style="text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 700; border-radius: 7px;">
            <i class="ph ph-house" style="font-size: 1rem;"></i>
            <span>Home</span>
        </a>
        <a href="/library" class="rs-btn" title="Full Book Library" style="text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 600; border-radius: 7px;">
            <i class="ph ph-books" style="font-size: 1rem;"></i>
            <span>Library</span>
        </a>
        <button id="reader-bookmark-btn" class="rs-btn" title="Bookmark This Part" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 600; border-radius: 7px;">
            <i class="ph ph-bookmark" style="font-size: 1rem;"></i>
            <span>Save</span>
        </button>
        <a href="/settings" class="rs-btn" title="Reader Typography & Theme Settings" style="text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; padding: 0.38rem 0.2rem; font-size: 0.72rem; font-weight: 600; border-radius: 7px;">
            <i class="ph ph-gear" style="font-size: 1rem;"></i>
            <span>Settings</span>
        </a>
    </div>

    <!-- 4. Compact Instant Search Bar -->
    <form action="/search" method="GET" class="search-container" style="position: relative; margin-bottom: 0.65rem;">
        <input type="text" id="rs-book-search" name="q" placeholder="Search the universe..." style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 7px; border: 1px solid var(--rs-border); background: var(--rs-surface); color: var(--rs-text); font-size: 0.86rem;">
        <div id="rs-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 6px; max-height: 320px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-top: 4px;"></div>
    </form>

    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 0 0 0.5rem 0;">
    
    <!-- 5. Primary Contextual Book Navigation (De-duplicated) -->
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
        
        <!-- 6. Side-by-Side Previous / Next Part Flipper -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; margin-top: 0.45rem; padding-top: 0.5rem; border-top: 1px solid var(--rs-border);">
            <?php if ($sbPrevUrl): ?>
                <a href="<?php echo htmlspecialchars($sbPrevUrl); ?>" class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; text-decoration: none; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); font-size: 0.8rem; font-weight: 600;">
                    <i class="ph ph-caret-left" style="font-size: 0.95rem;"></i> Prev Part
                </a>
            <?php else: ?>
                <span class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); opacity: 0.4; cursor: not-allowed; font-size: 0.8rem; font-weight: 600;">
                    <i class="ph ph-caret-left" style="font-size: 0.95rem;"></i> Prev Part
                </span>
            <?php endif; ?>

            <?php if ($sbNextUrl): ?>
                <a href="<?php echo htmlspecialchars($sbNextUrl); ?>" class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; text-decoration: none; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); font-size: 0.8rem; font-weight: 600;">
                    Next Part <i class="ph ph-caret-right" style="font-size: 0.95rem;"></i>
                </a>
            <?php else: ?>
                <span class="rs-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.42rem 0.4rem; border-radius: 7px; color: var(--rs-text); opacity: 0.4; cursor: not-allowed; font-size: 0.8rem; font-weight: 600;">
                    Next Part <i class="ph ph-caret-right" style="font-size: 0.95rem;"></i>
                </span>
            <?php endif; ?>
        </div>
    </nav>

    <!-- 7. Compact E-Book Editions Strip (Illustrated + Text-Only) -->
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

<!-- Hover Styles for Sidebar Links -->
<style>
    .sidebar-nav-link:hover {
        background: var(--rs-bg-alt);
        color: var(--rs-primary) !important;
    }
</style>

<!-- Mobile Backdrop: Darkens the main content when sidebar is open on small screens -->
<div id="reader-sidebar-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px);"></div>

<!-- 
  Instant Search JS
  Lazily fetches a pre-compiled JSON index from the CDN to provide instantaneous 
  typeahead searching across the entire narrative universe.
-->
<script>
(function() {
    const searchInput = document.getElementById('rs-book-search');
    const resultsContainer = document.getElementById('rs-search-results');
    let searchIndex = null;
    let isFetching = false;

    // Hardcoded CDN location for the compiled search index
    const searchIndexUrl = 'https://assets.raggiesoft.com/raggiesoft-books/json/search-index.json';

    // Lazy load the index only when the user focuses the input box
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

    // Execute search locally across the fetched JSON object
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        resultsContainer.innerHTML = '';

        // Prevent heavy DOM manipulation for tiny queries
        if (query.length < 3 || !searchIndex) {
            resultsContainer.style.display = 'none';
            return;
        }

        // Search across title, content, book, and series fields
        const results = searchIndex.filter(item => {
            return (item.title && item.title.toLowerCase().includes(query)) || 
                   (item.content && item.content.toLowerCase().includes(query)) ||
                   (item.book && item.book.toLowerCase().includes(query)) ||
                   (item.series && item.series.toLowerCase().includes(query));
        }).slice(0, 10); // Cap results to top 10 for performance

        if (results.length > 0) {
            results.forEach(item => {
                const div = document.createElement('a');
                // Format the URL. The index contains legacy paths like "/raggiesoft-books/books/amaya/..." 
                // but the modern Stardust engine runs directly on the root domain.
                div.href = item.url.replace('/raggiesoft-books/books/', '/');
                div.style = "display: block; padding: 0.75rem 1rem; border-bottom: 1px solid var(--rs-border); text-decoration: none; color: var(--rs-text); cursor: pointer;";
                
                div.innerHTML = `
                    <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px;">${item.series}: ${item.title}</div>
                    <div style="font-size: 0.75rem; color: var(--rs-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.book} &middot; ${item.chapter}</div>
                `;
                
                // Add simple hover states dynamically
                div.addEventListener('mouseenter', () => div.style.background = 'var(--rs-bg-alt)');
                div.addEventListener('mouseleave', () => div.style.background = 'transparent');
                
                resultsContainer.appendChild(div);
            });
            resultsContainer.style.display = 'block';
        } else {
            // Handle no-results state gracefully
            resultsContainer.innerHTML = '<div style="padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--rs-text-muted);">No results found in the archives.</div>';
            resultsContainer.style.display = 'block';
        }
    });

    // Automatically dismiss the search dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
})();
</script>
