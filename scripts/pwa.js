/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: scripts/pwa.js
 * URL:  https://books.raggiesoft.com/scripts/pwa.js
 *
 * WHAT IS THIS FILE?
 *   Loaded once inside `<head>` (`includes/components/headers/header.php`),
 *   this client-side Vanilla JavaScript file bridges the gap between running
 *   RaggieSoft Book Reader inside a normal browser tab vs. running it as an
 *   installed Progressive Web App (PWA) icon on iOS, Android, macOS, or Windows.
 *
 * WHY DOES IT EXIST & WHAT VISIBLE BEHAVIORS DOES IT POWER?
 *   1. **Resume Last-Read Chapter**: Saves the current chapter URL in the
 *      browser's `localStorage` (`lastReadChapter`). If the reader configured
 *      "Resume where I left off" (`rs-launch-behavior === 'resume'`) and taps
 *      their Home Screen app icon (which opens `/`), it jumps straight back to
 *      their chapter!
 *   2. **Standalone PWA Chrome Cleanup**: Detects when the app is running in an
 *      installed OS window (`display-mode: standalone`) and hides the cross-site
 *      RaggieSoft Network top bar so the app feels 100% native.
 *   3. **Cross-PWA Link Handoffs (`window.launchQueue` & Outbound Capture)**:
 *      - *Inbound*: If RaggieSoft Backstage or The Listening Room sends a link
 *        to an already-open Book Reader window, routes it through the Stardust
 *        SPA router so background chapter music never cuts out!
 *      - *Outbound*: Marks links to `backstage.raggiesoft.com` and `music.raggiesoft.com`
 *        with `target="_blank" rel="noopener"` in standalone mode so the OS opens
 *        the sibling app window cleanly.
 *   4. **iOS Safari "Add to Home Screen" Guide**: Because Apple's iOS Safari
 *      does not show an automatic "Install App" prompt, displays a polite,
 *      dismissible hint banner (at most once every 7 days) showing iPhone/iPad
 *      visitors how to tap Share -> "Add to Home Screen".
 *   5. **Cache Cleanup & Service Worker Registration**: Purges outdated pre-`v21`
 *      Cache Storage bins and registers `/sw.js?v=21` after the page finishes loading.
 * =============================================================================
 */

