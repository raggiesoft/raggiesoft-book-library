<?php
// Stardust Engine Library: Master Router
header('Content-Type: text/html; charset=utf-8');

$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath = realpath(__DIR__ . '/../');

// Include Header
require_once $basePath . '/includes/components/headers/header.php';
require_once $basePath . '/includes/components/sidebars/sidebar.php';

// ---------------------------------------------------------
// STARDUST ROUTER
// ---------------------------------------------------------
if ($requestUri == '/' || $requestUri == '/isabel.php' || $requestUri == '/index.php') {
    // 1. Publisher Home Page (Ocean View Archives)
    require_once $basePath . '/includes/components/pages/home.php';

} else if ($requestUri == '/catalog' || $requestUri == '/catalog/overview.php') {
    // 2. The Catalog (Grid of all books/series)
    require_once $basePath . '/includes/components/pages/catalog_overview.php';

} else if (preg_match('#^/([^/]+)$#', $requestUri, $matches) && !in_array($matches[1], ['catalog', 'isabel.php'])) {
    // 3. Series Table of Contents (e.g. /casey, /ashley-tower)
    $seriesSlug = $matches[1];
    require_once $basePath . '/includes/components/pages/series_toc.php';

} else {
    // 4. The Markdown Reader (viewer)
    // Deep links like /casey/b001/c001/p001
    require_once $basePath . '/includes/components/pages/viewer.php';
}

require_once $basePath . '/includes/components/footers/footer.php';
?>
