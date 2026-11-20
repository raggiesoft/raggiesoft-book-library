<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: scripts/validate-seo.php
 * License: MIT
 * Run From Terminal: `php raggiesoft.com/subdomains/books/scripts/validate-seo.php`
 *
 * WHAT IS THIS FILE?
 *   Unlike `isabel.php` or `header.php` (which run on the web server when a
 *   human visits `books.raggiesoft.com`), this file is an **automated command-line
 *   test suite**! You run it in your terminal before or during deployment to
 *   verify that Search Engine Optimization (SEO), social sharing cards, sitemaps,
 *   and canonical redirects across all 2,500+ routes are 100% healthy.
 *
 * WHY DOES IT EXIST?
 *   A tiny typo in a router or template could accidentally:
 *     - Make two pages share the exact same `<title>` or `<meta name="description">`
 *     - Let an OpenGraph title (`og:title`) exceed 60 characters or description
 *       exceed 155 characters (causing Discord/X/Google to chop text awkwardly)
 *     - Return HTTP `200 OK` instead of `404 Not Found` on a broken URL ("Soft-404")
 *     - Emit broken JSON-LD structured data or two `<h1>` headings on one page.
 *   This script tests all of those rules automatically in ~1 second without even
 *   needing a running Nginx server!
 *
 * HOW DOES IT TEST `isabel.php` WITHOUT A WEB SERVER?
 *   Look at `renderBookRoute()` below: it uses PHP's `proc_open()` (similar to
 *   Node.js's `child_process.spawn()`) to launch a fresh, isolated PHP child
 *   process, pre-fills `$_SERVER['REQUEST_URI']` to fake a real browser request,
 *   captures the HTML output buffer (`ob_start()` -> `ob_get_clean()`) and HTTP
 *   status code (`http_response_code()`), and returns both for inspection!
 *
 * THREE VERIFICATION LAYERS:
 *   - Layer 1: Validates `robots.txt`, `sitemap.xml`, all 4 child XML sitemaps
 *              (`2,000+` canonical URLs), and `data/link-previews.json`.
 *   - Layer 2: Verifies HTTP `301` redirects on legacy/trailing-slash URLs and
 *              real HTTP `404` + `noindex, follow` on nonexistent URLs.
 *   - Layer 3: Renders representative live SSR pages across every view type and
 *              checks `<title>`, `<meta name="description">`, `og:title`,
 *              `og:image:alt`, `<link rel="canonical">`, `<h1>` count, and JSON-LD.
 * =============================================================================
 */

// `declare(strict_types=1)` enables TypeScript-like strict runtime type checking in PHP:
// passing `"123"` (a string) to a function expecting `int` throws a `TypeError` instead of silently coercing!
declare(strict_types=1);

// Resolve the root `subdomains/books` folder (one directory up from `scripts/`).
// If missing, print an error to `STDERR` (standard error stream, like `console.error` in JS) and exit with code `1`.
$booksRoot = realpath(__DIR__ . '/..');
if ($booksRoot === false) {
    fwrite(STDERR, "❌ Could not resolve subdomains/books root directory.\n");
    exit(1);
}

// Global test accumulator state: `$failures` collects failed error messages; `$checksRun` counts assertions.
$failures = [];
$checksRun = 0;

/**
 * Mini test assertion helper (like `console.assert(condition, message)` or Jest's `expect()`).
 * Instead of crashing on the first error, it collects ALL failures into `$failures`
 * so the developer can see and fix every problem in a single run!
 *
 * @param bool   $condition Truthiness of the assertion.
 * @param string $message   Human-readable failure message recorded if `$condition` is false.
 */
function assertSeo(bool $condition, string $message): void
{
    // In PHP, functions do NOT automatically see outer global variables (unlike JS closures!);
    // `global $failures, $checksRun;` explicitly imports those two top-level variables.
    global $failures, $checksRun;
    $checksRun++;
    if (!$condition) {
        $failures[] = $message;
    }
}

// =============================================================================
// LAYER 1: Validate `robots.txt`, `sitemap.xml`, and Child XML Sitemaps
// =============================================================================
// 1A. Check `robots.txt` (the standard text file search crawlers read first to see what they may index)
$robotsPath = $booksRoot . '/robots.txt';
assertSeo(is_file($robotsPath), 'Missing robots.txt in subdomains/books/');
if (is_file($robotsPath)) {
    $robotsTxt = (string)file_get_contents($robotsPath);
    assertSeo(str_contains($robotsTxt, 'User-agent: *'), 'robots.txt missing User-agent: *');
    assertSeo(
        str_contains($robotsTxt, 'Sitemap: https://books.raggiesoft.com/sitemap.xml'),
        'robots.txt missing canonical Sitemap directive'
    );
}

