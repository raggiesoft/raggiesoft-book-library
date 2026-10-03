<?php
// Sophia's Table of Contents Sidebar
global $seriesSlug, $routeData, $cdnBaseUrl, $requestUri;

// Build reverse lookup from filePath to URL
$fileToUrl = [];
if (!empty($routeData)) {
    foreach ($routeData as $url => $data) {
        if ($url === 'common') continue;
        if (isset($data['filePath'])) {
            $fileToUrl[$data['filePath']] = $url;
        }
    }
}

$katieUrl = $cdnBaseUrl . '/raggiesoft-books/books/' . $seriesSlug . '/katie.json';
$katieContent = @file_get_contents($katieUrl);
$katie = $katieContent ? json_decode($katieContent, true) : null;
?>

<aside id="stardust-sidebar" style="overflow-y: auto; height: 100vh;">
    <h3 style="margin-top: 1rem;"><?php echo htmlspecialchars($katie['title'] ?? 'Table of Contents'); ?></h3>
    <p style="opacity: 0.7; font-size: 0.85rem;">Table of Contents</p>
    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 20px 0;">
    
    <nav class="toc-nav">
        <?php if ($katie && !empty($katie['books'])): ?>
            <?php foreach ($katie['books'] as $book): ?>
                <div class="toc-book" style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--rs-primary);">
                        <?php echo htmlspecialchars($book['book_title']); ?>
                    </h4>
                    
                    <?php if (!empty($book['chapters'])): ?>
                        <?php foreach ($book['chapters'] as $chapter): ?>
                            <div class="toc-chapter" style="margin-bottom: 1rem; padding-left: 1rem;">
                                <h5 style="font-size: 0.9rem; margin-bottom: 0.25rem; opacity: 0.9;">
                                    <?php echo htmlspecialchars($chapter['chap_title']); ?>
                                </h5>
                                
                                <?php if (!empty($chapter['parts'])): ?>
                                    <ul style="list-style: none; padding-left: 1rem; margin: 0; font-size: 0.85rem;">
                                        <?php foreach ($chapter['parts'] as $part): ?>
                                            <?php 
                                            $href = $fileToUrl[$part['file_path']] ?? '#'; 
                                            $isActive = ($href === $requestUri) ? 'color: var(--rs-primary); font-weight: bold;' : 'opacity: 0.7;';
                                            ?>
                                            <li style="margin-bottom: 0.25rem;">
                                                <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; <?php echo $isActive; ?> display: block; padding: 2px 0;">
                                                    <?php echo htmlspecialchars($part['part_title']); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No table of contents available.</p>
        <?php endif; ?>
    </nav>
</aside>

<style>
.toc-nav a:hover {
    opacity: 1 !important;
    color: var(--rs-primary);
}
</style>
