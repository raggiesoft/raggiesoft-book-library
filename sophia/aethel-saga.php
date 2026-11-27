<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/aethel-saga.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `https://books.raggiesoft.com/aethel-saga` — the
 *     cinematic Universe Portal for the epic fantasy novel "The Silver Gauntlet
 *     of Aethel" inside the RaggieSoft Book Reader.
 *
 * HOW IT CONNECTS TO THE MASTER ROUTER (`eleanor/isabel.php`):
 *     1. When a reader visits `/aethel-saga`, `eleanor/isabel.php` matches
 *        `$requestUri === '/aethel-saga'`, sets `$isAethelSaga = true`, configures
 *        page-specific OpenGraph tags (`$ogTitle`, `$ogDescription`, `$ogImage`),
 *        and outputs the global `<head>` + `#stardust-app` wrapper via `header.php`.
 *     2. `isabel.php` then `require_once`s this file (`sophia/aethel-saga.php`).
 *     3. Clicking "Enter the Saga" (`/aethel`), "Read Prologue", or any of the
 *        four "Enter Era" buttons transitions seamlessly into `sophia/oliver.php`
 *        and `sophia/sidebar.php` to read the novel itself.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - A full-bleed dark-amber fantasy Hero Banner with three action buttons:
 *         1. Series Overview & Table of Contents (`/aethel`)
 *         2. Direct Prologue jump link (`Scene 1: "The Architect's Lesson"`)
 *         3. Companion Rock-Opera Album link to Firelight on `music.raggiesoft.com`
 *     - A formatted `<blockquote>` excerpt from Era I ("The Solar Garden").
 *     - A responsive 4-column CSS Grid introducing all four canonical Eras
 *       (Era 1: Myth, Era 2: Silence, Era 3: Tides, Era 4: Iron).
 *
 * 100-LEVEL CSS & SPA CONCEPTS TO NOTICE HERE:
 *     - `clamp(min, preferred, max)` in `.aethel-title` smoothly scales the
 *       headline font size with viewport width (`5.5vw`) while never shrinking
 *       below `2.4rem` on phones or growing past `4rem` on ultrawide monitors.
 *     - `data-no-spa="true"` on the external `music.raggiesoft.com` link tells
 *       the client-side Single-Page App router (`stardust-spa.min.js`) NOT to
 *       intercept that click with `fetch()`, allowing the browser to open the
 *       external music site normally in a new tab (`target="_blank"`).
 * =============================================================================
 */

// Import `$cdnBaseUrl` from `eleanor/isabel.php` and build full CDN URLs for artwork.
global $cdnBaseUrl;
$posterBg = $cdnBaseUrl . '/raggiesoft-books/aethel/images/aethel-hero.jpg';
$coverArt = $cdnBaseUrl . '/engine-room-records/artists/firelight/2025-silver-gauntlet-of-aethel/album-art.jpg';
?>

