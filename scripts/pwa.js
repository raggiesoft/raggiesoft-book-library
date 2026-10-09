document.addEventListener('DOMContentLoaded', () => {
    // 1. Resume Reading Logic
    const currentPath = window.location.pathname;
    
    // If not on the homepage, save the current path as last read
    if (currentPath !== '/' && currentPath.length > 2) {
        // Exclude specific system paths if needed, e.g., /accessibility/
        if (!currentPath.includes('accessibility')) {
            localStorage.setItem('lastReadChapter', currentPath);
        }
    }

    // Check if running as a PWA (standalone)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone || document.referrer.includes('android-app://');
    
    if (isStandalone && currentPath === '/') {
        // If launched from home screen and on the homepage, redirect to last read
        const lastRead = localStorage.getItem('lastReadChapter');
        if (lastRead && lastRead !== '/') {
            // Give a tiny delay so the splash screen transitions naturally before redirect
            setTimeout(() => {
                window.location.replace(lastRead);
            }, 100);
        }
    }

    // 2. iOS Add to Home Screen Hint
    const isIos = () => {
        const userAgent = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(userAgent);
    };

    if (isIos() && !isStandalone) {
        // Check if we already showed it recently
        const hintDismissed = localStorage.getItem('iosA2HSDismissed');
        const now = new Date().getTime();
        
        // Show if not dismissed, or dismissed > 7 days ago
        if (!hintDismissed || (now - parseInt(hintDismissed)) > 7 * 24 * 60 * 60 * 1000) {
            showIosInstallHint();
        }
    }

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
        
        document.body.insertAdjacentHTML('beforeend', hintHtml);
        
        document.getElementById('close-ios-hint').addEventListener('click', () => {
            document.getElementById('ios-install-hint').remove();
            localStorage.setItem('iosA2HSDismissed', new Date().getTime().toString());
        });
    }

    // 3. Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').then(registration => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, err => {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
});
