<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: header.php
 * ============================================================================
 * Purpose:
 * This is the master HTML `<head>` and document initialization component for 
 * the Stardust Engine Library. It establishes the global layout, loads all 
 * essential CSS/JS assets, defines PWA metadata, and initializes the SPA container.
 * 
 * Architectural Role:
 * Every view in the system must `require` this file to ensure proper DOM 
 * construction and asset availability. It dynamicially switches themes based 
 * on query parameters or route configurations and handles dynamic SEO tags.
 * 
 * Key Components:
 * 1. PWA Manifests & Icons: Extremely thorough Apple touch splash screens for 
 *    native iOS app illusion.
 * 2. Dynamic SEO: Injects OpenGraph (`og:`) and Twitter card metadata.
 * 3. Asset Pipeline: Fetches CSS from the CDN and initializes Google Fonts and 
 *    Phosphor icons.
 * 4. DOM Initialization: Starts `<body>` and creates the `#stardust-app` wrapper 
 *    which the SPA router heavily relies upon.
 * 5. Audio Player: Instantiates a global background audio tag outside the SPA 
 *    wrapper so music persists across page navigations.
 * 
 * Maintenance Notes:
 * - Splash screen resolutions must be kept up-to-date with new iOS device releases 
 *   or the PWA presentation will break on those devices.
 * - The `#stardust-app` ID is critical; `stardust-spa.min.js` expects it to exist.
 * ============================================================================
 */

// Stardust Engine Library: Global Header Component
// Allow query string to force the UI into dark mode for testing or user preference overrides.
$forceDarkClass = (isset($_GET['force_dark']) && $_GET['force_dark'] == '1') ? ' theme-dark' : '';
?>
<!DOCTYPE html>
<html lang="en" style="color-scheme: light dark;">
<head>
    <meta charset="UTF-8">
    <!-- Viewport configuration prevents zooming on input focus, essential for native app feel -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    
    <!-- Dynamic theme colors based on OS preference -->
    <meta name="theme-color" content="#121212" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-icon-180.png">
    
    <!-- PWA iOS Specific Configuration -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <!-- iOS Splash Screens (Exhaustive list to cover all device form factors) -->
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1668-2388.jpg" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2388-1668.jpg" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1668-2224.jpg" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2224-1668.jpg" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1536-2048.jpg" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2048-1536.jpg" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1640-2360.jpg" media="(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2360-1640.jpg" media="(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1620-2160.jpg" media="(device-width: 810px) and (device-height: 1080px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2160-1620.jpg" media="(device-width: 810px) and (device-height: 1080px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1488-2266.jpg" media="(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2266-1488.jpg" media="(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1320-2868.jpg" media="(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2868-1320.jpg" media="(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1206-2622.jpg" media="(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2622-1206.jpg" media="(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1260-2736.jpg" media="(device-width: 420px) and (device-height: 912px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2736-1260.jpg" media="(device-width: 420px) and (device-height: 912px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1290-2796.jpg" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2796-1290.jpg" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1179-2556.jpg" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2556-1179.jpg" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1170-2532.jpg" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2532-1170.jpg" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1284-2778.jpg" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2778-1284.jpg" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1080-2340.jpg" media="(device-width: 360px) and (device-height: 780px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2340-1080.jpg" media="(device-width: 360px) and (device-height: 780px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1242-2688.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2688-1242.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1125-2436.jpg" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2436-1125.jpg" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-828-1792.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1792-828.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1242-2208.jpg" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-2208-1242.jpg" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-750-1334.jpg" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1334-750.jpg" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-640-1136.jpg" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="https://assets.raggiesoft.com/raggiesoft-books/images/pwa-icons/apple-splash-1136-640.jpg" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    
    <link rel="manifest" href="/manifest.json">
    <title><?= htmlspecialchars($siteName ?? 'Ocean View Archives') ?></title>
    
    <!-- OpenGraph & Twitter Metadata generation based on injected PHP variables -->
    <?php if (!empty($ogTitle)): ?>
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <?php endif; ?>
    
    <?php if (!empty($ogDescription)): ?>
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <?php endif; ?>
    
    <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>
    
    <?php if (!empty($ogUrl)): ?>
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>">
    <?php endif; ?>
    
    <meta property="og:type" content="website">

    <?php $assetsUrl = $cdnBaseUrl ?? 'https://assets.raggiesoft.com'; ?>
    
    <!-- Core CSS Pipelines -->
    <link rel="stylesheet" href="<?= $assetsUrl ?>/common/css/raggiesoft-grid.css">
    <link rel="stylesheet" href="<?= $assetsUrl ?>/stardust-engine-library/css/stardust-engine.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?= $assetsUrl ?>/stardust-engine-library/css/theme-<?= htmlspecialchars($currentPageTheme ?? 'oceanview') ?>.min.css">
    
    <!-- Typographic Assets -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap">
    <!-- Phosphor Icons (fallback for UI icons) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400;1,700&family=Inter:wght@400;600;700&family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    
    <!-- Self-Hosted Typographic Assets -->
    <style>
        @font-face {
            font-family: 'OpenDyslexic';
            src: url('<?= $assetsUrl ?>/fonts/opendyslexic/OpenDyslexic-Regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'OpenDyslexic';
            src: url('<?= $assetsUrl ?>/fonts/opendyslexic/OpenDyslexic-Italic.woff') format('woff');
            font-weight: 400;
            font-style: italic;
            font-display: swap;
        }
        @font-face {
            font-family: 'OpenDyslexic';
            src: url('<?= $assetsUrl ?>/fonts/opendyslexic/OpenDyslexic-Bold.woff') format('woff');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'OpenDyslexic';
            src: url('<?= $assetsUrl ?>/fonts/opendyslexic/OpenDyslexic-BoldItalic.woff') format('woff');
            font-weight: 700;
            font-style: italic;
            font-display: swap;
        }
    </style>

    <!-- Google Analytics Integration -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-P9RG9JVYB6"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-P9RG9JVYB6');
    </script>
    <script src="/scripts/pwa.js"></script>
</head>

<body class="theme-<?= htmlspecialchars($currentPageTheme ?? 'oceanview') ?><?= $forceDarkClass ?>">
    
    <!-- Accessibility focus anchor -->
    <a href="#stardust-main-content" class="visually-hidden-focusable" style="position: absolute; z-index: 9999; padding: 1rem; background: var(--rs-primary); color: white; text-decoration: none; border-radius: 4px; left: 1rem; top: 1rem;">Skip to main content</a>

    <!-- Audio Player (Outside SPA Zone) -->
    <!-- Positioned outside #stardust-app so navigating between pages doesn't kill playback -->
    <audio id="stardust-bgm" loop preload="auto"></audio>

    <!-- The SPA router ONLY replaces the contents of this div -->
    <div id="stardust-app">
        <?php 
        // Allow dynamic injection of a specific header bar menu if configured by the view
        if (isset($currentHeaderMenu) && file_exists($currentHeaderMenu)) {
            require_once $currentHeaderMenu;
        } 
        ?>
