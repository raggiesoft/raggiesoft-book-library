<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: eleanor/isabel.php
 * App:  RaggieSoft Book Reader (`https://books.raggiesoft.com`)
 *
 * WHAT IS THIS FILE? ("THE FRONT CONTROLLER / MASTER ROUTER")
 *   If you have only built static HTML/JS websites before, you might expect a
 *   URL like `https://books.raggiesoft.com/katie/toc` to correspond to a physical
 *   folder named `/katie/toc/index.html` on the server. Instead, RaggieSoft Book
 *   Reader has over 2,500 URLs—and ZERO database servers!
 *
 *   How does one PHP file serve 2,500+ clean URLs?
 *   1. On the DigitalOcean server, Nginx (the web server software) uses a rule
 *      called `try_files`: if a requested URL matches a real static file on disk
 *      (like `/sw.js`, `/favicon.ico`, or `/scripts/pwa.js`), Nginx serves that
 *      file directly. Otherwise, Nginx forwards EVERY request to THIS single PHP
 *      script: `eleanor/isabel.php`!
 *   2. In web architecture, this pattern is called a **Front Controller** (similar
 *      to `app.get('*', ...)` in an Express.js server). Notice we deliberately avoid
 *      naming it `index.php` so automated bots scanning for WordPress/PHP defaults
 *      don't find predictable entrypoints.
 *   3. In Ocean View Archives lore, the platform modules are named after the four
 *      OVA Siblings:
 *        - **Eleanor** (`eleanor/`): The public-facing web gateway directory.
 *        - **Isabel** (`eleanor/isabel.php`): This Master Router!
 *        - **Sophia** (`sophia/`): The protected vault of page templates (`home.php`,
 *          `catalog.php`, `story-arcs.php`, `sidebar.php`, etc.).
 *        - **Oliver** (`sophia/oliver.php`): The dedicated Markdown novel reader pane.
 *
 * WHAT FEEDS INTO `isabel.php`?
 *   - `$_SERVER['REQUEST_URI']`: The URL path the visitor requested (e.g. `/rachel`).
 *   - `$_GET` & `$_COOKIE`: Query parameters (e.g. `?path=...`) and saved Story
 *     Crossroads choices (`rs_cx_<bookSlug>`).
 *   - Static JSON manifests compiled ahead-of-time by Paige (`paige.php`):
 *       * `data/settings.json` (CDN base URL & site name)
 *       * `data/routes/<seriesSlug>.json` (maps clean URLs -> `.md` file paths)
 *       * `raggiesoft-assets/raggiesoft-books/books/<seriesSlug>/toc.json` (full
 *         table of contents, word counts, illustrations, and Story Crossroads graph)
 *   - Raw `.md` manuscript files (prefetched from local NVMe disk or CDN so SEO
 *     tags in `<head>` can read YAML frontmatter before the body renders).
 *
 * WHAT VISIBLE UI / BEHAVIOR DOES IT PRODUCE?
 *   1. Sends HTTP `301 Moved Permanently` redirects for legacy or trailing-slash
 *      URLs so bookmarks never break and Google never indexes duplicate URLs.
 *   2. Sends a real HTTP `404 Not Found` status code (`http_response_code(404)`)
 *      when a book or chapter doesn't exist.
 *   3. Resolves interactive **Story Crossroads** choices (walking the branching
 *      segment graph from `__root__`) so the sidebar and Next/Previous buttons
 *      reflect the reader's chosen narrative route.
 *   4. Computes unique `<title>`, `<meta name="description">`, and OpenGraph /
 *      Twitter social preview cards (`<= 60` char title, `<= 155` char description).
 *   5. Sandwiches the matching `sophia/` view between `includes/components/headers/header.php`
 *      and `includes/components/footers/footer.php` to output a complete HTML5 page!
 *
 * CRITICAL PHP RULE ("HEADERS ALREADY SENT"):
 *   In HTTP, response headers (`header(...)`, `http_response_code(...)`, `setcookie(...)`)
 *   MUST be sent BEFORE a single byte of HTML or whitespace leaves the server.
 *   That is why `<?php` sits on Line 1, Column 1 with zero spaces above it, and no
 *   `echo` or `require_once header.php` happens until line ~920!
 * =============================================================================
 */

// =============================================================================
// STEP 1: SEND GLOBAL HTTP RESPONSE HEADERS
// =============================================================================
// Tell the browser the response is UTF-8 HTML5.
// Why `Cache-Control: no-cache` instead of `no-store`?
//   - `no-cache` tells the browser: "Check with the server (`304 Not Modified` / fresh HTML)
//     before reusing cached HTML so newly published chapters show up right away,
//     BUT allow the browser's instant Back/Forward Cache (bfcache) to work!"
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');

// =============================================================================
// STEP 2: PARSE & CANONICALIZE THE INCOMING URL (`301` PERMANENT REDIRECTS)
// =============================================================================
// `$_SERVER` is a built-in PHP associative array containing web-server environment info.
// `parse_url(..., PHP_URL_PATH)` strips query strings (turning `/katie?arc=foo` into `/katie`),
// just like `new URL(req.url, 'https://...').pathname` in JavaScript!
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = (is_string($rawPath) && $rawPath !== '') ? $rawPath : '/';

