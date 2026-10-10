<?php
/**
 * =============================================================================
 * Architecture & Maintenance Guide: settings.php
 * =============================================================================
 * Purpose:
 *     This file handles the global preferences and "About" interface for the 
 *     Ocean View Archives reader. It allows users to modify typography, theme, 
 *     and line spacing, while also presenting legal information and credits.
 *
 * Design Principles:
 *     - Client-Side Persistence: All settings are immediately saved to `localStorage` 
 *       via JavaScript, avoiding unnecessary backend state or cookies.
 *     - Real-Time Updates: The `applyThemeGlobally()` function dispatches a custom 
 *       event (`rs-settings-changed`) to instantly update the UI without reloading.
 *     - Lore Integration: The "About" section explicitly references the narrative 
 *       lore (e.g., Isabel as the developer) to maintain immersion.
 *
 * Maintenance Notes:
 *     - When adding new themes or fonts, ensure the corresponding CSS classes 
 *       (e.g., `theme-[name]`) exist in the main stylesheet.
 *     - The `applyThemeGlobally` logic must clear all possible existing theme classes 
 *       before applying the new one. Keep the class list updated here.
 * =============================================================================
 */

// Import global variables from the Stardust routing engine
global $cdnBaseUrl, $siteName, $requestUri;
?>

<!-- 
  MAIN CONTAINER
  We use flex: 1 and a bottom padding (6rem) to ensure the content doesn't get 
  hidden behind the fixed bottom navigation bar on mobile devices.
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    <div style="max-width: 800px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- HEADER SECTION -->
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

        <!-- APP SETTINGS SECTION -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-text-aa"></i> Typography & Theme
            </h2>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                
                <!-- FONT SIZE CONTROLS -->
                <div>
                    <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Font Size</label>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <button type="button" id="rs-settings-font-dec" class="rs-btn" style="flex: 1; font-size: 1.2rem;">A-</button>
                        <!-- Dynamic readout of current font size percentage -->
                        <span id="rs-settings-font-val" style="font-weight: bold; font-family: monospace; font-size: 1.1rem; min-width: 3ch; text-align: center;">100</span>
                        <button type="button" id="rs-settings-font-inc" class="rs-btn" style="flex: 1; font-size: 1.2rem;">A+</button>
                    </div>
                </div>

                <!-- READING THEME SELECTOR -->
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
                
                <!-- TYPEFACE (FONT FAMILY) SELECTOR -->
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
                
                <!-- FOREIGN LANGUAGE DISPLAY -->
                <div>
                    <label for="rs-settings-translation" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Foreign Language Display</label>
                    <select id="rs-settings-translation" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="original">Original Text (Tap to Translate)</option>
                        <option value="translated">English Translation (Tap for Original)</option>
                    </select>
                </div>

                                <!-- BACKGROUND AUDIO -->
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <label for="rs-settings-audio" style="font-weight: bold; font-size: 0.95rem; margin: 0; cursor: pointer;">Enable Background Audio</label>
                    <input type="checkbox" id="rs-settings-audio" style="width: 1.5rem; height: 1.5rem; cursor: pointer;">
                </div>

                <!-- NARRATIVE THEMES TOGGLE -->
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <label for="rs-settings-narrative-themes" style="font-weight: bold; font-size: 0.95rem; margin: 0; cursor: pointer;">Allow Book-Specific Themes</label>
                    <input type="checkbox" id="rs-settings-narrative-themes" style="width: 1.5rem; height: 1.5rem; cursor: pointer;">
                </div>

                <!-- LAUNCH BEHAVIOR SELECTOR -->
                <div>
                    <label for="rs-settings-launch-behavior" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">On App Launch</label>
                    <select id="rs-settings-launch-behavior" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="home">Go to Home Screen</option>
                        <option value="resume">Resume Last Read Book</option>
                    </select>
                </div>

                <!-- LINE SPACING (LINE HEIGHT) SELECTOR -->
                <div>
                    <label for="rs-settings-line-height" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Line Spacing</label>
                    <select id="rs-settings-line-height" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="1.4">Compact (1.4)</option>
                        <option value="1.6">Normal (1.6)</option>
                        <option value="1.8">Relaxed (1.8)</option>
                        <option value="2.0">Loose (2.0)</option>
                    </select>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--rs-border); margin: 2rem 0;">
            
            <!-- RESET BUTTON -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <button type="button" id="rs-settings-reset-all" class="rs-btn rs-btn-danger" style="width: 100%;">Reset All Settings to Default</button>
            </div>
        </div>

        <!-- ABOUT THE DEVELOPER SECTION -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-scales"></i> Legal & Policies
            </h2>
            
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                
                <!-- Internal link to Accessibility (now natively themed) -->
                <a href="/accessibility" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-wheelchair"></i> Accessibility Statement
                </a>
                
                <!-- External links borrowing from raggiesoft-hub -->
                <a href="https://raggiesoft.com/about/terms" target="_blank" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-scroll"></i> Terms of Service <i class="ph ph-arrow-square-out" style="margin-left: auto; opacity: 0.5;"></i>
                </a>
                
                <a href="https://raggiesoft.com/about/privacy" target="_blank" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-shield-check"></i> Privacy Policy <i class="ph ph-arrow-square-out" style="margin-left: auto; opacity: 0.5;"></i>
                </a>
                
                <a href="https://raggiesoft.com/about/ai-disclaimer" target="_blank" class="rs-btn" style="text-align: left; display: flex; align-items: center; gap: 0.5rem; background: var(--rs-bg); border: 1px solid var(--rs-border); color: var(--rs-text); justify-content: flex-start;">
                    <i class="ph ph-robot"></i> AI Disclaimer <i class="ph ph-arrow-square-out" style="margin-left: auto; opacity: 0.5;"></i>
                </a>

                
                <!-- Licensing & Copyright Information -->
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--rs-border);">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--rs-primary);">Copyright & Licensing (Stardust Engine v0.1.0)</h4>
                    <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.5rem;">
                        <strong>Book Content:</strong> Licensed under <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" style="color: inherit; font-weight: bold;">CC BY-SA 4.0</a>.
                    </p>
                    <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.5rem;">
                        <strong>Reader Software:</strong> Released under the <a href="https://opensource.org/licenses/MIT" target="_blank" style="color: inherit; font-weight: bold;">MIT License</a>.
                    </p>
                    <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0;">
                        <strong>Copyright:</strong> &copy; <?php echo date('Y'); ?> RaggieSoft. All rights reserved.
                    </p>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- 
  BOTTOM NAVIGATION COMPONENT
  This includes the iOS-style tab bar at the bottom of the screen.
