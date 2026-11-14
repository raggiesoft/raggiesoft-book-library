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
global $toc_manifest, $cdnBaseUrl, $seriesSlug, $Parsedown, $ebookSizes, $routeData, $fileToUrl;

// Setup fallback values in case the JSON data is missing expected fields.
$seriesTitle = $toc_manifest['series_title'] ?? 'Book Series';
$seriesDescriptionLong = $toc_manifest['series_description_long'] ?? '';
$seriesDescription = $toc_manifest['series_description'] ?? 'No description available.';
$seriesImage = $toc_manifest['series_image'] ?? '';
$tocUrl = '/' . $seriesSlug . '/toc';

// Resolve the canonical opening scene URL so readers and search crawlers can enter Chapter 1 directly in 1 click.
$firstPartUrl = '';
if (!empty($toc_manifest['eras'][0]['chapters'][0]['parts'][0])) {
    $openingPart = $toc_manifest['eras'][0]['chapters'][0]['parts'][0];
    $openingFilePath = (string)($openingPart['file_path'] ?? '');
    if ($openingFilePath !== '' && !empty($fileToUrl[$openingFilePath])) {
        $firstPartUrl = (string)$fileToUrl[$openingFilePath];
    } elseif (!empty($openingPart['url'])) {
        $firstPartUrl = (string)preg_replace('#/part-1-[^/]+$#', '', str_replace('/raggiesoft-books/books', '', (string)$openingPart['url']));
    }
}
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
                <img src="<?php echo htmlspecialchars($cdnBaseUrl . $seriesImage); ?>" alt="Cover for <?php echo htmlspecialchars($seriesTitle); ?>" class="landing-cover" width="600" height="900" fetchpriority="high" decoding="async" />
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
            $landingWordStats = $toc_manifest['word_stats'] ?? ($routeData['common']['word_stats'] ?? []);
            $landingStoryWords = (int)($landingWordStats['story_word_count'] ?? 0);
            $landingLoreWords = (int)($landingWordStats['lore_word_count'] ?? 0);
            $landingCombinedWords = (int)($landingWordStats['combined_word_count'] ?? ($landingStoryWords + $landingLoreWords));
            $landingStoryPages = (int)($landingWordStats['story_page_count'] ?? ($landingWordStats['estimated_story_pages'] ?? ($landingStoryWords > 0 ? ceil($landingStoryWords / 275) : 0)));
            $landingTotalPages = (int)($landingWordStats['combined_page_count'] ?? ($landingWordStats['estimated_total_pages'] ?? ($landingCombinedWords > 0 ? ceil($landingCombinedWords / 275) : 0)));
            $landingReadingHours = (float)($landingWordStats['reading_hours'] ?? ($landingWordStats['reading_hours_story'] ?? ($landingStoryWords > 0 ? round($landingStoryWords / (250 * 60), 1) : 0)));
            $landingScaleLabel = (string)($landingWordStats['scale_category'] ?? ($landingWordStats['scale_label'] ?? ($landingWordStats['scale_badge'] ?? '')));
            $landingScaleBenchmark = (string)($landingWordStats['benchmark_note'] ?? ($landingWordStats['scale_benchmark'] ?? ''));
            ?>
            <?php if ($landingCombinedWords > 0): ?>
            <!-- Dynamic Manuscript & Lore Scale Telemetry Card (Compiled by paige.php) -->
            <div style="margin-bottom: 1.75rem; padding: 1.15rem 1.3rem; border-radius: 12px; background: var(--rs-surface); border: 1px solid var(--rs-border); border-left: 4px solid var(--rs-primary);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.74rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="ph-fill ph-chart-bar"></i>
                        <span>Live Manuscript &amp; Lore Scale</span>
                    </div>
                    <?php if ($landingScaleLabel !== ''): ?>
                    <span style="font-size: 0.74rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px; background: rgba(227, 178, 124, 0.16); color: var(--rs-primary);">
                        <?php echo htmlspecialchars($landingScaleLabel); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(135px, 1fr)); gap: 0.85rem; margin-bottom: 0.7rem;">
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.72;">Story Prose</div>
                        <div style="font-size: 1.2rem; font-weight: 800; color: var(--rs-heading); font-variant-numeric: tabular-nums;"><?php echo number_format($landingStoryWords); ?> <span style="font-size: 0.75rem; font-weight: 600; opacity: 0.75;">words</span></div>
                        <div style="font-size: 0.75rem; opacity: 0.75;">~<?php echo number_format($landingStoryPages); ?> print pages &bull; ~<?php echo number_format($landingReadingHours, 1); ?> hrs</div>
                    </div>
                    <?php if ($landingLoreWords > 0): ?>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.72;">Curated Lore &amp; Liner Notes</div>
                        <div style="font-size: 1.2rem; font-weight: 800; color: var(--rs-heading); font-variant-numeric: tabular-nums;">+<?php echo number_format($landingLoreWords); ?> <span style="font-size: 0.75rem; font-weight: 600; opacity: 0.75;">words</span></div>
                        <div style="font-size: 0.75rem; opacity: 0.75;">Archives, dossiers &amp; liner notes</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.72;">Combined Volume Total</div>
                        <div style="font-size: 1.2rem; font-weight: 800; color: var(--rs-primary); font-variant-numeric: tabular-nums;"><?php echo number_format($landingCombinedWords); ?> <span style="font-size: 0.75rem; font-weight: 600; opacity: 0.85;">words</span></div>
                        <div style="font-size: 0.75rem; opacity: 0.75;">~<?php echo number_format($landingTotalPages); ?> combined pages</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; font-size: 0.78rem; padding-top: 0.55rem; border-top: 1px dashed var(--rs-border); opacity: 0.88;">
                    <span><?php echo htmlspecialchars($landingScaleBenchmark); ?></span>
                    <a href="/stats" style="color: var(--rs-primary); font-weight: 700; text-decoration: none; white-space: nowrap;">Compare Across All 30 Volumes &rarr;</a>
                </div>
            </div>
            <?php endif; ?>
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <?php if ($firstPartUrl !== ''): ?>
                <a href="<?php echo htmlspecialchars($firstPartUrl); ?>" class="rs-btn rs-btn-primary" style="font-size: 1.1rem; padding: 0.75rem 1.85rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph-fill ph-play-circle" aria-hidden="true"></i> Start Reading
                </a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($tocUrl); ?>" class="rs-btn<?php echo $firstPartUrl === '' ? ' rs-btn-primary' : ''; ?>" style="font-size: 1.05rem; padding: 0.75rem 1.6rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-book-open" aria-hidden="true"></i> View the Table of Contents
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

            <?php
            /**
             * Bidirectional Companion Music Discovery Map:
             * Connects every music-linked Ocean View Archives volume directly to its canonical
             * artist, album, and flagship song pages in The Listening Room (`music.raggiesoft.com`)
             * so readers and search crawlers can move cleanly between narrative scenes and recordings.
             */
            $companionMusicCards = [
                'stardust-engine' => [
                    'eyebrow' => 'Connected Stardust Engine Ecosystem',
                    'icon'    => 'ph-planet',
                    'heading' => 'Explore The Stardust Engine & The Listening Room',
                    'copy'    => 'This companion novel pairs with the official band portal of The Stardust Engine and all 21 studio, live, and archival albums in The Listening Room.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine', 'label' => 'Stream Discography in The Listening Room', 'icon' => 'ph-headphones', 'external' => true, 'primary' => true],
                        ['href' => 'https://stardust.raggiesoft.com', 'label' => 'Official Band Portal (stardust.raggiesoft.com)', 'icon' => 'ph-rocket-launch', 'external' => true],
                        ['href' => 'https://stardust.raggiesoft.com/novel', 'label' => 'Read in Cosmic Exoplanet View', 'icon' => 'ph-planet', 'external' => true],
                        ['href' => '/stardust-engine/book-6-reignition-live-at-the-crucible-2015-2016/chapter-1-live-at-the-crucible-may-21-2016', 'label' => 'Book 6: Live at the Crucible (Ryan\'s POV)', 'icon' => 'ph-guitar'],
                        ['href' => '/aubrie/spring-semester-2016/live-at-the-crucible-senior-commencement', 'label' => 'Compare with Jordan\'s POV (Section 104, Row A)', 'icon' => 'ph-arrows-left-right'],
                    ],
                ],
                'erin' => [
                    'eyebrow' => 'Connected Music in The Listening Room',
                    'icon'    => 'ph-headphones',
                    'heading' => 'Listen to the Companion Stardust Engine Albums',
                    'copy'    => 'Hear the studio and archival recordings tied directly to John and Erin\'s story in The Listening Room.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine/2004-progress-street', 'label' => 'Progress Street (2004)', 'icon' => 'ph-vinyl-record', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine/2007-lost-sounds', 'label' => 'Lost Sounds (2007)', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine', 'label' => 'All Stardust Engine Releases', 'icon' => 'ph-headphones', 'external' => true],
                    ],
                ],
                'ad-astra' => [
                    'eyebrow' => 'Connected Cosmic Soundtrack',
                    'icon'    => 'ph-planet',
                    'heading' => 'Listen to the Ad Astra Recordings in The Listening Room',
                    'copy'    => 'Explore the deep-space concept recordings by The Stardust Engine alongside the interactive cosmic visualizer.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine/1996-ad-astra-single', 'label' => 'Escape Velocity (Ad Astra)', 'icon' => 'ph-rocket-launch', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine/2003-sanctuary-zero-g', 'label' => 'Sanctuary (Zero-G)', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine/2011-starfield-calibration', 'label' => 'Starfield Calibration', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://nebulae.raggiesoft.com', 'label' => 'Cosmic Visualizer (nebulae.raggiesoft.com)', 'icon' => 'ph-sparkle', 'external' => true],
                    ],
                ],
                'fractured-prisms' => [
                    'eyebrow' => 'Connected Discography in The Listening Room',
                    'icon'    => 'ph-headphones',
                    'heading' => 'Hear Fractured Prisms in The Listening Room',
                    'copy'    => 'Listen to the studio and stadium recordings performed across the chapters of Fractured Prisms, complete with full lyrics and liner notes.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/fractured-prisms', 'label' => 'Fractured Prisms Artist Catalog', 'icon' => 'ph-headphones', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/fractured-prisms/1983-carnaby-street', 'label' => 'Carnaby Street (1983)', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/fractured-prisms/1983-carnaby-street/the-hollow-square', 'label' => 'The Hollow Square — Lyrics & Preview', 'icon' => 'ph-music-notes', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/fractured-prisms/1984-1883', 'label' => '1883 (1984)', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/fractured-prisms/1985-live-at-independence-stadium', 'label' => 'Live at Independence Stadium (1985)', 'icon' => 'ph-microphone-stage', 'external' => true],
                    ],
                ],
                'aubrie' => [
                    'eyebrow' => 'Connected Music in The Listening Room',
                    'icon'    => 'ph-headphones',
                    'heading' => 'Hear the Companion Albums for The Eleventh Child',
                    'copy'    => 'Stream the two-volume concept albums by The Paper Wall and the May 2016 commencement concert at The Crucible.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/the-paper-wall/2003-eleventh-child-book-1', 'label' => 'The Eleventh Child, Book 1: System.Crash', 'icon' => 'ph-vinyl-record', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/the-paper-wall/2003-eleventh-child-book-2', 'label' => 'The Eleventh Child, Book 2: System.Restore', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/the-stardust-engine/2016-live-at-the-crucible', 'label' => 'Live at the Crucible (2016)', 'icon' => 'ph-microphone-stage', 'external' => true],
                    ],
                ],
                'luna-leo' => [
                    'eyebrow' => 'Connected Music in The Listening Room',
                    'icon'    => 'ph-headphones',
                    'heading' => 'Hear The Paper Wall Companion Trilogy',
                    'copy'    => 'Explore the three companion albums recorded by The Paper Wall alongside Luna and Leo\'s cross-country journey.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/the-paper-wall', 'label' => 'The Paper Wall Artist Catalog', 'icon' => 'ph-headphones', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/the-paper-wall/2000-guiding-stars', 'label' => 'Guiding Stars (2000)', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/the-paper-wall/2001-static-and-silence-book-1', 'label' => 'Static & Silence, Book 1', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/the-paper-wall/2001-static-and-silence-book-2', 'label' => 'Static & Silence, Book 2', 'icon' => 'ph-vinyl-record', 'external' => true],
                    ],
                ],
                'crimson-node' => [
                    'eyebrow' => 'Connected Music in The Listening Room',
                    'icon'    => 'ph-headphones',
                    'heading' => 'Stream the Crimson Node Companion Album',
                    'copy'    => 'Listen to the 15-track companion album with full lyrics and story notes in The Listening Room.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/crimson-node/2002-crimson-node', 'label' => 'Crimson Node (2002) — Full Album', 'icon' => 'ph-vinyl-record', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/crimson-node', 'label' => 'Crimson Node Artist Page', 'icon' => 'ph-headphones', 'external' => true],
                    ],
                ],
                'julian' => [
                    'eyebrow' => 'Connected Symphonic Recordings',
                    'icon'    => 'ph-music-notes',
                    'heading' => 'Hear The Arctic Symphony Project in The Listening Room',
                    'copy'    => 'Listen to the orchestral winter suites connected to Julian Rowlands\' story.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/the-arctic-symphony-project', 'label' => 'The Arctic Symphony Project', 'icon' => 'ph-headphones', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/the-arctic-symphony-project/1996-a-symphony-of-snow/homeward-on-this-day', 'label' => 'Homeward on This Day (1996)', 'icon' => 'ph-music-notes', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/the-arctic-symphony-project/2007-tales-from-the-solstice/upon-this-winters-night', 'label' => 'Upon This Winter\'s Night (2007)', 'icon' => 'ph-music-notes', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/the-arctic-symphony-project/2007-tales-from-the-solstice', 'label' => 'Tales from the Solstice — Full Opera', 'icon' => 'ph-vinyl-record', 'external' => true],
                    ],
                ],
                'aethel' => [
                    'eyebrow' => 'Connected Theatrical Soundtracks',
                    'icon'    => 'ph-film-strip',
                    'heading' => 'Hear Firelight\'s Silver Gauntlet Soundtracks',
                    'copy'    => 'Stream both the 1987 theatrical soundtrack and the three-volume 2017 orchestral motion picture scores by Firelight.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/firelight', 'label' => 'Firelight Artist Catalog', 'icon' => 'ph-headphones', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/firelight/1987-silver-gauntlet-of-aethel', 'label' => '1987 Theatrical Soundtrack', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/firelight/2017-silver-gauntlet-of-aethel-book-i', 'label' => 'Book I: The Unwinding Path Score', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/firelight/2017-silver-gauntlet-of-aethel-book-ii', 'label' => 'Book II: The Shadow\'s Heart Score', 'icon' => 'ph-vinyl-record', 'external' => true],
                        ['href' => 'https://music.raggiesoft.com/firelight/2017-silver-gauntlet-of-aethel-book-iii', 'label' => 'Book III: The Dawn of the Twins Score', 'icon' => 'ph-vinyl-record', 'external' => true],
                    ],
                ],
                'runebreaker' => [
                    'eyebrow' => 'Connected Soundtrack in The Listening Room',
                    'icon'    => 'ph-game-controller',
                    'heading' => 'Hear Mana Canvas\'s Runebreaker Soundtrack',
                    'copy'    => 'Stream the 23-track 1992 original soundtrack and 1993 concert suite in The Listening Room.',
                    'links'   => [
                        ['href' => 'https://music.raggiesoft.com/mana-canvas/1992-runebreaker-ost', 'label' => 'Runebreaker Original Soundtrack (1992)', 'icon' => 'ph-vinyl-record', 'external' => true, 'primary' => true],
                        ['href' => 'https://music.raggiesoft.com/mana-canvas/1993-runebreaker-live', 'label' => 'Runebreaker Live (1993)', 'icon' => 'ph-microphone-stage', 'external' => true],
                    ],
                ],
            ];
            $activeMusicCard = $companionMusicCards[$seriesSlug] ?? null;
            ?>
            <?php if ($activeMusicCard): ?>
            <!-- Companion Portal & Listening Room Integration Box -->
            <div style="margin-top: 1.75rem; padding: 1.25rem 1.35rem; border-radius: 12px; background: var(--rs-surface); border: 1px solid var(--rs-border); border-left: 5px solid var(--rs-primary);">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-primary); margin-bottom: 0.35rem;">
                    <i class="ph-fill <?php echo htmlspecialchars($activeMusicCard['icon']); ?>" aria-hidden="true"></i> <?php echo htmlspecialchars($activeMusicCard['eyebrow']); ?>
                </div>
                <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0 0 0.5rem 0; color: var(--rs-heading);">
                    <?php echo htmlspecialchars($activeMusicCard['heading']); ?>
                </h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0 0 1rem 0; color: var(--rs-text); opacity: 0.92;">
                    <?php echo htmlspecialchars($activeMusicCard['copy']); ?>
                </p>
                <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
                    <?php foreach ($activeMusicCard['links'] as $mLink):
                        $isExt = !empty($mLink['external']);
                        $isPri = !empty($mLink['primary']);
                    ?>
                    <a href="<?php echo htmlspecialchars($mLink['href']); ?>"<?php echo $isExt ? ' data-no-spa="true" target="_blank" rel="noopener"' : ''; ?> class="rs-btn<?php echo $isPri ? ' rs-btn-primary' : ''; ?>" style="font-size: 0.9rem; padding: 0.55rem 1rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph-fill <?php echo htmlspecialchars($mLink['icon']); ?>" aria-hidden="true"></i> <?php echo htmlspecialchars($mLink['label']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($toc_manifest['eras']) && is_array($toc_manifest['eras'])): ?>
    <!-- Crawlable Story Eras & Opening Chapters Overview (reduces click depth from /library to deep scenes to 2 hops) -->
    <section aria-labelledby="landing-eras-heading" style="margin-top: 2.5rem; padding-top: 2rem; border-top: 1px solid var(--rs-border);">
        <div style="display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem;">
            <h2 id="landing-eras-heading" style="margin: 0; font-size: 1.65rem; color: var(--rs-primary);">Story Eras &amp; Chapters</h2>
            <a href="<?php echo htmlspecialchars($tocUrl); ?>" style="color: var(--rs-primary); font-weight: 700; text-decoration: none; font-size: 0.95rem;">
                Full Table of Contents &rarr;
            </a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            <?php foreach ($toc_manifest['eras'] as $eraItem):
                $eraOverviewUrl = !empty($eraItem['era_url'])
                    ? str_replace('/raggiesoft-books/books', '', (string)$eraItem['era_url'])
                    : $tocUrl;
                $eraChapCount = !empty($eraItem['chapters']) && is_array($eraItem['chapters']) ? count($eraItem['chapters']) : 0;
                $eraWords = (int)($eraItem['word_count'] ?? 0);
                $eraFirstPartHref = '';
                $eraFirstChapTitle = '';
                if (!empty($eraItem['chapters'][0]['parts'][0])) {
                    $eraOpeningPart = $eraItem['chapters'][0]['parts'][0];
                    $eraOpeningFile = (string)($eraOpeningPart['file_path'] ?? '');
                    $eraFirstPartHref = ($eraOpeningFile !== '' && !empty($fileToUrl[$eraOpeningFile]))
                        ? (string)$fileToUrl[$eraOpeningFile]
                        : (string)preg_replace('#/part-1-[^/]+$#', '', str_replace('/raggiesoft-books/books', '', (string)($eraOpeningPart['url'] ?? '')));
                    $eraFirstChapTitle = (string)($eraItem['chapters'][0]['chap_title'] ?? '');
                }
            ?>
            <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 10px; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <h3 style="margin: 0 0 0.35rem 0; font-size: 1.1rem; line-height: 1.35;">
                        <a href="<?php echo htmlspecialchars($eraOverviewUrl); ?>" style="color: var(--rs-heading); text-decoration: none;">
                            <?php echo htmlspecialchars((string)($eraItem['era_title'] ?? 'Era')); ?>
                        </a>
                    </h3>
                    <?php if (!empty($eraItem['era_description'])): ?>
                    <p style="margin: 0; font-size: 0.88rem; line-height: 1.55; color: var(--rs-text-muted);">
                        <?php echo htmlspecialchars((string)$eraItem['era_description']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; padding-top: 0.55rem; border-top: 1px dashed var(--rs-border); font-size: 0.8rem;">
                    <span style="color: var(--rs-text-muted); font-weight: 600; font-variant-numeric: tabular-nums;">
                        <?php echo (int)$eraChapCount; ?> <?php echo $eraChapCount === 1 ? 'chapter' : 'chapters'; ?><?php echo $eraWords > 0 ? ' &bull; ' . number_format($eraWords) . ' words' : ''; ?>
                    </span>
                    <?php if ($eraFirstPartHref !== ''): ?>
                    <a href="<?php echo htmlspecialchars($eraFirstPartHref); ?>" title="<?php echo htmlspecialchars($eraFirstChapTitle !== '' ? 'Start ' . $eraFirstChapTitle : 'Begin Era'); ?>" style="color: var(--rs-primary); font-weight: 700; text-decoration: none;">
                        Begin Era &rarr;
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

<?php 
// Build deterministic canonical share URL so social shares never include legacy `/books/` prefixes or query strings.
$currentUrl = 'https://books.raggiesoft.com/' . rawurlencode((string)$seriesSlug);
$encodedUrl = urlencode($currentUrl);
$encodedText = urlencode("Check out " . $seriesTitle . " by Michael Ragsdale!");
$ogImageFile = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
?>
    <!-- Share Section -->
    <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--rs-border); text-align: center;">
        <h2 style="font-size: 1.4rem; margin-bottom: 1.5rem; color: var(--rs-text);">Share this Book</h2>
        
        <!-- OpenGraph Image Preview -->
        <img id="rs-share-og-preview" src="<?php echo htmlspecialchars($ogImageFile); ?>" alt="Social share card for <?php echo htmlspecialchars($seriesTitle); ?>" width="1200" height="630" loading="lazy" decoding="async" style="width: 100%; max-width: 600px; height: auto; aspect-ratio: 1200 / 630; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); margin-bottom: 2rem; border: 1px solid var(--rs-border);">
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <!-- Facebook Share Intent -->
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-facebook-logo" aria-hidden="true"></i> Facebook
            </a>
            
            <!-- Bluesky Compose Intent -->
            <a href="https://bsky.app/intent/compose?text=<?php echo $encodedText . '%20' . $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-cloud-sun" aria-hidden="true"></i> Bluesky
            </a>

            <!-- Native Clipboard API Copy capability (wired via addEventListener below) -->
            <button type="button" id="rs-copy-book-link" data-copy-url="<?php echo htmlspecialchars($currentUrl); ?>" class="rs-btn rs-btn-primary">
                <i class="ph ph-link" aria-hidden="true"></i> <span id="rs-copy-book-label">Copy Link</span>
            </button>
        </div>

        <p style="margin-top: 2rem; font-size: 0.8rem; color: var(--rs-text); opacity: 0.72; line-height: 1.5;">
            <strong>Work of Fiction Disclaimer:</strong> All characters, bands, universities (including CPI), corporations, and events in this series are works of fiction. Any resemblance to actual persons, living or dead, institutions, or events is purely coincidental.
        </p>
    </div>
    <script>
    (function () {
        var ogImg = document.getElementById('rs-share-og-preview');
        if (ogImg) {
            ogImg.addEventListener('error', function () {
                ogImg.style.display = 'none';
            });
        }
        var copyBtn = document.getElementById('rs-copy-book-link');
        var copyLabel = document.getElementById('rs-copy-book-label');
        if (copyBtn && navigator.clipboard) {
            copyBtn.addEventListener('click', function () {
                var targetUrl = copyBtn.getAttribute('data-copy-url') || window.location.href;
                navigator.clipboard.writeText(targetUrl).then(function () {
                    if (copyLabel) {
                        var original = copyLabel.textContent;
                        copyLabel.textContent = 'Link Copied!';
                        setTimeout(function () {
                            copyLabel.textContent = original;
                        }, 2200);
                    }
                });
            });
        }
    })();
    </script>
</div>
