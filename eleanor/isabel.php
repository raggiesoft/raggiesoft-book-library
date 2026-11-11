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
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Strip any query parameters to match exact semantic route paths
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');

// Normalize legacy `/books/<series>` or `/raggiesoft-books/<series>` deep-links
// (e.g., `/books/stardust-engine`, `/raggiesoft-books/ad-astra`) to the canonical
// root-level series route (`/<series>`) with a permanent 301 redirect.
if (preg_match('#^/(?:raggiesoft-)?books/(.+)$#', $requestUri, $legacyBookMatch)) {
    header('Location: /' . ltrim($legacyBookMatch[1], '/'), true, 301);
    exit;
}

/**
 * Legacy Part-Slug Redirect Map (`$legacyPartRedirects`)
 * -----------------------------------------------------------------------------
 * Architectural Purpose:
 *     Preserves external bookmarks, search-engine citations, and historical deep-links
 *     whenever a Part's YAML `title:` is updated to strip real-world street numbers
 *     or real-world university building names (such as Old Dominion University's
 *     `1016 Hampton House`, replaced in-universe by Virginia Dominion University's
 *     `Harbor House`).
 *
 * Maintenance Note:
 *     Keys and target paths omit trailing slashes; `$normalizedRequestUri` trims any
 *     trailing slash prior to lookup so both `/path` and `/path/` issue a clean
 *     HTTP 301 Permanent Redirect before route JSON resolution executes.
 */
$legacyPartRedirects = [
    // Katie & Nathaniel (Era 10, Chapter 3, Part 2): Stripped street number from Victoria Boulevard
    '/katie/across-the-rotunda-the-senate-of-virginia/the-accessibility-project-riding-every-bus-across-the-commonwealth-for-the-united-states-senate/part-2-the-jammed-ramp-at-3400-victoria-boulevard-shoreline-transit-across-the-bay-wearing-the-nfa-s-f-grade-in-fairfax'
        => '/katie/across-the-rotunda-the-senate-of-virginia/the-accessibility-project-riding-every-bus-across-the-commonwealth-for-the-united-states-senate/part-2-the-jammed-ramp-on-victoria-boulevard-shoreline-transit-across-the-bay-wearing-the-nfa-s-f-grade-in-fairfax',

    // Nichole (Era 2, Chapter 3, Part 3 & Era 6, Chapter 8, Part 1): Stripped street number from Delaney Street
    '/nichole/middle-school/middle-school-life/part-3-1850-delaney-street-virginia-beach-the-architect-and-the-laborer'
        => '/nichole/middle-school/middle-school-life/part-3-delaney-street-virginia-beach-the-architect-and-the-laborer',
    '/nichole/college-tours/the-gap-year-protocol/part-1-1850-delaney-street-virginia-beach-virginia-the-departure'
        => '/nichole/college-tours/the-gap-year-protocol/part-1-delaney-street-virginia-beach-virginia-the-departure',

    // Nichole (Era 13, Chapter 1, Part 1 — single-part chapter emits both canonical and `/part-1-` alias routes)
    '/nichole/the-end/the-synchronized-shutdown/the-master-suite-95-west-main-road-portsmouth-ri-the-legacy'
        => '/nichole/the-end/the-synchronized-shutdown/the-master-suite-west-main-road-portsmouth-ri-the-legacy',
    '/nichole/the-end/the-synchronized-shutdown/part-1-the-master-suite-95-west-main-road-portsmouth-ri-the-legacy'
        => '/nichole/the-end/the-synchronized-shutdown/part-1-the-master-suite-west-main-road-portsmouth-ri-the-legacy',

    // Rachel (Era 18, Chapter 4, Part 1 & Era 19, Chapter 1, Part 2): Stripped house numbers from Canadian Arch & Rich Court
    '/rachel/the-shallow-end/the-open-door-sanctuary/part-1-the-keys-to-3701-the-dollar-lease'
        => '/rachel/the-shallow-end/the-open-door-sanctuary/part-1-the-keys-to-canadian-arch-the-dollar-lease',
    '/rachel/the-eastern-anchor/the-passcode-the-blackwater-extraction/part-2-the-evasive-route-the-1802-perimeter'
        => '/rachel/the-eastern-anchor/the-passcode-the-blackwater-extraction/part-2-the-evasive-route-the-rich-court-perimeter',

    // Rachel (Era 21, Chapter 1, Parts 2 & 3): Replaced real-world ODU `1016 Hampton House` with fictional VDU `Harbor House`
    '/rachel/the-gih-pilot-the-heel-cord-surgery-2015-2016/from-cchr-to-vdu-the-upperclass-gih-pilot-apartment/part-2-move-in-day-at-1016-hampton-house-building-2-the-2-bed-2-bath-floor-plan-alternating-beds'
        => '/rachel/the-gih-pilot-the-heel-cord-surgery-2015-2016/from-cchr-to-vdu-the-upperclass-gih-pilot-apartment/part-2-move-in-day-at-harbor-house-building-2-the-2-bed-2-bath-floor-plan-alternating-beds',
    '/rachel/the-gih-pilot-the-heel-cord-surgery-2015-2016/from-cchr-to-vdu-the-upperclass-gih-pilot-apartment/part-3-the-only-gih-pair-in-building-2-hampton-house-the-ra-s-look-in-the-hallway-the-1st-floor-laundry-room'
        => '/rachel/the-gih-pilot-the-heel-cord-surgery-2015-2016/from-cchr-to-vdu-the-upperclass-gih-pilot-apartment/part-3-the-only-gih-pair-in-building-2-harbor-house-the-ra-s-look-in-the-hallway-the-1st-floor-laundry-room',
];

