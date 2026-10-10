<?php
/**
 * ACCESSIBILITY VIEW (accessibility.php)
 * ---------------------------------------------------------
 * Architectural Block Comment:
 * File: accessibility.php
 * Purpose:
 *     This file replaces the old standalone accessibility page with a 
 *     native app-like screen that integrates with the Stardust Engine's 
 *     bottom navigation and UI shell. It declares RaggieSoft's accessibility policies 
 *     and details technical accommodations built into the reading platform.
 * 
 * Design Decisions & Future Maintenance:
 *     - Integration: Uses `include` to bolt on the global `bottom-nav.php`, ensuring it acts as a primary application tab.
 *     - Layout: Hardcoded inline CSS utilizes CSS variables (`var(--rs-bg)`, `var(--rs-text)`) to maintain compatibility with 
 *       the site's dynamic light/dark/custom theming engine.
 *     - Content Structure: Organized logically into sections (Motion, Color/Contrast, Keyboard, Screen Readers) rather than 
 *       a wall of text.
 *     - Mobile Responsiveness: Extensive use of Flexbox and `safe-area-inset` calculations to ensure it renders correctly on 
 *       modern mobile devices with notches and bottom swiping bars.
 */
global $cdnBaseUrl, $siteName, $requestUri;
?>

<!-- 
  MAIN CONTAINER
  We use flex: 1 and a bottom padding to ensure the content doesn't get 
  hidden behind the fixed bottom navigation bar on mobile devices.
-->
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding-bottom: 6rem;">
    <!-- Container wrapper with max-width for desktop, and safe-area adjustments for mobile -->
    <div style="max-width: 800px; margin: 0 auto; padding: calc(1.5rem + env(safe-area-inset-top, 0px)) 1.5rem 1rem;">
        
        <!-- HEADER SECTION -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <!-- Navigation Breadcrumb allowing users to return to the parent Settings view -->
                <a href="/settings" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--rs-text); text-decoration: none; font-weight: 600; opacity: 0.7; margin-bottom: 1rem;"><i class="ph ph-arrow-left"></i> Back to Settings</a>
                
                <p style="font-size: 1.1rem; color: var(--rs-primary); margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                    Our Commitment
                </p>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--rs-heading); letter-spacing: -0.5px; margin: 0; line-height: 1.1;">
                    Accessibility
                </h1>
            </div>
        </div>

        <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9; color: var(--rs-text);">
            At RaggieSoft, we believe digital experiences should be accessible, comfortable, and safe for everyone.
        </p>

        <!-- NATIVE ACCESSIBILITY & MOTION SECTION -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-person-simple-walk"></i> Native Accessibility & Motion
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                RaggieSoft respects the accessibility preferences you have already configured on your own device. 
                We do not use intrusive custom toggle switches that force you to re-configure your needs on our site.
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                If you have <strong>"Reduce Motion"</strong> enabled in your operating system settings (Windows, macOS, iOS, or Android), 
                our platform—including the Stardust Engine—will automatically detect this. 
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                All immersive background animations, rapid weather effects (like rain or lightning), and heavy UI transitions 
                will be instantly disabled or reduced to static textures, ensuring a safe and comfortable reading experience for 
                users with vestibular disorders, photosensitive epilepsy, or sensory sensitivities.
            </p>
        </div>

        <!-- COLOR & CONTRAST SECTION -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-circle-half"></i> Color & Contrast
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                This page natively respects your device's <strong>Color Scheme</strong> settings (Light or Dark Mode). 
                If you have configured a specific reading theme inside the Stardust Engine Reader, this app will 
                automatically inherit that theme (Light, Dark, Sepia, or Dark Sepia) to prevent sudden, jarring changes in brightness.
            </p>
        </div>
        
        <!-- KEYBOARD NAVIGATION SECTION -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-keyboard"></i> Keyboard Navigation
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                Our reading interfaces and hubs are designed to be fully navigable via keyboard, ensuring that users who rely 
                on assistive technologies or cannot use a mouse can fully interact with the narrative.
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                A <strong>"Skip to main content"</strong> link is provided at the very top of every page. When focused via the Tab key, it becomes visible, allowing keyboard users to bypass repetitive navigation and jump directly to the reading pane.
            </p>
        </div>
        
        <!-- SCREEN READERS & AAC DIALOGUE SECTION -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-speaker-high"></i> Screen Readers & AAC Dialogue
            </h3>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 1rem;">
                Our narratives frequently feature characters who communicate using Augmentative and Alternative Communication (AAC) devices (such as text-to-speech laptops or tablets).
            </p>
            <p style="color: var(--rs-text); line-height: 1.6; opacity: 0.9; margin-bottom: 0;">
                To ensure a consistent and immersive experience for screen reader users, the Stardust Engine automatically intercepts these lines of dialogue and prefaces them with visually-hidden <strong>"AAC Device:"</strong> text. This guarantees that screen readers clearly differentiate between spoken dialogue and synthesized device output, preserving the structural intent of the story for all readers.
            </p>
        </div>
        
    </div>
</div>

<!-- 
  BOTTOM NAVIGATION COMPONENT
  This includes the iOS-style tab bar at the bottom of the screen.
-->
<?php include __DIR__ . '/includes/components/bottom-nav.php'; ?>
