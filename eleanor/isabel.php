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
// Note: Use `Cache-Control: no-cache` (never `no-store`) so browsers revalidate fresh HTML
// while preserving instant Back-Forward Cache (bfcache) restoration across narrative chapters.
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');

// Strip any query parameters to match exact semantic route paths
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = (is_string($rawPath) && $rawPath !== '') ? $rawPath : '/';

// Normalize legacy `/books/<series>` or `/raggiesoft-books/<series>` deep-links
// (e.g., `/books/stardust-engine`, `/raggiesoft-books/ad-astra`) to the canonical
// root-level series route (`/<series>`) with a permanent 301 redirect.
if (preg_match('#^/(?:raggiesoft-)?books/(.+)$#', $requestUri, $legacyBookMatch)) {
    $cleanLegacyTarget = rtrim(ltrim($legacyBookMatch[1], '/'), '/');
    header('Location: /' . $cleanLegacyTarget, true, 301);
    exit;
}

// Canonicalize non-root trailing slashes (`/<slug>/` -> `/<slug>`) with a permanent 301 redirect
// so search engines and social crawlers never index duplicate trailing-slash URLs.
if ($requestUri !== '/' && str_ends_with($requestUri, '/')) {
    header('Location: ' . rtrim($requestUri, '/'), true, 301);
    exit;
}

