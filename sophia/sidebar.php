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
global $seriesSlug, $routeData, $cdnBaseUrl, $requestUri, $katie;

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

// Determine "Up" navigation based on URL segments
// Defaults back to the series landing page
$segments = explode('/', trim($requestUri, '/'));
$sbUpUrl = '/' . $seriesSlug; 
$sbUpLabel = "Up to Series";

// Dynamically calculate sequential "Previous" and "Next" buttons based on current route
if (!empty($routeData)) {
    $routeConfig = $routeData[$requestUri] ?? [];
    
    // First, check if explicit overrides exist in the route definition
    $sbPrevUrl = $routeConfig['prevUrl'] ?? null;
    $sbNextUrl = $routeConfig['nextUrl'] ?? null;
    
    // Extract all registered route URLs
    $sbRouteKeys = array_keys($routeData);
    $sbSeriesFilter = '/' . ($seriesSlug ?? '');
    
    // Filter out non-sequential pages (like the root common block or landing page)
    $sbFilteredKeys = array_filter($sbRouteKeys, function($k) use ($sbSeriesFilter) { 
        return $k !== 'common' && $k !== $sbSeriesFilter; 
    });
    
    // Re-index array to guarantee numerical progression
    $sbFilteredKeys = array_values($sbFilteredKeys);
    
    // Locate the current page in the sequence
    $sbCurrentIndex = array_search($requestUri, $sbFilteredKeys);
    if ($sbCurrentIndex !== false) {
        // Assign Previous if not explicitly overridden and not the first page
        if ($sbCurrentIndex > 0 && empty($sbPrevUrl)) $sbPrevUrl = $sbFilteredKeys[$sbCurrentIndex - 1];
        // Assign Next if not explicitly overridden and not the last page
        if ($sbCurrentIndex < count($sbFilteredKeys) - 1 && empty($sbNextUrl)) $sbNextUrl = $sbFilteredKeys[$sbCurrentIndex + 1];
    }
}
?>

<!-- Slide-out sidebar container -->
<aside id="stardust-sidebar" class="reader-sidebar">
    
    <!-- Quick Access Actions Grid -->
    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 0.5rem;">
            <a href="/" class="rs-btn rs-btn-brand" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-house"></i> Home</a>
            <a href="/library" class="rs-btn" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-books"></i> Library</a>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button id="reader-bookmark-btn" class="rs-btn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-bookmark"></i> Bookmark</button>
            <!-- Triggers global settings modal -->
            <a href="/settings" class="rs-btn" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-gear"></i> Settings</a>
        </div>
    </div>

    <!-- 
      Instant Search Bar
      Acts as a standard form fallback, but enhanced via JS for instant typeahead 
    -->
    <form action="/search" method="GET" class="search-container" style="position: relative; margin-bottom: 1.5rem;">
        <input type="text" id="rs-book-search" name="q" placeholder="Search the universe..." style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid var(--rs-border); background: var(--rs-surface); color: var(--rs-text); font-size: 0.95rem;">
        <div id="rs-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 6px; max-height: 350px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-top: 4px;"></div>
    </form>

    <!-- Contextual Series Cover Image -->
    <?php if (!empty($katie['series_image'])): ?>
    <div style="margin-top: 1.5rem; margin-bottom: 0.5rem; text-align: center;">
        <img src="<?php echo htmlspecialchars($cdnBaseUrl . $katie['series_image']); ?>" alt="Cover for <?php echo htmlspecialchars($katie['series_title'] ?? ''); ?>" style="width: 100%; max-width: 140px; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); display: block; margin: 0 auto;">
    </div>
    <?php endif; ?>

    <!-- Contextual Series Title -->
    <h3 style="margin-top: 1rem; color: var(--rs-primary); text-align: center;"><?php 
        $sidebarSeriesTitle = $katie['series_title'] ?? (ucfirst($seriesSlug) . ' Narrative');
        echo htmlspecialchars($sidebarSeriesTitle); 
    ?></h3>
    <p style="opacity: 0.7; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Navigation</p>
    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 20px 0;">
    
    <!-- Primary Contextual Navigation Links -->
    <nav class="toc-nav" style="display: flex; flex-direction: column; gap: 0.5rem;">
        <a href="/library" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-books" style="font-size: 1.25rem;"></i> Library
        </a>
        <a href="<?php echo htmlspecialchars($sbUpUrl); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-book-open" style="font-size: 1.25rem;"></i> Series Overview
        </a>
        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-list" style="font-size: 1.25rem;"></i> Table of Contents
        </a>
        
        <hr style="border:0; border-top:1px solid var(--rs-border); margin: 10px 0;">
        
        <!-- Render "Previous Part" conditionally if calculated -->
        <?php if ($sbPrevUrl): ?>
            <a href="<?php echo htmlspecialchars($sbPrevUrl); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
                <i class="ph ph-caret-left" style="font-size: 1.25rem;"></i> Previous Part
            </a>
        <?php else: ?>
            <span style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.5rem; color: var(--rs-text); opacity: 0.5; cursor: not-allowed; font-weight: 500;">
                <i class="ph ph-caret-left" style="font-size: 1.25rem;"></i> Previous Part
            </span>
        <?php endif; ?>

        <!-- Render "Next Part" conditionally if calculated -->
        <?php if ($sbNextUrl): ?>
            <a href="<?php echo htmlspecialchars($sbNextUrl); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
                <i class="ph ph-caret-right" style="font-size: 1.25rem;"></i> Next Part
            </a>
        <?php else: ?>
            <span style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.5rem; color: var(--rs-text); opacity: 0.5; cursor: not-allowed; font-weight: 500;">
                <i class="ph ph-caret-right" style="font-size: 1.25rem;"></i> Next Part
            </span>
        <?php endif; ?>
    </nav>
</aside>

<!-- Hover Styles for Sidebar Links -->
<style>
    .sidebar-nav-link:hover {
        background: var(--rs-border);
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
document.addEventListener("DOMContentLoaded", function() {
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
                    <div style="font-size: 0.75rem; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.book} &middot; ${item.chapter}</div>
                `;
                
                // Add simple hover states dynamically
                div.addEventListener('mouseenter', () => div.style.background = 'var(--rs-border)');
                div.addEventListener('mouseleave', () => div.style.background = 'transparent');
                
                resultsContainer.appendChild(div);
            });
            resultsContainer.style.display = 'block';
        } else {
            // Handle no-results state gracefully
            resultsContainer.innerHTML = '<div style="padding: 0.75rem 1rem; font-size: 0.85rem; opacity: 0.7;">No results found in the archives.</div>';
            resultsContainer.style.display = 'block';
        }
    });

    // Automatically dismiss the search dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
});
</script>
