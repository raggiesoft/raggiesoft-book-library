<?php
// Stardust Engine Library: Global Footer Component
?>
    </div> <!-- Close #stardust-app -->

    <!-- The hyper-optimized Stardust SPA Router -->
    <script src="https://assets.raggiesoft.com/stardust-engine-library/js/stardust-spa.min.js"></script>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>
