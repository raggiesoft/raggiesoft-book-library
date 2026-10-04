<?php
// Sophia's Reader Settings Dialog
?>
<!-- WIZARD95 DIALOG -->
<dialog id="reader-wizard-dialog" class="wizard-dialog">
    <div class="wizard-container">
        <!-- Left Sidebar Graphic -->
        <div id="wizard-sidebar-graphic" class="wizard-sidebar"></div>
        
        <!-- Right Content Area -->
        <div class="wizard-content">
            
            <!-- Step 1 -->
            <div id="wizard-step-1" class="wizard-step active-step">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Welcome to Ocean View Archives!</h2>
                <p>Hi there! I'm Isabel, the routing engine here at the Archives.</p>
                <p>Before you start reading, Oliver and I wanted to help you set up your reading environment. The Archives are designed for long, distraction-free reading sessions, so let's make sure the layout is comfortable for you.</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Choose your Article Width:</h4>
                    <select id="wizard-width-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="default">Default (800px) - Recommended</option>
                        <option value="wide">Wide (1200px)</option>
                        <option value="full">Full Screen (100%)</option>
                    </select>
                </div>
            </div>

            <!-- Step 2 -->
            <div id="wizard-step-2" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Themes & Text Size</h2>
                <p>Hey, I'm Eleanor! I handle the public entry points.</p>
                <p>If you're reading late at night, staring at a bright white screen can cause serious eye strain. We have a few themes available to help with that.</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Select a Theme:</h4>
                    <select id="wizard-theme-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="auto">System Default (Matches your OS)</option>
                        <option value="light">Light Mode (Crisp & Clean)</option>
                        <option value="dark">Dark Mode (Best for night)</option>
                        <option value="sepia">Sepia Mode (Easy on the eyes)</option>
                        <option value="dark-sepia">Dark Sepia (Warm & Dark)</option>
                    </select>
                </div>
            </div>

            <!-- Step 3 -->
            <div id="wizard-step-3" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Fonts & Accessibility</h2>
                <p>Hello! I'm Sophia, the keeper of the vault.</p>
                <p>My brother Oliver wants you to know about <strong>Atkinson Hyperlegible</strong>. It's a special font designed by the Braille Institute to make letters incredibly distinct and readable, especially for low-vision readers. Try it out!</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Choose your Typography:</h4>
                    <select id="wizard-font-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="system-ui, -apple-system, sans-serif">System Sans-Serif (Default)</option>
                        <option value="'Lora', serif">Lora (Classic Serif)</option>
                        <option value="'Inter', sans-serif">Inter (Modern Sans)</option>
                        <option value="'Atkinson Hyperlegible', sans-serif">Atkinson Hyperlegible (Accessibility)</option>
                    </select>
                </div>
            </div>

            <!-- Step 4 -->
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

            <!-- Step 5 -->
            <div id="wizard-step-5" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Immersive Story Themes</h2>
                <p>We're almost done! I'm Eleanor, and this is my sister Isabel.</p>
                <p>Some of the narratives here feature unique, immersive aesthetics designed specifically for that scene (like reading under a dark, rainy 4 AM sky). We highly recommend leaving this enabled for the full experience, but you can choose to force your standard color mode instead.</p>
                <div style="margin-top: 2rem; padding: 1.5rem; background: var(--rs-bg); border-radius: 8px; border: 1px solid var(--rs-border);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                        <input type="checkbox" id="wizard-custom-theme-toggle" checked style="width: 1.25rem; height: 1.25rem; accent-color: var(--rs-primary);">
                        <label for="wizard-custom-theme-toggle" style="font-size: 1rem; font-weight: 600; cursor: pointer;">Allow Custom Story Themes</label>
                    </div>
                    <p style="font-size: 0.9rem; opacity: 0.8; margin: 0; margin-top: 0.5rem; padding-left: 2rem;">If unchecked, the archives will always use the color mode you selected in Step 2.</p>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="wizard-footer">
                <button id="wizard-btn-prev" class="rs-btn" style="visibility: hidden;">&larr; Back</button>
                <button id="wizard-btn-next" class="rs-btn rs-btn-primary">Next &rarr;</button>
                <button id="wizard-btn-finish" class="rs-btn rs-btn-primary" style="display: none;">Start Reading &rarr;</button>
            </div>
            
        </div>
    </div>
