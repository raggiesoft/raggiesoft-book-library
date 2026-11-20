<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/accessibility.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `https://books.raggiesoft.com/accessibility` — the
 *     official consumer-friendly Accessibility Commitment screen inside the
 *     RaggieSoft Book Reader.
 *
 * HOW IT CONNECTS TO THE MASTER ROUTER (`eleanor/isabel.php`):
 *     1. When the browser requests `/accessibility`, `eleanor/isabel.php`
 *        matches `$requestUri === '/accessibility'`, sets `$isAccessibility = true`,
 *        configures OpenGraph metadata (`Accessibility Statement | RaggieSoft Book Reader`),
 *        and outputs the global HTML `<head>` + persistent theme loader via
 *        `includes/components/headers/header.php`.
 *     2. `isabel.php` then `require_once`s this view (`sophia/accessibility.php`)
 *        inside `#stardust-main-wrapper`.
 *     3. Finally, `includes/components/bottom-nav.php` renders the mobile/tablet
 *        navigation bar at the bottom of the viewport.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - A "Back to Settings" breadcrumb link (`/settings`) at the top left.
 *     - Four plain-English cards explaining how the Book Reader honors:
 *         1. Native OS "Reduce Motion" (`@media (prefers-reduced-motion: reduce)`)
 *            so rain/weather animations never trigger vestibular discomfort.
 *         2. Automatic OS Light/Dark Mode (`prefers-color-scheme`) plus Sepia
 *            and Dark Sepia reading themes stored in `localStorage`.
 *         3. Full Keyboard Navigation (including the "Skip to main content"
 *            focusable link at the very top of `header.php`).
 *         4. Screen Reader support for Augmentative and Alternative Communication
 *            (AAC) device dialogue (`"AAC Device:"` visually-hidden prefix
 *            injected by `StardustParsedown` when parsing story Markdown).
 *
 * 100-LEVEL HTML & CSS CONCEPTS TO NOTICE HERE:
 *     - Notice how zero JavaScript is needed on this page! Because `header.php`
 *       already reads `localStorage.getItem('reader-settings')` before first
 *       paint and sets CSS Custom Properties (`--rs-bg`, `--rs-surface`,
 *       `--rs-text`, `--rs-heading`, `--rs-primary`), every card on this screen
 *       automatically inherits the user's active font and color theme.
 * =============================================================================
 */

// Import shared routing state set up upstream in `eleanor/isabel.php`.
global $cdnBaseUrl, $siteName, $requestUri;
?>

<!-- 
  MAIN SCROLLABLE VIEWPORT CONTAINER:
  - `flex: 1` expands this pane to fill the available vertical height inside `#stardust-main-wrapper`.
  - `padding-bottom: 6rem` reserves space at the bottom so the final card is never
    covered up by the fixed bottom tab bar (`bottom-nav.php`) on phones.
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    <!-- Centered readable column (`max-width: 800px`) with iOS notch clearance (`env(safe-area-inset-top, 0px)`) -->
    <div style="max-width: 800px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- PAGE HEADER & BREADCRUMB -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <!-- Breadcrumb link returning the reader to `/settings` (`sophia/settings.php`) -->
                <a href="/settings" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--rs-text-muted); text-decoration: none; font-weight: 600; margin-bottom: 1rem;"><i class="ph ph-arrow-left"></i> Back to Settings</a>
                
                <!-- Uppercase eyebrow label above the primary h1 page title -->
                <p style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Our Commitment
                </p>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Accessibility
                </h1>
            </div>
        </div>

        <!-- Lead introductory sentence -->
        <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9; color: var(--rs-text);">
            At RaggieSoft, we believe digital experiences should be accessible, comfortable, and safe for everyone.
        </p>

        <!-- PILLAR 1: NATIVE ACCESSIBILITY & REDUCED MOTION (`prefers-reduced-motion`) -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-person-simple-walk"></i> Native Accessibility & Motion
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                RaggieSoft respects the accessibility preferences you have already configured on your own device. 
                We do not use intrusive custom toggle switches that force you to re-configure your needs on our site.
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                If you have <strong>"Reduce Motion"</strong> enabled in your operating system settings (Windows, macOS, iOS, or Android), 
                our platform—including the Stardust Engine—will automatically detect this. 
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                All immersive background animations, rapid weather effects (like rain or lightning), and heavy UI transitions 
                will be instantly disabled or reduced to static textures, ensuring a safe and comfortable reading experience for 
                users with vestibular disorders, photosensitive epilepsy, or sensory sensitivities.
            </p>
        </div>

        <!-- PILLAR 2: COLOR SCHEMES & HIGH-CONTRAST READING THEMES (`prefers-color-scheme`) -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-circle-half"></i> Color & Contrast
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                This page natively respects your device's <strong>Color Scheme</strong> settings (Light or Dark Mode). 
                If you have configured a specific reading theme inside the Stardust Engine Reader, this app will 
                automatically inherit that theme (Light, Dark, Sepia, or Dark Sepia) to prevent sudden, jarring changes in brightness.
            </p>
        </div>
        
        <!-- PILLAR 3: FULL KEYBOARD NAVIGATION & SKIP LINKS -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-keyboard"></i> Keyboard Navigation
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                Our reading interfaces and hubs are designed to be fully navigable via keyboard, ensuring that users who rely 
                on assistive technologies or cannot use a mouse can fully interact with the narrative.
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                A <strong>"Skip to main content"</strong> link is provided at the very top of every page. When focused via the Tab key, it becomes visible, allowing keyboard users to bypass repetitive navigation and jump directly to the reading pane.
            </p>
        </div>
        
        <!-- PILLAR 4: SCREEN READERS & AUGMENTATIVE/ALTERNATIVE COMMUNICATION (AAC) DIALOGUE -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-speaker-high"></i> Screen Readers & AAC Dialogue
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                Our narratives frequently feature characters who communicate using Augmentative and Alternative Communication (AAC) devices (such as text-to-speech laptops or tablets).
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                To ensure a consistent and immersive experience for screen reader users, the Stardust Engine automatically intercepts these lines of dialogue and prefaces them with visually-hidden <strong>"AAC Device:"</strong> text. This guarantees that screen readers clearly differentiate between spoken dialogue and synthesized device output, preserving the structural intent of the story for all readers.
            </p>
        </div>
        
    </div>
</div>

<!-- 
  BOTTOM NAVIGATION COMPONENT:
  Includes the shared iOS/PWA-style bottom tab bar (`includes/components/bottom-nav.php`).
-->
<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>
