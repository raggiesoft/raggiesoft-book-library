<?php
// Stardust Engine Library: Global Sidebar Component
global $requestUri;
?>
<aside id="stardust-sidebar">
    <h3>Stardust Engine</h3>
    <p><strong>Router:</strong> /chloe/isabel.php</p>
    <p><strong>URI:</strong> <?php echo htmlspecialchars($requestUri); ?></p>
    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 20px 0;">
    <nav>
        <p><a href="/test/page-1">Test Page 1</a></p>
        <p><a href="/test/page-2">Test Page 2</a></p>
        <p><a href="/test/page-3">Test Page 3</a></p>
    </nav>
</aside>
