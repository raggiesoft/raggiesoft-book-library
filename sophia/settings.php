<?php
// Sophia's Settings & About View
global $cdnBaseUrl, $siteName, $requestUri;
?>

<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    <div style="max-width: 800px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
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

        <!-- APP SETTINGS (Migrated from Dialog) -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-text-aa"></i> Typography & Theme
            </h2>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                <!-- Font Size -->
                <div>
                    <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Font Size</label>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <button type="button" id="rs-settings-font-dec" class="rs-btn" style="flex: 1; font-size: 1.2rem;">A-</button>
                        <span id="rs-settings-font-val" style="font-weight: bold; font-family: monospace; font-size: 1.1rem; min-width: 3ch; text-align: center;">100</span>
                        <button type="button" id="rs-settings-font-inc" class="rs-btn" style="flex: 1; font-size: 1.2rem;">A+</button>
                    </div>
                </div>

                <!-- Theme -->
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
                
                <!-- Font Family -->
                <div>
                    <label for="rs-settings-font-family" style="display: block; font-weight: bold; margin-bottom: 0.5rem; font-size: 0.95rem;">Typeface</label>
                    <select id="rs-settings-font-family" class="rs-input" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <option value="system-ui">System Default (San Francisco/Roboto)</option>
                        <option value="georgia">Georgia (Serif)</option>
                        <option value="lora">Lora (Serif)</option>
                        <option value="inter">Inter (Sans-Serif)</option>
                        <option value="atkinson">Atkinson Hyperlegible (Sans-Serif)</option>
                        <option value="dyslexic">OpenDyslexic</option>
                    </select>
                </div>

                <!-- Line Height -->
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
            
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <button type="button" id="rs-settings-reset-all" class="rs-btn rs-btn-danger" style="width: 100%;">Reset All Settings to Default</button>
            </div>
        </div>

        <!-- ABOUT THE DEVELOPER -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-code"></i> About the Developer
            </h2>
            
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="https://assets.raggiesoft.com/raggiesoft-books/images/about/isabel.jpg" alt="Isabel" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.2rem; font-weight: 700;">Isabel</h3>
                        <p style="margin: 0; font-size: 0.95rem; opacity: 0.8; line-height: 1.5;">Lead Developer & Architect. The book reader was her vision, coded to ensure the family's stories survive digitally across the universe.</p>
                    </div>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--rs-border); margin: 0;">
                <h4 style="margin: 0; font-size: 1rem; color: var(--rs-primary);">Acknowledgments & Support</h4>
                
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="https://assets.raggiesoft.com/raggiesoft-books/images/about/eleanor.jpg" alt="Eleanor" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Eleanor</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">Content Strategy & Editorial Lead. Ensuring every narrative fragment is properly archived.</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="https://assets.raggiesoft.com/raggiesoft-books/images/about/sophia.jpg" alt="Sophia" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Sophia</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">UI/UX Design. Keeping the interface clean, intuitive, and accessible.</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="https://assets.raggiesoft.com/raggiesoft-books/images/about/oliver.jpg" alt="Oliver" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Oliver</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">Systems & Infrastructure. Making sure the servers run and everything stays online.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>

<!-- Settings JS Logic (runs when this page loads) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // --- Load Current Settings ---
    const currentSizeStr = localStorage.getItem('rs-font-size') || '100';
    let currentSize = parseInt(currentSizeStr, 10);
    const sizeValDisplay = document.getElementById('rs-settings-font-val');
    sizeValDisplay.textContent = currentSize;

    const currentTheme = localStorage.getItem('rs-theme') || 'auto';
    const themeSelect = document.getElementById('rs-settings-theme');
    if(themeSelect) themeSelect.value = currentTheme;

    const currentFont = localStorage.getItem('rs-font-family') || 'system-ui';
    const fontSelect = document.getElementById('rs-settings-font-family');
    if(fontSelect) fontSelect.value = currentFont;

    const currentLH = localStorage.getItem('rs-line-height') || '1.6';
    const lhSelect = document.getElementById('rs-settings-line-height');
    if(lhSelect) lhSelect.value = currentLH;
    
    // --- Event Listeners ---
    document.getElementById('rs-settings-font-dec').addEventListener('click', () => {
        if (currentSize > 60) {
            currentSize -= 10;
            localStorage.setItem('rs-font-size', currentSize);
            sizeValDisplay.textContent = currentSize;
            applyThemeGlobally();
        }
    });

    document.getElementById('rs-settings-font-inc').addEventListener('click', () => {
        if (currentSize < 200) {
            currentSize += 10;
            localStorage.setItem('rs-font-size', currentSize);
            sizeValDisplay.textContent = currentSize;
            applyThemeGlobally();
        }
    });

    themeSelect.addEventListener('change', (e) => {
        localStorage.setItem('rs-theme', e.target.value);
        applyThemeGlobally();
    });

    fontSelect.addEventListener('change', (e) => {
        localStorage.setItem('rs-font-family', e.target.value);
        applyThemeGlobally();
    });

    lhSelect.addEventListener('change', (e) => {
        localStorage.setItem('rs-line-height', e.target.value);
        applyThemeGlobally();
    });

    document.getElementById('rs-settings-reset-all').addEventListener('click', () => {
        if(confirm("Are you sure you want to reset all reading settings to default?")) {
            localStorage.removeItem('rs-font-size');
            localStorage.removeItem('rs-theme');
            localStorage.removeItem('rs-font-family');
            localStorage.removeItem('rs-line-height');
            
            // Reload page to re-initialize defaults
            window.location.reload();
        }
    });

    // We can re-use the applyTheme() logic if needed, but since we want it to affect the app shell immediately:
    function applyThemeGlobally() {
        const theme = localStorage.getItem('rs-theme') || 'auto';
        document.body.classList.remove('theme-auto', 'theme-light', 'theme-sepia', 'theme-dark', 'theme-dark-sepia', 'theme-sepia-system');
        document.body.classList.add('theme-' + theme);

        const event = new CustomEvent('rs-settings-changed');
        document.dispatchEvent(event);
    }
});
</script>
