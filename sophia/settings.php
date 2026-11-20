<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/settings.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `/settings` on `books.raggiesoft.com` — the global
 *     "Preferences & Reading Settings" control center for the RaggieSoft Book Reader.
 *
 * HOW IT CONNECTS TO `eleanor/isabel.php`, `header.php`, & `reader.js`:
 *     1. `eleanor/isabel.php` routes `/settings` here (`$isSettings = true`),
 *        setting `$robotsMeta = 'noindex, follow'` since preference controls are
 *        personal UI rather than indexable book content.
 *     2. Every preference adjusted on this screen is saved immediately into the
 *        browser's `window.localStorage` (`'reader-settings'`, `'rs-line-height'`,
 *        and `'rs-launch-behavior'`) — zero accounts, cookies, or database tables!
 *     3. Whenever a setting changes, the inline `<script>` updates CSS Custom
 *        Properties (`--rs-font-family`, `--rs-font-size`, `--rs-line-height`) on
 *        `document.documentElement` (`<html>`) AND updates `#rs-settings-preview`
 *        in real time so the reader sees the exact look before tapping "Back to Reading"!
 *     4. On every subsequent page load, the synchronous script at the top of
 *        `header.php` reads those exact `localStorage` keys before the first pixel
 *        paints so there is zero Flash of Unstyled Content (FOUC).
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - A smart "Back to Reading" pill button (`#rs-settings-back-btn`) that
 *       reads `localStorage.getItem('rs-last-read')` so readers (especially in
 *       fullscreen standalone PWA mode with no browser Back button!) jump right
 *       back to the exact chapter they were reading.
 *     - A **Live Reading Preview** box (`#rs-settings-preview`) styled with `.story-content`.
 *     - Controls for Font Size (`A-`, percentage reset pill, `A+`), Reading Theme
 *       (`Auto`, `Sepia System`, `Crisp Light`, `Classic Sepia`, `True Dark`, `Dark Sepia`),
 *       Typeface (`System`, `Georgia`, `Lora`, `Inter`, `Atkinson Hyperlegible`, `OpenDyslexic`),
 *       Line Spacing (`1.4` to `2.0`), Desktop Page Width (`800px`, `1200px`, `100%`),
 *       Foreign Language tap-to-translate default, Background Audio autoplay,
 *       Book-Specific Atmospheric Themes, Offline Download Card visibility, App
 *       Launch Behavior (`Go to Home Screen` vs `Resume Last Read Book`), and a
 *       `Reset All Settings to Default` button.
 *     - Legal & Policy links, MIT / CC BY-SA 4.0 licensing, and Work of Fiction disclaimer.
 * =============================================================================
 */

// Import global variables from the Stardust Master Router (`eleanor/isabel.php`)
global $cdnBaseUrl, $siteName, $requestUri;
?>

