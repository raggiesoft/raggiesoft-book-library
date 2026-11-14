<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: sophia/stats.php
 * =============================================================================
 * Purpose:
 *     Renders the live, dynamic "Library Word Count & Contemporary Publishing
 *     Scale Ledger" (`https://books.raggiesoft.com/stats`).
 *     Displays real-time manuscript word counts (Pure Story Prose vs. Curated
 *     Lore vs. Combined Total), estimated trade paperback page counts (~275
 *     words/page), estimated reading durations (~250 words/minute), and
 *     comparisons against standard, generic contemporary publishing industry
 *     benchmarks (strictly zero third-party author names or external book titles).
 *
 * Dynamic Data Pipeline:
 *     - Reads `/data/library-stats.json` (compiled automatically by `paige.php`
 *       on every build/deploy, with fallback to CDN `/raggiesoft-books/json/library-stats.json`
 *       or `/raggiesoft-books/books/catalog.json`).
 *     - Whenever any novel scene (`p*.md`) or lore file (`lore/**`, `universal-lore/**`,
 *       `backstage/**`, or `engine-room-records/catalog.json`) is edited and
 *       published via `paige.php` / `jenna-sync.sh`, every metric on this page
 *       updates automatically with zero manual intervention.
 *
 * Accessibility & Theme Support (WCAG 2.1 AA):
 *     - Adapts automatically to Light Mode, Dark Mode, Sepia, and system
 *       `prefers-color-scheme` using `--rs-*` custom properties.
 *     - Uses semantic table landmarks, keyboard-operable filter pills, and
 *       accessible progress bars.
 * =============================================================================
 */

global $cdnBaseUrl, $basePath;

$resolvedBasePath = !empty($basePath) ? $basePath : dirname(__DIR__);
$localStatsFile   = $resolvedBasePath . '/data/library-stats.json';
$cdnStatsUrl      = rtrim((string)$cdnBaseUrl, '/') . '/raggiesoft-books/json/library-stats.json';

$statsRaw = is_file($localStatsFile)
    ? @file_get_contents($localStatsFile)
    : @file_get_contents($cdnStatsUrl);

$stats = $statsRaw ? (json_decode((string)$statsRaw, true) ?: []) : [];

$volumeCount          = (int)($stats['volume_count'] ?? 30);
$activeVolumeCount    = (int)($stats['active_story_volume_count'] ?? 27);
$totalEras            = (int)($stats['total_eras'] ?? 0);
$totalChapters        = (int)($stats['total_chapters'] ?? 0);
$totalParts           = (int)($stats['total_parts'] ?? 0);
$totalIllustrations   = (int)($stats['total_illustrations'] ?? 0);
$totalStoryWords      = (int)($stats['total_story_words'] ?? 0);
$totalBookLoreWords   = (int)($stats['total_book_lore_words'] ?? 0);
$totalSharedLoreWords = (int)($stats['total_shared_universe_lore_words'] ?? 0);
$totalCuratedLore     = (int)($stats['total_curated_lore_words'] ?? 0);
$totalLyricsWords     = (int)($stats['total_lyrics_words'] ?? 0);
$totalCombinedWords   = (int)($stats['total_combined_words'] ?? 0);
$totalWithLyricsWords = (int)($stats['total_combined_with_lyrics_words'] ?? 0);
$totalStoryPages      = (int)($stats['total_story_pages'] ?? max(1, (int)round($totalStoryWords / 275)));
$totalCombinedPages   = (int)($stats['total_combined_pages'] ?? max(1, (int)round($totalCombinedWords / 275)));
$totalReadingHours    = (float)($stats['total_reading_hours'] ?? round($totalStoryWords / 15000, 1));
$totalNovelEquiv      = (float)($stats['total_novel_equivalents'] ?? round($totalStoryWords / 90000, 1));
$totalCombinedEquiv   = (float)($stats['total_combined_novel_equivalents'] ?? round($totalCombinedWords / 90000, 1));
$sharedLoreBreakdown  = is_array($stats['shared_lore_breakdown'] ?? null) ? $stats['shared_lore_breakdown'] : [];
$booksList            = is_array($stats['books'] ?? null) ? $stats['books'] : [];

// Determine maximum combined word count among books for proportional visual bar scaling
$maxBookCombined = 1;
foreach ($booksList as $bItem) {
    $cVal = (int)($bItem['word_stats']['combined_word_count'] ?? 0);
    if ($cVal > $maxBookCombined) {
        $maxBookCombined = $cVal;
    }
}
?>

