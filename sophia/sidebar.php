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

// $katie is already loaded in isabel.php for SEO purposes
?>

<aside id="stardust-sidebar" class="reader-sidebar">
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
        <a href="https://raggiesoft.com/raggiesoft-books/books" class="rs-btn rs-btn-brand" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-books"></i> Library</a>
        <button id="reader-settings-toggle" class="rs-btn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;"><i class="ph ph-gear"></i> Settings</button>
    </div>
    <h3 style="margin-top: 1rem; color: var(--rs-primary);"><?php 
        $sidebarSeriesTitle = $katie['series_title'] ?? (ucfirst($seriesSlug) . ' Narrative');
        echo htmlspecialchars($sidebarSeriesTitle); 
    ?></h3>
    <p style="opacity: 0.7; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Table of Contents</p>
    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 20px 0;">
    
    <nav class="toc-nav">
        <?php if ($katie && !empty($katie['books'])): ?>
            <?php foreach ($katie['books'] as $book): ?>
                <?php 
                $bookHasActive = false;
                foreach ($book['chapters'] ?? [] as $chapter) {
                    foreach ($chapter['parts'] ?? [] as $part) {
                        if (($fileToUrl[$part['file_path']] ?? '#') === $requestUri) {
                            $bookHasActive = true;
                            break 2;
                        }
                    }
                }
                $bookOpenAttr = $bookHasActive ? 'open' : '';
                ?>
                <details class="toc-book" style="margin-bottom: 1.5rem;" <?php echo $bookOpenAttr; ?>>
                    <summary style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--rs-primary); cursor: pointer; font-weight: bold; list-style-position: inside; user-select: none;">
                        <?php echo htmlspecialchars($book['book_title']); ?>
                    </summary>
                    
                    <?php if (!empty($book['chapters'])): ?>
                        <?php foreach ($book['chapters'] as $chapter): ?>
                            <?php 
                            $chapterHasActive = false;
                            foreach ($chapter['parts'] ?? [] as $part) {
                                if (($fileToUrl[$part['file_path']] ?? '#') === $requestUri) {
                                    $chapterHasActive = true;
                                    break;
                                }
                            }
                            $chapOpenAttr = $chapterHasActive ? 'open' : '';
                            ?>
                            <details class="toc-chapter" style="margin-bottom: 1rem; padding-left: 1rem;" <?php echo $chapOpenAttr; ?>>
                                <summary style="font-size: 0.9rem; margin-bottom: 0.25rem; opacity: 0.9; cursor: pointer; list-style-position: inside; user-select: none;">
                                    <?php echo htmlspecialchars($chapter['chap_title']); ?>
                                </summary>
                                
                                <?php if (!empty($chapter['parts'])): ?>
                                    <ul style="list-style: none; padding-left: 1.25rem; margin: 0.5rem 0 0 0; font-size: 0.85rem;">
                                        <?php foreach ($chapter['parts'] as $part): ?>
                                            <?php 
                                            $href = $fileToUrl[$part['file_path']] ?? '#'; 
                                            $isActive = ($href === $requestUri) ? 'color: var(--rs-primary); font-weight: bold;' : 'opacity: 0.7;';
                                            $activeClass = ($href === $requestUri) ? 'active-toc-link' : '';
                                            ?>
                                            <li style="margin-bottom: 0.25rem;">
                                                <a href="<?php echo htmlspecialchars($href); ?>" class="<?php echo $activeClass; ?>" style="text-decoration: none; <?php echo $isActive; ?> display: block; padding: 2px 0;">
                                                    <?php echo htmlspecialchars($part['part_title']); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </details>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No table of contents available.</p>
        <?php endif; ?>
    </nav>
</aside>

<!-- Mobile Backdrop -->
<div id="reader-sidebar-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px);"></div>




