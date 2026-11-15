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

    // Check if running as an installed PWA (standalone, window-controls-overlay, minimal-ui, or fullscreen).
    const isStandalone =
        window.matchMedia('(display-mode: standalone), (display-mode: window-controls-overlay), (display-mode: minimal-ui), (display-mode: fullscreen)').matches ||
        window.navigator.standalone === true ||
        document.referrer.includes('android-app://');

    if (isStandalone) {
        document.documentElement.classList.add('is-pwa-standalone');
        document.body.classList.add('is-pwa-standalone');
        const netBar = document.querySelector('.rs-network-bar');
        if (netBar) {
            netBar.hidden = true;
            netBar.setAttribute('aria-hidden', 'true');
            netBar.style.setProperty('display', 'none', 'important');
        }
    }
    
    let hasHandledLaunchQueueTarget = false;

    // 1b. Cross-PWA Incoming Link Consumer (Launch Handler API: window.launchQueue)
    // When RaggieSoft Backstage or The Listening Room hands off a book/chapter URL
    // to an already-open Book Reader PWA window (`client_mode: "focus-existing"`),
    // route seamlessly through the Stardust SPA router so active chapter audio never stops.
    if ('launchQueue' in window && window.launchQueue && typeof window.launchQueue.setConsumer === 'function') {
        window.launchQueue.setConsumer((launchParams) => {
            if (!launchParams || !launchParams.targetURL) return;
            try {
                const incoming = new URL(launchParams.targetURL, window.location.origin);
                if (incoming.origin !== window.location.origin) return;
                hasHandledLaunchQueueTarget = true;
                const dest = incoming.pathname + incoming.search + incoming.hash;
                const here = window.location.pathname + window.location.search + window.location.hash;
                if (dest === here) return;

                if (incoming.pathname !== '/' && incoming.pathname.length > 2 && !incoming.pathname.includes('accessibility')) {
                    localStorage.setItem('lastReadChapter', incoming.pathname);
                }

                if (document.body) {
                    const spaAnchor = document.createElement('a');
                    spaAnchor.href = dest;
                    spaAnchor.style.display = 'none';
                    document.body.appendChild(spaAnchor);
                    spaAnchor.click();
                    spaAnchor.remove();
                } else {
                    window.location.assign(dest);
                }
            } catch (e) {
                console.warn('LaunchQueue navigation ignored:', e);
            }
        });
    }

    // 1c. Sibling RaggieSoft PWA Outbound Handoff Enhancer
    // When running as an installed standalone PWA, ensure links to sibling RaggieSoft
    // PWAs (`backstage.raggiesoft.com`, `music.raggiesoft.com`) open via auxiliary
    // browsing context (`target="_blank" rel="noopener"`) so Chromium & Android
    // PWA Link Capturing hands them directly to the installed sibling PWA window
    // instead of trapping them inside Book Reader with an out-of-scope toolbar.
    if (isStandalone) {
        const siblingPwaHosts = new Set(['backstage.raggiesoft.com', 'music.raggiesoft.com']);
        document.addEventListener(
            'click',
            (event) => {
                if (event.defaultPrevented) return;
                const targetEl = event.target instanceof Element ? event.target : null;
                const anchor = targetEl ? targetEl.closest('a[href]') : null;
                if (!anchor) return;
                try {
                    const destUrl = new URL(anchor.href, window.location.href);
                    if (destUrl.hostname !== window.location.hostname && siblingPwaHosts.has(destUrl.hostname)) {
                        anchor.target = '_blank';
                        const relTokens = new Set((anchor.getAttribute('rel') || '').split(/\s+/).filter(Boolean));
                        relTokens.add('noopener');
                        anchor.setAttribute('rel', Array.from(relTokens).join(' '));
                    }
                } catch (_) {
                    // Ignore malformed hrefs
                }
            },
            true
        );
    }

    if (isStandalone && currentPath === '/') {
        // If launched from the OS home screen and landing on the root homepage, check launch preference.
        const launchBehavior = localStorage.getItem('rs-launch-behavior') || 'home';
        const lastRead = localStorage.getItem('lastReadChapter');
        
        if (!hasHandledLaunchQueueTarget && launchBehavior === 'resume' && lastRead && lastRead !== '/') {
            // Give a tiny delay so the native OS splash screen transitions naturally before the JS redirect fires.
            setTimeout(() => {
                if (!hasHandledLaunchQueueTarget) {
                    window.location.replace(lastRead);
                }
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
                <button id="close-ios-hint" aria-label="Dismiss installation notice" style="position: absolute; top: 8px; right: 8px; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--rs-text-muted);"><i class="ph ph-x"></i></button>
                <i class="ph ph-compass" style="font-size: 2rem; color: var(--rs-primary);"></i>
                <div style="font-size: 0.95rem; line-height: 1.4; color: var(--rs-text);">
                    Install <strong>Ocean View Archives</strong> for offline reading.
                </div>
                <div style="font-size: 0.85rem; color: var(--rs-text-muted);">
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
                registration.update();
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, err => {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
});
