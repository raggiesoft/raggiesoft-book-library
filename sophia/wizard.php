<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/wizard.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `/welcome` on `books.raggiesoft.com` (and `/setup`
 *     via a permanent 301 redirect in `isabel.php`) — a friendly, 6-step
 *     first-time reader onboarding wizard hosted in-universe by the four Ocean
 *     View Archives siblings (Isabel, Eleanor, Sophia, and Oliver).
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php` & `reader.js`:
 *     1. When a reader visits `/welcome`, `eleanor/isabel.php` sets
 *        `$isWelcome = true`, marks the page `noindex, follow` (since setup
 *        screens shouldn't clutter search engine results), and `require_once`s
 *        this file inside `#stardust-main-wrapper`.
 *     2. Notice `data-cdn-url="<?php echo htmlspecialchars($cdnBaseUrl); ?>"`
 *        on `#reader-wizard-dialog`! That HTML `data-*` attribute passes the
 *        PHP server's CDN base URL cleanly to `reader.js` so JavaScript knows
 *        where to load each sibling's portrait illustration into
 *        `#wizard-sidebar-graphic` as the user clicks "Next ->" through Steps 1–6.
 *     3. Every `<select>` and `<input type="checkbox">` on this page is wired by
 *        `reader.js` to write the user's choices directly into `window.localStorage`
 *        (`reader-settings`), using the exact same keys as `sophia/settings.php`.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - Step 1 (Isabel): Article measure/column width (`default` 800px, `wide` 1200px, `full` 100%).
 *     - Step 2 (Eleanor): Reading color theme (`auto` OS match, `sepia-system`, `light`, `dark`, `sepia`, `dark-sepia`).
 *     - Step 3 (Sophia): Typography (`system`, `georgia`, `lora`, `inter`, `atkinson` Hyperlegible, `opendyslexic`).
 *     - Step 4: Narrative Soundtrack autoplay preference (defaults to `"false"` / Turned Off per WCAG 2.1 AA).
 *     - Step 5 (Eleanor & Isabel): Toggle allowing custom scene-specific atmospheres (such as `dark-rain`).
 *     - Step 6 (Oliver): Overview of offline PWA reading and "Start Reading ->" completion button.
 *
 * 100-LEVEL DOM STATE PATTERN TO NOTICE HERE:
 *     - All 6 steps exist in the HTML document from the very beginning!
 *       Step 1 starts visible (`class="wizard-step active-step"`), while Steps 2–6
 *       start hidden (`style="display: none;"`). Clicking `#wizard-btn-next` or
 *       `#wizard-btn-prev` simply hides the old step and shows the next step—zero
 *       network requests required between steps!
 * =============================================================================
 */

