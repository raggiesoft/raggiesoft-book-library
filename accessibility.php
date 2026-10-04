<?php
// accessibility.php
// A11y statement specifically for the Stardust Engine Reader environment
require_once __DIR__ . '/includes/components/headers/header.php';
?>

<style>
/* Respect Stardust Reader Theme or Fallback to System Preference */
:root {
    --page-bg: #ffffff;
    --page-text: #212529;
    --page-surface: #f8f9fa;
    --page-border: #dee2e6;
}

body.theme-light {
    --page-bg: #ffffff;
    --page-text: #212529;
    --page-surface: #f8f9fa;
    --page-border: #dee2e6;
}

body.theme-dark {
    --page-bg: #212529;
    --page-text: #f8f9fa;
    --page-surface: #343a40;
    --page-border: #495057;
}

body.theme-sepia {
    --page-bg: #fdf6e3;
    --page-text: #5c4a2f;
    --page-surface: #eee8d5;
    --page-border: #dcd4b6;
}

body.theme-dark-sepia {
    --page-bg: #2d261e;
    --page-text: #d4c4a8;
    --page-surface: #3e362d;
    --page-border: #524739;
}

@media (prefers-color-scheme: dark) {
    body:not([class*="theme-"]) {
        --page-bg: #212529;
        --page-text: #f8f9fa;
        --page-surface: #343a40;
        --page-border: #495057;
    }
}

.a11y-container {
    background-color: var(--page-bg);
    color: var(--page-text);
    padding: 3rem 1.5rem;
    height: 100vh;
    overflow-y: auto;
    font-family: 'Inter', sans-serif;
    line-height: 1.6;
}
.a11y-card {
    background-color: var(--page-surface);
    border: 1px solid var(--page-border);
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
}
</style>

<script>
// Apply Stardust Engine Reader settings if they exist
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('stardust-reader-theme') || 'auto';
    if (savedTheme !== 'auto') {
        document.body.classList.add(`theme-${savedTheme}`);
    }
});
</script>

<div class="a11y-container">
    <div style="max-width: 800px; margin: 0 auto; padding-bottom: 4rem;">
        <div style="margin-bottom: 2rem;">
            <a href="javascript:history.back()" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--page-text); text-decoration: none; font-weight: 600; opacity: 0.7;"><i class="ph ph-arrow-left"></i> Back to Reader</a>
        </div>
        
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">Accessibility Statement</h1>
        <p style="font-size: 1.2rem; margin-bottom: 3rem; opacity: 0.9;">At RaggieSoft, we believe digital experiences should be accessible, comfortable, and safe for everyone.</p>

        <div class="a11y-card">
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;"><i class="ph ph-person-simple-walk" style="margin-right: 0.5rem;"></i> Native Accessibility & Motion</h3>
            <p>
                RaggieSoft respects the accessibility preferences you have already configured on your own device. 
                We do not use intrusive custom toggle switches that force you to re-configure your needs on our site.
            </p>
            <p>
                If you have <strong>"Reduce Motion"</strong> enabled in your operating system settings (Windows, macOS, iOS, or Android), 
                our platform—including the Stardust Engine—will automatically detect this. 
            </p>
            <p style="margin-bottom: 0;">
                All immersive background animations, rapid weather effects (like rain or lightning), and heavy UI transitions 
                will be instantly disabled or reduced to static textures, ensuring a safe and comfortable reading experience for 
                users with vestibular disorders, photosensitive epilepsy, or sensory sensitivities.
            </p>
        </div>

        <div class="a11y-card">
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;"><i class="ph ph-circle-half" style="margin-right: 0.5rem;"></i> Color & Contrast</h3>
            <p>
                This page natively respects your device's <strong>Color Scheme</strong> settings (Light or Dark Mode). 
                If you have configured a specific reading theme inside the Stardust Engine Reader, this page will 
                automatically inherit that theme (Light, Dark, Sepia, or Dark Sepia) to prevent sudden, jarring changes in brightness.
            </p>
        </div>
        
        <div class="a11y-card">
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;"><i class="ph ph-keyboard" style="margin-right: 0.5rem;"></i> Keyboard Navigation</h3>
            <p>
                Our reading interfaces and hubs are designed to be fully navigable via keyboard, ensuring that users who rely 
                on assistive technologies or cannot use a mouse can fully interact with the narrative.
            </p>
            <p style="margin-bottom: 0;">
                A <strong>"Skip to main content"</strong> link is provided at the very top of every page. When focused via the Tab key, it becomes visible, allowing keyboard users to bypass repetitive navigation and jump directly to the reading pane.
            </p>
        </div>
        
        <div class="a11y-card">
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;"><i class="ph ph-speaker-high" style="margin-right: 0.5rem;"></i> Screen Readers & AAC Dialogue</h3>
            <p>
                Our narratives frequently feature characters who communicate using Augmentative and Alternative Communication (AAC) devices (such as text-to-speech laptops or tablets).
            </p>
            <p style="margin-bottom: 0;">
                To ensure a consistent and immersive experience for screen reader users, the Stardust Engine automatically intercepts these lines of dialogue and prefaces them with visually-hidden <strong>"AAC Device:"</strong> text. This guarantees that screen readers clearly differentiate between spoken dialogue and synthesized device output, preserving the structural intent of the story for all readers.
            </p>
        </div>
        
        <div style="margin-top: 3rem; text-align: center;">
            <a href="javascript:history.back()" style="display: inline-block; padding: 0.75rem 1.5rem; background: var(--rs-primary, #007bff); color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">Return to Reading</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/components/footers/footer.php'; ?>
