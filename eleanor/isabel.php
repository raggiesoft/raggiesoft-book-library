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
 *       configurations (`$toc_manifest`) are loaded from static JSON files. This 
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
$isKnox = false;
$isAethelSaga = false;
if ($requestUri === '/' || $requestUri === '/isabel.php' || $requestUri === '/index.php') {
    $isHome = true;
    $seriesSlug = ''; // Clear slug to avoid conflicts
    $pageConfig['title'] = 'Home';
} else if ($requestUri === '/library' || $requestUri === '/catalog') {
    $isCatalog = true;
    $seriesSlug = ''; // Clear slug
    $pageConfig['title'] = 'Library';
} else if ($requestUri === '/knox') {
    $isKnox = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Project: KNOX';
} else if ($requestUri === '/aethel-saga') {
    $isAethelSaga = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'The Silver Gauntlet of Aethel';
} else if ($requestUri === '/discover') {
    header('Location: /library', true, 301);
    exit;
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
} else if (false) {
    $isCatalog = true;
    $seriesSlug = ''; // Clear slug
    $pageConfig['title'] = 'Library';
}

// Extract the determined theme for the current view wrapper
$currentPageTheme = $pageConfig['theme'] ?? 'raggiesoft-books';

// Pre-fetch the Katie manifest for SEO calculations and Sidebar rendering
$toc_manifest = null;
$ebookSizes = [
    'epub' => '',
    'pdf' => '',
    'text_epub' => '',
    'text_pdf' => ''
];
if ($seriesSlug) {
    // Note: `@` suppresses network/file warnings if the JSON is missing
    $toc_manifestUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
    $toc_manifestContent = @file_get_contents($toc_manifestUrl);
    $toc_manifest = $toc_manifestContent ? json_decode($toc_manifestContent, true) : null;

    // Resolve human-readable eBook file sizes (preferring local route manifest / ebook-sizes.json, falling back to CDN toc.json)
    if (!empty($routeData['common']['ebook_sizes']) && is_array($routeData['common']['ebook_sizes'])) {
        $ebookSizes = array_merge($ebookSizes, $routeData['common']['ebook_sizes']);
    } else {
        $localSizesFile = $basePath . '/data/ebook-sizes.json';
        if (file_exists($localSizesFile)) {
            $allLocalSizes = json_decode((string)@file_get_contents($localSizesFile), true);
            if (!empty($allLocalSizes[$seriesSlug]) && is_array($allLocalSizes[$seriesSlug])) {
                $ebookSizes = array_merge($ebookSizes, $allLocalSizes[$seriesSlug]);
            }
        }
    }
    if (empty($ebookSizes['epub']) && !empty($toc_manifest['ebook_sizes']) && is_array($toc_manifest['ebook_sizes'])) {
        $ebookSizes = array_merge($ebookSizes, $toc_manifest['ebook_sizes']);
    }
}

$siteName = 'Ocean View Archives';
if (!empty($pageConfig['title'])) {
    // Cleanse title strings like "Scene 1: Chapter 1: The Architect's Lesson" 
    // to just "The Architect's Lesson" for tab aesthetics
    $titleParts = explode(':', $pageConfig['title']);
    $cleanSceneTitle = trim(end($titleParts));
    
    // Resolve series title from manifest, falling back to a formatted slug
    $seriesTitle = !empty($toc_manifest['series_title']) ? $toc_manifest['series_title'] : (ucwords(str_replace('-', ' ', $seriesSlug)));
    
    if ($cleanSceneTitle === $seriesTitle || $cleanSceneTitle === "Table of Contents - $seriesTitle") {
        $siteName = $cleanSceneTitle . ' | Ocean View Archives';
    } else {
        $siteName = $cleanSceneTitle . ' | ' . $seriesTitle;
    }
}