<style>
    /* =========================================================================
       LIBRARY WORD COUNT & CONTEMPORARY SCALE LEDGER STYLES
       ========================================================================= */
    .ova-stats-hero {
        background: linear-gradient(145deg, #092c36 0%, #0F4C5C 60%, #0a3642 100%);
        color: #ffffff;
        border-bottom: 4px solid #E3B27C;
        padding: calc(1.75rem + env(safe-area-inset-top, 0px)) 1.5rem 2.25rem;
    }
    .ova-stats-hero-inner {
        max-width: 1180px;
        margin: 0 auto;
    }
    .ova-stats-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: rgba(9, 44, 54, 0.78);
        border: 1px solid #E3B27C;
        color: #f6d5a8;
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        margin-bottom: 0.75rem;
    }
    .ova-stats-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.85rem, 3.8vw, 2.75rem);
        font-weight: 800;
        color: #f6d5a8;
        margin: 0 0 0.55rem 0;
        line-height: 1.15;
    }
    .ova-stats-lead {
        font-size: 1rem;
        line-height: 1.6;
        color: #f1f5f9;
        max-width: 820px;
        margin: 0 0 1.5rem 0;
    }
    .ova-stats-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
    }
    .ova-stats-kpi-card {
        background: rgba(9, 28, 36, 0.84);
        border: 1.5px solid rgba(227, 178, 124, 0.55);
        border-radius: 14px;
        padding: 1.1rem 1.25rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28);
    }
    .ova-stats-kpi-label {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #f6d5a8;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .ova-stats-kpi-value {
        font-size: clamp(1.55rem, 2.6vw, 2.1rem);
        font-weight: 900;
        color: #ffffff;
        line-height: 1.15;
        margin-bottom: 0.3rem;
        font-variant-numeric: tabular-nums;
    }
    .ova-stats-kpi-sub {
        font-size: 0.82rem;
        color: #cbd5e1;
        line-height: 1.45;
    }

    .ova-stats-container {
        max-width: 1180px;
        margin: 0 auto;
        padding: 1.75rem 1.25rem 4rem;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .ova-scale-benchmarks-card {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-left: 5px solid #E3B27C;
        border-radius: 14px;
        padding: 1.25rem 1.4rem;
    }
    .ova-scale-tiers-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 0.65rem;
        margin-top: 0.9rem;
    }
    @media (max-width: 1120px) {
        .ova-scale-tiers-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 639px) {
        .ova-scale-tiers-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .ova-scale-tier-box {
        background: var(--rs-bg);
        border: 1px solid var(--rs-border);
        border-radius: 10px;
        padding: 0.7rem 0.75rem;
        min-width: 0;
    }
    .ova-scale-tier-name {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--rs-primary);
        margin-bottom: 0.15rem;
        line-height: 1.25;
    }
    .ova-scale-tier-range {
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--rs-heading);
        font-variant-numeric: tabular-nums;
        line-height: 1.25;
    }
    .ova-scale-tier-note {
        font-size: 0.72rem;
        color: var(--rs-text-muted);
        margin-top: 0.18rem;
        line-height: 1.3;
    }

    .ova-stats-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }
    .ova-stats-filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .ova-stats-filter-btn {
        border: 1px solid var(--rs-border);
        background: var(--rs-surface);
        color: var(--rs-text);
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .ova-stats-filter-btn:hover {
        border-color: var(--rs-primary);
    }
    .ova-stats-filter-btn.is-active {
        background: var(--rs-primary);
        color: var(--rs-on-primary, #ffffff);
        border-color: var(--rs-primary);
    }
    .ova-stats-search-input {
        padding: 0.5rem 0.9rem;
        border-radius: 999px;
        border: 1px solid var(--rs-border);
        background: var(--rs-surface);
        color: var(--rs-text);
        font-size: 0.86rem;
        min-width: 220px;
        max-width: 100%;
    }

    .ova-stats-table-wrap {
        width: 100%;
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-radius: 14px;
        overflow: hidden;
    }
    .ova-stats-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 0.85rem;
        font-variant-numeric: tabular-nums;
    }
    .ova-stats-table th {
        text-align: left;
        padding: 0.75rem 0.65rem;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--rs-primary);
        border-bottom: 2px solid var(--rs-border);
        line-height: 1.25;
        vertical-align: bottom;
    }
    .ova-stats-table th.col-rank { width: 38px; text-align: center; }
    .ova-stats-table th.col-vol  { width: 29%; }
    .ova-stats-table th.col-story { width: 11%; }
    .ova-stats-table th.col-lore  { width: 11%; }
    .ova-stats-table th.col-total { width: 12%; }
    .ova-stats-table th.col-pages { width: 13%; }
    .ova-stats-table th.col-scale { width: 24%; }

    .ova-stats-table th.num,
    .ova-stats-table td.num {
        text-align: right;
    }
    .ova-stats-table td {
        padding: 0.8rem 0.65rem;
        border-bottom: 1px solid var(--rs-border);
        vertical-align: middle;
        color: var(--rs-text);
        overflow-wrap: break-word;
    }
    .ova-stats-table td.cell-rank {
        text-align: center;
        font-weight: 800;
        color: var(--rs-text-muted);
    }
    .ova-stats-table tbody tr:last-child td {
        border-bottom: none;
    }
    .ova-stats-table tbody tr:hover {
        background: rgba(227, 178, 124, 0.06);
    }
    .ova-stats-book-cell {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
    }
    .ova-stats-book-thumb {
        width: 36px;
        height: 54px;
        border-radius: 5px;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid var(--rs-border);
    }
    .ova-stats-book-link {
        font-weight: 800;
        font-size: 0.92rem;
        color: var(--rs-heading);
        text-decoration: none;
        display: block;
        line-height: 1.25;
    }
    .ova-stats-book-link:hover {
        color: var(--rs-primary);
        text-decoration: underline;
    }
    .ova-stats-book-meta {
        font-size: 0.73rem;
        color: var(--rs-text-muted);
        margin-top: 0.15rem;
        line-height: 1.3;
    }
    .ova-stats-bar-track {
        width: 100%;
        height: 7px;
        background: var(--rs-bg);
        border-radius: 999px;
        overflow: hidden;
        display: flex;
        margin-top: 0.35rem;
        border: 1px solid var(--rs-border);
    }
    .ova-stats-bar-story {
        background: var(--rs-primary);
        height: 100%;
    }
    .ova-stats-bar-lore {
        background: var(--rs-secondary);
        height: 100%;
    }
    .ova-scale-badge {
        display: inline-block;
        padding: 0.18rem 0.5rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        background: rgba(227, 178, 124, 0.16);
        color: var(--rs-heading);
        border: 1px solid rgba(227, 178, 124, 0.45);
        max-width: 100%;
    }
    .ova-mobile-rank-pill {
        display: none;
    }

    /* =========================================================================
       MOBILE & COMPACT TABLET CARD LAYOUT (< 860px)
       Transforms each table row into a native zero-horizontal-scroll card so
       no columns are ever clipped on phones or split-screen tablets.
       ========================================================================= */
    @media (max-width: 859px) {
        .ova-stats-search-input {
            width: 100%;
        }
        .ova-stats-table,
        .ova-stats-table tbody {
            display: block;
            width: 100%;
        }
        .ova-stats-table thead {
            display: none;
        }
        .ova-stats-table tbody tr.ova-stats-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.55rem;
            padding: 0.95rem 1rem;
            border-bottom: 1px solid var(--rs-border);
        }
        .ova-stats-table td {
            border-bottom: none;
            padding: 0;
        }
        .ova-stats-table td.cell-rank {
            display: none;
        }
        .ova-mobile-rank-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 0.08rem 0.42rem;
            border-radius: 6px;
            background: rgba(227, 178, 124, 0.18);
            color: var(--rs-primary);
            margin-right: 0.35rem;
            vertical-align: middle;
        }
        .ova-stats-table td.cell-vol {
            grid-column: 1 / -1;
            margin-bottom: 0.25rem;
        }
        .ova-stats-table td.cell-metric {
            text-align: left !important;
            background: var(--rs-bg);
            border: 1px solid var(--rs-border);
            border-radius: 8px;
            padding: 0.45rem 0.55rem;
        }
        .ova-stats-table td.cell-metric::before {
            content: attr(data-label);
            display: block;
            font-size: 0.63rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--rs-primary);
            margin-bottom: 0.12rem;
        }
        .ova-stats-table td.cell-pages,
        .ova-stats-table td.cell-scale {
            grid-column: 1 / -1;
            text-align: left !important;
        }
        .ova-stats-table td.cell-pages {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.4rem;
            font-size: 0.8rem;
            padding-top: 0.2rem;
        }
        .ova-stats-table td.cell-pages::before {
            content: "Print & Reading Estimate:";
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--rs-text-muted);
        }
        .ova-stats-table td.cell-scale {
            padding-top: 0.35rem;
            border-top: 1px dashed var(--rs-border);
        }
    }
