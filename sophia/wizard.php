<?php
// Sophia's Welcome Wizard (Standalone View)
global $cdnBaseUrl;
?>
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding: 2rem 1rem 6rem 1rem; display: flex; justify-content: center; align-items: flex-start;">
    <div id="reader-wizard-dialog" data-cdn-url="<?php echo htmlspecialchars($cdnBaseUrl); ?>" class="wizard-dialog">
    <div class="wizard-container">
        <!-- Left Sidebar Graphic (Image handled by CSS/JS) -->
        <div id="wizard-sidebar-graphic" class="wizard-sidebar"></div>
        
        <!-- Right Content Area -->
        <div class="wizard-content">
            
            <!-- Step 1: Introduction and Width Preferences -->
            <div id="wizard-step-1" class="wizard-step active-step">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Welcome to Ocean View Archives!</h2>
                <p>Hi there! I'm Isabel, the brainchild behind this app, and I'll be your guide. My siblings and I live right here in the Ocean View section of Norfolk, Virginia.</p>
                <p>Before you start reading, Oliver and I wanted to help you set up your reading environment. The Archives are designed for long, distraction-free reading sessions, so let's make sure the layout is comfortable for you.</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Choose your Article Width:</h4>
                    <!-- Note: Value maps to corresponding CSS classes handled by reader.js -->
                    <select id="wizard-width-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <option value="default">Default (800px) - Recommended</option>
                        <option value="wide">Wide (1200px)</option>
                        <option value="full">Full Screen (100%)</option>
                    </select>
                </div>
            </div>

            <!-- Step 2: Theme Selection -->
            <div id="wizard-step-2" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Themes & Text Size</h2>
                <p>Hey, I'm Eleanor! I handle the public entry points.</p>
                <p>If you're reading late at night, staring at a bright white screen can cause serious eye strain. We have a few themes available to help with that.</p>
                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 0.5rem;">Select a Theme:</h4>
                    <select id="wizard-theme-select" class="rs-input" style="width: 100%; padding: 0.5rem;">
                        <optgroup label="System Default (Matches your OS)">
                            <option value="auto">Auto (Light / Dark)</option>
                            <option value="sepia-system">Auto (Sepia / Dark Sepia)</option>
                        </optgroup>
                        <optgroup label="Fixed Themes">
                            <option value="light">Light Mode (Crisp & Clean)</option>
                            <option value="dark">Dark Mode (Best for night)</option>
                            <option value="sepia">Sepia Mode (Easy on the eyes)</option>
                            <option value="dark-sepia">Dark Sepia (Warm & Dark)</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Step 3: Typography and Accessibility Fonts -->
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

            <!-- Step 4: Audio Autoplay Settings -->
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

            <!-- Step 5: Special Immersive Themes Permission -->
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

            <!-- Step 6: PWA and Offline Cache Explanation -->
            <div id="wizard-step-6" class="wizard-step" style="display: none;">
                <h2 style="margin-top: 0; color: var(--rs-primary);">Offline Reading</h2>
                <p>Hi! I'm Oliver, the one responsible for the reading pane itself.</p>
                <p>Did you know that you can download entire narratives to your device? This means you can read without an internet connection—perfect for airplanes, road trips, or just saving data.</p>
                <p>Just look for the Offline switches in the Library settings to download the narratives you want to take with you.</p>
                <div style="margin-top: 2rem; padding: 1.5rem; background: var(--rs-bg); border-radius: 8px; border: 1px solid var(--rs-border); text-align: center;">
                    <i class="ph ph-cloud-arrow-down" style="font-size: 2.5rem; color: var(--rs-primary); margin-bottom: 0.5rem; display: inline-block;"></i>
                    <p style="font-size: 0.95rem; font-weight: 600; margin: 0;">You can manage your offline books anytime from the Settings menu!</p>
                </div>
            </div>

            <!-- Wizard Navigation Buttons -->
            <div class="wizard-footer">
                <button id="wizard-btn-prev" class="rs-btn" style="visibility: hidden;">&larr; Back</button>
                <button id="wizard-btn-next" class="rs-btn rs-btn-primary">Next &rarr;</button>
                <button id="wizard-btn-finish" class="rs-btn rs-btn-primary" style="display: none;">Start Reading &rarr;</button>
            </div>
            
        </div>
    </div>
</div>
</div>