// Wait for `DOMContentLoaded` (which fires as soon as the HTML tags are parsed into `document`,
// without waiting for slow images or fonts to finish downloading).
document.addEventListener('DOMContentLoaded', () => {
    // =========================================================================
    // 1. SAVE LAST-READ LOCATION IN `localStorage`
    // =========================================================================
    // `localStorage` is a persistent key-value dictionary built into every browser
    // that survives closing the tab or restarting the phone/computer.
    const currentPath = window.location.pathname;
    
    // Save any real reading path (longer than `/`), skipping utility screens like `/accessibility`
    // so re-opening the app never drops the reader onto a policy screen instead of their novel.
    if (currentPath !== '/' && currentPath.length > 2) {
        if (!currentPath.includes('accessibility')) {
            localStorage.setItem('lastReadChapter', currentPath);
        }
    }

    // =========================================================================
    // 2. DETECT INSTALLED ("STANDALONE") PWA DISPLAY MODE
    // =========================================================================
    // Check three cross-platform signals to tell if we are running as an installed app:
    //   - Standard CSS Media Query (`display-mode: standalone` / `window-controls-overlay`, etc.)
    //   - Legacy Apple iOS Safari property (`window.navigator.standalone === true`)
    //   - Android Trusted Web Activity referrer (`android-app://`)
    const isStandalone =
        window.matchMedia('(display-mode: standalone), (display-mode: window-controls-overlay), (display-mode: minimal-ui), (display-mode: fullscreen)').matches ||
        window.navigator.standalone === true ||
        document.referrer.includes('android-app://');

    if (isStandalone) {
        // Add `.is-pwa-standalone` CSS hook and hide the web-only RaggieSoft Network bar
        document.documentElement.classList.add('is-pwa-standalone');
        document.body.classList.add('is-pwa-standalone');
        const netBar = document.querySelector('.rs-network-bar');
        if (netBar) {
            netBar.hidden = true;
            netBar.setAttribute('aria-hidden', 'true');
            netBar.style.setProperty('display', 'none', 'important');
        }
    }
    
    // Tracks whether the Launch Handler API (`window.launchQueue`) already navigated us to a deep link
    // on startup—preventing the homepage "Resume Last Chapter" timer below from overriding it!
    let hasHandledLaunchQueueTarget = false;

    // =========================================================================
    // 3. INBOUND CROSS-PWA HANDOFF (`window.launchQueue` Launch Handler API)
    // =========================================================================
    // When `manifest.json` specifies `"launch_handler": { "client_mode": "focus-existing" }`,
    // clicking a `books.raggiesoft.com` link inside another app focuses the existing Book Reader
    // PWA window and delivers the clicked URL to `window.launchQueue.setConsumer(...)` instead
    // of reloading the window!
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

                // Clever SPA trick: create an invisible `<a href="...">`, append it to `document.body`,
                // call `.click()` so `stardust-spa.js`'s body click listener intercepts it for a soft
                // DOM swap (keeping `<audio id="stardust-bgm">` playing uninterrupted!), then remove it!
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

    // =========================================================================
    // 4. OUTBOUND SIBLING PWA HANDOFF ENHANCER (Capture-Phase Click Listener)
    // =========================================================================
    // When installed as a standalone PWA, clicking a regular link to a different subdomain
    // (`backstage.raggiesoft.com` or `music.raggiesoft.com`) would otherwise open a trapped
    // in-app browser sheet inside Book Reader. Adding `target="_blank" rel="noopener"` during
    // the **capture phase** (`true` as the 3rd argument to `addEventListener`, running top-down
    // from `document` BEFORE target listeners) lets the OS launch the installed sibling PWA window!
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
                        relTokens.add('noopener'); // Security best practice: prevents new window from accessing `window.opener`
                        anchor.setAttribute('rel', Array.from(relTokens).join(' '));
                    }
                } catch (_) {
                    // Ignore malformed hrefs
                }
            },
            true
        );
    }

    // =========================================================================
    // 5. AUTOMATIC CHAPTER RESUME ON HOME SCREEN LAUNCH
    // =========================================================================
    if (isStandalone && currentPath === '/') {
        // Check if the reader opted into `'resume'` in Settings (`sophia/settings.php`)
        const launchBehavior = localStorage.getItem('rs-launch-behavior') || 'home';
        const lastRead = localStorage.getItem('lastReadChapter');
        
        if (!hasHandledLaunchQueueTarget && launchBehavior === 'resume' && lastRead && lastRead !== '/') {
            // Wait 100ms so the OS startup splash screen finishes fading smoothly,
            // then use `location.replace()` (replaces `/` in history so Back doesn't loop!).
            setTimeout(() => {
                if (!hasHandledLaunchQueueTarget) {
                    window.location.replace(lastRead);
                }
            }, 100);
        }
    }

    // =========================================================================
    // 6. iOS SAFARI "ADD TO HOME SCREEN" EDUCATIONAL BANNER
    // =========================================================================
    // Helper arrow function testing if the visitor's User-Agent string is an iPhone, iPad, or iPod.
    const isIos = () => {
        const userAgent = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(userAgent);
    };

    if (isIos() && !isStandalone) {
        // Read the Unix millisecond timestamp when the user last dismissed the banner
        const hintDismissed = localStorage.getItem('iosA2HSDismissed');
        const now = new Date().getTime();
        
        // Only show if never dismissed OR if > 7 days (`7 * 24 * 60 * 60 * 1000` ms) have passed.
        if (!hintDismissed || (now - parseInt(hintDismissed)) > 7 * 24 * 60 * 60 * 1000) {
            showIosInstallHint();
        }
    }

    /**
     * Injects a theme-aware floating card at the bottom of mobile Safari explaining
     * how to tap the Share icon and choose "Add to Home Screen".
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
        
        // `insertAdjacentHTML('beforeend', ...)` appends HTML right before `</body>`
        // without destroying existing DOM event listeners (unlike `document.body.innerHTML += ...`!).
        document.body.insertAdjacentHTML('beforeend', hintHtml);
        
        // Remove the banner on click and record the current timestamp so we wait 7 days before asking again
        document.getElementById('close-ios-hint').addEventListener('click', () => {
            document.getElementById('ios-install-hint').remove();
            localStorage.setItem('iosA2HSDismissed', new Date().getTime().toString());
        });
    }

    // =========================================================================
    // 7. PROACTIVE CACHE STORAGE CLEANUP & SERVICE WORKER REGISTRATION
    // =========================================================================
    // Immediately delete any old pre-`v21` `ova-*` cache drawers from the page thread
    // (for example, `ova-dynamic-v19`, which previously cached `.mp3` files and interfered
    // with Safari WebKit CoreMedia's HTTP 206 byte-range audio streaming).
    if ('caches' in window) {
        const activeBins = new Set(['ova-cache-v21', 'ova-dynamic-v21', 'ova-images-v21']);
        caches.keys().then(names => {
            names.forEach(name => {
                if (name.startsWith('ova-') && !activeBins.has(name)) {
                    caches.delete(name);
                }
            });
        }).catch(() => {});
    }

    // Register `/sw.js?v=21` AFTER `window` `'load'` (once fonts, images, and CSS finish)
    // so Service Worker installation never competes for bandwidth with initial page rendering!
    // `updateViaCache: 'none'` tells the browser to check the live server for `sw.js` updates
    // instead of trusting stale HTTP disk cache.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js?v=21', { scope: '/', updateViaCache: 'none' }).then(registration => {
                registration.update();
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, err => {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
});