</dialog>

<!-- STANDARD SETTINGS DIALOG -->
<dialog id="reader-settings-dialog" style="padding: 0; border-radius: 12px; border: 1px solid var(--rs-border); background: var(--rs-card-bg, #fff); color: var(--rs-text); max-width: 400px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden;">
    <div style="display: flex; flex-direction: column; height: 100%;">
        <div style="padding: 1.5rem 1.5rem 0 1.5rem; border-bottom: 1px solid var(--rs-border);">
            <h3 style="margin-top: 0; margin-bottom: 1rem; font-weight: bold;">Reader Settings</h3>
            <div style="display: flex; gap: 1rem; margin-bottom: -1px; overflow-x: auto;" id="reader-settings-tabs">
                <button type="button" class="settings-tab active" data-tab="layout">Layout</button>
                <button type="button" class="settings-tab" data-tab="theme">Theme</button>
                <button type="button" class="settings-tab" data-tab="advanced">Advanced</button>
            </div>
        </div>
        
        <div style="padding: 1.5rem; overflow-y: auto; max-height: 60vh;">
            <!-- LAYOUT TAB -->
            <div id="settings-tab-layout" class="settings-tab-content">
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Text Size</h4>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="rs-btn" id="btn-text-decrease" style="flex: 1;">A-</button>
                        <button type="button" class="rs-btn" id="btn-text-reset" style="flex: 1;">Default</button>
                        <button type="button" class="rs-btn" id="btn-text-increase" style="flex: 1;">A+</button>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Font Family</h4>
                    <select id="reader-font-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="system-ui, -apple-system, sans-serif">System Sans-Serif (Default)</option>
                        <option value="'Lora', serif">Lora (Serif)</option>
                        <option value="'Inter', sans-serif">Inter (Sans-Serif)</option>
                        <option value="'Atkinson Hyperlegible', sans-serif">Atkinson Hyperlegible</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Article Width</h4>
                    <select id="reader-width-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="default">Default (800px)</option>
                        <option value="wide">Wide (1200px)</option>
                        <option value="full">Full Width (100%)</option>
                    </select>
                </div>
            </div>

            <!-- THEME TAB -->
            <div id="settings-tab-theme" class="settings-tab-content" style="display: none;">
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Color Mode</h4>
                                        <select id="reader-theme-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="auto">System Default</option>
                        <option value="light">Light Mode</option>
                        <option value="dark">Dark Mode</option>
                        <option value="sepia">Sepia Mode</option>
                        <option value="dark-sepia">Dark Sepia</option>
                    </select>
                    <div style="margin-top: 1rem; padding: 1rem; background: var(--rs-bg, #f5f5f5); border-radius: 8px; border: 1px solid var(--rs-border);">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="checkbox" id="reader-custom-theme-toggle" checked style="width: 1.1rem; height: 1.1rem; accent-color: var(--rs-primary);">
                            <strong style="font-size: 0.95rem;">Allow Custom Story Themes</strong>
                        </div>
                        <p style="font-size: 0.85rem; opacity: 0.8; margin: 0;">Some scenes have unique, immersive aesthetics (like a rainy 4 AM night). Check this box to allow these themes to override your color mode when available.</p>
                    </div>

                </div>
            </div>

            <!-- ADVANCED TAB -->
            <div id="settings-tab-advanced" class="settings-tab-content" style="display: none;">
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Narrative Soundtrack</h4>
                    <select id="reader-audio-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="false">Turned Off</option>
                        <option value="true">Play Automatically</option>
                    </select>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--rs-border); margin: 2rem 0;">
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <button type="button" id="reader-run-wizard-btn" class="rs-btn" style="width: 100%;" onclick="runWelcomeWizard();"><i class="ph ph-magic-wand"></i> Run Welcome Wizard</button>
                    <button type="button" id="reader-settings-reset-all" class="rs-btn rs-btn-danger" style="width: 100%;">Reset All Settings to Default</button>
                </div>
            </div>
        </div>
        
        <div style="padding: 1.5rem; border-top: 1px solid var(--rs-border); text-align: center;">
            <button type="button" id="close-settings-btn" class="rs-btn rs-btn-primary" style="width: 100%;">Done</button>
        </div>
    </div>
