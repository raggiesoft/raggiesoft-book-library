<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/landing.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This sub-view renders the dedicated Series Overview ("Book Cover & Back-Cover
 *     Synopsis") page at `https://books.raggiesoft.com/<series>` (for example,
 *     `/rachel`, `/aubrie`, `/katie`, or `/stardust-engine`).
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php`, `sophia/sidebar.php`, & `sophia/oliver.php`:
 *     1. In `data/routes/<series>.json`, the root series route (`/<series>`) maps
 *        to the special `filePath` token `'__SERIES_LANDING__'`.
 *     2. `eleanor/isabel.php` loads the series' `toc.json` into `$toc_manifest`,
 *        resolves eBook file sizes into `$ebookSizes`, sets OpenGraph `og:type`
 *        to `'book'`, and renders `sophia/sidebar.php` (which populates `$fileToUrl`).
 *     3. `sophia/oliver.php` instantiates `$Parsedown = new StardustParsedown('vanilla')`,
 *        turns on PHP output buffering (`ob_start()`), and `require_once`s this file
 *        inside `#stardust-reading-pane` alongside full Schema.org `Book` JSON-LD!
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     1. Two-Column Hero Grid (`.landing-grid`):
 *          - Left Column: Sticky 2:3 uncropped novel cover artwork.
 *          - Right Column: Full Markdown synopsis (`landing.md` or `series_description`
 *            from `ovab.json`), plus 1-click `Start Reading` (jumping straight to
 *            Era 1, Chapter 1, Part 1), `View the Table of Contents`, `Table of
 *            Illustrations`, and `Follow a Story Arc` buttons.
 *     2. Full-Width Below-the-Fold Stack (`.landing-full-stack`):
 *          - Curated **Story Arcs** reading playlists (`#series-story-arcs`).
 *          - **Live Manuscript & Lore Scale** telemetry card (words, print pages, hours).
 *          - **Offline eBook Editions** bar (Illustrated + Text-Only EPUB & PDF).
 *          - **Companion Music Discovery Card** (`$companionMusicCards`) connecting
 *            music-linked novels directly to `music.raggiesoft.com` (The Listening Room).
 *     3. Crawlable **Story Eras & Chapters** overview grid + Social Share bar with
 *        an async Clipboard API (`navigator.clipboard.writeText()`) "Copy Link" button!
 * =============================================================================
 */

// Import shared manifests, CDN base URL, Parsedown Markdown parser, eBook sizes, and route maps.
global $toc_manifest, $cdnBaseUrl, $seriesSlug, $Parsedown, $ebookSizes, $routeData, $fileToUrl;

// Step 1: Extract core series metadata from `$toc_manifest` (`toc.json`) with safe fallbacks.
$seriesTitle = $toc_manifest['series_title'] ?? 'Book Series';
$seriesDescriptionLong = $toc_manifest['series_description_long'] ?? '';
$seriesDescription = $toc_manifest['series_description'] ?? 'No description available.';
$seriesImage = $toc_manifest['series_image'] ?? '';
$tocUrl = '/' . $seriesSlug . '/toc';

// Step 2: Resolve the exact URL of Era 1 -> Chapter 1 -> Part 1 (`$firstPartUrl`) so the
// primary "Start Reading" button drops the reader directly into the opening scene in 1 click!
$firstPartUrl = '';
if (!empty($toc_manifest['eras'][0]['chapters'][0]['parts'][0])) {
    $openingPart = $toc_manifest['eras'][0]['chapters'][0]['parts'][0];
    $openingFilePath = (string)($openingPart['file_path'] ?? '');
    if ($openingFilePath !== '' && !empty($fileToUrl[$openingFilePath])) {
        $firstPartUrl = (string)$fileToUrl[$openingFilePath];
    } elseif (!empty($openingPart['url'])) {
        // Strip legacy single-part `/part-1-<slug>` suffix if falling back to raw `toc.json` URL
        $firstPartUrl = (string)preg_replace('#/part-1-[^/]+$#', '', str_replace('/raggiesoft-books/books', '', (string)$openingPart['url']));
    }
}

