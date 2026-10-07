<?php
global $katie, $cdnBaseUrl, $seriesSlug, $Parsedown;
$seriesTitle = $katie['series_title'] ?? 'Book Series';
$seriesDescriptionLong = $katie['series_description_long'] ?? '';
$seriesDescription = $katie['series_description'] ?? 'No description available.';
$seriesImage = $katie['series_image'] ?? '';
$tocUrl = '/' . $seriesSlug . '/toc';
?>
<style>
.landing-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    align-items: start;
    margin-bottom: 3rem;
}
@media (min-width: 768px) {
    .landing-grid {
        grid-template-columns: 300px 1fr;
        gap: 3rem;
    }
}
.landing-cover {
    width: 100%;
    max-width: 300px;
    margin: 0 auto;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    aspect-ratio: 2 / 3;
    object-fit: cover;
}
</style>

<div class="book-page" style="max-width: 900px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 3.5rem; margin-bottom: 1rem; color: var(--rs-text);"><?php echo htmlspecialchars($seriesTitle); ?></h1>
    </div>
    
    <div class="landing-grid">
        <div style="text-align: center;">
            <?php if (!empty($seriesImage)): ?>
                <img src="<?php echo htmlspecialchars($cdnBaseUrl . $seriesImage); ?>" alt="Cover for <?php echo htmlspecialchars($seriesTitle); ?>" class="landing-cover" />
            <?php else: ?>
                <div class="landing-cover" style="background: var(--rs-surface); display: flex; align-items: center; justify-content: center; border: 1px solid var(--rs-border);">
                    <i class="ph ph-book" style="font-size: 5rem; opacity: 0.2;"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: left;">
            <h2 style="border-bottom: 1px solid var(--rs-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem; color: var(--rs-primary);">About this Series</h2>
            <div style="font-size: 1.2rem; line-height: 1.8; margin-bottom: 2.5rem; opacity: 0.9;">
                <?php 
                if (!empty($seriesDescriptionLong) && isset($Parsedown)) {
                    $descContent = $seriesDescriptionLong;
                    if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $descContent, $matches)) {
                        $descContent = substr($descContent, strlen($matches[0]));
                    }
                    echo $Parsedown->text($descContent);
                } else {
                    echo '<p>' . nl2br(htmlspecialchars($seriesDescription)) . '</p>';
                }
                ?>
            </div>
            
            <div>
                <a href="<?php echo htmlspecialchars($tocUrl); ?>" class="rs-btn rs-btn-primary" style="font-size: 1.1rem; padding: 0.75rem 2rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-book-open"></i> View the Table of Contents
                </a>
            </div>
        </div>
    </div>

<?php 
$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
$encodedUrl = urlencode($currentUrl);
$encodedText = urlencode("Check out " . $seriesTitle . " by Michael Ragsdale!");
$ogImageFile = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
?>
    <!-- Share Section -->
    <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--rs-border); text-align: center;">
        <h3 style="margin-bottom: 1.5rem; color: var(--rs-text);">Share this Book</h3>
        
        <img src="<?php echo htmlspecialchars($ogImageFile); ?>" alt="Share Preview" style="width: 100%; max-width: 600px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); margin-bottom: 2rem; border: 1px solid var(--rs-border);" onerror="this.style.display='none'">
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-facebook-logo"></i> Facebook
            </a>
            
            <a href="https://bsky.app/intent/compose?text=<?php echo $encodedText . '%20' . $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-cloud-sun"></i> Bluesky
            </a>

            <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($currentUrl); ?>'); alert('Link copied to clipboard!');" class="rs-btn rs-btn-primary">
                <i class="ph ph-link"></i> Copy Link
            </button>
        </div>
    </div>
</div>
