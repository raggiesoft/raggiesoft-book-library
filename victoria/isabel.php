<?php
// Stardust Engine Library: Master Router
// The Three Siblings: Victoria (Vault), Isabel (Router), Oliver (Viewer)
header('Content-Type: text/html; charset=utf-8');

$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath = realpath(__DIR__ . '/../');

// ---------------------------------------------------------
// ISABEL'S ROUTING LOGIC
// ---------------------------------------------------------

$pageConfig = [
    'showSidebar' => true,
    'theme' => 'oceanview',
    'view' => $basePath . '/victoria/oliver.php'
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
    header('Location: https://raggiesoft.com/raggiesoft-books');
    exit;
}

// Extract Variables for Views
$currentPageTheme = $pageConfig['theme'] ?? 'oceanview';
$showSidebar = $pageConfig['showSidebar'] ?? true;
$siteName = $pageConfig['siteName'] ?? 'Ocean View Archives';

// Resolve Header
$headerFile = $pageConfig['headerMenu'] ?? 'header-default';
$currentHeaderMenu = $basePath . '/includes/components/headers/' . $headerFile . '.php';

// Include Global HTML Header & Navbar (which handles theme classes)
require_once $basePath . '/includes/components/headers/header.php';

// Wrap Sidebar and Main content
echo '<div id="stardust-main-wrapper">';

// Include Sidebar
if ($showSidebar) {
    $sidebarFile = $basePath . '/includes/components/sidebars/sidebar.php';
    if (!empty($pageConfig['sidebar'])) {
        $candidate = $basePath . '/includes/components/sidebars/' . basename($pageConfig['sidebar']) . '.php';
        if (file_exists($candidate)) $sidebarFile = $candidate;
    }
    require_once $sidebarFile;
}

// Oliver takes over for the actual reading pane
require_once $pageConfig['view'];

echo '</div> <!-- END #stardust-main-wrapper -->';

require_once $basePath . '/includes/components/footers/footer.php';
?>
