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
$cdnBaseUrl = 'https://assets.raggiesoft.com';
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
            <a href="https://raggiesoft.com/raggiesoft-books/books" style="text-decoration: none;">Publisher Home</a> &raquo; 
            <strong style="color: var(--rs-text);"><?php echo htmlspecialchars($title); ?></strong>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button id="stardust-sidebar-toggle" class="rs-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="ph ph-list"></i> Chapters</button>
                <button id="reader-settings-toggle" class="rs-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="ph ph-gear"></i> Settings</button>
            </div>
        </div>

        <article class="story-content">
            <!-- Title Header -->
            <div style="text-align: center; margin-bottom: 3rem; padding-bottom: 2rem; border-bottom: 1px solid var(--rs-border);">
                <h1 style="margin-bottom: 1rem; font-weight: 700;"><?php echo htmlspecialchars($title); ?></h1>
                
                <?php if (!empty($frontmatter['date']) || !empty($frontmatter['start_time']) || !empty($frontmatter['pov']) || !empty($frontmatter['location'])): ?>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; opacity: 0.7; font-size: 0.85rem; font-weight: 600;">
                        <?php 
                        if (!empty($frontmatter['date'])): 
                            $isoString = '';
                            if (!empty($frontmatter['start_time'])) {
                                $tzMap = [
                                    'ET' => 'America/New_York', 'EST' => 'America/New_York', 'EDT' => 'America/New_York',
                                    'PT' => 'America/Los_Angeles', 'PST' => 'America/Los_Angeles', 'PDT' => 'America/Los_Angeles',
                                    'CT' => 'America/Chicago', 'CST' => 'America/Chicago', 'CDT' => 'America/Chicago',
                                    'MT' => 'America/Denver', 'MST' => 'America/Denver', 'MDT' => 'America/Denver'
                                ];
                                $tzStr = $tzMap[$frontmatter['timezone'] ?? 'ET'] ?? 'UTC';
                                try {
                                    $dt = new DateTime($frontmatter['date'] . ' ' . $frontmatter['start_time'], new DateTimeZone($tzStr));
                                    $isoString = $dt->format(DateTime::ATOM);
                                } catch (Exception $e) {}
                            }
                        ?>
                            <span id="story-datetime-display" style="display: flex; align-items: center; gap: 4px;" data-iso="<?php echo htmlspecialchars($isoString); ?>">
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
                                        const formatter = new Intl.DateTimeFormat(undefined, { 
                                            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', 
                                            hour: 'numeric', minute: '2-digit', timeZoneName: 'short' 
                                        });
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
</style>
