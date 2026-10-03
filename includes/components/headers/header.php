<?php
// Stardust Engine Library: Global Header Component
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ocean View Archives</title>
    <link rel="stylesheet" href="https://assets.raggiesoft.com/stardust-engine-library/css/stardust-engine.min.css">
    <link rel="stylesheet" href="https://assets.raggiesoft.com/stardust-engine-library/css/theme-oceanview.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap">
    <!-- Phosphor Icons (fallback for UI icons) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="theme-oceanview">

    <!-- Audio Player (Outside SPA Zone) -->
    <audio id="stardust-bgm" loop preload="auto"></audio>

    <!-- The SPA router ONLY replaces the contents of this div -->
    <div id="stardust-app">
        
        <header id="stardust-header">
            <div style="display: flex; align-items: center;">
                <a href="/" style="display: flex; align-items: center; gap: 10px; color: var(--rs-primary);">
                    <img src="https://assets.raggiesoft.com/raggiesoft-books/images/logos/oceanview-archives.svg" alt="Ocean View Logo" width="30" height="30">
                    <span style="font-family: 'Playfair Display', serif; font-weight: bold; letter-spacing: 1px;">OCEAN VIEW ARCHIVES</span>
                </a>
            </div>
            
            <nav style="display: flex; gap: 20px;">
                <a href="/catalog" style="display: flex; align-items: center; gap: 5px;">
                    <i class="ph ph-books"></i> The Catalog
                </a>
                <a href="https://raggiesoft.com" style="display: flex; align-items: center; gap: 5px;" target="_blank">
                    <i class="ph ph-arrow-square-out"></i> RaggieSoft
                </a>
            </nav>
        </header>

