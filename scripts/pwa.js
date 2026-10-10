/**
 * Architectural Block Comment:
 * File: pwa.js
 * Purpose:
 *     This client-side script initializes and manages the Progressive Web App (PWA) behaviors for the reading platform.
 *     It handles service worker registration, offline resume functionality, and iOS-specific installation prompts.
 * 
 * Design Decisions & Future Maintenance:
 *     - Standalone Detection: Detects if the app is running in a browser tab vs. installed on the device (standalone mode).
 *     - Reading Resume: Automatically stores the current URL in `localStorage`. If the app is launched in standalone mode, 
 *       it redirects the user straight to their last read chapter rather than forcing them to navigate from the homepage.
 *     - iOS Quirks: iOS Safari historically hides PWA installation prompts. This script includes a custom, dismissible 
 *       HTML overlay specifically targeting iOS users to educate them on how to "Add to Home Screen".
 *     - Deferral: Logic is executed inside `DOMContentLoaded` to ensure DOM elements can be safely manipulated, while 
 *       Service Worker registration is deferred to `window.addEventListener('load')` to prioritize page content rendering.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Resume Reading Logic
    // Grab the current URL path to evaluate state.
    const currentPath = window.location.pathname;
    
    // If not on the homepage (and it's a valid route), save the current path as the last read location.
    if (currentPath !== '/' && currentPath.length > 2) {
        // Exclude specific system paths if needed, e.g., /accessibility/, so we don't drop users into settings on next launch.
        if (!currentPath.includes('accessibility')) {
            localStorage.setItem('lastReadChapter', currentPath);
        }
    }

    // Check if running as an installed PWA (standalone) via various browser-specific metrics.
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone || document.referrer.includes('android-app://');
    
    if (isStandalone && currentPath === '/') {
        // If launched from the OS home screen and landing on the root homepage, attempt to redirect to last read chapter.
        const lastRead = localStorage.getItem('lastReadChapter');
        if (lastRead && lastRead !== '/') {
            // Give a tiny delay so the native OS splash screen transitions naturally before the JS redirect fires.
            setTimeout(() => {
                window.location.replace(lastRead);
            }, 100);
        }
    }

    // 2. iOS Add to Home Screen Hint
    // Helper function to detect Apple mobile devices via user-agent string.
    const isIos = () => {
        const userAgent = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(userAgent);
    };

    if (isIos() && !isStandalone) {
        // Check if we already showed the installation hint recently to avoid spamming the user.
        const hintDismissed = localStorage.getItem('iosA2HSDismissed');
        const now = new Date().getTime();
        
        // Show if not dismissed, or if it was dismissed more than 7 days ago.
        if (!hintDismissed || (now - parseInt(hintDismissed)) > 7 * 24 * 60 * 60 * 1000) {
            showIosInstallHint();
        }
    }

    /**
     * Injects a floating banner at the bottom of the screen instructing iOS users 
     * on how to manually install the PWA using Safari's share menu.
     */
    function showIosInstallHint() {
        const hintHtml = `
            <div id="ios-install-hint" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--rs-surface); border: 1px solid var(--rs-border); padding: 15px 20px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 10000; display: flex; flex-direction: column; align-items: center; gap: 10px; width: 90%; max-width: 320px; text-align: center; font-family: 'Inter', sans-serif;">
                <button id="close-ios-hint" style="position: absolute; top: 8px; right: 8px; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--rs-text); opacity: 0.6;"><i class="ph ph-x"></i></button>
                <i class="ph ph-compass" style="font-size: 2rem; color: var(--rs-primary);"></i>
                <div style="font-size: 0.95rem; line-height: 1.4; color: var(--rs-text);">
                    Install <strong>Ocean View Archives</strong> for offline reading.
                </div>
                <div style="font-size: 0.85rem; opacity: 0.8; color: var(--rs-text);">
                    Tap <i class="ph ph-export" style="vertical-align: middle;"></i> and then <strong>Add to Home Screen</strong>.
                </div>
            </div>
        `;
        
        // Append the banner to the DOM.
        document.body.insertAdjacentHTML('beforeend', hintHtml);
        
        // Bind the close button to remove the banner and update the dismissal timestamp in localStorage.
        document.getElementById('close-ios-hint').addEventListener('click', () => {
            document.getElementById('ios-install-hint').remove();
            localStorage.setItem('iosA2HSDismissed', new Date().getTime().toString());
        });
    }

    // 3. Register Service Worker
    // Ensure the browser supports Service Workers before attempting registration.
    if ('serviceWorker' in navigator) {
        // Wait for the window `load` event to ensure the SW registration doesn't block the critical rendering path.
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').then(registration => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, err => {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
});
