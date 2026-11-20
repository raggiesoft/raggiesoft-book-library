<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/story-arcs.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `https://books.raggiesoft.com/arcs` (and `/story-arcs`)
 *     — the library-wide "Story Arcs & Reading Playlists" hub.
 *     Think of a Story Arc like a music playlist for a novel: instead of reading
 *     every single chapter of a massive multi-era book in order, a reader can
 *     follow one specific character storyline (e.g., Heather, Wesley, and Chelsea's
 *     journey in *Katie & Nathaniel*) chronologically across the years, complete
 *     with interleaved behind-the-scenes "Bonus Scenes" from RaggieSoft Backstage!
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php`, `paige.php`, & `reader.js`:
 *     1. Build Time (`paige.php`): Reads each novel's `arcs.json` and compiles a
 *        unified manifest into `data/story-arcs.json` (and CDN `json/story-arcs.json`).
 *     2. Server Side (`isabel.php` -> `story-arcs.php`): When `$requestUri` is
 *        `/arcs` or `/story-arcs`, `isabel.php` sets `$isStoryArcs = true` and
 *        includes this file. Here in PHP, we load `story-arcs.json`, tally total
 *        chapters and bonus stops, group arcs by book series for the filter bar,
 *        and honor optional `?book=<slug>` deep-links (e.g. `/arcs?book=katie`).
 *     3. Client Side (`reader.js`):
 *          - Clicking a `[data-arc-filter]` pill filters `.rs-arc-card` elements
 *            instantly in the browser via `addEventListener()` and updates `aria-pressed`.
 *          - Clicking "Start Story Arc" opens the first chapter with `?arc=<slug>`,
 *            activating Story Arc Playlist Mode inside `sophia/oliver.php`!
 *          - Clicking a `[data-backstage-slug]` Bonus Scene button triggers a
 *            cross-origin (CORS) `fetch()` to `https://backstage.raggiesoft.com/api/v1/<slug>.json`
 *            (with CDN fallback) and displays the rendered HTML inside the native
 *            `<dialog id="rs-backstage-dialog" closedby="any">` modal at the bottom!
 *
 * 100-LEVEL MODERN HTML FEATURES TO NOTICE HERE:
 *     - `<details class="rs-arc-disclosure" name="hub-story-arcs">`: Giving multiple
 *       `<details>` elements the same `name` attribute creates a native HTML5
 *       "exclusive accordion" (opening one stop list automatically closes the
 *       previously open one—zero JavaScript required!), while keeping collapsed
 *       text searchable via Ctrl+F / Cmd+F Find-in-Page!
 *     - `<dialog closedby="any">`: Modern declarative HTML attribute allowing
 *       users to close the modal either by pressing `Esc` OR clicking the backdrop!
 * =============================================================================
 */

// Import `$cdnBaseUrl` and `$basePath` (filesystem root of the `books` app) from `isabel.php`.
global $cdnBaseUrl, $basePath;

// Step 1: Load the pre-compiled Story Arcs JSON manifest.
// We check local NVMe disk (`data/story-arcs.json`) first for zero-latency reads,
// and fall back to the public CDN if the local file is absent.
$storyArcsLocalPath = ($basePath ?? dirname(__DIR__)) . '/data/story-arcs.json';
$storyArcsRaw = '';
if (is_file($storyArcsLocalPath)) {
    $storyArcsRaw = (string)@file_get_contents($storyArcsLocalPath);
}
if ($storyArcsRaw === '' && !empty($cdnBaseUrl)) {
    $storyArcsRaw = (string)@file_get_contents($cdnBaseUrl . '/raggiesoft-books/json/story-arcs.json');
}
// Decode JSON into a PHP associative array and extract the `arcs` list safely.
$storyArcsDoc  = json_decode($storyArcsRaw, true) ?: [];
$allStoryArcs  = is_array($storyArcsDoc['arcs'] ?? null) ? $storyArcsDoc['arcs'] : [];