</dialog>


<style>
/* Reader Themes applied to #stardust-reading-pane or body */
body.theme-dark {
    --rs-bg: #121212;
    --rs-bg-alt: #1a1a1a;
    --rs-card-bg: #1e1e1e;
    --rs-text: #e0e0e0;
    --rs-border: #333;
    --rs-surface: #1e1e1e;
}
body.theme-sepia {
    --rs-bg: #f4ecd8;
    --rs-bg-alt: #eaddc4;
    --rs-card-bg: #fdf6e3;
    --rs-text: #5c4b37;
    --rs-border: #e0d5c1;
    --rs-surface: #fdf6e3;
}
body.theme-dark-sepia {
    --rs-bg: #2b251e;
    --rs-bg-alt: #383028;
    --rs-card-bg: #1f1b16;
    --rs-text: #e0d5c1;
    --rs-border: #4a4135;
    --rs-surface: #1f1b16;
}

@media (prefers-color-scheme: dark) {
    body.theme-auto {
        --rs-bg: #121212;
        --rs-bg-alt: #1a1a1a;
        --rs-card-bg: #1e1e1e;
        --rs-text: #e0e0e0;
        --rs-border: #333;
        --rs-surface: #1e1e1e;
    }
}

dialog::backdrop {
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(2px);
}

