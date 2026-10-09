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

// Load Master Settings
$settingsFile = $basePath . '/data/settings.json';
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    $cdnBaseUrl = $settings['cdnBaseUrl'] ?? 'https://assets.raggiesoft.com';
    $siteName = $settings['siteName'] ?? 'Ocean View Archives';
} else {
    $cdnBaseUrl = 'https://assets.raggiesoft.com';
    $siteName = 'Ocean View Archives';
}

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

$isCatalog = false;
if ($requestUri === '/' || $requestUri === '/isabel.php' || $requestUri === '/index.php' || $requestUri === '/catalog') {
    $isCatalog = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Catalog';
}

// Extract Variables for Views
$currentPageTheme = $pageConfig['theme'] ?? 'raggiesoft-books';
// Pre-fetch Katie for both SEO Title and Sidebar
$katie = null;
if ($seriesSlug) {
    $katieUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
    $katieContent = @file_get_contents($katieUrl);
    $katie = $katieContent ? json_decode($katieContent, true) : null;
}

$siteName = 'Ocean View Archives';
if (!empty($pageConfig['title'])) {
    // Parse "Part 1: Chapter 1: The Architect's Lesson" -> "The Architect's Lesson"
    $titleParts = explode(':', $pageConfig['title']);
    $cleanPartTitle = trim(end($titleParts));
    
    $seriesTitle = !empty($katie['series_title']) ? $katie['series_title'] : (ucwords(str_replace('-', ' ', $seriesSlug)));
    
    if ($cleanPartTitle === $seriesTitle || $cleanPartTitle === "Table of Contents - $seriesTitle") {
        $siteName = $cleanPartTitle . ' | Ocean View Archives';
    } else {
        $siteName = $cleanPartTitle . ' | ' . $seriesTitle;
    }
}

// Setup OpenGraph Variables
$ogUrl = 'https://' . $_SERVER['HTTP_HOST'] . $requestUri;
$ogTitle = $siteName;
$ogDescription = !empty($katie['series_description']) ? $katie['series_description'] : 'Read this story on the Raggiesoft Ocean View Archives.';
$ogImage = '';
if (!empty($seriesSlug)) {
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
}

// Check for OpenGraph overrides in the Markdown Frontmatter
$prefetchedMdContent = null;
if (!empty($pageConfig['filePath'])) {
    $actualFilePath = $pageConfig['filePath'];
    $isSpecial = in_array($actualFilePath, ['__SERIES_LANDING__', '__TOC__']) || strpos($actualFilePath, '__BOOK_TOC__|') === 0 || strpos($actualFilePath, '__CHAP_TOC__|') === 0;
    if (!$isSpecial) {
        $mdUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/' . $actualFilePath;
        $prefetchedContent = @file_get_contents($mdUrl);
        if ($prefetchedContent !== false) {
            $prefetchedMdContent = str_replace('{{CDN}}', $cdnBaseUrl, $prefetchedContent);
            if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $prefetchedMdContent, $matches)) {
                $rawFrontmatter = $matches[1];
                $lines = explode("\n", $rawFrontmatter);
                $fmTitle = '';
                $fmOgTitle = '';
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, ':') !== false) {
                        list($key, $val) = explode(':', $line, 2);
                        $key = trim($key);
                        $val = trim($val);
                        $val = trim($val, '"\'');
                        if ($key === 'title' && $val !== '') $fmTitle = $val;
                        if ($key === 'og_title' && $val !== '') $fmOgTitle = $val;
                        if ($key === 'og_description' && $val !== '') $ogDescription = $val;
                        if ($key === 'og_image' && $val !== '') {
                            $ogImage = $val;
                            if (strpos($ogImage, 'http') !== 0) {
                                $ogImage = $cdnBaseUrl . '/' . ltrim($ogImage, '/');
                            }
                        }
                    }
                }
                
                if ($fmOgTitle !== '') {
                    $ogTitle = $fmOgTitle;
                } elseif ($fmTitle !== '') {
                    $seriesTitle = !empty($katie['series_title']) ? $katie['series_title'] : (ucwords(str_replace('-', ' ', $seriesSlug)));
                    $ogTitle = $fmTitle . ' | ' . $seriesTitle;
                }
            }
        }
    }
}

// Do NOT load a header menu (like header-default) so the reader has full screen real estate
$currentHeaderMenu = null;

// Include Global HTML Header (Opens HTML, Head, Body, #stardust-app)
require_once $basePath . '/includes/components/headers/header.php';
echo '<link rel="stylesheet" href="' . $cdnBaseUrl . '/raggiesoft-books/css/reader.css?v=' . time() . '">';
echo '<script src="' . $cdnBaseUrl . '/raggiesoft-books/js/reader.js?v=' . time() . '" defer></script>';


echo '<div id="stardust-main-wrapper" style="display: flex; height: 100vh; overflow: hidden; width: 100%;">';

if ($isCatalog) {
    require_once $basePath . '/sophia/catalog.php';
} else {
    // Include Sophia's TOC Sidebar
    require_once $basePath . '/sophia/sidebar.php';

    // Oliver takes over for the actual reading pane
    require_once $basePath . '/sophia/oliver.php';
}

echo '</div> <!-- END #stardust-main-wrapper -->';

// Include Reader Settings Dialog
require_once $basePath . '/sophia/settings-dialog.php';

// Include Global HTML Footer (Closes #stardust-app, injects SPA script)
require_once $basePath . '/includes/components/footers/footer.php';
?>