<style>
    /* =========================================================================
       THE SILVER GAUNTLET OF AETHEL PORTAL STYLES (WCAG 2.1 AA Compliant)
       Note: Inside `<style>` tags, we always use CSS `/* ... * /` comments.
       ========================================================================= */

    /* Outer page wrapper inheriting active Light/Dark/Sepia `--rs-bg` & `--rs-text` tokens */
    .aethel-portal-wrap {
        flex: 1;
        background: var(--rs-bg);
        color: var(--rs-text);
        padding-bottom: 6rem;
    }

    /* Full-width hero banner layering a dark slate-to-amber CSS gradient over the CDN hero image
       so white/gold text maintains >= 4.5:1 WCAG AA contrast regardless of image brightness */
    .aethel-hero {
        position: relative;
        background:
            linear-gradient(135deg, rgba(10, 12, 18, 0.92) 0%, rgba(17, 24, 39, 0.82) 60%, rgba(120, 53, 15, 0.78) 100%),
            url('<?= htmlspecialchars($posterBg) ?>') center top / cover no-repeat;
        color: #f8fafc;
        border-bottom: 3px solid #f59e0b;
        padding: calc(2.5rem + env(safe-area-inset-top, 0px)) 1.5rem 3rem;
    }

    /* Centers inner hero content at a comfortable 1140px max width on desktop monitors */
    .aethel-container {
        max-width: 1140px;
        margin: 0 auto;
    }

    /* Pill-shaped (`border-radius: 999px`) category badge above the main title */
    .aethel-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #fde68a;
        background: rgba(120, 53, 15, 0.65);
        border: 1px solid rgba(251, 191, 36, 0.55);
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        margin-bottom: 1rem;
    }

    /* Fluid typography using CSS `clamp()` so the serif display title scales smoothly across screen sizes */
    .aethel-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(2.4rem, 5.5vw, 4rem);
        font-weight: 800;
        line-height: 1.08;
        margin: 0 0 0.35rem 0;
        color: #ffffff;
    }

    .aethel-subtitle {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(1.35rem, 3vw, 2rem);
        font-weight: 600;
        color: #fbbf24;
        margin: 0 0 1.25rem 0;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    /* `max-width: 65ch` caps line length at ~65 characters—the classic typographic sweet spot for comfortable reading */
    .aethel-lead {
        font-family: Georgia, serif;
        font-size: 1.15rem;
        line-height: 1.65;
        color: #f1f5f9;
        max-width: 65ch;
        margin: 0 0 1.75rem 0;
    }

    /* Flexbox button bar that wraps neatly into stacked rows on narrow mobile screens (`flex-wrap: wrap`) */
    .aethel-cta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        align-items: center;
    }

    /* Primary amber/gold Call-to-Action (CTA) button with dark slate text (`#0f172a`) for crisp contrast */
    .aethel-btn-gold {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #f59e0b;
        color: #0f172a !important;
        text-decoration: none;
        font-weight: 800;
        font-size: 0.95rem;
        padding: 0.75rem 1.4rem;
        border-radius: 999px;
        border: 1px solid #fde68a;
        transition: background-color 0.2s ease, transform 0.15s ease;
    }

    /* `:focus-visible` matches `:hover` so keyboard Tab users see clear visual feedback */
    .aethel-btn-gold:hover,
    .aethel-btn-gold:focus-visible {
        background: #fbbf24;
        transform: translateY(-1px);
    }

    /* Secondary translucent dark pill button for Prologue & Companion Music links */
    .aethel-btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(15, 23, 42, 0.85);
        color: #f8fafc !important;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.75rem 1.4rem;
        border-radius: 999px;
        border: 1px solid rgba(251, 191, 36, 0.5);
    }

    /* Standard horizontal/vertical spacing wrapper for body sections below the hero */
    .aethel-section {
        max-width: 1140px;
        margin: 0 auto;
        padding: 2.5rem 1.5rem;
    }

    /* Responsive CSS Grid: automatically fits 1, 2, or 4 columns depending on screen width
       without needing media queries (`repeat(auto-fit, minmax(240px, 1fr))`)! */
    .aethel-era-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
    }

    /* Individual Era card: `justify-content: space-between` pins the "Enter Era" button to the bottom edge */
    .aethel-era-card {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-top: 4px solid var(--rs-secondary);
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }

    .aethel-era-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        margin-top: 1.25rem;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        background: var(--rs-primary);
        color: var(--rs-on-primary, #ffffff) !important;
    }
</style>

