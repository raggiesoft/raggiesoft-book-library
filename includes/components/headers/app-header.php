<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: app-header.php
 * ============================================================================
 * Purpose:
 *     Persistent Co-Branded Top Application Header Bar for RaggieSoft Book
 *     Reader (`https://books.raggiesoft.com`), mirroring the architectural
 *     layout of The Listening Room (`https://music.raggiesoft.com`).
 *
 * Design Decisions & Future Maintenance:
 *     1. Co-Branded "App + Imprint" Identity:
 *        - Prominently displays the RaggieSoft Book Reader App Icon (`/icons/icon-192.png?v=4`)
 *          on the left alongside "RaggieSoft Book Reader" and the Ocean View Archives
 *          keyhole-compass imprint crest ("by Ocean View Archives").
 *     2. Adaptive Theme & WCAG 2.1 AA Compliance:
 *        - Automatically adapts to Light Mode, Dark Mode, Sepia, and Dark-Sepia
 *          via `--rs-*` CSS custom properties and `@media (prefers-color-scheme: dark)`.
 *     3. Desktop & Tablet Navigation + PWA Window Drag Support:
 *        - Provides instant SPA navigation links (`Home`, `Library`, `Illustrations`,
 *          `Bookmarks`, `Settings`) with active route highlighting and respects
 *          iOS Dynamic Island safe-area insets.
 * ============================================================================
 */
global $requestUri, $cdnBaseUrl;
$headerActivePath = rtrim((string)($requestUri ?? strtok($_SERVER['REQUEST_URI'] ?? '/', '?')), '/');
if ($headerActivePath === '' || $headerActivePath === '/index.php' || $headerActivePath === '/isabel.php') {
    $headerActivePath = '/';
}
$headerAssetsUrl = !empty($cdnBaseUrl) ? rtrim($cdnBaseUrl, '/') : 'https://assets.raggiesoft.com';
?>
<style>
    .rs-book-app-header {
        position: sticky;
        top: 0;
        z-index: 40;
        background: rgba(11, 20, 35, 0.92);
        border-bottom: 1px solid rgba(227, 178, 124, 0.28);
        padding: max(0.55rem, calc(env(safe-area-inset-top, 0px) + 0.4rem)) 1rem 0.55rem;
        backdrop-filter: saturate(180%) blur(14px);
        -webkit-backdrop-filter: saturate(180%) blur(14px);
        flex-shrink: 0;
    }
    html[data-os="ios"].is-pwa-standalone .rs-book-app-header {
        padding-top: max(3.25rem, calc(env(safe-area-inset-top, 0px) + 0.5rem));
    }
    body.theme-light .rs-book-app-header {
        background: rgba(255, 255, 255, 0.94);
        border-bottom: 1px solid #cbd5e1;
    }
    body.theme-sepia .rs-book-app-header {
        background: rgba(244, 236, 216, 0.95);
        border-bottom: 1px solid #d3c4a9;
    }
    @media (prefers-color-scheme: light) {
        body.theme-auto .rs-book-app-header,
        body.theme-oceanview:not(.theme-dark) .rs-book-app-header {
            background: rgba(9, 44, 54, 0.96);
            border-bottom: 1px solid rgba(227, 178, 124, 0.38);
        }
    }
    .rs-book-app-header-inner {
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
    .rs-book-brand-link:focus-visible {
        outline: 2px solid #E3B27C;
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
    .rs-book-brand-title {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 0.98rem;
        font-weight: 800;
        letter-spacing: -0.01em;
        color: #f8fafc;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    body.theme-light .rs-book-brand-title {
        color: #0f172a;
    }
    body.theme-sepia .rs-book-brand-title {
        color: #3e3122;
    }
    .rs-book-brand-subtitle {
        display: inline-flex;
        align-items: center;
        gap: 0.32rem;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #E3B27C;
        margin-top: 0.1rem;
        white-space: nowrap;
    }
    body.theme-light .rs-book-brand-subtitle {
        color: #0F4C5C;
    }
    body.theme-sepia .rs-book-brand-subtitle {
        color: #5c4b37;
    }
    .rs-book-brand-subtitle img {
        width: 14px;
        height: 14px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .rs-book-header-nav {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .rs-book-header-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.42rem 0.78rem;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #e2e8f0 !important;
        text-decoration: none !important;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    body.theme-light .rs-book-header-link {
        color: #334155 !important;
    }
    body.theme-sepia .rs-book-header-link {
        color: #4a3b2c !important;
    }
    .rs-book-header-link:hover {
        background: rgba(227, 178, 124, 0.16);
        color: #ffffff !important;
        text-decoration: none !important;
    }
    body.theme-light .rs-book-header-link:hover {
        background: #f1f5f9;
        color: #0f172a !important;
    }
    .rs-book-header-link.is-active {
        background: #E3B27C;
        color: #09222a !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }
    body.theme-light .rs-book-header-link.is-active {
        background: #0F4C5C;
        color: #ffffff !important;
    }
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

<header id="stardust-header" class="rs-book-app-header" aria-label="RaggieSoft Book Reader Application Header">
    <div class="rs-book-app-header-inner">
        <a href="/" class="rs-book-brand-link" title="RaggieSoft Book Reader — Ocean View Archives Home">
            <div class="rs-book-brand-icon-wrap">
                <img src="/icons/icon-192.png?v=4" alt="RaggieSoft Book Reader App Icon">
            </div>
            <div class="rs-book-brand-text">
                <span class="rs-book-brand-title">RaggieSoft Book Reader</span>
                <span class="rs-book-brand-subtitle">
                    <img src="<?= htmlspecialchars($headerAssetsUrl) ?>/raggiesoft-books/images/logos/oceanview-archives.svg" alt="" aria-hidden="true">
                    <span>by Ocean View Archives</span>
                </span>
            </div>
        </a>

        <nav class="rs-book-header-nav" aria-label="Book Reader Global Navigation">
            <a href="/" class="rs-book-header-link <?= $headerActivePath === '/' ? 'is-active' : '' ?>" <?= $headerActivePath === '/' ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-house"></i>
                <span>Home</span>
            </a>
            <a href="/library" class="rs-book-header-link <?= ($headerActivePath === '/library' || $headerActivePath === '/catalog') ? 'is-active' : '' ?>" <?= ($headerActivePath === '/library' || $headerActivePath === '/catalog') ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-books"></i>
                <span>Library</span>
            </a>
            <a href="/illustrations" class="rs-book-header-link <?= ($headerActivePath === '/illustrations' || $headerActivePath === '/table-of-illustrations') ? 'is-active' : '' ?>" <?= ($headerActivePath === '/illustrations' || $headerActivePath === '/table-of-illustrations') ? 'aria-current="page"' : '' ?>>
                <i class="ph-fill ph-images"></i>
                <span>Illustrations</span>
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

