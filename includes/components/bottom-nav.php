<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: includes/components/bottom-nav.php
 * App:  RaggieSoft Book Reader (`https://books.raggiesoft.com`)
 *
 * WHAT IS THIS FILE?
 *   This reusable PHP UI component renders the 4-tab bottom navigation bar
 *   (`Home`, `Library`, `Bookmarks`, `Settings`) shown at the bottom of phone
 *   screens (`< 768px` wide), styled after native iOS / mobile app tab bars.
 *   (On tablet and desktop screens `>= 768px`, CSS in `app-header.php` hides
 *   `.ios-tab-bar` because the top header navigation bar takes over!)
 *
 * WHAT FEEDS INTO IT?
 *   - `$requestUri`: Set earlier in the same request by `eleanor/isabel.php`
 *     (or computed directly from `$_SERVER['REQUEST_URI']` as a fallback).
 *
 * WHAT VISIBLE UI / BEHAVIOR DOES IT PRODUCE?
 *   - Highlights whichever tab matches the current URL (`active` vs. `inactive`
 *     CSS class).
 *   - Swaps that tab's Phosphor icon from an outline stroke (`class="ph ..."`)
 *     to a solid filled icon (`class="ph-fill ..."`), matching native iOS apps.
 *   - Adds `aria-current="page"` on the active `<a>` link so screen readers
 *     (VoiceOver / TalkBack) announce "Home, current page, link".
 *   - Because these are standard `<a href="/...">` tags without `data-no-spa`,
 *     clicking any tab triggers `stardust-spa.js` for instant soft navigation!
 * =============================================================================
 */

// Import `$requestUri` from the global scope created by `eleanor/isabel.php`.
// Fallback: `strtok($str, '?')` splits `$str` at the first `?` and returns the path before it
// (just like `window.location.pathname` or `url.split('?')[0]` in JavaScript!).
global $requestUri;
$activePath = $requestUri ?? strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

// Compute boolean flags (`true` / `false`) for each of the 4 mobile navigation tabs.
// Note on PHP Short-Echo Tags below (`< ? = $cond ? 'a' : 'b' ? >`):
// Think of PHP's short-echo tag inside HTML just like `${ ... }` inside a JavaScript
// template literal—it evaluates the ternary expression on the server and prints the winning string!
$isHomeTab = ($activePath === '/' || $activePath === '/index.php' || $activePath === '/isabel.php');
$isLibraryTab = ($activePath === '/library' || $activePath === '/catalog');
$isBookmarksTab = ($activePath === '/bookmarks');
$isSettingsTab = ($activePath === '/settings');
?>
<div class="ios-tab-bar" role="navigation" aria-label="Book Reader Primary Navigation">
    <!-- Tab 1: Home Showcase (`/`) -->
    <a href="/" class="ios-tab-item <?= $isHomeTab ? 'active' : 'inactive' ?>" <?= $isHomeTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isHomeTab ? 'ph-fill' : 'ph' ?> ph-house"></i>
        <span>Home</span>
    </a>

    <!-- Tab 2: Full Novel Catalog (`/library`) -->
    <a href="/library" class="ios-tab-item <?= $isLibraryTab ? 'active' : 'inactive' ?>" <?= $isLibraryTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isLibraryTab ? 'ph-fill' : 'ph' ?> ph-books"></i>
        <span>Library</span>
    </a>
    
    <!-- Tab 3: Saved Reading Progress & Bookmarks (`/bookmarks`) -->
    <a href="/bookmarks" class="ios-tab-item <?= $isBookmarksTab ? 'active' : 'inactive' ?>" <?= $isBookmarksTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isBookmarksTab ? 'ph-fill' : 'ph' ?> ph-bookmark"></i>
        <span>Bookmarks</span>
    </a>
    
    <!-- Tab 4: Typography, Spacing, & Theme Preferences (`/settings`) -->
    <a href="/settings" class="ios-tab-item <?= $isSettingsTab ? 'active' : 'inactive' ?>" <?= $isSettingsTab ? 'aria-current="page"' : '' ?>>
        <i class="<?= $isSettingsTab ? 'ph-fill' : 'ph' ?> ph-gear"></i>
        <span>Settings</span>
    </a>
</div>