<!-- `#stardust-main-content` is the target of the "Skip to main content" keyboard accessibility link in `header.php` -->
<div class="stardust-mobile-scroll aethel-portal-wrap" id="stardust-main-content">
    <!-- SECTION 1: FULL-BLEED FANTASY SAGA HERO BANNER -->
    <section class="aethel-hero" aria-label="The Silver Gauntlet of Aethel Introduction">
        <div class="aethel-container">
            <!-- Return breadcrumb back to the Ocean View Archives Homepage (`/`) -->
            <div style="margin-bottom: 1rem;">
                <a href="/" style="color: #cbd5e1; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i class="ph ph-arrow-left"></i> Back to Ocean View Archives Imprint
                </a>
            </div>
            <div class="aethel-eyebrow">
                <i class="ph-fill ph-sword"></i> Ocean View Archives &bull; Epic Fantasy Saga
            </div>
            <h1 class="aethel-title">THE SILVER GAUNTLET</h1>
            <p class="aethel-subtitle">OF AETHEL</p>
            <p class="aethel-lead">
                Two twins. One broken world. And a debt that must be paid in fire. Follow Kaelan and Kaela across all four mythic eras of Aethel inside the Stardust Engine Book Reader.
            </p>
            <!-- Primary Reading & Companion Soundtrack Action Buttons -->
            <div class="aethel-cta-row">
                <!-- Route 1: Opens `/aethel` (`sophia/landing.php` inside `oliver.php`) -->
                <a href="/aethel" class="aethel-btn-gold">
                    <i class="ph-fill ph-book-open"></i> Enter the Saga (Series Overview &amp; TOC)
                </a>
                <!-- Route 2: Jumps straight into Era 1, Book I, Scene 1 inside `oliver.php` -->
                <a href="/aethel/era-1-the-age-of-myth/book-i-the-silver-sun/scene-1-chapter-1-the-architect-s-lesson" class="aethel-btn-outline">
                    <i class="ph-fill ph-scroll"></i> Read Prologue: &ldquo;The Architect's Lesson&rdquo;
                </a>
                <!-- Route 3: External companion rock-opera album on `music.raggiesoft.com` (`data-no-spa="true"` bypasses SPA fetch) -->
                <a href="https://music.raggiesoft.com/firelight/1987-silver-gauntlet-of-aethel" data-no-spa="true" target="_blank" rel="noopener" class="aethel-btn-outline">
                    <i class="ph-fill ph-headphones"></i> Firelight Companion Album (Listening Room)
                </a>
            </div>
        </div>
    </section>

    <!-- SECTION 2: PROLOGUE LITERARY EXCERPT (`aria-labelledby` links the `<section>` landmark to its `<h2>` heading) -->
    <section class="aethel-section" id="prologue" aria-labelledby="aethel-prologue-heading">
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-left: 5px solid var(--rs-secondary); border-radius: 12px; padding: 1.75rem;">
            <h2 id="aethel-prologue-heading" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--rs-text-muted); margin: 0 0 0.75rem 0;">
                Prologue Excerpt &bull; Era I: The Solar Garden
            </h2>
            <blockquote style="font-family: Georgia, serif; font-size: 1.2rem; font-style: italic; line-height: 1.7; margin: 0 0 1rem 0; color: var(--rs-heading);">
                &ldquo;The Solar Garden did not smell of flowers; it smelled of ozone and potential. Solas stood in the center of the tuning ring&hellip; He was not a boy of flesh and blood here; he was a construct of pure, radiant energy, his skin glowing with the heat of a young star.&rdquo;
            </blockquote>
            <a href="/aethel/era-1-the-age-of-myth/book-i-the-silver-sun/scene-1-chapter-1-the-architect-s-lesson" style="color: var(--rs-primary); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;">
                Continue reading Scene 1: &ldquo;The Architect's Lesson&rdquo; <i class="ph-bold ph-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- SECTION 3: THE FOUR CANONICAL ERAS (Each link opens `sophia/era-toc.php` for that Era) -->
    <section class="aethel-section" id="eras" style="padding-top: 0.5rem;" aria-labelledby="aethel-eras-heading">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 id="aethel-eras-heading" style="font-family: 'Playfair Display', Georgia, serif; font-size: 2rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.35rem 0;">
                The Four Canonical Eras
            </h2>
            <p style="color: var(--rs-text-muted); margin: 0;">Explore all four canonical eras of the saga in chronological reading order.</p>
        </div>

        <div class="aethel-era-grid">
            <!-- Era 1 Card -->
            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-sun-horizon" style="font-size: 2.25rem; color: var(--rs-secondary); display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 1: The Age of Myth
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                        <em>Book I: The Silver Sun.</em> The Architect's Lesson, the tuning of the solar flare, and the cosmic betrayal that shattered the sky.
                    </p>
                </div>
                <a href="/aethel/era-1-the-age-of-myth" class="aethel-era-link">
                    Enter Era I <i class="ph-bold ph-arrow-right"></i>
                </a>
            </article>

            <!-- Era 2 Card -->
            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-snowflake" style="font-size: 2.25rem; color: var(--rs-primary); display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 2: The Age of Silence
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                        <em>The Sundering of Sunstead &amp; The Shadow's Heart.</em> Twins Kaelan and Kaela awaken in a sunless realm to stand against the Gloom.
                    </p>
                </div>
                <a href="/aethel/era-2-the-age-of-silence" class="aethel-era-link">
                    Enter Era II <i class="ph-bold ph-arrow-right"></i>
                </a>
            </article>

            <!-- Era 3 Card -->
            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-waves" style="font-size: 2.25rem; color: var(--rs-primary); display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 3: The Age of Tides
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                        <em>The Glass Sea &amp; The Star-Eater.</em> The voyage across the endless ocean into the Silent Sector and the Ribcage of the Gods.
                    </p>
                </div>
                <a href="/aethel/era-3-the-age-of-tides" class="aethel-era-link">
                    Enter Era III <i class="ph-bold ph-arrow-right"></i>
                </a>
            </article>

            <!-- Era 4 Card -->
            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-flame" style="font-size: 2.25rem; color: var(--rs-secondary); display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 4: The Iron Heart
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                        <em>The Magnetic North, The Pilgrimage &amp; The Zero Point.</em> The final confrontation across the clockwork ocean to settle the ancient debt.
                    </p>
                </div>
                <a href="/aethel/era-4-the-iron-heart" class="aethel-era-link">
                    Enter Era IV <i class="ph-bold ph-arrow-right"></i>
                </a>
            </article>
        </div>
    </section>
</div>

<?php
// Render the shared bottom tab navigation bar (`Home`, `Library`, `Bookmarks`, `Settings`).
include __DIR__ . "/../includes/components/bottom-nav.php";
?>
