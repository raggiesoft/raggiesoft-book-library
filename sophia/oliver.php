<?php
// RaggieSoft Books - Markdown Viewer (Route JSON Driven)

$requestUri = rtrim($requestUri, '/');
$parts = explode('/', ltrim($requestUri, '/'));
$seriesSlug = $parts[0] ?? '';

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

if (empty($actualFilePath)) {
    echo '<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; overflow-y: auto;"><div class="book-page"><h1>Chapter Not Found</h1><p>The requested route could not be found in the route map.</p></div></main>';
    return;
}

// 1. Fetch Markdown Content from CDN (or intercept internal paths)
// $cdnBaseUrl is injected by isabel.php
$specialPageType = null;
$specialPageIndex1 = 0;
$specialPageIndex2 = 0;
$mdContent = '';

if ($actualFilePath === '__SERIES_LANDING__') {
    $specialPageType = 'landing';
} elseif ($actualFilePath === '__TOC__') {
    $specialPageType = 'toc';
} elseif (strpos($actualFilePath, '__BOOK_TOC__|') === 0) {
    $p = explode('|', $actualFilePath);
    $specialPageType = 'book_toc';
    $specialPageIndex1 = (int)($p[1] ?? 0);
} elseif (strpos($actualFilePath, '__CHAP_TOC__|') === 0) {
    $p = explode('|', $actualFilePath);
    $specialPageType = 'chap_toc';
    $specialPageIndex1 = (int)($p[1] ?? 0);
    $specialPageIndex2 = (int)($p[2] ?? 0);
} else {
    if (isset($prefetchedMdContent) && $prefetchedMdContent !== null) {
        $mdContent = $prefetchedMdContent;
    } else {
        $mdUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/' . $actualFilePath;
        $mdContent = @file_get_contents($mdUrl);
        if ($mdContent !== false) {
            $mdContent = str_replace('{{CDN}}', $cdnBaseUrl, $mdContent);
        }
    }
}

if ($mdContent === false && !$specialPageType) {
    echo '<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; overflow-y: auto;"><div class="book-page"><h1>File Not Found</h1><p>The narrative file could not be loaded from the Vault.</p></div></main>';
    return;
}

