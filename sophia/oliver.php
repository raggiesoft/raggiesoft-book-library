<?php
/*
 * ============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/oliver.php (THE CORE BOOK READER ENGINE)
 * ============================================================================
 * WHAT PAGES & VIEWS THIS FILE RENDERS:
 *     Named after "Oliver" (the fourth sibling in the Ocean View Archives lore),
 *     this 3,400+-line controller + view template is the heart of
 *     `https://books.raggiesoft.com`. Whenever a visitor opens ANY URL belonging
 *     to a novel (`/<series>`, `/<series>/toc`, `/<series>/illustrations`,
 *     `/<series>/<era>`, `/<series>/<era>/<chapter>`, or a deep story manuscript
 *     part `/<series>/<era>/<chapter>/<part>`), `eleanor/isabel.php` renders
 *     `sophia/sidebar.php` on the left and hands control to `sophia/oliver.php`
 *     to render `<main id="stardust-reading-pane">`!
 *
 * VARIABLES & JSON MANIFESTS FED INTO `oliver.php` FROM `eleanor/isabel.php`:
 *     - `$requestUri`: Clean URL path requested by the browser (e.g. `/rachel/...`).
 *     - `$basePath`: Server filesystem root of the `books.raggiesoft.com` app.
 *     - `$cdnBaseUrl`: Asset CDN origin (`https://assets.raggiesoft.com`).
 *     - `$toc_manifest`: Decoded `toc.json` for the active series (Eras, Chapters,
 *       Parts, Story Arcs, Story Crossroads graph, and `word_stats`).
 *     - `$prefetchedMdContent`: Raw `.md` manuscript already loaded by `isabel.php`
 *       when computing `<head>` OpenGraph tags—reused here so we never read disk twice!
 *     - `$activeStoryPathId`, `$activeSegmentIdSet`, `$activeCrossroadsChoices`:
 *       Active **RaggieSoft Story Crossroads** branching choices resolved by
 *       `isabel.php` from the `rs_cx_<series>` cookie / `?path=` query string.
 *     - `$fileToUrl`: Reverse lookup dictionary (`filePath => cleanUrl`) built
 *       just moments earlier by `sophia/sidebar.php`.
 *
 * COMPLETE MAP OF SUBSYSTEMS INSIDE THIS FILE (TOP TO BOTTOM):
 *     1. Route Lookup & Trauma-Informed 404 Card (`data/routes/<series>.json` +
 *        `renderOliverNotFoundCard()`).
 *     2. Sub-View Dispatcher (`__SERIES_LANDING__` -> `landing.php`, `__TOC__` ->
 *        `toc.php`, `__ILLUSTRATIONS__` -> `illustrations.php`, `__ERA_TOC__` ->
 *        `era-toc.php`, `__CHAP_TOC__` -> `chap-toc.php`) & Markdown File Loader.
 *     3. Custom YAML Frontmatter Parser (`--- ... ---` -> `$frontmatter` array).
 *     4. `StardustParsedown` Markdown-to-HTML Renderer, `## Heading` In-Scene
 *        Mini-TOC Extractor (`$sceneSubsections`), and Wikipedia-Style Inline
 *        Link Preview Enricher (`data-rs-preview` across Books, Music, & Portals).
 *     5. Sequential `Previous` / `Next` Resolver (filtering out inactive Story
 *        Crossroads segments and rewiring for `?arc=<slug>` Story Arc Playlists).
 *     6. Schema.org Structured Data Graph (`BookSeries` + `Book`/`Chapter` +
 *        `BreadcrumbList` JSON-LD) & Atmospheric Narrative Themes (`dark-rain`).
 *     7. Reading Pane Typography & Theme Inheritance (`#stardust-reading-pane` +
 *        `.story-content`, driven by CSS Custom Properties `--rs-font-family`,
 *        `--rs-font-size`, `--rs-line-height`, measure width, and `theme-*` classes
 *        persisted in `localStorage['reader-settings']` via `settings.php` & `reader.js`).
 *     8. Scene Hero Plate, Multi-Slide 16:9 Hero Image Carousel (keyboard + touch
 *        swipe), and Locale-Aware Temporal Chronology (`stardate`, `realm_time`,
 *        and ISO-8601 `DateTime::ATOM` timezone conversion).
 *     9. Persistent Soundtrack `<audio>` Queue Engine & Lyrics/WebVTT Architecture
 *        (bridging `#narrative-audio-element` SSR metadata to the live `#stardust-bgm`
 *        player in `header.php`, `.md` lyrics modal, and The Listening Room's
 *        time-synced `.vtt` WebVTT lyric cues).
 *    10. RaggieSoft Backstage Bonus-Dossier Drawer (`#rs-backstage-callout` +
 *        `<dialog id="rs-backstage-dialog">` hydrated via CORS `fetch()` against
 *        `https://backstage.raggiesoft.com/api/v1/<slug>.json`).
 *    11. Coequal **Story Crossroads** Interactive Route Cards (`#rs-story-crossroads`)
 *        & Mid-Novel Story Path Switcher (`<dialog id="rs-story-crossroads-dialog">`).
 *    12. Offline EPUB/PDF Download Card (`#rs-offline-cta-card`), Social Share Bar,
 *        Zoë Patel's OS-Adaptive `ZOË.Systems.Logs` Syntax Highlighter CSS, `<led>`
 *        Transit Destination Signs, and the Inline Link Hovercard JS Controller!
 * ============================================================================
 */

// ============================================================================
// SUBSYSTEM 1: ROUTE MANIFEST RESOLUTION (`data/routes/<seriesSlug>.json`)
// ============================================================================
// Trim any trailing slash from `$requestUri` and split on `'/'` so segment `[0]`
// gives us the novel's URL slug (e.g. `"/rachel/era-1/..."` -> `$seriesSlug = "rachel"`).
$requestUri = rtrim($requestUri, '/');
$parts = explode('/', ltrim($requestUri, '/'));
$seriesSlug = $parts[0] ?? '';

// Load the pre-compiled route table generated by `paige.php` for this series.
// Each URL key in `<seriesSlug>.json` maps to `['title' => '...', 'filePath' => 'e001/c001/p001.md', ...]`.
$routeFile = $basePath . '/data/routes/' . $seriesSlug . '.json';
$routeData = [];
$config = [];
$actualFilePath = '';

if (file_exists($routeFile)) {
    $routeData = json_decode(file_get_contents($routeFile), true);
    if (isset($routeData[$requestUri])) {
        $config = $routeData[$requestUri];
        if (isset($config['filePath'])) {
            $actualFilePath = $config['filePath'];
        }
    } elseif (strpos($requestUri, '/part-1-') !== false) {
        // Legacy fallback: single-part chapters strip `/part-1-<slug>` down to `/<slug>`
        $strippedPart1Uri = preg_replace('#/part-1-([^/]+)$#', '/$1', $requestUri);
        if (isset($routeData[$strippedPart1Uri])) {
            $config = $routeData[$strippedPart1Uri];
            if (isset($config['filePath'])) {
                $actualFilePath = $config['filePath'];
            }
        }
    }
}

/**
 * Renders an accessible, trauma-informed "Chapter or Part Not Found" navigation card
 * inside `#stardust-reading-pane`, inviting the reader to visit the Book's Homepage,
 * the Library Catalog (`/library`), or the Ocean View Archives Homepage (`/`).
 *
 * @param string     $seriesSlug   Active book slug from the first URL segment (if any).
 * @param array|null $toc_manifest Loaded series TOC manifest (if any).
 * @param array      $routeData    Loaded series route manifest (if any).
 * @param string     $heading      Primary heading text.
 * @param string     $message      Explanatory message text.
 */
if (!function_exists('renderOliverNotFoundCard')) {
    function renderOliverNotFoundCard(string $seriesSlug, ?array $toc_manifest, array $routeData, string $heading, string $message): void {
        $hasValidBook = ($seriesSlug !== '' && (!empty($routeData) || !empty($toc_manifest)));
        $bookTitle = '';
        if ($hasValidBook) {
            if (!empty($toc_manifest['series_title'])) {
                $bookTitle = (string)$toc_manifest['series_title'];
            } elseif (!empty($routeData['/' . $seriesSlug]['title'])) {
                $bookTitle = (string)$routeData['/' . $seriesSlug]['title'];
            } else {
                $bookTitle = ucwords(str_replace('-', ' ', $seriesSlug));
            }
        }
        $safeSlug = htmlspecialchars($seriesSlug, ENT_QUOTES, 'UTF-8');
        $safeBookTitle = htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8');
        $safeHeading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        ?>
        <main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; height: 100dvh; overflow-y: auto;">
            <div class="book-page reader-page" style="max-width: 800px; margin: 0 auto; padding-top: max(2rem, calc(env(safe-area-inset-top, 0px) + 1rem));">
                <div class="reader-nav" style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--rs-border);">
                    <div style="color: var(--rs-text-muted); font-size: 0.9rem; min-width: 0; flex: 1;">
                        <a href="/library" style="text-decoration: none; color: var(--rs-primary);">Library</a>
                        <?php if ($hasValidBook): ?>
                            &raquo; <a href="/<?php echo $safeSlug; ?>" style="text-decoration: none; color: var(--rs-primary); font-weight: 600;"><?php echo $safeBookTitle; ?></a>
                        <?php endif; ?>
                    </div>
                    <div style="flex-shrink: 0;">
                        <button id="stardust-sidebar-toggle" class="rs-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem; white-space: nowrap;">
                            <i class="ph ph-list" aria-hidden="true"></i> Menu
                        </button>
                    </div>
                </div>

                <section aria-labelledby="not-found-heading" style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 2rem 1.75rem; box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);">
                    <div style="display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-primary); margin-bottom: 0.65rem;">
                        <i class="ph ph-compass" aria-hidden="true" style="font-size: 1rem;"></i>
                        <span>Ocean View Archives Navigation</span>
                    </div>
                    <h1 id="not-found-heading" style="margin: 0 0 0.75rem 0; font-size: 1.85rem; line-height: 1.25; color: var(--rs-text);">
                        <?php echo $safeHeading; ?>
                    </h1>
                    <p style="margin: 0 0 1.5rem 0; font-size: 1.05rem; line-height: 1.65; color: var(--rs-text); opacity: 0.88;">
                        <?php echo $safeMessage; ?>
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if ($hasValidBook): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                                <a href="/<?php echo $safeSlug; ?>" class="rs-btn rs-btn-brand" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.15rem; font-weight: 600;">
                                    <i class="ph ph-book-open" aria-hidden="true"></i>
                                    <span><?php echo $safeBookTitle; ?> Homepage</span>
                                </a>
                                <a href="/<?php echo $safeSlug; ?>/toc" class="rs-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.15rem; font-weight: 600;">
                                    <i class="ph ph-list-bullets" aria-hidden="true"></i>
                                    <span><?php echo $safeBookTitle; ?> Table of Contents</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                            <a href="/library" class="rs-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.15rem; font-weight: 600;">
                                <i class="ph ph-books" aria-hidden="true"></i>
                                <span>Browse the Library Catalog</span>
                            </a>
                            <a href="/" class="rs-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.15rem; font-weight: 600;">
                                <i class="ph ph-house" aria-hidden="true"></i>
                                <span>Ocean View Archives Homepage</span>
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
        <style>
        #stardust-reading-pane { padding-bottom: 6rem !important; }
        </style>
        <?php
    }
}

// Exit early with a helpful navigation invitation if the route does not exist
if (empty($actualFilePath)) {
    renderOliverNotFoundCard(
        (string)$seriesSlug,
        isset($toc_manifest) && is_array($toc_manifest) ? $toc_manifest : null,
        is_array($routeData) ? $routeData : [],
        'Chapter or Part Not Found',
        'This bookmark or link may point to a page that was recently reorganized into a new Chapter or Part. Where would you like to head next?'
    );
    return;
}

// ============================================================================
// SUBSYSTEM 2: SPECIAL SUB-VIEW DISPATCHER & MARKDOWN MANUSCRIPT FETCHER
// ============================================================================
// `$actualFilePath` is either a sentinel token (`__SERIES_LANDING__`, `__TOC__`,
// `__ILLUSTRATIONS__`, `__ERA_TOC__|...`, `__CHAP_TOC__|...`) OR a relative
// Markdown manuscript path like `e001/c001/p001.md` (or `paths/<optId>/e002/...`).
$specialPageType = null;
$specialPagePathId = null;
$specialPageIndex1 = 0;
$specialPageIndex2 = 0;
$mdContent = '';

// Check sentinel tokens first; pipe-delimited tokens (`|`) carry Story Crossroads `pathId` and 0-based Era/Chapter indices
if ($actualFilePath === '__SERIES_LANDING__') {
    $specialPageType = 'landing';
} elseif ($actualFilePath === '__TOC__') {
    $specialPageType = 'toc';
} elseif ($actualFilePath === '__ILLUSTRATIONS__') {
    $specialPageType = 'illustrations';
} elseif (strpos($actualFilePath, '__ERA_TOC__|') === 0) {
    $p = explode('|', $actualFilePath);
    $specialPageType = 'era_toc';
    if (count($p) >= 3) {
        $specialPagePathId = $p[1];
        $specialPageIndex1 = (int)$p[2];
    } else {
        $specialPageIndex1 = (int)($p[1] ?? 0);
    }
} elseif (strpos($actualFilePath, '__CHAP_TOC__|') === 0) {
    $p = explode('|', $actualFilePath);
    $specialPageType = 'chap_toc';
    if (count($p) >= 4) {
        $specialPagePathId = $p[1];
        $specialPageIndex1 = (int)$p[2];
        $specialPageIndex2 = (int)$p[3];
    } else {
        $specialPageIndex1 = (int)($p[1] ?? 0);
        $specialPageIndex2 = (int)($p[2] ?? 0);
    }
} else {
    // Story scene `.md` loading pipeline:
    // 1. Reuse `$prefetchedMdContent` if `eleanor/isabel.php` already read the file during SEO `<head>` setup!
    // 2. Otherwise check local NVMe `raggiesoft-assets`, then `raggiesoft-narratives`, then remote `$cdnBaseUrl`.
    if (isset($prefetchedMdContent) && $prefetchedMdContent !== null) {
        $mdContent = $prefetchedMdContent;
    } else {
        $oliverServerRoot = is_dir(dirname($basePath, 3) . '/raggiesoft-assets')
            ? dirname($basePath, 3)
            : (is_dir(dirname($basePath, 1) . '/raggiesoft-assets') ? dirname($basePath, 1) : dirname($basePath, 3));
        $localAssetMd = $oliverServerRoot . '/raggiesoft-assets/raggiesoft-books/books/' . $seriesSlug . '/' . ltrim($actualFilePath, '/');
        $localNarrMd  = $oliverServerRoot . '/raggiesoft-narratives/books/' . $seriesSlug . '/' . ltrim($actualFilePath, '/');
        if (is_file($localAssetMd)) {
            $mdContent = @file_get_contents($localAssetMd);
        } elseif (is_file($localNarrMd)) {
            $mdContent = @file_get_contents($localNarrMd);
        } else {
            $mdUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/' . ltrim($actualFilePath, '/');
            $mdContent = @file_get_contents($mdUrl);
        }
        if ($mdContent !== false) {
            // Expand author-time `{{CDN}}` / `{{cms}}` placeholders into the live CDN URL (`https://assets.raggiesoft.com`)
            $mdContent = str_replace(['{{CDN}}', '{{cms}}'], $cdnBaseUrl, $mdContent);
        }
    }
}

// Guard clause: if the physical `.md` file could not be read from disk or CDN, render the 404 navigation card
if ($mdContent === false && !$specialPageType) {
    renderOliverNotFoundCard(
        (string)$seriesSlug,
        isset($toc_manifest) && is_array($toc_manifest) ? $toc_manifest : null,
        is_array($routeData) ? $routeData : [],
        'Part Manuscript Not Found',
        'We could not load this manuscript file from the Ocean View Archives right now. You can return to the book overview, browse the library catalog, or head back to the homepage below.'
    );
    return;
}

// ============================================================================
// SUBSYSTEM 3: CUSTOM YAML FRONTMATTER PARSER (`--- ... ---`)
// ============================================================================
// Every story Markdown file starts with a metadata header wrapped between `---` lines:
//   ---
//   title: "Part 1: ..."
//   date: "May 21, 2016"
//   audio: "the-stardust-engine/2016-live-at-the-crucible/..."
//   backstage:
//     - "books/aubrie/..."
//   ---
// We slice that header off `$mdContent` so raw YAML never prints in the story,
// and parse both scalar `key: value` pairs and bulleted `- item` YAML lists into `$frontmatter`.
$frontmatter = [];
if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $mdContent, $matches)) {
    $rawFrontmatter = $matches[1];
    $mdContent = substr($mdContent, strlen($matches[0])); // Strip frontmatter block from the prose body
    
    $lines = explode("\n", $rawFrontmatter);
    $currentArrayKey = null;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;
        
        // Case A: YAML list item (`- "value"`) belonging to the active `$currentArrayKey`
        if (strpos($trimmed, '-') === 0 && $currentArrayKey) {
            $val = trim(substr($trimmed, 1));
            $val = trim($val, '"\'');
            $frontmatter[$currentArrayKey][] = $val;
        } 
        // Case B: `key: value` or `key:` (opening a multi-line YAML array)
        elseif (strpos($trimmed, ':') !== false) {
            list($key, $val) = explode(':', $trimmed, 2);
            $key = trim($key);
            $val = trim($val);
            if ($val === '') {
                $currentArrayKey = $key;
                $frontmatter[$currentArrayKey] = [];
            } else {
                $val = trim($val, '"\'');
                $frontmatter[$key] = $val;
                $currentArrayKey = null;
            }
        } else {
            $currentArrayKey = null;
        }
    }
}

// ============================================================================
// IMMUTABLE SEPTEMBER 11, 2001 ZERO-IMAGES FATAL GUARDRAIL (`oliver.php`)
// ============================================================================
// Runtime SSR enforcement on `books.raggiesoft.com`: if any story section set on
// September 11, 2001 ever reaches the live viewer with YAML `image` / `images` /
// `og_image` / `ogImage` metadata, inline Markdown `![...](...)`, or raw `<img`
// HTML, immediately respond HTTP 500 and throw an uncaught `RuntimeException`!
if (!$specialPageType && !empty($frontmatter['date']) && is_string($frontmatter['date'])) {
    if (preg_match('/(?:\b2001-0?9-11\b|\b0?9[\/\-]11[\/\-]2001\b|\bSept(?:ember)?\.?\s+11(?:th)?,?\s+2001\b|\b11\s+Sept(?:ember)?\.?,?\s+2001\b)/i', trim($frontmatter['date']))) {
        $oliverHasFmImg = !empty($frontmatter['image']) || !empty($frontmatter['images']) || !empty($frontmatter['og_image']) || !empty($frontmatter['ogImage']);
        $oliverBodyNoCode = (string)preg_replace('/```[\s\S]*?```/', '', (string)$mdContent);
        $oliverHasInlineImg = (bool)preg_match('/!\[[^\]]*\]\([^)]+\)|<img\b/i', $oliverBodyNoCode);
        if ($oliverHasFmImg || $oliverHasInlineImg) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            throw new RuntimeException('FATAL GUARDRAIL ERROR [IMMUTABLE SEPTEMBER 11, 2001 ZERO-IMAGES RULE]: Story sections taking place on September 11, 2001 are permanently prohibited from rendering images.');
        }
    }
}

