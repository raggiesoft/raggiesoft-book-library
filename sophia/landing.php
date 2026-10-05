<?php
global $katie, $cdnBaseUrl, $seriesSlug;
$seriesTitle = $katie['series_title'] ?? 'Book Series';
$seriesDescription = $katie['series_description'] ?? 'No description available.';
$seriesImage = $katie['series_image'] ?? '';
$tocUrl = '/' . $seriesSlug . '/toc';
?>
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="font-size: 3rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($seriesTitle); ?></h1>
    </div>
    
    <div style="display: flex; flex-direction: column; align-items: center; gap: 2rem; margin-bottom: 3rem;">
        <?php if (!empty($seriesImage)): ?>
            <img src="<?php echo htmlspecialchars($cdnBaseUrl . $seriesImage); ?>" alt="Cover for <?php echo htmlspecialchars($seriesTitle); ?>" style="max-width: 300px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);" />
        <?php endif; ?>
        
        <div style="text-align: left; max-width: 600px;">
            <h2 style="border-bottom: 1px solid var(--rs-border); padding-bottom: 0.5rem; margin-bottom: 1rem; color: var(--rs-primary);">About this Series</h2>
            <p style="font-size: 1.2rem; line-height: 1.8; margin-bottom: 2rem;"><?php echo nl2br(htmlspecialchars($seriesDescription)); ?></p>
            
            <div style="text-align: center;">
                <a href="<?php echo htmlspecialchars($tocUrl); ?>" class="rs-btn rs-btn-primary" style="font-size: 1.2rem; padding: 0.75rem 2rem;">View the Table of Contents</a>
            </div>
        </div>
    </div>
</div>
