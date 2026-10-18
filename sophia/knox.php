<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: sophia/knox.php
 * =============================================================================
 * Purpose:
 *     Flagship Universe Portal for "Project: KNOX" & "The Quantum Directive"
 *     inside Ocean View Archives (https://books.raggiesoft.com/knox).
 *
 * Design Principles:
 *     - Introduces readers to the Project: KNOX / Quantum Directive narrative
 *       universe before entering the full-screen Stardust Engine Book Reader.
 *     - Preserves the interactive "Decryption Key: ROOK" Threat Profile toggle
 *       (Axiom Intelligence Estimate vs. Ground-Truth Rostova Twins profile).
 *     - Links directly to canonical e###/c###/s### routes for both Jessica's Cut
 *       (/quantum-directive-1-jessica) and Kelly's Cut (/quantum-directive-1-kelly)
 *       and hands off soundtrack playback to The Listening Room
 *       (https://music.raggiesoft.com/artists/the-stardust-engine/2017-knox-ost).
 *     - Strictly enforces WCAG 2.1 AA contrast across both Dark and Light modes.
 * =============================================================================
 */

global $cdnBaseUrl;
$heroBg = $cdnBaseUrl . '/engine-room-records/artists/the-stardust-engine/2017-knox-ost/social-preview.jpg';
$ostCover = $cdnBaseUrl . '/engine-room-records/artists/the-stardust-engine/2017-knox-ost/album-art.jpg';
?>

<style>
    /* =========================================================================
       PROJECT: KNOX UNIVERSE PORTAL STYLES (WCAG 2.1 AA Light & Dark Adaptive)
       ========================================================================= */
    .knox-portal-wrap {
        flex: 1;
        background: var(--rs-bg);
        color: var(--rs-text);
        padding-bottom: 6rem;
    }

    .knox-hero {
        position: relative;
        background:
            linear-gradient(135deg, rgba(9, 13, 22, 0.92) 0%, rgba(15, 23, 42, 0.84) 60%, rgba(127, 29, 29, 0.78) 100%),
            url('<?= htmlspecialchars($heroBg) ?>') center center / cover no-repeat;
        color: #f8fafc;
        border-bottom: 3px solid #ef4444;
        padding: calc(2.5rem + env(safe-area-inset-top, 0px)) 1.5rem 3rem;
    }

    .knox-container {
        max-width: 1140px;
        margin: 0 auto;
    }

    .knox-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #fca5a5;
        background: rgba(127, 29, 29, 0.55);
        border: 1px solid rgba(248, 113, 113, 0.5);
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        margin-bottom: 1rem;
    }

    .knox-title {
        font-size: clamp(2.5rem, 6vw, 4.25rem);
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.05;
        margin: 0 0 0.5rem 0;
        color: #ffffff;
    }

    .knox-acronym {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 1.1rem;
        color: #e2e8f0;
        margin: 0 0 1.25rem 0;
        letter-spacing: 0.06em;
    }

    .knox-acronym strong {
        color: #f87171;
    }

    .knox-lead {
        font-size: 1.1rem;
        line-height: 1.65;
        color: #f1f5f9;
        max-width: 68ch;
        margin: 0 0 1.75rem 0;
    }

    .knox-cta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        align-items: center;
    }

    .knox-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #dc2626;
        color: #ffffff !important;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.95rem;
        padding: 0.75rem 1.35rem;
        border-radius: 8px;
        border: 1px solid #f87171;
        transition: background-color 0.2s ease, transform 0.15s ease;
    }

    .knox-btn-primary:hover,
    .knox-btn-primary:focus-visible {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    .knox-btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(15, 23, 42, 0.85);
        color: #f8fafc !important;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.75rem 1.35rem;
        border-radius: 8px;
        border: 1px solid rgba(226, 232, 240, 0.45);
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .knox-btn-secondary:hover,
    .knox-btn-secondary:focus-visible {
        background: rgba(30, 41, 59, 0.95);
        border-color: #ffffff;
    }

    .knox-section {
        max-width: 1140px;
        margin: 0 auto;
        padding: 2.5rem 1.5rem;
    }

    .knox-grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        align-items: start;
    }

    .knox-grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.5rem;
    }

    .knox-card {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }

    .knox-dossier-card {
        background: var(--rs-surface);
        border: 2px solid #dc2626;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .knox-dossier-card.is-decrypted {
        border-color: #16a34a;
    }

    .knox-dossier-header {
        background: #b91c1c;
        color: #ffffff;
        padding: 0.9rem 1.25rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .knox-dossier-card.is-decrypted .knox-dossier-header {
        background: #15803d;
    }

    .knox-mono-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1rem 0;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.9rem;
        line-height: 1.7;
    }

    .knox-callout {
        padding: 0.85rem 1rem;
        border-radius: 8px;
        font-size: 0.88rem;
        line-height: 1.5;
    }

    .knox-callout-danger {
        background: rgba(220, 38, 38, 0.12);
        border-left: 4px solid #dc2626;
        color: var(--rs-text);
    }

    .knox-callout-success {
        background: rgba(22, 163, 74, 0.12);
        border-left: 4px solid #16a34a;
        color: var(--rs-text);
    }

    .knox-decrypt-btn {
        width: 100%;
        padding: 0.7rem 1rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-radius: 8px;
        cursor: pointer;
        background: transparent;
        border: 1.5px solid #dc2626;
        color: var(--rs-heading);
        transition: background-color 0.2s ease;
    }

    .knox-dossier-card.is-decrypted .knox-decrypt-btn {
        border-color: #16a34a;
    }

    .knox-decrypt-btn:hover,
    .knox-decrypt-btn:focus-visible {
        background: rgba(220, 38, 38, 0.1);
    }

    .knox-pill {
        display: inline-block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        border: 1px solid currentColor;
        margin-bottom: 0.75rem;
    }
