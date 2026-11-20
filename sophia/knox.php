<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/knox.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `https://books.raggiesoft.com/knox` — the flagship
 *     Sci-Fi Universe Portal for "Project: K.N.O.X." and "The Quantum Directive"
 *     inside the RaggieSoft Book Reader.
 *
 * HOW IT CONNECTS TO THE MASTER ROUTER (`eleanor/isabel.php`):
 *     1. When a reader visits `/knox`, `eleanor/isabel.php` matches
 *        `$requestUri === '/knox'`, sets `$isKnox = true`, populates page-specific
 *        OpenGraph social preview tags, and includes `header.php` + this file.
 *     2. From here, readers can jump straight into either parallel perspective
 *        of Book I (`/quantum-directive-1-jessica` or `/quantum-directive-1-kelly`),
 *        which `isabel.php` routes into `sophia/sidebar.php` + `sophia/oliver.php`.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - Hero Banner introducing the Telsan Gap frontier, dual-POV Book I buttons,
 *       and an external link (`data-no-spa="true"`) to The Stardust Engine's
 *       official KNOX soundtrack in The Listening Room (`music.raggiesoft.com`).
 *     - "The Axiom's Nightmare vs. The Reality" comparison paired with an
 *       interactive **Threat Profile Card** (`#knox-threat-card`): clicking
 *       `"Decryption Key: ROOK (Toggle Reality)"` flips the dossier live between
 *       Axiom's false intelligence estimate ("Subject: K.N.O.X.") and the ground
 *       truth ("Reality: The Rostova Twins — Anya & Kael")!
 *     - Worldbuilding cards for The Telsan Gap (`The Spire`, `The Weave`, `The Moss`)
 *       and Operative Profiles (`Anya`, `Kael`, `Rook`).
 *
 * 100-LEVEL CSS & DOM TOGGLE CONCEPTS TO NOTICE HERE:
 *     - Adaptive Contrast Tokens (`--knox-danger`, `--knox-success`, `--knox-info`):
 *       Notice how darker crimson/emerald/sky shades are used in Light Mode
 *       (`#b91c1c`, `#166534`, `#0369a1`) while brighter pastel shades (`#f87171`,
 *       `#4ade80`, `#38bdf8`) are swapped in under `body.theme-dark` and
 *       `@media (prefers-color-scheme: dark)`! That keeps contrast >= 4.5:1
 *       (WCAG 2.1 AA) on both light and dark backgrounds.
 *     - `card.classList.toggle('is-decrypted')` in the `<script>` at the bottom
 *       flips a CSS class on and off in one line and returns a boolean (`true`
 *       when added, `false` when removed) that we immediately use to update
 *       `aria-expanded`, swap `style.display`, and change the clearance badge text!
 * =============================================================================
 */

// Import `$cdnBaseUrl` from `eleanor/isabel.php` to construct CDN artwork URLs.
global $cdnBaseUrl;
$heroBg = $cdnBaseUrl . '/engine-room-records/artists/the-stardust-engine/2017-knox-ost/social-preview.jpg';
$ostCover = $cdnBaseUrl . '/engine-room-records/artists/the-stardust-engine/2017-knox-ost/album-art.jpg';
?>

