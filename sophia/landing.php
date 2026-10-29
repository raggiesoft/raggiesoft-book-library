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
 *     - Global Variables: Relies on `$toc_manifest` for configuration data, `$cdnBaseUrl` for image asset resolution,
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
global $toc_manifest, $cdnBaseUrl, $seriesSlug, $Parsedown, $ebookSizes, $routeData;

// Setup fallback values in case the JSON data is missing expected fields.
$seriesTitle = $toc_manifest['series_title'] ?? 'Book Series';
$seriesDescriptionLong = $toc_manifest['series_description_long'] ?? '';
$seriesDescription = $toc_manifest['series_description'] ?? 'No description available.';
$seriesImage = $toc_manifest['series_image'] ?? '';
$tocUrl = '/' . $seriesSlug . '/toc';
$landingSizes = !empty($ebookSizes) && is_array($ebookSizes)
    ? $ebookSizes
    : ($routeData['common']['ebook_sizes'] ?? ($toc_manifest['ebook_sizes'] ?? []));
$landingEpubSize     = $landingSizes['epub'] ?? '';
$landingPdfSize      = $landingSizes['pdf'] ?? '';
$landingTextEpubSize = $landingSizes['text_epub'] ?? '';
$landingTextPdfSize  = $landingSizes['text_pdf'] ?? '';
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
            
            <!-- Call to action buttons to view the TOC, Table of Illustrations, and download eBooks (Illustrated + Text-Only Editions) -->
            <?php
            $landingIllusCount = (int)($toc_manifest['illustration_count'] ?? ($routeData['common']['illustration_count'] ?? 0));
            $illustrationsUrl = '/' . $seriesSlug . '/illustrations';
            ?>
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <a href="<?php echo htmlspecialchars($tocUrl); ?>" class="rs-btn rs-btn-primary" style="font-size: 1.1rem; padding: 0.75rem 2rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-book-open"></i> View the Table of Contents
                </a>
                <?php if ($landingIllusCount > 0): ?>
                <a href="<?php echo htmlspecialchars($illustrationsUrl); ?>" class="rs-btn" style="font-size: 1.05rem; padding: 0.75rem 1.35rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-images"></i>
                    <span>Table of Illustrations <span style="opacity: 0.85; font-weight: 600; font-size: 0.9em;">(<?php echo $landingIllusCount; ?>)</span></span>
                </a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.epub"); ?>" target="_blank" class="rs-btn" title="Download Illustrated EPUB<?php echo $landingEpubSize ? ' (' . htmlspecialchars($landingEpubSize) . ')' : ''; ?>" style="font-size: 1.05rem; padding: 0.75rem 1.35rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-download-simple"></i>
                    <span>EPUB<?php echo $landingEpubSize ? ' <span style="opacity: 0.82; font-weight: 500; font-size: 0.9em;">(' . htmlspecialchars($landingEpubSize) . ')</span>' : ''; ?></span>
                </a>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.pdf"); ?>" target="_blank" class="rs-btn" title="Download Illustrated PDF<?php echo $landingPdfSize ? ' (' . htmlspecialchars($landingPdfSize) . ')' : ''; ?>" style="font-size: 1.05rem; padding: 0.75rem 1.35rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-file-pdf"></i>
                    <span>PDF<?php echo $landingPdfSize ? ' <span style="opacity: 0.82; font-weight: 500; font-size: 0.9em;">(' . htmlspecialchars($landingPdfSize) . ')</span>' : ''; ?></span>
                </a>
            </div>
            <div style="margin-top: 0.75rem; font-size: 0.88rem; opacity: 0.9; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <span><i class="ph ph-file-text"></i> Text-Only Edition (no images):</span>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.epub"); ?>" target="_blank" title="Download Text-Only EPUB (No Images<?php echo $landingTextEpubSize ? ' — ' . htmlspecialchars($landingTextEpubSize) : ''; ?>)" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">EPUB &mdash; Text Only<?php echo $landingTextEpubSize ? ' (' . htmlspecialchars($landingTextEpubSize) . ')' : ''; ?></a>
                <span aria-hidden="true">&bull;</span>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.pdf"); ?>" target="_blank" title="Download Text-Only PDF (No Images<?php echo $landingTextPdfSize ? ' — ' . htmlspecialchars($landingTextPdfSize) : ''; ?>)" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">PDF &mdash; Text Only<?php echo $landingTextPdfSize ? ' (' . htmlspecialchars($landingTextPdfSize) . ')' : ''; ?></a>
            </div>

            <?php if ($seriesSlug === 'stardust-engine' || $seriesSlug === 'erin'): ?>
            <!-- Companion Cosmic Portal & Listening Room Integration Box -->
            <div style="margin-top: 1.75rem; padding: 1.25rem 1.35rem; border-radius: 12px; background: var(--rs-surface); border: 1px solid var(--rs-border); border-left: 5px solid var(--rs-primary);">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-primary); margin-bottom: 0.35rem;">
                    <i class="ph-fill ph-planet"></i> Connected Stardust Engine Ecosystem
                </div>
                <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0 0 0.5rem 0; color: var(--rs-heading);">
                    Intermingle with <code>stardust.raggiesoft.com</code> &amp; The Listening Room
                </h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0 0 1rem 0; color: var(--rs-text); opacity: 0.92;">
                    This companion novel is synchronized 1:1 with the official cosmic portal of <strong>The Stardust Engine</strong> and all 21 studio, live, and archival albums in <strong>The Listening Room</strong>.
                </p>
                <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
                    <a href="https://stardust.raggiesoft.com" data-no-spa="true" target="_blank" rel="noopener noreferrer" class="rs-btn rs-btn-primary" style="font-size: 0.9rem; padding: 0.55rem 1rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph-fill ph-rocket-launch"></i> Official Band Portal (stardust.raggiesoft.com)
                    </a>
                    <a href="https://stardust.raggiesoft.com/novel" data-no-spa="true" target="_blank" rel="noopener noreferrer" class="rs-btn" style="font-size: 0.9rem; padding: 0.55rem 1rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph-fill ph-planet"></i> Read in Cosmic Exoplanet / Deep Space View
                    </a>
                    <a href="https://music.raggiesoft.com/artists/the-stardust-engine" data-no-spa="true" target="_blank" rel="noopener noreferrer" class="rs-btn" style="font-size: 0.9rem; padding: 0.55rem 1rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph-fill ph-headphones"></i> Stream Discography in The Listening Room
                    </a>
                    <?php if ($seriesSlug === 'stardust-engine'): ?>
                    <a href="/stardust-engine/book-6-reignition-live-at-the-crucible-2015-2016/chapter-1-live-at-the-crucible-may-21-2016" class="rs-btn" style="font-size: 0.9rem; padding: 0.55rem 1rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph-fill ph-guitar"></i> Jump to Book 6: Live at the Crucible (Ryan's POV)
                    </a>
                    <a href="/aubrie/spring-semester-2016/live-at-the-crucible-senior-commencement" class="rs-btn" style="font-size: 0.9rem; padding: 0.55rem 1rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph-fill ph-arrows-left-right"></i> Compare with Jordan's POV (Section 104, Row A)
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
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

        <p style="margin-top: 2rem; font-size: 0.8rem; color: var(--rs-text); opacity: 0.72; line-height: 1.5;">
            <strong>Work of Fiction Disclaimer:</strong> All characters, bands, universities (including CPI), corporations, and events in this series are works of fiction. Any resemblance to actual persons, living or dead, institutions, or events is purely coincidental.
        </p>
    </div>
</div>
