<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: isabel.php
 * =============================================================================
 * Purpose:
 *     This script acts as the "Master Router" for the Stardust Engine. Named 
 *     'Isabel' within the narrative lore, it intercepts all incoming requests, 
 *     resolves them against static JSON route manifests, sets up OpenGraph 
 *     metadata, and finally orchestrates the assembly of the page using various 
 *     sub-components (Eleanor, Sophia, Oliver).
 *
 * Design Principles:
 *     - Single Entry Point (Front Controller): All traffic is directed here 
 *       via web server rewrites (e.g., Nginx or Apache).
 *     - Decoupled State: Routing definitions (`$routeData`) and narrative 
 *       configurations (`$katie`) are loaded from static JSON files. This 
 *       removes the need for a database.
 *     - SEO Optimization: Automatically parses Markdown frontmatter to inject 
 *       page-specific OpenGraph tags (`og:title`, `og:image`, etc.) for rich 
 *       social sharing.
 *
 * Maintenance Notes:
 *     - If the internal Stardust file structure changes, verify that the 
 *       `require_once` paths at the bottom of the script are still accurate.
 *     - The Markdown parser is intentionally naive. It assumes the YAML frontmatter 
 *       is wrapped in standard `---` block boundaries. If you introduce complex 
 *       YAML, you may need a dedicated YAML parsing library.
 * =============================================================================
 */

// Stardust Engine Library: Master Router
// The OVA Siblings: Eleanor (Public Entry), Isabel (Router), Sophia (Protected Vault), Oliver (Viewer)
header('Content-Type: text/html; charset=utf-8');

// Strip any query parameters to match exact semantic route paths
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');

// Resolve the absolute base directory of the server to ensure reliable includes
$basePath = realpath(__DIR__ . '/../');

// ---------------------------------------------------------
// ISABEL'S ROUTING LOGIC
// ---------------------------------------------------------

// Establish default page configuration payload
$pageConfig = [
    'theme' => 'raggiesoft-books'
];

// Attempt to load global Master Settings from the local data directory
$settingsFile = $basePath . '/data/settings.json';
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    // Coalesce fallback values to prevent undefined variables
    $cdnBaseUrl = $settings['cdnBaseUrl'] ?? 'https://assets.raggiesoft.com';
    $siteName = $settings['siteName'] ?? 'Ocean View Archives';
} else {
    $cdnBaseUrl = 'https://assets.raggiesoft.com';
    $siteName = 'Ocean View Archives';
}

// Attempt to identify the narrative series slug from the first URL segment
$seriesSlug = '';
if (preg_match('#^/([^/]+)#', $requestUri, $matches)) {
    $seriesSlug = $matches[1];
    
    // Attempt to load specific Route Data for the identified series
    $routeFile = $basePath . '/data/routes/' . $seriesSlug . '.json';
    if (file_exists($routeFile)) {
        $routeData = json_decode(file_get_contents($routeFile), true) ?? [];
        $common = $routeData['common'] ?? [];
        $routeSpecific = $routeData[$requestUri] ?? [];
        
        // Merge configurations hierarchically: Specific overrides Common overrides Default
        $pageConfig = array_merge($pageConfig, $common, $routeSpecific);
    }
}

// Determine if the requested URI corresponds to top-level platform pages
$isCatalog = false;
$isHome = false;
$isDiscover = false;
$isBookmarks = false;
$isSettings = false;
if ($requestUri === '/' || $requestUri === '/isabel.php' || $requestUri === '/index.php') {
    $isHome = true;
    $seriesSlug = ''; // Clear slug to avoid conflicts
    $pageConfig['title'] = 'Home';
} else if ($requestUri === '/library' || $requestUri === '/catalog') {
    $isCatalog = true;
    $seriesSlug = ''; // Clear slug
    $pageConfig['title'] = 'Library';
} else if ($requestUri === '/discover') {
    $isDiscover = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Discover';
} else if ($requestUri === '/bookmarks') {
    $isBookmarks = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Bookmarks';
} else if ($requestUri === '/settings') {
    $isSettings = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Settings';
} else if ($requestUri === '/accessibility') {
    $isAccessibility = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Accessibility';
} else if ($requestUri === '/about') {
    $isAbout = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'About';
} else if ($requestUri === '/welcome' || $requestUri === '/setup') {
    $isWelcome = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Welcome';
}