/* WIZARD95 STYLES */
.wizard-dialog {
    padding: 0;
    border-radius: 12px;
    border: 1px solid var(--rs-border);
    background: var(--rs-card-bg, #fff);
    color: var(--rs-text);
    max-width: 700px;
    width: 100%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    overflow: hidden;
}
.wizard-container {
    display: flex;
    min-height: 450px;
}
.wizard-sidebar {
    width: 35%;
    background-color: #000;
    background-size: cover;
    background-position: center;
    border-right: 1px solid var(--rs-border);
}
.wizard-content {
    width: 65%;
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
}
.wizard-step p {
    margin-bottom: 1rem;
    line-height: 1.6;
    font-size: 0.95rem;
}
.wizard-footer {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    border-top: 1px solid var(--rs-border);
    padding-top: 1.5rem;
}

@media (max-width: 768px) {
    .wizard-container {
        flex-direction: column;
        min-height: auto;
    }
    .wizard-sidebar {
        width: 100%;
        display: none;
        background-position: top center;
        border-right: none;
        border-bottom: 1px solid var(--rs-border);
    }
    .wizard-content {
        width: 100%;
        padding: 1.5rem;
    }
}

.settings-tab {
    background: transparent;
    border: none;
    padding: 0.5rem 1rem;
    font-size: 0.95rem;
    color: var(--rs-text);
    opacity: 0.7;
    cursor: pointer;
    border-bottom: 2px solid transparent;
}
.settings-tab:hover {
    opacity: 1;
}
.settings-tab.active {
    opacity: 1;
    font-weight: 600;
    color: var(--rs-primary);
    border-bottom: 2px solid var(--rs-primary);
}

.rs-input {
    background: var(--rs-surface);
    color: var(--rs-text);
    border: 1px solid var(--rs-border);
    border-radius: 6px;
    font-family: inherit;
}
.rs-input:focus {
    outline: 2px solid var(--rs-primary);
    outline-offset: 1px;
}
</style>

<script>
(function() {
    const dialog = document.getElementById('reader-settings-dialog');
    const btnOpen = document.getElementById('reader-settings-toggle');
    const btnClose = document.getElementById('close-settings-btn');
    
    const contentBody = document.querySelector('.story-content');
    const themeSelect = document.getElementById('reader-theme-select');
    const widthSelect = document.getElementById('reader-width-select');
    const fontSelect = document.getElementById('reader-font-select');
    const audioSelect = document.getElementById('reader-audio-select');
    const customThemeToggle = document.getElementById('reader-custom-theme-toggle');
    const readerPage = document.querySelector('.reader-page');
    const btnResetAll = document.getElementById('reader-settings-reset-all');
    const btnRunWizard = document.getElementById('reader-run-wizard-btn');
    const tabBtns = document.querySelectorAll('.settings-tab');
    const tabContents = document.querySelectorAll('.settings-tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', (e) => { e.preventDefault();
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.style.display = 'none');
            btn.classList.add('active');
            document.getElementById('settings-tab-' + btn.dataset.tab).style.display = 'block';
        });
    });
    

    
    const btnIncrease = document.getElementById('btn-text-increase');
    const btnDecrease = document.getElementById('btn-text-decrease');
    const btnReset = document.getElementById('btn-text-reset');

    // Global State Variables (must be initialized before first run)
    let currentFontSize = 1.15; 
    let currentWidth = 'default';
    let currentFontFamily = 'system-ui, -apple-system, sans-serif';
    let currentAutoPlayAudio = 'false';
    let currentCustomThemeEnabled = true;

    
    // Load Settings
    try {
        const stored = localStorage.getItem('reader-settings');
        if (stored) {
            const settings = JSON.parse(stored);
            if (settings.theme) { applyTheme(settings.theme, settings.customThemeEnabled !== false); } else { applyTheme('auto', settings.customThemeEnabled !== false); }
            if (settings.fontSize) applyFontSize(settings.fontSize);
            if (settings.width) { applyWidth(settings.width); } else { applyWidth('default'); }
            if (settings.fontFamily) { applyFontFamily(settings.fontFamily); } else { applyFontFamily('system-ui, -apple-system, sans-serif'); }
            if (settings.autoPlayAudio) { applyAudioSetting(settings.autoPlayAudio); } else { applyAudioSetting('false'); }
        }
    } catch (e) {}
    if (!localStorage.getItem('reader-settings')) {
        applyTheme('auto', true);
        applyWidth('default');
        applyFontFamily('system-ui, -apple-system, sans-serif');
        applyAudioSetting('false');
    }

    // Font Size Handlers
    function applyFontSize(size) {
        currentFontSize = size;
        if (contentBody) {
            contentBody.style.fontSize = `${size}rem`;
        }
        saveSettings();
    }

    if (btnIncrease) btnIncrease.addEventListener('click', () => applyFontSize(Math.min(currentFontSize + 0.1, 2.5)));
    if (btnDecrease) btnDecrease.addEventListener('click', () => applyFontSize(Math.max(currentFontSize - 0.1, 0.8)));
    if (btnReset) btnReset.addEventListener('click', () => applyFontSize(1.15));

    // Theme Handlers
    function applyTheme(theme, customEnabled = true) {
        currentCustomThemeEnabled = customEnabled;
        if (customThemeToggle) customThemeToggle.checked = customEnabled;
        const wizCustomTheme = document.getElementById('wizard-custom-theme-toggle');
        if (wizCustomTheme) wizCustomTheme.checked = customEnabled;
        
        document.body.classList.remove('theme-light', 'theme-dark', 'theme-sepia', 'theme-dark-sepia', 'theme-auto', 'theme-custom');
        
        const customThemeMeta = document.querySelector('meta[name="stardust-narrative-theme"]');
        const narrativeTheme = customThemeMeta ? customThemeMeta.getAttribute('content') : null;
        const customThemeLink = document.getElementById('narrative-theme-css');
        
        // Remove old narrative class just in case it changed
        document.body.className = document.body.className.replace(/narrative-theme-[a-zA-Z0-9_-]+/g, '').trim();

        if (currentCustomThemeEnabled && narrativeTheme && narrativeTheme.trim() !== '') {
            document.body.classList.add('theme-custom');
            document.body.classList.add(`narrative-theme-${narrativeTheme}`);
            if (customThemeLink) customThemeLink.disabled = false;
        } else {
            if (customThemeLink) customThemeLink.disabled = true;
            document.body.classList.add(`theme-${theme}`);
        }
        
        if (themeSelect) themeSelect.value = theme;
        saveSettings();
    }

    if (themeSelect) {
        themeSelect.addEventListener('change', (e) => {
            applyTheme(e.target.value, currentCustomThemeEnabled);
        });
    }
    
    if (customThemeToggle) {
        customThemeToggle.addEventListener('change', (e) => {
            applyTheme(themeSelect ? themeSelect.value : 'auto', e.target.checked);
        });
    }

    // Width Handlers
    function applyWidth(width) {
        currentWidth = width;
        if (readerPage) {
            if (width === 'wide') {
                readerPage.style.maxWidth = '1200px';
            } else if (width === 'full') {
                readerPage.style.maxWidth = '100%';
            } else {
                readerPage.style.maxWidth = '800px';
            }
        }
        if (widthSelect) widthSelect.value = width;
        saveSettings();
    }
    
    if (widthSelect) {
        widthSelect.addEventListener('change', (e) => applyWidth(e.target.value));
    }

    // Font Family Handlers
    function applyFontFamily(font) {
        currentFontFamily = font;
        if (contentBody) {
            contentBody.style.fontFamily = font;
        }
        if (fontSelect) fontSelect.value = font;
        saveSettings();
    }

    if (fontSelect) {
        fontSelect.addEventListener('change', (e) => applyFontFamily(e.target.value));
    }

    function applyAudioSetting(val) {
        currentAutoPlayAudio = val;
        if (audioSelect) audioSelect.value = val;
        saveSettings();
    }
    if (audioSelect) {
        audioSelect.addEventListener('change', (e) => applyAudioSetting(e.target.value));
    }
    
    // Reset All Handler
    if (btnResetAll) {
        btnResetAll.addEventListener('click', () => {
            applyFontSize(1.15);
            applyTheme('auto', true);
            applyWidth('default');
            applyFontFamily('system-ui, -apple-system, sans-serif');
            applyAudioSetting('false');
            dialog.close();
        });
    }

    function saveSettings() {
        localStorage.setItem('reader-settings', JSON.stringify({
            theme: themeSelect ? themeSelect.value : 'auto',
            customThemeEnabled: currentCustomThemeEnabled,
            fontSize: currentFontSize,
            width: currentWidth,
            fontFamily: currentFontFamily,
            autoPlayAudio: currentAutoPlayAudio
        }));
    }

    // Modal Handlers
    const wizardDialog = document.getElementById('reader-wizard-dialog');
    const hasCompletedWizard = localStorage.getItem('rs-wizard-completed');

    if (btnOpen) {
        btnOpen.addEventListener('click', () => {
            if (localStorage.getItem('rs-wizard-completed')) {
                dialog.showModal();
            } else {
                wizardDialog.showModal();
                updateWizardState();
            }
        });
    }
    if (btnClose && dialog) {
        btnClose.addEventListener('click', () => dialog.close());
    }

    // WIZARD LOGIC
    let currentStep = 1;
    const totalSteps = 5;
    const wizardSidebar = document.getElementById('wizard-sidebar-graphic');
    const btnNext = document.getElementById('wizard-btn-next');
    const btnPrev = document.getElementById('wizard-btn-prev');
    const btnFinish = document.getElementById('wizard-btn-finish');
    window.runWelcomeWizard = function() {
        localStorage.removeItem('rs-wizard-completed');
        if (dialog && dialog.open) {
            dialog.close();
        }
        currentStep = 1;
        const wizDialog = document.getElementById('reader-wizard-dialog');
        if (wizDialog) {
            wizDialog.showModal();
            updateWizardState();
        }
    };
    
    // Sync wizard selects with global selects
    const wizTheme = document.getElementById('wizard-theme-select');
    const wizWidth = document.getElementById('wizard-width-select');
    const wizFont = document.getElementById('wizard-font-select');
    const wizAudio = document.getElementById('wizard-audio-select');
    
    // Initial sync
    if (wizTheme) wizTheme.value = themeSelect ? themeSelect.value : 'auto';
    if (wizWidth) wizWidth.value = widthSelect ? widthSelect.value : 'default';
    if (wizFont) wizFont.value = fontSelect ? fontSelect.value : 'system-ui, -apple-system, sans-serif';
    if (wizAudio) wizAudio.value = audioSelect ? audioSelect.value : 'false';
    const wizCustomTheme = document.getElementById('wizard-custom-theme-toggle');
    if (wizCustomTheme && customThemeToggle) wizCustomTheme.checked = customThemeToggle.checked;

    // Live update when wizard selects change
    if (wizTheme) wizTheme.addEventListener('change', (e) => applyTheme(e.target.value, currentCustomThemeEnabled));
    if (wizWidth) wizWidth.addEventListener('change', (e) => applyWidth(e.target.value));
    if (wizFont) wizFont.addEventListener('change', (e) => applyFontFamily(e.target.value));
    if (wizAudio) wizAudio.addEventListener('change', (e) => applyAudioSetting(e.target.value));
    if (wizCustomTheme) wizCustomTheme.addEventListener('change', (e) => applyTheme(themeSelect ? themeSelect.value : 'auto', e.target.checked));


    const stepImages = {
        1: '<?php echo $cdnBaseUrl; ?>/stardust-engine-library/images/wizard/isabel_oliver_hug.jpg',
        2: '<?php echo $cdnBaseUrl; ?>/stardust-engine-library/images/wizard/eleanor_oliver_hug.jpg',
        3: '<?php echo $cdnBaseUrl; ?>/stardust-engine-library/images/wizard/sophia_oliver_hug.jpg',
        4: '<?php echo $cdnBaseUrl; ?>/stardust-engine-library/images/wizard/sophia_isabel_audio.jpg',
        5: '<?php echo $cdnBaseUrl; ?>/stardust-engine-library/images/wizard/eleanor_isabel_twins.jpg'
    };

    function updateWizardState() {
        // Hide all steps
        for(let i = 1; i <= totalSteps; i++) {
            const stepEl = document.getElementById('wizard-step-' + i);
            if (stepEl) stepEl.style.display = 'none';
        }
        
        // Show current step
        const currentEl = document.getElementById('wizard-step-' + currentStep);
        if (currentEl) currentEl.style.display = 'block';
        
        // Update Sidebar Image
        if (wizardSidebar && stepImages[currentStep]) {
            wizardSidebar.style.backgroundImage = `url('${stepImages[currentStep]}')`;
        }

        // Update Buttons
        if (btnPrev) {
            btnPrev.style.visibility = currentStep > 1 ? 'visible' : 'hidden';
        }
        
        if (currentStep === totalSteps) {
            if (btnNext) btnNext.style.display = 'none';
            if (btnFinish) btnFinish.style.display = 'block';
        } else {
            if (btnNext) btnNext.style.display = 'block';
            if (btnFinish) btnFinish.style.display = 'none';
        }
    }

    if (btnNext) {
        btnNext.addEventListener('click', () => {
            if (currentStep < totalSteps) {
                currentStep++;
                updateWizardState();
            }
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateWizardState();
            }
        });
    }

    if (btnFinish) {
        btnFinish.addEventListener('click', () => {
            localStorage.setItem('rs-wizard-completed', 'true');
            wizardDialog.close();
        });
    }
})();
</script>