// Step 3: Resolve human-readable file sizes (e.g., `"42.8 MB"` vs `"1.4 MB"`) for all 4 offline eBook editions.
$landingSizes = !empty($ebookSizes) && is_array($ebookSizes)
    ? $ebookSizes
    : ($routeData['common']['ebook_sizes'] ?? ($toc_manifest['ebook_sizes'] ?? []));
$landingEpubSize     = $landingSizes['epub'] ?? '';
$landingPdfSize      = $landingSizes['pdf'] ?? '';
$landingTextEpubSize = $landingSizes['text_epub'] ?? '';
$landingTextPdfSize  = $landingSizes['text_pdf'] ?? '';

// Step 4: Extract illustration count, manuscript word/page/hour metrics, and curated Story Arcs (`arcs.json`).
// Industry typesetting math: `275` words per trade-paperback page; `250` words per reading minute (`15,000` words/hour).
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
// Curated Story Arcs compiled by `paige.php` from `books/<seriesSlug>/arcs.json`
$landingStoryArcs = is_array($toc_manifest['story_arcs'] ?? null)
    ? $toc_manifest['story_arcs']
    : (is_array($routeData['common']['story_arcs'] ?? null) ? $routeData['common']['story_arcs'] : []);
?>
<style>
/* Series Overview Hero Grid: pairs the uncropped 2:3 cover strictly with the synopsis & primary reading CTAs,
 * while Manuscript Scale, Offline eBook Editions, and Companion Music span 100% full width below with zero dead space. */
.landing-shell {
    max-inline-size: 920px;
    margin-inline: auto;
}
.landing-hero-header {
    text-align: center;
    margin-block-end: 2.25rem;
}
.landing-hero-title {
    font-size: clamp(2.15rem, 4.5vw, 3.25rem);
    line-height: 1.15;
    margin: 0;
    color: var(--rs-heading, var(--rs-text));
}
.landing-grid {
    display: grid;
    grid-template-columns: 1fr; /* Single column stack for mobile devices */
    gap: 2rem;
    align-items: start;
    margin-block-end: 2rem;
}
@media (min-width: 768px) {
    .landing-grid {
        grid-template-columns: 270px minmax(0, 1fr);
        gap: 2.5rem;
    }
    .landing-cover-col {
        position: sticky;
        top: 1.5rem;
    }
}
/* Explicit `block-size: auto; height: auto; display: block;` ensures HTML `width`/`height` attributes
 * reserve space for zero CLS without forcing a fixed 900px height or cropping cover typography horizontally. */
.landing-cover {
    display: block;
    inline-size: 100%;
    max-inline-size: 270px;
    block-size: auto;
    height: auto;
    margin-inline: auto;
    border-radius: 10px;
    border: 1px solid var(--rs-border);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.42);
}
.landing-cover-placeholder {
    aspect-ratio: 2 / 3;
    background: var(--rs-surface);
    display: flex;
    align-items: center;
    justify-content: center;
}
.landing-about-heading {
    margin-block: 0 1.15rem !important;
    padding-block-end: 0.5rem;
    border-bottom: 1px solid var(--rs-border);
    color: var(--rs-primary) !important;
}
.landing-synopsis-prose {
    font-size: 1.12rem;
    line-height: 1.78;
    margin-block-end: 1.5rem;
    color: var(--rs-text);
}
.landing-synopsis-prose p:first-child {
    margin-block-start: 0;
}
.landing-synopsis-prose p:last-child {
    margin-block-end: 0;
}
.landing-primary-actions {
    display: flex;
    gap: 0.85rem;
    align-items: center;
    flex-wrap: wrap;
}
.landing-full-stack {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-block-end: 2.25rem;
}
.landing-editions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem 1.5rem;
    padding: 1rem 1.3rem;
    border-radius: 12px;
    background: var(--rs-surface);
    border: 1px solid var(--rs-border);
}
</style>

