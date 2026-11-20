<?php
/*
 * =============================================================================
 * READ-ALONG TEACHING GUIDE: sophia/about.php
 * =============================================================================
 * WHAT PAGE THIS FILE RENDERS:
 *     This template renders `https://books.raggiesoft.com/about` — the
 *     standalone "About the Developer & Legal Policies" screen inside the
 *     RaggieSoft Book Reader web app.
 *
 * HOW IT CONNECTS TO THE MASTER ROUTER (`eleanor/isabel.php`):
 *     1. When a visitor navigates to `/about`, the web server hands the request
 *        to `eleanor/isabel.php` (the "Front Controller" or Master Router).
 *     2. `isabel.php` sets `$isAbout = true`, loads `data/settings.json` to
 *        define `$cdnBaseUrl` (typically `https://assets.raggiesoft.com`),
 *        outputs `<head>` and the opening `<div id="stardust-app">` shell via
 *        `includes/components/headers/header.php`, and then `require_once`s
 *        this file (`sophia/about.php`).
 *     3. At the bottom of this file, `includes/components/bottom-nav.php`
 *        attaches the mobile tab bar before `isabel.php` closes `</body></html>`.
 *
 * WHAT THE READER SEES & INTERACTS WITH:
 *     - Card 1 ("About the Developer"): Introduces the four fictional siblings
 *       from the Ocean View Archives lore—Isabel (Lead Developer & Router),
 *       Eleanor (Public Entry & Editorial), Sophia (UI/UX & Vault), and Oliver
 *       (Reading Pane & Infrastructure)—whose names also correspond to the
 *       actual PHP folders and files powering this app!
 *     - Card 2 ("Legal & Policies"): Provides one-tap navigation to the
 *       in-app Accessibility Commitment (`/accessibility`), external RaggieSoft
 *       policy pages (Terms of Service, Privacy Policy, AI Disclaimer), the
 *       open-source MIT / CC BY-SA 4.0 licenses, and the Work of Fiction
 *       disclaimer.
 *
 * 100-LEVEL PHP & CSS CONCEPTS TO NOTICE HERE:
 *     - Notice how `<?php` appears on Line 1 with ZERO blank lines or HTML
 *       comments above it. In PHP, any character before `<?php` starts sending
 *       HTTP body output immediately, so keeping Line 1 inside `<?php ... ?>`
 *       is a universal best practice across PHP templates.
 *     - `global $cdnBaseUrl;` imports a variable created in the parent script
 *       (`isabel.php`) so we can echo CDN image URLs inside `<img>` tags.
 *     - CSS Custom Properties like `var(--rs-bg)` and `var(--rs-surface)` let
 *       every card automatically switch colors between Light, Dark, and Sepia
 *       reading themes without writing separate HTML for each color mode.
 * =============================================================================
 */

