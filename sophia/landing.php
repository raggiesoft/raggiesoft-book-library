<?php
/**
 * Architectural Block Comment:
 * File: landing.php
 * Purpose:
 *     This script renders the main landing page for an entire book series.
 *     It displays the series cover art, parses and outputs a long-form markdown description, 
 *     and provides social media sharing links.
 * 
 * Design Decisions & Future Maintenance:
 *     - Global Variables: Relies on `$katie` for configuration data, `$cdnBaseUrl` for image asset resolution,
 *       `$seriesSlug` for URL generation, and `$Parsedown` for converting the long markdown description into HTML.
 *     - Markdown Parsing: Includes logic to strip YAML frontmatter (`--- ... ---`) from the top of the description file
 *       before passing it to Parsedown to prevent raw metadata from bleeding into the UI.
 *     - Responsive Grid: Utilizes CSS Grid (`.landing-grid`) to shift the layout from stacked on mobile to a two-column setup on desktop.
 *     - Social Sharing: Generates platform-specific intent URLs (Facebook, Bluesky) and implements a clipboard copy button.
 *       Open Graph (`og:image`) assets are hardcoded to follow a specific naming convention based on `$seriesSlug`.
 *     - Security/Sanitization: `htmlspecialchars` is applied dynamically to all variables rendered inside HTML tags to prevent XSS. 
 *       Note that `$Parsedown->text()` is assumed to be safe or is handling its own sanitization for the description body.
 */

// Import requisite dependencies and metadata.
global $katie, $cdnBaseUrl, $seriesSlug, $Parsedown;

// Setup fallback values in case the JSON data is missing expected fields.
$seriesTitle = $katie['series_title'] ?? 'Book Series';
$seriesDescriptionLong = $katie['series_description_long'] ?? '';
$seriesDescription = $katie['series_description'] ?? 'No description available.';
$seriesImage = $katie['series_image'] ?? '';
$tocUrl = '/' . $seriesSlug . '/toc';
?>
<style>
/* CSS Grid layout for the landing presentation */
.landing-grid {
    display: grid;
    grid-template-columns: 1fr; /* Single column stack for mobile devices */
    gap: 2rem;
    align-items: start;
    margin-bottom: 3rem;
}
/* Breakpoint for tablet/desktop views to expand to a two-column layout */
@media (min-width: 768px) {
    .landing-grid {
        grid-template-columns: 300px 1fr; /* Fixed cover width, flowing text */
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
    <!-- Landing Header -->
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 3.5rem; margin-bottom: 1rem; color: var(--rs-text);"><?php echo htmlspecialchars($seriesTitle); ?></h1>
    </div>
    
    <div class="landing-grid">
        <!-- Cover Art Column -->
        <div style="text-align: center;">
            <?php if (!empty($seriesImage)): ?>
                <img src="<?php echo htmlspecialchars($cdnBaseUrl . $seriesImage); ?>" alt="Cover for <?php echo htmlspecialchars($seriesTitle); ?>" class="landing-cover" />
            <?php else: ?>
                <!-- Fallback placeholder if no cover image is defined -->
                <div class="landing-cover" style="background: var(--rs-surface); display: flex; align-items: center; justify-content: center; border: 1px solid var(--rs-border);">
                    <i class="ph ph-book" style="font-size: 5rem; opacity: 0.2;"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Synopsis and Interaction Column -->
        <div style="text-align: left;">
            <h2 style="border-bottom: 1px solid var(--rs-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem; color: var(--rs-primary);">About this Series</h2>
            <div style="font-size: 1.2rem; line-height: 1.8; margin-bottom: 2.5rem; opacity: 0.9;">
                <?php 
                // Determine whether to render the long markdown description or the short text fallback.
                if (!empty($seriesDescriptionLong) && isset($Parsedown)) {
                    $descContent = $seriesDescriptionLong;
                    
                    // Regex routine to detect and strip YAML frontmatter block from the start of the markdown string.
                    if (preg_match('/^---\s*[\r\n]+(.*?)[\r\n]+---\s*[\r\n]+/s', $descContent, $matches)) {
                        // Slice off the frontmatter portion based on the matched length.
                        $descContent = substr($descContent, strlen($matches[0]));
                    }
                    // Parse the resulting markdown into HTML for display.
                    echo $Parsedown->text($descContent);
                } else {
                    // Fallback to the short description, formatting line breaks into `<br>` tags.
                    echo '<p>' . nl2br(htmlspecialchars($seriesDescription)) . '</p>';
                }
                ?>
            </div>
            
            <!-- Call to action button to view the TOC -->
            <div>
                <a href="<?php echo htmlspecialchars($tocUrl); ?>" class="rs-btn rs-btn-primary" style="font-size: 1.1rem; padding: 0.75rem 2rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-book-open"></i> View the Table of Contents
                </a>
            </div>
        </div>
    </div>

<?php 
// Prepare URLs and Text strings for the Social Share functionalities.
// Note: $_SERVER['HTTP_HOST'] can sometimes be manipulated, so it should ideally be sanitized or overridden by a config setting in production.
$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
$encodedUrl = urlencode($currentUrl);
$encodedText = urlencode("Check out " . $seriesTitle . " by Michael Ragsdale!");
$ogImageFile = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
?>
    <!-- Share Section -->
    <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--rs-border); text-align: center;">
        <h3 style="margin-bottom: 1.5rem; color: var(--rs-text);">Share this Book</h3>
        
        <!-- OpenGraph Image Preview. Hides itself if the image fails to load. -->
        <img src="<?php echo htmlspecialchars($ogImageFile); ?>" alt="Share Preview" style="width: 100%; max-width: 600px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); margin-bottom: 2rem; border: 1px solid var(--rs-border);" onerror="this.style.display='none'">
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <!-- Facebook Share Intent -->
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-facebook-logo"></i> Facebook
            </a>
            
            <!-- Bluesky Compose Intent -->
            <a href="https://bsky.app/intent/compose?text=<?php echo $encodedText . '%20' . $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-cloud-sun"></i> Bluesky
            </a>

            <!-- Native Clipboard API Copy capability -->
            <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($currentUrl); ?>'); alert('Link copied to clipboard!');" class="rs-btn rs-btn-primary">
                <i class="ph ph-link"></i> Copy Link
            </button>
        </div>
    </div>
</div>
