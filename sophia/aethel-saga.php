<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: sophia/aethel-saga.php
 * =============================================================================
 * Purpose:
 *     Flagship Universe Portal for "The Silver Gauntlet of Aethel" inside
 *     Ocean View Archives (https://books.raggiesoft.com/aethel-saga).
 *
 * Design Principles:
 *     - Introduces readers to the four mythic eras of Aethel (Era 1: Myth,
 *       Era 2: Silence, Era 3: Tides, Era 4: Iron) before entering the
 *       full-screen Stardust Engine Book Reader.
 *     - Uses 100% canonical e###/c###/s### semantic routes (zero legacy b-num/p-num URLs).
 *     - Links directly to Firelight's companion rock-opera album in The Listening Room
 *       (https://music.raggiesoft.com/firelight/1987-silver-gauntlet-of-aethel).
 * =============================================================================
 */

global $cdnBaseUrl;
$posterBg = $cdnBaseUrl . '/raggiesoft-books/aethel/images/aethel-hero.jpg';
$coverArt = $cdnBaseUrl . '/engine-room-records/artists/firelight/2025-silver-gauntlet-of-aethel/album-art.jpg';
?>

<style>
    /* =========================================================================
       THE SILVER GAUNTLET OF AETHEL PORTAL STYLES (WCAG 2.1 AA Compliant)
       ========================================================================= */
    .aethel-portal-wrap {
        flex: 1;
        background: var(--rs-bg);
        color: var(--rs-text);
        padding-bottom: 6rem;
    }

    .aethel-hero {
        position: relative;
        background:
            linear-gradient(135deg, rgba(10, 12, 18, 0.92) 0%, rgba(17, 24, 39, 0.82) 60%, rgba(120, 53, 15, 0.78) 100%),
            url('<?= htmlspecialchars($posterBg) ?>') center top / cover no-repeat;
        color: #f8fafc;
        border-bottom: 3px solid #f59e0b;
        padding: calc(2.5rem + env(safe-area-inset-top, 0px)) 1.5rem 3rem;
    }

    .aethel-container {
        max-width: 1140px;
        margin: 0 auto;
    }

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

    .aethel-lead {
        font-family: Georgia, serif;
        font-size: 1.15rem;
        line-height: 1.65;
        color: #f1f5f9;
        max-width: 65ch;
        margin: 0 0 1.75rem 0;
    }

    .aethel-cta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        align-items: center;
    }

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

    .aethel-btn-gold:hover,
    .aethel-btn-gold:focus-visible {
        background: #fbbf24;
        transform: translateY(-1px);
    }

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

    .aethel-section {
        max-width: 1140px;
        margin: 0 auto;
        padding: 2.5rem 1.5rem;
    }

    .aethel-era-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
    }

    .aethel-era-card {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-top: 4px solid #d97706;
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
        color: #ffffff !important;
    }
</style>

<div class="stardust-mobile-scroll aethel-portal-wrap" id="stardust-main-content">
    <!-- HERO -->
    <section class="aethel-hero" aria-label="The Silver Gauntlet of Aethel Introduction">
        <div class="aethel-container">
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
            <div class="aethel-cta-row">
                <a href="/aethel" class="aethel-btn-gold">
                    <i class="ph-fill ph-book-open"></i> Enter the Saga (Series Overview &amp; TOC)
                </a>
                <a href="/aethel/era-1-the-age-of-myth/book-i-the-silver-sun/scene-1-chapter-1-the-architect-s-lesson" class="aethel-btn-outline">
                    <i class="ph-fill ph-sparkle"></i> Read Prologue: &ldquo;The Architect's Lesson&rdquo;
                </a>
                <a href="https://music.raggiesoft.com/firelight/1987-silver-gauntlet-of-aethel" data-no-spa="true" target="_blank" rel="noopener" class="aethel-btn-outline">
                    <i class="ph-fill ph-headphones"></i> Firelight Companion Album (Listening Room)
                </a>
            </div>
        </div>
    </section>

    <!-- PROLOGUE EXCERPT -->
    <section class="aethel-section" id="prologue" aria-labelledby="aethel-prologue-heading">
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-left: 5px solid #d97706; border-radius: 12px; padding: 1.75rem;">
            <h2 id="aethel-prologue-heading" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.75; margin: 0 0 0.75rem 0;">
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

    <!-- THE FOUR ERAS -->
    <section class="aethel-section" id="eras" style="padding-top: 0.5rem;" aria-labelledby="aethel-eras-heading">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 id="aethel-eras-heading" style="font-family: 'Playfair Display', Georgia, serif; font-size: 2rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.35rem 0;">
                The Four Canonical Eras
            </h2>
            <p style="opacity: 0.8; margin: 0;">Every era is compiled from canonical <code>e001</code>&ndash;<code>e004</code> manuscripts by Paige (<code>paige.php</code>).</p>
        </div>

        <div class="aethel-era-grid">
            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-sun-horizon" style="font-size: 2.25rem; color: #d97706; display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 1: The Age of Myth
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                        <em>Book I: The Silver Sun.</em> The Architect's Lesson, the tuning of the solar flare, and the cosmic betrayal that shattered the sky.
                    </p>
                </div>
                <a href="/aethel/era-1-the-age-of-myth" class="aethel-era-link">
                    Enter Era I <i class="ph-bold ph-arrow-right"></i>
                </a>
            </article>

            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-snowflake" style="font-size: 2.25rem; color: #38bdf8; display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 2: The Age of Silence
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                        <em>The Sundering of Sunstead &amp; The Shadow's Heart.</em> Twins Kaelan and Kaela awaken in a sunless realm to stand against the Gloom.
                    </p>
                </div>
                <a href="/aethel/era-2-the-age-of-silence" class="aethel-era-link">
                    Enter Era II <i class="ph-bold ph-arrow-right"></i>
                </a>
            </article>

            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-waves" style="font-size: 2.25rem; color: #0284c7; display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 3: The Age of Tides
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                        <em>The Glass Sea &amp; The Star-Eater.</em> The voyage across the endless ocean into the Silent Sector and the Ribcage of the Gods.
                    </p>
                </div>
                <a href="/aethel/era-3-the-age-of-tides" class="aethel-era-link">
                    Enter Era III <i class="ph-bold ph-arrow-right"></i>
                </a>
            </article>

            <article class="aethel-era-card">
                <div>
                    <i class="ph-fill ph-flame" style="font-size: 2.25rem; color: #dc2626; display: block; margin-bottom: 0.75rem;"></i>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">
                        Era 4: The Iron Heart
                    </h3>
                    <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; opacity: 0.88;">
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

<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>

