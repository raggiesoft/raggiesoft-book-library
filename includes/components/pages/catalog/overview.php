<?php
// includes/components/pages/catalog/overview.php
// Contemporary Fiction Library - Migrated from Hub

$cdnBaseUrl = 'https://assets.raggiesoft.com';

$heroImages = [
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/1.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/2.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/3.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/4.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/5.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/6.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/7.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/8.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/9.jpg",
    $cdnBaseUrl . "/raggiesoft-books/images/library-hero/10.jpg"
];
$startImage = !empty($heroImages) 
    ? $heroImages[array_rand($heroImages)] 
    : $cdnBaseUrl . "/common/patterns/stars-transparent.png";
$imagesJson = htmlspecialchars(json_encode($heroImages), ENT_QUOTES, 'UTF-8');
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Audiowide&display=swap');
    
    /* =====================================================================
       BRUTE FORCE READABILITY ARMOR
       These classes ensure the hero section text, backgrounds, and borders
       remain highly visible over the rotating background images
       ===================================================================== */
    .force-text-light { color: #ffffff !important; }
    .force-text-muted { color: rgba(255, 255, 255, 0.75) !important; }
    
    .force-glass-bg {
        background-color: rgba(0, 0, 0, 0.65) !important;
        backdrop-filter: blur(8px) !important;
    }

    .force-border-default { border: 1px solid rgba(255, 255, 255, 0.1) !important; }

    .force-shadow-heavy {
        text-shadow: 0px 4px 15px rgba(0,0,0,0.9), 0px 1px 3px rgba(0,0,0,1) !important;
    }
    .force-shadow-medium {
        text-shadow: 0px 2px 8px rgba(0,0,0,0.9) !important;
    }

    .immersive-container {
        position: relative;
        overflow: hidden;
        width: 100%;
        background-color: #000;
        min-height: 100vh;
        padding-bottom: 50px;
        display: flex;
        flex-direction: column;
    }

    .hero-bg-layer {
        position: absolute;
        background-attachment: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        transition: opacity 2s ease-in-out; 
        z-index: 0;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        background: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.8));
    }
    
    .content-wrapper {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .hero-banner {
        display: flex;
        justify-content: center;
        margin-top: 5rem;
        margin-bottom: 4rem;
        padding: 0 1rem;
    }
    
    .hero-banner-content {
        padding: 2rem;
        border-radius: 1rem;
        text-align: center;
        max-width: 800px;
        box-shadow: 0 1rem 3rem rgba(0,0,0,0.5);
    }
    
    .hero-banner-content h1 {
        font-family: 'Audiowide', cursive;
        font-size: clamp(2rem, 5vw, 3.5rem);
        margin: 0 0 1rem 0;
        text-transform: uppercase;
        font-weight: 700;
    }
    
    .hero-banner-content p {
        font-size: 1.25rem;
        margin: 0 auto 2rem auto;
        font-weight: 600;
        max-width: 700px;
        line-height: 1.5;
    }
    
    .hero-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        border: 2px solid #fff;
        background: transparent;
        color: #fff;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .hero-btn:hover {
        background: #fff;
        color: #000;
    }
    
    .catalog-wrapper {
        padding: 0 2rem 4rem 2rem;
    }
</style>

<main id="stardust-reading-pane" style="padding: 0 !important; display: block !important; overflow-x: hidden !important; width: 100% !important; flex: 1;">
<div class="immersive-container hero-rotator-container" data-images="<?php echo $imagesJson; ?>">
    <div class="hero-bg-layer hero-bg-layer-1" style="background-image: url('<?php echo $startImage; ?>');"></div>
    <div class="hero-bg-layer hero-bg-layer-2" style="background-image: url(''); opacity: 0;"></div>
    <div class="hero-overlay"></div>

    <div class="content-wrapper">
        <div class="hero-banner">
            <div class="hero-banner-content force-glass-bg force-border-default">
                <h1 class="force-text-light force-shadow-heavy">
                    Contemporary Library
                </h1>
                <p class="force-text-light force-shadow-medium">
                    The grounded, real-world archives of RaggieSoft Media.
                </p>
                <div>
                    <a href="https://raggiesoft.com/raggiesoft-books/image-library" class="hero-btn">
                        <i class="ph ph-images"></i> View Image Library
                    </a>
                </div>
            </div>
        </div>

        <div class="catalog-wrapper">
            <section class="stardust-grid">
                <?php
                // Fetch the master catalog directly from the CDN
                $catalogUrl = $cdnBaseUrl . '/raggiesoft-books/books/catalog.json';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $catalogUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
                $catalogData = curl_exec($ch);
                curl_close($ch);

                $books = [];
                
                if ($catalogData) {
                    $books = json_decode($catalogData, true) ?? [];
                }

                if (empty($books)):
                ?>
                    <div style="text-align: center; padding: 4rem 0; width: 100%; grid-column: 1 / -1;">
                        <i class="ph ph-books" style="font-size: 4rem; color: #fff; opacity: 0.5;"></i>
                        <h3 style="margin-top: 1rem; color: #fff; opacity: 0.7;">Library Catalog Offline</h3>
                        <p style="color: #fff; opacity: 0.5;">The system is currently compiling the archives. Please check back later.</p>
                    </div>
                <?php
                else:
                    foreach ($books as $book):
                        $slug = $book['slug'] ?? '';
                        $title = $book['title'] ?? 'Unknown Archive';
                        $desc = $book['description'] ?? '';
                        
                        $route = !empty($book['first_route']) ? str_replace('/raggiesoft-books/books', '', $book['first_route']) : '/' . $slug;
                        
                        // Determine cover art
                        $image = $cdnBaseUrl . '/raggiesoft-books/images/book-placeholder.jpg';
                        if (!empty($book['image'])) {
                            $image = $cdnBaseUrl . $book['image'];
                        }
                ?>
                    <a href="<?php echo htmlspecialchars($route); ?>" class="stardust-card">
                        <div class="stardust-card-cover theme-oceanview" style="position: relative; padding-top: 150%;">
                            <?php if($image): ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="stardust-card-cover-fallback">
                                    <h3 style="margin: 0; color: #fff; font-size: 1.5rem;"><?php echo htmlspecialchars($title); ?></h3>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="stardust-card-info">
                            <h4><?php echo htmlspecialchars($title); ?></h4>
                            <p><?php echo htmlspecialchars($desc); ?></p>
                            <span class="stardust-card-action"><i class="ph ph-book-open"></i> Read Series &rarr;</span>
                        </div>
                    </a>
                <?php
                    endforeach;
                endif;
                ?>
            </section>
        </div>
    </div>
</div>
</main>
<script src="<?php echo $cdnBaseUrl; ?>/common/js/hero-image.js"></script>