// Step 2: Aggregate per-book filter counts and grand totals for the hero KPI strip.
$bookSeriesFilterMap = [];
$totalArcChapterStops = 0;
$totalArcBonusStops   = 0;
foreach ($allStoryArcs as $arcItem) {
    $bSlug  = (string)($arcItem['series_slug'] ?? '');
    $bTitle = (string)($arcItem['series_title'] ?? ucwords(str_replace('-', ' ', $bSlug)));
    if ($bSlug !== '') {
        if (!isset($bookSeriesFilterMap[$bSlug])) {
            $bookSeriesFilterMap[$bSlug] = [
                'slug'  => $bSlug,
                'title' => $bTitle,
                'count' => 0,
            ];
        }
        $bookSeriesFilterMap[$bSlug]['count']++;
    }
    $totalArcChapterStops += (int)($arcItem['part_count'] ?? 0);
    $totalArcBonusStops   += (int)($arcItem['backstage_count'] ?? 0);
}

// Step 3: Check whether the URL includes `?book=<slug>` (for instance, when clicked from
// `sidebar.php` or `landing.php`). Sanitize input strictly to alphanumeric/dash/underscore
// characters, and reset to `''` (show all) if the slug doesn't match any book with Story Arcs.
$requestedBookFilter = isset($_GET['book']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$_GET['book']) : '';
if ($requestedBookFilter !== '' && !isset($bookSeriesFilterMap[$requestedBookFilter])) {
    $requestedBookFilter = '';
}
?>

