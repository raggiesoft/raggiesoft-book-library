<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/illustrations.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This sub-view renders the dedicated per-book Table of Illustrations page
 *     for a single novel (for example: `https://books.raggiesoft.com/rachel/illustrations`
 *     or `https://books.raggiesoft.com/aubrie/illustrations`).
 *     (Compare this with `sophia/all-illustrations.php`, which renders `/illustrations`
 *     across ALL novels in the entire library at once!)
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php` & `sophia/oliver.php`:
 *     1. In `data/routes/<series>.json`, `/<series>/illustrations` maps to the
 *        special `filePath` token `'__ILLUSTRATIONS__'`.
 *     2. `eleanor/isabel.php` loads `$toc_manifest` (`toc.json`), `$cdnBaseUrl`,
 *        and `$seriesSlug`, promotes the book's first illustrated plate into
 *        `og:image` for social sharing, and renders `sophia/sidebar.php` (which
 *        creates `$fileToUrl`).
 *     3. `sophia/oliver.php` sees `$actualFilePath === '__ILLUSTRATIONS__'`,
 *        sets `$specialPageType = 'illustrations'`, captures this file's HTML
 *        inside `ob_start()`, and places it inside `#stardust-reading-pane`.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - The "Table of Illustrations" `<h1>` heading naming the current book.
 *     - Era jump-navigation pills, 16:9 high-resolution scene artwork cards in
 *       chronological reading order, "Read Scene" links, and a full-screen
 *       `<dialog>` lightbox—all rendered by the shared reusable component
 *       `includes/components/illustrations-toc.php`.
 *
 * 100-LEVEL ARCHITECTURE CONCEPT — DRY COMPONENT REUSE:
 *     - "DRY" stands for "Don't Repeat Yourself." Because the exact same book
 *       illustration gallery is ALSO embedded inside a collapsible `<details>`
 *       drawer at the top of `sophia/toc.php`, the gallery markup lives in one
 *       shared component (`includes/components/illustrations-toc.php`).
 *     - Setting `$illustrationsTocMode = 'page'` right before `require` tells
 *       that shared component to render in full-page standalone mode rather
 *       than compact embedded mode!
 * =============================================================================
 */

// Import series manifest, CDN base URL, active book slug, and file-to-URL map from `isabel.php` & `sidebar.php`.
global $toc_manifest, $cdnBaseUrl, $seriesSlug, $fileToUrl;

// Resolve the human-readable book title from `toc.json` (or prettify the URL slug, e.g. `stardust-engine` -> `Stardust Engine`).
$seriesTitleForIllus = $toc_manifest['series_title'] ?? ucwords(str_replace('-', ' ', (string)$seriesSlug));
?>
<!-- Per-book Table of Illustrations page container rendered inside `oliver.php` -->
<div class="book-page" style="max-width: 960px; margin: 0 auto;">
    <!-- Page Header: Title + Subtitle naming the current novel (uses short-echo tag shorthand below to print the escaped title) -->
    <div style="margin-bottom: 1.5rem; border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">
        <h1 style="margin: 0 0 0.45rem 0; color: var(--rs-primary);">Table of Illustrations</h1>
        <p style="margin: 0; font-size: 0.96rem; color: var(--rs-text-muted);">
            Visual scene index and high-resolution artwork gallery for <strong><?= htmlspecialchars($seriesTitleForIllus) ?></strong>.
        </p>
    </div>

    <?php
    // Configure the shared gallery component to render in full standalone `'page'` mode
    // (as opposed to `'embedded'` mode inside `toc.php`'s `<details>` accordion).
    $illustrationsTocMode = 'page';
    require __DIR__ . '/../includes/components/illustrations-toc.php';
    ?>
</div>
