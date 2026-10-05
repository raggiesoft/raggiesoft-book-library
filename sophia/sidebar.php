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
        <a href="https://raggiesoft.com/raggiesoft-books/books" class="rs-btn rs-btn-brand" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-books"></i> Library</a>
        <button id="reader-settings-toggle" class="rs-btn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-gear"></i> Settings</button>
    </div>
    <h3 style="margin-top: 1rem; color: var(--rs-primary);"><?php 
        $sidebarSeriesTitle = $katie['series_title'] ?? (ucfirst($seriesSlug) . ' Narrative');
        echo htmlspecialchars($sidebarSeriesTitle); 
    ?></h3>
    <p style="opacity: 0.7; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Navigation</p>
    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 20px 0;">
    
    <nav class="toc-nav" style="display: flex; flex-direction: column; gap: 0.5rem;">
        <a href="https://raggiesoft.com/raggiesoft-books/books" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-house" style="font-size: 1.25rem;"></i> Home
        </a>
        <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-list" style="font-size: 1.25rem;"></i> Table of Contents
        </a>
        <a href="<?php echo htmlspecialchars($sbUpUrl); ?>" class="sidebar-nav-link" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.75rem 0.5rem; border-radius: 6px; color: var(--rs-text); font-weight: 500;">
            <i class="ph ph-level-up" style="font-size: 1.25rem;"></i> <?php echo htmlspecialchars($sbUpLabel); ?>
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
