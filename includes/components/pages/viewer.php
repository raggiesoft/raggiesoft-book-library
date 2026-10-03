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
    echo '<main id="stardust-reading-pane" tabindex="-1"><div class="book-page"><h1>Chapter Not Found</h1><p>The requested route could not be found in the route map.</p></div></main>';
    return;
}

// 1. Fetch Markdown Content
$mdPath = $basePath . '/../raggiesoft-narratives/books/' . $seriesSlug . '/' . $actualFilePath;
if (!file_exists($mdPath)) {
    echo '<main id="stardust-reading-pane" tabindex="-1"><div class="book-page"><h1>File Not Found</h1><p>The physical file could not be located.</p></div></main>';
    return;
}
$mdContent = file_get_contents($mdPath);

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

<main id="stardust-reading-pane" tabindex="-1">
    <div class="book-page reader-page">
        <!-- Breadcrumbs & Nav -->
        <div class="reader-nav mb-4">
            <a href="/catalog" class="text-muted text-decoration-none">Catalog</a> &raquo; 
            <a href="<?php echo htmlspecialchars($overviewUrl); ?>" class="text-muted text-decoration-none"><?php echo htmlspecialchars(ucfirst($seriesSlug)); ?></a> &raquo; 
            <strong><?php echo htmlspecialchars($title); ?></strong>
        </div>

        <article class="story-content">
            <!-- Title Header -->
            <div class="text-center mb-5 pb-3 border-bottom">
                <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($title); ?></h1>
                
                <?php if (!empty($frontmatter['date']) || !empty($frontmatter['start_time']) || !empty($frontmatter['pov']) || !empty($frontmatter['location'])): ?>
                    <div class="metadata d-flex flex-wrap justify-content-center gap-3 text-muted small fw-semibold">
                        <?php if (!empty($frontmatter['date'])): ?>
                            <span><i class="ph ph-calendar-blank"></i> <?php echo htmlspecialchars($frontmatter['date']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['start_time'])): ?>
                            <span><i class="ph ph-clock"></i> <?php echo htmlspecialchars($frontmatter['start_time']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['location'])): ?>
                            <span><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($frontmatter['location']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($frontmatter['pov'])): ?>
                            <span><i class="ph ph-eye"></i> POV: <?php echo htmlspecialchars($frontmatter['pov']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Parsedown Content -->
            <?php echo $htmlContent; ?>
        </article>
        
        <!-- Bottom Navigation -->
        <div class="d-flex justify-content-between mt-5 pt-4 border-top">
            <?php if ($prevUrl): ?>
                <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="btn btn-outline-secondary">&larr; Previous</a>
            <?php else: ?>
                <button class="btn btn-outline-secondary" disabled>&larr; Previous</button>
            <?php endif; ?>

            <a href="<?php echo htmlspecialchars($overviewUrl); ?>" class="btn btn-link text-muted">Index</a>

            <?php if ($nextUrl): ?>
                <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="btn btn-primary">Next &rarr;</a>
            <?php else: ?>
                <button class="btn btn-primary" disabled>Next &rarr;</button>
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
    color: var(--text-color, #111);
}
.story-content p {
    margin-bottom: 1.5rem;
    text-indent: 2rem; /* Traditional book indentation */
}
</style>
