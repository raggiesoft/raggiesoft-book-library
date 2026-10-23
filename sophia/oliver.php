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

// 3. Render HTML
require_once $basePath . '/includes/classes/stardust-parsedown.php';
$Parsedown = new StardustParsedown('vanilla');
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
}

// 4. Sequence Navigation (Provided by Route JSON)
$prevUrl = $config['prevUrl'] ?? null;
$nextUrl = $config['nextUrl'] ?? null;
$sequenceName = $routeData['common']['siteName'] ?? 'Ocean View Archives';

// Fallback logic to generate previous/next if they aren't explicitly in the JSON
$routeKeys = array_keys($routeData);
$seriesSlugForFilter = '/' . ($seriesSlug ?? '');
// Filter out non-content keys
$filteredKeys = array_filter($routeKeys, function($k) use ($seriesSlugForFilter) { 
    return $k !== 'common' && $k !== $seriesSlugForFilter; 
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
            // Resolve optional YAML frontmatter `image:` and `image_alt:` for the 16:9 Chapter Header Plate (Option A).
            // Spans 100% of the reading column width on large displays and scales down proportionally on smaller screens.
            $sceneHeaderImage = !empty($frontmatter['image']) ? trim($frontmatter['image']) : '';
            $sceneHeaderImageAlt = !empty($frontmatter['image_alt']) ? trim($frontmatter['image_alt']) : ($title . ' — ' . $seriesTitle);
            if ($sceneHeaderImage !== '') {
                $sceneHeaderImage = str_replace(['{{CDN}}', '{{cms}}'], $cdnBaseUrl, $sceneHeaderImage);
                $sceneHeaderImage = preg_replace('/#.*$/', '', $sceneHeaderImage);
                if (strpos($sceneHeaderImage, 'http') !== 0) {
                    $cleanHeaderPath = ltrim($sceneHeaderImage, '/');
                    if (strpos($cleanHeaderPath, 'raggiesoft-books/') !== 0 && strpos($cleanHeaderPath, 'engine-room-records/') !== 0) {
                        $cleanHeaderPath = 'raggiesoft-books/images/scenes/' . $cleanHeaderPath;
                    }
                    $sceneHeaderImage = $cdnBaseUrl . '/' . $cleanHeaderPath;
                }
            }
            ?>
            <!-- Title Header -->
            <div style="text-align: center; margin-bottom: 3rem; padding-bottom: 2rem; border-bottom: 1px solid var(--rs-border); position: relative;">
                <?php if ($sceneHeaderImage !== ''): ?>
                <figure class="reader-chapter-plate" style="margin: 0 0 1.75rem 0; width: 100%; border-radius: 12px; overflow: hidden; border: 1px solid var(--rs-border); box-shadow: 0 6px 24px rgba(0,0,0,0.12); background: var(--rs-surface);">
                    <img src="<?php echo htmlspecialchars($sceneHeaderImage); ?>"
                         alt="<?php echo htmlspecialchars($sceneHeaderImageAlt); ?>"
                         width="1200"
                         height="675"
                         fetchpriority="high"
                         decoding="async"
                         style="width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; display: block;">
                </figure>
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

            <!-- Audio Player Integration -->
            <?php if (!empty($frontmatter['audio'])): ?>
                    <?php 
                        // Automatically prepend the artists directory path to simplify YAML frontmatter
                        $rawAudioPath = ltrim($frontmatter['audio'], '/');
                        $strippedAudioPath = str_replace('engine-room-records/artists/', '', $rawAudioPath);
                        if (strpos($rawAudioPath, 'engine-room-records/') === false) {
                            $rawAudioPath = 'engine-room-records/artists/' . $rawAudioPath;
                        }
                        $audioUrl = $cdnBaseUrl . '/' . $rawAudioPath; 
                        
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

                        // Extract artist and album info from path conventions:
                        // Expected relative path format: "{artistSlug}/{albumSlug}/web-mp3/{filenameNoExt}.mp3"
                        $audioParts = explode('/', $strippedAudioPath);
                        $artistSlug = $audioParts[0] ?? '';
                        $albumSlug = $audioParts[1] ?? '';
                        
                        $filenameWithExt = end($audioParts);
                        $filenameNoExt = pathinfo($filenameWithExt, PATHINFO_FILENAME);

                        // Construct canonical deep-links to The Listening Room (music.raggiesoft.com):
                        // - $listeningRoomTrackUrl deep-links directly to the specific song via ?track={filenameNoExt}
                        // - $listeningRoomAlbumUrl links directly to the parent album view
                        // - $listeningRoomArtistUrl links to the artist catalog page
                        $listeningRoomTrackUrl = 'https://music.raggiesoft.com/' . rawurlencode($artistSlug) . '/' . rawurlencode($albumSlug) . '/?track=' . rawurlencode($filenameNoExt);
                        $listeningRoomAlbumUrl = 'https://music.raggiesoft.com/' . rawurlencode($artistSlug) . '/' . rawurlencode($albumSlug) . '/';
                        $listeningRoomArtistUrl = 'https://music.raggiesoft.com/' . rawurlencode($artistSlug) . '/';
                        
                        // Default fallback displays before CDN JSON hydration
                        $audioTitleDisplay = ucwords(str_replace('-', ' ', preg_replace('/^\d+-\d+-/', '', $filenameNoExt)));
                        $artistDisplay = !empty($frontmatter['pov']) ? $frontmatter['pov'] : 'Narrative Soundtrack';
                        $albumDisplay = 'Stardust Engine Narratives';
                        $albumArtUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/album-art.jpg';
                        
                        // Fetch Metadata from CDN JSONs for accurate album & track info
                        $albumJsonUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/album.json';
                        $tracksJsonUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/tracks.json';
                        
                        $albumDataRaw = @file_get_contents($albumJsonUrl);
                        if ($albumDataRaw) {
                            $albumData = json_decode($albumDataRaw, true);
                            if (!empty($albumData['name'])) $albumDisplay = $albumData['name'];
                            if (!empty($albumData['byArtist']['name'])) $artistDisplay = $albumData['byArtist']['name'];
                        }
                        
                        $tracksDataRaw = @file_get_contents($tracksJsonUrl);
                        if ($tracksDataRaw) {
                            $tracksData = json_decode($tracksDataRaw, true);
                            if (!empty($tracksData['tracks'])) {
                                foreach ($tracksData['tracks'] as $track) {
                                    if (($track['fileName'] ?? '') === $filenameNoExt) {
                                        if (!empty($track['title'])) $audioTitleDisplay = $track['title'];
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // Extract external DSP & Fourthwall store links from the artist's albums.json
                        // Supports both associative era maps and {"eras": [...]} wrapper schemas,
                        // and matches albums by folder, slug, URL basename, or artwork path.
                        $spotifyUrl = $appleUrl = $amazonUrl = $youtubeUrl = $storeStandardUrl = $storeAudiophileUrl = null;
                        
                        $artistAlbumsJsonUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/albums.json';
                        $artistAlbumsRaw = @file_get_contents($artistAlbumsJsonUrl);
                        if ($artistAlbumsRaw) {
                            $artistAlbumsData = json_decode($artistAlbumsRaw, true);
                            if (is_array($artistAlbumsData)) {
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
                        }
                    ?>
                    <div class="reader-audio-player" style="margin-top: 2rem; border: 1px solid var(--rs-border); border-radius: 12px; background: var(--rs-surface); overflow: hidden; max-width: 100%; margin-left: auto; margin-right: auto; text-align: left; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem;">
                            <a href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Open album <?php echo htmlspecialchars($albumDisplay); ?> in The Listening Room" aria-label="Open album <?php echo htmlspecialchars($albumDisplay); ?> by <?php echo htmlspecialchars($artistDisplay); ?> in The Listening Room" style="display: block; flex-shrink: 0; border-radius: 6px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <img src="<?php echo htmlspecialchars($albumArtUrl); ?>" alt="Album cover for <?php echo htmlspecialchars($albumDisplay); ?> by <?php echo htmlspecialchars($artistDisplay); ?>" style="width: 80px; height: 80px; border-radius: 6px; object-fit: cover; display: block;">
                            </a>
                            
                            <div style="flex: 1; overflow: hidden; display: flex; flex-direction: column; min-width: 0;">
                                <div style="font-size: 0.75rem; opacity: 0.75; text-transform: uppercase; letter-spacing: 1px; line-height: 1; margin-bottom: 0.35rem;">Background Audio</div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-weight: 700; font-size: 1.1rem; line-height: 1.2; margin-bottom: 0.25rem;">
                                    <span class="reader-audio-scroll-text">
                                        <a href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Listen to &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room" style="color: inherit; text-decoration: underline; text-decoration-color: var(--rs-primary); text-underline-offset: 3px;">
                                            <?php echo htmlspecialchars($audioTitleDisplay); ?> <i class="ph ph-arrow-square-out" aria-hidden="true" style="font-size: 0.85em; opacity: 0.85;"></i>
                                        </a>
                                    </span>
                                </div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-size: 0.85rem; opacity: 0.85; margin-bottom: 0.75rem;">
                                    <span class="reader-audio-scroll-text">
                                        <a href="<?php echo htmlspecialchars($listeningRoomArtistUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="View <?php echo htmlspecialchars($artistDisplay); ?> in The Listening Room" style="color: inherit; text-decoration: none; border-bottom: 1px dotted currentColor;"><?php echo htmlspecialchars($artistDisplay); ?></a>
                                        &bull;
                                        <a href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="reader-audio-external-link" title="Open album <?php echo htmlspecialchars($albumDisplay); ?> in The Listening Room" style="color: inherit; text-decoration: underline; text-underline-offset: 2px;"><i><?php echo htmlspecialchars($albumDisplay); ?></i></a>
                                    </span>
                                </div>
                                
                                <!-- Custom HTML5 Audio Player UI -->
                                <div class="reader-audio-controls-row">
                                    <audio id="narrative-audio-element" data-title="<?php echo htmlspecialchars($audioTitleDisplay); ?>" data-artist="<?php echo htmlspecialchars($artistDisplay); ?>" data-album="<?php echo htmlspecialchars($albumDisplay); ?>" data-artwork="<?php echo htmlspecialchars($albumArtUrl); ?>" data-listening-room-track-url="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" data-listening-room-album-url="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" src="<?php echo htmlspecialchars($audioUrl); ?>" data-start-time="<?php echo $audioStart; ?>" loop preload="metadata" style="display: none;"></audio>
                                    
                                    <button id="narrative-audio-play-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="Play/Pause" title="Play/Pause">
                                        <i class="ph ph-play" style="font-size: 1.2rem;" id="narrative-audio-play-icon" aria-hidden="true"></i>
                                    </button>
                                    
                                    <div class="reader-audio-scrubber-container">
                                        <span id="narrative-audio-current">0:00</span>
                                        <input type="range" id="narrative-audio-scrubber" value="0" min="0" step="1" aria-label="Seek background audio timeline">
                                        <span id="narrative-audio-duration">0:00</span>
                                    </div>
                                    
                                    <button id="narrative-audio-loop-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid var(--rs-primary); color: var(--rs-primary); background: rgba(0,0,0,0.05);" aria-label="Toggle Repeat: ON" title="Repeat 1 Track: ON">
                                        <i class="ph ph-repeat-once" style="font-size: 1.2rem;" aria-hidden="true"></i>
                                    </button>
                                    
                                    <?php $lyricsUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/lyrics/' . $filenameNoExt . '.md'; ?>
                                    <button id="narrative-audio-lyrics-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="View Lyrics" title="View Lyrics" data-url="<?php echo htmlspecialchars($lyricsUrl); ?>" data-title="<?php echo htmlspecialchars($audioTitleDisplay); ?>">
                                        <i class="ph ph-music-notes" style="font-size: 1.2rem;" aria-hidden="true"></i>
                                    </button>

                                    <a href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn reader-audio-external-link" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent; color: inherit; text-decoration: none;" aria-label="Open &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room" title="Open &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room">
                                        <i class="ph ph-headphones" style="font-size: 1.2rem;" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- The Listening Room & External Streaming Links -->
                        <div style="background: var(--rs-bg); border-top: 1px solid var(--rs-border); padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; align-items: center; text-align: center;">
                            <div style="font-size: 0.8rem; opacity: 0.8;">Open this song or full album directly in <strong>The Listening Room</strong>, or stream the high-fidelity mix on your favorite service:</div>
                            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                <a href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" title="Listen to &quot;<?php echo htmlspecialchars($audioTitleDisplay); ?>&quot; in The Listening Room" class="rs-btn rs-btn-primary reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-headphones" aria-hidden="true" style="margin-right: 4px;"></i> Song in Listening Room <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.85;"></i></a>
                                <a href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" title="Open <?php echo htmlspecialchars($albumDisplay); ?> in The Listening Room" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-vinyl-record" aria-hidden="true" style="margin-right: 4px;"></i> Full Album <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php if ($spotifyUrl): ?>
                                    <a href="<?php echo htmlspecialchars($spotifyUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Spotify" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-spotify-logo" aria-hidden="true" style="margin-right: 4px;"></i> Spotify <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($appleUrl): ?>
                                    <a href="<?php echo htmlspecialchars($appleUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Apple Music" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-apple-logo" aria-hidden="true" style="margin-right: 4px;"></i> Apple <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($amazonUrl): ?>
                                    <a href="<?php echo htmlspecialchars($amazonUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Amazon Music" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-amazon-logo" aria-hidden="true" style="margin-right: 4px;"></i> Amazon <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($youtubeUrl): ?>
                                    <a href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on YouTube" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-youtube-logo" aria-hidden="true" style="margin-right: 4px;"></i> YouTube <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($storeStandardUrl): ?>
                                    <a href="<?php echo htmlspecialchars($storeStandardUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Buy MP3 / OGG Digital Archive" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-shopping-cart" aria-hidden="true" style="margin-right: 4px;"></i> MP3/OGG <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($storeAudiophileUrl): ?>
                                    <a href="<?php echo htmlspecialchars($storeAudiophileUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Buy WAV / FLAC Audiophile Archive" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-shopping-bag" aria-hidden="true" style="margin-right: 4px;"></i> WAV/FLAC <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px; opacity: 0.7;"></i></a>
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
                            <span style="opacity: 0.8;"><?php echo htmlspecialchars($artistDisplay); ?> &bull; <i><?php echo htmlspecialchars($albumDisplay); ?></i></span>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="<?php echo htmlspecialchars($listeningRoomTrackUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn rs-btn-primary reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-headphones" aria-hidden="true" style="margin-right: 4px;"></i> Open Song <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px;"></i></a>
                                <a href="<?php echo htmlspecialchars($listeningRoomAlbumUrl); ?>" target="_blank" rel="noopener noreferrer" class="rs-btn reader-audio-external-link" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;"><i class="ph ph-vinyl-record" aria-hidden="true" style="margin-right: 4px;"></i> Open Album <i class="ph ph-arrow-square-out" aria-hidden="true" style="margin-left: 4px;"></i></a>
                            </div>
                        </div>
                    </dialog>
                    
                    
                <?php endif; ?>

            <!-- Parsedown Content -->
            <?php echo $htmlContent; ?>
        </article>
        
        <!-- E-Book CTA Card (Dismissible; persisted in reader-settings.showOfflineCta and resettable in Settings) -->
        <?php if (!$specialPageType): ?>
        <div id="rs-offline-cta-card" class="rs-offline-cta-card" style="position: relative; margin-top: 4rem; padding: 2rem; border: 1px solid var(--rs-border); border-radius: 12px; background: var(--rs-surface); text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <button type="button" id="rs-dismiss-offline-cta" class="rs-btn" title="Dismiss card (can be re-enabled in Settings)" aria-label="Dismiss Take this Story Offline card" style="position: absolute; top: 0.75rem; right: 0.75rem; padding: 0.3rem 0.65rem; font-size: 0.8rem; opacity: 0.75; display: inline-flex; align-items: center; gap: 0.25rem;">
                <i class="ph ph-x"></i> Hide
            </button>
            <h3 style="margin-bottom: 0.75rem; color: var(--rs-primary);">Take this Story Offline</h3>
            <p style="margin-bottom: 1.25rem; font-size: 1.05rem; opacity: 0.9;">Download the complete, illustrated eBook for your e-reader, tablet, or phone. Yours to keep forever.</p>
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.epub"); ?>" target="_blank" class="rs-btn rs-btn-primary" style="font-size: 1.05rem; padding: 0.7rem 1.4rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-download-simple"></i> Download EPUB
                </a>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.pdf"); ?>" target="_blank" class="rs-btn" style="font-size: 1.05rem; padding: 0.7rem 1.4rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-file-pdf"></i> Download PDF
                </a>
            </div>
            <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px dashed var(--rs-border); font-size: 0.85rem; opacity: 0.85; display: flex; align-items: center; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                <span><i class="ph ph-cell-signal-low"></i> On a metered connection? Plain-text (no images):</span>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.epub"); ?>" target="_blank" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">EPUB (Text Only)</a>
                <span aria-hidden="true">&bull;</span>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.pdf"); ?>" target="_blank" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">PDF (Text Only)</a>
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
.zoe-kernel-log-title { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--zkl-text); }
.zkl-header-sep { color: var(--zkl-stream); margin: 0 0.25rem; }
.zoe-kernel-log-meta { font-size: 0.72rem; padding: 0.12rem 0.5rem; border-radius: 999px; background: var(--zkl-ts-bg); color: var(--zkl-ts-fg); flex-shrink: 0; }
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
.zoe-log-main { flex: 1; min-width: 0; overflow-wrap: anywhere; word-break: break-word; }
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
@media (max-width: 640px) {
    .zoe-log-entry { flex-wrap: wrap; gap: 0.4rem; }
    .zkl-timestamp { margin-left: 2.2rem; }
}
</style>