<!-- Primary `<main>` landmark (`tabindex="-1"` lets keyboard skip-links move focus here cleanly) -->
<main id="stardust-reading-pane" tabindex="-1" class="rs-platform-main">
    <div class="book-page rs-arcs-hub-shell">
        <!-- HERO BANNER & SUMMARY STAT PILLS -->
        <header class="rs-arcs-hero">
            <div class="rs-arcs-kicker">
                <i class="ph-fill ph-playlist" aria-hidden="true"></i>
                <span>Ocean View Archives &bull; Reading Playlists</span>
            </div>
            <h1 class="rs-arcs-hero-title">Follow a Story Arc</h1>
            <p class="rs-arcs-hero-lead">
                Want to follow one character&rsquo;s journey or a favorite storyline from beginning to end? Choose a curated
                <strong>Story Arc</strong> below and step straight from chapter to chapter across the years&mdash;complete with
                behind-the-scenes bonus scenes from <strong>RaggieSoft Backstage</strong> along the way.
            </p>

            <!-- Summary counts formatted with `number_format()` (e.g., `1,234`) -->
            <div class="rs-arcs-stat-strip" role="list" aria-label="Story Arc Library Summary">
                <div class="rs-arcs-stat-pill" role="listitem">
                    <strong><?php echo number_format(count($allStoryArcs)); ?></strong>
                    <span>Curated Story Arcs</span>
                </div>
                <div class="rs-arcs-stat-pill" role="listitem">
                    <strong><?php echo number_format(count($bookSeriesFilterMap)); ?></strong>
                    <span>Featured Books</span>
                </div>
                <div class="rs-arcs-stat-pill" role="listitem">
                    <strong><?php echo number_format($totalArcChapterStops); ?></strong>
                    <span>Reading Chapters</span>
                </div>
                <div class="rs-arcs-stat-pill" role="listitem">
                    <strong><?php echo number_format($totalArcBonusStops); ?></strong>
                    <span>Bonus Backstage Scenes</span>
                </div>
            </div>
        </header>

        <?php if (count($bookSeriesFilterMap) > 1): ?>
        <!--
          BOOK SERIES FILTER BAR:
          - Only rendered when 2+ novels have Story Arcs.
          - Each `<button>` carries `data-arc-filter="<slug>"` and `aria-pressed="true|false"`.
          - `reader.js` listens for clicks on `.rs-arc-filter-btn` via event delegation and toggles
            the `hidden` attribute on `.rs-arc-card[data-arc-series]` cards without reloading the page.
        -->
        <nav class="rs-arcs-filter-bar" aria-label="Filter Story Arcs by Book">
            <button type="button"
                    class="rs-btn rs-arc-filter-btn<?php echo $requestedBookFilter === '' ? ' is-active' : ''; ?>"
                    data-arc-filter="all"
                    aria-pressed="<?php echo $requestedBookFilter === '' ? 'true' : 'false'; ?>">
                <span>All Story Arcs</span>
                <span class="rs-arc-filter-count"><?php echo count($allStoryArcs); ?></span>
            </button>
            <?php foreach ($bookSeriesFilterMap as $bFilter): ?>
            <button type="button"
                    class="rs-btn rs-arc-filter-btn<?php echo $requestedBookFilter === $bFilter['slug'] ? ' is-active' : ''; ?>"
                    data-arc-filter="<?php echo htmlspecialchars($bFilter['slug']); ?>"
                    aria-pressed="<?php echo $requestedBookFilter === $bFilter['slug'] ? 'true' : 'false'; ?>">
                <span><?php echo htmlspecialchars($bFilter['title']); ?></span>
                <span class="rs-arc-filter-count"><?php echo (int)$bFilter['count']; ?></span>
            </button>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <!-- STORY ARCS CARD GRID -->
        <div class="rs-arcs-grid" id="rs-arcs-hub-grid">
            <?php foreach ($allStoryArcs as $arc):
                // Extract and normalize fields from each compiled Story Arc object
                $arcSlug       = (string)($arc['slug'] ?? '');
                $arcSeriesSlug = (string)($arc['series_slug'] ?? '');
                $arcSeriesName = (string)($arc['series_title'] ?? '');
                $arcTitle      = (string)($arc['title'] ?? '');
                $arcSubtitle   = (string)($arc['subtitle'] ?? '');
                $arcDesc       = (string)($arc['description'] ?? '');
                $rawImg        = (string)($arc['image'] ?? '');
                // Prefix relative CDN paths with `$cdnBaseUrl`, or pass full `https://` URLs through unchanged
                $arcImgUrl     = (str_starts_with($rawImg, 'http://') || str_starts_with($rawImg, 'https://'))
                    ? $rawImg
                    : rtrim((string)$cdnBaseUrl, '/') . '/' . ltrim($rawImg, '/');
                // `$arcStartUrl` points to Stop 1 with `?arc=<arcSlug>` appended so `oliver.php` enters Playlist Mode
                $arcStartUrl   = (string)($arc['start_url'] ?? ('/' . $arcSeriesSlug));
                $arcBsUrl      = (string)($arc['backstage_playlist_url'] ?? 'https://backstage.raggiesoft.com/arcs/');
                $partCount     = (int)($arc['part_count'] ?? 0);
                $bsCount       = (int)($arc['backstage_count'] ?? 0);
                $readHours     = (float)($arc['reading_hours'] ?? 0);
                $readMins      = (int)($arc['reading_minutes'] ?? 1);
                // Display hours (`~1.5 hrs`) when >= 60 minutes, or minutes (`~25 min`) for shorter arcs
                $readLabel     = $readHours >= 1.0 ? ('~' . number_format($readHours, 1) . ' hrs') : ('~' . $readMins . ' min');
                $stops         = is_array($arc['stops'] ?? null) ? $arc['stops'] : [];
                // Pre-hide non-matching cards on the server if `?book=<slug>` was passed in the URL
                $isHiddenInit  = ($requestedBookFilter !== '' && $requestedBookFilter !== $arcSeriesSlug);
            ?>
            <article class="rs-arc-card"
                     id="arc-<?php echo htmlspecialchars($arcSeriesSlug . '-' . $arcSlug); ?>"
                     data-arc-series="<?php echo htmlspecialchars($arcSeriesSlug); ?>"
                     <?php if ($isHiddenInit): ?>hidden<?php endif; ?>>
                <!-- Decorative 16:9 thumbnail (`tabindex="-1" aria-hidden="true"` avoids duplicate screen-reader link announcements alongside the `<h2>` title link below) -->
                <a href="<?php echo htmlspecialchars($arcStartUrl); ?>" class="rs-arc-card-media" tabindex="-1" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($arcImgUrl); ?>"
                         alt=""
                         width="800"
                         height="450"
                         loading="lazy"
                         decoding="async">
                    <span class="rs-arc-card-series-pill"><?php echo htmlspecialchars($arcSeriesName); ?></span>
                </a>

                <div class="rs-arc-card-body">
                    <?php if ($arcSubtitle !== ''): ?>
                    <div class="rs-arc-card-subtitle"><?php echo htmlspecialchars($arcSubtitle); ?></div>
                    <?php endif; ?>

                    <h2 class="rs-arc-card-title">
                        <a href="<?php echo htmlspecialchars($arcStartUrl); ?>"><?php echo htmlspecialchars($arcTitle); ?></a>
                    </h2>

                    <p class="rs-arc-card-desc"><?php echo htmlspecialchars($arcDesc); ?></p>

                    <!-- Metadata chips: Chapter count, Bonus Backstage scene count, and estimated reading time -->
                    <div class="rs-arc-card-meta">
                        <span><i class="ph-fill ph-book-open-text" aria-hidden="true"></i> <?php echo $partCount; ?> <?php echo $partCount === 1 ? 'Chapter' : 'Chapters'; ?></span>
                        <?php if ($bsCount > 0): ?>
                        <span><i class="ph-fill ph-film-slate" aria-hidden="true"></i> <?php echo $bsCount; ?> Bonus <?php echo $bsCount === 1 ? 'Scene' : 'Scenes'; ?></span>
                        <?php endif; ?>
                        <span><i class="ph ph-clock" aria-hidden="true"></i> <?php echo htmlspecialchars($readLabel); ?></span>
                    </div>

                    <!-- Card CTA Row: Start Playlist (`?arc=`), Open Full Book, or Open Director's Cut Guide on Backstage -->
                    <div class="rs-arc-card-actions">
                        <a href="<?php echo htmlspecialchars($arcStartUrl); ?>" class="rs-btn rs-btn-primary rs-arc-start-btn">
                            <i class="ph-fill ph-play-circle" aria-hidden="true"></i>
                            <span>Start Story Arc</span>
                        </a>
                        <a href="/<?php echo htmlspecialchars($arcSeriesSlug); ?>" class="rs-btn">
                            <i class="ph ph-book" aria-hidden="true"></i>
                            <span>Full Book</span>
                        </a>
                        <!-- `data-backstage-direct="true"` tells `reader.js` to open `backstage.raggiesoft.com/arcs/...` in a new tab instead of intercepting it into the single-dossier modal -->
                        <a href="<?php echo htmlspecialchars($arcBsUrl); ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           data-no-spa="true"
                           data-backstage-direct="true"
                           class="rs-btn"
                           title="Open the Director's Cut Story Arc guide on RaggieSoft Backstage">
                            <i class="ph ph-film-slate" aria-hidden="true"></i>
                            <span>Backstage Guide</span>
                            <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                        </a>
                    </div>

                    <?php if (!empty($stops)): ?>
                    <!-- Collapsible Stop-by-Stop Playlist (`<details name="hub-story-arcs">` forms a zero-JS exclusive accordion) -->
                    <details class="rs-arc-disclosure" name="hub-story-arcs">
                        <summary class="rs-arc-disclosure-summary">
                            <span><i class="ph ph-list-numbers" aria-hidden="true"></i> View All <?php echo count($stops); ?> Stops in This Story Arc</span>
                            <i class="ph-bold ph-caret-down rs-arc-disclosure-caret" aria-hidden="true"></i>
                        </summary>
                        <ol class="rs-arc-stop-list">
                            <?php foreach ($stops as $stop):
                                // Each stop is either `'part'` (a chapter inside `books.raggiesoft.com`)
                                // or `'backstage'` (a bonus behind-the-scenes dossier from `backstage.raggiesoft.com`).
                                $sKind    = (string)($stop['kind'] ?? 'part');
                                $sNum     = (int)($stop['stop_num'] ?? 0);
                                $sTitle   = (string)($stop['title'] ?? '');
                                $sSub     = (string)($stop['subtitle'] ?? '');
                                $sDate    = (string)($stop['date'] ?? '');
                                $sMins    = (int)($stop['reading_minutes'] ?? 1);
                                $sArcUrl  = (string)($stop['arc_url'] ?? '#');
                                $sBsSlug  = (string)($stop['backstage_slug'] ?? '');
                                $sExtUrl  = (string)($stop['url'] ?? '#');
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
                                    <?php if ($sKind === 'backstage' && $sBsSlug !== ''): ?>
                                        <!-- Clicking this `<button>` triggers `reader.js` to `fetch('https://backstage.raggiesoft.com/api/v1/' + slug + '.json')` and open `#rs-backstage-dialog` right on this page! -->
                                        <button type="button"
                                                class="rs-arc-stop-link-btn"
                                                data-backstage-slug="<?php echo htmlspecialchars($sBsSlug); ?>">
                                            <?php echo htmlspecialchars($sTitle); ?>
                                        </button>
                                    <?php else: ?>
                                        <!-- Clicking a chapter stop opens `oliver.php` at that chapter with `?arc=<arcSlug>` active -->
                                        <a href="<?php echo htmlspecialchars($sArcUrl); ?>" class="rs-arc-stop-link">
                                            <?php echo htmlspecialchars($sTitle); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($sSub !== ''): ?>
                                        <div class="rs-arc-stop-sub"><?php echo htmlspecialchars($sSub); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($sKind === 'backstage'): ?>
                                    <!-- Direct external bounce button opening the full Backstage web app in a new tab -->
                                    <a href="<?php echo htmlspecialchars($sExtUrl); ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       data-no-spa="true"
                                       data-backstage-direct="true"
                                       class="rs-btn rs-arc-stop-mini-btn"
                                       title="Open in RaggieSoft Backstage">
                                        <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                                    </a>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!--
          SHARED RAGGIESOFT BACKSTAGE QUICK-VIEW MODAL (`<dialog id="rs-backstage-dialog">`):
          Mounted right inside `/arcs` with the exact same DOM IDs as `oliver.php` so `reader.js`
          can fetch JSON from `https://backstage.raggiesoft.com/api/v1/<slug>.json` via CORS and
          let readers preview any Bonus Scene inline without losing their place in the hub!
        -->
        <dialog id="rs-backstage-dialog" closedby="any" aria-labelledby="rs-backstage-dialog-title" style="padding: 0; border: 1px solid var(--rs-border); border-radius: 14px; background: var(--rs-bg); color: var(--rs-text); box-shadow: 0 16px 48px rgba(0,0,0,0.45); max-width: 820px; width: 92%; max-height: 86vh; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--rs-border); background: var(--rs-surface);">
                <div>
                    <div id="rs-backstage-dialog-kicker" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--rs-primary);">
                        🎬 RaggieSoft Backstage Archive
                    </div>
                    <h3 id="rs-backstage-dialog-title" style="margin: 0.15rem 0 0; font-size: 1.22rem; font-weight: 800;">Backstage Bonus Scene</h3>
                </div>
                <button type="button" id="rs-backstage-dialog-close" class="rs-btn" aria-label="Close Backstage dialog" title="Close Backstage dialog" style="padding: 0.45rem 0.75rem; font-size: 1rem; cursor: pointer;">
                    <i class="ph ph-x" aria-hidden="true"></i> Close
                </button>
            </div>
            <!-- Optional sender/date/location header bar populated dynamically when JSON arrives -->
            <div id="rs-backstage-dialog-meta" style="padding: 0.65rem 1.5rem; border-bottom: 1px solid var(--rs-border); background: var(--rs-surface); font-size: 0.85rem; opacity: 0.9; display: none;"></div>
            <!-- Scrollable rendered dossier body (`data.html` from Backstage API v1) -->
            <div id="rs-backstage-dialog-body" class="story-content" style="padding: 1.5rem; overflow-y: auto; max-height: calc(86vh - 165px); font-size: 1.02rem; line-height: 1.68; text-indent: 0;"></div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 0.85rem 1.5rem; border-top: 1px solid var(--rs-border); background: var(--rs-surface); font-size: 0.85rem;">
                <span style="color: var(--rs-text-muted);">100% In-Universe Bonus Feature &bull; RaggieSoft Backstage Archive</span>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a id="rs-backstage-dialog-bounce" href="https://backstage.raggiesoft.com" target="_blank" rel="noopener noreferrer" data-backstage-direct="true" class="rs-btn rs-btn-primary" style="padding: 0.45rem 0.95rem; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="ph ph-film-slate" aria-hidden="true"></i>
                        <span>Open on RaggieSoft Backstage</span>
                        <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </dialog>
    </div>
</main>
