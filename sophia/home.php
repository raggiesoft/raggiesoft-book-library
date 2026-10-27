<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: sophia/home.php
 * =============================================================================
 * Purpose:
 *     Official Home Page for Ocean View Archives (https://books.raggiesoft.com/).
 *     Introduces visitors to the official literary imprint of RaggieSoft Media,
 *     offers a prominent front-and-center gateway to the Full Library (/library),
 *     showcases the three flagship literary universes (Contemporary Fiction,
 *     Project: KNOX, and The Silver Gauntlet of Aethel), and explains the
 *     Stardust Engine Book Reader PWA and automated typesetting pipeline powered
 *     by Paige (`paige.php`).
 *
 * Design Principles:
 *     - Dedicated Home Page (Zero Discover Clutter): Replaces the retired
 *       Discover / hybrid dashboard view with a clean, authoritative literary
 *       imprint homepage and direct entry to `/library`.
 *     - Optional Resume Reading Bar: If returning readers have an active reading
 *       session in `localStorage` (`rs-last-read`), a compact "Continue Reading"
 *       banner appears automatically without showing an empty state to new visitors.
 *     - WCAG 2.1 AA Compliance: Enforces >= 4.5:1 text contrast across both
 *       Light Mode and Dark Mode (`prefers-color-scheme`) as well as the
 *       Deep Coastal Teal (#0F4C5C) & Metallic Bronze (#E3B27C) Imprint Hero.
 * =============================================================================
 */

// Import necessary variables provided by the Stardust routing engine (Isabel)
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN to populate volume count & resume card metadata
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
$catalogData = @file_get_contents($catalogUrl);
$books = [];

if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}
$volumeCount = count($books);
?>

<!-- ARCHITECTURE: Semantic Publisher & CollectionPage JSON-LD metadata -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Publisher",
      "name": "Ocean View Archives",
      "url": "https://books.raggiesoft.com",
      "parentOrganization": {
        "@type": "Organization",
        "name": "RaggieSoft Media",
        "url": "https://corporate.raggiesoft.com"
      },
      "description": "The official literary and archival imprint of RaggieSoft Media, preserving original contemporary fiction, sci-fi, and fantasy universes inside the Stardust Engine Library PWA."
    },
    {
      "@type": "CollectionPage",
      "name": "Ocean View Archives — Literary Imprint & Book Reader Hub",
      "url": "https://books.raggiesoft.com",
      "description": "Directory of literary works, prominently featuring The Eleventh Child: The Story of Jordan and the Contemporary Fiction Library."
    }
  ]
}
</script>

