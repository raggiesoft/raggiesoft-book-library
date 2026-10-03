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

// 1. Fetch Markdown Content from CDN
// $cdnBaseUrl is injected by isabel.php
$mdUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/' . $actualFilePath;

$mdContent = @file_get_contents($mdUrl);
if ($mdContent !== false) {
    $mdContent = str_replace('{{CDN}}', $cdnBaseUrl, $mdContent);
}
if ($mdContent === false) {
    echo '<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; overflow-y: auto;"><div class="book-page"><h1>File Not Found</h1><p>The narrative file could not be loaded from the Vault.</p></div></main>';
    return;
}

// 2. Parse YAML Frontmatter
$frontmatter = [];
if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $mdContent, $matches)) {
    $rawFrontmatter = $matches[1];
    $mdContent = substr($mdContent, strlen($matches[0])); // Strip it from the content
    
    $lines = explode("\n", $rawFrontmatter);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, ':') !== false) {
            list($key, $val) = explode(':', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, '"\''); // remove surrounding quotes
            if ($val !== '') {
                $frontmatter[$key] = $val;
            }
        }
    }
}

// 3. Render HTML
require_once $basePath . '/includes/classes/stardust-parsedown.php';
$Parsedown = new StardustParsedown();
$htmlContent = $Parsedown->text($mdContent);

// 4. Sequence Navigation (Provided by Route JSON)
$prevUrl = $config['prevUrl'] ?? null;
$nextUrl = $config['nextUrl'] ?? null;
$sequenceName = $routeData['common']['siteName'] ?? 'Ocean View Archives';

// Fallback logic to generate previous/next if they aren't explicitly in the JSON
$routeKeys = array_keys($routeData);
$filteredKeys = array_filter($routeKeys, function($k) { return $k !== 'common' && strpos($k, '/book-') !== false; });
$filteredKeys = array_values($filteredKeys); // reindex
$currentIndex = array_search($requestUri, $filteredKeys);
if ($currentIndex !== false) {
    if ($currentIndex > 0 && empty($prevUrl)) $prevUrl = $filteredKeys[$currentIndex - 1];
    if ($currentIndex < count($filteredKeys) - 1 && empty($nextUrl)) $nextUrl = $filteredKeys[$currentIndex + 1];
}

$overviewUrl = '/' . $seriesSlug;
$title = $config['title'] ?? ($frontmatter['title'] ?? 'Untitled Chapter');
?>

