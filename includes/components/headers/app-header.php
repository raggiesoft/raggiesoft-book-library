<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: includes/components/headers/app-header.php
 * App:  RaggieSoft Book Reader (`https://books.raggiesoft.com`)
 *
 * WHAT IS THIS FILE?
 *   This component renders the persistent **Co-Branded Top Application Header Bar**
 *   (`<header id="stardust-header" class="rs-book-app-header">`) displayed at the
 *   top of all platform discovery screens (`/`, `/library`, `/arcs`, `/illustrations`,
 *   `/stats`, `/bookmarks`, `/settings`, `/about`, `/accessibility`).
 *   (Note: When reading an actual novel chapter inside `sophia/oliver.php`,
 *   `eleanor/isabel.php` omits this top bar so 100% of vertical screen height
 *   is dedicated to the book prose and `sophia/sidebar.php`!)
 *
 * WHAT FEEDS INTO IT?
 *   - `$requestUri`: Active path from `eleanor/isabel.php` (used to highlight
 *     the current nav link with `.is-active` and `aria-current="page"`).
 *   - `$cdnBaseUrl`: CDN base URL (`https://assets.raggiesoft.com`) for loading
 *     the Ocean View Archives keyhole-compass SVG imprint crest.
 *
 * KEY CSS & ACCESSIBILITY CONCEPTS DEMONSTRATED HERE:
 *   1. **CSS Specificity (`#stardust-header.rs-book-app-header`)**:
 *      Combining an ID selector (`#stardust-header`) with a class selector
 *      (`.rs-book-app-header`) guarantees our header styles win over generic
 *      `#stardust-header` rules in external stylesheets.
 *   2. **Atomic Scoped CSS Custom Properties (`--rs-hdr-*`)**:
 *      Instead of scattering color overrides across 10 child selectors, we define
 *      11 `--rs-hdr-*` variables on the `<header>` root and switch all 11 together
 *      for `theme-light`, `theme-sepia`, `theme-dark`, `theme-dark-sepia`, `theme-auto`,
 *      and `theme-sepia-system`—guaranteeing >= 7:1 WCAG AAA text contrast in every mode!
 *   3. **Responsive Desktop/Mobile Navigation Swap (`768px` Breakpoint)**:
 *      - Below `768px` (phones): hides `.rs-book-header-nav` links in the top bar
 *        so `bottom-nav.php` handles thumb-friendly bottom navigation.
 *      - At `768px` and above (tablets/desktops): shows `.rs-book-header-nav` and
 *        hides `.ios-tab-bar`.
 * =============================================================================
 */

