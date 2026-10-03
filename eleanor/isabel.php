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
    'view' => $basePath . '/sophia/oliver.php'
];

// Always try to load the route data based on the series slug
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
$currentPageTheme = $pageConfig['theme'] ?? 'oceanview';
$siteName = $pageConfig['siteName'] ?? 'Ocean View Archives';

// We do NOT use the legacy header.php or sidebar.php anymore.
// We just drop straight into Oliver.
require_once $basePath . '/sophia/oliver.php';
?>
