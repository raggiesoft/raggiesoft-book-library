<?php
// Stardust Engine Library: Global Header Component
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($siteName ?? 'Ocean View Archives') ?></title>
    <link rel="stylesheet" href="https://assets.raggiesoft.com/stardust-engine-library/css/stardust-engine.min.css">
    <link rel="stylesheet" href="https://assets.raggiesoft.com/stardust-engine-library/css/theme-<?= htmlspecialchars($currentPageTheme ?? 'oceanview') ?>.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap">
    <!-- Phosphor Icons (fallback for UI icons) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="theme-<?= htmlspecialchars($currentPageTheme ?? 'oceanview') ?>">

    <!-- Audio Player (Outside SPA Zone) -->
    <audio id="stardust-bgm" loop preload="auto"></audio>

    <!-- The SPA router ONLY replaces the contents of this div -->
    <div id="stardust-app">
        <?php 
        if (isset($currentHeaderMenu) && file_exists($currentHeaderMenu)) {
            require_once $currentHeaderMenu;
        } 
        ?>