// Setup baseline OpenGraph variables for social sharing (Twitter, Facebook, Discord, etc.)
$ogUrl = 'https://' . $_SERVER['HTTP_HOST'] . $requestUri;
$ogTitle = $siteName;
$ogDescription = !empty($toc_manifest['series_description']) ? $toc_manifest['series_description'] : 'Read this story on the Raggiesoft Ocean View Archives.';
$ogImage = '';

// Determine context-appropriate OpenGraph imagery based on the active route type
if (!empty($seriesSlug)) {
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
} else if ($isCatalog) {
    $siteName = 'Contemporary Fiction Library | Ocean View Archives';
    $ogTitle = 'Contemporary Fiction Library | Ocean View Archives';
    $ogDescription = 'Living stories. Expanding lore. Dive into the universe. The grounded, real-world archives of RaggieSoft Media.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/og/contemporary_v2.jpg';
} else if ($isKnox) {
    $siteName = 'Project: KNOX Universe | Ocean View Archives';
    $ogTitle = 'Project: KNOX Universe | Ocean View Archives';
    $ogDescription = 'On a high-gravity frontier world in the Telsan Gap, twins Anya and Kael Rostova evade the monolithic Axiom Archival Division.';
    $ogImage = $cdnBaseUrl . '/engine-room-records/artists/the-stardust-engine/2017-knox-ost/social-preview.jpg';
} else if ($isAethelSaga) {
    $siteName = 'The Silver Gauntlet of Aethel | Ocean View Archives';
    $ogTitle = 'The Silver Gauntlet of Aethel | Ocean View Archives';
    $ogDescription = 'Two twins. One broken world. Follow Kaelan and Kaela across the four mythic eras of Aethel inside the Ocean View Archives.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/aethel/images/aethel-hero.jpg';
} else if ($isHome) {
    $siteName = 'Ocean View Archives | RaggieSoft Media';
    $ogTitle = 'Ocean View Archives | RaggieSoft Media';
    $ogDescription = 'The official literary imprint of RaggieSoft Media. Discover Contemporary Fiction, Project: KNOX, and The Silver Gauntlet of Aethel.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/og/contemporary_v2.jpg';
}