// ============================================================================
// SUBSYSTEM 4: MARKDOWN RENDERING, IN-SCENE MINI-TOC, & WIKI LINK PREVIEWS
// ============================================================================
// `StardustParsedown` extends standard Parsedown to handle custom narrative tags
// such as AAC speech synthesis blocks, `<led>` bus destination signs, `<pa>`
// transit announcements, `.foreign-language` tap-to-translate spans, and
// Zoë Patel's `ZOË.Systems.Logs` diagnostic blocks!
require_once $basePath . '/includes/classes/stardust-parsedown.php';
$Parsedown = new StardustParsedown('vanilla');
$sceneSubsections = [];
if ($specialPageType) {
    // PHP Output Buffering (`ob_start()` -> `ob_get_clean()`): captures all `echo`ed HTML from
    // `landing.php`, `toc.php`, `illustrations.php`, `era-toc.php`, or `chap-toc.php` into `$htmlContent`!
    ob_start();
    if ($specialPageType === 'landing') {
        require_once __DIR__ . '/landing.php';
    } elseif ($specialPageType === 'toc') {
        require_once __DIR__ . '/toc.php';
    } elseif ($specialPageType === 'illustrations') {
        require_once __DIR__ . '/illustrations.php';
    } elseif ($specialPageType === 'era_toc') {
        require_once __DIR__ . '/era-toc.php';
    } elseif ($specialPageType === 'chap_toc') {
        require_once __DIR__ . '/chap-toc.php';
    }
    $htmlContent = ob_get_clean();
} else {
    // Parse Standard Markdown
    $htmlContent = $Parsedown->text($mdContent);

    // Detect `## Heading` (`<h2>`) sub-headers (commonly placed after `***` scene breaks)
    // to inject deterministic anchor IDs and build the In-Scene Mini Table of Contents.
    $subHeadingCounter = 0;
    $htmlContent = preg_replace_callback(
        '/<h2(?:\s+[^>]*)?>(.*?)<\/h2>/is',
        function ($m) use (&$sceneSubsections, &$subHeadingCounter) {
            $subHeadingCounter++;
            $innerHtml = $m[1];
            $plainLabel = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($innerHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $slugPart = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $plainLabel), '-'));
            if ($slugPart === '') {
                $slugPart = 'section';
            }
            if (strlen($slugPart) > 48) {
                $slugPart = rtrim(substr($slugPart, 0, 48), '-');
            }
            $secId = 'sec-' . $subHeadingCounter . '-' . $slugPart;
            $sceneSubsections[] = [
                'num' => $subHeadingCounter,
                'id' => $secId,
                'label' => $plainLabel !== '' ? $plainLabel : ('Section ' . $subHeadingCounter),
            ];
            return '<h2 id="' . htmlspecialchars($secId, ENT_QUOTES, 'UTF-8') . '" class="reader-scene-subheading" tabindex="-1">' . $innerHtml . '</h2>';
        },
        $htmlContent
    );

    // Enrich inline Markdown links (`<a href="...">`) with Wikipedia-style page preview metadata
    // (`data-rs-preview` + `interestfor` popover hint) for both Desktop hover/focus and Touch-First tap-to-preview.
    $linkPreviewsFile = $basePath . '/data/link-previews.json';
    $linkPreviewsMap = is_file($linkPreviewsFile)
        ? (json_decode((string)@file_get_contents($linkPreviewsFile), true) ?: [])
        : [];
    $localAssetsRoot = is_dir(dirname($basePath, 3) . '/raggiesoft-assets')
        ? dirname($basePath, 3) . '/raggiesoft-assets'
        : (is_dir(dirname($basePath, 1) . '/raggiesoft-assets')
            ? dirname($basePath, 1) . '/raggiesoft-assets'
            : dirname($basePath, 3) . '/raggiesoft-assets');
    $wikiLinkCounter = 0;

    $htmlContent = preg_replace_callback(
        '/<a\s+([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>(.*?)<\/a>/is',
        function ($m) use (&$wikiLinkCounter, $linkPreviewsMap, $cdnBaseUrl, $localAssetsRoot) {
            $beforeHref = $m[1];
            $quote = $m[2];
            $rawHref = trim(html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $afterHref = $m[4];
            $innerHtml = $m[5];

            // Skip empty, same-page hash, mailto, or non-prose links
            if ($rawHref === '' || strpos($rawHref, '#') === 0 || strpos($rawHref, 'mailto:') === 0 || strpos($rawHref, 'javascript:') === 0) {
                return $m[0];
            }

            $parsed = @parse_url($rawHref);
            $host = strtolower($parsed['host'] ?? '');
            $path = '/' . ltrim($parsed['path'] ?? '', '/');
            $path = ($path !== '/') ? rtrim($path, '/') : '/';
            $query = $parsed['query'] ?? '';
            $fragment = isset($parsed['fragment']) && $parsed['fragment'] !== '' ? ('#' . $parsed['fragment']) : '';

            $previewPayload = null;
            $finalHref = $rawHref;
            $isExternal = false;

            // Case 1: Ocean View Archives internal book/chapter/scene link (books.raggiesoft.com or root-relative)
            if ($host === '' || $host === 'books.raggiesoft.com') {
                $lookupPath = preg_replace('#^/(?:raggiesoft-)?books/#', '/', $path);
                $finalHref = $lookupPath . ($query !== '' ? '?' . $query : '') . $fragment;
                $entry = $linkPreviewsMap[$lookupPath] ?? null;
                if (!$entry && strpos($lookupPath, '/part-1-') !== false) {
                    $strippedPath = preg_replace('#/part-1-([^/]+)$#', '/$1', $lookupPath);
                    if (isset($linkPreviewsMap[$strippedPath])) {
                        $entry = $linkPreviewsMap[$strippedPath];
                        $finalHref = $strippedPath . ($query !== '' ? '?' . $query : '') . $fragment;
                    }
                }
                if ($entry) {
                    $imgSrc = $entry['image'] ?? '';
                    if ($imgSrc !== '' && strpos($imgSrc, 'http') !== 0) {
                        $imgSrc = rtrim($cdnBaseUrl, '/') . '/' . ltrim($imgSrc, '/');
                    }
                    $kindLabel = ($entry['kind'] ?? 'scene') === 'gallery'
                        ? 'Open Illustrations Gallery →'
                        : (($entry['kind'] ?? 'scene') === 'book' ? 'Open Novel Overview →' : 'Read Scene →');
                    $previewPayload = [
                        'kind' => $entry['kind'] ?? 'scene',
                        'kicker' => $entry['kicker'] ?? 'Ocean View Archives',
                        'title' => $entry['title'] ?? strip_tags($innerHtml),
                        'date' => $entry['date'] ?? '',
                        'location' => $entry['location'] ?? '',
                        'image' => $imgSrc,
                        'excerpt' => $entry['excerpt'] ?? '',
                        'url' => $finalHref,
                        'cta' => $kindLabel,
                        'external' => false
                    ];
                }
            }
            // Case 2: The Listening Room companion audio link (music.raggiesoft.com)
            // Automatically normalizes legacy `?track=` links to clean canonical `/[artist]/[album]/[track]` URLs
            elseif ($host === 'music.raggiesoft.com') {
                $isExternal = true;
                // Normalize any legacy `/artists/<artist>/...` path prefix to `/<artist>/...`
                $normMusicPath = preg_replace('#^/artists/#', '/', $path);
                $seg = array_values(array_filter(explode('/', trim((string)$normMusicPath, '/'))));
                $artistSlug = $seg[0] ?? '';
                $albumSlug = $seg[1] ?? '';
                $pathTrackSlug = ($artistSlug !== 'playlists' && isset($seg[2])) ? $seg[2] : '';
                $queryTrackParam = '';
                if ($query !== '') {
                    parse_str($query, $qVars);
                    if (!empty($qVars['track'])) {
                        $queryTrackParam = (string)$qVars['track'];
                    }
                }
                $trackSlug = $pathTrackSlug !== '' ? $pathTrackSlug : $queryTrackParam;

                // Derive clean canonical song slug when a legacy `?track=` filename or ID was supplied
                $cleanCanonicalTrackSlug = '';
                if ($trackSlug !== '' && $artistSlug !== '' && $albumSlug !== '' && $artistSlug !== 'playlists') {
                    $tmpSlug = preg_replace('/\.mp3$/i', '', $trackSlug);
                    // Handle legacy `<artist>-<album>-<disc>-<track>` IDs if encountered
                    $idPrefix = $artistSlug . '-' . $albumSlug . '-';
                    if (stripos((string)$tmpSlug, $idPrefix) === 0) {
                        $tmpSlug = substr((string)$tmpSlug, strlen($idPrefix));
                    }
                    $tmpSlug = preg_replace('/^\d+-\d+-/', '', (string)$tmpSlug);
                    $tmpSlug = preg_replace('/^\d+-/', '', (string)$tmpSlug);
                    $cleanCanonicalTrackSlug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', (string)$tmpSlug), '-'));
                }

                // 1. Check pre-compiled `link-previews.json` (`music://...`) generated by Paige
                $musicEntry = null;
                $matchedSpecificTrackEntry = false;
                if ($cleanCanonicalTrackSlug !== '' && $artistSlug !== '' && $albumSlug !== '') {
                    $canonicalLookupKey = "music://{$artistSlug}/{$albumSlug}/{$cleanCanonicalTrackSlug}";
                    if (isset($linkPreviewsMap[$canonicalLookupKey])) {
                        $musicEntry = $linkPreviewsMap[$canonicalLookupKey];
                        $matchedSpecificTrackEntry = true;
                    }
                }
                if (!$musicEntry && $queryTrackParam !== '' && $artistSlug !== '' && $albumSlug !== '') {
                    $legacyLookupKey = "music://{$artistSlug}/{$albumSlug}?track={$queryTrackParam}";
                    if (isset($linkPreviewsMap[$legacyLookupKey])) {
                        $musicEntry = $linkPreviewsMap[$legacyLookupKey];
                        $matchedSpecificTrackEntry = true;
                    }
                }
                if (!$musicEntry) {
                    $baseLookupKey = 'music://' . ltrim((string)$normMusicPath, '/');
                    if (isset($linkPreviewsMap[$baseLookupKey])) {
                        $musicEntry = $linkPreviewsMap[$baseLookupKey];
                    } elseif ($artistSlug !== '' && $albumSlug !== '' && isset($linkPreviewsMap["music://{$artistSlug}/{$albumSlug}"])) {
                        $musicEntry = $linkPreviewsMap["music://{$artistSlug}/{$albumSlug}"];
                    }
                }

                // Rewrite rendered href to the canonical `music.raggiesoft.com` song/album/artist route (no `?track=`)
                if ($matchedSpecificTrackEntry && !empty($musicEntry['url'])) {
                    $finalHref = $musicEntry['url'] . $fragment;
                } elseif ($cleanCanonicalTrackSlug !== '' && $artistSlug !== '' && $albumSlug !== '') {
                    $finalHref = "https://music.raggiesoft.com/{$artistSlug}/{$albumSlug}/{$cleanCanonicalTrackSlug}" . $fragment;
                } else {
                    if ($queryTrackParam !== '') {
                        parse_str($query, $cleanQVars);
                        unset($cleanQVars['track']);
                        $remainingQuery = http_build_query($cleanQVars);
                    } else {
                        $remainingQuery = $query;
                    }
                    $finalHref = 'https://music.raggiesoft.com'
                        . ($normMusicPath !== '/' ? $normMusicPath : '')
                        . ($remainingQuery !== '' ? '?' . $remainingQuery : '')
                        . $fragment;
                }

                if ($musicEntry) {
                    $musicImg = $musicEntry['image'] ?? '';
                    if ($musicImg !== '' && strpos($musicImg, 'http') !== 0) {
                        $musicImg = rtrim($cdnBaseUrl, '/') . '/' . ltrim($musicImg, '/');
                    }
                    $previewTitle = $musicEntry['title'] ?? trim(strip_tags($innerHtml));
                    // If we fell back to the album entry on a track link, use the cleaned track title
                    if ($trackSlug !== '' && !$matchedSpecificTrackEntry) {
                        $cleanTrack = ucwords(str_replace('-', ' ', $cleanCanonicalTrackSlug !== '' ? $cleanCanonicalTrackSlug : preg_replace('/^\d+-\d+-/', '', $trackSlug)));
                        if ($cleanTrack !== '') {
                            $previewTitle = $cleanTrack;
                        }
                    }
                    $previewPayload = [
                        'kind' => 'music',
                        'kicker' => $musicEntry['kicker'] ?? 'The Listening Room',
                        'title' => $previewTitle,
                        'date' => $musicEntry['date'] ?? 'Engine Room Records',
                        'location' => $musicEntry['location'] ?? '',
                        'image' => $musicImg,
                        'excerpt' => $musicEntry['excerpt'] ?? 'Stream in The Listening Room with synchronized lyrics, studio liner notes, and lossless master playback.',
                        'url' => $finalHref,
                        'cta' => 'Listen in The Listening Room ↗',
                        'external' => true
                    ];
                } else {
                    // 2. Zero-Local-Disk Dynamic Fallback: construct CDN artwork URL directly
                    // (Never check `$localAssetsRoot` on the droplet and never fall back to `stardust-engine.jpg`)
                    $artistLabel = $artistSlug !== ''
                        ? ucwords(str_replace('-', ' ', $artistSlug))
                        : 'Engine Room Records';
                    if ($artistSlug === 'the-stardust-engine') {
                        $artistLabel = 'The Stardust Engine';
                    } elseif ($artistSlug === 'the-architects') {
                        $artistLabel = 'The Architects';
                    } elseif ($artistSlug === 'the-hoshizora-ensemble') {
                        $artistLabel = 'The Hoshizora Ensemble';
                    } elseif ($artistSlug === 'the-paper-wall') {
                        $artistLabel = 'The Paper Wall';
                    }
                    $albumLabel = $albumSlug !== ''
                        ? ucwords(str_replace('-', ' ', preg_replace('/^\d{4}-/', '', $albumSlug)))
                        : 'Discography';
                    $albumYear = '';
                    if (preg_match('/^(\d{4})-/', $albumSlug, $ym)) {
                        $albumYear = $ym[1];
                    }
                    $cleanTrackSlug = preg_replace('/^\d+-\d+-/', '', $trackSlug);
                    $trackTitle = $cleanTrackSlug !== ''
                        ? ucwords(str_replace('-', ' ', $cleanTrackSlug))
                        : trim(strip_tags($innerHtml));

                    if ($artistSlug !== '' && $albumSlug !== '' && $artistSlug !== 'playlists') {
                        $musicImg = rtrim($cdnBaseUrl, '/') . "/engine-room-records/artists/{$artistSlug}/{$albumSlug}/social-preview.jpg";
                    } elseif ($artistSlug !== '' && $artistSlug !== 'playlists') {
                        $musicImg = rtrim($cdnBaseUrl, '/') . "/engine-room-records/artists/{$artistSlug}/social-preview.jpg";
                    } else {
                        $musicImg = rtrim($cdnBaseUrl, '/') . '/engine-room-records/images/err-hero.jpg';
                    }

                    $previewPayload = [
                        'kind' => 'music',
                        'kicker' => 'The Listening Room • ' . $artistLabel,
                        'title' => $trackTitle !== '' ? $trackTitle : $albumLabel,
                        'date' => $albumYear !== '' ? "Released {$albumYear}" : 'Engine Room Records',
                        'location' => $albumLabel,
                        'image' => $musicImg,
                        'excerpt' => 'Stream in The Listening Room with synchronized lyrics, studio liner notes, and lossless master playback.',
                        'url' => $finalHref,
                        'cta' => 'Listen in The Listening Room ↗',
                        'external' => true
                    ];
                }
            }
            // Case 3: Companion RaggieSoft Universe & Open-Source Project Properties
            elseif (in_array($host, ['stardust.raggiesoft.com', 'engineroom.raggiesoft.com', 'lore.raggiesoft.com', 'nebulae.raggiesoft.com', 'finsharp.raggiesoft.dev', 'raggiescript.raggiesoft.dev', 'raggiesoft.dev'], true)) {
                $isExternal = true;
                $siteTitleMap = [
                    'stardust.raggiesoft.com' => 'The Stardust Engine Official Portal',
                    'engineroom.raggiesoft.com' => 'Engine Room Records Archive',
                    'lore.raggiesoft.com' => 'RaggieSoft Lore & World Guide',
                    'nebulae.raggiesoft.com' => 'Caelum-IV Planetary Survey (Nebulae)',
                    'finsharp.raggiesoft.dev' => 'Fin# — Open-Source Language Born in The Eleventh Child',
                    'raggiescript.raggiesoft.dev' => 'RaggieScript — Open-Source Language Portal',
                    'raggiesoft.dev' => 'RaggieSoft Open-Source Projects Hub'
                ];
                $siteImageMap = [
                    'stardust.raggiesoft.com' => rtrim($cdnBaseUrl, '/') . '/stardust-engine/images/stardust-engine-hero.jpg',
                    'engineroom.raggiesoft.com' => rtrim($cdnBaseUrl, '/') . '/engine-room-records/images/err-hero.jpg',
                    'lore.raggiesoft.com' => rtrim($cdnBaseUrl, '/') . '/raggiesoft-books/images/covers/og/stardust-engine.jpg',
                    'nebulae.raggiesoft.com' => rtrim($cdnBaseUrl, '/') . '/raggiesoft-books/images/covers/og/ad-astra.jpg',
                    'finsharp.raggiesoft.dev' => rtrim($cdnBaseUrl, '/') . '/raggiesoft-books/images/scenes/aubrie/e007/c001/p003/birth-of-finsharp-og.jpg',
                    'raggiescript.raggiesoft.dev' => rtrim($cdnBaseUrl, '/') . '/raggiesoft-books/images/covers/og/aubrie.jpg',
                    'raggiesoft.dev' => rtrim($cdnBaseUrl, '/') . '/raggiesoft-books/images/covers/og/aubrie.jpg'
                ];
                $previewPayload = [
                    'kind' => 'companion',
                    'kicker' => $siteTitleMap[$host] ?? 'RaggieSoft Universe',
                    'title' => trim(strip_tags($innerHtml)) ?: ($siteTitleMap[$host] ?? $host),
                    'date' => 'Companion Archive',
                    'location' => $host,
                    'image' => $siteImageMap[$host] ?? (rtrim($cdnBaseUrl, '/') . '/engine-room-records/images/err-hero.jpg'),
                    'excerpt' => 'Explore the official in-universe companion portal with interactive tour archives, schematics, and lore.',
                    'url' => $rawHref,
                    'cta' => 'Open Companion Portal ↗',
                    'external' => true
                ];
            }

            if (!$previewPayload) {
                return $m[0];
            }

            $wikiLinkCounter++;
            $linkId = 'rs-wiki-link-' . $wikiLinkCounter;
            $jsonAttr = htmlspecialchars(json_encode($previewPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            $safeHref = htmlspecialchars($finalHref, ENT_QUOTES, 'UTF-8');

            $combinedAttrs = trim($beforeHref . ' ' . $afterHref);
            if (preg_match('/\bclass=(["\'])(.*?)\1/i', $combinedAttrs)) {
                $combinedAttrs = preg_replace('/\bclass=(["\'])(.*?)\1/i', 'class=$1$2 rs-wiki-link$1', $combinedAttrs, 1);
            } else {
                $combinedAttrs .= ' class="rs-wiki-link"';
            }
            if ($isExternal && stripos($combinedAttrs, 'data-no-spa') === false) {
                $combinedAttrs .= ' data-no-spa="true" target="_blank" rel="noopener"';
            }

            return '<a id="' . $linkId . '" href="' . $safeHref . '" ' . trim($combinedAttrs) . ' data-rs-preview="' . $jsonAttr . '">' . $innerHtml . '</a>';
        },
        $htmlContent
    );
}

// ============================================================================
// SUBSYSTEM 5: SEQUENTIAL PREVIOUS / NEXT NAVIGATION & STORY CROSSROADS GATING
// ============================================================================
// Computes the bottom `$prevUrl` and `$nextUrl` buttons:
//   - Filters out `'common'`, the series root `/<series>`, and (when reading a story scene)
//     intermediate TOC/Gallery views (`__TOC__`, `__ERA_TOC__`, `__CHAP_TOC__`, `__ILLUSTRATIONS__`).
//   - Story Crossroads Filtering: Skips any route whose `pathId` belongs to an unchosen
//     branch (`empty($activeSegmentIdSet[$candidatePathId])`), AND halts `$nextUrl` whenever
//     the current chapter defines `$config['crossroads']` or `$config['noNext']` so the reader
//     is invited to choose their branching route at `#rs-story-crossroads`!
$prevUrl = $config['prevUrl'] ?? null;
// Diamond Convergence Previous-Link Resolver: When multiple Crossroads branches rejoin onto one shared chapter
// (such as `e001/c002/p001.md`), check `$config['rejoinPrevBySegment']` against `$activeSegmentIdSet` so clicking
// "Previous" steps straight back to the final scene of whichever branch the reader actually completed!
if (!empty($config['rejoinPrevBySegment']) && is_array($config['rejoinPrevBySegment'])) {
    foreach ($config['rejoinPrevBySegment'] as $candSegId => $candPrevUrl) {
        if (!empty($activeSegmentIdSet[(string)$candSegId]) && is_string($candPrevUrl) && $candPrevUrl !== '') {
            $prevUrl = $candPrevUrl;
        }
    }
}
$nextUrl = $config['nextUrl'] ?? null;
$sequenceName = $routeData['common']['siteName'] ?? 'Ocean View Archives';

$routeKeys = array_keys($routeData);
$seriesSlugForFilter = '/' . ($seriesSlug ?? '');
$filteredKeys = array_filter($routeKeys, function($k) use ($seriesSlugForFilter, $routeData, $specialPageType, $activeStoryPathId, $activeSegmentIdSet) { 
    if ($k === 'common' || $k === $seriesSlugForFilter) {
        return false;
    }
    $candidatePathId = (string)($routeData[$k]['pathId'] ?? '');
    if ($candidatePathId !== '' && empty($activeSegmentIdSet[$candidatePathId]) && $candidatePathId !== (string)$activeStoryPathId) {
        return false;
    }
    if (!$specialPageType) {
        $fp = (string)($routeData[$k]['filePath'] ?? '');
        if (
            $fp === '__SERIES_LANDING__' ||
            $fp === '__TOC__' ||
            $fp === '__ILLUSTRATIONS__' ||
            strpos($fp, '__ERA_TOC__|') === 0 ||
            strpos($fp, '__CHAP_TOC__|') === 0
        ) {
            return false;
        }
    }
    return true;
});
$filteredKeys = array_values($filteredKeys); // Re-index numeric keys `0..N-1` after `array_filter()`
$currentIndex = array_search($requestUri, $filteredKeys);
if ($currentIndex !== false) {
    if ($currentIndex > 0 && empty($prevUrl) && empty($config['noPrev'])) {
        $prevUrl = $filteredKeys[$currentIndex - 1];
    }
    if ($currentIndex < count($filteredKeys) - 1 && empty($nextUrl) && empty($config['noNext']) && empty($config['crossroads'])) {
        $nextUrl = $filteredKeys[$currentIndex + 1];
    }
}

/*
 * ============================================================================
 * STORY ARC PLAYLIST MODE RESOLVER (`?arc=<slug>`) & CHAPTER ARC DISCOVERY
 * ============================================================================
 * Resolves whether the current manuscript part belongs to one or more curated
 * Story Arcs (`$matchingPartStoryArcs`). When `?arc=<slug>` is active on the URL,
 * rewires `$prevUrl` and `$nextUrl` to step strictly through that Story Arc's
 * sequence, calculates playlist progress (`--arc-progress`), and surfaces any
 * behind-the-scenes Backstage bonus scenes sitting right after this chapter.
 */
$seriesStoryArcs = is_array($toc_manifest['story_arcs'] ?? null)
    ? $toc_manifest['story_arcs']
    : (is_array($routeData['common']['story_arcs'] ?? null) ? $routeData['common']['story_arcs'] : []);
$matchingPartStoryArcs = [];
$activeStoryArc = null;
$activeArcCurrentStop = null;
$activeArcProgressPct = 0.0;
$activeArcPrevLabel = 'Previous';
$activeArcNextLabel = 'Next';
$activeArcInterleavedBackstageStops = [];

if (!$specialPageType && $actualFilePath !== '' && !empty($seriesStoryArcs)) {
    $requestedArcSlug = isset($_GET['arc']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$_GET['arc']) : '';
    foreach ($seriesStoryArcs as $sArc) {
        $arcStops = is_array($sArc['stops'] ?? null) ? $sArc['stops'] : [];
        $foundRawIndex = null;
        foreach ($arcStops as $sIdx => $st) {
            if (($st['kind'] ?? '') === 'part' && ($st['file_path'] ?? '') === $actualFilePath) {
                $foundRawIndex = $sIdx;
                break;
            }
        }
        if ($foundRawIndex === null) {
            continue;
        }
        $matchingPartStoryArcs[] = [
            'slug'       => (string)($sArc['slug'] ?? ''),
            'title'      => (string)($sArc['title'] ?? ''),
            'stop_count' => (int)($sArc['stop_count'] ?? count($arcStops)),
            'arc_url'    => $requestUri . '?arc=' . rawurlencode((string)($sArc['slug'] ?? '')),
        ];

        if ($requestedArcSlug !== '' && ($sArc['slug'] ?? '') === $requestedArcSlug) {
            $activeStoryArc       = $sArc;
            $activeArcCurrentStop = $arcStops[$foundRawIndex];
            $totalStops           = max(1, count($arcStops));
            $currStopNum          = (int)($activeArcCurrentStop['stop_num'] ?? ($foundRawIndex + 1));
            $activeArcProgressPct = round(($currStopNum / $totalStops) * 100, 1);

            // Walk backward to find the previous chapter stop in this Story Arc
            $prevArcUrl = null;
            for ($back = $foundRawIndex - 1; $back >= 0; $back--) {
                if (($arcStops[$back]['kind'] ?? '') === 'part') {
                    $prevArcUrl = $arcStops[$back]['arc_url'] ?? null;
                    break;
                }
            }
            // Walk forward to collect interleaved Backstage bonus stops AND find the next chapter stop
            $nextArcUrl = null;
            for ($fwd = $foundRawIndex + 1; $fwd < count($arcStops); $fwd++) {
                $fKind = (string)($arcStops[$fwd]['kind'] ?? '');
                if ($fKind === 'backstage') {
                    $activeArcInterleavedBackstageStops[] = $arcStops[$fwd];
                } elseif ($fKind === 'part') {
                    $nextArcUrl = $arcStops[$fwd]['arc_url'] ?? null;
                    break;
                }
            }

            $prevUrl = $prevArcUrl;
            $nextUrl = $nextArcUrl;
            $activeArcPrevLabel = 'Previous in Arc';
            $activeArcNextLabel = 'Next in Arc';
        }
    }
}

$overviewUrl = '/' . $seriesSlug;
$title = $config['title'] ?? ($frontmatter['title'] ?? 'Untitled Chapter');
$narrativeTheme = $frontmatter['theme'] ?? null;

// Fallback to series-level theme defined in ovab.json (`series_theme`) or landing.md frontmatter for landing/TOC views
if (empty($narrativeTheme) && !empty($toc_manifest['series_theme'])) {
    $narrativeTheme = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$toc_manifest['series_theme']);
}
if (empty($narrativeTheme) && !empty($toc_manifest['series_description_long'])) {
    if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $toc_manifest['series_description_long'], $descMatches)) {
        if (preg_match('/^theme:\s*["\']?([a-zA-Z0-9_-]+)["\']?\s*$/m', $descMatches[1], $themeMatch)) {
            $narrativeTheme = $themeMatch[1];
        }
    }
}

// Allow querystring override for theme previewing
if (isset($_GET['preview_theme']) && !empty($_GET['preview_theme'])) {
    $narrativeTheme = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['preview_theme']);
}

// Resolve full hierarchy context (Series -> Era -> Chapter -> Part) BEFORE rendering
// Schema.org JSON-LD and visual breadcrumbs so every structured entity and breadcrumb link is accurate.
$seriesTitle = !empty($toc_manifest['series_title'])
    ? trim((string)$toc_manifest['series_title'])
    : ucwords(str_replace('-', ' ', (string)$seriesSlug));
$eraTitle = '';
$eraUrl = '';
$chapTitle = '';
$chapUrl = '';
$chapPartCount = 1;
$matchedPartMeta = null;

if ($specialPageType === 'era_toc') {
    $eObj = $toc_manifest['eras'][$specialPageIndex1] ?? null;
    if ($eObj) {
        $eraTitle = (string)($eObj['era_title'] ?? '');
        $eraUrl = !empty($eObj['era_url']) ? str_replace('/raggiesoft-books/books', '', (string)$eObj['era_url']) : '';
    }
} elseif ($specialPageType === 'chap_toc') {
    $eObj = $toc_manifest['eras'][$specialPageIndex1] ?? null;
    $cObj = $eObj['chapters'][$specialPageIndex2] ?? null;
    if ($eObj) {
        $eraTitle = (string)($eObj['era_title'] ?? '');
        $eraUrl = !empty($eObj['era_url']) ? str_replace('/raggiesoft-books/books', '', (string)$eObj['era_url']) : '';
    }
    if ($cObj) {
        $chapTitle = (string)($cObj['chap_title'] ?? '');
        $chapUrl = !empty($cObj['chap_url']) ? str_replace('/raggiesoft-books/books', '', (string)$cObj['chap_url']) : '';
        $chapPartCount = !empty($cObj['parts']) && is_array($cObj['parts']) ? count($cObj['parts']) : 0;
    }
} elseif (!$specialPageType && !empty($toc_manifest['eras']) && is_array($toc_manifest['eras'])) {
    foreach ($toc_manifest['eras'] as $e) {
        if (!empty($e['chapters']) && is_array($e['chapters'])) {
            foreach ($e['chapters'] as $c) {
                if (!empty($c['parts']) && is_array($c['parts'])) {
                    foreach ($c['parts'] as $p) {
                        if (($p['file_path'] ?? '') === $actualFilePath) {
                            $eraTitle = (string)($e['era_title'] ?? '');
                            $eraUrl = !empty($e['era_url']) ? str_replace('/raggiesoft-books/books', '', (string)$e['era_url']) : '';
                            $chapTitle = (string)($c['chap_title'] ?? '');
                            $chapUrl = !empty($c['chap_url']) ? str_replace('/raggiesoft-books/books', '', (string)$c['chap_url']) : '';
                            $chapPartCount = count($c['parts']);
                            $matchedPartMeta = $p;
                            break 3;
                        }
                    }
                }
            }
        }
    }
}

