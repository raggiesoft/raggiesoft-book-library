<?php
// Sophia's Reader Settings Dialog
?>
<dialog id="reader-settings-dialog" style="padding: 2rem; border-radius: 12px; border: 1px solid var(--rs-border); background: var(--rs-card-bg, #fff); color: var(--rs-text); max-width: 400px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-weight: bold;">Reader Settings</h3>
    
    <div style="margin-bottom: 1.5rem;">
        <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Text Size</h4>
        <div style="display: flex; gap: 0.5rem;">
            <button class="rs-btn" id="btn-text-decrease" style="flex: 1;">A-</button>
            <button class="rs-btn" id="btn-text-reset" style="flex: 1;">Default</button>
            <button class="rs-btn" id="btn-text-increase" style="flex: 1;">A+</button>
        </div>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Theme</h4>
        <select id="reader-theme-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
            <option value="light">Light Mode</option>
            <option value="dark">Dark Mode</option>
            <option value="sepia">Sepia Mode</option>
        </select>
    </div>
    
    <div style="text-align: right; margin-top: 2rem;">
        <button id="close-settings-btn" class="rs-btn rs-btn-primary">Done</button>
    </div>
</dialog>

<style>
/* Reader Themes applied to #stardust-reading-pane or body */
body.theme-dark {
    --rs-bg: #121212;
    --rs-card-bg: #1e1e1e;
    --rs-text: #e0e0e0;
    --rs-border: #333;
}
body.theme-sepia {
    --rs-bg: #f4ecd8;
    --rs-card-bg: #fdf6e3;
    --rs-text: #5c4b37;
    --rs-border: #e0d5c1;
}

dialog::backdrop {
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(2px);
}
</style>

<script>
(function() {
    const dialog = document.getElementById('reader-settings-dialog');
    const btnOpen = document.getElementById('reader-settings-toggle');
    const btnClose = document.getElementById('close-settings-btn');
    
    const contentBody = document.querySelector('.story-content');
    const themeSelect = document.getElementById('reader-theme-select');
    
    const btnIncrease = document.getElementById('btn-text-increase');
    const btnDecrease = document.getElementById('btn-text-decrease');
    const btnReset = document.getElementById('btn-text-reset');

    let currentFontSize = 1.15; // default rem
    
    // Load Settings
    try {
        const stored = localStorage.getItem('reader-settings');
        if (stored) {
            const settings = JSON.parse(stored);
            if (settings.theme) applyTheme(settings.theme);
            if (settings.fontSize) applyFontSize(settings.fontSize);
        }
    } catch (e) {}

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
    function applyTheme(theme) {
        document.body.classList.remove('theme-light', 'theme-dark', 'theme-sepia');
        if (theme !== 'light') {
            document.body.classList.add(`theme-${theme}`);
        }
        if (themeSelect) themeSelect.value = theme;
        saveSettings();
    }

    if (themeSelect) {
        themeSelect.addEventListener('change', (e) => {
            applyTheme(e.target.value);
        });
    }

    function saveSettings() {
        localStorage.setItem('reader-settings', JSON.stringify({
            theme: themeSelect ? themeSelect.value : 'light',
            fontSize: currentFontSize
        }));
    }

    // Modal Handlers
    if (btnOpen && dialog) {
        btnOpen.addEventListener('click', () => dialog.showModal());
    }
    if (btnClose && dialog) {
        btnClose.addEventListener('click', () => dialog.close());
    }
})();
</script>