<!-- 
  MAIN SCROLLABLE CONTAINER:
  `flex: 1` fills the viewport height; `padding-bottom: 6rem` ensures the bottom card
  clears the fixed mobile tab bar (`bottom-nav.php`).
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    <div style="max-width: 800px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- 
          SMART "BACK TO READING" NAVIGATION BAR:
          Hydrated by Step 0 of the `<script>` below using `localStorage['rs-last-read']`.
          Essential when installed to an iPhone/iPad/Android home screen as a standalone PWA
          (where browser URL/Back bars are hidden).
        -->
        <div id="rs-settings-back-bar" style="margin-bottom: 1.25rem;">
            <a href="/" id="rs-settings-back-btn" class="rs-btn" style="display: inline-flex; align-items: center; gap: 0.6rem; padding: 0.6rem 1.1rem; border-radius: 999px; background: var(--rs-surface); border: 1px solid var(--rs-border); color: var(--rs-text); text-decoration: none; font-weight: 600; font-size: 0.95rem; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                <i class="ph-bold ph-arrow-left" style="color: var(--rs-primary); font-size: 1.1rem;"></i>
                <span id="rs-settings-back-label">Back to Reading</span>
            </a>
        </div>

        <!-- PAGE HEADER -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <p style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Preferences
                </p>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Settings
                </h1>
            </div>
        </div>

        <!-- CARD 1: TYPOGRAPHY, THEME, & APP BEHAVIOR CONTROLS -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-text-aa"></i> Typography & Theme
            </h2>

            <!-- 
              LIVE TYPOGRAPHY & THEME PREVIEW:
              Uses the exact `.story-content` class from `oliver.php` so changes to font family,
              font size (`rem`), line spacing, and color mode update this sample paragraph live!
            -->
            <div style="margin-bottom: 1.5rem; padding: 1.25rem; border-radius: 10px; background: var(--rs-bg); border: 1px solid var(--rs-border);">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-primary); margin-bottom: 0.5rem;">
                    Live Reading Preview
                </div>
                <div id="rs-settings-preview" class="story-content" style="margin: 0;">
                    <p style="margin: 0;">
                        The warm afternoon light filtered through the limestone arches of Bennett Hall, casting long, quiet shadows across the courtyard as a new semester began at Commonwealth Polytechnic Institute.
                    </p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                
                <!-- 1. FONT SIZE STEPPER CONTROLS (`0.85rem` min to `2.5rem` max; `1.15rem` = `100%` baseline) -->
                <div>
                    <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Font Size</label>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <button type="button" id="rs-settings-font-dec" class="rs-btn" style="flex: 1; font-size: 1.15rem;" aria-label="Decrease font size">A-</button>
                        <!-- Center percentage pill: displays current scale and resets to `100%` (`1.15rem`) when clicked -->
                        <button type="button" id="rs-settings-font-reset" class="rs-btn" title="Reset font size to 100%" style="font-weight: bold; font-family: monospace; font-size: 1rem; min-width: 5.5rem; text-align: center; background: var(--rs-bg);">
                            <span id="rs-settings-font-val">100%</span>
                        </button>
                        <button type="button" id="rs-settings-font-inc" class="rs-btn" style="flex: 1; font-size: 1.15rem;" aria-label="Increase font size">A+</button>
                    </div>
                </div>

                <!-- 2. READING THEME SELECTOR (`<label for="...">` explicitly linked to `<select id="...">` for WCAG 1.3.1 / 4.1.2) -->
                <div>
                    <label for="rs-settings-theme" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Reading Theme</label>
                    <select id="rs-settings-theme" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <optgroup label="System Match">
                            <option value="auto">Auto (Match OS)</option>
                            <option value="sepia-system">Sepia System (Adaptive)</option>
                        </optgroup>
                        <optgroup label="Light Themes">
                            <option value="light">Crisp Light</option>
                            <option value="sepia">Classic Sepia</option>
                        </optgroup>
                        <optgroup label="Dark Themes">
                            <option value="dark">True Dark</option>
                            <option value="dark-sepia">Dark Sepia</option>
                        </optgroup>
                    </select>
                </div>
                
                <!-- 3. TYPEFACE (FONT FAMILY) SELECTOR -->
                <div>
                    <label for="rs-settings-font-family" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Typeface</label>
                    <select id="rs-settings-font-family" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="system">System Default (San Francisco/Roboto)</option>
                        <option value="georgia">Georgia (Serif)</option>
                        <option value="lora">Lora (Classic Serif)</option>
                        <option value="inter">Inter (Modern Sans)</option>
                        <option value="atkinson">Atkinson Hyperlegible (Accessibility)</option>
                        <option value="opendyslexic">OpenDyslexic</option>
                    </select>
                </div>

                <!-- 4. LINE SPACING (`line-height`) SELECTOR (WCAG 1.4.12 Text Spacing support) -->
                <div>
                    <label for="rs-settings-line-height" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Line Spacing</label>
                    <select id="rs-settings-line-height" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="1.4">Compact (1.4)</option>
                        <option value="1.6">Normal (1.6)</option>
                        <option value="1.8">Relaxed (1.8)</option>
                        <option value="2.0">Loose (2.0)</option>
                    </select>
                </div>

                <!-- 5. ARTICLE MEASURE / PAGE WIDTH SELECTOR (Desktop & Tablet) -->
                <div>
                    <label for="rs-settings-width" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Page Width (Desktop / Tablet)</label>
                    <select id="rs-settings-width" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="default">Standard Reading Width (800px)</option>
                        <option value="wide">Wide Margin (1200px)</option>
                        <option value="full">Full Screen Width (100%)</option>
                    </select>
                </div>
                
                <!-- 6. FOREIGN LANGUAGE PHRASE DISPLAY (Controls `.foreign-language` spans in `reader.js`) -->
                <div>
                    <label for="rs-settings-translation" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Foreign Language Display</label>
                    <select id="rs-settings-translation" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="original">Original Text (Tap to Translate)</option>
                        <option value="translated">English Translation (Tap for Original)</option>
                    </select>
                </div>

                <!-- 7. BACKGROUND SOUNDTRACK AUTOPLAY CHECKBOX -->
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <label for="rs-settings-audio" style="font-weight: bold; font-size: 0.95rem; margin: 0; cursor: pointer;">Enable Background Audio</label>
                    <input type="checkbox" id="rs-settings-audio" style="width: 1.5rem; height: 1.5rem; cursor: pointer;">
                </div>

                <!-- 8. SCENE-SPECIFIC NARRATIVE THEMES CHECKBOX (e.g., `dark-rain`) -->
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <label for="rs-settings-narrative-themes" style="font-weight: bold; font-size: 0.95rem; margin: 0; cursor: pointer;">Allow Book-Specific Themes</label>
                    <input type="checkbox" id="rs-settings-narrative-themes" style="width: 1.5rem; height: 1.5rem; cursor: pointer;">
                </div>

                <!-- 9. "TAKE THIS STORY OFFLINE" E-BOOK CARD VISIBILITY CHECKBOX (`#rs-offline-cta-card` in `oliver.php`) -->
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <label for="rs-settings-offline-cta" style="font-weight: bold; font-size: 0.95rem; margin: 0; cursor: pointer;">Show "Take this Story Offline" Download Card</label>
                    <input type="checkbox" id="rs-settings-offline-cta" style="width: 1.5rem; height: 1.5rem; cursor: pointer;">
                </div>

                <!-- 10. APP LAUNCH BEHAVIOR (`home.php` checks `'rs-launch-behavior'` on `/` load) -->
                <div>
                    <label for="rs-settings-launch-behavior" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">On App Launch</label>
                    <select id="rs-settings-launch-behavior" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="home">Go to Home Screen</option>
                        <option value="resume">Resume Last Read Book</option>
                    </select>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--rs-border); margin: 2rem 0;">
            
            <!-- FACTORY RESET BUTTON (Clears settings keys from `localStorage` after user confirmation) -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <button type="button" id="rs-settings-reset-all" class="rs-btn rs-btn-danger" style="width: 100%;">Reset All Settings to Default</button>
            </div>
        </div>

        <!-- CARD 2: LEGAL, ACCESSIBILITY, LICENSING & FICTITIOUS DISCLAIMER -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-scales"></i> Legal & Policies
            </h2>
            
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                
                <!-- Internal route to `sophia/accessibility.php` -->
                <a href="/accessibility" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-wheelchair"></i> Accessibility Statement
                </a>
                
                <!-- External policy links on `raggiesoft.com` -->
                <a href="https://raggiesoft.com/about/terms" target="_blank" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-scroll"></i> Terms of Service <i class="ph ph-arrow-square-out" style="margin-left: auto; color: var(--rs-text-muted);"></i>
                </a>
                
                <a href="https://raggiesoft.com/about/privacy" target="_blank" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-shield-check"></i> Privacy Policy <i class="ph ph-arrow-square-out" style="margin-left: auto; color: var(--rs-text-muted);"></i>
                </a>
                
                <a href="https://raggiesoft.com/about/ai-disclaimer" target="_blank" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-robot"></i> AI Disclaimer <i class="ph ph-arrow-square-out" style="margin-left: auto; color: var(--rs-text-muted);"></i>
                </a>

                
                <!-- Licensing & Mandatory Work of Fiction Disclaimer -->
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--rs-border);">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--rs-primary);">Copyright & Licensing (Stardust Engine v0.1.0)</h4>
                    <p style="font-size: 0.9rem; color: var(--rs-text-muted); margin-bottom: 0.5rem;">
                        <strong>Book Content:</strong> Licensed under <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" style="color: inherit; font-weight: bold;">CC BY-SA 4.0</a>.
                    </p>
                    <p style="font-size: 0.9rem; color: var(--rs-text-muted); margin-bottom: 0.5rem;">
                        <strong>Reader Software:</strong> Released under the <a href="https://opensource.org/licenses/MIT" target="_blank" style="color: inherit; font-weight: bold;">MIT License</a>.
                    </p>
                    <p style="font-size: 0.9rem; color: var(--rs-text-muted); margin-bottom: 0.75rem;">
                        <strong>Copyright:</strong> &copy; <?php echo date('Y'); ?> RaggieSoft. All rights reserved.
                    </p>
                    <p style="font-size: 0.82rem; color: var(--rs-text-muted); line-height: 1.5; margin-bottom: 0;">
                        <strong>Work of Fiction Disclaimer:</strong> All novels, stories, characters, bands, universities (including Commonwealth Polytechnic Institute / CPI), corporations (including OmniCorp and Apex Records), and events in the Ocean View Archives and across the RaggieSoft network are works of fiction. Any resemblance to actual persons, living or dead, businesses, institutions, or events is purely coincidental.
                    </p>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- 
  BOTTOM NAVIGATION COMPONENT:
  Includes the persistent mobile/tablet bottom tab bar (`includes/components/bottom-nav.php`).
