<?php
/**
 * ============================================================================
 * ARCHITECTURE & MODULE OVERVIEW: sidebar.php
 * ============================================================================
 * Purpose:
 * A global sidebar component utilized for desktop layouts or slide-out mobile 
 * menus. Currently acts as a diagnostic and development navigation tool.
 * 
 * Architectural Role:
 * Provides persistent navigation and debugging information across different 
 * routed views. It helps developers verify the active URI and the core router 
 * in use (Isabel via Chloe).
 * 
 * Key Components:
 * 1. State Inspection: Reads the global `$requestUri` injected by the Stardust 
 *    router to display the current virtual path.
 * 2. Static Navigation: Hardcoded links for testing the SPA routing mechanics.
 * 
 * Maintenance Notes:
 * - This component relies on the `$requestUri` global. If the variable scoping 
 *   in the parent view restricts this, the diagnostic output will fail.
 * - Future iterations should replace the hardcoded "Test Page" links with 
 *   dynamic series or book navigation fetched from `katie.json`.
 * ============================================================================
 */

// Stardust Engine Library: Global Sidebar Component
global $requestUri;
?>
<aside id="stardust-sidebar">
    <!-- Diagnostic Header -->
    <h3>Stardust Engine</h3>
    <p><strong>Router:</strong> /chloe/isabel.php</p>
    <p><strong>URI:</strong> <?php echo htmlspecialchars($requestUri); ?></p>
    
    <hr style="border:0; border-top:1px solid var(--rs-border); margin: 20px 0;">
    
    <!-- Navigation Links -->
    <nav>
        <p><a href="/test/page-1">Test Page 1</a></p>
        <p><a href="/test/page-2">Test Page 2</a></p>
        <p><a href="/test/page-3">Test Page 3</a></p>
    </nav>
</aside>
