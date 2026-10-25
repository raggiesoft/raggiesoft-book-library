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
?>
<div class="toc-header">
    <p class="toc-eyebrow">
        <a href="/<?= htmlspecialchars($seriesSlug) ?>" style="color: var(--accent); text-decoration: none;">
            <?= htmlspecialchars($toc_manifest['series_title'] ?? ucwords(str_replace('-', ' ', $seriesSlug))) ?>
        </a>
    </p>
    <h1 class="toc-title">Table of Illustrations</h1>
    <p style="margin: 0.5rem 0 0; font-size: 0.96rem; color: var(--text-muted, #c9beb5);">
        Visual scene index and high-resolution artwork gallery for <strong><?= htmlspecialchars($toc_manifest['series_title'] ?? ucwords(str_replace('-', ' ', $seriesSlug))) ?></strong>.
    </p>
</div>

<?php
$illustrationsTocMode = 'page';
require __DIR__ . '/../includes/components/illustrations-toc.php';
?>