// 1B. Parse `sitemap.xml` (a Sitemap Index pointing to 4 child sitemaps:
//     `editorial.xml`, `books.xml`, `chapters.xml`, and `parts.xml`) using `simplexml_load_file()`.
$sitemapIndexPath = $booksRoot . '/sitemap.xml';
assertSeo(is_file($sitemapIndexPath), 'Missing sitemap.xml index in subdomains/books/');

$allSitemapUrls = [];
if (is_file($sitemapIndexPath)) {
    // Capture XML parser warnings internally rather than printing raw warnings to stdout
    libxml_use_internal_errors(true);
    $indexXml = simplexml_load_file($sitemapIndexPath);
    assertSeo($indexXml !== false, 'sitemap.xml failed XML parsing');
    if ($indexXml !== false) {
        $childCount = 0;
        foreach ($indexXml->sitemap as $smNode) {
            $childCount++;
            $childLoc = (string)$smNode->loc;
            $childMod = (string)$smNode->lastmod;
            assertSeo(
                str_starts_with($childLoc, 'https://books.raggiesoft.com/sitemaps/'),
                "Unexpected child sitemap loc: {$childLoc}"
            );
            assertSeo(
                (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $childMod),
                "Invalid lastmod '{$childMod}' in sitemap.xml for {$childLoc}"
            );

            // Load each child XML file on disk and inspect every `<url><loc>...</loc></url>` entry
            $relChild = str_replace('https://books.raggiesoft.com/', '', $childLoc);
            $localChildPath = $booksRoot . '/' . $relChild;
            assertSeo(is_file($localChildPath), "Missing child sitemap file: {$localChildPath}");
            if (is_file($localChildPath)) {
                $urlsetXml = simplexml_load_file($localChildPath);
                assertSeo($urlsetXml !== false, "Invalid XML in {$relChild}");
                if ($urlsetXml !== false) {
                    foreach ($urlsetXml->url as $uNode) {
                        $loc = (string)$uNode->loc;
                        $mod = (string)$uNode->lastmod;
                        // Ensure no URL appears twice across any of the 4 XML sitemaps
                        assertSeo(
                            !isset($allSitemapUrls[$loc]),
                            "Duplicate <loc> across XML sitemaps: {$loc}"
                        );
                        $allSitemapUrls[$loc] = $relChild;

                        // Root is `https://books.raggiesoft.com/`; all deeper paths must have NO trailing slash
                        if ($loc !== 'https://books.raggiesoft.com/') {
                            assertSeo(
                                !str_ends_with($loc, '/'),
                                "Trailing slash found in sitemap URL: {$loc}"
                            );
                        }
                        assertSeo(
                            !str_contains($loc, 'https://books.raggiesoft.com/books/'),
                            "Legacy /books/ prefix found in sitemap URL: {$loc}"
                        );
                        assertSeo(
                            (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $mod),
                            "Invalid <lastmod> '{$mod}' for {$loc} in {$relChild}"
                        );
                    }
                }
            }
        }
        assertSeo($childCount === 4, "Expected 4 child sitemaps in sitemap.xml, found {$childCount}");
    }
}

assertSeo(
    count($allSitemapUrls) >= 2000,
    'Expected >= 2,000 canonical URLs across books XML sitemaps, found ' . count($allSitemapUrls)
);

// 1C. Verify `data/link-previews.json` emits clean path-based song URLs (never legacy `?track=` query strings)
$linkPreviewsPath = $booksRoot . '/data/link-previews.json';
assertSeo(is_file($linkPreviewsPath), 'Missing data/link-previews.json');
if (is_file($linkPreviewsPath)) {
    $previews = json_decode((string)file_get_contents($linkPreviewsPath), true);
    assertSeo(is_array($previews) && count($previews) > 1000, 'link-previews.json failed JSON decode or is empty');
    if (is_array($previews)) {
        $hollowKey = 'music://fractured-prisms/1983-carnaby-street/the-hollow-square';
        assertSeo(
            isset($previews[$hollowKey]) &&
            ($previews[$hollowKey]['url'] ?? '') === 'https://music.raggiesoft.com/fractured-prisms/1983-carnaby-street/the-hollow-square',
            "link-previews.json missing canonical entry for {$hollowKey}"
        );
        foreach ($previews as $k => $entry) {
            if (is_array($entry) && isset($entry['url'])) {
                assertSeo(
                    !str_contains((string)$entry['url'], '?track='),
                    "Non-canonical ?track= URL found in link-previews.json key {$k}: {$entry['url']}"
                );
            }
        }
    }
}

