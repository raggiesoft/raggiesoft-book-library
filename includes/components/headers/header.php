<?php
// Stardust Engine Library: Global Header Component
$forceDarkClass = (isset($_GET['force_dark']) && $_GET['force_dark'] == '1') ? ' theme-dark' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($siteName ?? 'Ocean View Archives') ?></title>
    <?php $assetsUrl = $cdnBaseUrl ?? 'https://assets.raggiesoft.com'; ?>
    <link rel="stylesheet" href="<?= $assetsUrl ?>/common/css/raggiesoft-grid.css">
    <link rel="stylesheet" href="<?= $assetsUrl ?>/stardust-engine-library/css/stardust-engine.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?= $assetsUrl ?>/stardust-engine-library/css/theme-<?= htmlspecialchars($currentPageTheme ?? 'oceanview') ?>.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap">
    <!-- Phosphor Icons (fallback for UI icons) -->
        <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400;1,700&family=Inter:wght@400;600;700&family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-P9RG9JVYB6"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-P9RG9JVYB6');
    </script>
</head>
<body class="theme-<?= htmlspecialchars($currentPageTheme ?? 'oceanview') ?><?= $forceDarkClass ?>">
    <a href="#stardust-main-content" class="visually-hidden-focusable" style="position: absolute; z-index: 9999; padding: 1rem; background: var(--rs-primary); color: white; text-decoration: none; border-radius: 4px; left: 1rem; top: 1rem;">Skip to main content</a>

    <!-- Audio Player (Outside SPA Zone) -->
    <audio id="stardust-bgm" loop preload="auto"></audio>

    <!-- The SPA router ONLY replaces the contents of this div -->
    <div id="stardust-app">
        <?php 
        if (isset($currentHeaderMenu) && file_exists($currentHeaderMenu)) {
            require_once $currentHeaderMenu;
        } 
        ?>
