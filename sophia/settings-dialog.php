<?php
// Sophia's Reader Settings Dialog
?>
<!-- WIZARD95 DIALOG -->
<dialog id="reader-wizard-dialog" data-cdn-url="<?php echo htmlspecialchars($cdnBaseUrl); ?>" class="wizard-dialog">
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
<dialog id="reader-settings-dialog" style="padding: 0; border-radius: 12px; border: 1px solid var(--rs-border); background: var(--rs-card-bg, var(--rs-surface, #fff)); color: var(--rs-text); max-width: 500px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden;">
    <div style="display: flex; flex-direction: column; height: 100%;">
        <div style="padding: 1.5rem 1.5rem 0 1.5rem; border-bottom: 1px solid var(--rs-border);">
            <h3 id="reader-settings-title" style="margin-top: 0; margin-bottom: 1rem; font-weight: bold; user-select: none;">Reader Settings</h3>
            <div style="display: flex; gap: 1rem; margin-bottom: -1px; overflow-x: auto;" id="reader-settings-tabs">
                <button type="button" class="settings-tab active" data-tab="layout">Layout</button>
                <button type="button" class="settings-tab" data-tab="theme">Theme</button>
                <button type="button" class="settings-tab" data-tab="library">Library</button>
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
                    <div style="margin-top: 1rem; padding: 1rem; background: var(--rs-surface, #f5f5f5); border-radius: 8px; border: 1px solid var(--rs-border);">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="checkbox" id="reader-custom-theme-toggle" checked style="width: 1.1rem; height: 1.1rem; accent-color: var(--rs-primary);">
                            <strong style="font-size: 0.95rem;">Allow Custom Story Themes</strong>
                        </div>
                        <p style="font-size: 0.85rem; opacity: 0.8; margin: 0;">Some scenes have unique, immersive aesthetics (like a rainy 4 AM night). Check this box to allow these themes to override your color mode when available.</p>
                    </div>
                    


                </div>
            </div>

            <!-- LIBRARY TAB -->
            <div id="settings-tab-library" class="settings-tab-content" style="display: none;">
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Offline Reading & Visibility</h4>
                    <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 0;">Selected archives are downloaded for offline reading and remain visible in your catalog.</p>
                    <div id="library-management-list" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <!-- Populated dynamically via JS -->
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
                    <a href="/accessibility.php" class="rs-btn" style="width: 100%; text-decoration: none; text-align: center; background: transparent; border: 1px solid var(--rs-border);"><i class="ph ph-wheelchair"></i> Accessibility Statement</a>
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

<!-- DEVELOPER THEME TESTER DIALOG -->
<dialog id="dev-theme-tester-dialog" data-cdn-url="<?php echo htmlspecialchars($cdnBaseUrl); ?>" style="padding: 0; border-radius: 12px; border: 1px solid var(--rs-border); background: var(--rs-card-bg, var(--rs-surface, #fff)); color: var(--rs-text); max-width: 400px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden;">
    <div style="display: flex; flex-direction: column; height: 100%;">
        <div style="padding: 1.5rem 1.5rem 0 1.5rem; border-bottom: 1px solid var(--rs-border);">
            <h3 style="margin-top: 0; margin-bottom: 1rem; font-weight: bold; color: var(--rs-primary);">🛠 Developer Sandbox</h3>
        </div>
        <div style="padding: 1.5rem; flex: 1; overflow-y: auto;">
            <p style="font-size: 0.9rem; margin-top: 0;">This is a hidden sandbox for WCAG contrast testing and CSS development.</p>
                    <div style="margin-top: 1rem; padding: 1rem; background: var(--rs-surface, #f5f5f5); border-radius: 8px; border: 1px solid var(--rs-border);">
                        <h4 style="font-size: 0.95rem; margin-top: 0; margin-bottom: 0.5rem;">Developer Theme Preview</h4>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <select id="dev-theme-select" class="rs-input" style="flex: 1; padding: 0.5rem; font-size: 0.9rem;">
                                <option value="">-- Select Theme --</option>
                                <?php
                                $themesJsonFile = __DIR__ . '/../data/themes.json';
                                if (file_exists($themesJsonFile)) {
                                    $themesList = json_decode(file_get_contents($themesJsonFile), true);
                                    if (is_array($themesList)) {
                                        foreach ($themesList as $themeId => $themeName) {
                                            // Handle multiple formats of themes.json
                                            $supportsModes = true; // default
                                            if (is_array($themeName)) {
                                                $supportsModes = isset($themeName['supports_modes']) ? $themeName['supports_modes'] : true;
                                                $themeName = $themeName['name'];
                                            } else if (is_numeric($themeId)) {
                                                $themeId = $themeName; 
                                            }
                                            $dataAttr = $supportsModes ? 'true' : 'false';
                                            echo '<option value="' . htmlspecialchars($themeId) . '" data-supports-modes="' . $dataAttr . '">' . htmlspecialchars($themeName) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <button type="button" id="dev-theme-apply" class="rs-btn">Preview</button>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <strong style="font-size: 0.85rem; width: 80px;">Color Mode:</strong>
                            <select id="dev-theme-force-mode" class="rs-input" style="flex: 1; padding: 0.4rem; font-size: 0.85rem;">
                                <option value="auto">Auto (OS Default)</option>
                                <option value="light">Force Light</option>
                                <option value="dark">Force Dark</option>
                            </select>
                        </div>
                    </div>
        </div>
        <div style="padding: 1.5rem; border-top: 1px solid var(--rs-border); text-align: center;">
            <button type="button" id="close-dev-tester-btn" class="rs-btn rs-btn-primary" style="width: 100%;">Close & Revert Theme</button>
        </div>
    </div>
</dialog>





