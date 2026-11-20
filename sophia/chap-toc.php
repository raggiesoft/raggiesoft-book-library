<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/chap-toc.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This sub-view renders a scoped, single-Chapter Table of Contents page
 *     (for example: `https://books.raggiesoft.com/katie/<era-slug>/<chapter-slug>`)
 *     listing every scene Part (`p001.md`, `p002.md`, ...) inside one Chapter.
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php` & `sophia/oliver.php`:
 *     1. In `data/routes/<series>.json` (compiled by `paige.php`), a Chapter
 *        overview URL maps to a special internal `filePath` token:
 *          - Standard chapter: `__CHAP_TOC__|<eraIndex>|<chapIndex>`
 *          - Story Crossroads branch chapter: `__CHAP_TOC__|<pathId>|<eraIndex>|<chapIndex>`
 *     2. `eleanor/isabel.php` loads `$toc_manifest` (`toc.json`), resolves active
 *        Story Crossroads segments into `$effectiveTocEras`, and loads
 *        `sophia/sidebar.php` (which builds the `$fileToUrl` reverse lookup map).
 *     3. `sophia/oliver.php` parses the `__CHAP_TOC__|...` token into
 *        `$specialPagePathId`, `$specialPageIndex1` (0-based Era index), and
 *        `$specialPageIndex2` (0-based Chapter index), turns on PHP output
 *        buffering (`ob_start()`), and `require_once`s this file so its HTML
 *        is injected seamlessly inside `#stardust-reading-pane`!
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - A clickable parent Era link above the Chapter `<h1>` heading so the
 *       reader can move "up" one hierarchy level to the Era Overview (`era-toc.php`).
 *     - A card listing every Part in the chapter in chronological order, plus an
 *       `"Illustrated"` pill badge whenever that Part includes scene artwork.
 *
 * 100-LEVEL PHP SECURITY CONCEPT TO NOTICE HERE:
 *     - Every string echoed into HTML attributes or text (`$parentEraHref`,
 *       `$era['era_title']`, `$chap['chap_title']`, `$href`, `$part['part_title']`)
 *       is wrapped in `htmlspecialchars()`. That converts special characters like
 *       `<`, `>`, `"`, and `&` into safe HTML entities so book metadata can never
 *       accidentally break HTML tags or cause Cross-Site Scripting (XSS).
 * =============================================================================
 */

// Import global variables populated upstream by `isabel.php`, `sidebar.php`, and `oliver.php`.
global $toc_manifest, $effectiveTocEras, $specialPagePathId, $specialPageIndex1, $specialPageIndex2, $fileToUrl;

// Step 1: Locate the requested Era dictionary (`$era`).
// - If `$specialPagePathId` is set, this chapter lives inside a specific Story Crossroads
//   branch under `$toc_manifest['paths'][$specialPagePathId]['eras']`.
// - Otherwise, read from `$effectiveTocEras` (the active merged reading path) or raw `$toc_manifest['eras']`.
if (!empty($specialPagePathId) && isset($toc_manifest['paths'][$specialPagePathId]['eras'][$specialPageIndex1])) {
    $era = $toc_manifest['paths'][$specialPagePathId]['eras'][$specialPageIndex1];
} else {
    $eraSourceList = (!empty($effectiveTocEras) && is_array($effectiveTocEras)) ? $effectiveTocEras : ($toc_manifest['eras'] ?? []);
    $era = $eraSourceList[$specialPageIndex1] ?? ($toc_manifest['eras'][$specialPageIndex1] ?? null);
}

// Step 2: Locate the requested Chapter dictionary (`$chap`) inside `$era['chapters']` using its 0-based index.
$chap = $era['chapters'][$specialPageIndex2] ?? null;

// Guard clause ("fast failure"): if either the Era or Chapter index didn't resolve,
// print a friendly fallback message and `return` early instead of throwing a PHP warning.
if (!$chap) {
    echo '<p>Chapter not found.</p>';
    return;
}
?>
<!-- Scoped Chapter Overview container rendered inside `oliver.php`'s `<article class="story-content">` -->
<div class="book-page" style="max-width: 800px; margin: 0 auto;">
    <?php
    // Normalize any legacy `/raggiesoft-books/books/...` prefix out of `era_url`
    // so the upward Era link points cleanly to the root-level route (`/<series>/<era>`).
    $parentEraHref = !empty($era['era_url']) ? str_replace('/raggiesoft-books/books', '', (string)$era['era_url']) : '';
    ?>
    <!-- CHAPTER HEADER: Parent Era Eyebrow Link + Chapter Title -->
    <div style="margin-bottom: 2rem; border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">
        <div style="font-size: 0.9rem; color: var(--rs-text-muted); margin-bottom: 0.5rem;">
            <?php if ($parentEraHref !== ''): ?>
                <a href="<?php echo htmlspecialchars($parentEraHref); ?>" style="color: var(--rs-text-muted); text-decoration: underline; text-underline-offset: 3px;"><?php echo htmlspecialchars($era['era_title']); ?></a>
            <?php else: ?>
                <?php echo htmlspecialchars($era['era_title']); ?>
            <?php endif; ?>
        </div>
        <h1 style="margin: 0; color: var(--rs-primary);"><?php echo htmlspecialchars($chap['chap_title']); ?></h1>
    </div>

    <!-- CHAPTER PARTS LIST CARD -->
    <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 8px; padding: 1.5rem;">
        <!-- `role="list"` preserves screen-reader list announcements in Safari even when `list-style: none` removes bullet dots -->
        <ul role="list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
            <?php if (!empty($chap['parts'])): ?>
                <!-- Loop over every Part (`p001.md`, `p002.md`, ...) inside this Chapter -->
                <?php foreach ($chap['parts'] as $part): 
                    // Look up the clean browser URL (e.g., `/katie/.../part-2-...`) from `$fileToUrl`
                    // using the physical Markdown file path (`e001/c001/p002.md`) as the lookup key.
                    $href = $fileToUrl[$part['file_path']] ?? ($part['url'] ?? $part['file_path']);
                    // Check whether this Part has either a single hero `image` or a multi-slide `images` carousel.
                    $partHasImage = !empty($part['image']) || !empty($part['images']);
                ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none; color: var(--rs-primary); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 1.1rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <i class="ph ph-file-text" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($part['part_title']); ?></span>
                            <?php if ($partHasImage): ?>
                                <!-- Visual badge letting readers know this scene features 16:9 story artwork -->
                                <span title="Illustrated Scene" style="font-size: 0.75rem; font-weight: 600; padding: 0.12rem 0.45rem; border-radius: 999px; background: var(--rs-bg-alt); border: 1px solid var(--rs-border); color: var(--rs-primary); display: inline-flex; align-items: center; gap: 0.22rem;">
                                    <i class="ph ph-image" aria-hidden="true"></i>
                                    <span>Illustrated</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Graceful empty-state fallback if a Chapter folder exists before its Parts are published -->
                <li style="color: var(--rs-text-muted);">No parts available yet.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