-->
<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>

<!-- 
  CLIENT-SIDE SETTINGS LOGIC 
  Handles the real-time updating of UI state and syncing to localStorage.
-->
<script>
(function() {
    // Helper to get reader settings
    function getReaderSettings() {
        let defaultSettings = {
            theme: 'auto',
            customThemeEnabled: true,
            fontSize: 1.15,
            width: 'default',
            fontFamily: 'system',
            autoPlayAudio: 'false',
            translationMode: 'original'
        };
        try {
            const stored = localStorage.getItem('reader-settings');
            if (stored) {
                let parsed = JSON.parse(stored);
                // Migrate legacy font family strings to simple keys
                if (parsed.fontFamily) {
                    let ff = parsed.fontFamily.toLowerCase();
                    if (ff.includes('opendyslexic')) parsed.fontFamily = 'opendyslexic';
                    else if (ff.includes('atkinson')) parsed.fontFamily = 'atkinson';
                    else if (ff.includes('inter')) parsed.fontFamily = 'inter';
                    else if (ff.includes('lora')) parsed.fontFamily = 'lora';
                    else if (ff.includes('georgia')) parsed.fontFamily = 'georgia';
                    else if (ff.includes('system') || ff.includes('sans-serif') || ff.includes('apple-system')) parsed.fontFamily = 'system';
                }
                return Object.assign({}, defaultSettings, parsed);
            }
        } catch(e) {}
        return defaultSettings;
    }

    function saveReaderSettings(settings) {
        localStorage.setItem('reader-settings', JSON.stringify(settings));
    }

    const settings = getReaderSettings();

    // 1. Initialize Font Size UI (Map 1.15 to something like 100%)
    let currentSize = settings.fontSize || 1.15;
    const sizeValDisplay = document.getElementById('rs-settings-font-val');
    if (sizeValDisplay) sizeValDisplay.textContent = Math.round((currentSize / 1.15) * 100) + '%';

    // 2. Initialize UI Elements
    const themeSelect = document.getElementById('rs-settings-theme');
    if(themeSelect) themeSelect.value = settings.theme || 'auto';

    const fontSelect = document.getElementById('rs-settings-font-family');
    if(fontSelect) fontSelect.value = settings.fontFamily || 'system';
    
    const transSelect = document.getElementById('rs-settings-translation');
    if(transSelect) transSelect.value = settings.translationMode || 'original';

    const audioSelect = document.getElementById('rs-settings-audio');
    if (audioSelect) audioSelect.checked = (settings.autoPlayAudio === 'true');

    const narrativeThemesSelect = document.getElementById('rs-settings-narrative-themes');
    if (narrativeThemesSelect) narrativeThemesSelect.checked = (settings.customThemeEnabled !== false);

    const launchSelect = document.getElementById('rs-settings-launch-behavior');
    if(launchSelect) launchSelect.value = localStorage.getItem('rs-launch-behavior') || 'home';

    const lhSelect = document.getElementById('rs-settings-line-height');
    if(lhSelect) lhSelect.value = localStorage.getItem('rs-line-height') || '1.6';

    // --- EVENT LISTENERS FOR CONTROLS ---
    document.getElementById('rs-settings-font-dec').addEventListener('click', () => {
        let s = getReaderSettings();
        s.fontSize = Math.max(s.fontSize - 0.1, 0.8);
        saveReaderSettings(s);
        if (sizeValDisplay) sizeValDisplay.textContent = Math.round((s.fontSize / 1.15) * 100) + '%';
        document.body.style.fontSize = s.fontSize + 'rem';
    });

    document.getElementById('rs-settings-font-inc').addEventListener('click', () => {
        let s = getReaderSettings();
        s.fontSize = Math.min(s.fontSize + 0.1, 2.5);
        saveReaderSettings(s);
        if (sizeValDisplay) sizeValDisplay.textContent = Math.round((s.fontSize / 1.15) * 100) + '%';
        document.body.style.fontSize = s.fontSize + 'rem';
    });

    if (themeSelect) {
        themeSelect.addEventListener('change', (e) => {
            let s = getReaderSettings();
            s.theme = e.target.value;
            saveReaderSettings(s);
            applyThemeGlobally(s.theme);
        });
    }

    const fontMap = {
        'system': 'system-ui, -apple-system, sans-serif',
        'georgia': '"Georgia", serif',
        'lora': '"Lora", serif',
        'inter': '"Inter", sans-serif',
        'atkinson': '"Atkinson Hyperlegible", sans-serif',
        'opendyslexic': '"OpenDyslexic", sans-serif'
    };
    if (fontSelect) {
        fontSelect.addEventListener('change', (e) => {
            let s = getReaderSettings();
            s.fontFamily = e.target.value;
            saveReaderSettings(s);
            document.body.style.fontFamily = fontMap[s.fontFamily] || fontMap['system'];
        });
    }

    if (audioSelect) {
        audioSelect.addEventListener('change', (e) => {
            let s = getReaderSettings();
            s.autoPlayAudio = e.target.checked ? 'true' : 'false';
            saveReaderSettings(s);
        });
    }

    if (narrativeThemesSelect) {
        narrativeThemesSelect.addEventListener('change', (e) => {
            let s = getReaderSettings();
            s.customThemeEnabled = e.target.checked;
            saveReaderSettings(s);
            if (!s.customThemeEnabled) {
                applyThemeGlobally(s.theme);
            } else {
                window.location.reload(); // Reload to restore book theme if on a book page
            }
        });
    }

    if (launchSelect) {
        launchSelect.addEventListener('change', (e) => {
            localStorage.setItem('rs-launch-behavior', e.target.value);
        });
    }

    if (lhSelect) {
        lhSelect.addEventListener('change', (e) => {
            localStorage.setItem('rs-line-height', e.target.value);
            document.body.style.lineHeight = e.target.value;
        });
    }

    document.getElementById('rs-settings-reset-all').addEventListener('click', () => {
        if(confirm("Are you sure you want to reset all reading settings to default?")) {
            localStorage.removeItem('reader-settings');
            localStorage.removeItem('rs-line-height');
            localStorage.removeItem('rs-launch-behavior');
            window.location.reload();
        }
    });

    function applyThemeGlobally(theme) {
        document.body.classList.remove('theme-auto', 'theme-light', 'theme-sepia', 'theme-dark', 'theme-dark-sepia', 'theme-sepia-system');
        document.body.classList.add('theme-' + theme);
    }
})();
</script>
