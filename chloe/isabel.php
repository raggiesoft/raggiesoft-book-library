<?php
// Stardust Engine Library: Master Router
header('Content-Type: text/html; charset=utf-8');

$requestUri = $_SERVER['REQUEST_URI'];

// Here we will eventually parse the URI, load the proper katie.json, and fetch markdown.
// For now, we are establishing the base HTML5 shell and the SPA layout.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Stardust Engine Library</title>
    
    <!-- We will inject our clean, native CSS here later -->
    <style>
        /* Temporary structural styles to prove the SPA layout */
        body { margin: 0; font-family: system-ui, sans-serif; background: #f4f4f5; color: #18181b; overflow: hidden; }
        #stardust-app { display: flex; height: 100vh; width: 100vw; }
        #stardust-sidebar { width: 300px; background: #ffffff; border-right: 1px solid #e2e8f0; padding: 20px; overflow-y: auto; }
        #stardust-reading-pane { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; align-items: center; }
        .book-page { max-width: 680px; width: 100%; font-size: 1.125rem; line-height: 1.7; }
        a { color: #2563eb; text-decoration: none; font-weight: 500; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<!-- The SPA router will dynamically swap this class to change themes (e.g., theme-aethel, theme-dark) -->
<body class="theme-default">

    <!-- The Audio Player stays OUTSIDE the SPA swap zone so it never gets interrupted! -->
    <audio id="stardust-bgm" loop preload="auto">
        <!-- We'll inject audio sources dynamically later -->
    </audio>

    <!-- The SPA router ONLY replaces the contents of this div -->
    <div id="stardust-app">
        
        <aside id="stardust-sidebar">
            <h3>Stardust Engine</h3>
            <p><strong>Router:</strong> /chloe/isabel.php</p>
            <p><strong>URI:</strong> <?php echo htmlspecialchars($requestUri); ?></p>
            <hr style="border:0; border-top:1px solid #e2e8f0; margin: 20px 0;">
            <nav>
                <p><a href="/test/page-1">Test Page 1</a></p>
                <p><a href="/test/page-2">Test Page 2</a></p>
                <p><a href="/test/page-3">Test Page 3</a></p>
            </nav>
        </aside>

        <main id="stardust-reading-pane" tabindex="-1">
            <div class="book-page">
                <h1>Welcome to the Library</h1>
                <p>This is the raw, native HTML5 foundation of the Stardust Engine Book Library.</p>
                <p>Click any of the links in the sidebar. Because we are using the new <code>stardust-spa.js</code> router, the page will instantly swap the content without ever triggering a hard browser reload. This ensures the background music never skips a beat.</p>
                <p><strong>Current Path:</strong> <code><?php echo htmlspecialchars($requestUri); ?></code></p>
            </div>
        </main>

    </div>

    <!-- The hyper-optimized Stardust SPA Router -->
    <script src="https://assets.raggiesoft.com/common/js/stardust-spa.js"></script>

</body>
</html>