// =============================================================================
// Helper: Execute an isolated SSR request against `eleanor/isabel.php`
// =============================================================================
/**
 * Spawns a clean PHP CLI child process to run `eleanor/isabel.php` for `$requestUri`!
 *
 * Why a separate child process instead of `require 'isabel.php'` directly?
 *   1. `isabel.php` calls `exit;` whenever it sends a `301` redirect, and defines
 *      top-level variables and `require_once` calls that can only run once per process.
 *   2. By wrapping `require isabel.php` in `ob_start()` (Output Buffering) +
 *      `register_shutdown_function(...)` inside a fresh `proc_open` child process,
 *      even when `isabel.php` calls `exit;` on line 49, our shutdown hook still catches
 *      `http_response_code()` (e.g. `301`, `404`, `200`) and prints `__SEO_STATUS__:<code>\n<html>`!
 *
 * @param string $requestUri Path to simulate (e.g. `'/katie/toc'`).
 * @return array{status:int, html:string} Associative array with HTTP status code and rendered HTML.
 */
function renderBookRoute(string $requestUri): array
{
    global $booksRoot;
    $runnerCode = sprintf(
        '$_SERVER["HTTP_HOST"] = "books.raggiesoft.com"; '
        . '$_SERVER["HTTPS"] = "on"; '
        . '$_SERVER["REQUEST_METHOD"] = "GET"; '
        . '$_SERVER["REQUEST_URI"] = %s; '
        . 'chdir(%s); '
        . 'ob_start(); '
        . 'register_shutdown_function(function () { '
        . '    $body = ob_get_clean(); '
        . '    $code = http_response_code(); '
        . '    if (!is_int($code) || $code === 0) { $code = 200; } '
        . '    echo "__SEO_STATUS__:" . $code . "\n" . $body; '
        . '}); '
        . 'require %s;',
        var_export($requestUri, true),
        var_export($booksRoot . '/eleanor', true),
        var_export($booksRoot . '/eleanor/isabel.php', true)
    );

    // `PHP_BINARY` points to the exact `php` executable running this script.
    $cmd = [PHP_BINARY, '-d', 'display_errors=0', '-r', $runnerCode];
    $descriptors = [
        0 => ['pipe', 'r'], // stdin
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w'], // stderr
    ];
    $proc = proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($proc)) {
        return ['status' => 500, 'html' => ''];
    }
    fclose($pipes[0]);
    $stdout = (string)stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    if (preg_match('/^__SEO_STATUS__:(\d+)\n(.*)$/s', $stdout, $m)) {
        return ['status' => (int)$m[1], 'html' => $m[2]];
    }
    return ['status' => 500, 'html' => $stdout];
}

// =============================================================================
// LAYER 2: Verify `301` Canonical Redirects & Real `404` Soft-404 Protection
// =============================================================================
// Discover real Era, Chapter, and Part URLs dynamically from `katie` and `aubrie`
// so our tests stay valid even if chapter titles evolve over time.
$katieRoutes = json_decode((string)file_get_contents($booksRoot . '/data/routes/katie.json'), true) ?: [];
unset($katieRoutes['common']);
$samplePartUrl = '';
$sampleEraUrl = '/katie/toc';
$sampleChapUrl = '';
$katieToc = json_decode(
    (string)file_get_contents('/Users/michael/Developer/raggiesoft-servers/raggiesoft-assets/raggiesoft-books/books/katie/toc.json'),
    true
);
if (!empty($katieToc['eras'][0]['era_url'])) {
    $sampleEraUrl = str_replace('/raggiesoft-books/books', '', (string)$katieToc['eras'][0]['era_url']);
}
if (!empty($katieToc['eras'])) {
    foreach ($katieToc['eras'] as $eItem) {
        foreach (($eItem['chapters'] ?? []) as $cItem) {
            if (!empty($cItem['parts']) && count($cItem['parts']) > 1 && $sampleChapUrl === '') {
                $sampleChapUrl = str_replace('/raggiesoft-books/books', '', (string)$cItem['chap_url']);
                $firstFp = (string)($cItem['parts'][0]['file_path'] ?? '');
                foreach ($katieRoutes as $rKey => $rVal) {
                    if (is_array($rVal) && ($rVal['filePath'] ?? '') === $firstFp) {
                        $samplePartUrl = (string)$rKey;
                        break;
                    }
                }
            }
        }
    }
}

