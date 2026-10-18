<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: sophia/home.php
 * =============================================================================
 * Purpose:
 *     Primary Landing Hub for Ocean View Archives (https://books.raggiesoft.com/).
 *     Serves two unified architectural roles:
 *     1. Ocean View Archives Literary Imprint Introduction (Browser Visitors):
 *        Introduces visitors to the official literary imprint of RaggieSoft Media,
 *        its three flagship narrative collections (Contemporary Fiction,
 *        Project: KNOX, and The Silver Gauntlet of Aethel), and the automated
 *        typesetting pipeline powered by Paige (`paige.php`) & The Stardust
 *        Engine Library PWA—so readers are never dumped straight into an
 *        unexplained PWA interface.
 *     2. Personalized Reader Dashboard (Returning Readers & Installed PWA):
 *        Dynamically renders personalized "Jump Back In" (Resume Reading)
 *        shelves, "Available Offline" collections from browser `localStorage`,
 *        and curated shelves from `discover.json`.
 *
 * Design Principles:
 *     - Client-Side Rendering (CSR) Mix: User reading history is strictly kept
 *       in `localStorage` (`rs-last-read`, `rs-offline-*`). PHP fetches the
 *       master catalog, and JS merges the two without server state or databases.
 *     - WCAG 2.1 AA Compliance: Enforces >= 4.5:1 text contrast across both
 *       Light Mode and Dark Mode (`prefers-color-scheme`) as well as the
 *       Deep Coastal Teal (#0F4C5C) & Metallic Bronze (#E3B27C) Imprint Hero.
 * =============================================================================
 */

// Import necessary variables provided by the Stardust routing engine (Isabel)
global $cdnBaseUrl, $siteName, $requestUri;

// Fetch the master catalog directly from the CDN to populate cover data for local history
$catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';

// Suppress network errors; fallback to empty data if the CDN is unreachable
$catalogData = @file_get_contents($catalogUrl);
$books = [];

// Parse the returned JSON, defaulting safely to an empty array
if ($catalogData) {
    $books = json_decode($catalogData, true) ?? [];
}

$discoverConfigUrl = $cdnBaseUrl . "/raggiesoft-books/books/discover.json";
$discoverConfigData = @file_get_contents($discoverConfigUrl);
$discoverConfig = [];
if ($discoverConfigData) {
    $discoverConfig = json_decode($discoverConfigData, true) ?? [];
}
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
      "description": "Directory of literary works, including Contemporary Fiction, Project: KNOX (The Quantum Directive), and The Silver Gauntlet of Aethel."
    }
  ]
}
</script>

