<?php
// Stardust Engine Library: Global Header Component
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Stardust Engine Library</title>
    
    <!-- We will inject our clean, native CSS here later. For now, some raw wireframe styles. -->
    <style>
        :root {
            --rs-bg: #f4f4f5;
            --rs-text: #18181b;
            --rs-primary: #2563eb;
            --rs-border: #e2e8f0;
            --rs-surface: #ffffff;
        }

        body { margin: 0; font-family: system-ui, sans-serif; background: var(--rs-bg); color: var(--rs-text); overflow: hidden; }
        
        #stardust-app { display: flex; height: 100vh; width: 100vw; }
        
        #stardust-sidebar { width: 300px; background: var(--rs-surface); border-right: 1px solid var(--rs-border); padding: 20px; overflow-y: auto; }
        
        #stardust-reading-pane { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; align-items: center; }
        
        .book-page { max-width: 680px; width: 100%; font-size: 1.125rem; line-height: 1.7; }
        
        a { color: var(--rs-primary); text-decoration: none; font-weight: 500; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body class="theme-default">

    <!-- Audio Player (Outside SPA Zone) -->
    <audio id="stardust-bgm" loop preload="auto"></audio>

    <!-- The SPA router ONLY replaces the contents of this div -->
    <div id="stardust-app">
