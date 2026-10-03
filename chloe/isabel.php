<?php
// Stardust Engine Library: Master Router
header('Content-Type: text/html; charset=utf-8');

// The Nginx config proxies everything to this script, so we parse the URI here
// Clean query strings off the URI
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath = realpath(__DIR__ . '/../');

// Include Header
require_once $basePath . '/includes/components/headers/header.php';

// Include Sidebar
require_once $basePath . '/includes/components/sidebars/sidebar.php';

// ---------------------------------------------------------
// STARDUST ROUTER
// ---------------------------------------------------------
if ($requestUri == '/' || $requestUri == '/isabel.php' || $requestUri == '/index.php') {
    // Show Landing Page
    require_once $basePath . '/includes/components/pages/home.php';
} else if (preg_match('#^/([^/]+)$#', $requestUri, $matches)) {
    // Show Series Overview Page
    // Example: /casey, /ashley-tower
    $seriesSlug = $matches[1];
    require_once $basePath . '/includes/components/pages/overview.php';
} else {
    // Show Book Reader
    require_once $basePath . '/includes/components/pages/reader.php';
}

// Include Footer
require_once $basePath . '/includes/components/footers/footer.php';
?>