global $requestUri, $cdnBaseUrl;
// Normalize the active path so `/`, `/index.php`, and `/isabel.php` all highlight the "Home" link
$headerActivePath = rtrim((string)($requestUri ?? strtok($_SERVER['REQUEST_URI'] ?? '/', '?')), '/');
if ($headerActivePath === '' || $headerActivePath === '/index.php' || $headerActivePath === '/isabel.php') {
    $headerActivePath = '/';
}
$headerAssetsUrl = !empty($cdnBaseUrl) ? rtrim($cdnBaseUrl, '/') : 'https://assets.raggiesoft.com';
?>
<style>
    /* =========================================================================
       PERSISTENT CO-BRANDED APPLICATION HEADER BAR (#stardust-header.rs-book-app-header)
       =========================================================================
       Default palette below is Ocean View Midnight (Dark Mode), overridden cleanly
       below for Dark-Sepia, Crisp Light, and Warm Sepia reading modes.
       `backdrop-filter: saturate(180%) blur(14px)` creates a frosted-glass bar as
       content scrolls underneath `position: sticky; top: 0;`.
       ========================================================================= */
    #stardust-header.rs-book-app-header {
        --rs-hdr-bg: rgba(9, 34, 42, 0.96);
        --rs-hdr-border: rgba(227, 178, 124, 0.35);
        --rs-hdr-title: #f8fafc;
        --rs-hdr-subtitle: #E3B27C;
        --rs-hdr-crest-bg: transparent;
        --rs-hdr-link: #f1f5f9;
        --rs-hdr-hover-bg: rgba(227, 178, 124, 0.2);
        --rs-hdr-hover-fg: #ffffff;
        --rs-hdr-active-bg: #E3B27C;
        --rs-hdr-active-fg: #09222a;
        --rs-hdr-focus-ring: #E3B27C;

        position: sticky;
        top: 0;
        z-index: 40;
        display: block !important;
        width: 100%;
        height: auto !important;
        min-height: 60px;
        background: var(--rs-hdr-bg) !important;
        border-bottom: 1px solid var(--rs-hdr-border) !important;
        /* `env(safe-area-inset-top)` adds extra top clearance on notched / Dynamic Island devices */
        padding: max(0.55rem, calc(env(safe-area-inset-top, 0px) + 0.4rem)) 1rem 0.55rem !important;
        backdrop-filter: saturate(180%) blur(14px);
        -webkit-backdrop-filter: saturate(180%) blur(14px);
        flex-shrink: 0;
    }

    /* Extra top clearance when running as a full-screen installed iOS Home Screen PWA (`black-translucent` status bar) */
    html[data-os="ios"].is-pwa-standalone #stardust-header.rs-book-app-header {
        padding-top: max(3.25rem, calc(env(safe-area-inset-top, 0px) + 0.5rem)) !important;
    }

    /* Theme Variant 1: Dark Sepia (`theme-dark-sepia` and `theme-sepia-system` under OS Dark Mode) */
    body.theme-dark-sepia #stardust-header.rs-book-app-header {
        --rs-hdr-bg: rgba(31, 27, 22, 0.96);
        --rs-hdr-border: #4a4135;
        --rs-hdr-title: #f4ecd8;
        --rs-hdr-subtitle: #E3B27C;
        --rs-hdr-crest-bg: transparent;
        --rs-hdr-link: #e0d5c1;
        --rs-hdr-hover-bg: rgba(227, 178, 124, 0.2);
        --rs-hdr-hover-fg: #ffffff;
        --rs-hdr-active-bg: #E3B27C;
        --rs-hdr-active-fg: #1f1b16;
        --rs-hdr-focus-ring: #E3B27C;
    }
    @media (prefers-color-scheme: dark) {
        body.theme-sepia-system #stardust-header.rs-book-app-header {
            --rs-hdr-bg: rgba(31, 27, 22, 0.96);
            --rs-hdr-border: #4a4135;
            --rs-hdr-title: #f4ecd8;
            --rs-hdr-subtitle: #E3B27C;
            --rs-hdr-crest-bg: transparent;
            --rs-hdr-link: #e0d5c1;
            --rs-hdr-hover-bg: rgba(227, 178, 124, 0.2);
            --rs-hdr-hover-fg: #ffffff;
            --rs-hdr-active-bg: #E3B27C;
            --rs-hdr-active-fg: #1f1b16;
            --rs-hdr-focus-ring: #E3B27C;
        }
    }

    /* Theme Variant 2: Crisp Light (`theme-light` and `theme-auto` under OS Light Mode) */
    body.theme-light #stardust-header.rs-book-app-header {
        --rs-hdr-bg: rgba(255, 255, 255, 0.96);
        --rs-hdr-border: #cbd5e1;
        --rs-hdr-title: #0f172a;
        --rs-hdr-subtitle: #0F4C5C;
        --rs-hdr-crest-bg: #092c36;
        --rs-hdr-link: #1e293b;
        --rs-hdr-hover-bg: #e2e8f0;
        --rs-hdr-hover-fg: #0f172a;
        --rs-hdr-active-bg: #0F4C5C;
        --rs-hdr-active-fg: #ffffff;
        --rs-hdr-focus-ring: #0F4C5C;
    }
    @media (prefers-color-scheme: light) {
        body.theme-auto #stardust-header.rs-book-app-header,
        body:not(.theme-dark):not(.theme-dark-sepia):not(.theme-sepia):not(.theme-sepia-system):not(.theme-night) #stardust-header.rs-book-app-header {
            --rs-hdr-bg: rgba(255, 255, 255, 0.96);
            --rs-hdr-border: #cbd5e1;
            --rs-hdr-title: #0f172a;
            --rs-hdr-subtitle: #0F4C5C;
            --rs-hdr-crest-bg: #092c36;
            --rs-hdr-link: #1e293b;
            --rs-hdr-hover-bg: #e2e8f0;
            --rs-hdr-hover-fg: #0f172a;
            --rs-hdr-active-bg: #0F4C5C;
            --rs-hdr-active-fg: #ffffff;
            --rs-hdr-focus-ring: #0F4C5C;
        }
    }

    /* Theme Variant 3: Classic Warm Sepia & Adaptive Sepia under OS Light Mode */
    body.theme-sepia #stardust-header.rs-book-app-header {
        --rs-hdr-bg: rgba(244, 236, 216, 0.96);
        --rs-hdr-border: #c8b798;
        --rs-hdr-title: #2c2013;
        --rs-hdr-subtitle: #0F4C5C;
        --rs-hdr-crest-bg: #092c36;
        --rs-hdr-link: #3e2f1d;
        --rs-hdr-hover-bg: #e4d5b7;
        --rs-hdr-hover-fg: #1f160d;
        --rs-hdr-active-bg: #0F4C5C;
        --rs-hdr-active-fg: #ffffff;
        --rs-hdr-focus-ring: #0F4C5C;
    }
    @media (prefers-color-scheme: light) {
        body.theme-sepia-system #stardust-header.rs-book-app-header {
            --rs-hdr-bg: rgba(244, 236, 216, 0.96);
            --rs-hdr-border: #c8b798;
            --rs-hdr-title: #2c2013;
            --rs-hdr-subtitle: #0F4C5C;
            --rs-hdr-crest-bg: #092c36;
            --rs-hdr-link: #3e2f1d;
            --rs-hdr-hover-bg: #e4d5b7;
            --rs-hdr-hover-fg: #1f160d;
            --rs-hdr-active-bg: #0F4C5C;
            --rs-hdr-active-fg: #ffffff;
            --rs-hdr-focus-ring: #0F4C5C;
        }
    }

    /* Inner Flexbox container centering brand lockup (left) and nav links (right) up to 1200px */
    .rs-book-app-header-inner {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .rs-book-brand-link {
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        text-decoration: none !important;
        padding: 0.2rem 0.35rem;
        border-radius: 10px;
        min-width: 0;
        transition: transform 0.15s ease;
    }
    .rs-book-brand-link:hover {
        text-decoration: none !important;
    }
    /* `:focus-visible` shows a crisp 2px outline ONLY when navigating via keyboard (`Tab`), not mouse clicks */
    .rs-book-brand-link:focus-visible,
    .rs-book-header-link:focus-visible {
        outline: 2px solid var(--rs-hdr-focus-ring);
        outline-offset: 2px;
    }
    .rs-book-brand-icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        overflow: hidden;
        flex-shrink: 0;
        border: 1.5px solid rgba(227, 178, 124, 0.65);
        background: #0b142a;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.35);
        transition: transform 0.15s ease, border-color 0.15s ease;
    }
    .rs-book-brand-link:hover .rs-book-brand-icon-wrap {
        transform: scale(1.05);
        border-color: #f6d5a8;
    }
    .rs-book-brand-icon-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .rs-book-brand-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    #stardust-header.rs-book-app-header .rs-book-brand-title {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 0.98rem;
        font-weight: 800;
        letter-spacing: -0.01em;
        color: var(--rs-hdr-title) !important;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #stardust-header.rs-book-app-header .rs-book-brand-subtitle {
        display: inline-flex;
        align-items: center;
        gap: 0.32rem;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--rs-hdr-subtitle) !important;
        margin-top: 0.1rem;
        white-space: nowrap;
    }
    #stardust-header.rs-book-app-header .rs-book-brand-subtitle img {
        width: 16px;
        height: 16px;
        object-fit: contain;
        flex-shrink: 0;
        background: var(--rs-hdr-crest-bg);
        border-radius: 50%;
        padding: 1px;
    }
    .rs-book-header-nav {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    #stardust-header.rs-book-app-header .rs-book-header-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.42rem 0.78rem;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--rs-hdr-link) !important;
        text-decoration: none !important;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    #stardust-header.rs-book-app-header .rs-book-header-link:hover {
        background: var(--rs-hdr-hover-bg) !important;
        color: var(--rs-hdr-hover-fg) !important;
        text-decoration: none !important;
    }
    #stardust-header.rs-book-app-header .rs-book-header-link.is-active {
        background: var(--rs-hdr-active-bg) !important;
        color: var(--rs-hdr-active-fg) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
    }

    /* Windows High Contrast / Forced Colors Mode (`Canvas`, `CanvasText`, `Highlight`, `HighlightText` are OS system color keywords) */
    @media (forced-colors: active) {
        #stardust-header.rs-book-app-header {
            background: Canvas !important;
            border-bottom: 1px solid CanvasText !important;
        }
        #stardust-header.rs-book-app-header .rs-book-header-link.is-active {
            background: Highlight !important;
            color: HighlightText !important;
            outline: 2px solid CanvasText;
        }
    }

    /* Responsive Breakpoint Handoff (`767px` vs `768px`):
       Phones use `bottom-nav.php`; Tablets & Desktops use `.rs-book-header-nav`! */
    @media (max-width: 767px) {
        .rs-book-header-nav {
            display: none;
        }
    }
    @media (min-width: 768px) {
        .ios-tab-bar {
            display: none !important;
        }
    }