<style>
    /* =========================================================================
       OCEAN VIEW ARCHIVES HOME PAGE STYLES
       ========================================================================= */
    .ova-imprint-hero {
        background: linear-gradient(145deg, #092c36 0%, #0F4C5C 60%, #0a3642 100%);
        color: #ffffff;
        border-bottom: 4px solid #E3B27C;
        padding: calc(2.25rem + env(safe-area-inset-top, 0px)) 1.5rem 2.5rem;
        position: relative;
        overflow: hidden;
    }

    .ova-imprint-hero-inner {
        max-width: 1140px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .ova-crest-img {
        width: 140px;
        height: auto;
        margin-bottom: 1rem;
        filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.35));
    }

    .ova-imprint-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #f6d5a8;
        background: rgba(9, 44, 54, 0.72);
        border: 1px solid rgba(227, 178, 124, 0.55);
        padding: 0.3rem 0.85rem;
        border-radius: 999px;
        margin-bottom: 0.75rem;
    }

    .ova-imprint-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: clamp(2rem, 4.5vw, 3.2rem);
        font-weight: 800;
        color: #F4CFA0;
        letter-spacing: 0.03em;
        margin: 0 0 0.65rem 0;
        line-height: 1.15;
    }

    .ova-imprint-lead {
        font-size: 1.05rem;
        line-height: 1.65;
        color: #f1f5f9;
        max-width: 68ch;
        margin: 0 0 1.5rem 0;
    }

    .ova-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        justify-content: center;
        align-items: center;
    }

    .ova-btn-bronze {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #E3B27C;
        color: #09222a !important;
        font-weight: 800;
        font-size: 0.98rem;
        padding: 0.78rem 1.45rem;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid #f6d5a8;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        transition: transform 0.15s ease, background-color 0.2s ease;
    }

    .ova-btn-bronze:hover,
    .ova-btn-bronze:focus-visible {
        background: #f0c694;
        transform: translateY(-1px);
    }

    .ova-btn-ghost {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: rgba(9, 34, 42, 0.78);
        color: #f8fafc !important;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.78rem 1.3rem;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid rgba(227, 178, 124, 0.55);
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .ova-btn-ghost:hover,
    .ova-btn-ghost:focus-visible {
        background: rgba(15, 76, 92, 0.95);
        border-color: #f6d5a8;
    }

    /* Prominent Library Gateway Card */
    .ova-library-gateway {
        background: var(--rs-surface);
        border: 2px solid var(--rs-border);
        border-left: 6px solid #E3B27C;
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 2.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    }

    .ova-library-gateway-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        align-items: center;
    }

    .ova-gateway-primary-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.72rem 1.3rem;
        border-radius: 9px;
        font-weight: 800;
        font-size: 0.95rem;
        text-decoration: none;
        background: var(--rs-primary);
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .ova-gateway-secondary-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.68rem 1.05rem;
        border-radius: 9px;
        font-weight: 700;
        font-size: 0.88rem;
        text-decoration: none;
        background: var(--rs-bg);
        color: var(--rs-heading) !important;
        border: 1px solid var(--rs-border);
    }

    /* Universe Cards Grid */
    .ova-universes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.75rem;
    }

    .ova-universe-card {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .ova-universe-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
    }

    .ova-universe-media {
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #0f172a;
        overflow: hidden;
        position: relative;
        border-bottom: 1px solid var(--rs-border);
    }

    .ova-universe-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .ova-universe-body {
        padding: 1.35rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .ova-universe-tag {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--rs-primary);
        margin-bottom: 0.35rem;
    }

    .ova-universe-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--rs-heading);
        margin: 0 0 0.55rem 0;
        line-height: 1.25;
    }

    .ova-universe-desc {
        font-size: 0.92rem;
        line-height: 1.6;
        color: var(--rs-text);
        opacity: 0.9;
        margin: 0 0 1.25rem 0;
    }

    .ova-universe-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        width: 100%;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        background: var(--rs-primary);
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    /* 3-Pillar Reader Architecture Features */
    .ova-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2.5rem;
    }

    .ova-feature-card {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-radius: 12px;
        padding: 1.25rem;
    }

    .ova-feature-title {
        font-size: 1.02rem;
        font-weight: 800;
        color: var(--rs-heading);
        margin: 0 0 0.45rem 0;
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }

    .ova-feature-desc {
        font-size: 0.88rem;
        line-height: 1.6;
        color: var(--rs-text);
        opacity: 0.9;
        margin: 0;
    }

    /* Automated Typesetting Callout */
    .ova-pipeline-banner {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-left: 4px solid #0F4C5C;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
        align-items: center;
        justify-content: space-between;
    }
</style>

<!-- 
  MAIN CONTAINER
  Uses mobile scrolling rules, appending padding to avoid collision 
  with the sticky bottom navigation menu.