<div class="book-page landing-shell">
    <!-- Landing Header -->
    <div class="landing-hero-header">
        <h1 class="landing-hero-title"><?php echo htmlspecialchars($seriesTitle); ?></h1>
    </div>
    
    <div class="landing-grid">
        <!-- Cover Art Column -->
        <div class="landing-cover-col">
            <?php if (!empty($seriesImage)): ?>
                <img src="<?php echo htmlspecialchars($cdnBaseUrl . $seriesImage); ?>" alt="Cover for <?php echo htmlspecialchars($seriesTitle); ?>" class="landing-cover" width="848" height="1264" fetchpriority="high" decoding="async" />
            <?php else: ?>
                <!-- Fallback placeholder if no cover image is defined -->
                <div class="landing-cover landing-cover-placeholder">
                    <i class="ph ph-book" aria-hidden="true" style="font-size: 5rem; opacity: 0.2;"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Synopsis and Primary Reading CTA Column -->
        <div style="text-align: left; min-inline-size: 0;">
            <h2 class="landing-about-heading">About this Series</h2>
            <div class="landing-synopsis-prose">
                <?php 
                // Step 5: Render the book's back-cover synopsis.
                // - If `paige.php` found a multi-paragraph `landing.md` (`$seriesDescriptionLong`),
                //   strip any optional `--- ... ---` YAML frontmatter block at the top using `preg_match()`
                //   + `substr()`, then convert Markdown formatting into HTML via `$Parsedown->text()`.
                // - Otherwise, fall back to `series_description` from `ovab.json`, escaping HTML with
                //   `htmlspecialchars()` and turning newline characters (`\n`) into `<br>` tags via `nl2br()`.
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

            <!-- Primary Reading & Visual Index Actions -->
            <div class="landing-primary-actions">
                <?php if ($firstPartUrl !== ''): ?>
                <a href="<?php echo htmlspecialchars($firstPartUrl); ?>" class="rs-btn rs-btn-primary" style="font-size: 1.05rem; padding: 0.72rem 1.65rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph-fill ph-play-circle" aria-hidden="true"></i> Start Reading
                </a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($tocUrl); ?>" class="rs-btn<?php echo $firstPartUrl === '' ? ' rs-btn-primary' : ''; ?>" style="font-size: 1rem; padding: 0.72rem 1.35rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-book-open" aria-hidden="true"></i> View the Table of Contents
                </a>
                <?php if ($landingIllusCount > 0): ?>
                <a href="<?php echo htmlspecialchars($illustrationsUrl); ?>" class="rs-btn" style="font-size: 1rem; padding: 0.72rem 1.25rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph ph-images" aria-hidden="true"></i>
                    <span>Table of Illustrations <span style="opacity: 0.85; font-weight: 600; font-size: 0.9em;">(<?php echo $landingIllusCount; ?>)</span></span>
                </a>
                <?php endif; ?>
                <?php if (!empty($landingStoryArcs)): ?>
                <a href="#series-story-arcs" data-no-spa="true" class="rs-btn" style="font-size: 1rem; padding: 0.72rem 1.25rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="ph-fill ph-playlist" aria-hidden="true"></i>
                    <span>Follow a Story Arc <span style="opacity: 0.85; font-weight: 600; font-size: 0.9em;">(<?php echo count($landingStoryArcs); ?>)</span></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Full-Width Telemetry, Story Arcs, Offline eBook Editions & Connected Music Stack (Zero Left-Column Dead Space) -->
    <div class="landing-full-stack">
        <?php if (!empty($landingStoryArcs)): ?>
        <!-- Curated Multi-Arc Story Arcs (Reading Playlists) Section -->
        <section id="series-story-arcs" class="rs-series-arcs-section" aria-labelledby="series-story-arcs-heading">
            <div class="rs-series-arcs-header">
                <div>
                    <div class="rs-arcs-kicker">
                        <i class="ph-fill ph-playlist" aria-hidden="true"></i>
                        <span>Reading Playlists &bull; <?php echo count($landingStoryArcs); ?> <?php echo count($landingStoryArcs) === 1 ? 'Story Arc' : 'Story Arcs'; ?></span>
                    </div>
                    <h2 id="series-story-arcs-heading" class="rs-series-arcs-title">Follow a Story Arc in <?php echo htmlspecialchars($seriesTitle); ?></h2>
                    <p class="rs-series-arcs-lead">
                        Follow an individual character journey or storyline chronologically across the book&mdash;like a curated playlist of chapters and behind-the-scenes bonus scenes.
                    </p>
                </div>
                <a href="/arcs?book=<?php echo htmlspecialchars($seriesSlug); ?>" class="rs-btn" style="white-space: nowrap;">
                    <i class="ph ph-compass" aria-hidden="true"></i>
                    <span>All Library Story Arcs</span>
                </a>
            </div>

            <div class="rs-series-arcs-list">
                <?php foreach ($landingStoryArcs as $lArc):
                    $lArcSlug     = (string)($lArc['slug'] ?? '');
                    $lArcTitle    = (string)($lArc['title'] ?? '');
                    $lArcSub      = (string)($lArc['subtitle'] ?? '');
                    $lArcDesc     = (string)($lArc['description'] ?? '');
                    $lRawImg      = (string)($lArc['image'] ?? '');
                    $lImgUrl      = (str_starts_with($lRawImg, 'http://') || str_starts_with($lRawImg, 'https://'))
                        ? $lRawImg
                        : rtrim((string)$cdnBaseUrl, '/') . '/' . ltrim($lRawImg, '/');
                    $lStartUrl    = (string)($lArc['start_url'] ?? ('/' . $seriesSlug));
                    $lBsUrl       = (string)($lArc['backstage_playlist_url'] ?? 'https://backstage.raggiesoft.com/arcs/');
                    $lPartCount   = (int)($lArc['part_count'] ?? 0);
                    $lBsCount     = (int)($lArc['backstage_count'] ?? 0);
                    $lReadHours   = (float)($lArc['reading_hours'] ?? 0);
                    $lReadMins    = (int)($lArc['reading_minutes'] ?? 1);
                    $lReadLabel   = $lReadHours >= 1.0 ? ('~' . number_format($lReadHours, 1) . ' hrs') : ('~' . $lReadMins . ' min');
                    $lStops       = is_array($lArc['stops'] ?? null) ? $lArc['stops'] : [];
                ?>
                <article class="rs-series-arc-item">
                    <div class="rs-series-arc-top">
                        <a href="<?php echo htmlspecialchars($lStartUrl); ?>" class="rs-series-arc-thumb" tabindex="-1" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars($lImgUrl); ?>" alt="" width="480" height="270" loading="lazy" decoding="async">
                        </a>
                        <div class="rs-series-arc-content">
                            <?php if ($lArcSub !== ''): ?>
                            <div class="rs-arc-card-subtitle"><?php echo htmlspecialchars($lArcSub); ?></div>
                            <?php endif; ?>
                            <h3 class="rs-series-arc-item-title">
                                <a href="<?php echo htmlspecialchars($lStartUrl); ?>"><?php echo htmlspecialchars($lArcTitle); ?></a>
                            </h3>
                            <p class="rs-arc-card-desc"><?php echo htmlspecialchars($lArcDesc); ?></p>
                            <div class="rs-arc-card-meta">
                                <span><i class="ph-fill ph-book-open-text" aria-hidden="true"></i> <?php echo $lPartCount; ?> <?php echo $lPartCount === 1 ? 'Chapter' : 'Chapters'; ?></span>
                                <?php if ($lBsCount > 0): ?>
                                <span><i class="ph-fill ph-film-slate" aria-hidden="true"></i> <?php echo $lBsCount; ?> Bonus <?php echo $lBsCount === 1 ? 'Scene' : 'Scenes'; ?></span>
                                <?php endif; ?>
                                <span><i class="ph ph-clock" aria-hidden="true"></i> <?php echo htmlspecialchars($lReadLabel); ?></span>
                            </div>
                            <div class="rs-arc-card-actions">
                                <a href="<?php echo htmlspecialchars($lStartUrl); ?>" class="rs-btn rs-btn-primary rs-arc-start-btn">
                                    <i class="ph-fill ph-play-circle" aria-hidden="true"></i>
                                    <span>Start Story Arc</span>
                                </a>
                                <a href="<?php echo htmlspecialchars($lBsUrl); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   data-no-spa="true"
                                   data-backstage-direct="true"
                                   class="rs-btn">
                                    <i class="ph ph-film-slate" aria-hidden="true"></i>
                                    <span>Backstage Guide</span>
                                    <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($lStops)): ?>
                    <details class="rs-arc-disclosure" name="series-story-arcs">
                        <summary class="rs-arc-disclosure-summary">
                            <span><i class="ph ph-list-numbers" aria-hidden="true"></i> Browse All <?php echo count($lStops); ?> Stops in This Playlist</span>
                            <i class="ph-bold ph-caret-down rs-arc-disclosure-caret" aria-hidden="true"></i>
                        </summary>
                        <ol class="rs-arc-stop-list">
                            <?php foreach ($lStops as $stop):
                                $sKind   = (string)($stop['kind'] ?? 'part');
                                $sNum    = (int)($stop['stop_num'] ?? 0);
                                $sTitle  = (string)($stop['title'] ?? '');
                                $sSub    = (string)($stop['subtitle'] ?? '');
                                $sDate   = (string)($stop['date'] ?? '');
                                $sMins   = (int)($stop['reading_minutes'] ?? 1);
                                $sArcUrl = (string)($stop['arc_url'] ?? '#');
                                $sExtUrl = (string)($stop['url'] ?? '#');
                            ?>
                            <li class="rs-arc-stop-row is-<?php echo htmlspecialchars($sKind); ?>">
                                <span class="rs-arc-stop-num"><?php echo $sNum; ?></span>
                                <div class="rs-arc-stop-info">
                                    <div class="rs-arc-stop-kicker">
                                        <?php if ($sKind === 'backstage'): ?>
                                            <span class="rs-arc-kind-badge is-backstage"><i class="ph-fill ph-film-slate" aria-hidden="true"></i> Bonus Scene</span>
                                        <?php else: ?>
                                            <span class="rs-arc-kind-badge is-part"><i class="ph-fill ph-book-open" aria-hidden="true"></i> Chapter</span>
                                        <?php endif; ?>
                                        <?php if ($sDate !== ''): ?>
                                            <span><?php echo htmlspecialchars($sDate); ?></span>
                                        <?php endif; ?>
                                        <span>&bull; <?php echo $sMins; ?> min</span>
                                    </div>
                                    <?php if ($sKind === 'backstage'): ?>
                                        <a href="<?php echo htmlspecialchars($sExtUrl); ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           data-no-spa="true"
                                           data-backstage-direct="true"
                                           class="rs-arc-stop-link">
                                            <?php echo htmlspecialchars($sTitle); ?> <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($sArcUrl); ?>" class="rs-arc-stop-link">
                                            <?php echo htmlspecialchars($sTitle); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($sSub !== ''): ?>
                                        <div class="rs-arc-stop-sub"><?php echo htmlspecialchars($sSub); ?></div>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($landingCombinedWords > 0): ?>
        <!-- Dynamic Manuscript & Lore Scale Telemetry Card (Compiled by paige.php) -->
        <div style="padding: 1.15rem 1.35rem; border-radius: 12px; background: var(--rs-surface); border: 1px solid var(--rs-border); border-left: 4px solid var(--rs-primary);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.8rem;">
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i class="ph-fill ph-chart-bar" aria-hidden="true"></i>
                    <span>Live Manuscript &amp; Lore Scale</span>
                </div>
                <?php if ($landingScaleLabel !== ''): ?>
                <span style="font-size: 0.74rem; font-weight: 700; padding: 0.2rem 0.65rem; border-radius: 999px; background: color-mix(in srgb, var(--rs-primary) 14%, transparent); color: var(--rs-primary);">
                    <?php echo htmlspecialchars($landingScaleLabel); ?>
                </span>
                <?php endif; ?>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 0.75rem;">
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--rs-text-muted);">Story Prose</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--rs-heading); font-variant-numeric: tabular-nums;"><?php echo number_format($landingStoryWords); ?> <span style="font-size: 0.78rem; font-weight: 600; opacity: 0.8;">words</span></div>
                    <div style="font-size: 0.78rem; color: var(--rs-text-muted);">~<?php echo number_format($landingStoryPages); ?> print pages &bull; ~<?php echo number_format($landingReadingHours, 1); ?> hrs</div>
                </div>
                <?php if ($landingLoreWords > 0): ?>
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--rs-text-muted);">Curated Lore &amp; Liner Notes</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--rs-heading); font-variant-numeric: tabular-nums;">+<?php echo number_format($landingLoreWords); ?> <span style="font-size: 0.78rem; font-weight: 600; opacity: 0.8;">words</span></div>
                    <div style="font-size: 0.78rem; color: var(--rs-text-muted);">Archives, dossiers &amp; liner notes</div>
                </div>
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--rs-text-muted);">Combined Volume Total</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--rs-primary); font-variant-numeric: tabular-nums;"><?php echo number_format($landingCombinedWords); ?> <span style="font-size: 0.78rem; font-weight: 600; opacity: 0.88;">words</span></div>
                    <div style="font-size: 0.78rem; color: var(--rs-text-muted);">~<?php echo number_format($landingTotalPages); ?> combined pages</div>
                </div>
                <?php endif; ?>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; font-size: 0.8rem; padding-top: 0.6rem; border-top: 1px dashed var(--rs-border); color: var(--rs-text-muted);">
                <span><?php echo htmlspecialchars($landingScaleBenchmark); ?></span>
                <a href="/stats" style="color: var(--rs-primary); font-weight: 700; text-decoration: none; white-space: nowrap;">Compare Across All 30 Volumes &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Offline eBook Download Bar (Illustrated + Text-Only Editions) -->
        <div class="landing-editions-bar">
            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <div style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i class="ph-fill ph-books" aria-hidden="true"></i>
                    <span>Offline eBook Editions</span>
                </div>
                <div style="font-size: 0.86rem; color: var(--rs-text-muted); display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                    <span><i class="ph ph-file-text" aria-hidden="true"></i> Text-Only Edition (no images):</span>
                    <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.epub"); ?>" target="_blank" rel="noopener" title="Download Text-Only EPUB (No Images<?php echo $landingTextEpubSize ? ' — ' . htmlspecialchars($landingTextEpubSize) : ''; ?>)" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">EPUB &mdash; Text Only<?php echo $landingTextEpubSize ? ' (' . htmlspecialchars($landingTextEpubSize) . ')' : ''; ?></a>
                    <span aria-hidden="true">&bull;</span>
                    <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}-text.pdf"); ?>" target="_blank" rel="noopener" title="Download Text-Only PDF (No Images<?php echo $landingTextPdfSize ? ' — ' . htmlspecialchars($landingTextPdfSize) : ''; ?>)" style="color: var(--rs-primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">PDF &mdash; Text Only<?php echo $landingTextPdfSize ? ' (' . htmlspecialchars($landingTextPdfSize) . ')' : ''; ?></a>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.epub"); ?>" target="_blank" rel="noopener" class="rs-btn" title="Download Illustrated EPUB<?php echo $landingEpubSize ? ' (' . htmlspecialchars($landingEpubSize) . ')' : ''; ?>" style="font-size: 0.98rem; padding: 0.65rem 1.2rem; display: inline-flex; align-items: center; gap: 0.45rem;">
                    <i class="ph ph-download-simple" aria-hidden="true"></i>
                    <span>EPUB<?php echo $landingEpubSize ? ' <span style="opacity: 0.82; font-weight: 500; font-size: 0.9em;">(' . htmlspecialchars($landingEpubSize) . ')</span>' : ''; ?></span>
                </a>
                <a href="<?php echo htmlspecialchars("https://assets.raggiesoft.com/raggiesoft-books/books/{$seriesSlug}/{$seriesSlug}.pdf"); ?>" target="_blank" rel="noopener" class="rs-btn" title="Download Illustrated PDF<?php echo $landingPdfSize ? ' (' . htmlspecialchars($landingPdfSize) . ')' : ''; ?>" style="font-size: 0.98rem; padding: 0.65rem 1.2rem; display: inline-flex; align-items: center; gap: 0.45rem;">
                    <i class="ph ph-file-pdf" aria-hidden="true"></i>
                    <span>PDF<?php echo $landingPdfSize ? ' <span style="opacity: 0.82; font-weight: 500; font-size: 0.9em;">(' . htmlspecialchars($landingPdfSize) . ')</span>' : ''; ?></span>
                </a>
            </div>
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
            <div style="padding: 1.25rem 1.35rem; border-radius: 12px; background: var(--rs-surface); border: 1px solid var(--rs-border); border-left: 5px solid var(--rs-primary);">
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
// SECTION 5: SOCIAL SHARING & CLIPBOARD LINK COPY
// Build deterministic canonical share URL (`https://books.raggiesoft.com/<slug>`) so shared links
// never carry temporary query parameters or legacy `/books/` prefixes.
$currentUrl = 'https://books.raggiesoft.com/' . rawurlencode((string)$seriesSlug);
$encodedUrl = urlencode($currentUrl);
$encodedText = urlencode("Check out " . $seriesTitle . " by Michael Ragsdale!");
$ogImageFile = $cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $seriesSlug . '.jpg';
?>
    <!-- Share Section -->
    <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--rs-border); text-align: center;">
        <h2 style="font-size: 1.4rem; margin-bottom: 1.5rem; color: var(--rs-text);">Share this Book</h2>
        
        <!-- 16:9 Social Share Card Preview (`1200x630`; hidden gracefully via JS `error` listener below if an OG image hasn't been generated yet) -->
        <img id="rs-share-og-preview" src="<?php echo htmlspecialchars($ogImageFile); ?>" alt="Social share card for <?php echo htmlspecialchars($seriesTitle); ?>" width="1200" height="630" loading="lazy" decoding="async" style="width: 100%; max-width: 600px; height: auto; aspect-ratio: 1200 / 630; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); margin-bottom: 2rem; border: 1px solid var(--rs-border);">
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <!-- Facebook Share Intent URL (`?u=<urlEncodedLink>`) -->
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-facebook-logo" aria-hidden="true"></i> Facebook
            </a>
            
            <!-- Bluesky Compose Intent URL (`?text=<urlEncodedMessage>`) -->
            <a href="https://bsky.app/intent/compose?text=<?php echo $encodedText . '%20' . $encodedUrl; ?>" target="_blank" rel="noopener noreferrer" class="rs-btn">
                <i class="ph ph-cloud-sun" aria-hidden="true"></i> Bluesky
            </a>

            <!-- Modern Async Clipboard API Copy Button (`data-copy-url` passes PHP's `$currentUrl` cleanly to JS) -->
            <button type="button" id="rs-copy-book-link" data-copy-url="<?php echo htmlspecialchars($currentUrl); ?>" class="rs-btn rs-btn-primary">
                <i class="ph ph-link" aria-hidden="true"></i> <span id="rs-copy-book-label">Copy Link</span>
            </button>
        </div>

        <p style="margin-top: 2rem; font-size: 0.8rem; color: var(--rs-text); opacity: 0.72; line-height: 1.5;">
            <strong>Work of Fiction Disclaimer:</strong> All characters, bands, universities (including CPI), corporations, and events in this series are works of fiction. Any resemblance to actual persons, living or dead, institutions, or events is purely coincidental.
        </p>
    </div>
    <script>
    /*
     * SERIES LANDING SHARE & IMAGE FALLBACK SCRIPT:
     * 1. Listens for the `error` event on `#rs-share-og-preview` so broken-image icons never appear
     *    if a newly drafted book doesn't have an `og/<slug>.jpg` card on the CDN yet.
     * 2. Uses `navigator.clipboard.writeText(targetUrl)` (an asynchronous Promise-based Web API)
     *    to copy the canonical book URL to the user's clipboard, temporarily changing the button
     *    label to `'Link Copied!'` for 2,200 milliseconds (`setTimeout`) as visual confirmation!
     */
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