// Normalize trailing slash (while preserving root `/`) and execute O(1) hash lookup for renamed Part slugs
$normalizedRequestUri = ($requestUri !== '/') ? rtrim($requestUri, '/') : '/';
if (isset($legacyPartRedirects[$normalizedRequestUri])) {
    header('Location: ' . $legacyPartRedirects[$normalizedRequestUri], true, 301);
    exit;
}

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
    // Coalesce fallback values to prevent undefined or empty variables
    $cdnBaseUrl = !empty($settings['cdnBaseUrl']) ? rtrim($settings['cdnBaseUrl'], '/') : 'https://assets.raggiesoft.com';
    $siteName = !empty($settings['siteName']) ? $settings['siteName'] : 'Ocean View Archives';
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
        if (empty($routeSpecific) && strpos($requestUri, '/part-1-') !== false) {
            $strippedPart1Uri = preg_replace('#/part-1-([^/]+)$#', '/$1', $requestUri);
            if (!empty($routeData[$strippedPart1Uri])) {
                $routeSpecific = $routeData[$strippedPart1Uri];
            }
        }
        
        // Merge configurations hierarchically: Specific overrides Common overrides Default
        $pageConfig = array_merge($pageConfig, $common, $routeSpecific);
    }
}

// Determine if the requested URI corresponds to top-level platform pages
$isCatalog = false;
$isHome = false;
$isKnox = false;
$isAethelSaga = false;
$isAllIllustrations = false;
$isStats = false;
if ($requestUri === '/' || $requestUri === '/isabel.php' || $requestUri === '/index.php') {
    $isHome = true;
    $seriesSlug = ''; // Clear slug to avoid conflicts
    $pageConfig['title'] = 'Home';
} else if ($requestUri === '/illustrations' || $requestUri === '/table-of-illustrations') {
    $isAllIllustrations = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Library Table of Illustrations';
} else if ($requestUri === '/stats' || $requestUri === '/word-counts' || $requestUri === '/library-stats') {
    $isStats = true;
    $seriesSlug = '';
    $pageConfig['title'] = 'Library Word Count & Scale Stats';
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
    // Prefer local workspace toc.json when available in dev/staging, falling back to CDN
    $localTocPath = dirname($basePath, 3) . '/raggiesoft-assets/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
    if (is_file($localTocPath)) {
        $toc_manifestContent = @file_get_contents($localTocPath);
    } else {
        // Note: `@` suppresses network/file warnings if the JSON is missing
        $toc_manifestUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
        $toc_manifestContent = @file_get_contents($toc_manifestUrl);
    }
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
    
    if ($cleanSceneTitle === $seriesTitle || $cleanSceneTitle === "Table of Contents - $seriesTitle" || $cleanSceneTitle === "Table of Illustrations - $seriesTitle") {
        $siteName = $cleanSceneTitle . ' | Ocean View Archives';
    } else {
        $siteName = $cleanSceneTitle . ' | ' . $seriesTitle;
    }
}

// Setup baseline OpenGraph variables for social sharing (Twitter, Facebook, Discord, etc.)
$ogUrl = 'https://' . $_SERVER['HTTP_HOST'] . $requestUri;
$ogTitle = $siteName;
$ogDescription = !empty($toc_manifest['series_short_description'])
    ? $toc_manifest['series_short_description']
    : (!empty($toc_manifest['series_description']) ? $toc_manifest['series_description'] : 'Read this story on the Raggiesoft Ocean View Archives.');
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
} else if ($isAllIllustrations) {
    $siteName = 'Library Table of Illustrations | Ocean View Archives';
    $ogTitle = 'Library Table of Illustrations | Ocean View Archives';
    $ogDescription = 'Browse all 250+ illustrated scenes across The Rachel Narrative, The Eleventh Child, and the Ocean View Archives library.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/rachel.jpg';
} else if ($isStats) {
    $siteName = 'Library Word Count & Publishing Scale | Ocean View Archives';
    $ogTitle = 'Library Word Count & Publishing Scale | Ocean View Archives';
    $ogDescription = 'Live, auto-updated manuscript word counts, curated lore word counts, estimated page counts, and contemporary publishing scale benchmarks across all 30 Ocean View Archives volumes.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/rachel.jpg';
} else if ($isHome) {
    $siteName = 'RaggieSoft Book Reader — Ocean View Archives';
    $ogTitle = 'RaggieSoft Book Reader — Ocean View Archives';
    $ogDescription = 'The official literary imprint of RaggieSoft Media inside the RaggieSoft Book Reader. Featuring The Eleventh Child, The Rachel Narrative, and the Contemporary Fiction Library.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/aubrie.jpg';
}

// Intercept specific Markdown pages to extract explicit OpenGraph overrides from their YAML frontmatter
$prefetchedMdContent = null;
if (!empty($pageConfig['filePath'])) {
    $actualFilePath = $pageConfig['filePath'];
    
    // Identify non-physical structural files to prevent unnecessary CDN requests
    $isSpecial = in_array($actualFilePath, ['__SERIES_LANDING__', '__TOC__', '__ILLUSTRATIONS__']) || strpos($actualFilePath, '__ERA_TOC__|') === 0 || strpos($actualFilePath, '__CHAP_TOC__|') === 0;
    
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

// Inject the Co-Branded Top Application Header Bar on all platform screens,
// while preserving 100% vertical height inside individual book reading panes (where sidebar.php displays the badge)
$currentHeaderMenu = empty($seriesSlug) ? ($basePath . '/includes/components/headers/app-header.php') : null;

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
} else if ($isAllIllustrations) {
    require_once $basePath . '/sophia/all-illustrations.php';
} else if ($isStats) {
    require_once $basePath . '/sophia/stats.php';
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