// 2. Parse YAML Frontmatter
$frontmatter = [];
if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $mdContent, $matches)) {
    $rawFrontmatter = $matches[1];
    $mdContent = substr($mdContent, strlen($matches[0])); // Strip it from the content
    
    $lines = explode("\n", $rawFrontmatter);
    $currentArrayKey = null;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;
        
        if (strpos($trimmed, '-') === 0 && $currentArrayKey) {
            $val = trim(substr($trimmed, 1));
            $val = trim($val, '"\'');
            $frontmatter[$currentArrayKey][] = $val;
        } elseif (strpos($trimmed, ':') !== false) {
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

// 3. Render HTML
require_once $basePath . '/includes/classes/stardust-parsedown.php';
$Parsedown = new StardustParsedown();
if ($specialPageType) {
    ob_start();
    if ($specialPageType === 'landing') {
        require_once __DIR__ . '/landing.php';
    } elseif ($specialPageType === 'toc') {
        require_once __DIR__ . '/toc.php';
    } elseif ($specialPageType === 'book_toc') {
        require_once __DIR__ . '/book-toc.php';
    } elseif ($specialPageType === 'chap_toc') {
        require_once __DIR__ . '/chap-toc.php';
    }
    $htmlContent = ob_get_clean();
} else {
    $htmlContent = $Parsedown->text($mdContent);
}

// 4. Sequence Navigation (Provided by Route JSON)
$prevUrl = $config['prevUrl'] ?? null;
$nextUrl = $config['nextUrl'] ?? null;
$sequenceName = $routeData['common']['siteName'] ?? 'Ocean View Archives';

// Fallback logic to generate previous/next if they aren't explicitly in the JSON
$routeKeys = array_keys($routeData);
$seriesSlugForFilter = '/' . ($seriesSlug ?? '');
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
if (isset($_GET['preview_theme']) && !empty($_GET['preview_theme'])) {
    $narrativeTheme = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['preview_theme']);
}
?>
<?php if ($narrativeTheme): ?>
    <meta name="stardust-narrative-theme" content="<?php echo htmlspecialchars($narrativeTheme); ?>">
    <link id="narrative-theme-css" rel="stylesheet" href="<?php echo htmlspecialchars($cdnBaseUrl . '/raggiesoft-books/css/themes/' . $narrativeTheme . '.css'); ?>" disabled>
    <?php if ($narrativeTheme === 'dark-rain'): ?>
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

<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; overflow-y: auto;">
    <div class="book-page reader-page">
        <!-- Breadcrumbs & Nav -->
        <div class="reader-nav" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="opacity: 0.7; font-size: 0.9rem;">
            <?php 
            global $katie, $seriesSlug, $pageConfig;
            $actualFilePath = $pageConfig["filePath"] ?? "";
            
            $seriesTitle = !empty($katie['series_title']) ? $katie['series_title'] : (ucwords(str_replace('-', ' ', $seriesSlug)));
            $bookTitle = '';
            $chapTitle = '';
            
            if ($katie && isset($katie['books'])) {
                foreach ($katie['books'] as $b) {
                    if (isset($b['chapters'])) {
                        foreach ($b['chapters'] as $c) {
                            if (isset($c['parts'])) {
                                foreach ($c['parts'] as $p) {
                                    if ($p['file_path'] === $actualFilePath) {
                                        $bookTitle = $b['book_title'];
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
            <a href="/" style="text-decoration: none;">Library</a>
            &raquo; <strong style="color: var(--rs-text); font-weight: 600;"><?php echo htmlspecialchars($seriesTitle); ?></strong>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button id="stardust-sidebar-toggle" class="rs-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="ph ph-list"></i> Chapters</button>
            </div>
        </div>

        <article class="story-content">
            <?php if (!$specialPageType): ?>
            <!-- Title Header -->
            <div style="text-align: center; margin-bottom: 3rem; padding-bottom: 2rem; border-bottom: 1px solid var(--rs-border); position: relative;">
                <div class="reader-series-header">
                    <?php 
                    $eyebrowParts = [$seriesTitle];
                    if ($bookTitle) $eyebrowParts[] = $bookTitle;
                    if ($chapTitle) $eyebrowParts[] = $chapTitle;
                    echo htmlspecialchars(implode(' • ', $eyebrowParts));
                    ?>
                </div>
                <h1 class="reader-part-header"><?php echo htmlspecialchars($title); ?></h1>
                
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

                        <?php 
                        if (!empty($frontmatter['date'])): 
                            $isoString = '';
                            if (!empty($frontmatter['start_time'])) {
                                $rawTz = $frontmatter['timezone'] ?? 'America/New_York';
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
                            
                            <?php if ($isoString): ?>
                            
                            <?php endif; ?>
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

            <?php if (!empty($frontmatter['audio'])): ?>
                    <?php 
                        // Automatically prepend the artists directory path to simplify YAML frontmatter
                        $rawAudioPath = ltrim($frontmatter['audio'], '/');
                        $strippedAudioPath = str_replace('engine-room-records/artists/', '', $rawAudioPath);
                        if (strpos($rawAudioPath, 'engine-room-records/') === false) {
                            $rawAudioPath = 'engine-room-records/artists/' . $rawAudioPath;
                        }
                        $audioUrl = $cdnBaseUrl . '/' . $rawAudioPath; 
                        
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

                        $audioParts = explode('/', $strippedAudioPath);
                        $artistSlug = $audioParts[0] ?? '';
                        $albumSlug = $audioParts[1] ?? '';
                        
                        $filenameWithExt = end($audioParts);
                        $filenameNoExt = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        
                        // Default fallback displays
                        $audioTitleDisplay = ucwords(str_replace('-', ' ', preg_replace('/^\d+-\d+-/', '', $filenameNoExt)));
                        $artistDisplay = !empty($frontmatter['pov']) ? $frontmatter['pov'] : 'Narrative Soundtrack';
                        $albumDisplay = 'Stardust Engine Narratives';
                        $albumArtUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/album-art.jpg';
                        
                        // Fetch Metadata from CDN
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
                                    if ($track['fileName'] === $filenameNoExt) {
                                        if (!empty($track['title'])) $audioTitleDisplay = $track['title'];
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $spotifyUrl = $appleUrl = $amazonUrl = $youtubeUrl = $storeStandardUrl = $storeAudiophileUrl = null;
                        
                        $artistAlbumsJsonUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/albums.json';
                        $artistAlbumsRaw = @file_get_contents($artistAlbumsJsonUrl);
                        if ($artistAlbumsRaw) {
                            $artistAlbumsData = json_decode($artistAlbumsRaw, true);
                            if ($artistAlbumsData) {
                                foreach ($artistAlbumsData as $era) {
                                    if (!empty($era['albums'])) {
                                        foreach ($era['albums'] as $alb) {
                                            if (isset($alb['folder']) && $alb['folder'] === $albumSlug) {
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
                            <img src="<?php echo htmlspecialchars($albumArtUrl); ?>" alt="Album Art" style="width: 80px; height: 80px; border-radius: 6px; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            
                            <div style="flex: 1; overflow: hidden; display: flex; flex-direction: column; min-width: 0;">
                                <div style="font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px; line-height: 1; margin-bottom: 0.35rem;">Background Audio</div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-weight: 700; font-size: 1.1rem; line-height: 1.2; margin-bottom: 0.25rem;">
                                    <span class="reader-audio-scroll-text"><?php echo htmlspecialchars($audioTitleDisplay); ?></span>
                                </div>
                                
                                <div class="reader-audio-scroll-wrap" style="font-size: 0.85rem; opacity: 0.8; margin-bottom: 0.75rem;">
                                    <span class="reader-audio-scroll-text"><?php echo htmlspecialchars($artistDisplay); ?> &bull; <i><?php echo htmlspecialchars($albumDisplay); ?></i></span>
                                </div>
                                
                                <div class="reader-audio-controls-row">
                                    <audio id="narrative-audio-element" data-title="<?php echo htmlspecialchars($audioTitleDisplay); ?>" data-artist="<?php echo htmlspecialchars($artistDisplay); ?>" data-album="<?php echo htmlspecialchars($albumDisplay); ?>" data-artwork="<?php echo htmlspecialchars($albumArtUrl); ?>" src="<?php echo htmlspecialchars($audioUrl); ?>" data-start-time="<?php echo $audioStart; ?>" loop preload="metadata" style="display: none;"></audio>
                                    
                                    <button id="narrative-audio-play-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="Play/Pause" title="Play/Pause">
                                        <i class="ph ph-play" style="font-size: 1.2rem;" id="narrative-audio-play-icon"></i>
                                    </button>
                                    
                                    <div class="reader-audio-scrubber-container">
                                        <span id="narrative-audio-current">0:00</span>
                                        <input type="range" id="narrative-audio-scrubber" value="0" min="0" step="1">
                                        <span id="narrative-audio-duration">0:00</span>
                                    </div>
                                    
                                    <button id="narrative-audio-loop-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid var(--rs-primary); color: var(--rs-primary); background: rgba(0,0,0,0.05);" aria-label="Toggle Repeat: ON" title="Repeat 1 Track: ON">
                                        <i class="ph ph-repeat-once" style="font-size: 1.2rem;"></i>
                                    </button>
                                    
                                    <?php $lyricsUrl = $cdnBaseUrl . '/engine-room-records/artists/' . $artistSlug . '/' . $albumSlug . '/lyrics/' . $filenameNoExt . '.md'; ?>
                                    <button id="narrative-audio-lyrics-toggle" class="rs-btn" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--rs-border); background: transparent;" aria-label="View Lyrics" title="View Lyrics" data-url="<?php echo htmlspecialchars($lyricsUrl); ?>" data-title="<?php echo htmlspecialchars($audioTitleDisplay); ?>">
                                        <i class="ph ph-music-notes" style="font-size: 1.2rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                                                <?php if ($spotifyUrl || $appleUrl || $amazonUrl || $youtubeUrl || $storeStandardUrl || $storeAudiophileUrl): ?>
                        <div style="background: var(--rs-bg); border-top: 1px solid var(--rs-border); padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; align-items: center; text-align: center;">
                            <div style="font-size: 0.8rem; opacity: 0.7;">The background audio is a low-quality stream. To hear the full high-fidelity mix (and support the author), please stream it on your favorite service:</div>
                            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                <?php if ($spotifyUrl): ?>
                                    <a href="<?php echo htmlspecialchars($spotifyUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Spotify" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-spotify-logo" style="margin-right: 4px;"></i> Spotify <i class="ph ph-arrow-square-out" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($appleUrl): ?>
                                    <a href="<?php echo htmlspecialchars($appleUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Apple Music" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-apple-logo" style="margin-right: 4px;"></i> Apple <i class="ph ph-arrow-square-out" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($amazonUrl): ?>
                                    <a href="<?php echo htmlspecialchars($amazonUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on Amazon Music" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-amazon-logo" style="margin-right: 4px;"></i> Amazon <i class="ph ph-arrow-square-out" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($youtubeUrl): ?>
                                    <a href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Listen on YouTube" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-youtube-logo" style="margin-right: 4px;"></i> YouTube <i class="ph ph-arrow-square-out" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($storeStandardUrl): ?>
                                    <a href="<?php echo htmlspecialchars($storeStandardUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Buy MP3 / OGG Digital Archive" class="rs-btn rs-btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-shopping-cart" style="margin-right: 4px;"></i> MP3/OGG <i class="ph ph-arrow-square-out" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                                <?php if ($storeAudiophileUrl): ?>
                                    <a href="<?php echo htmlspecialchars($storeAudiophileUrl); ?>" target="_blank" rel="noopener noreferrer nofollow" title="Buy WAV / FLAC Audiophile Archive" class="rs-btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><i class="ph ph-shopping-bag" style="margin-right: 4px;"></i> WAV/FLAC <i class="ph ph-arrow-square-out" style="margin-left: 4px; opacity: 0.7;"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        
                    </div>
                    
                    <dialog id="narrative-lyrics-dialog" style="padding: 0; border: 1px solid var(--rs-border); border-radius: 12px; background: var(--rs-bg); color: var(--rs-text); box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 600px; width: 90%; max-height: 85vh; overflow: hidden;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid var(--rs-border); background: var(--rs-surface);">
                            <h3 id="narrative-lyrics-title" style="margin: 0; font-size: 1.2rem; font-weight: 700;"></h3>
                            <button id="narrative-lyrics-close" class="rs-btn" style="padding: 0.5rem; border: none; background: transparent; font-size: 1.2rem; cursor: pointer;"><i class="ph ph-x"></i></button>
                        </div>
                        <div id="narrative-lyrics-content" style="padding: 1.5rem; overflow-y: auto; max-height: calc(85vh - 80px); font-size: 1.1rem; line-height: 1.6;">
                        </div>
                    </dialog>
                    
                    
                <?php endif; ?>

            <!-- Parsedown Content -->
            <?php echo $htmlContent; ?>
        </article>
        
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
                    <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="rs-btn">&larr; Previous</a>
                <?php else: ?>
                    <button class="rs-btn" disabled style="opacity: 0.5; cursor: not-allowed;">&larr; Previous</button>
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
                    <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="rs-btn rs-btn-primary">Next &rarr;</a>
                <?php else: ?>
                    <button class="rs-btn rs-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Next &rarr;</button>
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


