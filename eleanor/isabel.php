<?php
// Stardust Engine Library: Master Router
// The OVA Siblings: Eleanor (Public Entry), Isabel (Router), Sophia (Protected Vault), Oliver (Viewer)
header('Content-Type: text/html; charset=utf-8');

$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath = realpath(__DIR__ . '/../');

// ---------------------------------------------------------
// ISABEL'S ROUTING LOGIC
// ---------------------------------------------------------

$pageConfig = [
    'theme' => 'raggiesoft-books'
];

// Always try to load the route data based on the series slug
$cdnBaseUrl = 'https://assets.raggiesoft.com';
$seriesSlug = '';
if (preg_match('#^/([^/]+)#', $requestUri, $matches)) {
    $seriesSlug = $matches[1];
    
    // Load Route Data for Series
    $routeFile = $basePath . '/data/routes/' . $seriesSlug . '.json';
    if (file_exists($routeFile)) {
        $routeData = json_decode(file_get_contents($routeFile), true) ?? [];
        $common = $routeData['common'] ?? [];
        $routeSpecific = $routeData[$requestUri] ?? [];
        
        // Merge common and route-specific config
        $pageConfig = array_merge($pageConfig, $common, $routeSpecific);
    }
}

// Redirect base path to the publisher imprint home
if ($requestUri === '/' || $requestUri === '/isabel.php' || $requestUri === '/index.php' || $requestUri === '/catalog') {
    header('Location: https://raggiesoft.com/raggiesoft-books/books');
    exit;
}

// Extract Variables for Views
$currentPageTheme = $pageConfig['theme'] ?? 'raggiesoft-books';
// Pre-fetch Katie for both SEO Title and Sidebar
$katie = null;
if ($seriesSlug) {
    $katieUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/katie.json';
    $katieContent = @file_get_contents($katieUrl);
    $katie = $katieContent ? json_decode($katieContent, true) : null;
}

$siteName = 'Ocean View Archives';
if (!empty($pageConfig['title'])) {
    // Parse "Part 1: Chapter 1: The Architect's Lesson" -> "The Architect's Lesson"
    $titleParts = explode(':', $pageConfig['title']);
    $cleanPartTitle = trim(end($titleParts));
    
    $seriesTitle = $katie['title'] ?? (ucfirst($seriesSlug) . ' Narrative');
    
    // SEO Format: Specific | General
    $siteName = $cleanPartTitle . ' | ' . $seriesTitle;
}

// Do NOT load a header menu (like header-default) so the reader has full screen real estate
$currentHeaderMenu = null;

// Include Global HTML Header (Opens HTML, Head, Body, #stardust-app)
require_once $basePath . '/includes/components/headers/header.php';

echo '<div id="stardust-main-wrapper" style="display: flex; height: 100vh; overflow: hidden; width: 100%;">';

// Include Sophia's TOC Sidebar
require_once $basePath . '/sophia/sidebar.php';

// Oliver takes over for the actual reading pane
require_once $basePath . '/sophia/oliver.php';

echo '</div> <!-- END #stardust-main-wrapper -->';

// Include Reader Settings Dialog
require_once $basePath . '/sophia/settings-dialog.php';

// Include Global HTML Footer (Closes #stardust-app, injects SPA script)
require_once $basePath . '/includes/components/footers/footer.php';
?>