// Find a single-part chapter route & the Carnaby Street / "The Hollow Square" scene from `aubrie`
$aubrieRoutes = json_decode((string)file_get_contents($booksRoot . '/data/routes/aubrie.json'), true) ?: [];
unset($aubrieRoutes['common']);
$hollowSquareBookSceneUrl = '';
$singlePartCanonicalUrl = '';
foreach ($aubrieRoutes as $rUrl => $rMeta) {
    if (!is_array($rMeta)) {
        continue;
    }
    if (str_contains((string)($rMeta['filePath'] ?? ''), 'e005/c004/p002.md')) {
        $hollowSquareBookSceneUrl = (string)$rUrl;
    }
    if ($singlePartCanonicalUrl === '' && substr_count((string)$rUrl, '/') === 3 && str_ends_with((string)($rMeta['filePath'] ?? ''), '/p001.md')) {
        $singlePartCanonicalUrl = (string)$rUrl;
    }
}

// Verify trailing-slash, `/books/...`, platform-alias, and `/part-1-...` URLs return HTTP `301`
$redirectRoutes = [
    '/katie/',
    '/books/fractured-prisms',
    '/catalog',
    '/word-counts',
];
if ($singlePartCanonicalUrl !== '') {
    $redirectRoutes[] = $singlePartCanonicalUrl . '/part-1-legacy-test';
}
foreach ($redirectRoutes as $redirUri) {
    $res = renderBookRoute($redirUri);
    assertSeo(
        $res['status'] === 301,
        "Expected HTTP 301 redirect for '{$redirUri}', got {$res['status']}"
    );
}

// Verify unknown book/chapter URLs return HTTP `404` + `noindex, follow` + zero `<link rel="canonical">`
$notFoundRoutes = [
    '/nonexistent-book-slug-xyz',
    '/katie/nonexistent-era-xyz/nonexistent-chap',
];
foreach ($notFoundRoutes as $nfUri) {
    $res = renderBookRoute($nfUri);
    assertSeo(
        $res['status'] === 404,
        "Expected HTTP 404 for unknown route '{$nfUri}', got {$res['status']}"
    );
    assertSeo(
        str_contains($res['html'], 'content="noindex, follow"'),
        "Expected noindex, follow robots tag on 404 route '{$nfUri}'"
    );
    assertSeo(
        !str_contains($res['html'], 'rel="canonical"'),
        "404 route '{$nfUri}' must not emit <link rel=\"canonical\">"
    );
}

// =============================================================================
// LAYER 3: Validate SSR HTML, Metadata, JSON-LD & Cross-Site Links on Live Views
// =============================================================================
$representativeRoutes = array_values(array_filter([
    '/',
    '/library',
    '/stats',
    '/illustrations',
    '/fractured-prisms',
    '/stardust-engine',
    '/katie',
    '/katie/toc',
    '/katie/illustrations',
    $sampleEraUrl,
    $sampleChapUrl,
    $samplePartUrl,
    $hollowSquareBookSceneUrl,
]));

$seenTitles = [];
$seenDescriptions = [];