<style>
    /* =========================================================================
       PROJECT: KNOX UNIVERSE PORTAL STYLES (WCAG 2.1 AA Light & Dark Adaptive)
       ========================================================================= */

    /* Main portal wrapper inheriting global `--rs-bg` & `--rs-text` theme variables */
    .knox-portal-wrap {
        flex: 1;
        background: var(--rs-bg);
        color: var(--rs-text);
        padding-bottom: 6rem;
    }

    /* Full-bleed sci-fi hero banner with dark navy-to-crimson gradient overlay for guaranteed legibility */
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

    /* Monospace tactical pill badge above the main hero title */
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

    /* Responsive fluid display title (`clamp()` scales smoothly from 2.5rem on mobile to 4.25rem on desktop) */
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

    /* Primary crimson CTA button */
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

    /* Secondary dark slate CTA button */
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

    /* Responsive 2-column and 3-column CSS Grids that automatically stack to 1 column on narrow screens */
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

    /* Light-mode default semantic status tokens (deep colors for >= 4.5:1 contrast on light/sepia surfaces) */
    .knox-portal-wrap {
        --knox-danger: #b91c1c;
        --knox-success: #166534;
        --knox-info: #0369a1;
    }

    /* Explicit Dark / Dark-Sepia theme overrides (luminous colors for >= 4.5:1 contrast on dark surfaces) */
    body.theme-dark .knox-portal-wrap,
    body.theme-dark-sepia .knox-portal-wrap {
        --knox-danger: #f87171;
        --knox-success: #4ade80;
        --knox-info: #38bdf8;
    }

    /* Automatic OS Dark Mode override when the reader is in `theme-auto` or `theme-sepia-system` */
    @media (prefers-color-scheme: dark) {
        body:not(.theme-light):not(.theme-sepia) .knox-portal-wrap {
            --knox-danger: #f87171;
            --knox-success: #4ade80;
            --knox-info: #38bdf8;
        }
    }

    .knox-card {
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }

    /* Interactive Threat Profile Dossier Card: starts with red border (`--knox-danger`),
       switches to emerald green (`--knox-success`) when `.is-decrypted` is toggled by JS! */
    .knox-dossier-card {
        background: var(--rs-surface);
        border: 2px solid var(--knox-danger);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .knox-dossier-card.is-decrypted {
        border-color: var(--knox-success);
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
    <!-- SECTION 1: SCI-FI UNIVERSE HERO BANNER & PARALLEL EDITION LINKS -->
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
                <!-- Parallel Edition 1: Jessica's POV (`/quantum-directive-1-jessica`) -->
                <a href="/quantum-directive-1-jessica" class="knox-btn-primary">
                    <i class="ph-fill ph-book-open"></i> Read Jessica's Cut (Book I)
                </a>
                <!-- Parallel Edition 2: Kelly's POV (`/quantum-directive-1-kelly`) -->
                <a href="/quantum-directive-1-kelly" class="knox-btn-secondary">
                    <i class="ph-fill ph-books"></i> Read Kelly's Cut (Parallel Edition)
                </a>
                <!-- External soundtrack handoff to `music.raggiesoft.com` (`data-no-spa="true"` bypasses SPA routing) -->
                <a href="https://music.raggiesoft.com/the-stardust-engine" data-no-spa="true" target="_blank" rel="noopener" class="knox-btn-secondary">
                    <i class="ph-fill ph-headphones"></i> Official KNOX OST (Listening Room)
                </a>
            </div>
        </div>
    </section>

    <!-- SECTION 2: THE AXIOM'S NIGHTMARE VS. GROUND REALITY (INTERACTIVE DOSSIER) -->
    <section class="knox-section" id="dossier" aria-labelledby="knox-dossier-heading">
        <div class="knox-grid-2">
            <!-- Left Column: Narrative Premise -->
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

            <!-- Right Column: Interactive Threat Profile Card (Wired by the inline `<script>` at the bottom) -->
            <div class="knox-dossier-card" id="knox-threat-card">
                <div class="knox-dossier-header">
                    <span><i class="ph-fill ph-shield-warning"></i> Threat Profile</span>
                    <span id="knox-clearance-badge" style="font-size: 0.75rem; padding: 0.15rem 0.5rem; background: rgba(0,0,0,0.3); border-radius: 4px;">ALPHA-PRIME</span>
                </div>

                <!-- State A (Visible initially): Axiom Corporate Intelligence Estimate -->
                <div id="axiom-profile" style="padding: 1.5rem;">
                    <h3 style="font-family: ui-monospace, monospace; font-size: 1.15rem; color: var(--knox-danger); margin: 0 0 0.75rem 0;">
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

                <!-- State B (Hidden initially via `display: none;`): Decrypted Rostova Twins Ground Truth -->
                <div id="reality-profile" style="padding: 1.5rem; display: none;">
                    <h3 style="font-family: ui-monospace, monospace; font-size: 1.15rem; color: var(--knox-success); margin: 0 0 0.75rem 0;">
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

                <!-- Toggle Button: `aria-expanded` announces toggle state to screen readers -->
                <div style="padding: 0 1.5rem 1.5rem;">
                    <button type="button" class="knox-decrypt-btn" id="knox-decrypt-toggle" aria-expanded="false">
                        <i class="ph-fill ph-key"></i> Decryption Key: ROOK (Toggle Reality)
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: WORLDBUILDING — THE TELSAN GAP -->
    <section class="knox-section" style="padding-top: 0.5rem;" aria-labelledby="telsan-gap-heading">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 id="telsan-gap-heading" style="font-size: 1.75rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 0.35rem 0;">
                The Telsan Gap
            </h2>
            <p style="color: var(--rs-text-muted); margin: 0;">A patch of unaligned dark space. A navigational hazard. A home.</p>
        </div>

        <div class="knox-grid-3">
            <article class="knox-card">
                <i class="ph-fill ph-buildings" style="font-size: 2.25rem; color: var(--rs-primary); display: block; margin-bottom: 0.75rem;"></i>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">The Spire</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                    A 16-kilometer-high obsidian tower piercing the sky. The seat of the Axiom Corporation's power, built on the industrial rot of Port Telsus.
                </p>
            </article>

            <article class="knox-card">
                <i class="ph-fill ph-tree-evergreen" style="font-size: 2.25rem; color: var(--knox-success); display: block; margin-bottom: 0.75rem;"></i>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">The Weave</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                    A vertical jungle of kilometer-high trees. To the Axiom, it is the &ldquo;Green Hell.&rdquo; To the natives, it is a symbiotic ecosystem that provides life.
                </p>
            </article>

            <article class="knox-card">
                <i class="ph-fill ph-diamond" style="font-size: 2.25rem; color: var(--knox-info); display: block; margin-bottom: 0.75rem;"></i>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--rs-heading); margin: 0 0 0.5rem 0;">The Moss</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                    Glimmer Moss. Rare. Bioluminescent. Capable of quantum entanglement. The only resource valuable enough to keep the twins alive.
                </p>
            </article>
        </div>
    </section>

    <!-- SECTION 4: OPERATIVE PROFILES -->
    <section class="knox-section" style="padding-top: 0.5rem;" aria-labelledby="knox-operatives-heading">
        <h2 id="knox-operatives-heading" style="font-size: 1.5rem; font-weight: 800; color: var(--rs-heading); margin: 0 0 1.25rem 0; text-align: center;">
            Operative Profiles
        </h2>
        <div class="knox-grid-3">
            <article class="knox-card" style="text-align: center;">
                <span class="knox-pill" style="color: var(--knox-success);">OPERATIVE: GHOST</span>
                <h3 style="font-family: ui-monospace, monospace; font-size: 1.35rem; color: var(--rs-heading); margin: 0 0 0.5rem 0;">ANYA</h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                    &ldquo;The Invisibility Cloak.&rdquo; She doesn't fight; she vanishes. The master of fieldcraft who turns the vertical jungle itself into a perimeter.
                </p>
            </article>

            <article class="knox-card" style="text-align: center;">
                <span class="knox-pill" style="color: var(--knox-success);">OPERATIVE: TOYMAKER</span>
                <h3 style="font-family: ui-monospace, monospace; font-size: 1.35rem; color: var(--rs-heading); margin: 0 0 0.5rem 0;">KAEL</h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                    &ldquo;The Architect.&rdquo; He builds the acoustic and chemical charges that dismantle Axiom infrastructure. Chemistry, fabrication, and the voice of reason.
                </p>
            </article>

            <article class="knox-card" style="text-align: center;">
                <span class="knox-pill" style="color: var(--knox-danger);">SIGNAL: NO CARRIER</span>
                <h3 style="font-family: ui-monospace, monospace; font-size: 1.35rem; color: var(--rs-heading); margin: 0 0 0.5rem 0; letter-spacing: 0.15em;">ROOK</h3>
                <p style="font-size: 0.92rem; line-height: 1.6; margin: 0; color: var(--rs-text);">
                    &ldquo;The Voice in the Static.&rdquo; Dock Master. Ally. Encrypted intermediary routing the twins through Port Telsus's blind spots.
                </p>
            </article>
        </div>
    </section>
</div>

<?php
// Attach the persistent bottom tab bar (`Home`, `Library`, `Bookmarks`, `Settings`).
include __DIR__ . "/../includes/components/bottom-nav.php";
?>

<script>
/*
 * INTERACTIVE THREAT PROFILE DECRYPTION CONTROLLER:
 * Wrapped in an IIFE `(function() { ... })();` so variable names stay scoped locally.
 * When the reader clicks `#knox-decrypt-toggle`, we toggle `.is-decrypted` on the card
 * (which swaps the red header/border to emerald green via CSS) and flip visibility
 * between `#axiom-profile` and `#reality-profile`.
 */
(function() {
    // 1. Look up the interactive elements in the DOM by their unique `id` attributes
    const toggleBtn = document.getElementById('knox-decrypt-toggle');
    const card = document.getElementById('knox-threat-card');
    const axiom = document.getElementById('axiom-profile');
    const reality = document.getElementById('reality-profile');
    const badge = document.getElementById('knox-clearance-badge');

    // 2. Defensive check: verify all elements exist before attaching the `click` listener
    if (toggleBtn && card && axiom && reality) {
        toggleBtn.addEventListener('click', function() {
            // `classList.toggle()` adds `'is-decrypted'` if absent (returning `true`)
            // or removes `'is-decrypted'` if already present (returning `false`).
            const isDecrypted = card.classList.toggle('is-decrypted');

            // Keep `aria-expanded` synchronized so assistive technologies announce the toggle state
            toggleBtn.setAttribute('aria-expanded', isDecrypted ? 'true' : 'false');

            // Swap which profile panel is visible and update the clearance header badge text
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