</style>

<div class="stardust-mobile-scroll knox-portal-wrap" id="stardust-main-content">
    <!-- HERO -->
    <section class="knox-hero" aria-label="Project: KNOX Universe Introduction">
        <div class="knox-container">
            <div style="margin-bottom: 1rem;">
                <a href="/" style="color: #cbd5e1; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i class="ph ph-arrow-left"></i> Back to Ocean View Archives Imprint
                </a>
            </div>
            <div class="knox-eyebrow">
                <i class="ph-fill ph-planet"></i> Ocean View Archives &bull; Sci-Fi Narrative Universe
            </div>
            <h1 class="knox-title">PROJECT: K.N.O.X.</h1>
            <p class="knox-acronym">
                <strong>K</strong>INETIC. <strong>N</strong>ULL. <strong>O</strong>PERATIVE: <strong>X</strong>. &mdash; THE QUANTUM DIRECTIVE
            </p>
            <p class="knox-lead">
                On a high-gravity frontier world in the Telsan Gap, a family hunts a phantom while evading the monolithic Axiom Archival Division. Experience both parallel perspectives of <em>The Quantum Directive</em> inside the Stardust Engine Book Reader.
            </p>
            <div class="knox-cta-row">
                <a href="/quantum-directive-1-jessica" class="knox-btn-primary">
                    <i class="ph-fill ph-book-open"></i> Read Jessica's Cut (Book I)
                </a>
                <a href="/quantum-directive-1-kelly" class="knox-btn-secondary">
                    <i class="ph-fill ph-books"></i> Read Kelly's Cut (Parallel Edition)
                </a>
                <a href="https://music.raggiesoft.com/artists/the-stardust-engine/2017-knox-ost" data-no-spa="true" target="_blank" rel="noopener" class="knox-btn-secondary">
                    <i class="ph-fill ph-headphones"></i> Official KNOX OST (Listening Room)
                </a>
            </div>
        </div>
    </section>

    <!-- THE AXIOM'S NIGHTMARE VS. REALITY -->
    <section class="knox-section" id="dossier" aria-labelledby="knox-dossier-heading">
        <div class="knox-grid-2">
            <div>
                <h2 id="knox-dossier-heading" style="font-size: 1.85rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 1rem 0;">
                    The Axiom's Nightmare
                </h2>
                <p style="font-size: 1.05rem; line-height: 1.7; margin: 0 0 1.25rem 0;">
                    To the Axiom Corporation, <strong>Subject K.N.O.X.</strong> is a Tier-1 threat: a rogue Republic super-soldier, likely male, heavy build, with extensive combat scarring. He is a phantom capable of leveling fuel depots and vanishing without a trace.
                </p>
                <hr style="border: 0; border-top: 3px solid #dc2626; width: 56px; margin: 1.5rem 0;">
                <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.75rem 0;">
                    The Reality
                </h3>
                <p style="line-height: 1.7; margin: 0 0 1rem 0;">
                    The Axiom is wrong. There is no soldier. There is no Knox.
                </p>
                <p style="line-height: 1.7; margin: 0;">
                    There is only <strong>Anya</strong>, a 1.5m woman often mistaken for a child, and her brother <strong>Kael</strong>, a nearsighted technician. They possess no military training and not a single scar on their bodies. Their &ldquo;invisibility&rdquo; isn't military-grade stealth tech&mdash;it's the Axiom's own arrogance. They are hunting a monster, so they simply don't see the &ldquo;children&rdquo; standing right in front of them.
                </p>
            </div>

            <!-- Interactive Threat Profile Card -->
            <div class="knox-dossier-card" id="knox-threat-card">
                <div class="knox-dossier-header">
                    <span><i class="ph-fill ph-shield-warning"></i> Threat Profile</span>
                    <span id="knox-clearance-badge" style="font-size: 0.75rem; padding: 0.15rem 0.5rem; background: rgba(0,0,0,0.3); border-radius: 4px;">ALPHA-PRIME</span>
                </div>

                <!-- Axiom View -->
                <div id="axiom-profile" style="padding: 1.5rem;">
                    <h3 style="font-family: ui-monospace, monospace; font-size: 1.15rem; color: #dc2626; margin: 0 0 0.75rem 0;">
                        SUBJECT: K.N.O.X.
                    </h3>
                    <ul class="knox-mono-list">
                        <li><strong>Designation:</strong> Kinetic Null Operative: X</li>
                        <li><strong>Est. Height:</strong> 1.9m &ndash; 2.0m</li>
                        <li><strong>Est. Build:</strong> Heavy / Muscular</li>
                        <li><strong>Distinguishing Marks:</strong> Extensive combat scarring (Assumed)</li>
                        <li><strong>Origin:</strong> Republic Special Forces (Assumed)</li>
                    </ul>
                    <div class="knox-callout knox-callout-danger">
                        <strong>Warning:</strong> Target utilizes military-grade ordnance. Do not engage alone.
                    </div>
                </div>

                <!-- Decrypted Reality View -->
                <div id="reality-profile" style="padding: 1.5rem; display: none;">
                    <h3 style="font-family: ui-monospace, monospace; font-size: 1.15rem; color: #16a34a; margin: 0 0 0.75rem 0;">
                        REALITY: THE ROSTOVA TWINS
                    </h3>
                    <ul class="knox-mono-list">
                        <li><strong>Operative A:</strong> Anya (1.5m / 4'11")</li>
                        <li><strong>Operative K:</strong> Kael (1.7m / 5'7")</li>
                        <li><strong>Marks:</strong> None. Zero scarring.</li>
                        <li><strong>Origin:</strong> Aerie-Hold (Native)</li>
                        <li><strong>Status:</strong> GHOSTS (Unidentified)</li>
                    </ul>
                    <div class="knox-callout knox-callout-success">
                        <strong>Note:</strong> Target utilizes chemistry and environment. You will not see them coming.
                    </div>
                </div>

                <div style="padding: 0 1.5rem 1.5rem;">
                    <button type="button" class="knox-decrypt-btn" id="knox-decrypt-toggle" aria-expanded="false">
                        <i class="ph-fill ph-key"></i> Decryption Key: ROOK (Toggle Reality)
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- THE TELSAN GAP -->
    <section class="knox-section" style="padding-top: 0.5rem;" aria-labelledby="telsan-gap-heading">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 id="telsan-gap-heading" style="font-size: 1.75rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.35rem 0;">
                The Telsan Gap
            </h2>
            <p style="opacity: 0.8; margin: 0;">A patch of unaligned dark space. A navigational hazard. A home.</p>
        </div>

        <div class="knox-grid-3">
            <article class="knox-card">
                <i class="ph-fill ph-buildings" style="font-size: 2.25rem; color: var(--rs-primary); display: block; margin-bottom: 0.75rem;"></i>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">The Spire</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                    A 16-kilometer-high obsidian tower piercing the sky. The seat of the Axiom Corporation's power, built on the industrial rot of Port Telsus.
                </p>
            </article>

            <article class="knox-card">
                <i class="ph-fill ph-tree-evergreen" style="font-size: 2.25rem; color: #16a34a; display: block; margin-bottom: 0.75rem;"></i>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">The Weave</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                    A vertical jungle of kilometer-high trees. To the Axiom, it is the &ldquo;Green Hell.&rdquo; To the natives, it is a symbiotic ecosystem that provides life.
                </p>
            </article>

            <article class="knox-card">
                <i class="ph-fill ph-diamond" style="font-size: 2.25rem; color: #38bdf8; display: block; margin-bottom: 0.75rem;"></i>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">The Moss</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                    Glimmer Moss. Rare. Bioluminescent. Capable of quantum entanglement. The only resource valuable enough to keep the twins alive.
                </p>
            </article>
        </div>
    </section>

    <!-- OPERATIVES & SOUNDTRACK -->
    <section class="knox-section" style="padding-top: 0.5rem;" aria-labelledby="knox-operatives-heading">
        <h2 id="knox-operatives-heading" style="font-size: 1.5rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 1.25rem 0; text-align: center;">
            Operative Profiles
        </h2>
        <div class="knox-grid-3">
            <article class="knox-card" style="text-align: center;">
                <span class="knox-pill" style="color: #16a34a;">OPERATIVE: GHOST</span>
                <h3 style="font-family: ui-monospace, monospace; font-size: 1.35rem; color: var(--rs-heading); margin: 0 0 0.5rem 0;">ANYA</h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                    &ldquo;The Invisibility Cloak.&rdquo; She doesn't fight; she vanishes. The master of fieldcraft who turns the vertical jungle itself into a perimeter.
                </p>
            </article>

            <article class="knox-card" style="text-align: center;">
                <span class="knox-pill" style="color: #16a34a;">OPERATIVE: TOYMAKER</span>
                <h3 style="font-family: ui-monospace, monospace; font-size: 1.35rem; color: var(--rs-heading); margin: 0 0 0.5rem 0;">KAEL</h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                    &ldquo;The Architect.&rdquo; He builds the acoustic and chemical charges that dismantle Axiom infrastructure. Chemistry, fabrication, and the voice of reason.
                </p>
            </article>

            <article class="knox-card" style="text-align: center;">
                <span class="knox-pill" style="color: #dc2626;">SIGNAL: NO CARRIER</span>
                <h3 style="font-family: ui-monospace, monospace; font-size: 1.35rem; color: var(--rs-heading); margin: 0 0 0.5rem 0; letter-spacing: 0.15em;">ROOK</h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; opacity: 0.88;">
                    &ldquo;The Voice in the Static.&rdquo; Dock Master. Ally. Encrypted intermediary routing the twins through Port Telsus's blind spots.
                </p>
            </article>
        </div>
    </section>
</div>

<?php include __DIR__ . "/../includes/components/bottom-nav.php"; ?>

<script>
(function() {
    const toggleBtn = document.getElementById('knox-decrypt-toggle');
    const card = document.getElementById('knox-threat-card');
    const axiom = document.getElementById('axiom-profile');
    const reality = document.getElementById('reality-profile');
    const badge = document.getElementById('knox-clearance-badge');

    if (toggleBtn && card && axiom && reality) {
        toggleBtn.addEventListener('click', function() {
            const isDecrypted = card.classList.toggle('is-decrypted');
            toggleBtn.setAttribute('aria-expanded', isDecrypted ? 'true' : 'false');
            if (isDecrypted) {
                axiom.style.display = 'none';
                reality.style.display = 'block';
                if (badge) badge.textContent = 'DECRYPTED: ROOK';
            } else {
                axiom.style.display = 'block';
                reality.style.display = 'none';
                if (badge) badge.textContent = 'ALPHA-PRIME';
            }
        });
    }
})();
</script>