</style>

<!-- Semantic `<header>` landmark with Co-Branded App + Imprint Lockup and Global `<nav>` -->
<header id="stardust-header" class="rs-book-app-header" aria-label="RaggieSoft Book Reader Application Header">
    <div class="rs-book-app-header-inner">
        <!-- Left Side: App Icon + "RaggieSoft Book Reader / by Ocean View Archives" -->
        <a href="/" class="rs-book-brand-link" title="RaggieSoft Book Reader — Ocean View Archives Home">
            <div class="rs-book-brand-icon-wrap">
                <img src="/icons/icon-192.png?v=4" alt="RaggieSoft Book Reader App Icon">
            </div>
            <div class="rs-book-brand-text">
                <span class="rs-book-brand-title">RaggieSoft Book Reader</span>
                <span class="rs-book-brand-subtitle">
                    <!-- Decorative crest next to text: `alt=""` + `aria-hidden="true"` prevents redundant screen-reader speech -->
                    <img src="<?= htmlspecialchars($headerAssetsUrl) ?>/raggiesoft-books/images/logos/oceanview-archives.svg" alt="" aria-hidden="true">
                    <span>by Ocean View Archives</span>
                </span>
            </div>
        </a>

        <!-- Right Side: Desktop & Tablet SPA Navigation Bar -->
        <nav class="rs-book-header-nav" aria-label="Book Reader Global Navigation">
            <a href="/" class="rs-book-header-link <?= $headerActivePath === '/' ? 'is-active' : '' ?>" <?= $headerActivePath === '/' ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-house"></i>
                <span>Home</span>
            </a>
            <a href="/library" class="rs-book-header-link <?= ($headerActivePath === '/library' || $headerActivePath === '/catalog') ? 'is-active' : '' ?>" <?= ($headerActivePath === '/library' || $headerActivePath === '/catalog') ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-books"></i>
                <span>Library</span>
            </a>
            <a href="/arcs" class="rs-book-header-link <?= ($headerActivePath === '/arcs' || $headerActivePath === '/story-arcs') ? 'is-active' : '' ?>" <?= ($headerActivePath === '/arcs' || $headerActivePath === '/story-arcs') ? 'aria-current="page"' : '' ?> title="Follow Curated Story Arc Reading Playlists">
                <i class="ph-fill ph-playlist"></i>
                <span>Story Arcs</span>
            </a>
            <a href="/illustrations" class="rs-book-header-link <?= ($headerActivePath === '/illustrations' || $headerActivePath === '/table-of-illustrations') ? 'is-active' : '' ?>" <?= ($headerActivePath === '/illustrations' || $headerActivePath === '/table-of-illustrations') ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-images"></i>
                <span>Illustrations</span>
            </a>
            <a href="/stats" class="rs-book-header-link <?= ($headerActivePath === '/stats' || $headerActivePath === '/word-counts' || $headerActivePath === '/library-stats') ? 'is-active' : '' ?>" <?= ($headerActivePath === '/stats' || $headerActivePath === '/word-counts' || $headerActivePath === '/library-stats') ? 'aria-current="page"' : '' ?> title="Live Library Word Counts &amp; Publishing Scale Benchmarks">
                <i class="ph-fill ph-chart-bar"></i>
                <span>Stats</span>
            </a>
            <a href="/bookmarks" class="rs-book-header-link <?= $headerActivePath === '/bookmarks' ? 'is-active' : '' ?>" <?= $headerActivePath === '/bookmarks' ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-bookmark"></i>
                <span>Bookmarks</span>
            </a>
            <a href="/settings" class="rs-book-header-link <?= $headerActivePath === '/settings' ? 'is-active' : '' ?>" <?= $headerActivePath === '/settings' ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-gear"></i>
                <span>Settings</span>
            </a>
        </nav>
    </div>
</header>
