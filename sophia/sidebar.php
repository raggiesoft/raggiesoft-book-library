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
    <a href="https://raggiesoft.com/raggiesoft-books/books" class="rs-btn rs-btn-brand" style="display: block; text-align: center; margin-bottom: 1.5rem; text-decoration: none;"><i class="ph ph-books"></i> Back to Library</a>
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

<!-- Mobile Backdrop -->
<div id="reader-sidebar-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px);"></div>

<style>
.reader-sidebar {
    padding: 1.5rem;
    overflow-y: auto;
    background: var(--rs-bg-alt, #fafafa);
    transition: transform 0.3s ease;
}

@media (max-width: 991px) {
    .reader-sidebar {
        position: fixed !important;
        top: 0;
        left: 0;
        width: 300px;
        height: 100vh;
        z-index: 1000;
        transform: translateX(-100%);
        box-shadow: 5px 0 15px rgba(0,0,0,0.1);
    }
    .reader-sidebar.open {
        transform: translateX(0);
    }
}

@media (min-width: 992px) {
    #stardust-sidebar.reader-sidebar {
        position: static !important;
        transform: none !important;
        width: 350px !important;
        height: 100vh !important;
        z-index: 1 !important;
        box-shadow: none !important;
        flex-shrink: 0;
        border-right: 1px solid var(--rs-border);
    }
    #stardust-sidebar-toggle {
        display: none !important;
    }
    #reader-sidebar-backdrop {
        display: none !important;
    }
}
.toc-nav a:hover {
    opacity: 1 !important;
    color: var(--rs-primary);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('stardust-sidebar');
    const toggleBtn = document.getElementById('stardust-sidebar-toggle');
    const backdrop = document.getElementById('reader-sidebar-backdrop');

    function toggleSidebar() {
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            backdrop.style.display = 'none';
        } else {
            sidebar.classList.add('open');
            backdrop.style.display = 'block';
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    
    if (backdrop) {
        backdrop.addEventListener('click', toggleSidebar);
    }
});
</script>
