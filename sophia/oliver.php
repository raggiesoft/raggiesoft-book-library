<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: oliver.php
 * ============================================================================
 * Purpose:
 * This file serves as the core reading interface ("Oliver"). It dynamically 
 * routes requests, parses Markdown files from the CDN, extracts YAML frontmatter, 
 * and renders the immersive narrative reading experience, including multimedia 
 * integration (audio).
 * 
 * Architectural Role:
 * The primary view engine for narrative content. It intercepts routing logic 
 * using JSON maps, separating the UI from the raw content stored externally. 
 * It converts standard Markdown to HTML via Parsedown while injecting custom 
 * UI elements like ambient audio players, breadcrumbs, and social sharing links.
 * 
 * Key Components:
 * 1. Routing Engine: Maps `$requestUri` to a physical `.md` path using a 
 *    series-specific JSON route file (`data/routes/*.json`).
 * 2. Markdown Parsing: Fetches content from the CDN, strips YAML frontmatter 
 *    into an array (`$frontmatter`), and uses `StardustParsedown` to render.
 * 3. Temporal Data Handling: Parses custom frontmatter keys (`stardate`, `date`, 
 *    `timezone`) to render in-universe chronologies.
 * 4. Audio Integration: Dynamically generates an HTML5 audio player if `audio` 
 *    frontmatter exists, resolving tracks against `engine-room-records` metadata.
 * 5. Special Pages: Handles TOCs and Landing pages by intercepting specific 
 *    internal path keywords (`__TOC__`, `__LANDING__`) and delegating to sub-views.
 * 
 * Maintenance Notes:
 * - Routing depends entirely on the JSON map. If a URL isn't in the JSON, it 404s.
 * - Timezone conversions (`America/New_York`) use hardcoded maps for standard 
 *   abbreviations (`EST/EDT`). Updates to PHP's timezone db or new abbreviations 
 *   require updates to `$tzMap`.
 * - Audio fetching logic relies on an assumed directory structure 
 *   (`engine-room-records/artists/{artist}/{album}`).
 * ============================================================================
 */

// RaggieSoft Books - Markdown Viewer (Route JSON Driven)

$requestUri = rtrim($requestUri, '/');
$parts = explode('/', ltrim($requestUri, '/'));
$seriesSlug = $parts[0] ?? '';

// Load the routing manifest for the specific series
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
    }
}

// Exit early with an error state if the route does not exist
if (empty($actualFilePath)) {
    echo '<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; height: 100dvh; overflow-y: auto;"><div class="book-page"><h1>Chapter Not Found</h1><p>The requested route could not be found in the route map.</p></div></main>

<style>
#stardust-reading-pane { padding-bottom: 6rem !important; }
</style>';
    return;
}

// 1. Fetch Markdown Content from CDN (or intercept internal paths)
// $cdnBaseUrl is injected by isabel.php
$specialPageType = null;
$specialPageIndex1 = 0;
$specialPageIndex2 = 0;
$mdContent = '';

// Handle programmatic view routing for tables of contents and landing pages
if ($actualFilePath === '__SERIES_LANDING__') {
    $specialPageType = 'landing';
} elseif ($actualFilePath === '__TOC__') {
    $specialPageType = 'toc';
} elseif (strpos($actualFilePath, '__ERA_TOC__|') === 0) {
    $p = explode('|', $actualFilePath);
    $specialPageType = 'era_toc';
    $specialPageIndex1 = (int)($p[1] ?? 0);
} elseif (strpos($actualFilePath, '__CHAP_TOC__|') === 0) {
    $p = explode('|', $actualFilePath);
    $specialPageType = 'chap_toc';
    $specialPageIndex1 = (int)($p[1] ?? 0);
    $specialPageIndex2 = (int)($p[2] ?? 0);
} else {
    // Standard content fetching block
    if (isset($prefetchedMdContent) && $prefetchedMdContent !== null) {
        $mdContent = $prefetchedMdContent;
    } else {
        // Suppress errors and handle 404s gracefully via the check below
        $mdUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/' . $actualFilePath;
        $mdContent = @file_get_contents($mdUrl);
        if ($mdContent !== false) {
            // Replace internal CDN macros
            $mdContent = str_replace('{{CDN}}', $cdnBaseUrl, $mdContent);
        }
    }
}