// Import `$cdnBaseUrl` from the Master Router (`eleanor/isabel.php`) so all
// avatar images load from the RaggieSoft asset CDN (`assets.raggiesoft.com`).
global $cdnBaseUrl;
?>
<!--
  OUTER SCROLLABLE SCREEN WRAPPER:
  - `.stardust-mobile-scroll` allows smooth vertical scrolling inside the fixed app shell.
  - `padding: calc(1.25rem + env(safe-area-inset-top, 0px)) 1rem 6rem 1rem` adds extra
    breathing room below iPhone/iPad notches (`safe-area-inset-top`) and 6rem at the
    bottom so the last card never hides behind the fixed bottom tab bar.
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding: calc(1.25rem + env(safe-area-inset-top, 0px)) 1rem 6rem 1rem;">
            <!-- CARD 1: DEVELOPER CREDITS (OVA SIBLINGS LORE) -->
            <!-- This section honors the lore that Isabel built this reader to preserve the family's narratives -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <!-- Phosphor Icon (`ph ph-code`) rendered via the icon font loaded in `header.php` -->
                <i class="ph ph-code"></i> About the Developer
            </h2>
            
            <!-- Vertical stack (`flex-direction: column; gap: 1.5rem`) of the four sibling profiles -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Isabel: Lead Dev (maps to `eleanor/isabel.php`, the Master Router) -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/isabel.jpg" alt="Isabel" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.2rem; font-weight: 700;">Isabel</h3>
                        <p style="margin: 0; font-size: 0.95rem; opacity: 0.8; line-height: 1.5;">Lead Developer & Architect. The book reader was her vision, coded to ensure the family's stories survive digitally across the universe.</p>
                    </div>
                </div>
                
                <!-- Subtle horizontal divider separating Lead Architect from Acknowledgments -->
                <hr style="border: 0; border-top: 1px solid var(--rs-border); margin: 0;">
                <h4 style="margin: 0; font-size: 1rem; color: var(--rs-primary);">Acknowledgments & Support</h4>
                
                <!-- Eleanor: Content (maps to the `eleanor/` public entry directory) -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/eleanor.jpg" alt="Eleanor" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Eleanor</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">Content Strategy & Editorial Lead. Ensuring every narrative fragment is properly archived.</p>
                    </div>
                </div>
                
                <!-- Sophia: Design (maps to the `sophia/` view templates folder you are reading right now!) -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/sophia.jpg" alt="Sophia" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Sophia</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">UI/UX Design. Keeping the interface clean, intuitive, and accessible.</p>
                    </div>
                </div>
                
                <!-- Oliver: Infrastructure (maps to `sophia/oliver.php`, the core Markdown Reading Pane) -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/oliver.jpg" alt="Oliver" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Oliver</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">Systems & Infrastructure. Making sure the servers run and everything stays online.</p>
                    </div>
                </div>
            </div>
        </div>

                <!-- CARD 2: LEGAL, ACCESSIBILITY & POLICIES SECTION -->
        <!-- Consolidates previously scattered policy pages into one mobile-friendly menu list -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-shield-check"></i> Legal & Policies
            </h3>
            
            <!-- Action rows: internal SPA route (`/accessibility`) uses a right caret; external links open safely in a new tab -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <!-- Internal route: intercepted by `stardust-spa.min.js` for instant client-side navigation to `sophia/accessibility.php` -->
                <a href="/accessibility" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: var(--rs-text); padding: 0.75rem; border-radius: 8px; background: var(--rs-bg); border: 1px solid var(--rs-border);">
                    <span style="display: flex; align-items: center; gap: 0.75rem;"><i class="ph ph-wheelchair" style="font-size: 1.25rem; color: var(--rs-primary);"></i> Accessibility Commitment</span>
                    <i class="ph ph-caret-right" style="color: var(--rs-text-muted);"></i>
                </a>
                
                <!-- External links include `rel="noopener noreferrer"` so the newly opened tab cannot access `window.opener` for security -->
                <a href="https://raggiesoft.com/about/terms" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: var(--rs-text); padding: 0.75rem; border-radius: 8px; background: var(--rs-bg); border: 1px solid var(--rs-border);">
                    <span style="display: flex; align-items: center; gap: 0.75rem;"><i class="ph ph-scroll" style="font-size: 1.25rem; color: var(--rs-primary);"></i> Terms of Service</span>
                    <i class="ph ph-arrow-up-right" style="color: var(--rs-text-muted);"></i>
                </a>
                
                <a href="https://raggiesoft.com/about/privacy" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: var(--rs-text); padding: 0.75rem; border-radius: 8px; background: var(--rs-bg); border: 1px solid var(--rs-border);">
                    <span style="display: flex; align-items: center; gap: 0.75rem;"><i class="ph ph-lock-key" style="font-size: 1.25rem; color: var(--rs-primary);"></i> Privacy Policy</span>
                    <i class="ph ph-arrow-up-right" style="color: var(--rs-text-muted);"></i>
                </a>
                
                <a href="https://raggiesoft.com/about/ai-disclaimer" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: var(--rs-text); padding: 0.75rem; border-radius: 8px; background: var(--rs-bg); border: 1px solid var(--rs-border);">
                    <span style="display: flex; align-items: center; gap: 0.75rem;"><i class="ph ph-robot" style="font-size: 1.25rem; color: var(--rs-primary);"></i> AI Disclaimer</span>
                    <i class="ph ph-arrow-up-right" style="color: var(--rs-text-muted);"></i>
                </a>
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--rs-border); margin: 1.5rem 0;">
            
            <!-- VERSIONING, OPEN LICENSES (MIT + CC BY-SA 4.0), & FICTITIOUS ENTITIES DISCLAIMER -->
            <div style="font-size: 0.85rem; color: var(--rs-text-muted); line-height: 1.6; text-align: center;">
                <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: var(--rs-text);">Stardust Engine v0.1.0</p>
                <!-- `date("Y")` runs on the PHP server to output the current 4-digit year (e.g., 2026) automatically -->
                <p style="margin: 0 0 0.5rem 0;">Copyright &copy; <?php echo date("Y"); ?> RaggieSoft. All rights reserved.</p>
                <p style="margin: 0;"><strong>Code License:</strong> MIT</p>
                <p style="margin: 0 0 0.75rem 0;"><strong>Narrative Content License:</strong> CC BY-SA 4.0</p>
                <p style="margin: 0; font-size: 0.8rem; max-width: 680px; margin-left: auto; margin-right: auto;">
                    <strong>Work of Fiction Disclaimer:</strong> All novels, stories, characters (including the developer personas above), bands, universities (including CPI), corporations (including OmniCorp and Apex Records), and events in the Ocean View Archives are works of fiction. Any resemblance to actual persons, living or dead, businesses, institutions, or events is purely coincidental.
                </p>
            </div>
        </div>

</div>
<?php
// Attach the persistent bottom tab navigation bar (`Home`, `Library`, `Bookmarks`, `Settings`)
// shared across all top-level platform screens.
include __DIR__ . '/../includes/components/bottom-nav.php';
?>