// 2A. Redirect `/book/<series>`, `/books/<series>`, or `/raggiesoft-books/<series>` URLs to `/<series>`.
//     HTTP status `301` means "Moved Permanently"—browsers and search engines automatically
//     update their saved URL to the new `Location:` header. `exit;` stops PHP immediately.
if (preg_match('#^/(?:raggiesoft-)?books?/(.+)$#', $requestUri, $legacyBookMatch)) {
    $cleanLegacyTarget = rtrim(ltrim($legacyBookMatch[1], '/'), '/');
    header('Location: /' . $cleanLegacyTarget, true, 301);
    exit;
}

// 2B. Strip trailing slashes on every route except the homepage `/` (`/katie/` -> `/katie`)
//     so search engines never treat `/katie` and `/katie/` as two competing duplicate pages.
if ($requestUri !== '/' && str_ends_with($requestUri, '/')) {
    header('Location: ' . rtrim($requestUri, '/'), true, 301);
    exit;
}

// 2C. Canonicalize friendly or legacy platform aliases to their single official URL.
//     Notice PHP's associative array syntax `['key' => 'value']`—this works just like
//     a JavaScript `const platformAliasRedirects = { '/catalog': '/library', ... }` lookup map!
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
 * 2D. Legacy Part-Slug Redirect Map (`$legacyPartRedirects`)
 * -----------------------------------------------------------------------------
 * When a chapter Part's YAML `title:` is edited (for example, to remove a real-world
 * street number or replace a real-world university dorm name with an in-universe VDU
 * landmark), `paige.php` generates a new URL slug from the updated title.
 * Mapping `oldSlug => newSlug` here ensures old links and bookmarks 301-redirect
 * in O(1) constant time (`isset(...)` hash lookup) instead of hitting a 404 page!
 */
