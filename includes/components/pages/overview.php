<?php
$narrativesPath = realpath($basePath . '/../raggiesoft-narratives/books');
$seriesPath = $narrativesPath . '/' . $seriesSlug;
$katieJsonPath = $seriesPath . '/katie.json';

if (!is_dir($seriesPath) || !file_exists($katieJsonPath)) {
    echo '<main id="stardust-reading-pane" tabindex="-1"><div class="book-page"><h1>Series Not Found</h1><p>Could not find the catalog for <code>' . htmlspecialchars($seriesSlug) . '</code>.</p></div></main>';
    return;
}

$katieData = json_decode(file_get_contents($katieJsonPath), true);
$seriesTitle = $katieData['series_title'] ?? ucfirst(str_replace('-', ' ', $seriesSlug));
?>
<main id="stardust-reading-pane" tabindex="-1">
    <div class="book-page overview-page">
        
        <header class="series-header text-center mb-5">
            <h1 class="display-4 fw-bold mb-2"><?php echo htmlspecialchars($seriesTitle); ?></h1>
            <p class="lead text-muted">Table of Contents</p>
        </header>

        <div class="series-catalog">
            <?php if (!empty($katieData['books'])): ?>
                <?php foreach ($katieData['books'] as $book): ?>
                    <div class="book-volume mb-5">
                        <h2 class="book-title mb-4 border-bottom pb-2">
                            <span class="text-primary">Book <?php echo htmlspecialchars($book['book_num']); ?>:</span> 
                            <?php echo htmlspecialchars($book['book_title']); ?>
                        </h2>
                        
                        <?php if (!empty($book['chapters'])): ?>
                            <div class="chapter-list">
                                <?php foreach ($book['chapters'] as $chapter): ?>
                                    <div class="chapter-group mb-4 ms-3">
                                        <h3 class="h5 text-secondary mb-3">Chapter <?php echo htmlspecialchars($chapter['chapter_num']); ?>: <?php echo htmlspecialchars($chapter['chapter_title']); ?></h3>
                                        
                                        <?php if (!empty($chapter['parts'])): ?>
                                            <ul class="list-unstyled ms-4">
                                                <?php foreach ($chapter['parts'] as $part): ?>
                                                    <?php 
                                                        // Construct URL: /seriesSlug/bXXX/cXXX/pXXX
                                                        $partUrl = '/' . $seriesSlug . '/' . $book['book_id'] . '/' . $chapter['chapter_id'] . '/' . $part['part_id'];
                                                    ?>
                                                    <li class="mb-2">
                                                        <a href="<?php echo htmlspecialchars($partUrl); ?>" class="text-decoration-none part-link">
                                                            <i class="ph ph-file-text me-2"></i>
                                                            <?php echo htmlspecialchars($part['part_title'] ?? 'Part ' . ltrim($part['part_id'], 'p')); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No books found in this series yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.overview-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 3rem 2rem;
}
.series-header h1 {
    font-family: 'Impact', sans-serif, system-ui;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--primary-color, #0f172a);
}
.book-title {
    font-size: 2rem;
    font-weight: 700;
}
.part-link {
    color: var(--text-color, #334155);
    transition: color 0.2s ease, padding-left 0.2s ease;
    display: inline-block;
}
.part-link:hover {
    color: var(--primary-color, #2563eb);
    padding-left: 5px;
}
</style>