foreach ($representativeRoutes as $uri) {
    $res = renderBookRoute($uri);
    $html = $res['html'];

    assertSeo($res['status'] === 200, "Expected HTTP 200 for '{$uri}', got {$res['status']}");

    // 1. `<title>` must be non-empty and unique across every tested page
    $hasTitle = preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
    $titleText = $hasTitle ? html_entity_decode(trim($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    assertSeo($titleText !== '', "Missing <title> on '{$uri}'");
    if ($titleText !== '') {
        $prevTitleUri = $seenTitles[$titleText] ?? '';
        assertSeo(
            !isset($seenTitles[$titleText]),
            "Duplicate <title> '{$titleText}' shared by '{$uri}' and '{$prevTitleUri}'"
        );
        $seenTitles[$titleText] = $uri;
    }

    // 2. `<meta name="description">` must be non-empty, unique, and `<= 155` characters
    $hasDesc = preg_match('/<meta\s+name="description"\s+content="([^"]*)"/i', $html, $descMatch);
    $descText = $hasDesc ? html_entity_decode(trim($descMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    assertSeo($descText !== '', "Missing <meta name=\"description\"> on '{$uri}'");
    if ($descText !== '') {
        $descLen = mb_strlen($descText, 'UTF-8');
        assertSeo(
            $descLen <= 155,
            "<meta name=\"description\"> exceeds 155 chars ({$descLen}) on '{$uri}': {$descText}"
        );
        $prevDescUri = $seenDescriptions[$descText] ?? '';
        assertSeo(
            !isset($seenDescriptions[$descText]),
            "Duplicate <meta name=\"description\"> shared by '{$uri}' and '{$prevDescUri}'"
        );
        $seenDescriptions[$descText] = $uri;
    }

    // 3. `<link rel="canonical">` must match the exact requested `https://books.raggiesoft.com<uri>`
    $expectedCanonical = $uri === '/' ? 'https://books.raggiesoft.com/' : 'https://books.raggiesoft.com' . $uri;
    $hasCanon = preg_match('/<link\s+rel="canonical"\s+href="([^"]*)"/i', $html, $canonMatch);
    $canonHref = $hasCanon ? trim($canonMatch[1]) : '';
    assertSeo(
        $canonHref === $expectedCanonical,
        "Canonical mismatch on '{$uri}': expected '{$expectedCanonical}', got '{$canonHref}'"
    );

    // 4. OpenGraph `og:title` must be `<= 60` characters & `og:image:alt` must be present for accessibility
    if (preg_match('/<meta\s+property="og:title"\s+content="([^"]*)"/i', $html, $ogTitleMatch)) {
        $ogTitleDecoded = html_entity_decode(trim($ogTitleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $ogLen = mb_strlen($ogTitleDecoded, 'UTF-8');
        assertSeo($ogLen <= 60, "og:title exceeds 60 chars ({$ogLen}) on '{$uri}': {$ogTitleDecoded}");
    } else {
        assertSeo(false, "Missing og:title on '{$uri}'");
    }
    assertSeo(
        (bool)preg_match('/<meta\s+property="og:image:alt"\s+content="[^"]+"/i', $html),
        "Missing non-empty og:image:alt on '{$uri}'"
    );

    // 5. Exactly 1 `<h1>` heading per document (critical for screen-reader document outline & SEO)
    $h1Count = preg_match_all('/<h1[\s>]/i', $html);
    assertSeo($h1Count === 1, "Expected exactly 1 <h1> on '{$uri}', found {$h1Count}");

    // 6. Every `<script type="application/ld+json">` block must parse as valid JSON
    $jsonLdCount = preg_match_all('/<script\s+type="application\/ld\+json">(.*?)<\/script>/is', $html, $ldMatches);
    assertSeo($jsonLdCount >= 1, "Missing application/ld+json structured data on '{$uri}'");
    foreach (($ldMatches[1] ?? []) as $idx => $rawJson) {
        $decodedLd = json_decode(trim($rawJson), true);
        assertSeo(
            is_array($decodedLd),
            "Invalid JSON-LD block #{$idx} on '{$uri}': " . json_last_error_msg()
        );
    }

    // 7. Zero legacy `?track=` or `/books/<slug>` links in rendered HTML
    assertSeo(
        !preg_match('/href="https:\/\/music\.raggiesoft\.com\/[^"]*\?track=/i', $html),
        "Found non-canonical ?track= link in rendered HTML of '{$uri}'"
    );
    assertSeo(
        !preg_match('/href="https:\/\/books\.raggiesoft\.com\/books\//i', $html),
        "Found legacy /books/ prefix link in rendered HTML of '{$uri}'"
    );

    // 8. Verify cross-site canonical song link to The Listening Room ("The Hollow Square")
    if ($uri === '/fractured-prisms' || $uri === $hollowSquareBookSceneUrl) {
        assertSeo(
            str_contains($html, 'https://music.raggiesoft.com/fractured-prisms/1983-carnaby-street/the-hollow-square'),
            "Expected direct canonical link to '/fractured-prisms/1983-carnaby-street/the-hollow-square' on '{$uri}'"
        );
    }
}

// =============================================================================
// FINAL REPORT & EXIT CODE
// =============================================================================
// Exit code `1` fails CI / deployment pipelines; exit code `0` (implicit at end of script) signals PASS!
if (!empty($failures)) {
    fwrite(STDERR, "❌ RaggieSoft Book Reader SEO Validation FAILED (" . count($failures) . " issue(s)):\n");
    foreach ($failures as $f) {
        fwrite(STDERR, "  - {$f}\n");
    }
    exit(1);
}

echo sprintf(
    "✅ Book Reader SEO Validation PASSED: %d assertions, %d canonical sitemap URLs, %d representative SSR routes verified (0 duplicate titles/descriptions, 0 ?track= links, 301/404 status codes verified).\n",
    $checksRun,
    count($allSitemapUrls),
    count($representativeRoutes)
);