// Canonicalize duplicate platform aliases to their single primary URLs via 301 redirect
$platformAliasRedirects = [
    '/index.php'              => '/',
    '/isabel.php'             => '/',
    '/eleanor/isabel.php'     => '/',
    '/catalog'                => '/library',
    '/discover'               => '/library',
    '/table-of-illustrations' => '/illustrations',
    '/word-counts'            => '/stats',
    '/library-stats'          => '/stats',
    '/setup'                  => '/welcome',
];
if (isset($platformAliasRedirects[$requestUri])) {
    header('Location: ' . $platformAliasRedirects[$requestUri], true, 301);
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

    // Nichole (Era 13, Chapter 1, Part 1 — single-part chapter redirects directly to canonical slug)
    '/nichole/the-end/the-synchronized-shutdown/the-master-suite-95-west-main-road-portsmouth-ri-the-legacy'
        => '/nichole/the-end/the-synchronized-shutdown/the-master-suite-west-main-road-portsmouth-ri-the-legacy',
    '/nichole/the-end/the-synchronized-shutdown/part-1-the-master-suite-95-west-main-road-portsmouth-ri-the-legacy'
        => '/nichole/the-end/the-synchronized-shutdown/the-master-suite-west-main-road-portsmouth-ri-the-legacy',

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
// ISABEL'S ROUTING & CANONICAL SEO METADATA LOGIC
// ---------------------------------------------------------

/**
 * Cleanly truncates text on a word boundary for OpenGraph/Twitter titles (<= 60 chars)
 * and meta descriptions (<= 155 chars) without chopping mid-word.
 */
$truncateSeoText = static function (string $text, int $maxChars): string {
    $clean = trim((string)preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    if ($clean === '' || mb_strlen($clean, 'UTF-8') <= $maxChars) {
        return $clean;
    }
    $cut = mb_substr($clean, 0, $maxChars - 1, 'UTF-8');
    $lastSpace = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace >= (int)floor($maxChars * 0.55)) {
        $cut = mb_substr($cut, 0, $lastSpace, 'UTF-8');
    }
    return rtrim($cut, " \t\n\r\0\x0B.,;:-—") . '…';
};

// Establish default page configuration payload
$pageConfig = [
    'theme' => 'raggiesoft-books'
];

// Attempt to load global Master Settings from the local data directory
$settingsFile = $basePath . '/data/settings.json';
if (file_exists($settingsFile)) {
    $settings = json_decode((string)file_get_contents($settingsFile), true);
    $cdnBaseUrl = !empty($settings['cdnBaseUrl']) ? rtrim($settings['cdnBaseUrl'], '/') : 'https://assets.raggiesoft.com';
    $siteName = !empty($settings['siteName']) ? $settings['siteName'] : 'Ocean View Archives';
} else {
    $cdnBaseUrl = 'https://assets.raggiesoft.com';
    $siteName = 'Ocean View Archives';
}

// Workspace server root (`/Users/michael/Developer/raggiesoft-servers` locally, `/var/www` on Droplet)
$localServerRoot = dirname($basePath, 3);

// 1. Evaluate top-level platform routes FIRST before checking narrative book slugs
$isCatalog = false;
$isHome = false;
$isKnox = false;
$isAethelSaga = false;
$isAllIllustrations = false;
$isStats = false;
$isBookmarks = false;
$isSettings = false;
$isAccessibility = false;
$isAbout = false;
$isWelcome = false;
$isPlatformRoute = false;

if ($requestUri === '/') {
    $isHome = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Home';
} elseif ($requestUri === '/illustrations') {
    $isAllIllustrations = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Library Table of Illustrations';
} elseif ($requestUri === '/stats') {
    $isStats = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Library Word Count & Scale Stats';
} elseif ($requestUri === '/library') {
    $isCatalog = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Library';
} elseif ($requestUri === '/knox') {
    $isKnox = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Project: KNOX';
} elseif ($requestUri === '/aethel-saga') {
    $isAethelSaga = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'The Silver Gauntlet of Aethel';
} elseif ($requestUri === '/bookmarks') {
    $isBookmarks = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Bookmarks';
} elseif ($requestUri === '/settings') {
    $isSettings = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Settings';
} elseif ($requestUri === '/accessibility') {
    $isAccessibility = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Accessibility';
} elseif ($requestUri === '/about') {
    $isAbout = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'About';
} elseif ($requestUri === '/welcome') {
    $isWelcome = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Welcome';
}

// 2. Resolve Narrative Book Series Routes & Enforce Canonical 301 / HTTP 404 Semantics
$seriesSlug = '';
$routeData = [];
$hasValidBookSeries = false;
$isNotFound = false;

if (!$isPlatformRoute && preg_match('#^/([^/]+)#', $requestUri, $matches)) {
    $seriesSlug = $matches[1];
    $routeFile = $basePath . '/data/routes/' . $seriesSlug . '.json';
    if (file_exists($routeFile)) {
        $hasValidBookSeries = true;
        $routeData = json_decode((string)file_get_contents($routeFile), true) ?? [];
        $common = $routeData['common'] ?? [];

        // Permanently 301-redirect legacy single-part `/part-1-<slug>` URLs to canonical `/<slug>`
        if (strpos($requestUri, '/part-1-') !== false) {
            $strippedPart1Uri = preg_replace('#/part-1-([^/]+)$#', '/$1', $requestUri);
            if (is_string($strippedPart1Uri) && !empty($routeData[$strippedPart1Uri])) {
                header('Location: ' . $strippedPart1Uri, true, 301);
                exit;
            }
        }

        $routeSpecific = $routeData[$requestUri] ?? [];
        $pageConfig = array_merge($pageConfig, $common, $routeSpecific);
        if (empty($routeSpecific['filePath'])) {
            $isNotFound = true;
        }
    } else {
        $isNotFound = true;
    }
} elseif (!$isPlatformRoute) {
    $isNotFound = true;
}

if ($isNotFound) {
    http_response_code(404);
}

// Extract the determined theme for the current view wrapper
$currentPageTheme = $pageConfig['theme'] ?? 'raggiesoft-books';

// Pre-fetch the series TOC manifest for SEO metadata, breadcrumbs, and Sidebar rendering
$toc_manifest = null;
$ebookSizes = [
    'epub' => '',
    'pdf' => '',
    'text_epub' => '',
    'text_pdf' => ''
];
if ($hasValidBookSeries && $seriesSlug !== '') {
    $localTocPath = $localServerRoot . '/raggiesoft-assets/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
    if (is_file($localTocPath)) {
        $toc_manifestContent = @file_get_contents($localTocPath);
    } else {
        $toc_manifestUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
        $toc_manifestContent = @file_get_contents($toc_manifestUrl);
    }
    $toc_manifest = $toc_manifestContent ? json_decode($toc_manifestContent, true) : null;

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

// Establish canonical URL, robots directives, and baseline OpenGraph variables
$canonicalUrl = 'https://books.raggiesoft.com' . ($requestUri === '/' ? '/' : rtrim($requestUri, '/'));
$ogUrl = $canonicalUrl;
$isNoIndexRoute = ($isNotFound || $isBookmarks || $isSettings || $isWelcome);
$robotsMeta = $isNoIndexRoute
    ? 'noindex, follow'
    : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$ogType = 'website';
$ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/aubrie.jpg';
$ogImageAlt = 'Ocean View Archives — RaggieSoft Book Reader';
$metaDescription = '';
$ogDescription = '';
$ogTitle = 'Ocean View Archives';

// Resolve route-specific titles, unique descriptions (<= 155 chars), OpenGraph types, and artwork
if ($isNotFound) {
    $siteName = 'Page Not Found | Ocean View Archives';
    $ogTitle = 'Page Not Found | Ocean View Archives';
    $ogDescription = 'This page could not be found in the Ocean View Archives. Browse the full library catalog or return to the homepage.';
    if ($hasValidBookSeries && $seriesSlug !== '') {
        $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
    }
} elseif ($isHome) {
    $siteName = 'RaggieSoft Book Reader — Ocean View Archives';
    $ogTitle = 'RaggieSoft Book Reader — Ocean View Archives';
    $ogDescription = 'Read free serialized novels, scene illustrations, and companion lore from Ocean View Archives in the RaggieSoft Book Reader.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/aubrie.jpg';
    $ogImageAlt = 'RaggieSoft Book Reader — Ocean View Archives featured novel showcase';
} elseif ($isCatalog) {
    $siteName = 'Fiction Library Catalog | Ocean View Archives';
    $ogTitle = 'Fiction Library Catalog | Ocean View Archives';
    $ogDescription = 'Browse all serialized novels, sagas, and illustrated volumes in the Ocean View Archives library with free EPUB and PDF editions.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/og/contemporary_v2.jpg';
    $ogImageAlt = 'Ocean View Archives Contemporary Fiction Library catalog';
} elseif ($isAllIllustrations) {
    $siteName = 'Scene Illustrations Gallery | Ocean View Archives';
    $ogTitle = 'Scene Illustrations Gallery | Ocean View Archives';
    $ogDescription = 'Explore 250+ illustrated story scenes across The Rachel Narrative, The Eleventh Child, Katie & Nathaniel, and Ocean View Archives.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/rachel.jpg';
    $ogImageAlt = 'Scene Illustrations Gallery across Ocean View Archives novels';
} elseif ($isStats) {
    $siteName = 'Manuscript Word Counts & Scale | Ocean View Archives';
    $ogTitle = 'Manuscript Word Counts & Scale | Ocean View Archives';
    $ogDescription = 'Explore live manuscript word counts, page estimates, and reading times across every novel in the Ocean View Archives library.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/rachel.jpg';
    $ogImageAlt = 'Manuscript word counts and publishing scale across Ocean View Archives';
} elseif ($isKnox) {
    $siteName = 'Project: KNOX Universe | Ocean View Archives';
    $ogTitle = 'Project: KNOX Universe | Ocean View Archives';
    $ogDescription = 'On a high-gravity frontier world in the Telsan Gap, twins Anya and Kael Rostova evade the monolithic Axiom Archival Division.';
    $ogImage = $cdnBaseUrl . '/engine-room-records/artists/the-stardust-engine/2017-knox-ost/social-preview.jpg';
    $ogImageAlt = 'Project: KNOX science-fiction universe banner';
} elseif ($isAethelSaga) {
    $siteName = 'The Silver Gauntlet of Aethel | Ocean View Archives';
    $ogTitle = 'The Silver Gauntlet of Aethel | Ocean View Archives';
    $ogDescription = 'Two twins. One broken world. Follow Kaelan and Kaela across the four mythic eras of Aethel inside the Ocean View Archives.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/aethel/images/aethel-hero.jpg';
    $ogImageAlt = 'The Silver Gauntlet of Aethel epic fantasy saga banner';
} elseif ($isAbout) {
    $siteName = 'About Ocean View Archives | RaggieSoft Book Reader';
    $ogTitle = 'About Ocean View Archives | RaggieSoft Book Reader';
    $ogDescription = 'Learn about Ocean View Archives, the literary imprint created by Michael P. Ragsdale featuring free CC BY-SA 4.0 novels and lore.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/stardust-engine.jpg';
    $ogImageAlt = 'About Ocean View Archives and RaggieSoft Book Reader';
} elseif ($isAccessibility) {
    $siteName = 'Accessibility Statement | RaggieSoft Book Reader';
    $ogTitle = 'Accessibility Statement | RaggieSoft Book Reader';
    $ogDescription = 'Read how RaggieSoft Book Reader supports screen readers, keyboard navigation, customizable typography, and comfortable reading modes.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/katie.jpg';
    $ogImageAlt = 'RaggieSoft Book Reader accessibility features';
} elseif ($isBookmarks) {
    $siteName = 'Saved Bookmarks | RaggieSoft Book Reader';
    $ogTitle = 'Saved Bookmarks | RaggieSoft Book Reader';
    $ogDescription = 'Jump back into your saved chapters and reading progress in the RaggieSoft Book Reader.';
} elseif ($isSettings) {
    $siteName = 'Reading Settings | RaggieSoft Book Reader';
    $ogTitle = 'Reading Settings | RaggieSoft Book Reader';
    $ogDescription = 'Customize fonts, text spacing, and light or dark reading themes in the RaggieSoft Book Reader.';
} elseif ($isWelcome) {
    $siteName = 'Welcome | RaggieSoft Book Reader';
    $ogTitle = 'Welcome | RaggieSoft Book Reader';
    $ogDescription = 'Set up your preferred reading experience in the RaggieSoft Book Reader.';
} elseif ($hasValidBookSeries && $seriesSlug !== '') {
    $seriesTitle = !empty($toc_manifest['series_title'])
        ? trim((string)$toc_manifest['series_title'])
        : ucwords(str_replace('-', ' ', $seriesSlug));
    $seriesShortDesc = !empty($toc_manifest['series_short_description'])
        ? trim((string)$toc_manifest['series_short_description'])
        : (!empty($toc_manifest['series_description']) ? trim((string)$toc_manifest['series_description']) : "Read {$seriesTitle} free in the RaggieSoft Book Reader on Ocean View Archives.");
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
    $ogImageAlt = "Cover artwork and title card for {$seriesTitle}";

    $actualFilePath = (string)($pageConfig['filePath'] ?? '');
    $seriesWordStats = $toc_manifest['word_stats'] ?? ($routeData['common']['word_stats'] ?? []);
    $seriesStoryWords = (int)($seriesWordStats['story_word_count'] ?? 0);
    $seriesEraCount   = (int)($seriesWordStats['era_count'] ?? count($toc_manifest['eras'] ?? []));
    $seriesChapCount  = (int)($seriesWordStats['chapter_count'] ?? 0);
    $seriesPartCount  = (int)($seriesWordStats['part_count'] ?? 0);
    $seriesIllusCount = (int)($toc_manifest['illustration_count'] ?? ($routeData['common']['illustration_count'] ?? 0));

    $resolveManifestImg = static function (string $rawImg) use ($cdnBaseUrl): string {
        $trimmed = trim($rawImg);
        if ($trimmed === '') return '';
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }
        return $cdnBaseUrl . '/' . ltrim($trimmed, '/');
    };

    if ($actualFilePath === '__SERIES_LANDING__') {
        $ogType = 'book';
        $siteName = "{$seriesTitle} | Ocean View Archives";
        $ogTitle = $truncateSeoText("{$seriesTitle} | Ocean View Archives", 60);
        $ogDescription = $truncateSeoText($seriesShortDesc, 155);
    } elseif ($actualFilePath === '__TOC__') {
        $siteName = "Table of Contents — {$seriesTitle} | Ocean View Archives";
        $ogTitle = $truncateSeoText("Table of Contents — {$seriesTitle}", 60);
        $tocDescRaw = "Browse all {$seriesEraCount} story eras, {$seriesChapCount} chapters, and {$seriesPartCount} parts of {$seriesTitle}"
            . ($seriesStoryWords > 0 ? ' (' . number_format($seriesStoryWords) . ' story words) — ' . $seriesShortDesc : ' — ' . $seriesShortDesc);
        $ogDescription = $truncateSeoText($tocDescRaw, 155);
    } elseif ($actualFilePath === '__ILLUSTRATIONS__') {
        $siteName = "Table of Illustrations — {$seriesTitle} | Ocean View Archives";
        $ogTitle = $truncateSeoText("Illustrations ({$seriesIllusCount}) — {$seriesTitle}", 60);
        $ogDescription = $truncateSeoText(
            $seriesIllusCount > 0
                ? "Explore all {$seriesIllusCount} illustrated story scenes from {$seriesTitle} in chronological reading order on Ocean View Archives."
                : "Browse scene illustrations for {$seriesTitle} on Ocean View Archives.",
            155
        );
        // Promote first illustrated scene plate in the book if available
        if (!empty($toc_manifest['eras']) && is_array($toc_manifest['eras'])) {
            foreach ($toc_manifest['eras'] as $eItem) {
                foreach (($eItem['chapters'] ?? []) as $cItem) {
                    foreach (($cItem['parts'] ?? []) as $pItem) {
                        if (!empty($pItem['image'])) {
                            $ogImage = $resolveManifestImg((string)$pItem['image']);
                            if (!empty($pItem['image_alt'])) {
                                $ogImageAlt = (string)$pItem['image_alt'];
                            }
                            break 3;
                        }
                    }
                }
            }
        }
    } elseif (str_starts_with($actualFilePath, '__ERA_TOC__|')) {
        $eraIdx = (int)substr($actualFilePath, strlen('__ERA_TOC__|'));
        $eraObj = $toc_manifest['eras'][$eraIdx] ?? null;
        $eraTitle = !empty($eraObj['era_title']) ? trim((string)$eraObj['era_title']) : trim((string)($pageConfig['title'] ?? 'Story Era'));
        $eraChaps = is_array($eraObj['chapters'] ?? null) ? $eraObj['chapters'] : [];
        $firstEraPart = $eraChaps[0]['parts'][0] ?? null;
        $eraBaseDesc = !empty($eraObj['era_description'])
            ? trim((string)$eraObj['era_description'])
            : ("Read {$eraTitle} (" . count($eraChaps) . ' ' . (count($eraChaps) === 1 ? 'chapter' : 'chapters') . ") in {$seriesTitle}: " . ($firstEraPart['excerpt'] ?? $seriesShortDesc));
        $siteName = "{$eraTitle} — {$seriesTitle} | Ocean View Archives";
        $ogTitle = $truncateSeoText("{$eraTitle} | {$seriesTitle}", 60);
        $ogDescription = $truncateSeoText($eraBaseDesc, 155);
        foreach ($eraChaps as $cItem) {
            foreach (($cItem['parts'] ?? []) as $pItem) {
                if (!empty($pItem['image'])) {
                    $ogImage = $resolveManifestImg((string)$pItem['image']);
                    if (!empty($pItem['image_alt'])) {
                        $ogImageAlt = (string)$pItem['image_alt'];
                    }
                    break 2;
                }
            }
        }
    } elseif (str_starts_with($actualFilePath, '__CHAP_TOC__|')) {
        $idxParts = explode('|', $actualFilePath);
        $eraIdx = (int)($idxParts[1] ?? 0);
        $chapIdx = (int)($idxParts[2] ?? 0);
        $eraObj = $toc_manifest['eras'][$eraIdx] ?? null;
        $chapObj = $eraObj['chapters'][$chapIdx] ?? null;
        $chapTitle = !empty($chapObj['chap_title']) ? trim((string)$chapObj['chap_title']) : trim((string)($pageConfig['title'] ?? 'Chapter'));
        $chapParts = is_array($chapObj['parts'] ?? null) ? $chapObj['parts'] : [];
        $firstChapPart = $chapParts[0] ?? null;
        $chapBaseDesc = "Read {$chapTitle} (" . count($chapParts) . ' ' . (count($chapParts) === 1 ? 'part' : 'parts') . ") in {$seriesTitle}: " . ($firstChapPart['excerpt'] ?? $seriesShortDesc);
        $siteName = "{$chapTitle} — {$seriesTitle} | Ocean View Archives";
        $ogTitle = $truncateSeoText("{$chapTitle} | {$seriesTitle}", 60);
        $ogDescription = $truncateSeoText($chapBaseDesc, 155);
        foreach ($chapParts as $pItem) {
            if (!empty($pItem['image'])) {
                $ogImage = $resolveManifestImg((string)$pItem['image']);
                if (!empty($pItem['image_alt'])) {
                    $ogImageAlt = (string)$pItem['image_alt'];
                }
                break;
            }
        }
    } else {
        // Deep Manuscript Part (`eXXX/cXXX/pXXX.md`)
        $ogType = 'article';
        $partTitle = trim((string)($pageConfig['title'] ?? 'Chapter'));
        $matchedTocPart = null;
        $matchedEraTitle = '';
        $matchedChapTitle = '';
        if (!empty($toc_manifest['eras']) && is_array($toc_manifest['eras'])) {
            foreach ($toc_manifest['eras'] as $eItem) {
                foreach (($eItem['chapters'] ?? []) as $cItem) {
                    foreach (($cItem['parts'] ?? []) as $pItem) {
                        if (($pItem['file_path'] ?? '') === $actualFilePath) {
                            $matchedTocPart = $pItem;
                            $matchedEraTitle = (string)($eItem['era_title'] ?? '');
                            $matchedChapTitle = (string)($cItem['chap_title'] ?? '');
                            break 3;
                        }
                    }
                }
            }
        }
        if (!empty($matchedTocPart['part_title'])) {
            $partTitle = trim((string)$matchedTocPart['part_title']);
        }
        $siteName = "{$partTitle} | {$seriesTitle}";
        $ogTitle = $truncateSeoText("{$partTitle} | {$seriesTitle}", 60);
        if (!empty($matchedTocPart['excerpt'])) {
            $ogDescription = $truncateSeoText((string)$matchedTocPart['excerpt'], 155);
        } else {
            $ogDescription = $truncateSeoText($seriesShortDesc, 155);
        }
        if (!empty($matchedTocPart['image'])) {
            $ogImage = $resolveManifestImg((string)$matchedTocPart['image']);
            if (!empty($matchedTocPart['image_alt'])) {
                $ogImageAlt = (string)$matchedTocPart['image_alt'];
            } else {
                $ogImageAlt = "{$partTitle} — {$seriesTitle}";
            }
        }
    }
}

// Intercept physical Markdown manuscript pages (preferring local NVMe disk first, falling back to CDN)
// to reuse `$prefetchedMdContent` in `oliver.php` and honor explicit YAML frontmatter SEO overrides.
$prefetchedMdContent = null;
if ($hasValidBookSeries && !empty($pageConfig['filePath'])) {
    $actualFilePath = (string)$pageConfig['filePath'];
    $isSpecial = in_array($actualFilePath, ['__SERIES_LANDING__', '__TOC__', '__ILLUSTRATIONS__'], true)
        || str_starts_with($actualFilePath, '__ERA_TOC__|')
        || str_starts_with($actualFilePath, '__CHAP_TOC__|');

    if (!$isSpecial) {
        $localAssetMdPath = $localServerRoot . '/raggiesoft-assets/raggiesoft-books/books/' . $seriesSlug . '/' . ltrim($actualFilePath, '/');
        $localNarrMdPath  = $localServerRoot . '/raggiesoft-narratives/books/' . $seriesSlug . '/' . ltrim($actualFilePath, '/');
        if (is_file($localAssetMdPath)) {
            $prefetchedContent = @file_get_contents($localAssetMdPath);
        } elseif (is_file($localNarrMdPath)) {
            $prefetchedContent = @file_get_contents($localNarrMdPath);
        } else {
            $mdUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/' . ltrim($actualFilePath, '/');
            $prefetchedContent = @file_get_contents($mdUrl);
        }

        if ($prefetchedContent !== false) {
            $prefetchedMdContent = str_replace(['{{CDN}}', '{{cms}}'], $cdnBaseUrl, $prefetchedContent);

            if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $prefetchedMdContent, $matches)) {
                $rawFrontmatter = $matches[1];
                $lines = explode("\n", $rawFrontmatter);
                $fmTitle = '';
                $fmOgTitle = '';
                $fmOgDesc = '';
                $fmImage = '';
                $fmImageAlt = '';
                $fmOgImage = '';
                $currentFmArrayKey = null;

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    if (str_starts_with($line, '-') && $currentFmArrayKey !== null) {
                        $itemVal = trim(trim(substr($line, 1)), '"\'');
                        if (($currentFmArrayKey === 'image' || $currentFmArrayKey === 'images') && $fmImage === '' && $itemVal !== '') {
                            $fmImage = $itemVal;
                        } elseif (($currentFmArrayKey === 'image_alt' || $currentFmArrayKey === 'image_alts') && $fmImageAlt === '' && $itemVal !== '') {
                            $fmImageAlt = $itemVal;
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
                        $val = trim($val, '"\'');

                        if ($key === 'title' && $val !== '') $fmTitle = $val;
                        if ($key === 'og_title' && $val !== '') $fmOgTitle = $val;
                        if (($key === 'og_description' || $key === 'summary') && $val !== '') $fmOgDesc = $val;
                        if (($key === 'image' || $key === 'images') && $val !== '' && $fmImage === '') $fmImage = $val;
                        if (($key === 'image_alt' || $key === 'image_alts') && $val !== '' && $fmImageAlt === '') $fmImageAlt = $val;
                        if ($key === 'og_image' && $val !== '') $fmOgImage = $val;
                    } else {
                        $currentFmArrayKey = null;
                    }
                }

                $resolvedSceneOgImage = ($fmOgImage !== '') ? $fmOgImage : $fmImage;
                if ($resolvedSceneOgImage !== '') {
                    $resolvedSceneOgImage = preg_replace('/#.*$/', '', $resolvedSceneOgImage);
                    if (!str_starts_with((string)$resolvedSceneOgImage, 'http')) {
                        $cleanImgPath = ltrim((string)$resolvedSceneOgImage, '/');
                        if (!str_starts_with($cleanImgPath, 'raggiesoft-books/') && !str_starts_with($cleanImgPath, 'engine-room-records/') && !str_starts_with($cleanImgPath, 'raggiesoft-lore/') && !str_starts_with($cleanImgPath, 'stardust-engine/images/')) {
                            $cleanImgPath = 'raggiesoft-books/images/scenes/' . preg_replace('#^scenes/#', '', $cleanImgPath);
                        }
                        $ogImage = $cdnBaseUrl . '/' . $cleanImgPath;
                    } else {
                        $ogImage = (string)$resolvedSceneOgImage;
                    }
                }
                if ($fmImageAlt !== '') {
                    $ogImageAlt = $fmImageAlt;
                }
                if ($fmOgTitle !== '') {
                    $ogTitle = $truncateSeoText($fmOgTitle, 60);
                }
                if ($fmOgDesc !== '') {
                    $ogDescription = $truncateSeoText($fmOgDesc, 155);
                } elseif ($ogDescription === '' || $ogDescription === $truncateSeoText($seriesShortDesc ?? '', 155)) {
                    // Extract opening prose from the live manuscript if `toc.json` excerpt was empty
                    $liveBody = substr($prefetchedMdContent, strlen($matches[0]));
                    $liveBody = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $liveBody);
                    $liveBody = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', (string)$liveBody);
                    $liveBody = preg_replace('/^#+\s+.*$/m', '', (string)$liveBody);
                    $liveBody = preg_replace('/^---+\s*$/m', '', (string)$liveBody);
                    $liveBody = preg_replace('/[*_`~>]/', '', strip_tags((string)$liveBody));
                    $liveExcerpt = $truncateSeoText((string)$liveBody, 155);
                    if ($liveExcerpt !== '') {
                        $ogDescription = $liveExcerpt;
                    }
                }
            }
        } else {
            // Physical manuscript missing from both disk and CDN -> mark 404 before `<head>` renders
            $isNotFound = true;
            http_response_code(404);
            $robotsMeta = 'noindex, follow';
            $siteName = 'Manuscript Part Not Found | Ocean View Archives';
        }
    }
}