</style>

<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg);">
    <!-- HERO SUMMARY -->
    <section class="ova-stats-hero" aria-labelledby="ova-stats-main-heading">
        <div class="ova-stats-hero-inner">
            <div class="ova-stats-kicker">
                <i class="ph-fill ph-chart-bar"></i> Live Dynamic Telemetry &bull; Updated Automatically
            </div>
            <h1 id="ova-stats-main-heading" class="ova-stats-title">
                Library Word Count &amp; Publishing Scale Ledger
            </h1>
            <p class="ova-stats-lead">
                Every manuscript in the <strong>Ocean View Archives</strong> is measured dynamically as scenes and lore dossiers are authored. Explore how each volume&rsquo;s story prose, worldbuilding lore, and combined word count compare against standard contemporary publishing benchmarks.
            </p>

            <div class="ova-stats-summary-grid">
                <!-- Card 1: Pure Story Prose -->
                <div class="ova-stats-kpi-card">
                    <div class="ova-stats-kpi-label">
                        <i class="ph-fill ph-book-open-text"></i> Pure Narrative Story Prose
                    </div>
                    <div class="ova-stats-kpi-value"><?= number_format($totalStoryWords) ?> <span style="font-size: 0.5em; font-weight: 700; color: #f6d5a8;">words</span></div>
                    <div class="ova-stats-kpi-sub">
                        Across <strong><?= number_format($totalParts) ?></strong> scene parts, <strong><?= number_format($totalChapters) ?></strong> chapters &amp; <strong><?= number_format($totalEras) ?></strong> eras in <?= $activeVolumeCount ?> active story volumes (~<strong><?= number_format($totalStoryPages) ?></strong> trade paperback pages &bull; ~<strong><?= number_format($totalReadingHours, 1) ?> hrs</strong> reading time).
                    </div>
                </div>

                <!-- Card 2: Curated Universe & Book Lore -->
                <div class="ova-stats-kpi-card">
                    <div class="ova-stats-kpi-label">
                        <i class="ph-fill ph-compass"></i> Curated Lore &amp; Liner Notes
                    </div>
                    <div class="ova-stats-kpi-value"><?= number_format($totalCuratedLore) ?> <span style="font-size: 0.5em; font-weight: 700; color: #f6d5a8;">words</span></div>
                    <div class="ova-stats-kpi-sub">
                        <strong><?= number_format($totalBookLoreWords) ?></strong> words of series-specific lore &amp; album liner notes + <strong><?= number_format($totalSharedLoreWords) ?></strong> words of shared institutional/transit lore (+ <strong><?= number_format($totalLyricsWords) ?></strong> words of original song lyrics).
                    </div>
                </div>

                <!-- Card 3: Combined Active Canon -->
                <div class="ova-stats-kpi-card">
                    <div class="ova-stats-kpi-label">
                        <i class="ph-fill ph-books"></i> Combined Story + Lore Canon
                    </div>
                    <div class="ova-stats-kpi-value"><?= number_format($totalCombinedWords) ?> <span style="font-size: 0.5em; font-weight: 700; color: #f6d5a8;">words</span></div>
                    <div class="ova-stats-kpi-sub">
                        <strong><?= number_format($totalWithLyricsWords) ?></strong> words including all song lyrics (~<strong><?= number_format($totalCombinedPages) ?></strong> print pages &bull; <strong><?= number_format($totalIllustrations) ?></strong> archival 16:9 illustrations).
                    </div>
                </div>

                <!-- Card 4: Contemporary Fiction Benchmark Equivalent -->
                <div class="ova-stats-kpi-card">
                    <div class="ova-stats-kpi-label">
                        <i class="ph-fill ph-scales"></i> Contemporary Novel Equivalent
                    </div>
                    <div class="ova-stats-kpi-value"><?= number_format($totalNovelEquiv, 1) ?>&times; <span style="font-size: 0.48em; font-weight: 700; color: #f6d5a8;">90k novels</span></div>
                    <div class="ova-stats-kpi-sub">
                        Measured against the standard <strong>90,000-word</strong> contemporary trade novel benchmark (or <strong><?= number_format($totalCombinedEquiv, 1) ?> full-length novels</strong> when combining story prose and curated lore).
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="ova-stats-container">
        <!-- INDUSTRY BENCHMARK SCALE REFERENCE -->
        <section class="ova-scale-benchmarks-card" aria-labelledby="ova-scale-ref-heading">
            <div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 0.5rem;">
                <h2 id="ova-scale-ref-heading" style="font-size: 1.15rem; font-weight: 800; color: var(--rs-heading); margin: 0;">
                    <i class="ph-fill ph-ruler" style="color: var(--rs-primary);"></i> Standard Contemporary Publishing Length Classifications
                </h2>
                <span style="font-size: 0.8rem; color: var(--rs-text-muted);">
                    Page counts calculated at the standard 275 words/page trade paperback typesetting rate
                </span>
            </div>
            <div class="ova-scale-tiers-grid">
                <div class="ova-scale-tier-box">
                    <div class="ova-scale-tier-name">Short Fiction / Novelette</div>
                    <div class="ova-scale-tier-range">Under 17,500 words</div>
                    <div class="ova-scale-tier-note">~5–65 pages &bull; Single-sitting vignette</div>
                </div>
                <div class="ova-scale-tier-box">
                    <div class="ova-scale-tier-name">Novella</div>
                    <div class="ova-scale-tier-range">17,500 – 40,000 words</div>
                    <div class="ova-scale-tier-note">~65–145 pages &bull; Focused arc</div>
                </div>
                <div class="ova-scale-tier-box">
                    <div class="ova-scale-tier-name">Compact Novel</div>
                    <div class="ova-scale-tier-range">40,000 – 70,000 words</div>
                    <div class="ova-scale-tier-note">~145–255 pages &bull; Lean trade paperback</div>
                </div>
                <div class="ova-scale-tier-box">
                    <div class="ova-scale-tier-name">Full-Length Contemporary Novel</div>
                    <div class="ova-scale-tier-range">70,000 – 110,000 words</div>
                    <div class="ova-scale-tier-note">~255–400 pages &bull; 90k industry standard</div>
                </div>
                <div class="ova-scale-tier-box">
                    <div class="ova-scale-tier-name">Epic Full-Length Novel</div>
                    <div class="ova-scale-tier-range">110,000 – 200,000 words</div>
                    <div class="ova-scale-tier-note">~400–725 pages &bull; Expansive saga</div>
                </div>
                <div class="ova-scale-tier-box">
                    <div class="ova-scale-tier-name">Multi-Volume Epic Omnibus</div>
                    <div class="ova-scale-tier-range">200,000+ words</div>
                    <div class="ova-scale-tier-note">725–2,000+ pages &bull; 2 to 6 print volumes</div>
                </div>
            </div>
        </section>

        <!-- NOVEL-BY-NOVEL BREAKDOWN TABLE -->
        <section aria-labelledby="ova-novel-ledger-heading">
            <div class="ova-stats-toolbar">
                <div>
                    <h2 id="ova-novel-ledger-heading" style="font-size: 1.35rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.25rem 0;">
                        Volume-by-Volume Word Count &amp; Scale Breakdown (<?= $volumeCount ?> Volumes)
                    </h2>
                    <p style="font-size: 0.85rem; color: var(--rs-text-muted); margin: 0;">
                        Showing <span style="color: var(--rs-primary); font-weight: 700;">■ Story Prose</span> + <span style="color: var(--rs-secondary); font-weight: 700;">■ Series &amp; Companion Album Lore</span> for each volume.
                    </p>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center;">
                    <div class="ova-stats-filter-group" role="group" aria-label="Filter volumes by publishing scale">
                        <button type="button" class="ova-stats-filter-btn is-active" data-filter="all">All (<?= count($booksList) ?>)</button>
                        <button type="button" class="ova-stats-filter-btn" data-filter="omnibus">Omnibus 200k+</button>
                        <button type="button" class="ova-stats-filter-btn" data-filter="novel">Novels 40k–200k</button>
                        <button type="button" class="ova-stats-filter-btn" data-filter="novella">Novellas &amp; Short Works</button>
                    </div>
                    <input type="search" id="ova-stats-search" class="ova-stats-search-input" placeholder="Filter by title or scale..." aria-label="Filter novels by title or scale">
                </div>
            </div>

            <div class="ova-stats-table-wrap">
                <table class="ova-stats-table" id="ova-stats-ledger-table">
                    <thead>
                        <tr>
                            <th class="col-rank">#</th>
                            <th class="col-vol">Novel Volume</th>
                            <th class="num col-story">Story Prose</th>
                            <th class="num col-lore">Series Lore</th>
                            <th class="num col-total">Combined Total</th>
                            <th class="num col-pages">Pages &amp; Time</th>
                            <th class="col-scale">Contemporary Publishing Scale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booksList as $idx => $bRow):
                            $bSlug    = (string)($bRow['slug'] ?? '');
                            $bTitle   = (string)($bRow['title'] ?? $bSlug);
                            $bImg     = !empty($bRow['image']) ? ($cdnBaseUrl . $bRow['image']) : ($cdnBaseUrl . '/raggiesoft-books/images/covers/og/' . $bSlug . '.jpg');
                            $ws       = $bRow['word_stats'] ?? [];
                            $sWords   = (int)($ws['story_word_count'] ?? 0);
                            $lWords   = (int)($ws['lore_word_count'] ?? 0);
                            $cWords   = (int)($ws['combined_word_count'] ?? ($sWords + $lWords));
                            $cPages   = (int)($ws['combined_page_count'] ?? max(1, (int)round($cWords / 275)));
                            $sPages   = (int)($ws['story_page_count'] ?? ($sWords > 0 ? max(1, (int)round($sWords / 275)) : 0));
                            $rHours   = (float)($ws['reading_hours'] ?? round($sWords / 15000, 1));
                            $rMins    = (int)($ws['reading_minutes'] ?? max(1, (int)round($sWords / 250)));
                            $erasCnt  = (int)($ws['era_count'] ?? 0);
                            $chapsCnt = (int)($ws['chapter_count'] ?? 0);
                            $partsCnt = (int)($ws['part_count'] ?? 0);
                            $illusCnt = (int)($ws['illustration_count'] ?? 0);
                            $badge    = (string)($ws['scale_badge'] ?? 'Novel');
                            $note     = (string)($ws['benchmark_note'] ?? '');

                            $tierKey = 'novella';
                            if ($sWords >= 200000) {
                                $tierKey = 'omnibus';
                            } elseif ($sWords >= 40000) {
                                $tierKey = 'novel';
                            }

                            $storyBarPct = $maxBookCombined > 0 ? round(($sWords / $maxBookCombined) * 100, 2) : 0;
                            $loreBarPct  = $maxBookCombined > 0 ? round(($lWords / $maxBookCombined) * 100, 2) : 0;
                            $timeLabel   = $sWords === 0
                                ? 'Lore Outline'
                                : ($rHours >= 1.0 ? number_format($rHours, 1) . ' hrs' : $rMins . ' min');
                        ?>
                        <tr class="ova-stats-row" data-tier="<?= htmlspecialchars($tierKey) ?>" data-search="<?= htmlspecialchars(strtolower($bTitle . ' ' . $bSlug . ' ' . $badge . ' ' . $note)) ?>">
                            <td class="cell-rank"><?= $idx + 1 ?></td>
                            <td class="cell-vol">
                                <div class="ova-stats-book-cell">
                                    <img src="<?= htmlspecialchars($bImg) ?>" alt="" class="ova-stats-book-thumb" loading="lazy">
                                    <div style="flex: 1; min-width: 0;">
                                        <a href="/<?= htmlspecialchars($bSlug) ?>" class="ova-stats-book-link">
                                            <span class="ova-mobile-rank-pill">#<?= $idx + 1 ?></span><?= htmlspecialchars($bTitle) ?>
                                        </a>
                                        <div class="ova-stats-book-meta">
                                            <?= $erasCnt ?> <?= $erasCnt === 1 ? 'Era' : 'Eras' ?> &bull;
                                            <?= $chapsCnt ?> <?= $chapsCnt === 1 ? 'Chap' : 'Chaps' ?> &bull;
                                            <?= $partsCnt ?> <?= $partsCnt === 1 ? 'Part' : 'Parts' ?>
                                            <?php if ($illusCnt > 0): ?>
                                                &bull; <a href="/<?= htmlspecialchars($bSlug) ?>/illustrations" style="color: var(--rs-primary); font-weight: 700; text-decoration: none;"><?= $illusCnt ?> Plates</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ova-stats-bar-track" aria-hidden="true" title="Story: <?= number_format($sWords) ?> words | Lore: <?= number_format($lWords) ?> words">
                                            <div class="ova-stats-bar-story" style="width: <?= $storyBarPct ?>%;"></div>
                                            <div class="ova-stats-bar-lore" style="width: <?= $loreBarPct ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="num cell-metric" data-label="Story Prose" style="font-weight: 800; color: var(--rs-heading);">
                                <?= number_format($sWords) ?>
                            </td>
                            <td class="num cell-metric" data-label="Series Lore" style="color: var(--rs-text); opacity: 0.9;">
                                <?= number_format($lWords) ?>
                            </td>
                            <td class="num cell-metric" data-label="Combined Total" style="font-weight: 800; color: var(--rs-primary);">
                                <?= number_format($cWords) ?>
                            </td>
                            <td class="num cell-pages">
                                <div>
                                    <span style="font-weight: 700; color: var(--rs-heading);">~<?= number_format($cPages) ?> pp</span>
                                    <span style="color: var(--rs-text-muted);"> &bull; <?= htmlspecialchars($timeLabel) ?></span>
                                    <?php if ($sPages > 0 && $sPages !== $cPages): ?>
                                        <div style="font-size: 0.72rem; color: var(--rs-text-muted);">(<?= number_format($sPages) ?> story pp)</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="cell-scale">
                                <span class="ova-scale-badge"><?= htmlspecialchars($badge) ?></span>
                                <div style="font-size: 0.74rem; color: var(--rs-text-muted); margin-top: 0.22rem; line-height: 1.35;">
                                    <?= htmlspecialchars($note) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SHARED UNIVERSE & ENGINE ROOM RECORDS LORE PILLARS -->
        <?php if (!empty($sharedLoreBreakdown)): ?>
        <section aria-labelledby="ova-shared-lore-heading">
            <h2 id="ova-shared-lore-heading" style="font-size: 1.3rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.35rem 0;">
                <i class="ph-fill ph-globe-hemisphere-west" style="color: var(--rs-primary);"></i> Shared Universe Lore &amp; Engine Room Records Music Archive
            </h2>
            <p style="font-size: 0.88rem; color: var(--rs-text-muted); margin: 0 0 1rem 0;">
                Cross-series worldbuilding archives, institutional charters, transit networks, and companion soundtrack lyrics shared across the RaggieSoft universe.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.9rem;">
                <?php foreach ($sharedLoreBreakdown as $sItem): ?>
                <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1rem 1.15rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.4rem;">
                    <div style="font-size: 0.86rem; font-weight: 700; color: var(--rs-heading); line-height: 1.35;">
                        <?= htmlspecialchars((string)($sItem['label'] ?? '')) ?>
                    </div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: var(--rs-primary); font-variant-numeric: tabular-nums;">
                        <?= number_format((int)($sItem['words'] ?? 0)) ?> <span style="font-size: 0.75rem; font-weight: 700; color: var(--rs-text-muted);">words</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <div style="padding-top: 1rem; border-top: 1px solid var(--rs-border); font-size: 0.8rem; color: var(--rs-text-muted); line-height: 1.5; text-align: center;">
            <strong>Work of Fiction Disclaimer:</strong> All novels, characters, bands, universities (including Commonwealth Polytechnic Institute / CPI), corporations (including OmniCorp, Axiom, Apex Records, Prairie Coachworks, and Sainte-Claire), transit agencies (Blue Ridge Transit), and events depicted in the Ocean View Archives are works of fiction. Any resemblance to actual persons, living or dead, businesses, institutions, or events is purely coincidental.
        </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>

<script>
(function() {
    var filterBtns = document.querySelectorAll('.ova-stats-filter-btn');
    var searchInput = document.getElementById('ova-stats-search');
    var rows = document.querySelectorAll('.ova-stats-row');
    var activeFilter = 'all';

    function applyTableFilters() {
        var q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        rows.forEach(function(row) {
            var tier = row.getAttribute('data-tier') || '';
            var hay = row.getAttribute('data-search') || '';
            var matchTier = (activeFilter === 'all') || (tier === activeFilter);
            var matchQuery = (q === '') || (hay.indexOf(q) !== -1);
            row.style.display = (matchTier && matchQuery) ? '' : 'none';
        });
    }

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterBtns.forEach(function(b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            activeFilter = btn.getAttribute('data-filter') || 'all';
            applyTableFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyTableFilters);
    }
})();
</script>