<style>
    /* =========================================================================
       OCEAN VIEW ARCHIVES IMPRINT HERO & UNIVERSE SHOWCASE STYLES
       ========================================================================= */
    .ova-imprint-hero {
        background: linear-gradient(145deg, #092c36 0%, #0F4C5C 60%, #0a3642 100%);
        color: #ffffff;
        border-bottom: 4px solid #E3B27C;
        padding: calc(2rem + env(safe-area-inset-top, 0px)) 1.5rem 2.25rem;
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
        font-size: clamp(2rem, 4.5vw, 3.1rem);
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
        margin: 0 0 1.4rem 0;
    }

    .ova-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        align-items: center;
    }

    .ova-btn-bronze {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: #E3B27C;
        color: #09222a !important;
        font-weight: 800;
        font-size: 0.92rem;
        padding: 0.68rem 1.25rem;
        border-radius: 8px;
        text-decoration: none;
        border: 1px solid #f6d5a8;
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
        background: rgba(9, 34, 42, 0.75);
        color: #f8fafc !important;
        font-weight: 600;
        font-size: 0.92rem;
        padding: 0.68rem 1.25rem;
        border-radius: 8px;
        text-decoration: none;
        border: 1px solid rgba(227, 178, 124, 0.55);
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .ova-btn-ghost:hover,
    .ova-btn-ghost:focus-visible {
        background: rgba(15, 76, 92, 0.95);
        border-color: #f6d5a8;
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
      Introduces browser visitors to the literary imprint before entering a novel or installing the PWA.
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
                <a href="/catalog" class="ova-btn-bronze">
                    <i class="ph-fill ph-book-open"></i> Browse Complete Library (<?= count($books) ?> Volumes)
                </a>
                <a href="/discover" class="ova-btn-ghost">
                    <i class="ph-fill ph-compass"></i> Curated Reading Shelves
                </a>
                <a href="#imprint-universes" class="ova-btn-ghost">
                    <i class="ph-fill ph-sparkle"></i> Explore Flagship Universes
                </a>
            </div>
        </div>
    </section>
    
    <!-- 
      DASHBOARD & COLLECTIONS CONTAINER
    -->
    <div style="max-width: 1140px; margin: 0 auto; padding: 2rem 1.5rem 1rem;">
        
        <!-- Top Greeting Bar -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <p id="rs-home-greeting" style="font-size: 0.95rem; color: var(--rs-primary); margin: 0 0 0.2rem 0; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                    Welcome, Reader.
                </p>
                <h2 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.15;">
                    Your Reading Desk &amp; Active Collections
                </h2>
            </div>
        </div>

        <!-- 
          PWA INSTALL BANNER
          Prompt injected for supported browsers to encourage offline caching installation.
        -->
        <div id="rs-pwa-install-banner" style="display: none; background: var(--rs-primary, #0056b3); color: white; border-radius: 12px; padding: 1rem; margin-bottom: 2rem; align-items: flex-start; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: flex-start; gap: 1rem; flex: 1;">
                <div style="width: 48px; height: 48px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                    <img src="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/icon-192.png" alt="App Icon" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Install the Ocean View Reader App</h3>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.9;">Tap Share &gt; Add to Home Screen for full-screen offline reading</p>
                    <button id="rs-pwa-install-btn" style="display: none; margin-top: 0.5rem; background: white; color: var(--rs-primary, #0056b3); border: none; padding: 0.4rem 1rem; border-radius: 6px; font-weight: bold; font-size: 0.85rem; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Install Now</button>
                </div>
            </div>
            <button id="rs-pwa-close-banner" aria-label="Dismiss install banner" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 0.5rem;">
                <i class="ph ph-x" style="font-weight: bold;"></i>
            </button>
        </div>

        <!-- 
          JUMP BACK IN SECTION
          Hidden natively; revealed via JS if a valid 'rs-last-read' entry exists in localStorage.
        -->
        <div id="rs-home-jump-back" style="display: none; margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-book-open"></i> Jump Back In
            </h3>
            <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1rem; display: flex; gap: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); align-items: center;">
                <div style="width: 80px; height: 120px; flex-shrink: 0; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <img id="rs-jump-cover" src="" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex: 1;">
                    <h4 id="rs-jump-series" style="font-size: 1.1rem; margin: 0 0 0.25rem 0; font-weight: 700;">Loading...</h4>
                    <p id="rs-jump-part" style="font-size: 0.9rem; opacity: 0.75; margin: 0 0 1rem 0;"></p>
                    <a id="rs-jump-btn" href="#" class="rs-btn rs-btn-brand" style="display: inline-block; text-decoration: none; font-size: 0.9rem; padding: 0.5rem 1rem;">Resume Reading</a>
                </div>
            </div>
        </div>

        <!-- 
          EMPTY STATE
          Shown when the user has no recent reading history.
        -->
        <div id="rs-home-empty" style="display: none; background: var(--rs-surface); border: 1px dashed var(--rs-border); border-radius: 12px; padding: 1.75rem 1.5rem; text-align: center; margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 0.4rem;">
                <i class="ph-fill ph-bookmark-simple" style="color: var(--rs-primary);"></i> No Active Reading Session Yet
            </h3>
            <p style="color: var(--rs-text); opacity: 0.85; font-size: 0.94rem; margin: 0; line-height: 1.5;">
                When you open any novel below, your exact chapter, scroll position, and typography preferences are saved automatically in your browser so you can pick up right where you left off.
            </p>
        </div>
        
        <div id="rs-home-offline" style="display: none; margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-cloud-check"></i> Available Offline
            </h3>
            <div id="rs-home-offline-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">
                <!-- Content dynamically generated by JS iteration over offline store -->
            </div>
        </div>

        <!-- 
          FLAGSHIP LITERARY UNIVERSES (ACTIVE COLLECTIONS)
          Preserves the 3 core Ocean View Archives collection pillars so visitors
          can explore Contemporary Fiction, Project: KNOX, or The Silver Gauntlet of Aethel.
        -->
        <section id="imprint-universes" aria-labelledby="ova-collections-heading">
            <h3 id="ova-collections-heading" style="font-size: 1.3rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph-fill ph-compass"></i> Flagship Literary Universes
            </h3>

            <div class="ova-universes-grid">
                <!-- 1. Contemporary Fiction -->
                <article class="ova-universe-card">
                    <div class="ova-universe-media">
                        <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/raggiesoft-books/images/og/contemporary_v2.jpg"
                             alt="Contemporary Fiction Collection"
                             loading="lazy">
                    </div>
                    <div class="ova-universe-body">
                        <div>
                            <span class="ova-universe-tag">Coastal Virginia &amp; CPI Sanctuary</span>
                            <h4 class="ova-universe-title">Contemporary Fiction</h4>
                            <p class="ova-universe-desc">
                                Standalone, grounded narratives set across Virginia. Explore stories of fierce sibling loyalty, authentic disability &amp; neurodivergent representation, and found families building perimeters against an unforgiving world.
                            </p>
                        </div>
                        <a href="/catalog" class="ova-universe-link">
                            <i class="ph-fill ph-book-open"></i> Enter the Contemporary Library
                        </a>
                    </div>
                </article>

                <!-- 2. Project: KNOX -->
                <article class="ova-universe-card">
                    <div class="ova-universe-media">
                        <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/engine-room-records/artists/the-stardust-engine/2017-knox-ost/social-preview.jpg"
                             alt="Project: KNOX — The Quantum Directive"
                             loading="lazy">
                    </div>
                    <div class="ova-universe-body">
                        <div>
                            <span class="ova-universe-tag">Sci-Fi Espionage &amp; Parallel Cuts</span>
                            <h4 class="ova-universe-title">Project: KNOX</h4>
                            <p class="ova-universe-desc">
                                On a planet crushed by high gravity in the Telsan Gap, twins Anya and Kael Rostova evade the monolithic Axiom Archival Division. Includes both Jessica's Cut and Kelly's Cut of <em>The Quantum Directive</em>.
                            </p>
                        </div>
                        <a href="/knox" class="ova-universe-link">
                            <i class="ph-fill ph-planet"></i> Access Project: KNOX Dossier
                        </a>
                    </div>
                </article>

                <!-- 3. The Silver Gauntlet of Aethel -->
                <article class="ova-universe-card">
                    <div class="ova-universe-media">
                        <img src="<?= htmlspecialchars($cdnBaseUrl) ?>/aethel/images/aethel-hero.jpg"
                             alt="The Silver Gauntlet of Aethel"
                             loading="lazy">
                    </div>
                    <div class="ova-universe-body">
                        <div>
                            <span class="ova-universe-tag">Four-Era Epic Fantasy Saga</span>
                            <h4 class="ova-universe-title">The Silver Gauntlet of Aethel</h4>
                            <p class="ova-universe-desc">
                                A love letter to 1980s fantasy adventure and symphonic rock. Follow twins Kaelan and Kaela across four mythic eras in a broken world where an ancient debt must be paid in fire.
                            </p>
                        </div>
                        <a href="/aethel-saga" class="ova-universe-link">
                            <i class="ph-fill ph-sword"></i> Explore The Aethel Saga
                        </a>
                    </div>
                </article>
            </div>
        </section>

        <!-- 
          FEATURED HIGHLIGHTS
          Displays curated carousels from discover.json to make the home screen 
          feel alive even when the user has no history or offline books.
        -->
        <?php if (!empty($books)): ?>
        <div id="rs-home-featured" style="margin-bottom: 2.5rem;">
            <?php 
            // Helper function to render a horizontal carousel of books
            if (!function_exists('renderCarousel')) {
                function renderCarousel($title, $icon, $bookList, $cdnBaseUrl) {
                    if (empty($bookList)) return;
                    echo '<div style="margin-bottom: 2.5rem;">';
                    echo '<h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">';
                    echo '<i class="ph ' . htmlspecialchars($icon) . '"></i> ' . htmlspecialchars($title);
                    echo '</h3>';
                    echo '<div class="rs-carousel-container" style="display: flex; gap: 1.25rem; overflow-x: auto; padding-bottom: 1rem; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;">';
                    foreach ($bookList as $book) {
                        $slug = $book['slug'] ?? '';
                        $bookTitle = $book['title'] ?? 'Unknown Archive';
                        $author = 'Michael Ragsdale'; 
                        $imgSrc = !empty($book['image']) ? $cdnBaseUrl . $book['image'] : $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                        echo '<a href="/' . htmlspecialchars($slug) . '/" style="text-decoration: none; color: inherit; display: block; flex: 0 0 140px; scroll-snap-align: start;">';
                        echo '  <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 0.75rem;">';
                        echo '      <img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($bookTitle) . '" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">';
                        echo '  </div>';
                        echo '  <h4 style="font-size: 0.9rem; font-weight: 700; margin: 0 0 0.25rem 0; line-height: 1.2; color: var(--rs-heading); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">' . htmlspecialchars($bookTitle) . '</h4>';
                        echo '  <p style="font-size: 0.75rem; opacity: 0.75; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">' . htmlspecialchars($author) . '</p>';
                        echo '</a>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }

            // Fetch Carousel Configuration from CDN
            $discoverConfigUrl = $cdnBaseUrl . '/raggiesoft-books/books/discover.json';
            $discoverData = @file_get_contents($discoverConfigUrl);
            $carousels = [];
            
            if ($discoverData) {
                $carousels = json_decode($discoverData, true) ?? [];
            }
            
            $catalogMap = [];
            foreach ($books as $b) {
                if (isset($b['slug'])) {
                    $catalogMap[$b['slug']] = $b;
                }
            }
            
            // Render first two carousels on the Home Screen
            if (!empty($carousels)) {
                $count = 0;
                foreach ($carousels as $carousel) {
                    if ($count >= 2) break; // Only show top 2 carousels
                    $carouselTitle = $carousel['title'] ?? 'Featured';
                    $carouselIcon = $carousel['icon'] ?? 'ph-books';
                    $slugs = $carousel['books'] ?? [];
                    
                    $carouselBooks = [];
                    foreach ($slugs as $s) {
                        if (isset($catalogMap[$s])) {
                            $carouselBooks[] = $catalogMap[$s];
                        }
                    }
                    
                    renderCarousel($carouselTitle, $carouselIcon, $carouselBooks, $cdnBaseUrl);
                    $count++;
                }
            } else {
                // Fallback
                $fallbackBooks = $books;
                shuffle($fallbackBooks);
                renderCarousel("Featured Archives", "ph-star", array_slice($fallbackBooks, 0, 6), $cdnBaseUrl);
            }
            ?>
        </div>
        <style>
        /* Hide scrollbar for carousels to maintain clean native look */
        .rs-carousel-container::-webkit-scrollbar {
            display: none;
        }
        .rs-carousel-container {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        </style>
        <?php endif; ?>

        <!-- 
          AUTOMATED TYPESETTING VIA PAIGE & THE STARDUST ENGINE
          Explains how raw manuscripts are compiled into this zero-database PWA.
        -->
        <section class="ova-pipeline-banner" aria-labelledby="ova-typesetting-heading">
            <div style="flex: 1; min-width: 260px;">
                <h3 id="ova-typesetting-heading" style="font-size: 1.1rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.4rem 0; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="ph-fill ph-sparkle" style="color: var(--rs-primary);"></i> Automated Typesetting via Paige (<code>paige.php</code>) &amp; The Stardust Engine
                </h3>
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
  CLIENT-SIDE DATA RESOLUTION LOGIC
  Extracts user data from localStorage and matches it against the PHP-injected catalog.
-->
<script>
(function() {
    // Safely inject PHP catalog into JS context once
    const catalog = <?php echo json_encode($books, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;

    // 0. Update Greeting text based on the user's current local hour
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 18) greeting = 'Good afternoon';
    const greetingEl = document.getElementById('rs-home-greeting');
    if (greetingEl) greetingEl.textContent = greeting + ', Reader.';

    // 1. Process "Jump Back In" State
    // Check localStorage for the exact path the user was last on
    const lastRead = localStorage.getItem('rs-last-read');
    const launchBehavior = localStorage.getItem('rs-launch-behavior') || 'home';
    if (launchBehavior === 'resume' && lastRead && window.location.pathname === '/' && !sessionStorage.getItem('rs-prevent-auto-redirect')) {
        sessionStorage.setItem('rs-prevent-auto-redirect', 'true');
        window.location.replace(lastRead);
    }
    let matched = false;
    if (lastRead) {
        // Extract the root narrative slug from the saved path (e.g., /alex-chloe/...)
        const parts = lastRead.split('/').filter(p => p.length > 0);
        if (parts.length >= 1) {
            const seriesSlug = parts[0];
            
            // Match the slug to extract specific metadata (title/cover)
            const seriesData = catalog.find(b => b.slug === seriesSlug);
            
            if (seriesData) {
                // Unhide the section and inject the data
                document.getElementById('rs-home-jump-back').style.display = 'block';
                document.getElementById('rs-jump-series').textContent = seriesData.title;
                document.getElementById('rs-jump-part').textContent = "Continue your progress";
                // Prepend base URL to image path
                document.getElementById('rs-jump-cover').src = "<?php echo $cdnBaseUrl; ?>" + seriesData.image;
                document.getElementById('rs-jump-btn').href = lastRead;
                matched = true;
            }
        }
    }
    
    if (!matched) {
        document.getElementById('rs-home-empty').style.display = 'block';
    }

    // 2. Process "Offline Shelf" State
    // Scan through all localStorage keys for active offline caches
    const offlineBooks = [];
    
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        // Identify offline indicator keys
        if (key.startsWith('rs-offline-')) {
            const seriesSlug = key.replace('rs-offline-', '');
            // Only push if the cache flag is explicitly enabled ('true')
            if (localStorage.getItem(key) === 'true') {
                const seriesData = catalog.find(b => b.slug === seriesSlug);
                if (seriesData) {
                    offlineBooks.push(seriesData);
                }
            }
        }
    }

    // If matches found, render the offline grid dynamically
    if (offlineBooks.length > 0) {
        const offlineShelf = document.getElementById('rs-home-offline');
        const offlineGrid = document.getElementById('rs-home-offline-grid');
        // Unhide container
        offlineShelf.style.display = 'block';
        
        // Generate DOM elements for each offline narrative
        offlineBooks.forEach(book => {
            const a = document.createElement('a');
            a.href = '/' + book.slug + '/';
            a.style = "text-decoration: none; color: inherit; display: block;";
            
            // Render card UI featuring an offline checkmark indicator
            a.innerHTML = `
                <div style="width: 100%; aspect-ratio: 2/3; background-color: var(--rs-surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 0.5rem; position: relative;">
                    <img src="<?php echo $cdnBaseUrl; ?>${book.image}" alt="${book.title}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    <div style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.6); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                        <i class="ph-fill ph-check-circle" style="font-size: 1rem;"></i>
                    </div>
                </div>
                <h4 style="font-size: 0.85rem; font-weight: 600; margin: 0; line-height: 1.2; text-align: center;">${book.title}</h4>
            `;
            offlineGrid.appendChild(a);
        });
    }

    // 3. Initialize PWA Install Banner
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const banner = document.getElementById('rs-pwa-install-banner');
    const closeBtn = document.getElementById('rs-pwa-close-banner');
    const installBtn = document.getElementById('rs-pwa-install-btn');
    
    let deferredPrompt;

    // Manually display banner on iOS due to lacking browser API support
    if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
        let isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        if (isIos()) {
            banner.style.display = 'flex';
        }
    }

    // Capture standard install prompt events on supported platforms
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default native banner popups
        e.preventDefault();
        deferredPrompt = e;
        
        // Show our themed banner
        if (!isStandalone && banner && localStorage.getItem('rs-pwa-banner-dismissed') !== 'true') {
            banner.style.display = 'flex';
            banner.querySelector('p').textContent = 'Install the web app for offline reading.';
            if (installBtn) {
                installBtn.style.display = 'block';
            }
        }
    });

    // Execute native prompt when user interacts with our button
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                // Auto-dismiss banner if user accepts
                if (outcome === 'accepted') {
                    if (banner) banner.style.display = 'none';
                }
                deferredPrompt = null;
            }
        });
    }

    // Persist user dismissal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (banner) banner.style.display = 'none';
            localStorage.setItem('rs-pwa-banner-dismissed', 'true');
        });
    }
})();
</script>