$metaDescription = $ogDescription;

// Inject the Co-Branded Top Application Header Bar on all platform screens (and unknown root 404s),
// while preserving 100% vertical height inside valid book reading panes (where sidebar.php displays the badge)
$currentHeaderMenu = (!$hasValidBookSeries) ? ($basePath . '/includes/components/headers/app-header.php') : null;

// Include the Global HTML Header (Opens HTML, Head, Body, and the primary wrapper)
require_once $basePath . '/includes/components/headers/header.php';

// Inject Stardust-specific styling and logic scripts with deterministic cache-busting version
echo '<link rel="stylesheet" href="' . $cdnBaseUrl . '/raggiesoft-books/css/reader.css?v=20261114a">';
echo '<script src="' . $cdnBaseUrl . '/raggiesoft-books/js/reader.js?v=20261114a" defer></script>';

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
} else if ($isBookmarks) {
    require_once $basePath . '/sophia/bookmarks.php';
} else if ($isSettings) {
    require_once $basePath . '/sophia/settings.php';
} else if ($isAccessibility) {
    require_once $basePath . '/sophia/accessibility.php';
} else if ($isAbout) {
    require_once $basePath . '/sophia/about.php';
} else if ($isWelcome) {
    require_once $basePath . '/sophia/wizard.php';
} else {
    if ($hasValidBookSeries) {
        // Include Sophia's TOC Sidebar for book navigation context
        require_once $basePath . '/sophia/sidebar.php';
    }

    // Hand execution over to Oliver, the dedicated Reading Pane component
    require_once $basePath . '/sophia/oliver.php';
}

echo '</div> <!-- END #stardust-main-wrapper -->';

// Attach the unified global settings modal structure to the end of the DOM
// settings-dialog.php removed (migrated to screens)

// Include the Global HTML Footer (Closes structural tags, injects SPA framework hooks)
require_once $basePath . '/includes/components/footers/footer.php';
?>