// Extract the determined theme for the current view wrapper
$currentPageTheme = $pageConfig['theme'] ?? 'raggiesoft-books';

// Pre-fetch the Katie manifest for SEO calculations and Sidebar rendering
$katie = null;
if ($seriesSlug) {
    // Note: `@` suppresses network/file warnings if the JSON is missing
    $katieUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
    $katieContent = @file_get_contents($katieUrl);
    $katie = $katieContent ? json_decode($katieContent, true) : null;
}

$siteName = 'Ocean View Archives';
if (!empty($pageConfig['title'])) {
    // Cleanse title strings like "Part 1: Chapter 1: The Architect's Lesson" 
    // to just "The Architect's Lesson" for tab aesthetics
    $titleParts = explode(':', $pageConfig['title']);
    $cleanPartTitle = trim(end($titleParts));
    
    // Resolve series title from manifest, falling back to a formatted slug
    $seriesTitle = !empty($katie['series_title']) ? $katie['series_title'] : (ucwords(str_replace('-', ' ', $seriesSlug)));
    
    if ($cleanPartTitle === $seriesTitle || $cleanPartTitle === "Table of Contents - $seriesTitle") {
        $siteName = $cleanPartTitle . ' | Ocean View Archives';
    } else {
        $siteName = $cleanPartTitle . ' | ' . $seriesTitle;
    }
}

// Setup baseline OpenGraph variables for social sharing (Twitter, Facebook, Discord, etc.)
$ogUrl = 'https://' . $_SERVER['HTTP_HOST'] . $requestUri;
$ogTitle = $siteName;
$ogDescription = !empty($katie['series_description']) ? $katie['series_description'] : 'Read this story on the Raggiesoft Ocean View Archives.';
$ogImage = '';

// Determine context-appropriate OpenGraph imagery based on the active route type
if (!empty($seriesSlug)) {
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
} else if ($isCatalog) {
    $ogTitle = 'Contemporary Fiction Library | Ocean View Archives';
    $ogDescription = 'Living stories. Expanding lore. Dive into the universe. The grounded, real-world archives of RaggieSoft Media.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/og/library-og.jpg';
} else if ($isHome) {
    require_once $basePath . '/sophia/home.php';
} else if ($isCatalog) {
    require_once $basePath . '/sophia/catalog.php';
} else if ($isDiscover) {
    require_once $basePath . '/sophia/discover.php';
} else if ($isBookmarks) {
    require_once $basePath . '/sophia/bookmarks.php';
} else if ($isSettings) {
    require_once $basePath . '/sophia/settings.php';
} else if (isset($isAccessibility) && $isAccessibility) {
    require_once $basePath . '/sophia/accessibility.php';
} else if (isset($isAbout) && $isAbout) {
    require_once $basePath . '/sophia/about.php';
} else if (isset($isWelcome) && $isWelcome) {
    require_once $basePath . '/sophia/wizard.php';
} else {
    // Include Sophia's TOC Sidebar for navigation context
    require_once $basePath . '/sophia/sidebar.php';

    // Hand execution over to Oliver, the dedicated Reading Pane component
    require_once $basePath . '/sophia/oliver.php';
}

echo '</div> <!-- END #stardust-main-wrapper -->';

// Attach the unified global settings modal structure to the end of the DOM
// settings-dialog.php removed (migrated to screens)

// Include the Global HTML Footer (Closes structural tags, injects SPA framework hooks)
require_once $basePath . '/includes/components/footers/footer.php';
?>