<main id="stardust-reading-pane" tabindex="-1" style="flex: 1; height: 100vh; overflow-y: auto;">
    <div class="book-page reader-page">
        <!-- Breadcrumbs & Nav -->
        <div class="reader-nav" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div style="opacity: 0.7; font-size: 0.9rem;">
            <?php 
            global $katie, $seriesSlug, $actualFilePath;
            
            $seriesTitle = $katie['series_title'] ?? (ucfirst($seriesSlug) . ' Narrative');
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
            <a href="https://raggiesoft.com/raggiesoft-books/books" style="text-decoration: none;">Library</a>
            &raquo; <strong style="color: var(--rs-text); font-weight: 600;"><?php echo htmlspecialchars($seriesTitle); ?></strong>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button id="stardust-sidebar-toggle" class="rs-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="ph ph-list"></i> Chapters</button>
            </div>
        </div>

        <article class="story-content">
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
                
                <?php if (!empty($frontmatter['date']) || !empty($frontmatter['start_time']) || !empty($frontmatter['pov']) || !empty($frontmatter['location'])): ?>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; opacity: 0.7; font-size: 0.85rem; font-weight: 600;">
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
                            <script>
                            (function() {
                                const el = document.getElementById('story-datetime-display');
                                const iso = el.getAttribute('data-iso');
                                if (iso) {
                                    try {
                                        const d = new Date(iso);
                                        const iana = el.getAttribute('data-iana');
                                        const options = { 
                                            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', 
                                            hour: 'numeric', minute: '2-digit', timeZoneName: 'short' 
                                        };
                                        if (iana) {
                                            options.timeZone = iana;
                                        }
                                        const formatter = new Intl.DateTimeFormat('en-US', options);
                                        el.querySelector('.dt-text').textContent = formatter.format(d);
                                    } catch(e) {}
                                }
                            })();
                            </script>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['location'])): ?>
                            <span style="display: flex; align-items: center; gap: 4px;"><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($frontmatter['location']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['pov'])): ?>
                            <span style="display: flex; align-items: center; gap: 4px;"><i class="ph ph-eye"></i> POV: <?php echo htmlspecialchars($frontmatter['pov']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($frontmatter['audio'])): ?>
                    <?php 
                        // Automatically prepend the artists directory path to simplify YAML frontmatter
                        $rawAudioPath = ltrim($frontmatter['audio'], '/');
                        $strippedAudioPath = str_replace('engine-room-records/artists/', '', $rawAudioPath);
                        if (strpos($rawAudioPath, 'engine-room-records/') === false) {
                            $rawAudioPath = 'engine-room-records/artists/' . $rawAudioPath;
                        }
                        $audioUrl = $cdnBaseUrl . '/' . $rawAudioPath; 
                        
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
                    <div class="reader-audio-player" style="margin-top: 2rem; border: 1px solid var(--rs-border); border-radius: 12px; background: var(--rs-card-bg); overflow: hidden; max-width: 500px; margin-left: auto; margin-right: auto; text-align: left; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem;">
                            <img src="<?php echo htmlspecialchars($albumArtUrl); ?>" alt="Album Art" style="width: 80px; height: 80px; border-radius: 6px; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            
                            <div style="flex: 1; overflow: hidden;">
                                <div style="font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px; line-height: 1; margin-bottom: 0.35rem;">Background Audio</div>
                                <div style="font-weight: 700; font-size: 1.1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($audioTitleDisplay); ?></div>
                                <div style="font-size: 0.85rem; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($artistDisplay); ?> &bull; <i><?php echo htmlspecialchars($albumDisplay); ?></i></div>
                            </div>
                            
                            <button id="narrative-audio-play" class="rs-btn" style="border-radius: 50%; width: 50px; height: 50px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid var(--rs-primary); color: var(--rs-primary);" aria-label="Play Soundtrack">
                                <i class="ph ph-play" style="font-size: 1.5rem; margin-left: 3px;"></i>
                            </button>
                        </div>
                        
                        <?php if ($spotifyUrl || $appleUrl || $amazonUrl || $youtubeUrl || $storeStandardUrl || $storeAudiophileUrl): ?>
                        <div style="background: var(--rs-bg); border-top: 1px solid var(--rs-border); padding: 0.75rem 1rem; display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                            <?php if ($spotifyUrl): ?>
                                <a href="<?php echo htmlspecialchars($spotifyUrl); ?>" target="_blank" title="Listen on Spotify" class="rs-btn" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border-color: #1DB954; color: #1DB954;"><i class="ph ph-spotify-logo" style="margin-right: 4px;"></i> Spotify</a>
                            <?php endif; ?>
                            <?php if ($appleUrl): ?>
                                <a href="<?php echo htmlspecialchars($appleUrl); ?>" target="_blank" title="Listen on Apple Music" class="rs-btn" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border-color: #FA243C; color: #FA243C;"><i class="ph ph-apple-logo" style="margin-right: 4px;"></i> Apple</a>
                            <?php endif; ?>
                            <?php if ($amazonUrl): ?>
                                <a href="<?php echo htmlspecialchars($amazonUrl); ?>" target="_blank" title="Listen on Amazon Music" class="rs-btn" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border-color: #00A8E1; color: #00A8E1;"><i class="ph ph-amazon-logo" style="margin-right: 4px;"></i> Amazon</a>
                            <?php endif; ?>
                            <?php if ($youtubeUrl): ?>
                                <a href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" title="Listen on YouTube" class="rs-btn" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border-color: #FF0000; color: #FF0000;"><i class="ph ph-youtube-logo" style="margin-right: 4px;"></i> YouTube</a>
                            <?php endif; ?>
                            <?php if ($storeStandardUrl): ?>
                                <a href="<?php echo htmlspecialchars($storeStandardUrl); ?>" target="_blank" title="Buy MP3 / OGG Digital Archive" class="rs-btn rs-btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;"><i class="ph ph-shopping-cart" style="margin-right: 4px;"></i> MP3/OGG</a>
                            <?php endif; ?>
                            <?php if ($storeAudiophileUrl): ?>
                                <a href="<?php echo htmlspecialchars($storeAudiophileUrl); ?>" target="_blank" title="Buy WAV / FLAC Audiophile Archive" class="rs-btn" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border-color: var(--rs-primary); color: var(--rs-primary);"><i class="ph ph-shopping-bag" style="margin-right: 4px;"></i> WAV/FLAC</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <audio id="narrative-audio-element" src="<?php echo htmlspecialchars($audioUrl); ?>" loop preload="none"></audio>
                    </div>
                    <script>
                    (function() {
                        const audioEl = document.getElementById('narrative-audio-element');
                        const playBtn = document.getElementById('narrative-audio-play');
                        if (audioEl && playBtn) {
                            const icon = playBtn.querySelector('i');
                            
                            // Initialize Media Session API
                            if ('mediaSession' in navigator) {
                                navigator.mediaSession.metadata = new MediaMetadata({
                                    title: <?php echo json_encode($audioTitleDisplay); ?>,
                                    artist: <?php echo json_encode($artistDisplay); ?>,
                                    album: <?php echo json_encode($albumDisplay); ?>,
                                    artwork: [
                                        { src: <?php echo json_encode($albumArtUrl); ?>, sizes: '512x512', type: 'image/jpeg' }
                                    ]
                                });
                                navigator.mediaSession.setActionHandler('play', () => audioEl.play());
                                navigator.mediaSession.setActionHandler('pause', () => audioEl.pause());
                            }

                            // Sync Play/Pause Button State with actual Audio Element state
                            // This ensures the button updates if paused via Media Keys/Lockscreen
                            audioEl.addEventListener('play', () => {
                                icon.classList.remove('ph-play');
                                icon.classList.add('ph-pause');
                                playBtn.classList.add('rs-btn-primary');
                                playBtn.setAttribute('aria-label', 'Pause Soundtrack');
                            });

                            audioEl.addEventListener('pause', () => {
                                icon.classList.remove('ph-pause');
                                icon.classList.add('ph-play');
                                playBtn.classList.remove('rs-btn-primary');
                                playBtn.setAttribute('aria-label', 'Play Soundtrack');
                            });

                            playBtn.addEventListener('click', () => {
                                if (audioEl.paused) {
                                    audioEl.play().catch(e => console.error("Audio play failed:", e));
                                } else {
                                    audioEl.pause();
                                }
                            });
                            
                            // Check Auto-Play User Preference
                            try {
                                const storedSettings = localStorage.getItem('reader-settings');
                                if (storedSettings) {
                                    const settings = JSON.parse(storedSettings);
                                    if (settings.autoPlayAudio === 'true') {
                                        // Browsers may still block this unless the user has interacted with the domain previously
                                        audioEl.play().catch(e => {
                                            console.warn("Autoplay blocked by browser. User must interact first.");
                                        });
                                    }
                                }
                            } catch (e) {}
                        }
                    })();
                    </script>
                <?php endif; ?>

            </div>

            <!-- Parsedown Content -->
            <?php echo $htmlContent; ?>
        </article>
        
        <!-- Bottom Navigation -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--rs-border);">
            <?php if ($prevUrl): ?>
                <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="rs-btn">&larr; Previous</a>
            <?php else: ?>
                <button class="rs-btn" disabled style="opacity: 0.5; cursor: not-allowed;">&larr; Previous</button>
            <?php endif; ?>

            <?php if ($nextUrl): ?>
                <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="rs-btn rs-btn-primary">Next &rarr;</a>
            <?php else: ?>
                <button class="rs-btn rs-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Next &rarr;</button>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.reader-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}
.story-content {
    font-size: 1.15rem;
    line-height: 1.8;
    color: var(--rs-text);
}
.story-content p {
    margin-bottom: 1.5rem;
    text-indent: 2rem; /* Traditional book indentation */
}
.reader-series-header {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--rs-primary, #007bff);
    margin-bottom: 1rem;
    opacity: 0.9;
}
.reader-part-header {
    font-size: 2.5rem;
    font-weight: 900;
    color: var(--rs-text);
    margin: 0 0 1rem 0;
    line-height: 1.2;
}
</style>