$rawReqPath = parse_url($_SERVER['REQUEST_URI'] ?? ('/' . $seriesSlug), PHP_URL_PATH);
$cleanReqPath = (is_string($rawReqPath) && $rawReqPath !== '' && $rawReqPath !== '/') ? rtrim($rawReqPath, '/') : ('/' . $seriesSlug);
$oliverCanonicalUrl = 'https://books.raggiesoft.com' . $cleanReqPath;
$seriesOverviewCanonical = 'https://books.raggiesoft.com/' . $seriesSlug;
$seriesShortDescForSchema = !empty($toc_manifest['series_short_description'])
    ? (string)$toc_manifest['series_short_description']
    : (!empty($toc_manifest['series_description']) ? (string)$toc_manifest['series_description'] : "Read {$seriesTitle} free on Ocean View Archives.");
$seriesWordStatsForSchema = $toc_manifest['word_stats'] ?? ($routeData['common']['word_stats'] ?? []);
$seriesStoryWordsForSchema = (int)($seriesWordStatsForSchema['story_word_count'] ?? 0);
$seriesStoryPagesForSchema = (int)($seriesWordStatsForSchema['story_page_count'] ?? ($seriesWordStatsForSchema['estimated_story_pages'] ?? 0));

$bookSeriesNode = [
    '@type' => 'BookSeries',
    '@id' => $seriesOverviewCanonical . '#bookseries',
    'name' => $seriesTitle,
    'description' => $seriesShortDescForSchema,
    'url' => $seriesOverviewCanonical,
    'inLanguage' => 'en-US',
    'license' => 'https://creativecommons.org/licenses/by-sa/4.0/',
    'image' => $cdnBaseUrl . '/raggiesoft-books/images/covers/2x3/' . $seriesSlug . '.jpg',
    'author' => [
        '@type' => 'Person',
        '@id' => 'https://michaelpragsdale.com/#michael-ragsdale',
        'name' => 'Michael P. Ragsdale',
        'url' => 'https://michaelpragsdale.com',
    ],
    'publisher' => [
        '@type' => 'Organization',
        '@id' => 'https://raggiesoft.com/#organization',
        'name' => 'Ocean View Archives (RaggieSoft)',
        'url' => 'https://books.raggiesoft.com',
    ],
];
if ($seriesSlug === 'ad-astra') {
    $bookSeriesNode['mentions'] = [
        [
            '@type' => 'WebSite',
            'name' => 'RaggieSoft Nebulae — Caelum-IV Planetary Survey',
            'url' => 'https://nebulae.raggiesoft.com',
        ],
        [
            '@type' => 'Article',
            '@id' => 'https://raggiesoft.com/in-memoriam#article',
            'headline' => 'In Memoriam: Sir Patrick Stewart (1940–2026) — Second Star to the Right',
            'url' => 'https://raggiesoft.com/in-memoriam',
        ],
    ];
}

// Construct accurate leaf Schema.org entity (`Book`, `CollectionPage`, or `Chapter`)
if ($specialPageType === 'landing') {
    $leafSchemaNode = [
        '@type' => 'Book',
        '@id' => $seriesOverviewCanonical . '#book',
        'name' => $seriesTitle,
        'description' => $seriesShortDescForSchema,
        'url' => $seriesOverviewCanonical,
        'bookFormat' => 'https://schema.org/EBook',
        'inLanguage' => 'en-US',
        'isAccessibleForFree' => true,
        'license' => 'https://creativecommons.org/licenses/by-sa/4.0/',
        'image' => $cdnBaseUrl . '/raggiesoft-books/images/covers/2x3/' . $seriesSlug . '.jpg',
        'isPartOf' => ['@id' => $seriesOverviewCanonical . '#bookseries'],
        'author' => ['@id' => 'https://michaelpragsdale.com/#michael-ragsdale'],
        'publisher' => ['@id' => 'https://raggiesoft.com/#organization'],
        'encoding' => [
            [
                '@type' => 'MediaObject',
                'encodingFormat' => 'application/epub+zip',
                'contentUrl' => "{$cdnBaseUrl}/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.epub"
            ],
            [
                '@type' => 'MediaObject',
                'encodingFormat' => 'application/pdf',
                'contentUrl' => "{$cdnBaseUrl}/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.pdf"
            ]
        ]
    ];
    if ($seriesStoryPagesForSchema > 0) {
        $leafSchemaNode['numberOfPages'] = $seriesStoryPagesForSchema;
    }
} elseif ($specialPageType !== null) {
    $collectionName = $title;
    if ($specialPageType === 'toc') {
        $collectionName = "Table of Contents — {$seriesTitle}";
    } elseif ($specialPageType === 'illustrations') {
        $collectionName = "Table of Illustrations — {$seriesTitle}";
    } elseif ($specialPageType === 'era_toc' && $eraTitle !== '') {
        $collectionName = "{$eraTitle} — {$seriesTitle}";
    } elseif ($specialPageType === 'chap_toc' && $chapTitle !== '') {
        $collectionName = "{$chapTitle} — {$seriesTitle}";
    }
    $leafSchemaNode = [
        '@type' => 'CollectionPage',
        '@id' => $oliverCanonicalUrl . '#collection',
        'name' => $collectionName,
        'url' => $oliverCanonicalUrl,
        'inLanguage' => 'en-US',
        'license' => 'https://creativecommons.org/licenses/by-sa/4.0/',
        'isPartOf' => ['@id' => $seriesOverviewCanonical . '#bookseries'],
        'author' => ['@id' => 'https://michaelpragsdale.com/#michael-ragsdale'],
    ];
} else {
    $leafSchemaNode = [
        '@type' => 'Chapter',
        '@id' => $oliverCanonicalUrl . '#chapter',
        'name' => $title,
        'url' => $oliverCanonicalUrl,
        'inLanguage' => 'en-US',
        'isAccessibleForFree' => true,
        'license' => 'https://creativecommons.org/licenses/by-sa/4.0/',
        'isPartOf' => ['@id' => $seriesOverviewCanonical . '#bookseries'],
        'author' => ['@id' => 'https://michaelpragsdale.com/#michael-ragsdale'],
    ];
    if (!empty($matchedPartMeta['excerpt'])) {
        $leafSchemaNode['description'] = (string)$matchedPartMeta['excerpt'];
    }
    if (!empty($matchedPartMeta['word_count'])) {
        $leafSchemaNode['wordCount'] = (int)$matchedPartMeta['word_count'];
    }
    if (!empty($matchedPartMeta['image'])) {
        $pImg = (string)$matchedPartMeta['image'];
        $leafSchemaNode['image'] = str_starts_with($pImg, 'http') ? $pImg : ($cdnBaseUrl . '/' . ltrim($pImg, '/'));
    }
}

// Construct hierarchical Schema.org BreadcrumbList for rich search snippets
$breadcrumbItems = [
    ['name' => 'Home', 'item' => 'https://books.raggiesoft.com/'],
    ['name' => 'Library', 'item' => 'https://books.raggiesoft.com/library'],
    ['name' => $seriesTitle, 'item' => $seriesOverviewCanonical],
];
if ($specialPageType === 'toc') {
    $breadcrumbItems[] = ['name' => 'Table of Contents', 'item' => $oliverCanonicalUrl];
} elseif ($specialPageType === 'illustrations') {
    $breadcrumbItems[] = ['name' => 'Table of Illustrations', 'item' => $oliverCanonicalUrl];
} elseif ($specialPageType === 'era_toc' && $eraTitle !== '') {
    $breadcrumbItems[] = ['name' => $eraTitle, 'item' => $oliverCanonicalUrl];
} elseif ($specialPageType === 'chap_toc') {
    if ($eraTitle !== '' && $eraUrl !== '') {
        $breadcrumbItems[] = ['name' => $eraTitle, 'item' => 'https://books.raggiesoft.com' . $eraUrl];
    }
    if ($chapTitle !== '') {
        $breadcrumbItems[] = ['name' => $chapTitle, 'item' => $oliverCanonicalUrl];
    }
} elseif (!$specialPageType) {
    if ($eraTitle !== '' && $eraUrl !== '') {
        $breadcrumbItems[] = ['name' => $eraTitle, 'item' => 'https://books.raggiesoft.com' . $eraUrl];
    }
    if ($chapPartCount > 1 && $chapTitle !== '' && $chapUrl !== '') {
        $breadcrumbItems[] = ['name' => $chapTitle, 'item' => 'https://books.raggiesoft.com' . $chapUrl];
    }
    $breadcrumbItems[] = ['name' => $title, 'item' => $oliverCanonicalUrl];
}

$breadcrumbListElements = [];
foreach ($breadcrumbItems as $bIdx => $bItem) {
    $breadcrumbListElements[] = [
        '@type' => 'ListItem',
        'position' => $bIdx + 1,
        'name' => $bItem['name'],
        'item' => $bItem['item']
    ];
}

$oliverBookSeriesSchema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        $bookSeriesNode,
        $leafSchemaNode,
        [
            '@type' => 'BreadcrumbList',
            '@id' => $oliverCanonicalUrl . '#breadcrumb',
            'itemListElement' => $breadcrumbListElements
        ]
    ],
];
?>
<!--
  SUBSYSTEM 6: SCHEMA.ORG STRUCTURED DATA (`BookSeries` + `Book`/`Chapter` + `BreadcrumbList`)
  Injected cleanly as `application/ld+json` so search engines understand the exact
  BookSeries -> Era -> Chapter -> Part hierarchy, CC BY-SA 4.0 license, and EPUB/PDF downloads.
