<?php
/**
 * ============================================================================
 * COMPONENT: Dedicated Table of Illustrations View (sophia/illustrations.php)
 * ============================================================================
 * PURPOSE:
 * Renders the standalone `/<seriesSlug>/illustrations` page for any narrative
 * in Ocean View Archives (`books.raggiesoft.com`), delegating the gallery grid
 * and Era jump navigation to the reusable component:
 *   `/includes/components/illustrations-toc.php`
 * ============================================================================
 */
global $toc_manifest, $cdnBaseUrl, $seriesSlug, $fileToUrl;
$seriesTitleForIllus = $toc_manifest['series_title'] ?? ucwords(str_replace('-', ' ', (string)$seriesSlug));
?>
<div class="book-page" style="max-width: 960px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem; border-bottom: 2px solid var(--rs-border); padding-bottom: 1rem;">
        <h1 style="margin: 0 0 0.45rem 0; color: var(--rs-primary);">Table of Illustrations</h1>
        <p style="margin: 0; font-size: 0.96rem; opacity: 0.85;">
            Visual scene index and high-resolution artwork gallery for <strong><?= htmlspecialchars($seriesTitleForIllus) ?></strong>.
        </p>
    </div>

    <?php
    $illustrationsTocMode = 'page';
    require __DIR__ . '/../includes/components/illustrations-toc.php';
    ?>
</div>
