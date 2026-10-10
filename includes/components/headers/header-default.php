<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: header-default.php
 * ============================================================================
 * Purpose:
 * This component provides a lightweight, default global header used across 
 * the RaggieSoft Books application. It acts as a fallback when a series-specific 
 * or customized header is not provided.
 * 
 * Architectural Role:
 * A simple UI presentation layer component. It is included directly by the 
 * parent `header.php` wrapper when dynamic context does not override it.
 * 
 * Key Components:
 * 1. Logo & Branding: Hardcoded SVG links to the top-level publisher site.
 * 2. Primary Navigation: Quick links to publisher and corporate entities.
 * 
 * Maintenance Notes:
 * - Links and branding are hardcoded. If the corporate site structure changes,
 *   these links must be manually updated.
 * ============================================================================
 */
?>
<header id="stardust-header">
    <div style="display: flex; align-items: center;">
        <a href="https://raggiesoft.com/raggiesoft-books" style="display: flex; align-items: center; gap: 10px; color: var(--rs-primary);">
            <img src="https://assets.raggiesoft.com/raggiesoft-books/images/logos/oceanview-archives.svg" alt="Ocean View Logo" width="30" height="30">
            <span style="font-family: 'Playfair Display', serif; font-weight: bold; letter-spacing: 1px;">OCEAN VIEW ARCHIVES</span>
        </a>
    </div>
    
    <nav style="display: flex; gap: 20px;">
        <a href="https://raggiesoft.com/raggiesoft-books" style="display: flex; align-items: center; gap: 5px;">
            <i class="ph ph-books"></i> Publisher Imprint
        </a>
        <a href="https://raggiesoft.com" style="display: flex; align-items: center; gap: 5px;" target="_blank">
            <i class="ph ph-arrow-square-out"></i> RaggieSoft
        </a>
    </nav>
</header>