$legacyPartRedirects = [
    // Katie & Nathaniel (Era 4 legacy slugs redirected to Graduation 2003 & Ten-Campus Summer Tour mainline)
    '/katie/seventeen-senior-year-greenway-court/drunken-new-year/part-4-purple-gowns-at-the-pavilion-pomp-and-circumstance-one-chance-strictly-for-wesley'
        => '/katie/seventeen-senior-year-greenway-court-the-ten-campus-summer-tour/drunken-new-year/part-5-cautious-ground-rules-wesley-s-cheer-under-the-canopy-staying-in-virginia',
    '/katie/seventeen-senior-year-greenway-court/drunken-new-year/part-4-purple-gowns-at-the-pavilion-pomp-and-circumstance-heather-s-apology'
        => '/katie/seventeen-senior-year-greenway-court-the-ten-campus-summer-tour/drunken-new-year/part-4-purple-gowns-at-the-pavilion-pomp-and-circumstance-heather-s-apology',
    '/katie/seventeen-senior-year-greenway-court/greenway-court-apartment-a6-the-square-closet-sanctuary/part-1-apartment-a6-on-greenway-court-dad-s-microwave-cart-across-the-hall-neighbors'
        => '/katie/seventeen-senior-year-greenway-court-the-ten-campus-summer-tour/apartment-a6-on-greenway-court-familiar-neighbors-across-the-hall/apartment-a6-on-greenway-court-dad-s-microwave-cart-across-the-hall-neighbors',
    '/katie/seventeen-senior-year-greenway-court/greenway-court-apartment-a6-the-square-closet-sanctuary/apartment-a6-on-greenway-court-dads-microwave-cart-across-the-hall-neighbors'
        => '/katie/seventeen-senior-year-greenway-court-the-ten-campus-summer-tour/apartment-a6-on-greenway-court-familiar-neighbors-across-the-hall/apartment-a6-on-greenway-court-dad-s-microwave-cart-across-the-hall-neighbors',
    '/katie/seventeen-senior-year-greenway-court/greenway-court-apartment-a6-the-square-closet-sanctuary/part-3-hurricane-isabel-two-layers-of-sandbags-at-greenway-court-two-weeks-at-dad-s-house-on-arvin-road'
        => '/katie/seventeen-senior-year-greenway-court-the-ten-campus-summer-tour/brenda-in-the-back-alley-the-square-closet-sanctuary-hurricane-isabel/part-2-hurricane-isabel-two-layers-of-sandbags-at-greenway-court-two-weeks-at-dad-s-house-on-arvin-road',

    // Luna & Leo (Era 12, Chapter 3, Part 2): Split into Parts 2 & 3 right after the Kaleidoscope Sun flute signal
    '/luna-leo/the-delores-nightmare/the-hijacked-plan/part-2-printer-rage-guardianship-questions-and-a-forceful-kiss-leftovers-bus-schedules-and-locked-doors'
        => '/luna-leo/the-delores-nightmare/the-hijacked-plan/part-2-printer-rage-guardianship-questions-and-the-flute-signal',

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

// Check if the requested path is in `$legacyPartRedirects` and issue an instant 301 redirect if so.
$normalizedRequestUri = ($requestUri !== '/') ? rtrim($requestUri, '/') : '/';
if (isset($legacyPartRedirects[$normalizedRequestUri])) {
    header('Location: ' . $legacyPartRedirects[$normalizedRequestUri], true, 301);
    exit;
}

// =============================================================================
// STEP 3: RESOLVE FILESYSTEM PATHS & GLOBAL SETTINGS (`data/settings.json`)
// =============================================================================
// Since `isabel.php` lives inside `subdomains/books/eleanor/`, going up one folder (`..`)
// lands at the root of the `subdomains/books` web app! `realpath()` resolves `..` cleanly.
$basePath = realpath(__DIR__ . '/../');

/**
 * Helper Closure: `$truncateSeoText(string $text, int $maxChars): string`
 * -----------------------------------------------------------------------------
 * Search engines and social cards (Discord, iMessage, X/Twitter, LinkedIn) cut off
 * titles longer than ~60 characters and descriptions longer than ~155 characters.
 * This anonymous function strips HTML tags, collapses extra spaces, and trims at the
 * last whole word boundary (`mb_strrpos` = multi-byte UTF-8 `lastIndexOf(' ')`)
 * before appending a clean Unicode ellipsis `…`.
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

// Default configuration array for the current request (will be merged with route JSON data below).
$pageConfig = [
    'theme' => 'raggiesoft-books'
];

// Read `data/settings.json` (`json_decode(..., true)` works like `JSON.parse()` in JS,
// where `true` tells PHP to return associative arrays instead of `stdClass` objects).
$settingsFile = $basePath . '/data/settings.json';
if (file_exists($settingsFile)) {
    $settings = json_decode((string)file_get_contents($settingsFile), true);
    $cdnBaseUrl = !empty($settings['cdnBaseUrl']) ? rtrim($settings['cdnBaseUrl'], '/') : 'https://assets.raggiesoft.com';
    $siteName = !empty($settings['siteName']) ? $settings['siteName'] : 'Ocean View Archives';
} else {
    $cdnBaseUrl = 'https://assets.raggiesoft.com';
    $siteName = 'Ocean View Archives';
}

// Locate the parent server root containing sibling `raggiesoft-assets` on disk:
//   - Local macOS dev: `/Users/michael/Developer/raggiesoft-servers` (3 levels up from `subdomains/books`)
//   - Production DigitalOcean Droplet: `/var/www` (1 level up from `/var/www/raggiesoft-book-library`)
// Reading local NVMe disk first avoids making round-trip HTTPS calls to the CDN during server-side rendering!
$localServerRoot = is_dir(dirname($basePath, 3) . '/raggiesoft-assets')
    ? dirname($basePath, 3)
    : (is_dir(dirname($basePath, 1) . '/raggiesoft-assets') ? dirname($basePath, 1) : dirname($basePath, 3));

// =============================================================================
// STEP 4: MATCH TOP-LEVEL PLATFORM ROUTES FIRST (`/`, `/library`, `/arcs`, ...)
// =============================================================================
// Boolean flags tracking which top-level screen (if any) the visitor requested.
$isCatalog = false;
$isHome = false;
$isStoryArcs = false;
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
} elseif ($requestUri === '/arcs' || $requestUri === '/story-arcs') {
    $isStoryArcs = true;
    $isPlatformRoute = true;
    $pageConfig['title'] = 'Story Arcs & Reading Playlists';
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

// =============================================================================
// STEP 5: RESOLVE NARRATIVE BOOK SERIES ROUTES (`data/routes/<seriesSlug>.json`)
// =============================================================================
// If the URL wasn't a top-level platform page (like `/library`), extract the first
// path segment (`/<seriesSlug>/...`, e.g. `katie`, `rachel`, `stardust-engine`)
// and check whether Paige compiled a route table at `data/routes/<seriesSlug>.json`.
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
        // Every route JSON file has a `"common"` block holding shared book defaults
        $common = $routeData['common'] ?? [];

        // If a chapter has only 1 part, Paige collapses `/part-1-<slug>` to `/<slug>`.
        // Redirect any old `/part-1-<slug>` URL to its collapsed canonical URL (`301`).
        if (strpos($requestUri, '/part-1-') !== false) {
            $strippedPart1Uri = preg_replace('#/part-1-([^/]+)$#', '/$1', $requestUri);
            if (is_string($strippedPart1Uri) && !empty($routeData[$strippedPart1Uri])) {
                header('Location: ' . $strippedPart1Uri, true, 301);
                exit;
            }
        }

        // Merge defaults -> `"common"` -> specific URL entry (`Object.assign({}, pageConfig, common, routeSpecific)` in JS)
        $routeSpecific = $routeData[$requestUri] ?? [];
        $pageConfig = array_merge($pageConfig, $common, $routeSpecific);
        if (empty($routeSpecific['filePath'])) {
            // If Story Crossroads are parked for this series (`crossroads.json` absent),
            // gracefully 301-redirect any retired `/<series>/paths/...` URL to `/<series>`
            // while automatically yielding to live Crossroads routes whenever active.
            if (str_starts_with($requestUri, '/' . $seriesSlug . '/paths/')) {
                header('Location: /' . $seriesSlug, true, 301);
                exit;
            }
            // Known book slug, but unknown chapter/part path inside that book -> HTTP 404!
            $isNotFound = true;
        }
    } else {
        // First URL segment matched neither a platform route nor any `data/routes/<slug>.json` -> HTTP 404!
        $isNotFound = true;
    }
} elseif (!$isPlatformRoute) {
    $isNotFound = true;
}

// Send a genuine HTTP `404 Not Found` status header (prevents Google "Soft-404" penalties
// while still letting us render a friendly, themed Not Found screen inside Oliver!).
if ($isNotFound) {
    http_response_code(404);
}

// Determine which CSS theme file `header.php` should load (`raggiesoft-books` -> `theme-oceanview.min.css`)
$currentPageTheme = $pageConfig['theme'] ?? 'raggiesoft-books';

// =============================================================================
// STEP 6: LOAD BOOK TABLE OF CONTENTS (`toc.json`) & EBOOK DOWNLOAD SIZES
// =============================================================================
$toc_manifest = null;
$ebookSizes = [
    'epub' => '',
    'pdf' => '',
    'text_epub' => '',
    'text_pdf' => ''
];
if ($hasValidBookSeries && $seriesSlug !== '') {
    // Prefer instant local NVMe read if `raggiesoft-assets` sits beside this repo; fall back to CDN URL.
    // Note: `@` before `file_get_contents` suppresses PHP warnings if a file/URL fails to open,
    // letting us check truthiness cleanly on the next line.
    $localTocPath = $localServerRoot . '/raggiesoft-assets/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
    if (is_file($localTocPath)) {
        $toc_manifestContent = @file_get_contents($localTocPath);
    } else {
        $toc_manifestUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/toc.json';
        $toc_manifestContent = @file_get_contents($toc_manifestUrl);
    }
    $toc_manifest = $toc_manifestContent ? json_decode($toc_manifestContent, true) : null;

    // Resolve human-readable file sizes (e.g. `"14.2 MB"`) for the free EPUB/PDF download buttons
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

/**
 * =============================================================================
 * STEP 7: MULTI-CROSSROADS NARRATIVE GRAPH & DYNAMIC ACTIVE-PATH TOC RESOLVER
 * =============================================================================
 * Plain-English Explanation:
 *   Some novels under the **RaggieSoft Story Crossroads** imprint are interactive
 *   branching narratives where the reader makes a pivotal decision at one or more
 *   "Crossroads" points, unlocking different story routes (`segments`).
 *
 *   How do we remember which route a reader picked without user accounts or a database?
 *   1. We store a tiny JSON map in a browser cookie named `rs_cx_<seriesSlug>`
 *      (e.g. `{"crossroads_1": "option_b"}`).
 *   2. If a reader clicks an option link (`?path=<optId>`), resets (`?path=reset`),
 *      or opens a shared deep-link directly into a branch (`$pageConfig['requiredChoices']`),
 *      we update `$activeCrossroadsChoices` and persist the updated cookie via `setcookie()`.
 *   3. Finally, a `while` loop walks the directed graph of story segments starting at
 *      `"__root__"`, merging Eras/Chapters along the reader's chosen options until it
 *      either reaches the end of the novel or stops at the next unchosen Crossroads
 *      (`$pendingFrontierCrossroads`)!
 */
$seriesCrossroadsList      = is_array($toc_manifest['crossroads'] ?? null) ? $toc_manifest['crossroads'] : [];
$seriesPathsMap            = is_array($toc_manifest['paths'] ?? null) ? $toc_manifest['paths'] : [];
$seriesSegmentsMap         = is_array($toc_manifest['segments'] ?? null) ? $toc_manifest['segments'] : [];
$activeCrossroadsChoices   = [];
$activeCrossroadsChain     = [];
$pendingFrontierCrossroads = null;
$activeSegmentIdSet        = ['__root__' => true];
$activeStoryPathId         = null;
$activeStoryPathObj        = null;
$effectiveTocEras          = is_array($toc_manifest['eras'] ?? null) ? $toc_manifest['eras'] : [];

if ($hasValidBookSeries && $seriesSlug !== '' && !empty($seriesCrossroadsList) && !empty($seriesSegmentsMap)) {
    $safeBookCookieSlug = preg_replace('/[^a-z0-9_-]/i', '_', $seriesSlug);
    $cxMapCookieName    = 'rs_cx_' . $safeBookCookieSlug;
    $legacyCookieName   = 'rs_path_' . $safeBookCookieSlug;

    // Index Crossroads definitions by `id` for fast lookup
    $crossroadsById = [];
    foreach ($seriesCrossroadsList as $cxDef) {
        if (!empty($cxDef['id'])) {
            $crossroadsById[(string)$cxDef['id']] = $cxDef;
        }
    }

    // 7A. Load saved multi-crossroads choices map from `$_COOKIE` (with backwards-compatible fallback)
    if (!empty($_COOKIE[$cxMapCookieName])) {
        $decodedCookie = json_decode((string)$_COOKIE[$cxMapCookieName], true);
        if (is_array($decodedCookie)) {
            foreach ($decodedCookie as $k => $v) {
                if (is_string($k) && is_string($v) && isset($crossroadsById[$k]) && isset($seriesPathsMap[$v])) {
                    $activeCrossroadsChoices[$k] = $v;
                }
            }
        }
    } elseif (!empty($_COOKIE[$legacyCookieName]) && isset($seriesPathsMap[(string)$_COOKIE[$legacyCookieName]])) {
        $legOpt = $seriesPathsMap[(string)$_COOKIE[$legacyCookieName]];
        if (!empty($legOpt['required_choices']) && is_array($legOpt['required_choices'])) {
            foreach ($legOpt['required_choices'] as $rk => $rv) {
                $activeCrossroadsChoices[(string)$rk] = (string)$rv;
            }
        } elseif (!empty($legOpt['crossroads_id'])) {
            $activeCrossroadsChoices[(string)$legOpt['crossroads_id']] = (string)$legOpt['id'];
        }
    }

    // 7B. Apply explicit URL query (`?path=<optId>` or `?path=reset` [& optional `?cx=<cxId>`])
    //     OR auto-hydrate ancestor `requiredChoices` when landing directly on a deep branch URL!
    $reqPathParam = isset($_GET['path']) ? trim((string)$_GET['path']) : '';
    $reqCxParam   = isset($_GET['cx']) ? trim((string)$_GET['cx']) : '';
    $cookieDirty  = false;

    if ($reqPathParam === 'reset') {
        if ($reqCxParam !== '' && isset($activeCrossroadsChoices[$reqCxParam])) {
            unset($activeCrossroadsChoices[$reqCxParam]);
        } else {
            $activeCrossroadsChoices = [];
        }
        $cookieDirty = true;
    } elseif ($reqPathParam !== '' && isset($seriesPathsMap[$reqPathParam])) {
        $targetSeg = $seriesPathsMap[$reqPathParam];
        if (!empty($targetSeg['required_choices']) && is_array($targetSeg['required_choices'])) {
            foreach ($targetSeg['required_choices'] as $rk => $rv) {
                $activeCrossroadsChoices[(string)$rk] = (string)$rv;
            }
        }
        if (!empty($targetSeg['crossroads_id'])) {
            $activeCrossroadsChoices[(string)$targetSeg['crossroads_id']] = $reqPathParam;
        }
        $cookieDirty = true;
    } elseif (!empty($pageConfig['requiredChoices']) && is_array($pageConfig['requiredChoices'])) {
        foreach ($pageConfig['requiredChoices'] as $rk => $rv) {
            if (($activeCrossroadsChoices[(string)$rk] ?? null) !== (string)$rv) {
                $activeCrossroadsChoices[(string)$rk] = (string)$rv;
                $cookieDirty = true;
            }
        }
    } elseif (!empty($pageConfig['pathId']) && isset($seriesPathsMap[(string)$pageConfig['pathId']])) {
        $pSeg = $seriesPathsMap[(string)$pageConfig['pathId']];
        if (!empty($pSeg['crossroads_id']) && ($activeCrossroadsChoices[(string)$pSeg['crossroads_id']] ?? null) !== (string)$pSeg['id']) {
            $activeCrossroadsChoices[(string)$pSeg['crossroads_id']] = (string)$pSeg['id'];
            $cookieDirty = true;
        }
    }

    // For shared post-convergence chapters (reached after multiple earlier branches rejoin),
    // populate `defaultChoices` ONLY for Crossroads keys the reader hasn't already chosen so
    // cold deep-link visitors see a complete Table of Contents while returning readers keep their chosen route!
    if ($reqPathParam !== 'reset' && !empty($pageConfig['defaultChoices']) && is_array($pageConfig['defaultChoices'])) {
        foreach ($pageConfig['defaultChoices'] as $dk => $dv) {
            if (!isset($activeCrossroadsChoices[(string)$dk])) {
                $activeCrossroadsChoices[(string)$dk] = (string)$dv;
                $cookieDirty = true;
            }
        }
    }

    // Save or clear the `Set-Cookie` HTTP header (31,536,000 seconds = 1 year; `SameSite=Lax` for security)
    if ($cookieDirty && !headers_sent()) {
        if (empty($activeCrossroadsChoices)) {
            setcookie($cxMapCookieName, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
            setcookie($legacyCookieName, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
        } else {
            setcookie($cxMapCookieName, json_encode($activeCrossroadsChoices, JSON_UNESCAPED_SLASHES), [
                'expires'  => time() + 31536000,
                'path'     => '/',
                'samesite' => 'Lax',
            ]);
        }
    }

    // 7C. Walk the Multi-Crossroads Segment Graph starting from `__root__`, stitching Eras/Chapters
    //     together seamlessly along the reader's active choices.
    $mergeSegmentEras = static function (array $baseEras, array $addEras): array {
        $merged = $baseEras;
        foreach ($addEras as $pEra) {
            $pEraNum = (int)($pEra['era_num'] ?? 0);
            $pEraScope = (string)($pEra['era_url'] ?? '');
            $matchedEraIdx = null;
            foreach ($merged as $mIdx => $mEra) {
                // Merge into the same Era card when two consecutive inline segments share `era_num` & `era_url`
                if ((int)($mEra['era_num'] ?? -1) === $pEraNum && (string)($mEra['era_url'] ?? '') === $pEraScope) {
                    $matchedEraIdx = $mIdx;
                    break;
                }
            }
            if ($matchedEraIdx === null) {
                $merged[] = $pEra;
            } else {
                foreach (($pEra['chapters'] ?? []) as $pChap) {
                    $pChapNum = (int)($pChap['chap_num'] ?? 0);
                    $matchedChapIdx = null;
                    foreach (($merged[$matchedEraIdx]['chapters'] ?? []) as $mcIdx => $mChap) {
                        if ((int)($mChap['chap_num'] ?? -1) === $pChapNum) {
                            $matchedChapIdx = $mcIdx;
                            break;
                        }
                    }
                    if ($matchedChapIdx === null) {
                        $merged[$matchedEraIdx]['chapters'][] = $pChap;
                    } else {
                        $merged[$matchedEraIdx]['chapters'][$matchedChapIdx]['parts'] = array_merge(
                            $merged[$matchedEraIdx]['chapters'][$matchedChapIdx]['parts'] ?? [],
                            $pChap['parts'] ?? []
                        );
                        $merged[$matchedEraIdx]['chapters'][$matchedChapIdx]['word_count'] =
                            (int)($merged[$matchedEraIdx]['chapters'][$matchedChapIdx]['word_count'] ?? 0)
                            + (int)($pChap['word_count'] ?? 0);
                        $merged[$matchedEraIdx]['chapters'][$matchedChapIdx]['illustration_count'] =
                            (int)($merged[$matchedEraIdx]['chapters'][$matchedChapIdx]['illustration_count'] ?? 0)
                            + (int)($pChap['illustration_count'] ?? 0);
                    }
                }
                $merged[$matchedEraIdx]['word_count'] =
                    (int)($merged[$matchedEraIdx]['word_count'] ?? 0) + (int)($pEra['word_count'] ?? 0);
                $merged[$matchedEraIdx]['illustration_count'] =
                    (int)($merged[$matchedEraIdx]['illustration_count'] ?? 0) + (int)($pEra['illustration_count'] ?? 0);
            }
        }
        return $merged;
    };

    $walkEras = [];
    $cursorSegId = (string)($toc_manifest['root_segment_id'] ?? '__root__');
    $visitedSegs = []; // Cycle-prevention set so a malformed JSON graph can never loop infinitely

    while ($cursorSegId !== '' && isset($seriesSegmentsMap[$cursorSegId]) && !isset($visitedSegs[$cursorSegId])) {
        $visitedSegs[$cursorSegId] = true;
        $activeSegmentIdSet[$cursorSegId] = true;
        $segDef = $seriesSegmentsMap[$cursorSegId];
        if ($cursorSegId !== '__root__') {
            $activeStoryPathId  = $cursorSegId;
            $activeStoryPathObj = $segDef;
        }
        $walkEras = $mergeSegmentEras($walkEras, is_array($segDef['eras'] ?? null) ? $segDef['eras'] : []);

        $nextCxId = isset($segDef['next_crossroads_id']) ? trim((string)$segDef['next_crossroads_id']) : '';
        if ($nextCxId === '' || !isset($crossroadsById[$nextCxId])) {
            break;
        }

        $cxObj = $crossroadsById[$nextCxId];
        $chosenOptId = $activeCrossroadsChoices[$nextCxId] ?? '';
        $matchedOptObj = null;
        if ($chosenOptId !== '') {
            foreach (($cxObj['options'] ?? []) as $candOpt) {
                if ((string)($candOpt['id'] ?? '') === $chosenOptId) {
                    $matchedOptObj = $candOpt;
                    break;
                }
            }
        }

        if ($matchedOptObj === null || !isset($seriesSegmentsMap[$chosenOptId])) {
            // Reader has not chosen at `$nextCxId` yet — stop here and surface `$cxObj` at the TOC frontier!
            $pendingFrontierCrossroads = $cxObj;
            break;
        }

        $activeCrossroadsChain[] = [
            'crossroads'    => $cxObj,
            'chosen_option' => $matchedOptObj,
        ];
        $cursorSegId = $chosenOptId;
    }

    $effectiveTocEras = $walkEras;
}

// =============================================================================
// STEP 8: COMPUTE CANONICAL URL, ROBOTS DIRECTIVE, & SOCIAL PREVIEW METADATA
// =============================================================================
// Every indexable page gets a unique `<title>`, `<meta name="description">`,
// OpenGraph (`og:*`), and Twitter Card (`twitter:*`) payload consumed by `header.php`.
// Utility/personal screens (`404`, `/bookmarks`, `/settings`, `/welcome`) get `noindex, follow`
// so search engines follow their outbound links without indexing empty user-local screens.
$canonicalUrl = 'https://books.raggiesoft.com' . ($requestUri === '/' ? '/' : rtrim($requestUri, '/'));
$ogUrl = $canonicalUrl;
$isNoIndexRoute = ($isNotFound || $isBookmarks || $isSettings || $isWelcome);
$robotsMeta = $isNoIndexRoute
    ? 'noindex, follow'
    : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$ogType = 'website';
$ogImage = $cdnBaseUrl . '/raggiesoft-books/images/og/oceanview-archives.jpg';
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
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/og/oceanview-archives.jpg';
    $ogImageAlt = 'Ocean View Archives — RaggieSoft Media literary imprint and RaggieSoft Book Reader';
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
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/og/oceanview-archives.jpg';
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
} elseif ($isStoryArcs) {
    $siteName = 'Story Arcs & Reading Playlists | Ocean View Archives';
    $ogTitle = 'Story Arcs & Reading Playlists | Ocean View Archives';
    $ogDescription = 'Follow curated chapter-by-chapter Story Arcs across Ocean View Archives novels, complete with behind-the-scenes bonus scenes.';
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/scenes/katie/b004/jun_2003_pavilion_accepted_apology_wesley_cheering_hug.jpg';
    $ogImageAlt = 'Story Arcs and Reading Playlists in the RaggieSoft Book Reader';
} elseif ($hasValidBookSeries && $seriesSlug !== '') {
    // Inside a specific novel (`/<seriesSlug>`, `/<seriesSlug>/toc`, `/<seriesSlug>/illustrations`,
    // Era overview, Chapter overview, or individual Manuscript Part `eXXX/cXXX/pXXX.md`).
    $seriesTitle = !empty($toc_manifest['series_title'])
        ? trim((string)$toc_manifest['series_title'])
        : ucwords(str_replace('-', ' ', $seriesSlug));
    $seriesShortDesc = !empty($toc_manifest['series_short_description'])
        ? trim((string)$toc_manifest['series_short_description'])
        : (!empty($toc_manifest['series_description']) ? trim((string)$toc_manifest['series_description']) : "Read {$seriesTitle} free in the RaggieSoft Book Reader on Ocean View Archives.");
    $ogImage = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
    $ogImageAlt = "Cover artwork and title card for {$seriesTitle}";

    // Paige sets special sentinel tokens (`__SERIES_LANDING__`, `__TOC__`, `__ILLUSTRATIONS__`,
    // `__ERA_TOC__|...`, `__CHAP_TOC__|...`) in `filePath` for index views, and relative `.md`
    // paths (`e001/c001/p001.md`) for actual story chapters.
    $actualFilePath = (string)($pageConfig['filePath'] ?? '');
    $seriesWordStats = $toc_manifest['word_stats'] ?? ($routeData['common']['word_stats'] ?? []);
    $seriesStoryWords = (int)($seriesWordStats['story_word_count'] ?? 0);
    $seriesEraCount   = (int)($seriesWordStats['era_count'] ?? count($toc_manifest['eras'] ?? []));
    $seriesChapCount  = (int)($seriesWordStats['chapter_count'] ?? 0);
    $seriesPartCount  = (int)($seriesWordStats['part_count'] ?? 0);
    $seriesIllusCount = (int)($toc_manifest['illustration_count'] ?? ($routeData['common']['illustration_count'] ?? 0));

    // Closure (`use ($cdnBaseUrl)` captures `$cdnBaseUrl` from the outer scope, which is explicit in PHP!)
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
        // Promote first illustrated scene plate in the book if available (`break 3` exits all 3 nested `foreach` loops!)
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
        $eraTokenParts = explode('|', $actualFilePath);
        if (count($eraTokenParts) >= 3) {
            $tokenPathId = $eraTokenParts[1];
            $eraIdx = (int)$eraTokenParts[2];
            $eraObj = $seriesPathsMap[$tokenPathId]['eras'][$eraIdx] ?? null;
        } else {
            $eraIdx = (int)($eraTokenParts[1] ?? 0);
            $eraObj = $toc_manifest['eras'][$eraIdx] ?? null;
        }
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
        if (count($idxParts) >= 4) {
            $tokenPathId = $idxParts[1];
            $eraIdx = (int)$idxParts[2];
            $chapIdx = (int)$idxParts[3];
            $eraObj = $seriesPathsMap[$tokenPathId]['eras'][$eraIdx] ?? null;
        } else {
            $eraIdx = (int)($idxParts[1] ?? 0);
            $chapIdx = (int)($idxParts[2] ?? 0);
            $eraObj = $toc_manifest['eras'][$eraIdx] ?? null;
        }
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
        // Deep Manuscript Part (`eXXX/cXXX/pXXX.md` or `paths/<id>/eXXX/cXXX/pXXX.md`)
        $ogType = 'article';
        $partTitle = trim((string)($pageConfig['title'] ?? 'Chapter'));
        $matchedTocPart = null;
        $matchedEraTitle = '';
        $matchedChapTitle = '';
        $allSearchableEras = is_array($toc_manifest['eras'] ?? null) ? $toc_manifest['eras'] : [];
        foreach ($seriesPathsMap as $spDef) {
            if (!empty($spDef['eras']) && is_array($spDef['eras'])) {
                $allSearchableEras = array_merge($allSearchableEras, $spDef['eras']);
            }
        }
        foreach ($allSearchableEras as $eItem) {
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

// =============================================================================
// STEP 9: PREFETCH LIVE MARKDOWN MANUSCRIPT & PARSE YAML FRONTMATTER OVERRIDES
// =============================================================================
// Why prefetch the `.md` file here in `isabel.php` BEFORE `header.php` runs?
//   1. `<head>` (emitted by `header.php`) needs any frontmatter `image:`, `image_alt:`,
//      `og_title:`, or `og_description:` overrides BEFORE `sophia/oliver.php` renders `<body>`.
//   2. Storing the string in `$prefetchedMdContent` lets `sophia/oliver.php` reuse it
//      immediately without reading the disk or CDN a second time!
//   3. If the `.md` file is missing everywhere, we can still set `http_response_code(404)`
//      cleanly before any HTML headers have been sent!
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
            // Expand `{{CDN}}` and `{{cms}}` asset macros right away
            $prefetchedMdContent = str_replace(['{{CDN}}', '{{cms}}'], $cdnBaseUrl, $prefetchedContent);

            // Lightweight YAML frontmatter parser: matches everything between the opening `---`
            // on line 1 and the closing `---` delimiter. Supports both `key: value` scalars and
            // multi-line YAML lists (`images:\n  - path/to/scene.jpg`).
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

                    // Handle YAML list item (`- "scene.jpg"`) belonging to the previous `key:` line
                    if (str_starts_with($line, '-') && $currentFmArrayKey !== null) {
                        $itemVal = trim(trim(substr($line, 1)), '"\'');
                        if (($currentFmArrayKey === 'image' || $currentFmArrayKey === 'images') && $fmImage === '' && $itemVal !== '') {
                            $fmImage = $itemVal;
                        } elseif (($currentFmArrayKey === 'image_alt' || $currentFmArrayKey === 'image_alts') && $fmImageAlt === '' && $itemVal !== '') {
                            $fmImageAlt = $itemVal;
                        }
                        continue;
                    }

                    // Handle `key: value` scalar line (or `key:` header starting a YAML list)
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

                // Resolve scene illustration URL for `<meta property="og:image">`
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
                    // Fallback: extract opening plain-text prose from the live manuscript if `toc.json` excerpt was empty
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

// =============================================================================
// STEP 10: ASSEMBLE & RENDER THE HTML DOCUMENT (HEADER -> VIEW -> FOOTER)
// =============================================================================
// Show the Co-Branded Top Application Header Bar (`app-header.php`) on all platform
// screens (`/`, `/library`, `/arcs`, `/stats`, etc.) and root 404s, while omitting it
// inside active book reading views (`$hasValidBookSeries === true`) so the reading pane
// gets 100% of vertical screen height (where `sophia/sidebar.php` carries navigation).
$currentHeaderMenu = (!$hasValidBookSeries) ? ($basePath . '/includes/components/headers/app-header.php') : null;

// 10A. Emit `<!DOCTYPE html>`, `<head>`, `<body>`, `<audio id="stardust-bgm">`, and `<div id="stardust-app">`
require_once $basePath . '/includes/components/headers/header.php';

// 10B. Open `#stardust-main-wrapper` (the Flexbox row holding optional Sidebar + Reading Pane)
echo '<div id="stardust-main-wrapper">';

// 10C. Dispatch to the exact Sophia view template (or Sophia Sidebar + Oliver Reader Pane)
if ($isHome) {
    require_once $basePath . '/sophia/home.php';
} else if ($isStoryArcs) {
    require_once $basePath . '/sophia/story-arcs.php';
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
        // Include Sophia's Table of Contents Sidebar on the left (or mobile drawer)
        require_once $basePath . '/sophia/sidebar.php';
    }

    // Hand execution over to Oliver, the dedicated Markdown Novel Reading Pane
    require_once $basePath . '/sophia/oliver.php';
}

echo '</div> <!-- END #stardust-main-wrapper -->';

// 10D. Emit `</div>` (closing `#stardust-app`), load `stardust-spa.min.js`, register `sw.js`, and close `</body></html>`
require_once $basePath . '/includes/components/footers/footer.php';
?>