// Import `$cdnBaseUrl` from `eleanor/isabel.php` so `reader.js` can load sibling artwork from the CDN.
global $cdnBaseUrl;
?>
<!-- Outer scrollable flex wrapper centering the wizard card on desktop/tablet while clearing notches on mobile -->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding: calc(2rem + env(safe-area-inset-top, 0px)) 1rem 6rem 1rem; display: flex; justify-content: center; align-items: flex-start;">
    <!--
      WIZARD ROOT ELEMENT:
      `data-cdn-url` exposes PHP's `$cdnBaseUrl` to JavaScript (`element.dataset.cdnUrl` in `reader.js`).
    -->
    <div id="reader-wizard-dialog" data-cdn-url="<?php echo htmlspecialchars($cdnBaseUrl); ?>" class="wizard-dialog">
    <div class="wizard-container">
        <!-- Left Sidebar Graphic: `reader.js` swaps the background character portrait here on each step -->
        <div id="wizard-sidebar-graphic" class="wizard-sidebar"></div>
        
        <!-- Right Interactive Step Content Area -->
        <div class="wizard-content">
            
            <!-- STEP 1 (Visible initially via `.active-step`): Introduction & Column Measure Width -->
            <div id="wizard-step-1" class="wizard-step active-step">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Welcome to Ocean View Archives!</h2>
                <p>Hi there! I'm Isabel, the brainchild behind this app, and I'll be your guide. My siblings and I live right here in the Ocean View section of Norfolk, Virginia.</p>
                <p>Before you start reading, Oliver and I wanted to help you set up your reading environment. The Archives are designed for long, distraction-free reading sessions, so let's make sure the layout is comfortable for you.</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Choose your Article Width:</h4>
                    <!-- Values (`default`, `wide`, `full`) map directly to `reader-settings.width` in `localStorage` -->
                    <select id="wizard-width-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="default">Default (800px) - Recommended</option>
                        <option value="wide">Wide (1200px)</option>
                        <option value="full">Full Screen (100%)</option>
                    </select>
                </div>
            </div>

            <!-- STEP 2 (Hidden until reader clicks Next): Color Mode & Eye-Strain Protection -->
            <div id="wizard-step-2" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Themes & Text Size</h2>
                <p>Hey, I'm Eleanor! I handle the public entry points.</p>
                <p>If you're reading late at night, staring at a bright white screen can cause serious eye strain. We have a few themes available to help with that.</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Select a Theme:</h4>
                    <!-- `<optgroup>` groups adaptive OS-following modes separately from fixed light/dark/sepia palettes -->
                    <select id="wizard-theme-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <optgroup label="System Default (Matches your OS)">
                            <option value="auto">Auto (Light / Dark)</option>
                            <option value="sepia-system">Auto (Sepia / Dark Sepia)</option>
                        </optgroup>
                        <optgroup label="Fixed Themes">
                            <option value="light">Light Mode (Crisp & Clean)</option>
                            <option value="dark">Dark Mode (Best for night)</option>
                            <option value="sepia">Sepia Mode (Easy on the eyes)</option>
                            <option value="dark-sepia">Dark Sepia (Warm & Dark)</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- STEP 3: Typeface Selection (including Braille Institute's Atkinson Hyperlegible & OpenDyslexic) -->
            <div id="wizard-step-3" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Fonts & Accessibility</h2>
                <p>Hello! I'm Sophia, the keeper of the vault.</p>
                <p>My brother Oliver wants you to know about <strong>Atkinson Hyperlegible</strong>. It's a special font designed by the Braille Institute to make letters incredibly distinct and readable, especially for low-vision readers. Try it out!</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Choose your Typography:</h4>
                    <!-- Short keys (`system`, `georgia`, `lora`, `inter`, `atkinson`, `opendyslexic`) resolve via `RS_FONT_MAP` -->
                    <select id="wizard-font-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="system">System Default (San Francisco/Roboto)</option>
                        <option value="georgia">Georgia (Serif)</option>
                        <option value="lora">Lora (Classic Serif)</option>
                        <option value="inter">Inter (Modern Sans)</option>
                        <option value="atkinson">Atkinson Hyperlegible (Accessibility)</option>
                        <option value="opendyslexic">OpenDyslexic</option>
                    </select>
                </div>
            </div>

            <!-- STEP 4: Companion Soundtrack Autoplay Preference (Default `"false"` honors WCAG 1.4.2 Audio Control) -->
            <div id="wizard-step-4" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Narrative Soundtrack</h2>
                <p>Did you know? Our Archives feature a built-in, original narrative soundtrack!</p>
                <p>All of the music is produced by the author and released through the real-world independent record label, <strong>Engine Room Records</strong>. (You can even find these songs streaming on platforms like Apple Music and Spotify!)</p>
                <p>Because these songs are deeply tied to the emotional pacing of specific scenes, you can choose whether or not to automatically play background audio when turning a page.</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Background Audio Preference:</h4>
                    <select id="wizard-audio-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="false">Turned Off (Default)</option>
                        <option value="true">Play Automatically</option>
                    </select>
                </div>
            </div>

            <!-- STEP 5: Scene-Specific Atmospheric Themes (`customThemeEnabled` in `reader-settings`) -->
            <div id="wizard-step-5" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Immersive Story Themes</h2>
                <p>We're almost done! I'm Eleanor, and this is my sister Isabel.</p>
                <p>Some of the narratives here feature unique, immersive aesthetics designed specifically for that scene (like reading under a dark, rainy 4 AM sky). We highly recommend leaving this enabled for the full experience, but you can choose to force your standard color mode instead.</p>
                <div style="margin-top: 2rem; padding: 1.5rem; background: var(--rs-bg); border-radius: 8px; border: 1px solid var(--rs-border);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                        <!-- Pairing `<input id="...">` with `<label for="...">` lets readers click the text label too -->
                        <input type="checkbox" id="wizard-custom-theme-toggle" checked style="width: 1.25rem; height: 1.25rem; accent-color: var(--rs-primary);">
                        <label for="wizard-custom-theme-toggle" style="font-size: 1rem; font-weight: 600; cursor: pointer;">Allow Custom Story Themes</label>
                    </div>
                    <p style="font-size: 0.9rem; color: var(--rs-text-muted); margin: 0; margin-top: 0.5rem; padding-left: 2rem;">If unchecked, the archives will always use the color mode you selected in Step 2.</p>
                </div>
            </div>

            <!-- STEP 6: Offline Reading & Progressive Web App (PWA) Caching Introduction -->
            <div id="wizard-step-6" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Offline Reading</h2>
                <p>Hi! I'm Oliver, the one responsible for the reading pane itself.</p>
                <p>Did you know that you can download entire narratives to your device? This means you can read without an internet connection—perfect for airplanes, road trips, or just saving data.</p>
                <p>Just look for the Offline switches in the Library settings to download the narratives you want to take with you.</p>
                <div style="margin-top: 2rem; padding: 1.5rem; background: var(--rs-bg); border-radius: 8px; border: 1px solid var(--rs-border); text-align: center;">
                    <i class="ph ph-cloud-arrow-down" style="font-size: 2.5rem; color: var(--rs-primary); margin-bottom: 0.5rem; display: inline-block;"></i>
                    <p style="font-size: 0.95rem; font-weight: 600; margin: 0;">You can manage your offline books anytime from the Settings menu!</p>
                </div>
            </div>

            <!--
              WIZARD FOOTER NAVIGATION BUTTONS:
              - `#wizard-btn-prev` starts with `visibility: hidden` on Step 1 so the Next button stays right-aligned.
              - On Step 6, `reader.js` hides `#wizard-btn-next` and reveals `#wizard-btn-finish`, which marks
                onboarding complete in `localStorage` and navigates the reader into the Library!
            -->
            <div class="wizard-footer">
                <button id="wizard-btn-prev" class="rs-btn" style="visibility: hidden;">&larr; Back</button>
                <button id="wizard-btn-next" class="rs-btn rs-btn-primary">Next &rarr;</button>
                <button id="wizard-btn-finish" class="rs-btn rs-btn-primary" style="display: none;">Start Reading &rarr;</button>
            </div>
            
        </div>
    </div>
</div>
</div>