// Intercept specific Markdown pages to extract explicit OpenGraph overrides from their YAML frontmatter
$prefetchedMdContent = null;
if (!empty($pageConfig['filePath'])) {
    $actualFilePath = $pageConfig['filePath'];
    
    // Identify non-physical structural files to prevent unnecessary CDN requests
    $isSpecial = in_array($actualFilePath, ['__SERIES_LANDING__', '__TOC__']) || strpos($actualFilePath, '__ERA_TOC__|') === 0 || strpos($actualFilePath, '__CHAP_TOC__|') === 0;
    
    if (!$isSpecial) {
        $mdUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/' . $actualFilePath;
        $prefetchedContent = @file_get_contents($mdUrl);
        
        if ($prefetchedContent !== false) {
            // Re-map internal {{CDN}} and {{cms}} placeholders to the active domain before processing
            $prefetchedMdContent = str_replace(['{{CDN}}', '{{cms}}'], $cdnBaseUrl, $prefetchedContent);
            
            // Execute naive regex to capture YAML frontmatter block
            if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $prefetchedMdContent, $matches)) {
                $rawFrontmatter = $matches[1];
                $lines = explode("\n", $rawFrontmatter);
                $fmTitle = '';
                $fmOgTitle = '';
                $fmImage = '';
                $fmOgImage = '';
                $currentFmArrayKey = null;
                
                // Parse key-value pairs and YAML list blocks manually
                // (Supports both scalar `image: "..."` and multi-slide Hero Image Carousels where
                // `image:` or `images:` is a YAML list; the first slide becomes the default `og:image`).
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    if (strpos($line, '-') === 0 && $currentFmArrayKey !== null) {
                        $itemVal = trim(substr($line, 1));
                        $itemVal = trim($itemVal, '"\'');
                        if (($currentFmArrayKey === 'image' || $currentFmArrayKey === 'images') && $fmImage === '' && $itemVal !== '') {
                            $fmImage = $itemVal;
                        }
                        continue;
                    }

                    if (strpos($line, ':') !== false) {
                        list($key, $val) = explode(':', $line, 2);
                        $key = trim($key);
                        $val = trim($val);
                        if ($val === '') {
                            $currentFmArrayKey = $key;
                            continue;
                        }
                        $currentFmArrayKey = null;
                        // Strip quotes
                        $val = trim($val, '"\'');
                        
                        if ($key === 'title' && $val !== '') $fmTitle = $val;
                        if ($key === 'og_title' && $val !== '') $fmOgTitle = $val;
                        if ($key === 'og_description' && $val !== '') $ogDescription = $val;
                        if (($key === 'image' || $key === 'images') && $val !== '' && $fmImage === '') $fmImage = $val;
                        if ($key === 'og_image' && $val !== '') $fmOgImage = $val;
                    } else {
                        $currentFmArrayKey = null;
                    }
                }
                
                // Automatic OpenGraph (og:image / twitter:image) inheritance:
                // If the scene specifies an explicit `og_image:`, prioritize it; otherwise,
                // automatically promote the scene's 16:9 chapter header plate (`image:`).
                $resolvedSceneOgImage = ($fmOgImage !== '') ? $fmOgImage : $fmImage;
                if ($resolvedSceneOgImage !== '') {
                    // Strip any StardustParsedown layout hash (#fullwidth / #inline)
                    $resolvedSceneOgImage = preg_replace('/#.*$/', '', $resolvedSceneOgImage);
                    if (strpos($resolvedSceneOgImage, 'http') !== 0) {
                        $cleanImgPath = ltrim($resolvedSceneOgImage, '/');
                        if (strpos($cleanImgPath, 'raggiesoft-books/') !== 0 && strpos($cleanImgPath, 'engine-room-records/') !== 0) {
                            $cleanImgPath = 'raggiesoft-books/images/scenes/' . $cleanImgPath;
                        }
                        $ogImage = $cdnBaseUrl . '/' . $cleanImgPath;
                    } else {
                        $ogImage = $resolvedSceneOgImage;
                    }
                }
                
                // Apply extracted title overrides
                if ($fmOgTitle !== '') {
                    $ogTitle = $fmOgTitle;
                } elseif ($fmTitle !== '') {
                    $seriesTitle = !empty($toc_manifest['series_title']) ? $toc_manifest['series_title'] : (ucwords(str_replace('-', ' ', $seriesSlug)));
                    $ogTitle = $fmTitle . ' | ' . $seriesTitle;
                }
            }
        }
    }
}

// Prevent injection of the global hub header to preserve screen real estate for readers
$currentHeaderMenu = null;

// Include the Global HTML Header (Opens HTML, Head, Body, and the primary wrapper)
require_once $basePath . '/includes/components/headers/header.php';

// Inject Stardust-specific styling and logic scripts
echo '<link rel="stylesheet" href="' . $cdnBaseUrl . '/raggiesoft-books/css/reader.css?v=' . time() . '">';
echo '<script src="' . $cdnBaseUrl . '/raggiesoft-books/js/reader.js?v=' . time() . '" defer></script>';

// Open the primary application boundary
echo '<div id="stardust-main-wrapper">';

// Execute the final View routing based on established state
if ($isHome) {
    require_once $basePath . '/sophia/home.php';
} else if ($isCatalog) {
    require_once $basePath . '/sophia/catalog.php';
} else if ($isKnox) {
    require_once $basePath . '/sophia/knox.php';
} else if ($isAethelSaga) {
    require_once $basePath . '/sophia/aethel-saga.php';
} else if (isset($isBookmarks) && $isBookmarks) {
    require_once $basePath . '/sophia/bookmarks.php';
} else if (isset($isSettings) && $isSettings) {
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
