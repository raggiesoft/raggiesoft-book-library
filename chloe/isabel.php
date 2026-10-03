<?php
// Stardust Engine Library: Master Router
header('Content-Type: text/html; charset=utf-8');

$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath = realpath(__DIR__ . '/../');

// ---------------------------------------------------------
// STARDUST ROUTER
// ---------------------------------------------------------

$isHome = ($requestUri == '/' || $requestUri == '/isabel.php' || $requestUri == '/index.php');

// Include Global HTML Header & Navbar
require_once $basePath . '/includes/components/headers/header.php';

// Wrap Sidebar and Main content
echo '<div id="stardust-main-wrapper">';

// Include Sidebar (Except on Home Page)
if (!$isHome) {
    require_once $basePath . '/includes/components/sidebars/sidebar.php';
}

if ($isHome) {
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

echo '</div> <!-- END #stardust-main-wrapper -->';

require_once $basePath . '/includes/components/footers/footer.php';
?>
