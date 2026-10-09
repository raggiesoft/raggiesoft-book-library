<?php
// Sophia's Navigation Sidebar (Simplified)
global $seriesSlug, $routeData, $cdnBaseUrl, $requestUri, $katie;

// Build reverse lookup from filePath to URL
$fileToUrl = [];
if (!empty($routeData)) {
    foreach ($routeData as $url => $data) {
        if ($url === 'common') continue;
        if (isset($data['filePath'])) {
            $fileToUrl[$data['filePath']] = $url;
        }
    }
}

// Calculate sequence navigation
$sbPrevUrl = null;
$sbNextUrl = null;

// Dynamic Up Navigation based on URL segments
$segments = explode('/', trim($requestUri, '/'));
$sbUpUrl = '/' . $seriesSlug; // Default to Landing Page
$sbUpLabel = "Up to Series";

if (!empty($routeData)) {
    $routeConfig = $routeData[$requestUri] ?? [];
    $sbPrevUrl = $routeConfig['prevUrl'] ?? null;
    $sbNextUrl = $routeConfig['nextUrl'] ?? null;
    
    $sbRouteKeys = array_keys($routeData);
    $sbSeriesFilter = '/' . ($seriesSlug ?? '');
    $sbFilteredKeys = array_filter($sbRouteKeys, function($k) use ($sbSeriesFilter) { 
        return $k !== 'common' && $k !== $sbSeriesFilter; 
    });
    $sbFilteredKeys = array_values($sbFilteredKeys);
    $sbCurrentIndex = array_search($requestUri, $sbFilteredKeys);
    if ($sbCurrentIndex !== false) {
        if ($sbCurrentIndex > 0 && empty($sbPrevUrl)) $sbPrevUrl = $sbFilteredKeys[$sbCurrentIndex - 1];
        if ($sbCurrentIndex < count($sbFilteredKeys) - 1 && empty($sbNextUrl)) $sbNextUrl = $sbFilteredKeys[$sbCurrentIndex + 1];
    }
}
?>

<aside id="stardust-sidebar" class="reader-sidebar">
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
        <a href="/" class="rs-btn rs-btn-brand" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-books"></i> Library</a>
        <button id="reader-settings-toggle" class="rs-btn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-gear"></i> Settings</button>
    </div>

    <!-- Instant Search Bar / Form Fallback -->
    <form action="/search" method="GET" class="search-container" style="position: relative; margin-bottom: 1.5rem;">
        <input type="text" id="rs-book-search" name="q" placeholder="Search the universe..." style="width: 100%; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid var(--rs-border); background: var(--rs-surface); color: var(--rs-text); font-size: 0.95rem;">
        <div id="rs-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 6px; max-height: 350px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-top: 4px;"></div>
    </form>

    <h3 style="margin-top: 1rem; color: var(--rs-primary);"><?php 
        $sidebarSeriesTitle = $katie['series_title'] ?? (ucfirst($seriesSlug) . ' Narrative');
        echo htmlspecialchars($sidebarSeriesTitle); 
    ?></h3>
    <p style="opacity: 0.7; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Navigation</p>
    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 20px 0;">
    
    <nav class="toc-nav" style="display: flex; flex-direction: column; gap: 0.5rem;">
        <a href="/" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-house" style="font-size: 1.25rem;"></i> Home
        </a>
        <a href="<?php echo htmlspecialchars($sbUpUrl); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-book-open" style="font-size: 1.25rem;"></i> Series Overview
        </a>
        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-list" style="font-size: 1.25rem;"></i> Table of Contents
        </a>
        
        <hr style="border:0; border-top:1px solid var(--rs-border); margin: 10px 0;">
        
        <?php if ($sbPrevUrl): ?>
            <a href="<?php echo htmlspecialchars($sbPrevUrl); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
                <i class="ph ph-caret-left" style="font-size: 1.25rem;"></i> Previous Part
            </a>
        <?php else: ?>
            <span style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.5rem; color: var(--rs-text); opacity: 0.5; cursor: not-allowed; font-weight: 500;">
                <i class="ph ph-caret-left" style="font-size: 1.25rem;"></i> Previous Part
            </span>
        <?php endif; ?>

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

<!-- Mobile Backdrop -->
<div id="reader-sidebar-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px);"></div>

<!-- Instant Search JS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('rs-book-search');
    const resultsContainer = document.getElementById('rs-search-results');
    let searchIndex = null;
    let isFetching = false;

    const searchIndexUrl = 'https://assets.raggiesoft.com/raggiesoft-books/json/search-index.json';

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

    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        resultsContainer.innerHTML = '';

        if (query.length < 3 || !searchIndex) {
            resultsContainer.style.display = 'none';
            return;
        }

        const results = searchIndex.filter(item => {
            return (item.title && item.title.toLowerCase().includes(query)) || 
                   (item.content && item.content.toLowerCase().includes(query)) ||
                   (item.book && item.book.toLowerCase().includes(query)) ||
                   (item.series && item.series.toLowerCase().includes(query));
        }).slice(0, 10); // Show top 10

        if (results.length > 0) {
            results.forEach(item => {
                const div = document.createElement('a');
                // The index contains URLs like "/raggiesoft-books/books/amaya/..." 
                // but this app runs on books.raggiesoft.com at the root.
                div.href = item.url.replace('/raggiesoft-books/books/', '/');
                div.style = "display: block; padding: 0.75rem 1rem; border-bottom: 1px solid var(--rs-border); text-decoration: none; color: var(--rs-text); cursor: pointer;";
                div.innerHTML = `
                    <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px;">${item.series}: ${item.title}</div>
                    <div style="font-size: 0.75rem; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.book} &middot; ${item.chapter}</div>
                `;
                div.addEventListener('mouseenter', () => div.style.background = 'var(--rs-border)');
                div.addEventListener('mouseleave', () => div.style.background = 'transparent');
                
                resultsContainer.appendChild(div);
            });
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.innerHTML = '<div style="padding: 0.75rem 1rem; font-size: 0.85rem; opacity: 0.7;">No results found in the archives.</div>';
            resultsContainer.style.display = 'block';
        }
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
});
</script>
