<?php
// Stardust Engine Library: Master Router
header('Content-Type: text/html; charset=utf-8');

// The Nginx config proxies everything to this script, so we parse the URI here
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = realpath(__DIR__ . '/../');

// Include Header
require_once $basePath . '/includes/components/headers/header.php';

// Include Sidebar
require_once $basePath . '/includes/components/sidebars/sidebar.php';
?>

<main id="stardust-reading-pane" tabindex="-1">
    <div class="book-page">
        <h1>Welcome to the Library</h1>
        <p>This is the raw, native HTML5 foundation of the Stardust Engine Book Library.</p>
        <p>Click any of the links in the sidebar. Because we are using the new <code>stardust-spa.js</code> router, the page will instantly swap the content without ever triggering a hard browser reload. This ensures the background music never skips a beat.</p>
        <p><strong>Current Path:</strong> <code><?php echo htmlspecialchars($requestUri); ?></code></p>
    </div>
</main>

<?php
// Include Footer
require_once $basePath . '/includes/components/footers/footer.php';
?>