-->
<script type="application/ld+json">
<?php echo json_encode($oliverBookSeriesSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>
<?php if ($narrativeTheme): ?>
    <!--
      ATMOSPHERIC NARRATIVE THEME LOADER:
      Notice `disabled` on `<link id="narrative-theme-css">`! `reader.js` checks whether
      `reader-settings.customThemeEnabled !== false` in `localStorage` before enabling this
      stylesheet, and automatically suppresses rain animations when the user's OS has
      `prefers-reduced-motion: reduce` turned on!
    -->
    <meta name="stardust-narrative-theme" content="<?php echo htmlspecialchars($narrativeTheme); ?>">
    <link id="narrative-theme-css" rel="stylesheet" href="<?php echo htmlspecialchars($cdnBaseUrl . '/raggiesoft-books/css/themes/' . $narrativeTheme . '.css?v=20261114a'); ?>" disabled>
    
    <?php if ($narrativeTheme === 'dark-rain'): ?>
    <!-- Procedural 60-Drop Rain Overlay (`aria-hidden="true"` hides decorative rain from screen readers) -->
    <div class="narrative-rain-container" aria-hidden="true">
        <?php for($i=0; $i<60; $i++): 
            $left = rand(0, 100);
            $duration = 0.4 + (rand(0, 40) / 100);
            $delay = rand(0, 200) / 100;
            $opacity = rand(20, 50) / 100;
        ?>
        <div class="narrative-rain-drop" style="left: <?php echo $left; ?>%; animation-duration: <?php echo $duration; ?>s; animation-delay: <?php echo $delay; ?>s; --drop-opacity: <?php echo $opacity; ?>;"></div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!--
  ============================================================================
  SUBSYSTEM 7: PRIMARY READING PANE & CUSTOMIZABLE TYPOGRAPHY / THEME SHELL
  ============================================================================
  100-Level CSS & Accessibility Architecture Note:
  - `#stardust-reading-pane` (`height: 100dvh; overflow-y: auto`) is the independent
    scrolling viewport next to `sophia/sidebar.php`, and `reader.js` automatically
    records the reader's current chapter URL + scroll progress in `localStorage`
    (`'rs-last-read'` and `'rs-last-read-title'`).
  - `<div class="book-page reader-page">` and `<article class="story-content">` inherit
    every WCAG 2.1 AA preference configured in `sophia/settings.php` & `sophia/wizard.php`:
      * Font Family: `var(--rs-font-family)` (`System`, `Georgia`, `Lora`, `Inter`, `Atkinson Hyperlegible`, `OpenDyslexic`)
      * Font Size: `var(--rs-font-size)` (`0.85rem`–`2.5rem`, baseline `1.15rem` = `100%`)
      * Line Spacing: `var(--rs-line-height)` (`1.4`, `1.6`, `1.8`, `2.0`)
      * Measure / Column Width: `.width-default` (`800px`), `.width-wide` (`1200px`), `.width-full` (`100%`)
      * Color Mode: `body.theme-auto|light|sepia|dark|dark-sepia|sepia-system` (`var(--rs-bg)`, `var(--rs-text)`)
-->
<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; height: 100dvh; overflow-y: auto;">
    <div class="book-page reader-page">
        <!-- Hierarchical Breadcrumbs & Sidebar Menu Toggle -->
        <div class="reader-nav" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
            <nav aria-label="Breadcrumb" style="color: var(--rs-text-muted); font-size: 0.9rem; min-width: 0; flex: 1; line-height: 1.45;">
                <a href="/library" style="text-decoration: none; color: var(--rs-primary);">Library</a>
                <?php if ($specialPageType === 'landing'): ?>
                    <span aria-hidden="true">&raquo;</span> <strong aria-current="page" style="color: var(--rs-text); font-weight: 600;"><?php echo htmlspecialchars($seriesTitle); ?></strong>
                <?php else: ?>
                    <span aria-hidden="true">&raquo;</span> <a href="/<?php echo htmlspecialchars($seriesSlug); ?>" style="text-decoration: none; color: var(--rs-primary); font-weight: 600;"><?php echo htmlspecialchars($seriesTitle); ?></a>
                    <?php if ($specialPageType === 'toc'): ?>
                        <span aria-hidden="true">&raquo;</span> <strong aria-current="page" style="color: var(--rs-text); font-weight: 600;">Table of Contents</strong>
                    <?php elseif ($specialPageType === 'illustrations'): ?>
                        <span aria-hidden="true">&raquo;</span> <strong aria-current="page" style="color: var(--rs-text); font-weight: 600;">Illustrations</strong>
                    <?php elseif ($specialPageType === 'era_toc' && $eraTitle !== ''): ?>
                        <span aria-hidden="true">&raquo;</span> <strong aria-current="page" style="color: var(--rs-text); font-weight: 600;"><?php echo htmlspecialchars($eraTitle); ?></strong>
                    <?php elseif ($specialPageType === 'chap_toc'): ?>
                        <?php if ($eraTitle !== '' && $eraUrl !== ''): ?>
                            <span aria-hidden="true">&raquo;</span> <a href="<?php echo htmlspecialchars($eraUrl); ?>" style="text-decoration: none; color: var(--rs-primary);"><?php echo htmlspecialchars($eraTitle); ?></a>
                        <?php endif; ?>
                        <?php if ($chapTitle !== ''): ?>
                            <span aria-hidden="true">&raquo;</span> <strong aria-current="page" style="color: var(--rs-text); font-weight: 600;"><?php echo htmlspecialchars($chapTitle); ?></strong>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($eraTitle !== '' && $eraUrl !== ''): ?>
                            <span aria-hidden="true">&raquo;</span> <a href="<?php echo htmlspecialchars($eraUrl); ?>" style="text-decoration: none; color: var(--rs-primary);"><?php echo htmlspecialchars($eraTitle); ?></a>
                        <?php endif; ?>
                        <?php if ($chapPartCount > 1 && $chapTitle !== '' && $chapUrl !== ''): ?>
                            <span aria-hidden="true">&raquo;</span> <a href="<?php echo htmlspecialchars($chapUrl); ?>" style="text-decoration: none; color: var(--rs-primary);"><?php echo htmlspecialchars($chapTitle); ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            <div style="display: flex; gap: 1rem; flex-shrink: 0;">
                <button id="stardust-sidebar-toggle" class="rs-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem; white-space: nowrap;"><i class="ph ph-list" aria-hidden="true"></i> Menu</button>
            </div>
        </div>

        <article class="story-content">
            <?php if (!$specialPageType && $activeStoryArc !== null):
                $bannerStopNum   = (int)($activeArcCurrentStop['stop_num'] ?? 1);
                $bannerStopTotal = (int)($activeStoryArc['stop_count'] ?? count($activeStoryArc['stops'] ?? []));
                $bannerArcTitle  = (string)($activeStoryArc['title'] ?? 'Story Arc');
            ?>
            <!-- Story Arc Playlist Mode Top Banner (Progress passed via CSS Custom Property per modern-web-guidance) -->
            <section class="rs-arc-reader-banner"
                     aria-label="Active Story Arc Playlist"
                     style="--arc-progress: <?php echo htmlspecialchars((string)$activeArcProgressPct); ?>%;">
                <div class="rs-arc-reader-banner-row">
                    <div class="rs-arc-reader-banner-info">
                        <div class="rs-arc-reader-kicker">
                            <i class="ph-fill ph-playlist" aria-hidden="true"></i>
                            <span>Story Arc Playlist &bull; Stop <?php echo $bannerStopNum; ?> of <?php echo $bannerStopTotal; ?></span>
                        </div>
                        <div class="rs-arc-reader-title"><?php echo htmlspecialchars($bannerArcTitle); ?></div>
                    </div>
                    <div class="rs-arc-reader-actions">
                        <button type="button"
                                id="rs-open-arc-playlist-btn"
                                class="rs-btn rs-btn-primary rs-arc-banner-btn"
                                aria-haspopup="dialog"
                                aria-controls="rs-arc-playlist-dialog">
                            <i class="ph ph-list-numbers" aria-hidden="true"></i>
                            <span>All <?php echo $bannerStopTotal; ?> Stops</span>
                        </button>
                        <a href="<?php echo htmlspecialchars($requestUri); ?>"
                           class="rs-btn rs-arc-banner-btn"
                           title="Exit Story Arc Playlist Mode and continue reading the full book in order">
                            <i class="ph ph-book-open" aria-hidden="true"></i>
                            <span>Switch to Full Book</span>
                        </a>
                    </div>
                </div>
                <div class="rs-arc-progress-track" role="progressbar" aria-valuemin="1" aria-valuemax="<?php echo $bannerStopTotal; ?>" aria-valuenow="<?php echo $bannerStopNum; ?>" aria-label="Story Arc reading progress">
                    <div class="rs-arc-progress-fill"></div>
                </div>
            </section>
            <?php elseif (!$specialPageType && !empty($matchingPartStoryArcs)): ?>
            <!-- Subtle Discovery Strip when a Chapter Belongs to One or More Curated Story Arcs -->
            <aside class="rs-arc-discovery-strip" aria-label="Related Story Arcs">
                <span class="rs-arc-discovery-label">
                    <i class="ph-fill ph-playlist" aria-hidden="true"></i>
                    <span>Follow Story Arc:</span>
                </span>
                <div class="rs-arc-discovery-pills">
                    <?php foreach ($matchingPartStoryArcs as $mArc): ?>
                    <a href="<?php echo htmlspecialchars($mArc['arc_url']); ?>" class="rs-arc-discovery-pill">
                        <span><?php echo htmlspecialchars($mArc['title']); ?></span>
                        <span class="rs-arc-discovery-count"><?php echo (int)$mArc['stop_count']; ?> stops &rarr;</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </aside>
            <?php endif; ?>

            <?php
            $oliverCurrentPathId = (string)($config['pathId'] ?? '');
            $oliverPathMap = is_array($toc_manifest['paths'] ?? null) ? $toc_manifest['paths'] : [];
            $oliverCrossroadsList = is_array($toc_manifest['crossroads'] ?? null)
                ? $toc_manifest['crossroads']
                : (is_array($routeData['common']['crossroads'] ?? null) ? $routeData['common']['crossroads'] : []);
            $oliverCurrentPathObj = ($oliverCurrentPathId !== '' && isset($oliverPathMap[$oliverCurrentPathId]))
                ? $oliverPathMap[$oliverCurrentPathId]
                : null;
            if (!$specialPageType && $oliverCurrentPathObj !== null && !empty($oliverCrossroadsList)):
            ?>
            <aside class="rs-active-path-bar" aria-label="Active Story Path">
                <div class="rs-active-path-bar-main">
                    <div class="rs-active-path-copy">
                        <span class="rs-crossroads-kicker">
                            <i class="ph-fill ph-signpost" aria-hidden="true"></i>
                            <span>Story Path</span>
                        </span>
                        <div class="rs-active-path-name"><?php echo htmlspecialchars((string)($oliverCurrentPathObj['label'] ?? '')); ?></div>
                    </div>
                    <div class="rs-active-path-actions">
                        <button type="button" class="rs-btn rs-btn-primary" data-open-crossroads-dialog="true">
                            <i class="ph ph-signpost" aria-hidden="true"></i>
                            <span>Switch Story Path</span>
                        </button>
                    </div>
                </div>
            </aside>
            <?php endif; ?>

            <?php if (!$specialPageType): ?>
            <?php
            /**
             * ============================================================================
             * SCENE HERO IMAGE & HERO IMAGE CAROUSEL RESOLVER (Option A Header Plate)
             * ============================================================================
             * Supports both a single scalar `image:` / `image_alt:` AND a multi-slide
             * YAML list (`image:` + `image_alt:` arrays, or `images:` + `image_alts:`).
             * - When 1 slide is present: renders a static 16:9 `<figure class="reader-chapter-plate">`.
             * - When 2+ slides are present: renders an accessible, keyboard- and swipe-navigable
             *   16:9 Hero Image Carousel (`<figure class="reader-chapter-plate reader-hero-carousel">`).
             */
            $normalizeHeroUrl = function ($rawUrl) use ($cdnBaseUrl) {
                $url = trim((string)$rawUrl);
                if ($url === '') return '';
                $url = str_replace(['{{CDN}}', '{{cms}}'], $cdnBaseUrl, $url);
                $url = preg_replace('/#.*$/', '', $url);
                if (strpos($url, 'http') !== 0) {
                    $cleanHeaderPath = ltrim($url, '/');
                    if (strpos($cleanHeaderPath, 'raggiesoft-books/') !== 0 && strpos($cleanHeaderPath, 'engine-room-records/') !== 0) {
                        $cleanHeaderPath = 'raggiesoft-books/images/scenes/' . $cleanHeaderPath;
                    }
                    $url = $cdnBaseUrl . '/' . $cleanHeaderPath;
                }
                return $url;
            };

            $rawHeroImages = [];
            if (!empty($frontmatter['images'])) {
                $rawHeroImages = is_array($frontmatter['images']) ? $frontmatter['images'] : [$frontmatter['images']];
            } elseif (!empty($frontmatter['image'])) {
                $rawHeroImages = is_array($frontmatter['image']) ? $frontmatter['image'] : [$frontmatter['image']];
            }

            $rawHeroAlts = [];
            if (!empty($frontmatter['image_alts'])) {
                $rawHeroAlts = is_array($frontmatter['image_alts']) ? $frontmatter['image_alts'] : [$frontmatter['image_alts']];
            } elseif (!empty($frontmatter['image_alt'])) {
                $rawHeroAlts = is_array($frontmatter['image_alt']) ? $frontmatter['image_alt'] : [$frontmatter['image_alt']];
            }

            $heroSlides = [];
            $defaultHeroAlt = $title . ' — ' . $seriesTitle;
            foreach ($rawHeroImages as $hIdx => $rawImgVal) {
                $resolvedSrc = $normalizeHeroUrl($rawImgVal);
                if ($resolvedSrc === '') continue;
                $resolvedAlt = isset($rawHeroAlts[$hIdx]) && trim((string)$rawHeroAlts[$hIdx]) !== ''
                    ? trim((string)$rawHeroAlts[$hIdx])
                    : (isset($rawHeroAlts[0]) && trim((string)$rawHeroAlts[0]) !== '' ? trim((string)$rawHeroAlts[0]) : $defaultHeroAlt);
                $heroSlides[] = [
                    'src' => $resolvedSrc,
                    'alt' => $resolvedAlt
                ];
            }
            $heroSlideCount = count($heroSlides);
            ?>
            <!-- Title Header -->
            <div style="text-align: center; margin-bottom: 3rem; padding-bottom: 2rem; border-bottom: 1px solid var(--rs-border); position: relative;">
                <?php if ($heroSlideCount === 1): ?>
                <figure class="reader-chapter-plate" style="margin: 0 0 1.75rem 0; width: 100%; border-radius: 12px; overflow: hidden; border: 1px solid var(--rs-border); box-shadow: 0 6px 24px rgba(0,0,0,0.12); background: var(--rs-surface);">
                    <img src="<?php echo htmlspecialchars($heroSlides[0]['src']); ?>"
                         alt="<?php echo htmlspecialchars($heroSlides[0]['alt']); ?>"
                         width="1200"
                         height="675"
                         fetchpriority="high"
                         decoding="async"
                         style="width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; display: block;">
                </figure>
                <?php elseif ($heroSlideCount > 1): ?>
                <figure class="reader-chapter-plate reader-hero-carousel"
                        data-hero-carousel
                        tabindex="0"
                        role="region"
                        aria-roledescription="carousel"
                        aria-label="<?php echo htmlspecialchars('Scene Hero Illustrations (' . $heroSlideCount . ' slides)'); ?>"
                        style="margin: 0 0 1.75rem 0; width: 100%; border-radius: 12px; overflow: hidden; border: 1px solid var(--rs-border); box-shadow: 0 6px 24px rgba(0,0,0,0.12); background: var(--rs-surface); position: relative;">
                    <div class="reader-hero-carousel-viewport">
                        <?php foreach ($heroSlides as $sIdx => $slide): ?>
                        <div class="reader-hero-slide<?php echo $sIdx === 0 ? ' is-active' : ''; ?>"
                             data-hero-slide="<?php echo $sIdx; ?>"
                             role="group"
                             aria-roledescription="slide"
                             aria-label="<?php echo htmlspecialchars('Illustration ' . ($sIdx + 1) . ' of ' . $heroSlideCount); ?>"
                             aria-hidden="<?php echo $sIdx === 0 ? 'false' : 'true'; ?>">
                            <img src="<?php echo htmlspecialchars($slide['src']); ?>"
                                 alt="<?php echo htmlspecialchars($slide['alt']); ?>"
                                 width="1200"
                                 height="675"
                                 <?php if ($sIdx === 0): ?>fetchpriority="high"<?php else: ?>fetchpriority="low" loading="lazy"<?php endif; ?>
                                 decoding="async"
                                 style="width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; display: block;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button"
                            class="reader-hero-nav reader-hero-prev"
                            data-hero-prev
                            onclick="window.stardustStepHeroCarousel && window.stardustStepHeroCarousel(this, -1); return false;"
                            aria-label="Previous scene illustration">
                        <i class="ph-bold ph-caret-left" aria-hidden="true"></i>
                    </button>
                    <button type="button"
                            class="reader-hero-nav reader-hero-next"
                            data-hero-next
                            onclick="window.stardustStepHeroCarousel && window.stardustStepHeroCarousel(this, 1); return false;"
                            aria-label="Next scene illustration">
                        <i class="ph-bold ph-caret-right" aria-hidden="true"></i>
                    </button>
                    <div class="reader-hero-controls">
                        <span class="reader-hero-counter" data-hero-counter aria-live="polite">1 / <?php echo $heroSlideCount; ?></span>
                        <div class="reader-hero-dots" role="tablist" aria-label="Select scene illustration">
                            <?php foreach ($heroSlides as $sIdx => $slide): ?>
                            <button type="button"
                                    class="reader-hero-dot<?php echo $sIdx === 0 ? ' is-active' : ''; ?>"
                                    data-hero-dot="<?php echo $sIdx; ?>"
                                    onclick="window.stardustGoHeroCarousel && window.stardustGoHeroCarousel(this, <?php echo (int)$sIdx; ?>); return false;"
                                    role="tab"
                                    aria-selected="<?php echo $sIdx === 0 ? 'true' : 'false'; ?>"
                                    aria-label="<?php echo htmlspecialchars('Show illustration ' . ($sIdx + 1) . ' of ' . $heroSlideCount); ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </figure>
                <script>
                /**
                 * SUBSYSTEM 8B: INLINE 16:9 HERO IMAGE CAROUSEL CONTROLLER
                 * 100-Level JavaScript Walkthrough:
                 * - Attached right below multi-slide hero plates (`$heroSlideCount > 1`) so it runs
                 *   automatically on both full page loads AND SPA soft-navigations (`stardust-spa.min.js`).
                 * - Modulo Wrap-Around Math: `((nextIdx % total) + total) % total` converts `-1` into
                 *   `total - 1` (the last slide) and `total` back into `0` (the first slide)!
                 * - Accessibility Synchronization: Updates `aria-hidden` on slides, `aria-selected` on
                 *   tab dots, and `aria-live="polite"` on `[data-hero-counter]`, plus binds `ArrowLeft` /
                 *   `ArrowRight` keys and horizontal touch swipes (`Math.abs(dx) > 40` pixels).
                 */
                (function () {
                    window.stardustSetHeroSlide = function (carousel, nextIdx) {
                        if (!carousel) return;
                        var slides = carousel.querySelectorAll('[data-hero-slide]');
                        var dots = carousel.querySelectorAll('[data-hero-dot]');
                        var counterEl = carousel.querySelector('[data-hero-counter]');
                        var total = slides.length;
                        if (total <= 1) return;
                        var target = ((nextIdx % total) + total) % total;
                        carousel.setAttribute('data-hero-active-index', String(target));
                        for (var i = 0; i < total; i++) {
                            var isActive = (i === target);
                            slides[i].classList.toggle('is-active', isActive);
                            slides[i].setAttribute('aria-hidden', isActive ? 'false' : 'true');
                            slides[i].style.opacity = isActive ? '1' : '0';
                            slides[i].style.pointerEvents = isActive ? 'auto' : 'none';
                            slides[i].style.position = isActive ? 'relative' : 'absolute';
                            slides[i].style.zIndex = isActive ? '2' : '0';
                        }
                        for (var j = 0; j < dots.length; j++) {
                            var dotActive = (j === target);
                            dots[j].classList.toggle('is-active', dotActive);
                            dots[j].setAttribute('aria-selected', dotActive ? 'true' : 'false');
                        }
                        if (counterEl) {
                            counterEl.textContent = (target + 1) + ' / ' + total;
                        }
                    };
                    window.stardustStepHeroCarousel = function (btnEl, delta) {
                        if (!btnEl) return;
                        var carousel = btnEl.closest('[data-hero-carousel]');
                        if (!carousel) return;
                        var cur = parseInt(carousel.getAttribute('data-hero-active-index') || '0', 10);
                        if (isNaN(cur)) cur = 0;
                        window.stardustSetHeroSlide(carousel, cur + delta);
                    };
                    window.stardustGoHeroCarousel = function (btnEl, targetIdx) {
                        if (!btnEl) return;
                        var carousel = btnEl.closest('[data-hero-carousel]');
                        if (!carousel) return;
                        window.stardustSetHeroSlide(carousel, targetIdx);
                    };
                    var carousels = document.querySelectorAll('[data-hero-carousel]');
                    for (var c = 0; c < carousels.length; c++) {
                        (function (carousel) {
                            if (!carousel.hasAttribute('data-hero-active-index')) {
                                window.stardustSetHeroSlide(carousel, 0);
                            }
                            if (carousel.dataset.heroKeySwipeBound === 'true') return;
                            carousel.dataset.heroKeySwipeBound = 'true';
                            carousel.addEventListener('keydown', function (e) {
                                var cur = parseInt(carousel.getAttribute('data-hero-active-index') || '0', 10) || 0;
                                if (e.key === 'ArrowLeft') {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    window.stardustSetHeroSlide(carousel, cur - 1);
                                } else if (e.key === 'ArrowRight') {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    window.stardustSetHeroSlide(carousel, cur + 1);
                                }
                            });
                            var touchStartX = null;
                            var touchStartY = null;
                            carousel.addEventListener('touchstart', function (e) {
                                if (e.touches && e.touches.length === 1) {
                                    touchStartX = e.touches[0].clientX;
                                    touchStartY = e.touches[0].clientY;
                                }
                            }, { passive: true });
                            carousel.addEventListener('touchend', function (e) {
                                if (touchStartX === null || touchStartY === null) return;
                                if (e.changedTouches && e.changedTouches.length === 1) {
                                    var dx = e.changedTouches[0].clientX - touchStartX;
                                    var dy = e.changedTouches[0].clientY - touchStartY;
                                    if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
                                        var cur = parseInt(carousel.getAttribute('data-hero-active-index') || '0', 10) || 0;
                                        window.stardustSetHeroSlide(carousel, dx < 0 ? cur + 1 : cur - 1);
                                    }
                                }
                                touchStartX = null;
                                touchStartY = null;
                            }, { passive: true });
                        })(carousels[c]);
                    }
                })();
                </script>
                <?php endif; ?>
                <div class="reader-series-header">
                    <?php 
                    $eyebrowParts = [$seriesTitle];
                    if ($eraTitle) $eyebrowParts[] = $eraTitle;
                    if ($chapTitle) $eyebrowParts[] = $chapTitle;
                    echo htmlspecialchars(implode(' • ', $eyebrowParts));
                    ?>
                </div>
                <h1 class="reader-part-header"><?php echo htmlspecialchars($title); ?></h1>
                
                <!-- Temporal Metadata Rendering -->
                <?php if (!empty($frontmatter['stardate']) || !empty($frontmatter['realm_time']) || !empty($frontmatter['date']) || !empty($frontmatter['start_time']) || !empty($frontmatter['pov']) || !empty($frontmatter['location']) || !empty($frontmatter['characters'])): ?>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; color: var(--rs-text-muted); font-size: 0.85rem; font-weight: 600;">
                        <?php if (!empty($frontmatter['stardate'])): ?>
                            <span style="display: flex; align-items: center; gap: 4px;" title="Stardate">
                                <i class="ph ph-planet"></i> 
                                <span class="dt-text"><?php echo htmlspecialchars('Stardate ' . $frontmatter['stardate']); ?></span>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['realm_time'])): ?>
                            <span style="display: flex; align-items: center; gap: 4px;" title="Realm Time">
                                <i class="ph ph-hourglass-high"></i> 
                                <span class="dt-text"><?php echo htmlspecialchars($frontmatter['realm_time']); ?></span>
                            </span>
                        <?php endif; ?>

                        <?php if (empty($frontmatter['date']) && !empty($frontmatter['start_time'])): ?>
                            <!-- Standalone 24-hour clock for fantasy/non-Earth realms (formatted to OS locale without timezone shifting) -->
                            <span id="story-time-only-display" style="display: flex; align-items: center; gap: 4px;" data-time-only="<?php echo htmlspecialchars($frontmatter['start_time']); ?>" data-end-time-only="<?php echo htmlspecialchars($frontmatter['end_time'] ?? ''); ?>">
                                <i class="ph ph-clock"></i>
                                <span class="dt-text">
                                    <?php echo htmlspecialchars($frontmatter['start_time'] . (!empty($frontmatter['end_time']) ? ' - ' . $frontmatter['end_time'] : '')); ?>
                                </span>
                            </span>
                        <?php endif; ?>

                        <?php 
                        if (!empty($frontmatter['date'])): 
                            $isoString = '';
                            if (!empty($frontmatter['start_time'])) {
                                $rawTz = $frontmatter['timezone'] ?? 'America/New_York';
                                // Map abbreviations to IANA strings for PHP DateTime
                                $tzMap = [
                                    'ET' => 'America/New_York', 'EST' => 'America/New_York', 'EDT' => 'America/New_York',
                                    'PT' => 'America/Los_Angeles', 'PST' => 'America/Los_Angeles', 'PDT' => 'America/Los_Angeles',
                                    'CT' => 'America/Chicago', 'CST' => 'America/Chicago', 'CDT' => 'America/Chicago',
                                    'MT' => 'America/Denver', 'MST' => 'America/Denver', 'MDT' => 'America/Denver'
                                ];
                                $tzStr = $tzMap[$rawTz] ?? $rawTz; // Use map, or fallback to the raw IANA string
                                try {
                                    $dt = new DateTime($frontmatter['date'] . ' ' . $frontmatter['start_time'], new DateTimeZone($tzStr));
                                    $isoString = $dt->format(DateTime::ATOM);
                                } catch (Exception $e) {}
                            }
                        ?>
                            <span id="story-datetime-display" style="display: flex; align-items: center; gap: 4px;" data-iso="<?php echo htmlspecialchars($isoString); ?>" data-iana="<?php echo htmlspecialchars($frontmatter['timezone'] ?? ''); ?>">
                                <i class="ph ph-calendar-blank"></i> 
                                <span class="dt-text">
                                    <?php echo htmlspecialchars($frontmatter['date'] . (!empty($frontmatter['start_time']) ? ' ' . $frontmatter['start_time'] . ' ' . ($frontmatter['timezone'] ?? '') : '')); ?>
                                </span>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['location'])): ?>
                            <span style="display: flex; align-items: center; gap: 4px;"><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($frontmatter['location']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['pov'])): ?>
                            <span style="display: flex; align-items: center; gap: 4px;"><i class="ph ph-eye"></i> POV: <?php echo htmlspecialchars($frontmatter['pov']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['characters']) && is_array($frontmatter['characters'])): ?>
                            <span style="display: flex; align-items: center; gap: 4px;"><i class="ph ph-users"></i> Characters: <?php echo htmlspecialchars(implode(', ', $frontmatter['characters'])); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php endif; // End if(!$specialPageType) ?>

            <!--
              ============================================================================
              SUBSYSTEM 9: PERSISTENT SOUNDTRACK `<audio>` QUEUE & LYRICS / WEBVTT SYNC
              ============================================================================
              100-Level Multimedia Architecture Walkthrough:
              1. Flexible YAML Input: A chapter's YAML frontmatter can specify:
                   - A single MP3 (`audio: "the-stardust-engine/2016-live-at-the-crucible/..."`),
                   - A full album (`audio: "the-paper-wall/2003-eleventh-child-book-1"`),
                   - A YAML list of multiple songs/acts (`audio: [ ... ]`), OR
                   - A named Engine Room Records playlist (`playlist: "..."`),
                 plus an optional seek offset (`audio_start: "1:30"` -> `90` seconds).
              2. Why Two `<audio>` Tags Exist (`#narrative-audio-element` vs `#stardust-bgm`):
                   - When a reader turns the page via the SPA router (`stardust-spa.min.js`),
                     the contents of `#stardust-app` (including `oliver.php`) are swapped out.
                   - If music played inside `#narrative-audio-element` right here, turning the
                     page to Part 2 of the same concert scene would destroy the `<audio>` node
                     and cut the song off mid-chorus (and Safari/WebKit CoreMedia blocks
                     `DOMParser`-created `<audio>` tags from playing without a fresh tap)!
                   - Therefore, `<audio id="narrative-audio-element" preload="none">` below acts
                     PURELY as a Server-Side-Rendered (`data-*`) metadata & JSON queue carrier,
                     while `reader.js` drives actual audio playback on the persistent
                     `<audio id="stardust-bgm">` element mounted *outside* `#stardust-app` in
                     `header.php`! If Part 2 uses the same track/queue as Part 1, playback
                     continues seamlessly across the page turn without skipping a single beat,
                     syncing OS lock-screen controls via `navigator.mediaSession`!
              3. Lyrics Modal & Time-Synced WebVTT (`.vtt`) Handoff:
                   - Clicking the musical-notes button (`#narrative-audio-lyrics-toggle`) fetches
                     the song's `.md` lyrics & lore sheet into `<dialog id="narrative-lyrics-dialog">`.
                   - Every track mastered through `harper.sh` ALSO ships a `HUMAN_VERIFIED`
                     `.vtt` (WebVTT timed-cue) file (`lyrics/<fileName>.vtt`). Clicking any
                     `.reader-audio-external-link` ("Song in Listening Room" / headphone icon)
                     automatically pauses `#stardust-bgm` (preventing dual-audio collision) and
                     opens `https://music.raggiesoft.com/<artist>/<album>/<track>`, where Svelte 5
                     synchronizes real-time line-by-line `.vtt` karaoke lyric cues with lossless audio!
            -->
            <?php if (!empty($frontmatter['audio']) || !empty($frontmatter['playlist'])): ?>
                    <?php
                        // Parse starting timestamp if provided (e.g., "1:30" or "90")
                        $audioStart = 0;
                        if (!empty($frontmatter['audio_start'])) {
                            $val = $frontmatter['audio_start'];
                            if (strpos($val, ':') !== false) {
                                $parts = explode(':', $val);
                                if (count($parts) == 2) {
                                    $audioStart = ((int)$parts[0] * 60) + (float)$parts[1];
                                }
                            } else {
                                $audioStart = (float)$val;
                            }
                        }

                        // Per-request memoization caches to avoid redundant CDN fetches across multi-track queues
                        $albumMetaCache = [];
                        $tracksMetaCache = [];
                        $artistAlbumsMetaCache = [];

                        $resolvedErrServerRoot = is_dir(dirname($basePath, 3) . '/raggiesoft-assets')
                            ? dirname($basePath, 3)
                            : (is_dir(dirname($basePath, 1) . '/raggiesoft-assets') ? dirname($basePath, 1) : dirname($basePath, 3));
                        $localErrArtistsRoot = $resolvedErrServerRoot . '/raggiesoft-assets/engine-room-records/artists';
                        $localErrPlaylistsRoot = $resolvedErrServerRoot . '/raggiesoft-assets/engine-room-records/playlists';
                        $fetchAlbumMeta = function ($artistSlug, $albumSlug) use ($cdnBaseUrl, $localErrArtistsRoot, &$albumMetaCache, &$tracksMetaCache, &$artistAlbumsMetaCache) {
                            $key = $artistSlug . '/' . $albumSlug;
                            if (!isset($albumMetaCache[$key])) {
                                $localAlbJson = $localErrArtistsRoot . '/' . $artistSlug . '/' . $albumSlug . '/album.json';
                                $raw = is_file($localAlbJson)
                                    ? @file_get_contents($localAlbJson)
                                    : @file_get_contents($cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/album.json');
                                $albumMetaCache[$key] = $raw ? (json_decode($raw, true) ?: []) : [];
                            }
                            if (!isset($tracksMetaCache[$key])) {
                                $localTrkJson = $localErrArtistsRoot . '/' . $artistSlug . '/' . $albumSlug . '/tracks.json';
                                $rawT = is_file($localTrkJson)
                                    ? @file_get_contents($localTrkJson)
                                    : @file_get_contents($cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/tracks.json');
                                $tracksMetaCache[$key] = $rawT ? (json_decode($rawT, true) ?: []) : [];
                            }
                            if (!isset($artistAlbumsMetaCache[$artistSlug])) {
                                $localArtJson = $localErrArtistsRoot . '/' . $artistSlug . '/albums.json';
                                $rawA = is_file($localArtJson)
                                    ? @file_get_contents($localArtJson)
                                    : @file_get_contents($cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/albums.json');
                                $artistAlbumsMetaCache[$artistSlug] = $rawA ? (json_decode($rawA, true) ?: []) : [];
                            }

                            $albumData = $albumMetaCache[$key];
                            $tracksData = $tracksMetaCache[$key];
                            $artistAlbumsData = $artistAlbumsMetaCache[$artistSlug];

                            $spotifyUrl = $appleUrl = $amazonUrl = $youtubeUrl = $storeStandardUrl = $storeAudiophileUrl = null;
                            if (is_array($artistAlbumsData) && !empty($artistAlbumsData)) {
                                $erasList = (isset($artistAlbumsData['eras']) && is_array($artistAlbumsData['eras']))
                                    ? $artistAlbumsData['eras']
                                    : $artistAlbumsData;
                                foreach ($erasList as $era) {
                                    if (!empty($era['albums']) && is_array($era['albums'])) {
                                        foreach ($era['albums'] as $alb) {
                                            $albFolder = $alb['folder'] ?? ($alb['slug'] ?? '');
                                            $albUrlBase = !empty($alb['url']) ? basename(rtrim($alb['url'], '/')) : '';
                                            $albImgPath = $alb['img'] ?? ($alb['cover'] ?? '');
                                            $isAlbumMatch = (
                                                ($albFolder !== '' && $albFolder === $albumSlug) ||
                                                ($albUrlBase !== '' && $albUrlBase === $albumSlug) ||
                                                ($albImgPath !== '' && strpos($albImgPath, '/' . $albumSlug . '/') !== false)
                                            );
                                            if ($isAlbumMatch) {
                                                if (!empty($alb['spotifyId'])) $spotifyUrl = "https://open.spotify.com/album/{$alb['spotifyId']}";
                                                if (!empty($alb['appleId'])) $appleUrl = "https://music.apple.com/us/album/{$alb['appleId']}";
                                                if (!empty($alb['amazonId'])) $amazonUrl = "https://music.amazon.com/albums/{$alb['amazonId']}";
                                                if (!empty($alb['youtubeId'])) $youtubeUrl = "https://music.youtube.com/playlist?list={$alb['youtubeId']}";
                                                if (!empty($alb['storeStandardUrl'])) $storeStandardUrl = $alb['storeStandardUrl'];
                                                if (!empty($alb['storeAudiophileUrl'])) $storeAudiophileUrl = $alb['storeAudiophileUrl'];
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }

                            return [
                                'albumData' => $albumData,
                                'tracks' => $tracksData['tracks'] ?? [],
                                'dsp' => [
                                    'spotifyUrl' => $spotifyUrl,
                                    'appleUrl' => $appleUrl,
                                    'amazonUrl' => $amazonUrl,
                                    'youtubeUrl' => $youtubeUrl,
                                    'storeStandardUrl' => $storeStandardUrl,
                                    'storeAudiophileUrl' => $storeAudiophileUrl
                                ]
                            ];
                        };

                        $buildTrackItem = function ($artistSlug, $albumSlug, $filenameNoExt, $actLabel = '') use ($cdnBaseUrl, $frontmatter, $fetchAlbumMeta) {
                            $meta = $fetchAlbumMeta($artistSlug, $albumSlug);
                            $albumData = $meta['albumData'];
                            $tracksList = $meta['tracks'];
                            $dsp = $meta['dsp'];

                            $albumDisplay = !empty($albumData['name']) ? $albumData['name'] : ucwords(str_replace('-', ' ', preg_replace('/^\d{4}-/', '', $albumSlug)));
                            $artistDisplay = !empty($albumData['byArtist']['name']) ? $albumData['byArtist']['name'] : (!empty($frontmatter['pov']) ? $frontmatter['pov'] : ucwords(str_replace('-', ' ', $artistSlug)));
                            $audioTitleDisplay = ucwords(str_replace('-', ' ', preg_replace('/^\d+-\d+-/', '', $filenameNoExt)));
                            $durationDisplay = '';
                            $trackNumber = null;

                            $canonicalTrackSlug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', (string)preg_replace('/^\d+-/', '', (string)preg_replace('/^\d+-\d+-/', '', $filenameNoExt))), '-')) ?: 'track';
                            foreach ($tracksList as $tIdx => $track) {
                                if (($track['fileName'] ?? '') === $filenameNoExt) {
                                    if (!empty($track['title'])) $audioTitleDisplay = $track['title'];
                                    if (!empty($track['duration'])) $durationDisplay = $track['duration'];
                                    if (!empty($track['slug'])) $canonicalTrackSlug = trim((string)$track['slug']);
                                    $trackNumber = $track['track'] ?? ($tIdx + 1);
                                    break;
                                }
                            }

                            $audioUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/web-mp3/' . $filenameNoExt . '.mp3';
                            $albumArtUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/album-art.jpg';
                            $lyricsUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/lyrics/' . $filenameNoExt . '.md';
                            $listeningRoomTrackUrl = 'https://music.raggiesoft.com/' . rawurlencode($artistSlug) . '/' . rawurlencode($albumSlug) . '/' . rawurlencode($canonicalTrackSlug);
                            $listeningRoomAlbumUrl = 'https://music.raggiesoft.com/' . rawurlencode($artistSlug) . '/' . rawurlencode($albumSlug);
                            $listeningRoomArtistUrl = 'https://music.raggiesoft.com/' . rawurlencode($artistSlug);

                            return [
                                'title' => $audioTitleDisplay,
                                'artist' => $artistDisplay,
                                'artistSlug' => $artistSlug,
                                'album' => $albumDisplay,
                                'albumSlug' => $albumSlug,
                                'fileName' => $filenameNoExt,
                                'trackNumber' => $trackNumber,
                                'duration' => $durationDisplay,
                                'actLabel' => $actLabel,
                                'audioUrl' => $audioUrl,
                                'artworkUrl' => $albumArtUrl,
                                'lyricsUrl' => $lyricsUrl,
                                'listeningRoomTrackUrl' => $listeningRoomTrackUrl,
                                'listeningRoomAlbumUrl' => $listeningRoomAlbumUrl,
                                'listeningRoomArtistUrl' => $listeningRoomArtistUrl,
                                'spotifyUrl' => $dsp['spotifyUrl'],
                                'appleUrl' => $dsp['appleUrl'],
                                'amazonUrl' => $dsp['amazonUrl'],
                                'youtubeUrl' => $dsp['youtubeUrl'],
                                'storeStandardUrl' => $dsp['storeStandardUrl'],
                                'storeAudiophileUrl' => $dsp['storeAudiophileUrl']
                            ];
                        };

                        $audioQueue = [];
                        $queueMode = 'single'; // 'single' | 'album' | 'playlist'
                        $playlistTitle = !empty($frontmatter['audio_playlist_title']) ? $frontmatter['audio_playlist_title'] : '';
                        $playlistListeningRoomUrl = '';

                        $expandAudioSpec = function ($spec, $actLabel = '') use (
                            $cdnBaseUrl,
                            &$audioQueue,
                            &$queueMode,
                            &$playlistTitle,
                            &$playlistListeningRoomUrl,
                            $fetchAlbumMeta,
                            $buildTrackItem,
                            $localErrPlaylistsRoot,
                            &$expandAudioSpec
                        ) {
                            $clean = trim((string)$spec, " \t\n\r\0\x0B/");
                            if ($clean === '') return;

                            // Case 1: Named Playlist ("playlists/{slug}" or "engine-room-records/playlists/{slug}")
                            if (strpos($clean, 'playlists/') === 0 || strpos($clean, 'engine-room-records/playlists/') === 0) {
                                $plRelative = preg_replace('#^(engine-room-records/)?playlists/#', '', $clean);
                                $plRelative = preg_replace('#/playlist\.json$#', '', $plRelative);
                                $plSlug = trim($plRelative, '/');
                                $localPlJson = $localErrPlaylistsRoot . '/' . $plSlug . '/playlist.json';
                                $plJsonUrl = $cdnBaseUrl . '/engine-room-records/playlists/' . $plSlug . '/playlist.json';
                                $plRaw = is_file($localPlJson) ? @file_get_contents($localPlJson) : @file_get_contents($plJsonUrl);
                                if ($plRaw) {
                                    $plData = json_decode($plRaw, true);
                                    if (is_array($plData)) {
                                        $queueMode = 'playlist';
                                        if (empty($playlistTitle) && !empty($plData['title'])) {
                                            $playlistTitle = $plData['title'];
                                        }
                                        if (empty($playlistListeningRoomUrl) && !empty($plData['listeningRoomUrl'])) {
                                            $playlistListeningRoomUrl = $plData['listeningRoomUrl'];
                                        } elseif (empty($playlistListeningRoomUrl)) {
                                            $playlistListeningRoomUrl = 'https://music.raggiesoft.com/playlists/' . rawurlencode($plSlug);
                                        }
                                        if (!empty($plData['tracks']) && is_array($plData['tracks'])) {
                                            foreach ($plData['tracks'] as $plTrack) {
                                                if (is_string($plTrack)) {
                                                    $expandAudioSpec($plTrack);
                                                } elseif (is_array($plTrack) && !empty($plTrack['path'])) {
                                                    $expandAudioSpec($plTrack['path'], $plTrack['actLabel'] ?? '');
                                                }
                                            }
                                        }
                                    }
                                }
                                return;
                            }

                            $stripped = preg_replace('#^engine-room-records/artists/#', '', $clean);
                            $parts = array_values(array_filter(explode('/', $stripped), function ($p) { return $p !== ''; }));

                            // Case 2: Full Album ("{artistSlug}/{albumSlug}")
                            if (count($parts) === 2 && !preg_match('/\.(mp3|ogg|flac|wav)$/i', $parts[1])) {
                                $artistSlug = $parts[0];
                                $albumSlug = $parts[1];
                                if ($queueMode === 'single') {
                                    $queueMode = 'album';
                                }
                                $meta = $fetchAlbumMeta($artistSlug, $albumSlug);
                                foreach ($meta['tracks'] as $track) {
                                    $fn = $track['fileName'] ?? '';
                                    if ($fn !== '') {
                                        $audioQueue[] = $buildTrackItem($artistSlug, $albumSlug, $fn, $actLabel);
                                    }
                                }
                                return;
                            }

                            // Case 3: Single Track ("{artistSlug}/{albumSlug}/web-mp3/{filename}.mp3" or "{artistSlug}/{albumSlug}/{filename}.mp3")
                            if (count($parts) >= 3) {
                                $artistSlug = $parts[0];
                                $albumSlug = $parts[1];
                                $filenameWithExt = end($parts);
                                $filenameNoExt = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                                $audioQueue[] = $buildTrackItem($artistSlug, $albumSlug, $filenameNoExt, $actLabel);
                            }
                        };

                        if (!empty($frontmatter['playlist'])) {
                            $plInput = $frontmatter['playlist'];
                            if (strpos($plInput, 'playlists/') === false) {
                                $plInput = 'playlists/' . ltrim($plInput, '/');
                            }
                            $expandAudioSpec($plInput);
                        }

                        if (!empty($frontmatter['audio'])) {
                            if (is_array($frontmatter['audio'])) {
                                if (count($frontmatter['audio']) > 1) {
                                    $queueMode = 'playlist';
                                }
                                foreach ($frontmatter['audio'] as $audioItem) {
                                    $expandAudioSpec($audioItem);
                                }
                            } else {
                                $expandAudioSpec($frontmatter['audio']);
                            }
                        }

                        $queueCount = count($audioQueue);
                        if ($queueCount > 1 && $queueMode === 'single') {
                            $queueMode = 'playlist';
                        }

                        $firstTrack = $audioQueue[0] ?? null;
                        if ($firstTrack):
                            $audioTitleDisplay = $firstTrack['title'];
                            $artistDisplay = $firstTrack['artist'];
                            $artistSlug = $firstTrack['artistSlug'];
                            $albumDisplay = $firstTrack['album'];
                            $albumSlug = $firstTrack['albumSlug'];
                            $filenameNoExt = $firstTrack['fileName'];
                            $audioUrl = $firstTrack['audioUrl'];
                            $albumArtUrl = $firstTrack['artworkUrl'];
                            $lyricsUrl = $firstTrack['lyricsUrl'];
                            $listeningRoomTrackUrl = $firstTrack['listeningRoomTrackUrl'];
                            $listeningRoomAlbumUrl = $firstTrack['listeningRoomAlbumUrl'];
                            $listeningRoomArtistUrl = $firstTrack['listeningRoomArtistUrl'];
                            $spotifyUrl = $firstTrack['spotifyUrl'];
                            $appleUrl = $firstTrack['appleUrl'];
                            $amazonUrl = $firstTrack['amazonUrl'];
                            $youtubeUrl = $firstTrack['youtubeUrl'];
                            $storeStandardUrl = $firstTrack['storeStandardUrl'];
                            $storeAudiophileUrl = $firstTrack['storeAudiophileUrl'];

                            if ($queueMode === 'playlist') {
                                $eyebrowLabel = 'Background Playlist • Track 1 of ' . $queueCount . ($playlistTitle !== '' ? ' — ' . $playlistTitle : '');
                            } elseif ($queueMode === 'album' && $queueCount > 1) {
                                $eyebrowLabel = 'Background Album • Track 1 of ' . $queueCount;
                            } else {
                                $eyebrowLabel = 'Background Audio';
                            }
                    ?>
                    <div class="reader-audio-player" style="margin-top: 2rem; border: 1px solid var(--rs-border); border-radius: 12px; background: var(--rs-surface); overflow: hidden; max-width: 100%; margin-left: auto; margin-right: auto; text-align: left; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem;">
                            <a id="narrative-audio-cover-link" href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Open album <?php echo htmlspecialchars($albumDisplay); ?> in The Listening Room" aria-label="Open album <?php echo htmlspecialchars($albumDisplay); ?> by <?php echo htmlspecialchars($artistDisplay); ?> in The Listening Room" style="display: block; flex-shrink: 0; border-radius: 6px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <img id="narrative-audio-cover-img" src="<?php echo htmlspecialchars($albumArtUrl); ?>" alt="Album cover for <?php echo htmlspecialchars($albumDisplay); ?> by <?php echo htmlspecialchars($artistDisplay); ?>" style="width: 80px; height: 80px; border-radius: 6px; object-fit: cover; display: block;">
                            </a>
                            
                            <div style="flex: 1; overflow: hidden; display: flex; flex-direction: column; min-width: 0;">
                                <div id="narrative-audio-eyebrow" style="font-size: 0.75rem; color: var(--rs-text-muted); text-transform: uppercase; letter-spacing: 1px; line-height: 1.2; margin-bottom: 0.35rem; font-weight: 600;"><?php echo htmlspecialchars($eyebrowLabel); ?></div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-weight: 700; font-size: 1.1rem; line-height: 1.2; margin-bottom: 0.25rem;">
                                    <span class="reader-audio-scroll-text">
                                        <a id="narrative-audio-title-link" href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Listen to &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room" style="color: inherit; text-decoration: underline; text-decoration-color: var(--rs-primary); text-underline-offset: 3px;">
                                            <span id="narrative-audio-title-text"><?php echo htmlspecialchars($audioTitleDisplay); ?></span> <i class="ph ph-arrow-square-out" aria-hidden="true" style="font-size: 0.85em; color: var(--rs-text-muted);"></i>
                                        </a>
                                    </span>
                                </div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-size: 0.85rem; color: var(--rs-text-muted); margin-bottom: 0.75rem;">
                                    <span class="reader-audio-scroll-text">
                                        <a id="narrative-audio-artist-link" href="<?php echo htmlspecialchars($listeningRoomArtistUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="View <?php echo htmlspecialchars($artistDisplay); ?> in The Listening Room" style="color: inherit; text-decoration: none; border-bottom: 1px dotted currentColor;"><?php echo htmlspecialchars($artistDisplay); ?></a>
                                        &bull;
                                        <a id="narrative-audio-album-link" href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Open album <?php echo htmlspecialchars($albumDisplay); ?> in The Listening Room" style="color: inherit; text-decoration: underline; text-underline-offset: 2px;"><i id="narrative-audio-album-text"><?php echo htmlspecialchars($albumDisplay); ?></i></a>
                                    </span>
                                </div>
                                
                                <!-- Custom HTML5 Audio Player UI -->
                                <div class="reader-audio-controls-row" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <!-- SSR Metadata & Queue Carrier (actual playback executes on the live persistent #stardust-bgm <audio> element outside #stardust-app for Safari/WebKit PWA CoreMedia compatibility) -->
                                    <audio id="narrative-audio-element"
                                           data-title="<?php echo htmlspecialchars($audioTitleDisplay); ?>"
                                           data-artist="<?php echo htmlspecialchars($artistDisplay); ?>"
                                           data-album="<?php echo htmlspecialchars($albumDisplay); ?>"
                                           data-artwork="<?php echo htmlspecialchars($albumArtUrl); ?>"
                                           data-duration="<?php echo htmlspecialchars((string)($firstTrack['duration'] ?? '')); ?>"
                                           data-listening-room-track-url="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>"
                                           data-listening-room-album-url="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>"
                                           data-queue-mode="<?php echo htmlspecialchars($queueMode); ?>"
                                           data-playlist-title="<?php echo htmlspecialchars($playlistTitle); ?>"
                                           data-playlist-url="<?php echo htmlspecialchars($playlistListeningRoomUrl); ?>"
                                           data-repeat-mode="<?php echo $queueCount > 1 ? 'all' : 'one'; ?>"
                                           data-active-index="0"
                                           data-queue="<?php echo htmlspecialchars(json_encode($audioQueue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>"
                                           data-audio-src="<?php echo htmlspecialchars($audioUrl); ?>"
                                           data-start-time="<?php echo $audioStart; ?>"
                                           preload="none"></audio>
                                    
                                    <?php if ($queueCount > 1): ?>
                                    <button type="button" id="narrative-audio-prev" class="rs-btn" style="border-radius: 50%; width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="Previous Track" title="Previous Track">
                                        <i class="ph ph-skip-back" style="font-size: 1.05rem;" aria-hidden="true"></i>
                                    </button>
                                    <?php endif; ?>

                                    <button type="button" id="narrative-audio-play-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="Play/Pause" title="Play/Pause">
                                        <i class="ph ph-play" style="font-size: 1.2rem;" id="narrative-audio-play-icon" aria-hidden="true"></i>
                                    </button>

                                    <?php if ($queueCount > 1): ?>
                                    <button type="button" id="narrative-audio-next" class="rs-btn" style="border-radius: 50%; width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="Next Track" title="Next Track">
                                        <i class="ph ph-skip-forward" style="font-size: 1.05rem;" aria-hidden="true"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <div class="reader-audio-scrubber-container" style="flex: 1; min-width: 140px;">
                                        <span id="narrative-audio-current">0:00</span>
                                        <input type="range" id="narrative-audio-scrubber" value="0" min="0" step="1" aria-label="Seek background audio timeline">
                                        <span id="narrative-audio-duration"><?php echo !empty($firstTrack['duration']) ? htmlspecialchars((string)$firstTrack['duration']) : '0:00'; ?></span>
                                    </div>
                                    
                                    <button type="button" id="narrative-audio-loop-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid var(--rs-primary); color: var(--rs-primary); background: rgba(0,0,0,0.05);" aria-label="<?php echo $queueCount > 1 ? 'Toggle Repeat: All Tracks' : 'Toggle Repeat: ON'; ?>" title="<?php echo $queueCount > 1 ? 'Repeat All Tracks: ON' : 'Repeat 1 Track: ON'; ?>">
                                        <i class="ph <?php echo $queueCount > 1 ? 'ph-repeat' : 'ph-repeat-once'; ?>" style="font-size: 1.2rem;" aria-hidden="true"></i>
                                    </button>
                                    
                                    <button type="button" id="narrative-audio-lyrics-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="View Lyrics" title="View Lyrics" data-url="<?php echo htmlspecialchars($lyricsUrl); ?>" data-title="<?php echo htmlspecialchars($audioTitleDisplay); ?>">
                                        <i class="ph ph-music-notes" style="font-size: 1.2rem;" aria-hidden="true"></i>
                                    </button>

                                    <?php if ($queueCount > 1): ?>
                                    <button type="button" id="narrative-audio-queue-toggle" class="rs-btn" aria-expanded="true" aria-controls="narrative-audio-queue-drawer" title="Toggle Tracklist Queue (<?php echo $queueCount; ?> tracks)" style="height: 36px; padding: 0 0.65rem; border-radius: 999px; display: inline-flex; align-items: center; gap: 0.35rem; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent; font-size: 0.78rem; font-weight: 700;">
                                        <i class="ph ph-queue" style="font-size: 1.05rem;" aria-hidden="true"></i>
                                        <span id="narrative-audio-queue-pos">1 / <?php echo $queueCount; ?></span>
                                    </button>
                                    <?php endif; ?>

                                    <a id="narrative-audio-headphones-link" href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn reader-audio-external-link" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent; color: inherit; text-decoration: none;" aria-label="Open &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room" title="Open &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room">
                                        <i class="ph ph-headphones" style="font-size: 1.2rem;" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <?php if ($queueCount > 1): ?>
                        <!-- Multi-Track Album / Playlist Queue Drawer -->
                        <div id="narrative-audio-queue-drawer" role="region" aria-label="<?php echo htmlspecialchars(($queueMode === 'playlist' ? 'Playlist' : 'Album') . ' Track Queue'); ?>" style="border-top: 1px solid var(--rs-border); background: var(--rs-bg); max-height: 220px; overflow-y: auto; padding: 0.4rem 0.65rem;">
                            <?php foreach ($audioQueue as $qIdx => $qTrack): ?>
                            <button type="button"
                                    class="reader-audio-queue-item<?php echo $qIdx === 0 ? ' is-active' : ''; ?>"
                                    data-queue-index="<?php echo (int)$qIdx; ?>"
                                    aria-current="<?php echo $qIdx === 0 ? 'true' : 'false'; ?>"
                                    style="width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.45rem 0.65rem; border: none; border-radius: 8px; background: <?php echo $qIdx === 0 ? 'rgba(59, 130, 246, 0.12)' : 'transparent'; ?>; color: var(--rs-text); text-align: left; cursor: pointer; font-family: inherit; font-size: 0.85rem; transition: background-color 0.15s ease;">
                                <span style="display: flex; align-items: center; gap: 0.6rem; min-width: 0; flex: 1;">
                                    <span class="reader-audio-queue-num" style="font-variant-numeric: tabular-nums; font-weight: 700; color: var(--rs-text-muted); min-width: 1.5rem; text-align: right;"><?php echo ($qIdx + 1); ?>.</span>
                                    <span style="min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <strong style="font-weight: 600;"><?php echo htmlspecialchars($qTrack['title']); ?></strong>
                                        <?php if (!empty($qTrack['actLabel'])): ?>
                                            <span style="color: var(--rs-text-muted); font-size: 0.78rem;"> &mdash; <?php echo htmlspecialchars($qTrack['actLabel']); ?></span>
                                        <?php elseif ($queueMode === 'playlist'): ?>
                                            <span style="color: var(--rs-text-muted); font-size: 0.78rem;"> &bull; <?php echo htmlspecialchars($qTrack['artist'] . ' (' . $qTrack['album'] . ')'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <?php if (!empty($qTrack['duration'])): ?>
                                <span style="font-variant-numeric: tabular-nums; font-size: 0.78rem; color: var(--rs-text-muted); flex-shrink: 0;"><?php echo htmlspecialchars($qTrack['duration']); ?></span>
                                <?php endif; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- The Listening Room & External Streaming Links -->
                        <div style="background: var(--rs-bg); border-top: 1px solid var(--rs-border); padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; align-items: center; text-align: center;">
                            <div style="font-size: 0.8rem; color: var(--rs-text-muted);">Open this song or full album directly in <strong>The Listening Room</strong>, or stream the high-fidelity mix on your favorite service:</div>
                            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                <a id="narrative-audio-lr-song-btn" href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" title="Listen to &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room" class="rs-btn rs-btn-primary reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-headphones" aria-hidden="true" style="margin-right: 4px;"></i> Song in Listening Room <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.85;"></i></a>
                                <a id="narrative-audio-lr-album-btn" href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" title="Open <?php echo htmlspecialchars($albumDisplay); ?> in The Listening Room" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-vinyl-record" aria-hidden="true" style="margin-right: 4px;"></i> Full Album <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php if (!empty($playlistListeningRoomUrl)): ?>
                                    <a href="<?php echo htmlspecialchars($playlistListeningRoomUrl); ?>" target="_blank" rel="noopener noreferrer" title="Open Playlist in The Listening Room" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-playlist" aria-hidden="true" style="margin-right: 4px;"></i> Full Playlist <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($spotifyUrl): ?>
                                    <a id="narrative-audio-spotify-btn" href="<?php echo htmlspecialchars($spotifyUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Spotify" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-spotify-logo" aria-hidden="true" style="margin-right: 4px;"></i> Spotify <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($appleUrl): ?>
                                    <a id="narrative-audio-apple-btn" href="<?php echo htmlspecialchars($appleUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Apple Music" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-apple-logo" aria-hidden="true" style="margin-right: 4px;"></i> Apple <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($amazonUrl): ?>
                                    <a id="narrative-audio-amazon-btn" href="<?php echo htmlspecialchars($amazonUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Amazon Music" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-amazon-logo" aria-hidden="true" style="margin-right: 4px;"></i> Amazon <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($youtubeUrl): ?>
                                    <a id="narrative-audio-youtube-btn" href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on YouTube" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-youtube-logo" aria-hidden="true" style="margin-right: 4px;"></i> YouTube <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($storeStandardUrl): ?>
                                    <a id="narrative-audio-store-std-btn" href="<?php echo htmlspecialchars($storeStandardUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Buy MP3 / OGG Digital Archive" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-shopping-cart" aria-hidden="true" style="margin-right: 4px;"></i> MP3/OGG <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($storeAudiophileUrl): ?>
                                    <a id="narrative-audio-store-aud-btn" href="<?php echo htmlspecialchars($storeAudiophileUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Buy WAV / FLAC Audiophile Archive" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-shopping-bag" aria-hidden="true" style="margin-right: 4px;"></i> WAV/FLAC <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <dialog id="narrative-lyrics-dialog" style="padding: 0; border: 1px solid var(--rs-border); border-radius: 12px; background: var(--rs-bg); color: var(--rs-text); box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 600px; width: 90%; max-height: 85vh; overflow: hidden;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid var(--rs-border); background: var(--rs-surface);">
                            <h3 id="narrative-lyrics-title" style="margin: 0; font-size: 1.2rem; font-weight: 700;"></h3>
                            <button id="narrative-lyrics-close" class="rs-btn" aria-label="Close lyrics dialog" title="Close lyrics dialog" style="padding: 0.5rem; border: none; background: transparent; font-size: 1.2rem; cursor: pointer;"><i class="ph ph-x" aria-hidden="true"></i></button>
                        </div>
                        <div id="narrative-lyrics-content" style="padding: 1.5rem; overflow-y: auto; max-height: calc(85vh - 140px); font-size: 1.1rem; line-height: 1.6;">
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem 1.5rem; border-top: 1px solid var(--rs-border); background: var(--rs-surface); font-size: 0.85rem;">
                            <span id="narrative-lyrics-footer-meta" style="color: var(--rs-text-muted);"><?php echo htmlspecialchars($artistDisplay); ?> &bull; <i><?php echo htmlspecialchars($albumDisplay); ?></i></span>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a id="narrative-lyrics-open-song" href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn rs-btn-primary reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-headphones" aria-hidden="true" style="margin-right: 4px;"></i> Open Song <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px;"></i></a>
                                <a id="narrative-lyrics-open-album" href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-vinyl-record" aria-hidden="true" style="margin-right: 4px;"></i> Open Album <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px;"></i></a>
                            </div>
                        </div>
                    </dialog>
                    <?php endif; // End if ($firstTrack) ?>
                <?php endif; ?>

            <?php if (!$specialPageType && !empty($sceneSubsections)):
                $miniTocKickerText = (isset($chapPartCount) && (int)$chapPartCount > 1)
                    ? 'Scenes in This Part'
                    : 'Scenes in This Chapter';
            ?>
            <!-- In-Part / In-Chapter Scene Mini Table of Contents (Constructed from `## Heading` sub-headers at `***` breaks) -->
            <nav class="reader-scene-mini-toc" aria-label="<?php echo htmlspecialchars($miniTocKickerText, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="reader-scene-mini-toc-header">
                    <span class="reader-scene-mini-toc-kicker">
                        <i class="ph ph-list-bullets" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($miniTocKickerText, ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                    <span class="reader-scene-mini-toc-badge">
                        <?php echo count($sceneSubsections) === 1 ? '1 Scene' : (count($sceneSubsections) . ' Scenes'); ?>
                    </span>
                </div>
                <ol class="reader-scene-mini-toc-list">
                    <?php foreach ($sceneSubsections as $subSec): ?>
                    <li class="reader-scene-mini-toc-item">
                        <a href="#<?php echo htmlspecialchars($subSec['id'], ENT_QUOTES, 'UTF-8'); ?>"
                           class="reader-scene-mini-toc-link"
                           onclick="var targetEl = document.getElementById('<?php echo htmlspecialchars($subSec['id'], ENT_QUOTES, 'UTF-8'); ?>'); if (targetEl) { var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; targetEl.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' }); try { targetEl.focus({ preventScroll: true }); } catch (e) {} if (window.history && window.history.replaceState) { window.history.replaceState(null, '', '#<?php echo htmlspecialchars($subSec['id'], ENT_QUOTES, 'UTF-8'); ?>'); } } return false;">
                            <span class="reader-scene-mini-toc-num" aria-hidden="true"><?php echo str_pad((string)$subSec['num'], 2, '0', STR_PAD_LEFT); ?></span>
                            <span class="reader-scene-mini-toc-label"><?php echo htmlspecialchars($subSec['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <?php endif; ?>

            <!-- Parsedown Content -->
            <?php echo $htmlContent; ?>
        </article>

        <?php
        // =====================================================================
        // SUBSYSTEM 10: RAGGIESOFT BACKSTAGE BONUS-DOSSIER DRAWER (`cors fetch()`)
        // =====================================================================
        // 100-Level Cross-Subdomain API Walkthrough:
        //   1. Authoring: Any novel scene (`p*.md`) can declare `backstage:` in its
        //      YAML frontmatter (either a single slug or a YAML list of slugs) AND/OR
        //      include inline links to `https://backstage.raggiesoft.com/<slug>`.
        //   2. Server-Side Preview Cards (PHP right below): Looks up titles, dates,
        //      senders, and summaries from Backstage's pre-built `api/v1/index.json`
        //      so `#rs-backstage-callout` renders rich cards immediately on first paint.
        //   3. Client-Side CORS `fetch()` (`reader.js` -> `<dialog id="rs-backstage-dialog">`):
        //      When the reader clicks "Read in Dialog" (`[data-backstage-slug]`) or an
        //      inline Backstage link, `reader.js` opens `<dialog id="rs-backstage-dialog">`
        //      and performs a cross-origin `fetch()` to:
        //        `https://backstage.raggiesoft.com/api/v1/<slug>.json`
        //      (falling back automatically to `https://assets.raggiesoft.com/raggiesoft-backstage/api/v1/<slug>.json`),
        //      injecting `data.html` straight into `#rs-backstage-dialog-body` so the
        //      reader can inspect in-universe emails, legal filings, and floorplans
        //      without losing their scroll position in the chapter!
        if (!$specialPageType):
            $rawBackstage = $frontmatter['backstage'] ?? [];
            $backstageSlugs = is_array($rawBackstage)
                ? array_values(array_filter(array_map('trim', $rawBackstage)))
                : (trim((string)$rawBackstage) !== '' ? [trim((string)$rawBackstage)] : []);

            $backstageIndexMap = [];
            if (!empty($backstageSlugs)) {
                $localBsIndexCandidates = [
                    dirname($basePath, 3) . '/raggiesoft-assets/raggiesoft-backstage/api/v1/index.json',
                    dirname($basePath, 1) . '/raggiesoft-assets/raggiesoft-backstage/api/v1/index.json',
                    dirname($basePath) . '/backstage/dist/api/v1/index.json',
                    '/var/www/backstage.raggiesoft.com/api/v1/index.json'
                ];
                foreach ($localBsIndexCandidates as $bsIdxFile) {
                    if (is_file($bsIdxFile)) {
                        $decodedBs = json_decode(@file_get_contents($bsIdxFile), true);
                        if (isset($decodedBs['items']) && is_array($decodedBs['items'])) {
                            $backstageIndexMap = $decodedBs['items'];
                            break;
                        }
                    }
                }
                if (empty($backstageIndexMap) && !empty($cdnBaseUrl)) {
                    $remoteBs = @file_get_contents($cdnBaseUrl . '/raggiesoft-backstage/api/v1/index.json');
                    if ($remoteBs !== false) {
                        $decodedBs = json_decode($remoteBs, true);
                        if (isset($decodedBs['items']) && is_array($decodedBs['items'])) {
                            $backstageIndexMap = $decodedBs['items'];
                        }
                    }
                }
            }
        ?>
        <?php if (!empty($backstageSlugs)): ?>
        <section id="rs-backstage-callout" class="rs-backstage-callout" aria-labelledby="rs-backstage-callout-heading" style="margin-top: 2.75rem; padding: 1.5rem 1.75rem; border: 1px solid var(--rs-border); border-left: 5px solid var(--rs-primary); border-radius: 12px; background: var(--rs-surface); box-shadow: 0 4px 18px rgba(0,0,0,0.06);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.65rem;">
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--rs-primary);">
                    <i class="ph ph-film-slate" aria-hidden="true"></i> RaggieSoft Backstage &bull; Bonus Features &amp; Correspondence
                </span>
                <a href="https://backstage.raggiesoft.com" target="_blank" rel="noopener noreferrer" data-backstage-direct="true" style="font-size: 0.82rem; font-weight: 700; color: var(--rs-primary); text-decoration: underline; text-underline-offset: 3px;">
                    Explore Backstage Hub &nearr;
                </a>
            </div>
            <h3 id="rs-backstage-callout-heading" style="margin: 0 0 0.45rem; font-size: 1.2rem;">
                Behind-the-Scenes Records &amp; Background Correspondence for This Scene
            </h3>
            <p style="margin: 0 0 1.15rem; font-size: 0.95rem; opacity: 0.88;">
                Read the accompanying in-universe administrative correspondence or behind-the-scenes dossier right here in a quick-view dialog, or bounce over to <strong>RaggieSoft Backstage</strong>.
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <?php foreach ($backstageSlugs as $bsSlug):
                    $cleanSlug = ltrim(preg_replace('#^https?://backstage\.raggiesoft\.com/#i', '', $bsSlug), '/');
                    $bsMeta = $backstageIndexMap[$cleanSlug] ?? [];
                    $bsTitle = $bsMeta['title'] ?? ucwords(str_replace(['-', '/'], [' ', ' — '], basename($cleanSlug)));
                    $bsSubtitle = $bsMeta['subtitle'] ?? '';
                    $bsDate = $bsMeta['date'] ?? '';
                    $bsTime = $bsMeta['time'] ?? '';
                    $bsSender = $bsMeta['sender'] ?? '';
                    $bsSummary = $bsMeta['summary'] ?? '';
                    $bsFullUrl = 'https://backstage.raggiesoft.com/' . $cleanSlug;
                ?>
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding: 1rem 1.15rem; border: 1px solid var(--rs-border); border-radius: 10px; background: var(--rs-bg);">
                    <div style="flex: 1; min-width: 240px;">
                        <?php if ($bsDate || $bsSender): ?>
                        <div style="font-size: 0.78rem; font-weight: 700; color: var(--rs-text-muted); margin-bottom: 0.2rem;">
                            <?php if ($bsDate): ?><span><?php echo htmlspecialchars($bsDate . ($bsTime ? ' • ' . $bsTime : '')); ?></span><?php endif; ?>
                            <?php if ($bsDate && $bsSender): ?> &bull; <?php endif; ?>
                            <?php if ($bsSender): ?><span>From: <?php echo htmlspecialchars($bsSender); ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div style="font-weight: 800; font-size: 1.05rem; color: var(--rs-text);">
                            <?php echo htmlspecialchars($bsTitle); ?>
                        </div>
                        <?php if ($bsSubtitle): ?>
                        <div style="font-size: 0.88rem; font-weight: 600; opacity: 0.9; margin-top: 0.1rem;">
                            <?php echo htmlspecialchars($bsSubtitle); ?>
                        </div>
                        <?php elseif ($bsSummary): ?>
                        <div style="font-size: 0.88rem; opacity: 0.85; margin-top: 0.15rem;">
                            <?php echo htmlspecialchars($bsSummary); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap;">
                        <button type="button" class="rs-btn rs-btn-primary" data-backstage-slug="<?php echo htmlspecialchars($cleanSlug); ?>" style="padding: 0.5rem 0.95rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                            <i class="ph ph-envelope-open" aria-hidden="true"></i>
                            <span>Read in Dialog</span>
                        </button>
                        <a href="<?php echo htmlspecialchars($bsFullUrl); ?>" target="_blank" rel="noopener noreferrer" data-backstage-direct="true" class="rs-btn" style="padding: 0.5rem 0.85rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <span>Open in Backstage</span>
                            <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <dialog id="rs-backstage-dialog" aria-labelledby="rs-backstage-dialog-title" style="padding: 0; border: 1px solid var(--rs-border); border-radius: 14px; background: var(--rs-bg); color: var(--rs-text); box-shadow: 0 16px 48px rgba(0,0,0,0.45); max-width: 820px; width: 92%; max-height: 86vh; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--rs-border); background: var(--rs-surface);">
                <div>
                    <div id="rs-backstage-dialog-kicker" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--rs-primary);">
                        🎬 RaggieSoft Backstage Archive
                    </div>
                    <h3 id="rs-backstage-dialog-title" style="margin: 0.15rem 0 0; font-size: 1.22rem; font-weight: 800;">Backstage Dossier</h3>
                </div>
                <button type="button" id="rs-backstage-dialog-close" class="rs-btn" aria-label="Close Backstage dialog" title="Close Backstage dialog" style="padding: 0.45rem 0.75rem; font-size: 1rem; cursor: pointer;">
                    <i class="ph ph-x" aria-hidden="true"></i> Close
                </button>
            </div>
            <div id="rs-backstage-dialog-meta" style="padding: 0.65rem 1.5rem; border-bottom: 1px solid var(--rs-border); background: var(--rs-surface); font-size: 0.85rem; opacity: 0.9; display: none;"></div>
            <div id="rs-backstage-dialog-body" class="story-content" style="padding: 1.5rem; overflow-y: auto; max-height: calc(86vh - 165px); font-size: 1.02rem; line-height: 1.68; text-indent: 0;">
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 0.85rem 1.5rem; border-top: 1px solid var(--rs-border); background: var(--rs-surface); font-size: 0.85rem;">
                <span style="color: var(--rs-text-muted);">100% In-Universe Bonus Feature &bull; RaggieSoft Backstage Archive</span>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a id="rs-backstage-dialog-bounce" href="https://backstage.raggiesoft.com" target="_blank" rel="noopener noreferrer" data-backstage-direct="true" class="rs-btn rs-btn-primary" style="padding: 0.45rem 0.95rem; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph ph-film-slate" aria-hidden="true"></i>
                        <span>Bounce to RaggieSoft Backstage</span>
                        <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </dialog>
        <?php endif; ?>

        <?php
        // =====================================================================
        // SUBSYSTEM 11: RAGGIESOFT STORY CROSSROADS INTERACTIVE ROUTE CARDS
        // =====================================================================
        // 100-Level Branching Narrative Graph Walkthrough:
        //   - When a chapter sits at a **Story Crossroads** decision point (`$config['crossroads']`
        //     compiled by `paige.php` from `books/<slug>/crossroads.json`), Subsystem 5 above
        //     intentionally left `$nextUrl` empty so the reader pauses right here at the end
        //     of the scene to choose which coequal route the story takes next!
        //   - Emblem Selection: Automatically selects `story-crossroads-logo-2way-512.png` for
        //     2-option Crossroads or `story-crossroads-logo-3way-512.png` when 3+ options exist.
        //   - Persistence Handshake: Each option button carries `data-story-path="<optId>"`,
        //     `data-story-crossroads="<cxId>"`, and `data-story-book="<seriesSlug>"`.
        //     When clicked, `reader.js` intercepts the click in the DOM *capture* phase
        //     (`addEventListener('click', ..., true)`) to write both the `rs_cx_<book>` cookie
        //     AND `localStorage['rs-story-choices-<book>']` *before* `stardust-spa.min.js`
        //     fetches `$optEntryUrl`, so `isabel.php` immediately unlocks the chosen branch!
        $partCrossroads = (!$specialPageType && !empty($config['crossroads']) && is_array($config['crossroads']))
            ? $config['crossroads']
            : null;
        if ($partCrossroads !== null):
            $cxOptionsList = is_array($partCrossroads['options'] ?? null) ? $partCrossroads['options'] : [];
            // Select 2-directional (Option A/B) vs 3-directional (3+ options) Story Crossroads emblem
            $cxSignpostDirs = (count($cxOptionsList) >= 3) ? 3 : 2;
            $cxBrandLogoUrl = rtrim($cdnBaseUrl, '/') . "/raggiesoft-books/images/logos/story-crossroads-logo-{$cxSignpostDirs}way-512.png";
        ?>
        <!-- Coequal Interactive Story Crossroads Decision Cards -->
        <section id="rs-story-crossroads" class="rs-crossroads-section" aria-labelledby="rs-crossroads-heading">
            <div class="rs-crossroads-header">
                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.5rem;">
                    <img src="<?php echo htmlspecialchars($cxBrandLogoUrl); ?>"
                         alt=""
                         aria-hidden="true"
                         width="52"
                         height="52"
                         loading="lazy"
                         style="width: 52px; height: 52px; border-radius: 12px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.22); border: 1px solid rgba(227, 178, 124, 0.45);">
                    <div class="rs-crossroads-kicker" style="margin-bottom: 0;">
                        <i class="ph-fill ph-signpost" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars((string)($partCrossroads['kicker'] ?? 'RaggieSoft Story Crossroads')); ?></span>
                    </div>
                </div>
                <h2 id="rs-crossroads-heading" class="rs-crossroads-title"><?php echo htmlspecialchars((string)($partCrossroads['title'] ?? 'Choose How the Story Continues')); ?></h2>
                <?php if (!empty($partCrossroads['prompt'])): ?>
                    <p class="rs-crossroads-prompt"><?php echo htmlspecialchars((string)$partCrossroads['prompt']); ?></p>
                <?php endif; ?>
            </div>
            <div class="rs-crossroads-grid">
                <?php foreach ($cxOptionsList as $cxOpt):
                    $optId = (string)($cxOpt['id'] ?? '');
                    $optLabel = (string)($cxOpt['label'] ?? '');
                    $optSub = (string)($cxOpt['subtitle'] ?? '');
                    $optEntryUrl = (string)($cxOpt['entry_url'] ?? '');
                    $optImgRaw = (string)($cxOpt['image'] ?? '');
                    $optImg = $optImgRaw !== ''
                        ? ((strpos($optImgRaw, 'http') === 0) ? $optImgRaw : rtrim($cdnBaseUrl, '/') . '/' . ltrim($optImgRaw, '/'))
                        : '';
                    $partCxId = (string)($partCrossroads['id'] ?? '');
                    $isSavedChoice = (($activeCrossroadsChoices[$partCxId] ?? null) === $optId) || ($activeStoryPathId !== null && $activeStoryPathId === $optId);
                ?>
                <article class="rs-crossroads-card<?php echo $isSavedChoice ? ' is-selected' : ''; ?>">
                    <?php if ($optImg !== ''): ?>
                        <img src="<?php echo htmlspecialchars($optImg); ?>" alt="<?php echo htmlspecialchars($optLabel); ?>" class="rs-crossroads-card-img" loading="lazy">
                    <?php endif; ?>
                    <div class="rs-crossroads-card-body">
                        <?php if ($isSavedChoice): ?>
                            <span class="rs-crossroads-saved-pill">
                                <i class="ph-fill ph-check-circle" aria-hidden="true"></i>
                                <span>Currently Selected Path</span>
                            </span>
                        <?php endif; ?>
                        <h3 class="rs-crossroads-card-title"><?php echo htmlspecialchars($optLabel); ?></h3>
                        <?php if ($optSub !== ''): ?>
                            <p class="rs-crossroads-card-desc"><?php echo htmlspecialchars($optSub); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="rs-crossroads-card-footer">
                        <a href="<?php echo htmlspecialchars($optEntryUrl); ?>"
                           data-story-path="<?php echo htmlspecialchars($optId); ?>"
                           data-story-crossroads="<?php echo htmlspecialchars($partCxId); ?>"
                           data-story-book="<?php echo htmlspecialchars($seriesSlug); ?>"
                           class="rs-btn rs-btn-primary rs-crossroads-choose-btn">
                            <span><?php echo $isSavedChoice ? 'Continue This Story Path' : 'Follow This Story Path'; ?></span>
                            <i class="ph ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php
        // Light-Dismiss Story Path Switcher Dialog (`<dialog closedby="any">`) for switching paths anywhere in the novel
        if (!$specialPageType && !empty($oliverCrossroadsList)):
            $dlgCx = $oliverCrossroadsList[0];
            foreach ($oliverCrossroadsList as $candCx) {
                if (!empty($config['crossroadsId']) && ($candCx['id'] ?? '') === $config['crossroadsId']) {
                    $dlgCx = $candCx;
                    break;
                }
            }
        ?>
        <dialog id="rs-story-crossroads-dialog" closedby="any" aria-labelledby="rs-story-crossroads-dialog-title" class="rs-crossroads-dialog">
            <div class="rs-arc-dialog-header">
                <div>
                    <div class="rs-crossroads-kicker">
                        <i class="ph-fill ph-signpost" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($seriesTitle); ?> &bull; Story Crossroads</span>
                    </div>
                    <h3 id="rs-story-crossroads-dialog-title" class="rs-arc-dialog-title"><?php echo htmlspecialchars((string)($dlgCx['title'] ?? 'Switch Story Path')); ?></h3>
                </div>
                <button type="button" id="rs-story-crossroads-close" class="rs-btn" aria-label="Close Story Path Switcher">
                    <i class="ph ph-x" aria-hidden="true"></i> Close
                </button>
            </div>
            <div class="rs-arc-dialog-body">
                <?php if (!empty($dlgCx['prompt'])): ?>
                    <p class="rs-arc-dialog-desc"><?php echo htmlspecialchars((string)$dlgCx['prompt']); ?></p>
                <?php endif; ?>
                <div class="rs-crossroads-grid">
                    <?php foreach (($dlgCx['options'] ?? []) as $dOpt):
                        $dOptId = (string)($dOpt['id'] ?? '');
                        $dOptLabel = (string)($dOpt['label'] ?? '');
                        $dOptSub = (string)($dOpt['subtitle'] ?? '');
                        $dOptUrl = (string)($dOpt['entry_url'] ?? '');
                        $dlgCxId = (string)($dlgCx['id'] ?? '');
                        $dIsCurrent = (($activeCrossroadsChoices[$dlgCxId] ?? null) === $dOptId) || ($activeStoryPathId !== null && $activeStoryPathId === $dOptId);
                    ?>
                    <article class="rs-crossroads-card<?php echo $dIsCurrent ? ' is-selected' : ''; ?>">
                        <div class="rs-crossroads-card-body">
                            <?php if ($dIsCurrent): ?>
                                <span class="rs-crossroads-saved-pill">
                                    <i class="ph-fill ph-check-circle" aria-hidden="true"></i>
                                    <span>Current Story Path</span>
                                </span>
                            <?php endif; ?>
                            <h4 class="rs-crossroads-card-title"><?php echo htmlspecialchars($dOptLabel); ?></h4>
                            <?php if ($dOptSub !== ''): ?>
                                <p class="rs-crossroads-card-desc"><?php echo htmlspecialchars($dOptSub); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="rs-crossroads-card-footer">
                            <a href="<?php echo htmlspecialchars($dOptUrl); ?>"
                               data-story-path="<?php echo htmlspecialchars($dOptId); ?>"
                               data-story-crossroads="<?php echo htmlspecialchars($dlgCxId); ?>"
                               data-story-book="<?php echo htmlspecialchars($seriesSlug); ?>"
                               class="rs-btn rs-btn-primary rs-crossroads-choose-btn">
                                <span><?php echo $dIsCurrent ? 'Start From Beginning of Path' : 'Switch to This Story Path'; ?></span>
                                <i class="ph ph-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (!empty($dlgCx['trigger_url'])): ?>
            <div class="rs-arc-dialog-footer">
                <a href="<?php echo htmlspecialchars((string)$dlgCx['trigger_url']); ?>" class="rs-btn">
                    <i class="ph ph-arrow-u-up-left" aria-hidden="true"></i>
                    <span>Re-read the Story Crossroads Scene</span>
                </a>
                <a href="/<?php echo htmlspecialchars($seriesSlug); ?>/toc" class="rs-btn">
                    <i class="ph ph-list" aria-hidden="true"></i>
                    <span>Open Table of Contents</span>
                </a>
            </div>
            <?php endif; ?>
        </dialog>
        <?php endif; ?>
        
        <?php
        // =====================================================================
        // SUBSYSTEM 11B: OUT-OF-PROSE REAL-WORLD COMPANION PROJECT CARD
        // =====================================================================
        // 100-Level Chronological-Integrity Walkthrough:
        //   - Scenes set in past years (such as August 2015 in `books/aubrie/e007/c001/p003.md`)
        //     must NEVER break the fourth wall inside the story prose itself.
        //   - Instead, a scene's YAML frontmatter can optionally specify `companion_site_url`
        //     alongside `companion_site_kicker`, `companion_site_title`, `companion_site_copy`,
        //     and `companion_site_cta`.
        //   - Right after the story `<article>` and Backstage cards finish, Oliver renders this
        //     warm, reader-friendly companion card so readers can jump straight to real-world
        //     projects inspired by the chapter (such as `https://finsharp.raggiesoft.dev`)!
        if (!$specialPageType && !empty($frontmatter['companion_site_url'])):
            $compUrl    = trim((string)$frontmatter['companion_site_url']);
            $compKicker = trim((string)($frontmatter['companion_site_kicker'] ?? 'Companion Project'));
            $compTitle  = trim((string)($frontmatter['companion_site_title'] ?? 'Explore the Real-World Companion Project'));
            $compCopy   = trim((string)($frontmatter['companion_site_copy'] ?? ''));
            $compCta    = trim((string)($frontmatter['companion_site_cta'] ?? 'Visit Companion Site'));
        ?>
        <section id="rs-companion-project-card" aria-labelledby="rs-companion-project-heading" style="margin-top: 2.75rem; padding: 1.6rem 1.8rem; border: 1px solid var(--rs-border); border-left: 5px solid var(--rs-primary); border-radius: 12px; background: var(--rs-surface); box-shadow: 0 4px 18px rgba(0,0,0,0.06);">
            <div style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--rs-primary); margin-bottom: 0.45rem;">
                <i class="ph ph-scales" aria-hidden="true"></i> <?php echo htmlspecialchars($compKicker); ?>
            </div>
            <h3 id="rs-companion-project-heading" style="margin: 0 0 0.5rem; font-size: 1.22rem;">
                <?php echo htmlspecialchars($compTitle); ?>
            </h3>
            <?php if ($compCopy !== ''): ?>
            <p style="margin: 0 0 1.1rem; font-size: 0.98rem; line-height: 1.6; opacity: 0.9;">
                <?php echo htmlspecialchars($compCopy); ?>
            </p>
            <?php endif; ?>
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                <a href="<?php echo htmlspecialchars($compUrl); ?>" target="_blank" rel="noopener noreferrer" data-no-spa="true" class="rs-btn rs-btn-primary" style="padding: 0.6rem 1.15rem; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.45rem;">
                    <span><?php echo htmlspecialchars($compCta); ?></span>
                    <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                </a>
                <a href="https://raggiesoft.dev" target="_blank" rel="noopener noreferrer" data-no-spa="true" class="rs-btn" style="padding: 0.6rem 1.05rem; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <span>Explore All Open-Source Projects on raggiesoft.dev</span>
                    <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- E-Book CTA Card (Dismissible; persisted in reader-settings.showOfflineCta and resettable in Settings) -->
        <?php if (!$specialPageType):
            global $ebookSizes;
            $oliverSizes = !empty($ebookSizes) && is_array($ebookSizes)
                ? $ebookSizes
                : ($routeData['common']['ebook_sizes'] ?? ($toc_manifest['ebook_sizes'] ?? []));
            $oliverEpubSize     = $oliverSizes['epub'] ?? '';
            $oliverPdfSize      = $oliverSizes['pdf'] ?? '';
            $oliverTextEpubSize = $oliverSizes['text_epub'] ?? '';
            $oliverTextPdfSize  = $oliverSizes['text_pdf'] ?? '';
        ?>
        <div id="rs-offline-cta-card" class="rs-offline-cta-card" style="position: relative; margin-top: 4rem; padding: 2rem; border: 1px solid var(--rs-border); border-radius: 12px; background: var(--rs-surface); text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <button type="button" id="rs-dismiss-offline-cta" class="rs-btn" title="Dismiss card (can be re-enabled in Settings)" aria-label="Dismiss Take this Story Offline card" style="position: absolute; top: 0.75rem; right: 0.75rem; padding: 0.3rem 0.65rem; font-size: 0.8rem; opacity: 0.75; display: inline-flex; align-items: center; gap: 0.25rem;">
                <i class="ph ph-x"></i> Hide
            </button>
            <h3 style="margin-bottom: 0.75rem; color: var(--rs-primary);">Take this Story Offline</h3>
            <p style="margin-bottom: 1.25rem; font-size: 1.05rem; opacity: 0.9;">Download the complete eBook for your e-reader, tablet, or phone in either the full Illustrated Edition or the lightweight Text-Only Edition. Yours to keep forever.</p>
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.epub"); ?>" target="_blank" class="rs-btn rs-btn-primary" title="Download Illustrated EPUB<?php echo $oliverEpubSize ? ' (' . htmlspecialchars($oliverEpubSize) . ')' : ''; ?>" style="font-size: 1.05rem; padding: 0.7rem 1.4rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-download-simple"></i>
                    <span>Download EPUB<?php echo $oliverEpubSize ? ' <span style="opacity: 0.88; font-weight: 500; font-size: 0.92em;">(' . htmlspecialchars($oliverEpubSize) . ')</span>' : ''; ?></span>
                </a>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.pdf"); ?>" target="_blank" class="rs-btn" title="Download Illustrated PDF<?php echo $oliverPdfSize ? ' (' . htmlspecialchars($oliverPdfSize) . ')' : ''; ?>" style="font-size: 1.05rem; padding: 0.7rem 1.4rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-file-pdf"></i>
                    <span>Download PDF<?php echo $oliverPdfSize ? ' <span style="opacity: 0.82; font-weight: 500; font-size: 0.92em;">(' . htmlspecialchars($oliverPdfSize) . ')</span>' : ''; ?></span>
                </a>
            </div>
            <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px dashed var(--rs-border); font-size: 0.88rem; opacity: 0.9; display: flex; align-items: center; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                <span><i class="ph ph-file-text"></i> Text-Only Edition (no images):</span>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.epub"); ?>" target="_blank" title="Download Text-Only EPUB (No Images<?php echo $oliverTextEpubSize ? ' — ' . htmlspecialchars($oliverTextEpubSize) : ''; ?>)" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">EPUB &mdash; Text Only<?php echo $oliverTextEpubSize ? ' (' . htmlspecialchars($oliverTextEpubSize) . ')' : ''; ?></a>
                <span aria-hidden="true">&bull;</span>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.pdf"); ?>" target="_blank" title="Download Text-Only PDF (No Images<?php echo $oliverTextPdfSize ? ' — ' . htmlspecialchars($oliverTextPdfSize) : ''; ?>)" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">PDF &mdash; Text Only<?php echo $oliverTextPdfSize ? ' (' . htmlspecialchars($oliverTextPdfSize) . ')' : ''; ?></a>
            </div>
        </div>
        <script>
        /*
         * SUBSYSTEM 12A: DISMISSIBLE OFFLINE E-BOOK CARD PERSISTENCE CHECK
         * Immediately checks `reader-settings.showOfflineCta` in `localStorage` before painting
         * below the fold so if the reader clicked "Hide" (or unchecked the toggle in `settings.php`),
         * `#rs-offline-cta-card` stays hidden with zero layout flicker.
         */
        (function() {
            try {
                var s = JSON.parse(localStorage.getItem('reader-settings') || '{}');
                if (s && s.showOfflineCta === false) {
                    var c = document.getElementById('rs-offline-cta-card');
                    if (c) c.style.display = 'none';
                }
            } catch (e) {}
        })();
        </script>
        <?php endif; ?>

        <!-- Interleaved Story Arc Bonus Stops & Bottom Sequence Navigation -->
        <?php if (!$specialPageType && $activeStoryArc !== null && !empty($activeArcInterleavedBackstageStops)): ?>
        <section class="rs-arc-bonus-checkpoint" aria-labelledby="rs-arc-bonus-checkpoint-heading">
            <div class="rs-arc-reader-kicker">
                <i class="ph-fill ph-film-slate" aria-hidden="true"></i>
                <span>Next Stop in This Story Arc &bull; Behind-the-Scenes Bonus <?php echo count($activeArcInterleavedBackstageStops) === 1 ? 'Scene' : 'Scenes'; ?></span>
            </div>
            <h3 id="rs-arc-bonus-checkpoint-heading" class="rs-arc-bonus-checkpoint-title">
                Read the Bonus Backstage <?php echo count($activeArcInterleavedBackstageStops) === 1 ? 'Scene' : 'Scenes'; ?> Before Moving On
            </h3>
            <div class="rs-arc-bonus-checkpoint-list">
                <?php foreach ($activeArcInterleavedBackstageStops as $bsStop):
                    $bsStopSlug = (string)($bsStop['backstage_slug'] ?? '');
                    $bsStopUrl  = (string)($bsStop['url'] ?? 'https://backstage.raggiesoft.com/');
                ?>
                <div class="rs-arc-bonus-checkpoint-card">
                    <div class="rs-arc-bonus-checkpoint-copy">
                        <div class="rs-arc-stop-kicker">
                            <span class="rs-arc-kind-badge is-backstage">Stop <?php echo (int)($bsStop['stop_num'] ?? 0); ?> &bull; Bonus Scene</span>
                            <?php if (!empty($bsStop['date'])): ?><span><?php echo htmlspecialchars((string)$bsStop['date']); ?></span><?php endif; ?>
                            <span>&bull; <?php echo (int)($bsStop['reading_minutes'] ?? 2); ?> min</span>
                        </div>
                        <div class="rs-arc-bonus-checkpoint-name"><?php echo htmlspecialchars((string)($bsStop['title'] ?? '')); ?></div>
                        <?php if (!empty($bsStop['excerpt'])): ?>
                        <p class="rs-arc-bonus-checkpoint-desc"><?php echo htmlspecialchars((string)$bsStop['excerpt']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="rs-arc-bonus-checkpoint-btns">
                        <?php if ($bsStopSlug !== ''): ?>
                        <button type="button" class="rs-btn rs-btn-primary" data-backstage-slug="<?php echo htmlspecialchars($bsStopSlug); ?>">
                            <i class="ph ph-envelope-open" aria-hidden="true"></i>
                            <span>Read Bonus Scene</span>
                        </button>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($bsStopUrl); ?>" target="_blank" rel="noopener noreferrer" data-no-spa="true" data-backstage-direct="true" class="rs-btn">
                            <span>Open in Backstage</span>
                            <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Share Section for Reader -->
        <?php if (!$specialPageType): ?>
        <?php 
        $currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
        $encodedUrl = urlencode($currentUrl);
        $encodedText = urlencode("Reading " . $title . " from " . $seriesTitle . " by Michael Ragsdale.");
        ?>
        <div class="bottom-nav-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--rs-border);">
            <div style="flex: 1; display: flex; justify-content: flex-start;">
                <?php if ($prevUrl): ?>
                    <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="rs-btn" id="reader-btn-prev">&larr; <?php echo htmlspecialchars($activeArcPrevLabel); ?></a>
                <?php else: ?>
                    <button class="rs-btn" disabled style="opacity: 0.5; cursor: not-allowed;" id="reader-btn-prev">&larr; <?php echo htmlspecialchars($activeArcPrevLabel); ?></button>
                <?php endif; ?>
            </div>

            <div style="flex: 2; display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; text-align: center;">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn" title="Share on Facebook">
                    <i class="ph ph-facebook-logo"></i> <span class="hide-on-mobile">Facebook</span>
                </a>
                
                <a href="https://bsky.app/intent/compose?text=<?php echo $encodedText . '%20' . $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn" title="Share on Bluesky">
                    <i class="ph ph-cloud-sun"></i> <span class="hide-on-mobile">Bluesky</span>
                </a>

                <button type="button" data-copy-share-url="<?php echo htmlspecialchars($currentUrl); ?>" class="rs-btn" title="Copy Link">
                    <i class="ph ph-link"></i> <span class="hide-on-mobile">Copy Link</span>
                </button>
            </div>

            <div style="flex: 1; display: flex; justify-content: flex-end;">
                <?php if ($nextUrl): ?>
                    <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="rs-btn rs-btn-primary" id="reader-btn-next"><?php echo htmlspecialchars($activeArcNextLabel); ?> &rarr;</a>
                <?php elseif (!empty($partCrossroads)): ?>
                    <a href="#rs-story-crossroads" data-no-spa="true" class="rs-btn rs-btn-primary" id="reader-btn-next">Choose Story Path &uarr;</a>
                <?php elseif ($activeStoryArc !== null): ?>
                    <a href="/arcs?book=<?php echo htmlspecialchars($seriesSlug); ?>" class="rs-btn rs-btn-primary" id="reader-btn-next">All Story Arcs &rarr;</a>
                <?php else: ?>
                    <button class="rs-btn rs-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;" id="reader-btn-next"><?php echo htmlspecialchars($activeArcNextLabel); ?> &rarr;</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($activeStoryArc !== null):
            $dlgStops = is_array($activeStoryArc['stops'] ?? null) ? $activeStoryArc['stops'] : [];
            $dlgCurrNum = (int)($activeArcCurrentStop['stop_num'] ?? 0);
        ?>
        <!-- Native <dialog closedby="any"> Story Arc Playlist Drawer (`modern-web-guidance: light-dismiss-a-dialog`) -->
        <dialog id="rs-arc-playlist-dialog" closedby="any" aria-labelledby="rs-arc-playlist-title" class="rs-arc-playlist-dialog">
            <div class="rs-arc-dialog-header">
                <div>
                    <div class="rs-arc-reader-kicker">
                        <i class="ph-fill ph-playlist" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($seriesTitle); ?> &bull; Story Arc Playlist</span>
                    </div>
                    <h3 id="rs-arc-playlist-title" class="rs-arc-dialog-title"><?php echo htmlspecialchars((string)($activeStoryArc['title'] ?? '')); ?></h3>
                </div>
                <button type="button" id="rs-arc-playlist-close" class="rs-btn" aria-label="Close Story Arc Playlist">
                    <i class="ph ph-x" aria-hidden="true"></i> Close
                </button>
            </div>
            <div class="rs-arc-dialog-body">
                <?php if (!empty($activeStoryArc['description'])): ?>
                <p class="rs-arc-dialog-desc"><?php echo htmlspecialchars((string)$activeStoryArc['description']); ?></p>
                <?php endif; ?>
                <ol class="rs-arc-stop-list">
                    <?php foreach ($dlgStops as $dStop):
                        $dKind   = (string)($dStop['kind'] ?? 'part');
                        $dNum    = (int)($dStop['stop_num'] ?? 0);
                        $isCurr  = ($dNum === $dlgCurrNum);
                        $dTitle  = (string)($dStop['title'] ?? '');
                        $dSub    = (string)($dStop['subtitle'] ?? '');
                        $dDate   = (string)($dStop['date'] ?? '');
                        $dMins   = (int)($dStop['reading_minutes'] ?? 1);
                        $dArcUrl = (string)($dStop['arc_url'] ?? '#');
                        $dBsSlug = (string)($dStop['backstage_slug'] ?? '');
                    ?>
                    <li class="rs-arc-stop-row is-<?php echo htmlspecialchars($dKind); ?><?php echo $isCurr ? ' is-current' : ''; ?>" <?php if ($isCurr): ?>aria-current="step"<?php endif; ?>>
                        <span class="rs-arc-stop-num"><?php echo $dNum; ?></span>
                        <div class="rs-arc-stop-info">
                            <div class="rs-arc-stop-kicker">
                                <?php if ($isCurr): ?>
                                    <span class="rs-arc-kind-badge is-current">Reading Now</span>
                                <?php elseif ($dKind === 'backstage'): ?>
                                    <span class="rs-arc-kind-badge is-backstage"><i class="ph-fill ph-film-slate" aria-hidden="true"></i> Bonus Scene</span>
                                <?php else: ?>
                                    <span class="rs-arc-kind-badge is-part"><i class="ph-fill ph-book-open" aria-hidden="true"></i> Chapter</span>
                                <?php endif; ?>
                                <?php if ($dDate !== ''): ?><span><?php echo htmlspecialchars($dDate); ?></span><?php endif; ?>
                                <span>&bull; <?php echo $dMins; ?> min</span>
                            </div>
                            <?php if ($dKind === 'backstage' && $dBsSlug !== ''): ?>
                                <button type="button" class="rs-arc-stop-link-btn" data-backstage-slug="<?php echo htmlspecialchars($dBsSlug); ?>" data-close-arc-dialog="true">
                                    <?php echo htmlspecialchars($dTitle); ?>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($dArcUrl); ?>" class="rs-arc-stop-link" data-close-arc-dialog="true">
                                    <?php echo htmlspecialchars($dTitle); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($dSub !== ''): ?>
                                <div class="rs-arc-stop-sub"><?php echo htmlspecialchars($dSub); ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="rs-arc-dialog-footer">
                <a href="/arcs?book=<?php echo htmlspecialchars($seriesSlug); ?>" class="rs-btn">
                    <i class="ph ph-compass" aria-hidden="true"></i>
                    <span>Browse All Story Arcs</span>
                </a>
                <a href="<?php echo htmlspecialchars($requestUri); ?>" class="rs-btn">
                    <i class="ph ph-book-open" aria-hidden="true"></i>
                    <span>Switch to Full Book</span>
                </a>
            </div>
        </dialog>
        <?php endif; ?>
        
        <style>
            @media (max-width: 768px) {
                .hide-on-mobile { display: none !important; }
                .bottom-nav-container { flex-direction: column-reverse; gap: 2rem !important; }
            }
        </style>
        <?php
        // Shared Native <dialog> Lightbox Modal (DRY component shared with Table of Illustrations)
        // Enables clicking or pressing Enter/Space on any inline or hero story image in a chapter
        // to inspect the full-size illustration in `#ova-illus-lightbox`.
        require $basePath . '/includes/components/illustration-lightbox-dialog.php';
        ?>
        <?php endif; ?>

    </div>
</main>

<style>
#stardust-reading-pane { padding-bottom: 6rem !important; }

/* ==========================================================================
 * OLIVER READER: ZOË PATEL COGNITIVE KERNEL LOG SYNTAX HIGHLIGHTER STYLES
 * ==========================================================================
 * Ensures immediate, zero-FOUC WCAG 2.1 AAA syntax highlighting for Zoë's
 * internal C#/.NET application logs (`ZOË.Systems.Logs.*`) across Light Mode,
 * Sepia Mode, Dark Mode, and custom narrative themes during SPA navigation.
 * ========================================================================== */
.story-content .zoe-kernel-log,
.story-content .zoe-kernel-log div,
.story-content .zoe-kernel-log p {
    text-indent: 0 !important;
}
.story-content .zoe-kernel-log::first-letter,
.story-content .zoe-kernel-log *::first-letter {
    float: none !important;
    font-size: inherit !important;
    line-height: inherit !important;
    margin: 0 !important;
    font-weight: inherit !important;
    color: inherit !important;
}
.zoe-kernel-log {
    --zkl-bg: #f8fafc;
    --zkl-header-bg: #eef2f6;
    --zkl-border: #cbd5e1;
    --zkl-row-border: rgba(148, 163, 184, 0.24);
    --zkl-row-hover: rgba(15, 23, 42, 0.04);
    --zkl-text: #0f172a;
    --zkl-gutter: #475569;
    --zkl-ns: #475569;
    --zkl-stream: #0369a1;
    --zkl-punct: #475569;
    --zkl-severity-fg: #991b1b;
    --zkl-severity-bg: rgba(185, 28, 28, 0.10);
    --zkl-severity-border: rgba(185, 28, 28, 0.30);
    --zkl-exception: #b91c1c;
    --zkl-thread-fg: #1e40af;
    --zkl-thread-bg: rgba(30, 64, 175, 0.09);
    --zkl-bracket: #166534;
    --zkl-string: #9a3412;
    --zkl-label: #6d28d9;
    --zkl-diagnostic: #115e59;
    --zkl-function: #7e22ce;
    --zkl-identifier: #155e75;
    --zkl-constant: #be123c;
    --zkl-number: #92400e;
    --zkl-operator: #334155;
    --zkl-ts-fg: #334155;
    --zkl-ts-bg: #e2e8f0;
    font-family: "JetBrains Mono", "Fira Code", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.88em;
    line-height: 1.6;
    background: var(--zkl-bg);
    color: var(--zkl-text);
    border: 1px solid var(--zkl-border);
    border-radius: 10px;
    overflow: hidden;
    margin: 1.75rem 0;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
}
body.theme-sepia .zoe-kernel-log {
    --zkl-bg: #f6efe0;
    --zkl-header-bg: #ebe1cc;
    --zkl-border: #d3c4a8;
    --zkl-gutter: #4a3b2c;
    --zkl-ns: #4a3b2c;
    --zkl-punct: #4a3b2c;
    --zkl-ts-bg: #e6dac2;
    --zkl-ts-fg: #3e3122;
}
@media (prefers-color-scheme: light) {
    body.theme-sepia-system .zoe-kernel-log {
        --zkl-bg: #f6efe0;
        --zkl-header-bg: #ebe1cc;
        --zkl-border: #d3c4a8;
        --zkl-gutter: #4a3b2c;
        --zkl-ns: #4a3b2c;
        --zkl-punct: #4a3b2c;
        --zkl-ts-bg: #e6dac2;
        --zkl-ts-fg: #3e3122;
    }
}
body.theme-dark .zoe-kernel-log,
body.theme-dark-sepia .zoe-kernel-log,
body.theme-night:not(.theme-light):not(.theme-sepia):not(.theme-auto):not(.theme-sepia-system) .zoe-kernel-log {
    --zkl-bg: #0b1120;
    --zkl-header-bg: #111827;
    --zkl-border: #1e293b;
    --zkl-row-border: rgba(148, 163, 184, 0.14);
    --zkl-row-hover: rgba(56, 189, 248, 0.06);
    --zkl-text: #e2e8f0;
    --zkl-gutter: #94a3b8;
    --zkl-ns: #94a3b8;
    --zkl-stream: #38bdf8;
    --zkl-punct: #94a3b8;
    --zkl-severity-fg: #fecaca;
    --zkl-severity-bg: rgba(239, 68, 68, 0.20);
    --zkl-severity-border: rgba(248, 113, 113, 0.45);
    --zkl-exception: #f87171;
    --zkl-thread-fg: #93c5fd;
    --zkl-thread-bg: rgba(59, 130, 246, 0.18);
    --zkl-bracket: #4ade80;
    --zkl-string: #fdba74;
    --zkl-label: #c084fc;
    --zkl-diagnostic: #2dd4bf;
    --zkl-function: #e879f9;
    --zkl-identifier: #67e8f9;
    --zkl-constant: #fda4af;
    --zkl-number: #fde047;
    --zkl-operator: #cbd5e1;
    --zkl-ts-fg: #cbd5e1;
    --zkl-ts-bg: #1e293b;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.38);
}
@media (prefers-color-scheme: dark) {
    body:not(.theme-light):not(.theme-sepia) .zoe-kernel-log {
        --zkl-bg: #0b1120;
        --zkl-header-bg: #111827;
        --zkl-border: #1e293b;
        --zkl-row-border: rgba(148, 163, 184, 0.14);
        --zkl-row-hover: rgba(56, 189, 248, 0.06);
        --zkl-text: #e2e8f0;
        --zkl-gutter: #94a3b8;
        --zkl-ns: #94a3b8;
        --zkl-stream: #38bdf8;
        --zkl-punct: #94a3b8;
        --zkl-severity-fg: #fecaca;
        --zkl-severity-bg: rgba(239, 68, 68, 0.20);
        --zkl-severity-border: rgba(248, 113, 113, 0.45);
        --zkl-exception: #f87171;
        --zkl-thread-fg: #93c5fd;
        --zkl-thread-bg: rgba(59, 130, 246, 0.18);
        --zkl-bracket: #4ade80;
        --zkl-string: #fdba74;
        --zkl-label: #c084fc;
        --zkl-diagnostic: #2dd4bf;
        --zkl-function: #e879f9;
        --zkl-identifier: #67e8f9;
        --zkl-constant: #fda4af;
        --zkl-number: #fde047;
        --zkl-operator: #cbd5e1;
        --zkl-ts-fg: #cbd5e1;
        --zkl-ts-bg: #1e293b;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.38);
    }
}
.zoe-kernel-log-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.55rem 1rem;
    background: var(--zkl-header-bg);
    border-bottom: 1px solid var(--zkl-border);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--zkl-ns);
}
.zoe-kernel-log-dots { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
.zkl-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.zkl-dot-red { background: #ef4444; }
.zkl-dot-amber { background: #f59e0b; }
.zkl-dot-green { background: #10b981; }
.zoe-kernel-log-os-badge { display: none; }
.zoe-kernel-log-win-controls { display: none; }
.zoe-kernel-log-title { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--zkl-text); }
.zkl-header-sep { color: var(--zkl-stream); margin: 0 0.25rem; }
.zoe-kernel-log-meta { font-size: 0.72rem; padding: 0.12rem 0.5rem; border-radius: 999px; background: var(--zkl-ts-bg); color: var(--zkl-ts-fg); flex-shrink: 0; }

/* ==========================================================================
 * OS-ADAPTIVE WINDOW CHROME FOR ZOË.SYSTEMS.LOGS (`html[data-os="..."]`)
 * ==========================================================================
 * Dynamically styles the `.zoe-kernel-log` window header and typography to
 * match the operating system the reader is viewing the book on:
 * - `macos` (Default): Left traffic-light dots (red, amber, green), 10px radius, SF Mono
 * - `windows`: Left `>_` terminal badge, right Windows 11 caption controls (`─`, `□`, `✕`), 8px radius, Cascadia Code / Consolas
 * - `linux`: Left `$_` prompt badge, right GNOME/Adwaita circular window buttons, Ubuntu Mono
 * - `ios`: Centered iOS sheet grabber handle + live emerald status indicator, 14px radius, SF Mono
 * - `android`: Left `▸_` Material terminal chip, 12px Material 3 radius, Roboto Mono
 * ========================================================================== */
html[data-os="windows"] .zoe-kernel-log {
    border-radius: 8px;
    font-family: "Cascadia Code", "Cascadia Mono", Consolas, "JetBrains Mono", ui-monospace, monospace;
}
html[data-os="windows"] .zoe-kernel-log-header {
    padding: 0 0 0 0.85rem;
    min-height: 34px;
}
html[data-os="windows"] .zoe-kernel-log-dots {
    display: none !important;
}
html[data-os="windows"] .zoe-kernel-log-os-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.1rem 0.38rem;
    border-radius: 4px;
    background: var(--zkl-ts-bg);
    color: var(--zkl-stream);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    flex-shrink: 0;
}
html[data-os="windows"] .zoe-kernel-log-os-badge::before {
    content: ">_";
}
html[data-os="windows"] .zoe-kernel-log-win-controls {
    display: inline-flex;
    align-items: stretch;
    align-self: stretch;
    margin-left: 0.25rem;
    flex-shrink: 0;
}
html[data-os="windows"] .zkl-win-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    min-height: 34px;
    font-family: "Segoe Fluent Icons", "Segoe MDL2 Assets", system-ui, sans-serif;
    font-size: 0.72rem;
    font-weight: 400;
    color: var(--zkl-ns);
    user-select: none;
    transition: background-color 0.12s ease, color 0.12s ease;
}
html[data-os="windows"] .zkl-win-btn:hover {
    background: rgba(148, 163, 184, 0.18);
    color: var(--zkl-text);
}
html[data-os="windows"] .zkl-win-close:hover {
    background: #e81123 !important;
    color: #ffffff !important;
}

html[data-os="linux"] .zoe-kernel-log {
    border-radius: 8px;
    font-family: "Ubuntu Mono", "JetBrains Mono", "Fira Code", "Liberation Mono", monospace;
}
html[data-os="linux"] .zoe-kernel-log-dots {
    display: none !important;
}
html[data-os="linux"] .zoe-kernel-log-os-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
    background: var(--zkl-ts-bg);
    color: var(--zkl-bracket);
    font-size: 0.72rem;
    font-weight: 800;
    flex-shrink: 0;
}
html[data-os="linux"] .zoe-kernel-log-os-badge::before {
    content: "$_";
}
html[data-os="linux"] .zoe-kernel-log-win-controls {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: 0.25rem;
    flex-shrink: 0;
}
html[data-os="linux"] .zkl-win-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--zkl-ts-bg);
    color: var(--zkl-ns);
    font-size: 0.65rem;
    line-height: 1;
    user-select: none;
}
html[data-os="linux"] .zkl-win-close {
    background: rgba(239, 68, 68, 0.18);
    color: var(--zkl-exception);
}

html[data-os="ios"] .zoe-kernel-log {
    border-radius: 14px;
    font-family: SFMono-Regular, "SF Mono", ui-monospace, Menlo, monospace;
}
html[data-os="ios"] .zoe-kernel-log-header {
    position: relative;
    padding-top: 0.8rem;
}
html[data-os="ios"] .zoe-kernel-log-header::before {
    content: "";
    position: absolute;
    top: 5px;
    left: 50%;
    transform: translateX(-50%);
    width: 34px;
    height: 4px;
    border-radius: 999px;
    background: var(--zkl-border);
    opacity: 0.85;
}
html[data-os="ios"] .zoe-kernel-log-dots,
html[data-os="ios"] .zoe-kernel-log-win-controls {
    display: none !important;
}
html[data-os="ios"] .zoe-kernel-log-os-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    flex-shrink: 0;
}

html[data-os="android"] .zoe-kernel-log {
    border-radius: 12px;
    font-family: "Roboto Mono", "JetBrains Mono", monospace;
}
html[data-os="android"] .zoe-kernel-log-dots,
html[data-os="android"] .zoe-kernel-log-win-controls {
    display: none !important;
}
html[data-os="android"] .zoe-kernel-log-os-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.08rem 0.38rem;
    border-radius: 6px;
    background: var(--zkl-ts-bg);
    color: var(--zkl-stream);
    font-size: 0.72rem;
    font-weight: 800;
    flex-shrink: 0;
}
html[data-os="android"] .zoe-kernel-log-os-badge::before {
    content: "▸_";
}
.zoe-kernel-log-body { display: flex; flex-direction: column; }
.zoe-log-entry {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    padding: 0.65rem 1rem;
    border-bottom: 1px solid var(--zkl-row-border);
    border-left: 4px solid #0284c7;
    transition: background-color 0.15s ease;
}
.zoe-log-entry:last-child { border-bottom: none; }
.zoe-log-entry:hover { background-color: var(--zkl-row-hover); }
.zoe-log-entry.zoe-log-level-info { border-left-color: #0284c7; }
.zoe-log-entry.zoe-log-level-warning { border-left-color: #d97706; }
.zoe-log-entry.zoe-log-level-critical { border-left-color: #dc2626; background-color: rgba(220, 38, 38, 0.04); }
.zoe-log-entry.zoe-log-level-emotional { border-left-color: #9333ea; }
.zoe-log-entry.zoe-log-level-action { border-left-color: #059669; }
.zoe-log-entry.zoe-log-level-scheduler { border-left-color: #4f46e5; }
.zoe-log-gutter { font-size: 0.75rem; color: var(--zkl-gutter); user-select: none; flex-shrink: 0; min-width: 1.6rem; text-align: right; font-variant-numeric: tabular-nums; }
.zoe-log-main { flex: 1; min-width: 0; overflow-wrap: break-word; word-break: normal; }
.zkl-ns { color: var(--zkl-ns); font-weight: 600; }
.zkl-stream { color: var(--zkl-stream); font-weight: 700; }
.zkl-punct { color: var(--zkl-punct); font-weight: 700; }
.zkl-severity { color: var(--zkl-severity-fg); background: var(--zkl-severity-bg); border: 1px solid var(--zkl-severity-border); padding: 0.05rem 0.38rem; border-radius: 4px; font-weight: 800; letter-spacing: 0.02em; }
.zkl-exception { color: var(--zkl-exception); font-weight: 700; text-decoration: underline; text-decoration-style: wavy; text-underline-offset: 3px; }
.zkl-thread { color: var(--zkl-thread-fg); background: var(--zkl-thread-bg); padding: 0.05rem 0.35rem; border-radius: 4px; font-weight: 700; }
.zkl-bracket { color: var(--zkl-bracket); font-weight: 600; }
.zkl-string { color: var(--zkl-string); }
.zkl-label { color: var(--zkl-label); font-weight: 700; }
.zkl-diagnostic { color: var(--zkl-diagnostic); font-style: italic; }
.zkl-function { color: var(--zkl-function); font-weight: 600; }
.zkl-identifier { color: var(--zkl-identifier); font-weight: 600; }
.zkl-constant { color: var(--zkl-constant); font-weight: 700; }
.zkl-number { color: var(--zkl-number); font-weight: 700; font-variant-numeric: tabular-nums; }
.zkl-operator { color: var(--zkl-operator); font-weight: 700; }
.zkl-timestamp { font-size: 0.76rem; color: var(--zkl-ts-fg); background: var(--zkl-ts-bg); padding: 0.12rem 0.48rem; border-radius: 4px; white-space: nowrap; flex-shrink: 0; font-variant-numeric: tabular-nums; margin-left: auto; }
@media (max-width: 768px) {
    .zoe-log-entry {
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem 0.6rem;
        padding: 0.65rem 0.85rem;
    }
    .zoe-log-gutter {
        order: 1;
        min-width: auto;
        text-align: left;
    }
    .zkl-timestamp {
        order: 2;
        margin-left: auto;
    }
    .zoe-log-main {
        order: 3;
        flex: 0 0 100%;
        width: 100%;
        min-width: 100%;
    }
}

/* ==========================================================================
 * OLIVER READER: INLINE HERO IMAGE CAROUSEL FALLBACK STYLES
 * ==========================================================================
 * Embedded directly in `oliver.php` alongside `reader.css` so installed PWA
 * instances (`Ocean View Archives`) render carousel transitions immediately
 * even before Service Worker cache rotation completes.
 * ========================================================================== */
.reader-hero-carousel { position: relative; user-select: none; outline: none; }
.reader-hero-carousel:focus-visible { outline: 3px solid var(--rs-primary, #3b82f6); outline-offset: 2px; }
.reader-hero-carousel-viewport { position: relative; width: 100%; aspect-ratio: 16 / 9; overflow: hidden; background: var(--rs-surface, #111827); }
.reader-hero-slide { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; pointer-events: none; transition: opacity 0.32s ease-in-out; }
.reader-hero-slide.is-active { position: relative; opacity: 1; pointer-events: auto; z-index: 2; }
.reader-hero-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 4; width: 42px; height: 42px; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, 0.35); background: rgba(15, 23, 42, 0.78); color: #ffffff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1.2rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); transition: background-color 0.18s ease, border-color 0.18s ease; }
.reader-hero-prev { left: 0.85rem; }
.reader-hero-next { right: 0.85rem; }
.reader-hero-nav:hover { background: rgba(15, 23, 42, 0.94); border-color: rgba(255, 255, 255, 0.75); }
.reader-hero-nav:focus-visible { outline: 3px solid #60a5fa; outline-offset: 2px; background: rgba(15, 23, 42, 0.96); }
.reader-hero-controls { position: absolute; left: 0.85rem; right: 0.85rem; bottom: 0.75rem; z-index: 4; display: flex; align-items: center; justify-content: space-between; pointer-events: none; }
.reader-hero-counter { pointer-events: auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.04em; padding: 0.25rem 0.65rem; border-radius: 9999px; background: rgba(15, 23, 42, 0.82); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.28); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
.reader-hero-dots { pointer-events: auto; display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.35rem 0.65rem; border-radius: 9999px; background: rgba(15, 23, 42, 0.78); border: 1px solid rgba(255, 255, 255, 0.28); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
.reader-hero-dot { width: 10px; height: 10px; padding: 0; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, 0.75); background: rgba(255, 255, 255, 0.35); cursor: pointer; transition: width 0.18s ease, background-color 0.18s ease; }
.reader-hero-dot.is-active { width: 24px; background: #ffffff; border-color: #ffffff; }
.reader-hero-dot:focus-visible { outline: 2px solid #60a5fa; outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) {
    .reader-hero-slide, .reader-hero-nav, .reader-hero-dot { transition: none !important; }
}

/* Inline PWA Immediate Styles for Transit Amber LED Matrix Signs (<led>) & <pa> */
.led-sign, em.led-sign { display: inline-flex; align-items: center; gap: 0.46em; vertical-align: middle; max-inline-size: 100%; padding: 0.2em 0.56em; margin: 0.08em 0.12em; border-radius: 5px; background: #0a0c10; color: #ffb300; border: 1px solid #d97706; box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.78), 0 1px 2px rgba(0, 0, 0, 0.18); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-style: normal; font-weight: 800; letter-spacing: 0.055em; line-height: 1.15; text-indent: 0 !important; text-shadow: 0 0 6px rgba(255, 179, 0, 0.26); box-sizing: border-box; }
.led-sign.led-sign--stacked, .led-sign.led-sign--split { padding: 0.24em 0.62em; }
.led-sign .led-route { font-size: 1.56em; line-height: 0.96; font-weight: 900; letter-spacing: 0.02em; font-variant-numeric: tabular-nums; padding-inline-end: 0.42em; border-inline-end: 1px solid rgba(255, 179, 0, 0.34); align-self: stretch; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.led-sign .led-lines { display: inline-flex; flex-direction: column; justify-content: center; align-items: flex-start; gap: 0.09em; min-inline-size: 0; }
.led-sign .led-line-single { font-size: 0.88em; line-height: 1.18; white-space: normal; overflow-wrap: anywhere; }
.led-sign .led-line-top { font-size: 0.74em; line-height: 1.12; font-weight: 800; letter-spacing: 0.06em; white-space: normal; overflow-wrap: anywhere; }
.led-sign .led-line-bottom { font-size: 0.7em; line-height: 1.12; font-weight: 700; letter-spacing: 0.05em; color: #ffc233; white-space: normal; overflow-wrap: anywhere; }

/* ==========================================================================
 * OLIVER READER: IN-SCENE MINI TABLE OF CONTENTS & `## Heading` SUB-HEADERS
 * ==========================================================================
 * Styles the `<nav class="reader-scene-mini-toc">` jump-navigation card and
 * `h2.reader-scene-subheading` section dividers across Light Mode, Sepia Mode,
 * Dark Mode, and custom narrative themes with strict WCAG 2.1 AA contrast.
 * ========================================================================== */
.story-content .reader-scene-mini-toc,
.story-content .reader-scene-mini-toc *,
.story-content h2.reader-scene-subheading {
    text-indent: 0 !important;
}
.story-content .reader-scene-mini-toc::first-letter,
.story-content .reader-scene-mini-toc *::first-letter,
.story-content h2.reader-scene-subheading::first-letter {
    float: none !important;
    font-size: inherit !important;
    line-height: inherit !important;
    margin: 0 !important;
    font-weight: inherit !important;
    color: inherit !important;
}
.reader-scene-mini-toc {
    margin: 2rem 0 2.5rem 0;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    border: 1px solid var(--rs-border);
    border-left: 4px solid var(--rs-primary);
    background: var(--rs-surface);
    color: var(--rs-text);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}
.reader-scene-mini-toc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding-bottom: 0.65rem;
    margin-bottom: 0.65rem;
    border-bottom: 1px dashed var(--rs-border);
}
.reader-scene-mini-toc-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rs-primary);
}
.reader-scene-mini-toc-badge {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    background: var(--rs-bg);
    border: 1px solid var(--rs-border);
    color: var(--rs-text);
    opacity: 0.85;
}
.reader-scene-mini-toc-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 0.45rem 0.85rem;
}
.reader-scene-mini-toc-item {
    margin: 0;
    padding: 0;
}
.reader-scene-mini-toc-link {
    display: flex;
    align-items: baseline;
    gap: 0.55rem;
    padding: 0.42rem 0.6rem;
    border-radius: 8px;
    text-decoration: none;
    color: var(--rs-text);
    font-size: 0.9rem;
    line-height: 1.35;
    border: 1px solid transparent;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.reader-scene-mini-toc-link:hover {
    background: var(--rs-bg);
    border-color: var(--rs-border);
    color: var(--rs-primary);
}
.reader-scene-mini-toc-link:focus-visible {
    outline: 2px solid var(--rs-primary);
    outline-offset: 2px;
    background: var(--rs-bg);
}
.reader-scene-mini-toc-num {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--rs-primary);
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
}
.reader-scene-mini-toc-label {
    font-weight: 600;
}
.story-content h2.reader-scene-subheading {
    scroll-margin-top: 2rem;
    margin: 2.25rem 0 1.25rem 0;
    padding: 0.45rem 0 0.55rem 0.85rem;
    border-left: 3px solid var(--rs-primary);
    font-size: 1.28rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--rs-text);
    outline: none;
}
.story-content hr + h2.reader-scene-subheading {
    margin-top: 1.25rem;
}

/* ==========================================================================
 * WIKIPEDIA-STYLE PAGE PREVIEW HOVERCARDS (Desktop Hover/Focus + Touch-First)
 * ==========================================================================
 * Implements Wikipedia-style inline link previews inside `.markdown-body`
 * following WCAG 1.4.13 (Dismissible, Hoverable, Persistent) and modern CSS
 * interest-delay tokens (`--interest-delay-start`, `--interest-delay-end`).
 * ========================================================================== */
.story-content a.rs-wiki-link {
    --interest-delay-start: 0.2s;
    --interest-delay-end: 0.18s;
    text-decoration-line: underline;
    text-decoration-style: dotted;
    text-decoration-thickness: 1.5px;
    text-underline-offset: 3px;
    transition: background-color 0.15s ease, color 0.15s ease;
    border-radius: 3px;
}

.story-content a.rs-wiki-link:hover,
.story-content a.rs-wiki-link:focus-visible,
.story-content a.rs-wiki-link.is-preview-active {
    background-color: rgba(227, 178, 124, 0.16);
    text-decoration-style: solid;
}

.rs-wiki-preview-card {
    position: fixed;
    z-index: 9999;
    width: min(360px, calc(100vw - 24px));
    background: var(--rs-surface, #131c27);
    color: var(--rs-text, #f1f5f9);
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.38));
    border-radius: 14px;
    box-shadow: 0 18px 42px rgba(0, 0, 0, 0.38), 0 4px 12px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    padding: 0;
    margin: 0;
    opacity: 0;
    transform: translateY(4px);
    pointer-events: none;
    transition: opacity 0.16s ease, transform 0.16s ease;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    text-indent: 0 !important;
}

.rs-wiki-preview-card.is-visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.rs-wiki-preview-media {
    width: 100%;
    aspect-ratio: 16 / 9;
    background: #0b131e;
    overflow: hidden;
    position: relative;
    border-bottom: 1px solid var(--rs-border, rgba(227, 178, 124, 0.25));
}

.rs-wiki-preview-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    margin: 0 !important;
    border-radius: 0 !important;
}

.rs-wiki-preview-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 30px;
    height: 30px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.35);
    background: rgba(9, 20, 30, 0.82);
    color: #ffffff;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.9rem;
    backdrop-filter: blur(6px);
}

@media (hover: none), (pointer: coarse) {
    .rs-wiki-preview-close {
        display: inline-flex;
    }
}

.rs-wiki-preview-body {
    padding: 0.9rem 1rem 0.95rem;
}

.rs-wiki-preview-kicker {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rs-primary, #E3B27C);
    margin: 0 0 0.2rem 0;
    line-height: 1.3;
}

.rs-wiki-preview-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--rs-heading, var(--rs-text, #ffffff));
    margin: 0 0 0.35rem 0;
    line-height: 1.28;
}

.rs-wiki-preview-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.7rem;
    font-size: 0.74rem;
    font-weight: 600;
    color: var(--rs-text-muted);
    margin-bottom: 0.45rem;
}

.rs-wiki-preview-excerpt {
    font-size: 0.83rem;
    line-height: 1.48;
    color: var(--rs-text);
    margin: 0 0 0.75rem 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.rs-wiki-preview-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding-top: 0.55rem;
    border-top: 1px solid var(--rs-border, rgba(227, 178, 124, 0.22));
}

.rs-wiki-preview-cta {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.42rem 0.85rem;
    border-radius: 8px;
    background: var(--rs-primary, #0F4C5C);
    color: var(--rs-on-primary, #ffffff) !important;
    font-size: 0.8rem;
    font-weight: 800;
    text-decoration: none !important;
}

.rs-wiki-preview-hint {
    font-size: 0.7rem;
    color: var(--rs-text-muted);
}
</style>

<!-- Wikipedia-Style Page Preview Hovercard (Shared Singleton for Desktop Hover/Focus & Touch-First Tap) -->
<div id="rs-wiki-preview-card" class="rs-wiki-preview-card" role="dialog" aria-modal="false" aria-label="Link page preview" aria-hidden="true">
    <div class="rs-wiki-preview-media" id="rs-wiki-preview-media">
        <img id="rs-wiki-preview-img" src="" alt="" loading="lazy" decoding="async">
        <button type="button" class="rs-wiki-preview-close" id="rs-wiki-preview-close" aria-label="Close page preview">
            <i class="ph-bold ph-x" aria-hidden="true"></i>
        </button>
    </div>
    <div class="rs-wiki-preview-body">
        <div class="rs-wiki-preview-kicker" id="rs-wiki-preview-kicker"></div>
        <div class="rs-wiki-preview-title" id="rs-wiki-preview-title"></div>
        <div class="rs-wiki-preview-meta" id="rs-wiki-preview-meta"></div>
        <p class="rs-wiki-preview-excerpt" id="rs-wiki-preview-excerpt"></p>
        <div class="rs-wiki-preview-footer">
            <a id="rs-wiki-preview-cta" href="#" class="rs-wiki-preview-cta">Read Scene &rarr;</a>
            <span class="rs-wiki-preview-hint" id="rs-wiki-preview-hint">Press Esc to close</span>
        </div>
    </div>
</div>
<script>
/*
 * ============================================================================
 * SUBSYSTEM 12B: CLIENT OS DETECTOR & WIKIPEDIA-STYLE HOVERCARD CONTROLLER
 * ============================================================================
 * 100-Level JavaScript Walkthrough:
 * 1. OS Window-Chrome Sync (`data-os` on `<html>`):
 *    Refines the server's `User-Agent` guess on the client—crucially detecting
 *    iPadOS (`navigator.maxTouchPoints > 1` on a `'Macintosh'` user agent!) so
 *    Zoë Patel's `.zoe-kernel-log` diagnostic blocks display authentic macOS,
 *    Windows 11, Linux, iOS/iPadOS, or Android window decorations!
 * 2. WCAG 1.4.13 Hovercard Singleton (`#rs-wiki-preview-card`):
 *    - Instead of creating hundreds of hidden tooltip `<div>`s, ALL `.rs-wiki-link`
 *      links share ONE floating `#rs-wiki-preview-card` singleton populated from
 *      each `<a>` tag's `data-rs-preview` JSON attribute (`JSON.parse(raw)`).
 *    - Desktop Pointer/Keyboard: Opens after a `190ms` hover intent delay (or on
 *      Tab `focus`), stays open while the mouse moves onto the card itself
 *      (WCAG 1.4.13 "Hoverable"), and closes on `Escape` or `180ms` after leaving.
 *    - Touch-First Two-Tap (`isTouchFirstEnvironment()`): On phones/tablets, the
 *      FIRST tap intercepts navigation in the capture phase (`useCapture = true`)
 *      to open the preview card ("Tap link again to open"), and the SECOND tap
 *      navigates to the linked scene or companion album!
 */
(function () {
    // Part 1: Detect viewer operating system (or honor `?preview_os=windows|linux|ios|android|macos`)
    try {
        var params = new URLSearchParams(window.location.search);
        var previewOs = (params.get('preview_os') || '').toLowerCase();
        var validOs = ['macos', 'windows', 'linux', 'ios', 'android'];
        var resolvedOs = '';
        if (validOs.indexOf(previewOs) !== -1) {
            resolvedOs = previewOs;
        } else {
            var ua = (navigator.userAgent || '').toLowerCase();
            var plat = ((navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || '').toLowerCase();
            // iPadOS Safari reports `'MacIntel'` / `'Macintosh'` by default; `maxTouchPoints > 1` reveals iPad hardware!
            var isIpadOs = (plat.indexOf('mac') !== -1 || ua.indexOf('macintosh') !== -1) && navigator.maxTouchPoints && navigator.maxTouchPoints > 1;
            if (isIpadOs || /iphone|ipad|ipod/.test(ua) || plat.indexOf('ios') !== -1) {
                resolvedOs = 'ios';
            } else if (ua.indexOf('android') !== -1 || plat.indexOf('android') !== -1) {
                resolvedOs = 'android';
            } else if (plat.indexOf('win') !== -1 || ua.indexOf('windows') !== -1) {
                resolvedOs = 'windows';
            } else if (plat.indexOf('linux') !== -1 || ua.indexOf('linux') !== -1 || ua.indexOf('cros') !== -1) {
                resolvedOs = 'linux';
            } else {
                resolvedOs = 'macos';
            }
        }
        document.documentElement.setAttribute('data-os', resolvedOs);
    } catch (e) {}

    // Part 2: Wikipedia-Style Inline Link Preview Hovercard Controller
    const card = document.getElementById('rs-wiki-preview-card');
    // Guard with `dataset.bound` so SPA soft-navigations never bind duplicate listeners to the singleton card
    if (!card || card.dataset.bound === 'true') return;
    card.dataset.bound = 'true';

    const mediaWrap = document.getElementById('rs-wiki-preview-media');
    const imgEl = document.getElementById('rs-wiki-preview-img');
    const kickerEl = document.getElementById('rs-wiki-preview-kicker');
    const titleEl = document.getElementById('rs-wiki-preview-title');
    const metaEl = document.getElementById('rs-wiki-preview-meta');
    const excerptEl = document.getElementById('rs-wiki-preview-excerpt');
    const ctaEl = document.getElementById('rs-wiki-preview-cta');
    const hintEl = document.getElementById('rs-wiki-preview-hint');
    const closeBtn = document.getElementById('rs-wiki-preview-close');

    let activeAnchor = null;
    let showTimer = null;
    let hideTimer = null;
    let lastTouchTime = 0;

    // Returns `true` if the user recently touched the screen OR the device has a coarse/non-hover primary pointer
    function isTouchFirstEnvironment() {
        if (Date.now() - lastTouchTime < 1200) return true;
        return window.matchMedia && window.matchMedia('(hover: none) and (pointer: coarse)').matches;
    }

    // Positions the fixed hovercard centered below `anchor` (or flips it ABOVE `anchor` if near the bottom of the screen!)
    function positionCard(anchor) {
        if (!anchor || !card) return;
        const rect = anchor.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const cardWidth = Math.min(360, vw - 24);
        const cardHeight = card.offsetHeight || 290;

        // Clamp horizontal `left` between `12px` and `vw - cardWidth - 12` so it never bleeds off-screen
        let left = rect.left + (rect.width / 2) - (cardWidth / 2);
        left = Math.max(12, Math.min(left, vw - cardWidth - 12));

        // Prefer `10px` below the link; flip `10px` above the link if bottom space is too tight
        let top = rect.bottom + 10;
        if (top + cardHeight > vh - 16 && rect.top - cardHeight - 10 > 12) {
            top = rect.top - cardHeight - 10;
        } else if (top + cardHeight > vh - 16) {
            top = Math.max(12, vh - cardHeight - 16);
        }

        card.style.left = Math.round(left) + 'px';
        card.style.top = Math.round(top) + 'px';
    }

    // Parses `data-rs-preview` JSON on `anchor`, populates the singleton card DOM, and reveals it
    function openPreviewForLink(anchor, viaTouch) {
        if (!anchor) return;
        const raw = anchor.getAttribute('data-rs-preview');
        if (!raw) return;
        let data = null;
        try {
            data = JSON.parse(raw);
        } catch (err) {
            return;
        }
        if (!data) return;

        clearTimeout(showTimer);
        clearTimeout(hideTimer);

        if (activeAnchor && activeAnchor !== anchor) {
            activeAnchor.classList.remove('is-preview-active');
        }
        activeAnchor = anchor;
        activeAnchor.classList.add('is-preview-active');

        if (data.image) {
            mediaWrap.style.display = '';
            imgEl.src = data.image;
            imgEl.alt = data.title || '';
        } else {
            mediaWrap.style.display = 'none';
            imgEl.src = '';
        }

        kickerEl.textContent = data.kicker || 'Ocean View Archives';
        titleEl.textContent = data.title || anchor.textContent || '';

        metaEl.innerHTML = '';
        if (data.date) {
            const dSpan = document.createElement('span');
            dSpan.textContent = '📅 ' + data.date;
            metaEl.appendChild(dSpan);
        }
        if (data.location) {
            const lSpan = document.createElement('span');
            lSpan.textContent = '📍 ' + data.location;
            metaEl.appendChild(lSpan);
        }
        metaEl.style.display = (data.date || data.location) ? 'flex' : 'none';

        excerptEl.textContent = data.excerpt || '';
        excerptEl.style.display = data.excerpt ? '' : 'none';

        ctaEl.textContent = data.cta || 'Read Scene →';
        ctaEl.href = data.url || anchor.getAttribute('href') || '#';
        if (data.external) {
            ctaEl.setAttribute('data-no-spa', 'true');
            ctaEl.setAttribute('target', '_blank');
            ctaEl.setAttribute('rel', 'noopener');
        } else {
            ctaEl.removeAttribute('data-no-spa');
            ctaEl.removeAttribute('target');
            ctaEl.removeAttribute('rel');
        }

        hintEl.textContent = viaTouch ? 'Tap link again to open' : 'Press Esc to close';

        card.classList.add('is-visible');
        card.setAttribute('aria-hidden', 'false');
        positionCard(anchor);
    }

    function closePreview() {
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        if (activeAnchor) {
            activeAnchor.classList.remove('is-preview-active');
            activeAnchor = null;
        }
        card.classList.remove('is-visible');
        card.setAttribute('aria-hidden', 'true');
    }

    function scheduleHide() {
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        hideTimer = setTimeout(closePreview, 180);
    }

    // Track touch interactions so hybrid or touch-first devices use two-tap preview
    document.addEventListener('touchstart', function () {
        lastTouchTime = Date.now();
    }, { passive: true, capture: true });

    // Desktop Hover & Keyboard Focus
    document.querySelectorAll('.story-content a.rs-wiki-link').forEach(function (link) {
        link.addEventListener('mouseenter', function () {
            if (isTouchFirstEnvironment()) return;
            clearTimeout(hideTimer);
            showTimer = setTimeout(function () {
                openPreviewForLink(link, false);
            }, 190);
        });

        link.addEventListener('mouseleave', function () {
            if (isTouchFirstEnvironment()) return;
            scheduleHide();
        });

        link.addEventListener('focus', function () {
            if (isTouchFirstEnvironment()) return;
            clearTimeout(hideTimer);
            openPreviewForLink(link, false);
        });

        link.addEventListener('blur', function () {
            if (isTouchFirstEnvironment()) return;
            scheduleHide();
        });

        // Touch-first two-step tap handler (runs in capture phase before stardust-spa.min.js)
        link.addEventListener('click', function (e) {
            if (!isTouchFirstEnvironment()) {
                closePreview();
                return;
            }
            if (activeAnchor !== link || !card.classList.contains('is-visible')) {
                e.preventDefault();
                e.stopPropagation();
                openPreviewForLink(link, true);
            } else {
                closePreview();
            }
        }, true);
    });

    // Keep card open while pointer hovers inside card (WCAG 1.4.13 Hoverable)
    card.addEventListener('mouseenter', function () {
        clearTimeout(hideTimer);
    });
    card.addEventListener('mouseleave', function () {
        if (!isTouchFirstEnvironment()) {
            scheduleHide();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closePreview();
        });
    }

    ctaEl.addEventListener('click', function () {
        closePreview();
    });

    // Light-dismiss when tapping outside the card or pressing Escape
    document.addEventListener('click', function (e) {
        if (!card.classList.contains('is-visible')) return;
        if (card.contains(e.target)) return;
        if (activeAnchor && activeAnchor.contains(e.target)) return;
        closePreview();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && card.classList.contains('is-visible')) {
            closePreview();
        }
    });

    const readingPane = document.getElementById('stardust-reading-pane');
    if (readingPane) {
        readingPane.addEventListener('scroll', function () {
            if (card.classList.contains('is-visible') && activeAnchor) {
                positionCard(activeAnchor);
            }
        }, { passive: true });
    }
})();
</script>