if ($mdContent === false && !$specialPageType) {
    echo '<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; height: 100dvh; overflow-y: auto;"><div class="book-page"><h1>File Not Found</h1><p>The narrative file could not be loaded from the Vault.</p></div></main>

<style>
#stardust-reading-pane { padding-bottom: 6rem !important; }
</style>';
    return;
}

// 2. Parse YAML Frontmatter
$frontmatter = [];
// Matches a block surrounded by '---' at the very start of the string
if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $mdContent, $matches)) {
    $rawFrontmatter = $matches[1];
    $mdContent = substr($mdContent, strlen($matches[0])); // Strip it from the content
    
    $lines = explode("\n", $rawFrontmatter);
    $currentArrayKey = null;
    
    // Custom YAML parser optimized for simple key-value and list structures
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;
        
        // Handle array items (lines starting with '-')
        if (strpos($trimmed, '-') === 0 && $currentArrayKey) {
            $val = trim(substr($trimmed, 1));
            $val = trim($val, '"\'');
            $frontmatter[$currentArrayKey][] = $val;
        } 
        // Handle key: value pairs
        elseif (strpos($trimmed, ':') !== false) {
            list($key, $val) = explode(':', $trimmed, 2);
            $key = trim($key);
            $val = trim($val);
            if ($val === '') {
                // Prepare for an array block
                $currentArrayKey = $key;
                $frontmatter[$currentArrayKey] = [];
            } else {
                // Scalar value
                $val = trim($val, '"\'');
                $frontmatter[$key] = $val;
                $currentArrayKey = null;
            }
        } else {
            $currentArrayKey = null;
        }
    }
}

