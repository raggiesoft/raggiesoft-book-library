<?php
// Stardust Engine Library: Master Router
header('Content-Type: text/html; charset=utf-8');

$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath = realpath(__DIR__ . '/../');

// ---------------------------------------------------------
// STARDUST ROUTER INTELLIGENCE
// ---------------------------------------------------------

$isHome = ($requestUri == '/' || $requestUri == '/isabel.php' || $requestUri == '/index.php');
$isCatalog = ($requestUri == '/catalog' || $requestUri == '/catalog/overview.php');

$pageConfig = [
    'showSidebar' => true,
    'theme' => 'oceanview',
    'view' => ''
];

$seriesSlug = '';
if (preg_match('#^/([^/]+)#', $requestUri, $matches) && !$isHome && !$isCatalog) {
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

if ($isHome) {
    $pageConfig['showSidebar'] = false;
    $pageConfig['view'] = $basePath . '/includes/components/pages/home.php';
} else if ($isCatalog) {
    $pageConfig['showSidebar'] = false;
    $pageConfig['view'] = $basePath . '/includes/components/pages/catalog_overview.php';
} else if ($seriesSlug && $requestUri === "/$seriesSlug") {
    $pageConfig['view'] = $basePath . '/includes/components/pages/series_toc.php';
} else {
    $pageConfig['view'] = $basePath . '/includes/components/pages/viewer.php';
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
    // We could dynamically load $pageConfig['sidebar'] if we wanted, 
    // but for now we fallback to the default sidebar component
    $sidebarFile = $basePath . '/includes/components/sidebars/sidebar.php';
    if (!empty($pageConfig['sidebar'])) {
        $candidate = $basePath . '/includes/components/sidebars/' . basename($pageConfig['sidebar']) . '.php';
        if (file_exists($candidate)) $sidebarFile = $candidate;
    }
    require_once $sidebarFile;
}

// Render the actual page view
if (file_exists($pageConfig['view'])) {
    require_once $pageConfig['view'];
} else {
    echo '<main id="stardust-reading-pane" tabindex="-1"><div class="book-page"><h1>404 Not Found</h1><p>The requested route could not be found.</p></div></main>';
}

echo '</div> <!-- END #stardust-main-wrapper -->';

require_once $basePath . '/includes/components/footers/footer.php';
?>