-->
<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>

<!-- 
  CLIENT-SIDE SETTINGS ENGINE (`localStorage` + CSS Custom Properties):
  100-Level JavaScript Walkthrough:
  1. Reads saved user preferences from `localStorage` (falling back to sensible defaults).
  2. Hydrates every `<select>` dropdown, `<input type="checkbox">`, font percentage readout,
     and the "Back to Reading" button to reflect the user's current saved state.
  3. Attaches `click` and `change` event listeners so every adjustment immediately:
       - Saves the updated object back to `localStorage` via `JSON.stringify()`,
       - Updates `--rs-font-family`, `--rs-font-size`, and `--rs-line-height` on `<html>`,
       - Updates the Live Reading Preview (`#rs-settings-preview`), and
       - Calls global helper hooks in `reader.js` (`window.applyWidth`, `window.applyReaderTheme`, etc.)!
-->
<script>
(function() {
    /**
     * Dictionary mapping short storage keys (`'lora'`, `'atkinson'`, `'opendyslexic'`)
     * to full CSS `font-family` fallback stacks. Kept identical to `header.php` and `reader.js`.
     */
    const fontMap = window.RS_FONT_MAP || {
        'system': 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        'georgia': '"Georgia", serif',
        'lora': '"Lora", "Georgia", serif',
        'inter': '"Inter", system-ui, -apple-system, sans-serif',
        'atkinson': '"Atkinson Hyperlegible", system-ui, -apple-system, sans-serif',
        'opendyslexic': '"OpenDyslexic", "Comic Sans MS", sans-serif'
    };

    /**
     * Sanitizes any stored font value (including older legacy CSS strings) into one of the
     * 6 canonical keys in `fontMap` so `<select id="rs-settings-font-family">` always matches.
     */
    function normalizeFontKey(rawFont) {
        if (typeof window.rsNormalizeFontKey === 'function') {
            return window.rsNormalizeFontKey(rawFont);
        }
        if (!rawFont || typeof rawFont !== 'string') return 'system';
        const lower = rawFont.toLowerCase().trim();
        if (fontMap[lower]) return lower;
        if (lower.includes('opendyslexic')) return 'opendyslexic';
        if (lower.includes('atkinson')) return 'atkinson';
        if (lower.includes('inter')) return 'inter';
        if (lower.includes('lora')) return 'lora';
        if (lower.includes('georgia')) return 'georgia';
        return 'system';
    }

    // --- STEP 0: Hydrate the "Back to Reading" Button (`#rs-settings-back-btn`) ---
    // `reader.js` records the path and title of every story chapter visited in `rs-last-read`
    // and `rs-last-read-title`. We ignore platform utility routes (`nonBookRoutes`) so the button
    // never loops back onto `/settings` itself!
    const backBtn = document.getElementById('rs-settings-back-btn');
    const backLabel = document.getElementById('rs-settings-back-label');
    const nonBookRoutes = [
        '/', '/library', '/catalog', '/settings', '/bookmarks',
        '/about', '/accessibility', '/welcome', '/setup',
        '/knox', '/aethel-saga', '/search'
    ];
    const lastReadPath = localStorage.getItem('rs-last-read');
    const lastReadTitle = localStorage.getItem('rs-last-read-title');
    const hasValidLastRead = Boolean(lastReadPath && !nonBookRoutes.includes(lastReadPath));

    if (backBtn) {
        if (hasValidLastRead) {
            // Point the button directly at the reader's active chapter!
            backBtn.setAttribute('href', lastReadPath);
            if (backLabel) {
                backLabel.textContent = lastReadTitle
                    ? 'Back to Reading (' + lastReadTitle + ')'
                    : 'Back to Reading';
            }
        } else {
            // First-time visitor with no active book yet: fall back to `/` (or `history.back()` if same-site)
            backBtn.setAttribute('href', '/');
            if (backLabel) {
                backLabel.textContent = 'Back';
            }
            backBtn.addEventListener('click', (e) => {
                if (window.history.length > 1 && document.referrer && document.referrer.includes(window.location.host)) {
                    e.preventDefault();
                    window.history.back();
                }
            });
        }
    }

    /**
     * Reads `'reader-settings'` JSON from `localStorage` and merges it over `defaultSettings`
     * via `Object.assign({}, defaultSettings, parsed)` so newly added settings always have a default.
     */
    function getReaderSettings() {
        const defaultSettings = {
            theme: 'auto',
            customThemeEnabled: true,
            fontSize: 1.15,
            width: 'default',
            fontFamily: 'system',
            autoPlayAudio: 'false',
            translationMode: 'original',
            showOfflineCta: true
        };
        try {
            const stored = localStorage.getItem('reader-settings');
            if (stored) {
                const parsed = JSON.parse(stored);
                parsed.fontFamily = normalizeFontKey(parsed.fontFamily);
                return Object.assign({}, defaultSettings, parsed);
            }
        } catch(e) {}
        return defaultSettings;
    }

    /**
     * Serializes the updated settings object to JSON and writes it to `localStorage['reader-settings']`.
     */
    function saveReaderSettings(settings) {
        settings.fontFamily = normalizeFontKey(settings.fontFamily);
        localStorage.setItem('reader-settings', JSON.stringify(settings));
    }

    const settings = getReaderSettings();
    // Write back normalized settings right away so any legacy font name is upgraded cleanly
    saveReaderSettings(settings);

    const previewEl = document.getElementById('rs-settings-preview');

    /**
     * Applies the chosen font family, font size (`rem`), and line spacing both globally
     * on `<html>` (`document.documentElement.style.setProperty(...)`) AND directly onto
     * the Live Reading Preview box (`#rs-settings-preview`).
     */
    function syncPreviewAndRootVars(s, lineHeightVal) {
        const fontKey = normalizeFontKey(s.fontFamily);
        const cssFont = fontMap[fontKey] || fontMap['system'];
        const sizeRem = (parseFloat(s.fontSize) || 1.15) + 'rem';
        const lh = lineHeightVal || localStorage.getItem('rs-line-height') || '1.8';

        document.documentElement.style.setProperty('--rs-font-family', cssFont);
        document.documentElement.style.setProperty('--rs-font-size', sizeRem);
        document.documentElement.style.setProperty('--rs-line-height', lh);
        document.body.style.fontFamily = cssFont;

        if (previewEl) {
            previewEl.style.fontFamily = cssFont;
            previewEl.style.fontSize = sizeRem;
            previewEl.style.lineHeight = lh;
        }
    }

    // --- STEP 1: Initialize the Font Size Percentage Readout (`1.15rem` = `100%`) ---
    const currentSize = parseFloat(settings.fontSize) || 1.15;
    const sizeValDisplay = document.getElementById('rs-settings-font-val');
    if (sizeValDisplay) sizeValDisplay.textContent = Math.round((currentSize / 1.15) * 100) + '%';

    // --- STEP 2: Populate Every Form Control with Current Saved Preferences ---
    const themeSelect = document.getElementById('rs-settings-theme');
    if (themeSelect) themeSelect.value = settings.theme || 'auto';

    const fontSelect = document.getElementById('rs-settings-font-family');
    if (fontSelect) fontSelect.value = normalizeFontKey(settings.fontFamily);

    const widthSelect = document.getElementById('rs-settings-width');
    if (widthSelect) widthSelect.value = settings.width || 'default';
    
    const transSelect = document.getElementById('rs-settings-translation');
    if (transSelect) transSelect.value = settings.translationMode || 'original';

    const audioSelect = document.getElementById('rs-settings-audio');
    if (audioSelect) audioSelect.checked = (String(settings.autoPlayAudio) === 'true');

    const narrativeThemesSelect = document.getElementById('rs-settings-narrative-themes');
    if (narrativeThemesSelect) narrativeThemesSelect.checked = (settings.customThemeEnabled !== false);

    const offlineCtaSelect = document.getElementById('rs-settings-offline-cta');
    if (offlineCtaSelect) offlineCtaSelect.checked = (settings.showOfflineCta !== false);

    const launchSelect = document.getElementById('rs-settings-launch-behavior');
    if (launchSelect) launchSelect.value = localStorage.getItem('rs-launch-behavior') || 'home';

    const lhSelect = document.getElementById('rs-settings-line-height');
    const initialLh = localStorage.getItem('rs-line-height') || '1.8';
    if (lhSelect) lhSelect.value = initialLh;

    // Sync CSS variables and `#rs-settings-preview` on initial render
    syncPreviewAndRootVars(settings, initialLh);

    /**
     * Helper called by `A-`, `A+`, and the `100%` reset button to round the new rem size
     * to 2 decimal places (`Math.round(newSize * 100) / 100`, avoiding floating-point drift
     * like `1.2500000000000002`), persist it, and refresh the percentage readout + preview.
     */
    function updateFontSize(newSize) {
        const s = getReaderSettings();
        s.fontSize = Math.round(newSize * 100) / 100;
        saveReaderSettings(s);
        if (sizeValDisplay) sizeValDisplay.textContent = Math.round((s.fontSize / 1.15) * 100) + '%';
        syncPreviewAndRootVars(s);
    }

    // --- STEP 3: Interactive Event Listeners for Every Control ---

    // Decrease Font Size (`A-`), clamped to a minimum floor of `0.85rem` (`~74%`) via `Math.max()`
    const decBtn = document.getElementById('rs-settings-font-dec');
    if (decBtn) {
        decBtn.addEventListener('click', () => {
            const s = getReaderSettings();
            updateFontSize(Math.max((parseFloat(s.fontSize) || 1.15) - 0.1, 0.85));
        });
    }

    // Increase Font Size (`A+`), clamped to a maximum ceiling of `2.5rem` (`~217%`) via `Math.min()`
    const incBtn = document.getElementById('rs-settings-font-inc');
    if (incBtn) {
        incBtn.addEventListener('click', () => {
            const s = getReaderSettings();
            updateFontSize(Math.min((parseFloat(s.fontSize) || 1.15) + 0.1, 2.5));
        });
    }

    // Reset Font Size to `100%` (`1.15rem`)
    const resetSizeBtn = document.getElementById('rs-settings-font-reset');
    if (resetSizeBtn) {
        resetSizeBtn.addEventListener('click', () => {
            updateFontSize(1.15);
        });
    }

    // Reading Theme dropdown listener
    if (themeSelect) {
        themeSelect.addEventListener('change', (e) => {
            const s = getReaderSettings();
            s.theme = e.target.value;
            saveReaderSettings(s);
            applyThemeGlobally(s.theme);
        });
    }

    // Typeface (Font Family) dropdown listener
    if (fontSelect) {
        fontSelect.addEventListener('change', (e) => {
            const s = getReaderSettings();
            s.fontFamily = normalizeFontKey(e.target.value);
            saveReaderSettings(s);
            syncPreviewAndRootVars(s);
            if (typeof window.applyFontFamily === 'function') {
                window.applyFontFamily(s.fontFamily);
            }
        });
    }

    // Desktop/Tablet Page Width (`default` 800px, `wide` 1200px, `full` 100%) listener
    if (widthSelect) {
        widthSelect.addEventListener('change', (e) => {
            const s = getReaderSettings();
            s.width = e.target.value;
            saveReaderSettings(s);
            if (typeof window.applyWidth === 'function') {
                window.applyWidth(s.width);
            }
        });
    }

    // Foreign Language Display (`original` vs `translated`) listener
    if (transSelect) {
        transSelect.addEventListener('change', (e) => {
            const s = getReaderSettings();
            s.translationMode = e.target.value;
            saveReaderSettings(s);
            if (typeof window.applyTranslationMode === 'function') {
                window.applyTranslationMode(s.translationMode);
            }
        });
    }

    // Background Audio Autoplay checkbox listener
    if (audioSelect) {
        audioSelect.addEventListener('change', (e) => {
            const s = getReaderSettings();
            s.autoPlayAudio = e.target.checked ? 'true' : 'false';
            saveReaderSettings(s);
        });
    }

    // Scene-Specific Atmospheric Themes (`customThemeEnabled`) checkbox listener
    if (narrativeThemesSelect) {
        narrativeThemesSelect.addEventListener('change', (e) => {
            const s = getReaderSettings();
            s.customThemeEnabled = e.target.checked;
            saveReaderSettings(s);
            applyThemeGlobally(s.theme);
        });
    }

    // "Take this Story Offline" CTA card visibility checkbox listener
    if (offlineCtaSelect) {
        offlineCtaSelect.addEventListener('change', (e) => {
            const s = getReaderSettings();
            s.showOfflineCta = e.target.checked;
            saveReaderSettings(s);
            if (typeof window.applyOfflineCtaSetting === 'function') {
                window.applyOfflineCtaSetting(s.showOfflineCta, false);
            }
        });
    }

    // App Launch Behavior (`'home'` vs `'resume'`) listener
    if (launchSelect) {
        launchSelect.addEventListener('change', (e) => {
            localStorage.setItem('rs-launch-behavior', e.target.value);
        });
    }

    // Line Spacing (`1.4`, `1.6`, `1.8`, `2.0`) listener
    if (lhSelect) {
        lhSelect.addEventListener('change', (e) => {
            const lh = e.target.value;
            localStorage.setItem('rs-line-height', lh);
            const s = getReaderSettings();
            syncPreviewAndRootVars(s, lh);
            if (typeof window.applyLineHeight === 'function') {
                window.applyLineHeight(lh);
            }
        });
    }

    // Reset All Settings button: asks for confirmation, deletes all 3 preference keys, and reloads
    const resetAllBtn = document.getElementById('rs-settings-reset-all');
    if (resetAllBtn) {
        resetAllBtn.addEventListener('click', () => {
            if (confirm("Are you sure you want to reset all reading settings to default?")) {
                localStorage.removeItem('reader-settings');
                localStorage.removeItem('rs-line-height');
                localStorage.removeItem('rs-launch-behavior');
                window.location.reload();
            }
        });
    }

    /**
     * Removes all 6 possible `theme-*` classes from `<body>`, applies the newly chosen
     * `theme-<name>` class, updates `document.documentElement.style.colorScheme` so native
     * browser scrollbars and `<select>` menus match Light/Dark mode, and notifies `reader.js`.
     */
    function applyThemeGlobally(theme) {
        document.body.classList.remove('theme-auto', 'theme-light', 'theme-sepia', 'theme-dark', 'theme-dark-sepia', 'theme-sepia-system');
        document.body.classList.add('theme-' + theme);
        document.documentElement.style.colorScheme = (theme === 'light' || theme === 'sepia')
            ? 'light'
            : ((theme === 'dark' || theme === 'dark-sepia') ? 'dark' : 'light dark');
        if (typeof window.applyReaderTheme === 'function') {
            window.applyReaderTheme(theme);
        }
    }
})();
</script>