-->
<div class="stardust-mobile-scroll" id="stardust-main-content" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">

    <!-- 
      OCEAN VIEW ARCHIVES IMPRINT INTRODUCTION HERO
      Introduces visitors to the literary imprint with a prominent direct button to the Library.
    -->
    <section class="ova-imprint-hero" aria-label="Ocean View Archives Literary Imprint Introduction">
        <div class="ova-imprint-hero-inner">
            <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/raggiesoft-books/images/logos/oceanview-archives.svg"
                 alt="Ocean View Archives Crest"
                 class="ova-crest-img">

            <div class="ova-imprint-badge">
                <i class="ph-fill ph-books"></i> Official Literary Imprint of RaggieSoft Media
            </div>

            <h1 class="ova-imprint-title">Ocean View Archives</h1>

            <p class="ova-imprint-lead">
                A grounded, unshakeable literary sanctuary built to preserve and bind the history of the pack. Read serialized contemporary novels, sci-fi directives, and fantasy sagas inside the distraction-free <strong>Stardust Engine Book Reader PWA</strong>&mdash;complete with offline caching, dyslexia-friendly typography, and companion soundtracks.
            </p>

            <div class="ova-hero-actions">
                <a href="/aubrie" class="ova-btn-bronze">
                    <i class="ph-fill ph-star"></i> Featured Novel: The Eleventh Child
                </a>
                <a href="/library" class="ova-btn-ghost">
                    <i class="ph-fill ph-books"></i> Browse the Full Library<?= $volumeCount > 0 ? ' (' . $volumeCount . ' Volumes)' : '' ?>
                </a>
                <a href="/aubrie/illustrations" class="ova-btn-ghost">
                    <i class="ph-fill ph-images"></i> Table of Illustrations
                </a>
            </div>
        </div>
    </section>
    
    <!-- 
      HOME PAGE CONTENT CONTAINER
    -->
    <div style="max-width: 1140px; margin: 0 auto; padding: 2.25rem 1.5rem 1rem;">

        <!-- 
          OPTIONAL CONTINUE READING BAR
          Hidden by default; only appears if the reader has an active 'rs-last-read' book in localStorage.
        -->
        <div id="rs-home-jump-back" style="display: none; margin-bottom: 2.25rem;">
            <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 14px; padding: 1.15rem 1.25rem; display: flex; flex-wrap: wrap; gap: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 1rem; align-items: center; min-width: 240px; flex: 1;">
                    <div style="width: 64px; height: 96px; flex-shrink: 0; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.12);">
                        <img id="rs-jump-cover" src="" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <span style="display: inline-block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-primary); margin-bottom: 0.2rem;">
                            <i class="ph-fill ph-bookmark-simple"></i> Active Reading Session
                        </span>
                        <h2 id="rs-jump-series" style="font-size: 1.15rem; margin: 0 0 0.2rem 0; font-weight: 800; color: var(--rs-heading);">Loading...</h2>
                        <p id="rs-jump-part" style="font-size: 0.88rem; color: var(--rs-text); opacity: 0.8; margin: 0;">Pick up right where you left off</p>
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center;">
                    <a id="rs-jump-btn" href="#" class="ova-gateway-primary-btn">
                        <i class="ph-fill ph-book-open"></i> Resume Reading
                    </a>
                    <a href="/library" class="ova-gateway-secondary-btn">
                        <i class="ph-fill ph-books"></i> Full Library
                    </a>
                </div>
            </div>
        </div>

        <!-- 
          PROMINENT FULL LIBRARY GATEWAY BANNER
          Gives visitors a clear, immediate path into the full catalog, bookmarks, and typography settings.
        -->
        <section class="ova-library-gateway" aria-labelledby="ova-library-gateway-heading">
            <div style="flex: 1; min-width: 260px;">
                <span class="ova-universe-tag">Complete Archive Catalog</span>
                <h2 id="ova-library-gateway-heading" style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.5rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.35rem 0;">
                    Enter the Ocean View Library
                </h2>
                <p style="font-size: 0.94rem; line-height: 1.55; color: var(--rs-text); opacity: 0.9; margin: 0;">
                    Browse all <?= $volumeCount > 0 ? $volumeCount : '14' ?> standalone volumes, search characters and scenes instantly, or organize your personal reading shelves.
                </p>
            </div>
            <div class="ova-library-gateway-actions">
                <a href="/library" class="ova-gateway-primary-btn">
                    <i class="ph-fill ph-books"></i> Open the Library
                </a>
                <a href="/bookmarks" class="ova-gateway-secondary-btn">
                    <i class="ph-fill ph-bookmark"></i> Bookmarks
                </a>
                <a href="/settings" class="ova-gateway-secondary-btn">
                    <i class="ph-fill ph-text-aa"></i> Reader Settings
                </a>
            </div>
        </section>

        <!-- 
          FLAGSHIP FEATURED SHOWCASE & LITERARY UNIVERSES
          Features The Eleventh Child: The Story of Jordan (/aubrie) and its Table of Illustrations (/aubrie/illustrations)
          as the premier technical and narrative showcase of the RaggieSoft Book Reader alongside Contemporary Fiction (/library).
        -->
        <section id="imprint-universes" aria-labelledby="ova-collections-heading">
            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.1rem;">
                <h2 id="ova-collections-heading" style="font-size: 1.35rem; font-weight: 800; color: var(--rs-heading); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="ph-fill ph-sparkle" style="color: var(--rs-primary);"></i> Featured Showcase &amp; Collections
                </h2>
                <a href="/library" style="font-size: 0.9rem; font-weight: 700; color: var(--rs-primary); text-decoration: none;">
                    View All Volumes in the Library &rarr;
                </a>
            </div>

            <div class="ova-universes-grid">
                <!-- 1. Featured Flagship Novel: The Eleventh Child -->
                <article class="ova-universe-card">
                    <div class="ova-universe-media">
                        <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/raggiesoft-books/images/covers/og/aubrie.jpg"
                             alt="The Eleventh Child: The Story of Jordan — Flagship Novel &amp; Reader Tech Demo"
                             loading="lazy">
                    </div>
                    <div class="ova-universe-body">
                        <div>
                            <span class="ova-universe-tag">Featured Novel &amp; Reader Tech Demo</span>
                            <h3 class="ova-universe-title">The Eleventh Child</h3>
                            <p class="ova-universe-desc">
                                The Story of Jordan. Follow eighteen-year-old non-speaking freshman Jordan Shawn Kelly as he escapes a lifetime of erasure and builds an unbreakable found family at CPI. Showcases inline <code>&lt;aac&gt;</code> speech synthesis, <code>ZOË.Systems.Logs</code> telemetry, and synchronized music cues.
                            </p>
                        </div>
                        <a href="/aubrie" class="ova-universe-link">
                            <i class="ph-fill ph-book-open-text"></i> Read The Eleventh Child
                        </a>
                    </div>
                </article>

                <!-- 2. The Eleventh Child: Table of Illustrations -->
                <article class="ova-universe-card">
                    <div class="ova-universe-media">
                        <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/raggiesoft-books/images/scenes/aubrie/e004/c010/s004/roa-gate-hug.jpg"
                             alt="Table of Illustrations — The Eleventh Child Visual Scene Index"
                             loading="lazy">
                    </div>
                    <div class="ova-universe-body">
                        <div>
                            <span class="ova-universe-tag">Visual Scene Index &amp; Lightbox</span>
                            <h3 class="ova-universe-title">Table of Illustrations</h3>
                            <p class="ova-universe-desc">
                                Explore every high-resolution 16:9 scene illustration across all six eras of <em>The Eleventh Child</em>, complete with instant era jump-pills, full-screen lightbox inspection, and direct links to each illustrated moment in the novel.
                            </p>
                        </div>
                        <a href="/aubrie/illustrations" class="ova-universe-link">
                            <i class="ph-fill ph-images"></i> Browse All Illustrations
                        </a>
                    </div>
                </article>

                <!-- 3. The Stardust Engine: The Story of the Iron Five -->
                <article class="ova-universe-card">
                    <div class="ova-universe-media">
                        <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/raggiesoft-books/images/covers/og/stardust-engine.jpg"
                             alt="The Stardust Engine: The Story of the Iron Five — Companion Band Novel"
                             loading="lazy">
                    </div>
                    <div class="ova-universe-body">
                        <div>
                            <span class="ova-universe-tag">Companion Rock-Opera Novel &bull; Ryan's POV</span>
                            <h3 class="ova-universe-title">The Stardust Engine</h3>
                            <p class="ova-universe-desc">
                                The thirty-year story of the Iron Five (Ryan, Cassidy, and Holly O'Connell &amp; twins Evan and Tyler Wright) told from Ryan's perspective&mdash;including the 6-part May 21, 2016 <em>Live at the Crucible</em> homecoming concert synchronized with <a href="https://stardust.raggiesoft.com" data-no-spa="true" style="color: var(--rs-primary); font-weight: 700;">stardust.raggiesoft.com</a>.
                            </p>
                        </div>
                        <a href="/stardust-engine" class="ova-universe-link">
                            <i class="ph-fill ph-rocket-launch"></i> Read The Stardust Engine
                        </a>
                    </div>
                </article>

                <!-- 4. Contemporary Fiction Library -->
                <article class="ova-universe-card">
                    <div class="ova-universe-media">
                        <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/raggiesoft-books/images/og/contemporary_v2.jpg"
                             alt="Contemporary Fiction Collection"
                             loading="lazy">
                    </div>
                    <div class="ova-universe-body">
                        <div>
                            <span class="ova-universe-tag">Coastal Virginia &amp; CPI Sanctuary</span>
                            <h3 class="ova-universe-title">Contemporary Fiction Library</h3>
                            <p class="ova-universe-desc">
                                Standalone, grounded narratives set across Virginia. Explore stories of fierce sibling loyalty, authentic disability &amp; neurodivergent representation, and found families building perimeters against an unforgiving world.
                            </p>
                        </div>
                        <a href="/library" class="ova-universe-link">
                            <i class="ph-fill ph-books"></i> Browse the Full Library
                        </a>
                    </div>
                </article>
            </div>
        </section>

        <!-- 
          INSIDE THE STARDUST ENGINE BOOK READER PWA
          Explains the accessibility, offline PWA, and musical companion features of the platform.
        -->
        <section aria-labelledby="ova-reader-features-heading">
            <h2 id="ova-reader-features-heading" style="font-size: 1.35rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph-fill ph-book-open-text" style="color: var(--rs-primary);"></i> Built for Accessible, Distraction-Free Reading
            </h2>
            <div class="ova-features-grid">
                <div class="ova-feature-card">
                    <h3 class="ova-feature-title">
                        <i class="ph-fill ph-text-aa" style="color: var(--rs-primary);"></i> Section 508 &amp; Dyslexia Typography
                    </h3>
                    <p class="ova-feature-desc">
                        Switch effortlessly between <em>Atkinson Hyperlegible</em>, <em>OpenDyslexic</em>, <em>Lora</em>, and <em>Georgia</em> with customizable font scaling, line spacing, and high-contrast Light, Dark, and Sepia themes.
                    </p>
                </div>
                <div class="ova-feature-card">
                    <h3 class="ova-feature-title">
                        <i class="ph-fill ph-cloud-arrow-down" style="color: var(--rs-primary);"></i> Offline-First PWA Architecture
                    </h3>
                    <p class="ova-feature-desc">
                        Install Ocean View Archives to your home screen for a full-screen native app experience. Your active chapter and scroll position are saved locally in your browser with zero tracking databases.
                    </p>
                </div>
                <div class="ova-feature-card">
                    <h3 class="ova-feature-title">
                        <i class="ph-fill ph-headphones" style="color: var(--rs-primary);"></i> Engine Room Companion Scores
                    </h3>
                    <p class="ova-feature-desc">
                        Every universe connects directly to its original rock-opera and synth soundtrack in <a href="https://music.raggiesoft.com" data-no-spa="true" style="color: var(--rs-primary); font-weight: 700;">The Listening Room</a>&mdash;from <em>The Stardust Engine</em> to <em>Firelight</em>.
                    </p>
                </div>
            </div>
        </section>

        <!-- 
          AUTOMATED TYPESETTING VIA PAIGE & THE STARDUST ENGINE
          Explains how raw manuscripts are compiled into this zero-database PWA.
        -->
        <section class="ova-pipeline-banner" aria-labelledby="ova-typesetting-heading">
            <div style="flex: 1; min-width: 260px;">
                <h2 id="ova-typesetting-heading" style="font-size: 1.1rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.4rem 0; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="ph-fill ph-sparkle" style="color: var(--rs-primary);"></i> Automated Typesetting via Paige (<code>paige.php</code>) &amp; The Stardust Engine
                </h2>
                <p style="font-size: 0.92rem; line-height: 1.6; color: var(--rs-text); opacity: 0.9; margin: 0;">
                    Ocean View Archives runs on a 100% zero-database, Git-backed literary pipeline. Canonical Markdown manuscripts (<code>e###/c###/s###.md</code>) are ingested, validated, and indexed into structured JSON manifests and client-side search indexes by <strong>Paige</strong> (Literary Editor &amp; Safe Person), then rendered by the OVA Siblings (Eleanor, Isabel, Sophia, and Oliver).
                </p>
            </div>
            <div>
                <a href="https://michaelpragsdale.com/build-team/paige" data-no-spa="true" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.1rem; border-radius: 8px; border: 1px solid var(--rs-border); background: var(--rs-bg); color: var(--rs-heading); font-weight: 700; font-size: 0.88rem; text-decoration: none;">
                    <i class="ph-fill ph-terminal-window"></i> Meet Paige (Build Team)
                </a>
            </div>
        </section>

        <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--rs-border); font-size: 0.8rem; color: var(--rs-text); opacity: 0.78; line-height: 1.5; text-align: center;">
            <strong>Work of Fiction Disclaimer:</strong> All novels, characters, bands, universities (including Commonwealth Polytechnic Institute / CPI), corporations (including OmniCorp, Axiom, Apex Records, Prairie Coachworks, and Sainte-Claire), transit agencies (Blue Ridge Transit), and events depicted in the Ocean View Archives are works of fiction. Any resemblance to actual persons, living or dead, businesses, institutions, or events is purely coincidental.
        </div>

    </div>
</div>

<!-- Render bottom tab bar component -->
<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>

<!-- 
  CLIENT-SIDE RESUME READING RESOLUTION
  Checks localStorage for an active reading session ('rs-last-read') and reveals
  the compact Continue Reading banner if present.
-->
<script>
(function() {
    const catalog = <?php echo json_encode($books, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
    const lastRead = localStorage.getItem('rs-last-read');
    const launchBehavior = localStorage.getItem('rs-launch-behavior') || 'home';
    if (launchBehavior === 'resume' && lastRead && window.location.pathname === '/' && !sessionStorage.getItem('rs-prevent-auto-redirect')) {
        sessionStorage.setItem('rs-prevent-auto-redirect', 'true');
        window.location.replace(lastRead);
    }
    if (lastRead) {
        const parts = lastRead.split('/').filter(p => p.length > 0);
        if (parts.length >= 1) {
            const seriesSlug = parts[0];
            const seriesData = catalog.find(b => b.slug === seriesSlug);
            if (seriesData) {
                const jumpBack = document.getElementById('rs-home-jump-back');
                if (jumpBack) {
                    jumpBack.style.display = 'block';
                    document.getElementById('rs-jump-series').textContent = seriesData.title;
                    document.getElementById('rs-jump-cover').src = "<?php echo $cdnBaseUrl; ?>" + seriesData.image;
                    document.getElementById('rs-jump-btn').href = lastRead;
                }
            }
        }
    }
})();
</script>
