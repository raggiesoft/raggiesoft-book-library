<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: bottom-nav.php
 * ============================================================================
 * Purpose:
 * This file provides the mobile-friendly bottom navigation bar for the 
 * application. It is styled to mimic iOS Human Interface Guidelines (HIG) 
 * for a native app feel when running as a Progressive Web App (PWA).
 * 
 * Architectural Role:
 * A reusable UI component included primarily in top-level views (like Sophia). 
 * It resolves active-state logic by inspecting `$requestUri` from the Master
 * Router (`isabel.php`).
 * 
 * Key Components:
 * 1. Active State Resolution: Dynamically assigns 'active' or 'inactive' 
 *    classes based on the current route path (`/` vs `/library` vs `/bookmarks` vs `/settings`).
 * 2. Icon Toggling: Switches Phosphor Icons from outline (`ph`) to solid 
 *    (`ph-fill`) based on the active state.
 * ============================================================================
 */
global $requestUri;
$activePath = $requestUri ?? strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$isHomeTab = ($activePath === '/' || $activePath === '/index.php' || $activePath === '/isabel.php');
$isLibraryTab = ($activePath === '/library' || $activePath === '/catalog');
$isBookmarksTab = ($activePath === '/bookmarks');
$isSettingsTab = ($activePath === '/settings');
?>
<div class="ios-tab-bar" role="navigation" aria-label="Book Reader Primary Navigation">
    <!-- Home Tab -->
    <a href="/" class="ios-tab-item <?= $isHomeTab ? 'active' : 'inactive' ?>" <?= $isHomeTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isHomeTab ? 'ph-fill' : 'ph' ?> ph-house"></i>
        <span>Home</span>
    </a>

    <!-- Library / Catalog Tab -->
    <a href="/library" class="ios-tab-item <?= $isLibraryTab ? 'active' : 'inactive' ?>" <?= $isLibraryTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isLibraryTab ? 'ph-fill' : 'ph' ?> ph-books"></i>
        <span>Library</span>
    </a>
    
    <!-- Bookmarks Tab -->
    <a href="/bookmarks" class="ios-tab-item <?= $isBookmarksTab ? 'active' : 'inactive' ?>" <?= $isBookmarksTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isBookmarksTab ? 'ph-fill' : 'ph' ?> ph-bookmark"></i>
        <span>Bookmarks</span>
    </a>
    
    <!-- Settings Tab -->
    <a href="/settings" class="ios-tab-item <?= $isSettingsTab ? 'active' : 'inactive' ?>" <?= $isSettingsTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isSettingsTab ? 'ph-fill' : 'ph' ?> ph-gear"></i>
        <span>Settings</span>
    </a>
</div>