// 3. Render HTML & Extract In-Scene `## Heading` (`<h2>`) Sub-Sections for Mini-TOC
require_once $basePath . '/includes/classes/stardust-parsedown.php';
$Parsedown = new StardustParsedown('vanilla');
$sceneSubsections = [];
if ($specialPageType) {
    ob_start();
    // Dynamically require the correct PHP sub-view
    if ($specialPageType === 'landing') {
        require_once __DIR__ . '/landing.php';
    } elseif ($specialPageType === 'toc') {
        require_once __DIR__ . '/toc.php';
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
}

// 4. Sequence Navigation (Provided by Route JSON)
$prevUrl = $config['prevUrl'] ?? null;
$nextUrl = $config['nextUrl'] ?? null;
$sequenceName = $routeData['common']['siteName'] ?? 'Ocean View Archives';

// Fallback logic to generate previous/next if they aren't explicitly in the JSON.
// When the reader is viewing an actual narrative scene (`!$specialPageType`), filter out
// intermediate structural TOC routes (`__TOC__`, `__ERA_TOC__`, `__CHAP_TOC__`) so
// "Previous" and "Next" flow seamlessly from scene to scene across chapter boundaries.
$routeKeys = array_keys($routeData);
$seriesSlugForFilter = '/' . ($seriesSlug ?? '');
$filteredKeys = array_filter($routeKeys, function($k) use ($seriesSlugForFilter, $routeData, $specialPageType) { 
    if ($k === 'common' || $k === $seriesSlugForFilter) {
        return false;
    }
    if (!$specialPageType) {
        $fp = (string)($routeData[$k]['filePath'] ?? '');
        if (
            $fp === '__SERIES_LANDING__' ||
            $fp === '__TOC__' ||
            strpos($fp, '__ERA_TOC__|') === 0 ||
            strpos($fp, '__CHAP_TOC__|') === 0
        ) {
            return false;
        }
    }
    return true;
});
$filteredKeys = array_values($filteredKeys); // reindex
$currentIndex = array_search($requestUri, $filteredKeys);
if ($currentIndex !== false) {
    if ($currentIndex > 0 && empty($prevUrl)) $prevUrl = $filteredKeys[$currentIndex - 1];
    if ($currentIndex < count($filteredKeys) - 1 && empty($nextUrl)) $nextUrl = $filteredKeys[$currentIndex + 1];
}

$overviewUrl = '/' . $seriesSlug;
$title = $config['title'] ?? ($frontmatter['title'] ?? 'Untitled Chapter');
$narrativeTheme = $frontmatter['theme'] ?? null;

// Fallback to series-level theme defined in landing.md frontmatter for landing/TOC views
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
?>
<?php if ($narrativeTheme): ?>
    <!-- Ambient CSS Themes and Animations -->
    <meta name="stardust-narrative-theme" content="<?php echo htmlspecialchars($narrativeTheme); ?>">
    <link id="narrative-theme-css" rel="stylesheet" href="<?php echo htmlspecialchars($cdnBaseUrl . '/raggiesoft-books/css/themes/' . $narrativeTheme . '.css'); ?>" disabled>
    
    <?php if ($narrativeTheme === 'dark-rain'): ?>
    <!-- Procedural Rain Generation for 'dark-rain' theme -->
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

<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; height: 100dvh; overflow-y: auto;">
    <div class="book-page reader-page">
        <!-- Breadcrumbs & Nav -->
        <div class="reader-nav" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="opacity: 0.7; font-size: 0.9rem;">
            <?php 
            global $toc_manifest, $seriesSlug, $pageConfig;
            $actualFilePath = $pageConfig["filePath"] ?? "";
            
            $seriesTitle = !empty($toc_manifest['series_title']) ? $toc_manifest['series_title'] : (ucwords(str_replace('-', ' ', $seriesSlug)));
            $eraTitle = '';
            $chapTitle = '';
            
            // Search the manifest for hierarchy titles to construct breadcrumbs
            if ($toc_manifest && isset($toc_manifest['eras'])) {
                foreach ($toc_manifest['eras'] as $e) {
                    if (isset($e['chapters'])) {
                        foreach ($e['chapters'] as $c) {
                            if (isset($c['scenes'])) {
                                foreach ($c['scenes'] as $s) {
                                    if ($s['file_path'] === $actualFilePath) {
                                        $eraTitle = $e['era_title'];
                                        $chapTitle = $c['chap_title'];
                                        break 3;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            ?>
            <a href="/library" style="text-decoration: none;">Library</a>
            &raquo; <strong style="color: var(--rs-text); font-weight: 600;"><?php echo htmlspecialchars($seriesTitle); ?></strong>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button id="stardust-sidebar-toggle" class="rs-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="ph ph-list"></i> Menu</button>
            </div>
        </div>

        <article class="story-content">
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
                                 <?php if ($sIdx === 0): ?>fetchpriority="high"<?php else: ?>loading="lazy"<?php endif; ?>
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
                 * Self-contained inline Hero Image Carousel controller for `oliver.php`.
                 * Executed automatically on both full page loads and `stardust-spa.min.js`
                 * soft-navigations (even when external CDN scripts are cached by the PWA Service Worker).
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
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; opacity: 0.7; font-size: 0.85rem; font-weight: 600;">
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

            <!-- Audio Player Integration (Supports Single Track, Full Album, Named Playlist, or Inline YAML Queue) -->
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

                        $fetchAlbumMeta = function ($artistSlug, $albumSlug) use ($cdnBaseUrl, &$albumMetaCache, &$tracksMetaCache, &$artistAlbumsMetaCache) {
                            $key = $artistSlug . '/' . $albumSlug;
                            if (!isset($albumMetaCache[$key])) {
                                $raw = @file_get_contents($cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/album.json');
                                $albumMetaCache[$key] = $raw ? (json_decode($raw, true) ?: []) : [];
                            }
                            if (!isset($tracksMetaCache[$key])) {
                                $rawT = @file_get_contents($cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/tracks.json');
                                $tracksMetaCache[$key] = $rawT ? (json_decode($rawT, true) ?: []) : [];
                            }
                            if (!isset($artistAlbumsMetaCache[$artistSlug])) {
                                $rawA = @file_get_contents($cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/albums.json');
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

                            foreach ($tracksList as $tIdx => $track) {
                                if (($track['fileName'] ?? '') === $filenameNoExt) {
                                    if (!empty($track['title'])) $audioTitleDisplay = $track['title'];
                                    if (!empty($track['duration'])) $durationDisplay = $track['duration'];
                                    $trackNumber = $track['track'] ?? ($tIdx + 1);
                                    break;
                                }
                            }

                            $audioUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/web-mp3/' . $filenameNoExt . '.mp3';
                            $albumArtUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/album-art.jpg';
                            $lyricsUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/lyrics/' . $filenameNoExt . '.md';
                            $listeningRoomTrackUrl = 'https://music.raggiesoft.com/artists/' . rawurlencode($artistSlug) . '/' . rawurlencode($albumSlug) . '?track=' . rawurlencode($filenameNoExt);
                            $listeningRoomAlbumUrl = 'https://music.raggiesoft.com/artists/' . rawurlencode($artistSlug) . '/' . rawurlencode($albumSlug);
                            $listeningRoomArtistUrl = 'https://music.raggiesoft.com/artists/' . rawurlencode($artistSlug);

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
                            &$expandAudioSpec
                        ) {
                            $clean = trim((string)$spec, " \t\n\r\0\x0B/");
                            if ($clean === '') return;

                            // Case 1: Named Playlist ("playlists/{slug}" or "engine-room-records/playlists/{slug}")
                            if (strpos($clean, 'playlists/') === 0 || strpos($clean, 'engine-room-records/playlists/') === 0) {
                                $plRelative = preg_replace('#^(engine-room-records/)?playlists/#', '', $clean);
                                $plRelative = preg_replace('#/playlist\.json$#', '', $plRelative);
                                $plSlug = trim($plRelative, '/');
                                $plJsonUrl = $cdnBaseUrl . '/engine-room-records/playlists/' . $plSlug . '/playlist.json';
                                $plRaw = @file_get_contents($plJsonUrl);
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
                                <div id="narrative-audio-eyebrow" style="font-size: 0.75rem; opacity: 0.78; text-transform: uppercase; letter-spacing: 1px; line-height: 1.2; margin-bottom: 0.35rem; font-weight: 600;"><?php echo htmlspecialchars($eyebrowLabel); ?></div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-weight: 700; font-size: 1.1rem; line-height: 1.2; margin-bottom: 0.25rem;">
                                    <span class="reader-audio-scroll-text">
                                        <a id="narrative-audio-title-link" href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Listen to &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room" style="color: inherit; text-decoration: underline; text-decoration-color: var(--rs-primary); text-underline-offset: 3px;">
                                            <span id="narrative-audio-title-text"><?php echo htmlspecialchars($audioTitleDisplay); ?></span> <i class="ph ph-arrow-square-out" aria-hidden="true" style="font-size: 0.85em; opacity: 0.85;"></i>
                                        </a>
                                    </span>
                                </div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-size: 0.85rem; opacity: 0.85; margin-bottom: 0.75rem;">
                                    <span class="reader-audio-scroll-text">
                                        <a id="narrative-audio-artist-link" href="<?php echo htmlspecialchars($listeningRoomArtistUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="View <?php echo htmlspecialchars($artistDisplay); ?> in The Listening Room" style="color: inherit; text-decoration: none; border-bottom: 1px dotted currentColor;"><?php echo htmlspecialchars($artistDisplay); ?></a>
                                        &bull;
                                        <a id="narrative-audio-album-link" href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Open album <?php echo htmlspecialchars($albumDisplay); ?> in The Listening Room" style="color: inherit; text-decoration: underline; text-underline-offset: 2px;"><i id="narrative-audio-album-text"><?php echo htmlspecialchars($albumDisplay); ?></i></a>
                                    </span>
                                </div>
                                
                                <!-- Custom HTML5 Audio Player UI -->
                                <div class="reader-audio-controls-row" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <audio id="narrative-audio-element"
                                           data-title="<?php echo htmlspecialchars($audioTitleDisplay); ?>"
                                           data-artist="<?php echo htmlspecialchars($artistDisplay); ?>"
                                           data-album="<?php echo htmlspecialchars($albumDisplay); ?>"
                                           data-artwork="<?php echo htmlspecialchars($albumArtUrl); ?>"
                                           data-listening-room-track-url="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>"
                                           data-listening-room-album-url="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>"
                                           data-queue-mode="<?php echo htmlspecialchars($queueMode); ?>"
                                           data-playlist-title="<?php echo htmlspecialchars($playlistTitle); ?>"
                                           data-playlist-url="<?php echo htmlspecialchars($playlistListeningRoomUrl); ?>"
                                           data-repeat-mode="<?php echo $queueCount > 1 ? 'all' : 'one'; ?>"
                                           data-active-index="0"
                                           data-queue="<?php echo htmlspecialchars(json_encode($audioQueue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>"
                                           src="<?php echo htmlspecialchars($audioUrl); ?>"
                                           data-start-time="<?php echo $audioStart; ?>"
                                           <?php if ($queueCount === 1): ?>loop<?php endif; ?>
                                           preload="metadata"
                                           style="display: none;"></audio>
                                    
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
                                        <span id="narrative-audio-duration">0:00</span>
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
                                    <span class="reader-audio-queue-num" style="font-variant-numeric: tabular-nums; font-weight: 700; opacity: 0.7; min-width: 1.5rem; text-align: right;"><?php echo ($qIdx + 1); ?>.</span>
                                    <span style="min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <strong style="font-weight: 600;"><?php echo htmlspecialchars($qTrack['title']); ?></strong>
                                        <?php if (!empty($qTrack['actLabel'])): ?>
                                            <span style="opacity: 0.72; font-size: 0.78rem;"> &mdash; <?php echo htmlspecialchars($qTrack['actLabel']); ?></span>
                                        <?php elseif ($queueMode === 'playlist'): ?>
                                            <span style="opacity: 0.72; font-size: 0.78rem;"> &bull; <?php echo htmlspecialchars($qTrack['artist'] . ' (' . $qTrack['album'] . ')'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <?php if (!empty($qTrack['duration'])): ?>
                                <span style="font-variant-numeric: tabular-nums; font-size: 0.78rem; opacity: 0.75; flex-shrink: 0;"><?php echo htmlspecialchars($qTrack['duration']); ?></span>
                                <?php endif; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- The Listening Room & External Streaming Links -->
                        <div style="background: var(--rs-bg); border-top: 1px solid var(--rs-border); padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; align-items: center; text-align: center;">
                            <div style="font-size: 0.8rem; opacity: 0.8;">Open this song or full album directly in <strong>The Listening Room</strong>, or stream the high-fidelity mix on your favorite service:</div>
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
                            <span id="narrative-lyrics-footer-meta" style="opacity: 0.8;"><?php echo htmlspecialchars($artistDisplay); ?> &bull; <i><?php echo htmlspecialchars($albumDisplay); ?></i></span>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a id="narrative-lyrics-open-song" href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn rs-btn-primary reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-headphones" aria-hidden="true" style="margin-right: 4px;"></i> Open Song <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px;"></i></a>
                                <a id="narrative-lyrics-open-album" href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-vinyl-record" aria-hidden="true" style="margin-right: 4px;"></i> Open Album <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px;"></i></a>
                            </div>
                        </div>
                    </dialog>
                    <?php endif; // End if ($firstTrack) ?>
                <?php endif; ?>

            <?php if (!$specialPageType && !empty($sceneSubsections)): ?>
            <!-- In-Scene Mini Table of Contents (Constructed from `## Heading` sub-headers at `***` breaks) -->
            <nav class="reader-scene-mini-toc" aria-label="In-scene sections">
                <div class="reader-scene-mini-toc-header">
                    <span class="reader-scene-mini-toc-kicker">
                        <i class="ph ph-list-bullets" aria-hidden="true"></i>
                        <span>In This Scene</span>
                    </span>
                    <span class="reader-scene-mini-toc-badge">
                        <?php echo count($sceneSubsections) === 1 ? '1 Section' : (count($sceneSubsections) . ' Sections'); ?>
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
        // RAGGIESOFT BACKSTAGE (<dialog> API & Correspondence Callout Integration)
        // =====================================================================
        // Renders the Backstage Bonus Features / Extracted Correspondence callout
        // when `backstage:` is present in the scene's YAML frontmatter, and mounts
        // `<dialog id="rs-backstage-dialog">` so both frontmatter buttons and
        // inline `https://backstage.raggiesoft.com/...` links open an in-reader
        // modal with a one-click bounce button to `https://backstage.raggiesoft.com/`.
        if (!$specialPageType):
            $rawBackstage = $frontmatter['backstage'] ?? [];
            $backstageSlugs = is_array($rawBackstage)
                ? array_values(array_filter(array_map('trim', $rawBackstage)))
                : (trim((string)$rawBackstage) !== '' ? [trim((string)$rawBackstage)] : []);

            $backstageIndexMap = [];
            if (!empty($backstageSlugs)) {
                $localBsIndexCandidates = [
                    dirname($basePath, 3) . '/raggiesoft-assets/raggiesoft-backstage/api/v1/index.json',
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
                        <div style="font-size: 0.78rem; font-weight: 700; opacity: 0.8; margin-bottom: 0.2rem;">
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
                <span style="opacity: 0.82;">100% In-Universe Bonus Feature &bull; <code>backstage.raggiesoft.com</code></span>
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
                    <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="rs-btn" id="reader-btn-prev">&larr; Previous</a>
                <?php else: ?>
                    <button class="rs-btn" disabled style="opacity: 0.5; cursor: not-allowed;" id="reader-btn-prev">&larr; Previous</button>
                <?php endif; ?>
            </div>

            <div style="flex: 2; display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; text-align: center;">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn" title="Share on Facebook">
                    <i class="ph ph-facebook-logo"></i> <span class="hide-on-mobile">Facebook</span>
                </a>
                
                <a href="https://bsky.app/intent/compose?text=<?php echo $encodedText . '%20' . $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn" title="Share on Bluesky">
                    <i class="ph ph-cloud-sun"></i> <span class="hide-on-mobile">Bluesky</span>
                </a>

                <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($currentUrl); ?>'); alert('Link copied to clipboard!');" class="rs-btn" title="Copy Link">
                    <i class="ph ph-link"></i> <span class="hide-on-mobile">Copy Link</span>
                </button>
            </div>

            <div style="flex: 1; display: flex; justify-content: flex-end;">
                <?php if ($nextUrl): ?>
                    <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="rs-btn rs-btn-primary" id="reader-btn-next">Next &rarr;</a>
                <?php else: ?>
                    <button class="rs-btn rs-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;" id="reader-btn-next">Next &rarr;</button>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            @media (max-width: 768px) {
                .hide-on-mobile { display: none !important; }
                .bottom-nav-container { flex-direction: column-reverse; gap: 2rem !important; }
            }
        </style>
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
    --zkl-row-border: rgba(148, 163, 184, 0.22);
    --zkl-row-hover: rgba(15, 23, 42, 0.035);
    --zkl-text: #0f172a;
    --zkl-gutter: #64748b;
    --zkl-ns: #475569;
    --zkl-stream: #0369a1;
    --zkl-punct: #475569;
    --zkl-severity-fg: #991b1b;
    --zkl-severity-bg: rgba(185, 28, 28, 0.10);
    --zkl-severity-border: rgba(185, 28, 28, 0.30);
    --zkl-exception: #b91c1c;
    --zkl-thread-fg: #1e40af;
    --zkl-thread-bg: rgba(30, 64, 175, 0.09);
    --zkl-bracket: #15803d;
    --zkl-string: #9a3412;
    --zkl-label: #6d28d9;
    --zkl-diagnostic: #0f766e;
    --zkl-function: #7e22ce;
    --zkl-identifier: #0e7490;
    --zkl-constant: #be123c;
    --zkl-number: #b45309;
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
    --zkl-ts-bg: #e6dac2;
    --zkl-ts-fg: #3e3122;
}
body.theme-dark .zoe-kernel-log,
body.theme-dark-sepia .zoe-kernel-log,
body.theme-night .zoe-kernel-log,
body.theme-oceanview .zoe-kernel-log {
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
</style>
<script>
(function () {
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
})();
</script>




