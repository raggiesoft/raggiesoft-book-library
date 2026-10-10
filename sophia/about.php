<?php
// Sophia's About View (Standalone)
global $cdnBaseUrl;
?>
<div class="stardust-mobile-scroll" style="flex: 1; background: var(--rs-bg); padding: 1rem 1rem 6rem 1rem;">
            <!-- This section honors the lore that Isabel built this reader to preserve the family's narratives -->
        <div style="background: var(--rs-surface); border: 1px solid var(--rs-border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--rs-heading); margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-code"></i> About the Developer
            </h2>
            
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Isabel: Lead Dev -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/isabel.jpg" alt="Isabel" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.2rem; font-weight: 700;">Isabel</h3>
                        <p style="margin: 0; font-size: 0.95rem; opacity: 0.8; line-height: 1.5;">Lead Developer & Architect. The book reader was her vision, coded to ensure the family's stories survive digitally across the universe.</p>
                    </div>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--rs-border); margin: 0;">
                <h4 style="margin: 0; font-size: 1rem; color: var(--rs-primary);">Acknowledgments & Support</h4>
                
                <!-- Eleanor: Content -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/eleanor.jpg" alt="Eleanor" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Eleanor</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">Content Strategy & Editorial Lead. Ensuring every narrative fragment is properly archived.</p>
                    </div>
                </div>
                
                <!-- Sophia: Design -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/sophia.jpg" alt="Sophia" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Sophia</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">UI/UX Design. Keeping the interface clean, intuitive, and accessible.</p>
                    </div>
                </div>
                
                <!-- Oliver: Infrastructure -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <img src="<?php echo $cdnBaseUrl; ?>/raggiesoft-books/images/about/oliver.jpg" alt="Oliver" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div>
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 700;">Oliver</h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">Systems & Infrastructure. Making sure the servers run and everything stays online.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- LEGAL, ACCESSIBILITY & POLICIES SECTION -->
        <!-- Consolidates previously scattered pages into one cohesive menu -->

</div>
<?php include __DIR__ . '/../includes/components/bottom-nav.php'; ?>
