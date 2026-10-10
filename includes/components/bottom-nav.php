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
 * It handles its own active-state logic by inspecting `$_SERVER['PHP_SELF']`.
 * 
 * Key Components:
 * 1. Active State Resolution: Dynamically assigns 'active' or 'inactive' 
 *    classes based on the current PHP script name.
 * 2. Icon Toggling: Switches Phosphor Icons from outline (`ph`) to solid 
 *    (`ph-fill`) based on the active state.
 * 
 * Maintenance Notes:
 * - If the routing engine (`isabel.php`) alters how `PHP_SELF` is presented 
 *   or masks it entirely via virtual paths, this active-state logic will break 
 *   and will need to be refactored to use `$requestUri` instead.
 * ============================================================================
 */
?>
<div class="ios-tab-bar">
    <!-- Home Tab -->
    <a href="/" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'ph-fill' : 'ph' ?> ph-house"></i>
        <span>Home</span>
    </a>
    
    <!-- Discover Tab -->
    <a href="/discover" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'discover.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'discover.php') ? 'ph-fill' : 'ph' ?> ph-compass"></i>
        <span>Discover</span>
    </a>
    
    <!-- Bookmarks Tab -->
    <a href="/bookmarks" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'bookmarks.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'bookmarks.php') ? 'ph-fill' : 'ph' ?> ph-bookmark"></i>
        <span>Bookmarks</span>
    </a>
    
    <!-- Library / Catalog Tab -->
    <a href="/library" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'catalog.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'catalog.php') ? 'ph-fill' : 'ph' ?> ph-books"></i>
        <span>Library</span>
    </a>
    
    <!-- Settings Tab -->
    <a href="/settings" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'ph-fill' : 'ph' ?> ph-gear"></i>
        <span>Settings</span>
    </a>
</div>
