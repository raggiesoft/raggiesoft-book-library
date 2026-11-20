<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: includes/components/illustration-lightbox-dialog.php
 * App:  RaggieSoft Book Reader (`https://books.raggiesoft.com`)
 *
 * WHAT IS THIS FILE?
 *   A "Lightbox" is a full-screen-overlay image viewer that dims the page behind
 *   it so you can inspect a story illustration at large resolution, read its
 *   scene caption, and step backward/forward (`Previous` / `Next`) through all
 *   illustrations on the page.
 *
 *   Instead of loading a heavy third-party lightbox library, this component builds
 *   one shared, zero-dependency lightbox using the modern **native HTML5 `<dialog>`
 *   element** (`<dialog id="ova-illus-lightbox">`), shared across two places:
 *     1. **Table of Illustrations** (`includes/components/illustrations-toc.php`),
 *        when clicking any card's "Inspect" button (`.illus-toc-zoom-btn`).
 *     2. **Live Story Chapters** (`sophia/oliver.php`), when clicking (or pressing
 *        `Enter` / `Space` on) any illustration inside `<article class="story-content">`.
 *
 * HOW DOES NATIVE HTML `<dialog>` WORK? (Key Web Platform Concept!)
 *   - By default, a `<dialog>` tag in HTML is completely invisible (`open === false`).
 *   - When JavaScript calls `dialog.showModal()` (NOT `.show()`!), the browser
 *     automatically:
 *       1. Promotes the `<dialog>` into the browser's special **Top Layer**
 *          (above every `z-index` on the page—no z-index wars!).
 *       2. Renders a dim frosted-glass `::backdrop` behind it.
 *       3. **Traps keyboard `Tab` focus** inside the dialog (and makes the rest
 *          of the page inert to clicks and screen readers!).
 *       4. Listens for the **`Escape` key** natively and closes the dialog!
 *   - Adding `closedby="any"` tells modern Chromium/Firefox browsers to also close
 *     when the user clicks outside the dialog box ("light dismiss"), and we include
 *     a 12-line bounding-box click fallback at the bottom for Safari!
 * =============================================================================
 */

// Singleton Guard: If both `illustrations-toc.php` and `sophia/oliver.php` include this file
// during the same PHP page render, `return;` on the second include so we never emit duplicate
// `<dialog id="ova-illus-lightbox">` elements into the DOM!
if (!empty($GLOBALS['__ova_illus_lightbox_rendered'])) {
    return;
}
$GLOBALS['__ova_illus_lightbox_rendered'] = true;

// Resolve the current book slug (`rachel`, `katie`, etc.) so inline chapter images can link
// to `/<seriesSlug>/illustrations`.
global $seriesSlug, $activeSeriesSlug;
$resolvedLightboxSeriesSlug = '';
if (!empty($activeSeriesSlug)) {
    $resolvedLightboxSeriesSlug = (string)$activeSeriesSlug;
} elseif (!empty($seriesSlug)) {
    $resolvedLightboxSeriesSlug = (string)$seriesSlug;
}
?>
<style>
/* ==========================================================================
 * SHARED NATIVE <dialog> ILLUSTRATION LIGHTBOX STYLES
 * Note on CSS Logical Properties used below:
 *   - `inline-size` / `max-inline-size` = `width` / `max-width`
 *   - `block-size`  / `max-block-size`  = `height` / `max-height`
 *   - `dvh` = Dynamic Viewport Height (accounts for mobile Safari's URL bar!)
 * ========================================================================== */
.illus-lightbox-dialog {
    max-inline-size: min(94vw, 1240px);
    max-block-size: min(92dvh, 900px);
    inline-size: 100%;
    padding: 0;
    border-radius: 0.85rem;
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.4));
    background: var(--rs-surface, #181215);
    color: var(--rs-text, #f4efe6);
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.78);
    overflow: hidden;
}

/* `:modal` matches `<dialog>` ONLY when opened via `.showModal()`.
   `overscroll-behavior: contain` stops trackpad/touch scrolling from leaking to the page behind! */
.illus-lightbox-dialog:modal {
    overscroll-behavior: contain;
}

/* `::backdrop` is the browser's built-in full-screen overlay behind a modal `<dialog>`. */
.illus-lightbox-dialog::backdrop {
    overflow: hidden;
    overscroll-behavior: contain;
    background: rgba(8, 6, 8, 0.84);
    backdrop-filter: blur(5px);
}

.illus-lightbox-inner {
    display: flex;
    flex-direction: column;
    max-block-size: min(92dvh, 900px);
    overflow: hidden;
}

.illus-lightbox-header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    padding: 0.75rem 1.15rem;
    border-block-end: 1px solid var(--rs-border, rgba(227, 178, 124, 0.22));
}

.illus-lightbox-dialog .illus-toc-chap-eyebrow {
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rs-primary, #e3b27c);
}

.illus-lightbox-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.3;
    color: var(--rs-text, #f4efe6);
}

.illus-lightbox-close {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    inline-size: 2.1rem;
    block-size: 2.1rem;
    border-radius: 0.45rem;
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.35));
    background: transparent;
    color: var(--rs-text, #f4efe6);
    cursor: pointer;
    font-size: 1.1rem;
}

.illus-lightbox-close:hover,
.illus-lightbox-close:focus-visible {
    background: rgba(227, 178, 124, 0.2);
}

.illus-lightbox-body {
    flex: 1 1 auto;
    min-block-size: 0;
    padding: 0.85rem 1.15rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.65rem;
}

/* `object-fit: contain` ensures the entire 16:9 or portrait artwork fits inside the modal
   without ever cropping a character's head or edges! */
.illus-lightbox-img {
    display: block;
    inline-size: auto;
    max-inline-size: 100%;
    max-block-size: clamp(220px, 65dvh, 680px);
    flex: 0 1 auto;
    min-block-size: 0;
    object-fit: contain;
    border-radius: 0.5rem;
    background: #0b080a;
}

.illus-lightbox-caption {
    flex-shrink: 0;
    inline-size: 100%;
    margin: 0;
    font-size: 0.86rem;
    line-height: 1.48;
    color: var(--rs-text, #f4efe6);
    opacity: 0.92;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    overflow: clip;
}

.illus-lightbox-footer {
    flex-shrink: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    padding: 0.7rem 1.15rem;
    border-block-start: 1px solid var(--rs-border, rgba(227, 178, 124, 0.22));
}

.illus-lightbox-nav-group {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
}

.illus-lightbox-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.32rem;
    padding: 0.36rem 0.72rem;
    border-radius: 0.45rem;
    border: 1px solid var(--rs-border, rgba(227, 178, 124, 0.35));
    background: var(--rs-bg);
    color: var(--rs-text, #f4efe6);
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.illus-lightbox-nav-btn:hover:not(:disabled),
.illus-lightbox-nav-btn:focus-visible:not(:disabled) {
    background: rgba(227, 178, 124, 0.2);
    border-color: var(--rs-primary, #e3b27c);
    color: var(--rs-primary, #e3b27c);
}

.illus-lightbox-nav-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.illus-lightbox-counter {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.24rem 0.55rem;
    border-radius: 999px;
    background: rgba(227, 178, 124, 0.14);
    color: var(--rs-primary, #e3b27c);
}

.illus-lightbox-dialog .illus-toc-read-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--rs-primary, #e3b27c);
    text-decoration: none;
}

.illus-lightbox-dialog .illus-toc-read-link:hover,
.illus-lightbox-dialog .illus-toc-read-link:focus-visible {
    text-decoration: underline;
}

/* Magnifying-glass cursor (`zoom-in`) & keyboard focus ring for clickable chapter images */
.story-content img.ova-clickable-story-img {
    cursor: zoom-in;
    transition: transform 0.18s ease, box-shadow 0.18s ease, outline-color 0.18s ease;
}

.story-content img.ova-clickable-story-img:focus-visible {
    outline: 3px solid var(--rs-primary, #e3b27c);
    outline-offset: 3px;
}

/* Respect readers who enabled "Reduce Motion" in OS Accessibility Settings! */
@media (prefers-reduced-motion: no-preference) {
    .story-content p > img.ova-clickable-story-img:hover {
        transform: scale(1.01);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.28);
    }
}
</style>

<!--
  Native HTML5 `<dialog>` Modal Markup:
  - `closedby="any"` allows clicking the dimmed backdrop OR pressing `Escape` to close.
  - `aria-labelledby="ova-illus-lightbox-title"` links the modal's accessible name to its `<h3>` title.
  - `aria-live="polite"` on `#ova-illus-lightbox-counter` announces `"2 / 14"` quietly to screen readers when stepping.
-->
<dialog id="ova-illus-lightbox"
        class="illus-lightbox-dialog"
        closedby="any"
        data-series-slug="<?= htmlspecialchars($resolvedLightboxSeriesSlug, ENT_QUOTES, 'UTF-8') ?>"
        aria-labelledby="ova-illus-lightbox-title">
    <div class="illus-lightbox-inner">
        <div class="illus-lightbox-header">
            <div style="min-inline-size: 0;">
                <div id="ova-illus-lightbox-chap" class="illus-toc-chap-eyebrow"></div>
                <h3 id="ova-illus-lightbox-title" class="illus-lightbox-title"></h3>
            </div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <a href="/illustrations" id="ova-illus-lightbox-up" class="illus-lightbox-nav-btn" title="Up to All-Narratives Illustrations Gallery">
                    <i class="ph ph-arrow-up" aria-hidden="true"></i>
                    <span>All Narratives</span>
                </a>
                <button type="button" id="ova-illus-lightbox-close" class="illus-lightbox-close" aria-label="Close illustration preview">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="illus-lightbox-body">
            <img id="ova-illus-lightbox-img" class="illus-lightbox-img" src="" alt="">
            <p id="ova-illus-lightbox-caption" class="illus-lightbox-caption"></p>
        </div>
        <div class="illus-lightbox-footer">
            <div class="illus-lightbox-nav-group" role="group" aria-label="Navigate between illustrations">
                <button type="button" id="ova-illus-lightbox-prev" class="illus-lightbox-nav-btn" aria-label="Previous illustration">
                    <i class="ph ph-caret-left" aria-hidden="true"></i>
                    <span>Previous</span>
                </button>
                <span id="ova-illus-lightbox-counter" class="illus-lightbox-counter" aria-live="polite">1 / 1</span>
                <button type="button" id="ova-illus-lightbox-next" class="illus-lightbox-nav-btn" aria-label="Next illustration">
                    <span>Next</span>
                    <i class="ph ph-caret-right" aria-hidden="true"></i>
                </button>
            </div>
            <a id="ova-illus-lightbox-link" href="#" class="illus-toc-read-link">
                <span id="ova-illus-lightbox-link-label">Read Full Scene in Context</span>
                <i class="ph ph-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</dialog>

<script>
// Immediately Invoked Function Expression (IIFE: `(function() { ... })();`):
// Wraps all variables (`dialog`, `imgEl`, `currentTrigger`, etc.) inside a private function
// scope so they never collide with global variables when `stardust-spa.js` re-runs scripts!
(function() {
    const dialog = document.getElementById('ova-illus-lightbox');
    // `dataset.bound` reads `data-bound="true"` on the element so we never attach duplicate listeners
    if (!dialog || dialog.dataset.bound === 'true') return;
    dialog.dataset.bound = 'true';

    // Cache references to every sub-element inside the `<dialog>` once at setup time
    const imgEl = document.getElementById('ova-illus-lightbox-img');
    const titleEl = document.getElementById('ova-illus-lightbox-title');
    const chapEl = document.getElementById('ova-illus-lightbox-chap');
    const captionEl = document.getElementById('ova-illus-lightbox-caption');
    const linkEl = document.getElementById('ova-illus-lightbox-link');
    const linkLabelEl = document.getElementById('ova-illus-lightbox-link-label');
    const closeBtn = document.getElementById('ova-illus-lightbox-close');
    const prevBtn = document.getElementById('ova-illus-lightbox-prev');
    const nextBtn = document.getElementById('ova-illus-lightbox-next');
    const counterEl = document.getElementById('ova-illus-lightbox-counter');

    // 1. Discover any explicit Table of Illustrations `.illus-toc-zoom-btn` ("Inspect") buttons
    const tocZoomBtns = Array.from(document.querySelectorAll('.illus-toc-zoom-btn'));

    // 2. Discover inline story & hero plate `<img>` elements inside `article.story-content`
    //    (when viewing a chapter/part in `oliver.php`), excluding audio album art or images inside links/galleries.
    const seriesHeaderEl = document.querySelector('.reader-series-header');
    const partHeaderEl = document.querySelector('.reader-part-header');
    const defaultChapEyebrow = seriesHeaderEl ? (seriesHeaderEl.textContent || '').trim() : '';
    const defaultPartTitle = partHeaderEl ? (partHeaderEl.textContent || '').trim() : '';
    const activeSeriesSlug = dialog.getAttribute('data-series-slug') || (window.location.pathname.split('/').filter(Boolean)[0] || '');
    const bookIllustrationsUrl = activeSeriesSlug ? ('/' + activeSeriesSlug + '/illustrations') : '/illustrations';

    const rawStoryImgs = Array.from(document.querySelectorAll('.story-content img')).filter(function(img) {
        if (img.closest('.illus-toc-wrapper, .reader-audio-player, a, [data-no-lightbox]')) {
            return false;
        }
        const src = img.getAttribute('src') || img.currentSrc || img.src || '';
        return src !== '';
    });

    // Deduplicate story images by `src` using a JavaScript `Map` (`src -> canonical <img>`)
    // so if a frontmatter hero plate is also embedded inline in the prose, the `1 / N` stepper
    // counts it once while clicking EITHER copy opens the lightbox at the right slot!
    const uniqueStoryTriggers = [];
    const storyTriggerBySrc = new Map();

    rawStoryImgs.forEach(function(img) {
        const src = img.getAttribute('src') || img.currentSrc || img.src || '';
        const alt = (img.getAttribute('alt') || '').trim();

        // Make the `<img>` keyboard-focusable (`tabindex="0"`) and announceable as a dialog button
        img.classList.add('ova-clickable-story-img');
        if (!img.hasAttribute('tabindex')) {
            img.setAttribute('tabindex', '0');
        }
        if (!img.hasAttribute('role')) {
            img.setAttribute('role', 'button');
        }
        img.setAttribute('aria-haspopup', 'dialog');
        if (!img.hasAttribute('title')) {
            img.setAttribute('title', 'Click to view larger illustration');
        }

        // Store metadata in `data-illus-*` attributes so story `<img>` elements share the exact
        // same data contract as `.illus-toc-zoom-btn` buttons!
        img.setAttribute('data-illus-src', src);
        img.setAttribute('data-illus-title', defaultPartTitle || 'Scene Illustration');
        img.setAttribute('data-illus-chap', defaultChapEyebrow || 'Ocean View Archives');
        img.setAttribute('data-illus-caption', alt);
        img.setAttribute('data-illus-url', bookIllustrationsUrl);
        img.setAttribute('data-illus-link-label', 'View Table of Illustrations');

        if (!storyTriggerBySrc.has(src)) {
            storyTriggerBySrc.set(src, img);
            uniqueStoryTriggers.push(img);
        }

        const canonicalTrigger = storyTriggerBySrc.get(src);
        img.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showIllustrationForTrigger(canonicalTrigger);
        });
        // Keyboard accessibility: `<a>` and `<button>` trigger on `Enter`/`Space` natively,
        // whereas `<img role="button" tabindex="0">` needs an explicit `keydown` listener!
        img.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                e.stopPropagation();
                showIllustrationForTrigger(canonicalTrigger);
            }
        });
    });

    let currentTrigger = null;

    // Return the active list of triggers—respecting any live search filter in `illustrations-toc.php`
    // (`card.style.display !== 'none'`) so `Previous` / `Next` only steps through visible search matches!
    function getVisibleTriggers() {
        if (tocZoomBtns.length > 0) {
            return tocZoomBtns.filter(function(b) {
                const card = b.closest('.illus-toc-card');
                return !card || card.style.display !== 'none';
            });
        }
        return uniqueStoryTriggers;
    }

    // Populate the `<dialog>` fields from `trigger`'s `data-illus-*` attributes and call `.showModal()`
    function showIllustrationForTrigger(trigger) {
        if (!trigger) return;
        currentTrigger = trigger;
        const src = trigger.getAttribute('data-illus-src') || '';
        const title = trigger.getAttribute('data-illus-title') || '';
        const chap = trigger.getAttribute('data-illus-chap') || '';
        const caption = trigger.getAttribute('data-illus-caption') || '';
        const url = trigger.getAttribute('data-illus-url') || '#';
        const customLinkLabel = trigger.getAttribute('data-illus-link-label') || 'Read Full Scene in Context';

        imgEl.src = src;
        imgEl.alt = caption || title;
        titleEl.textContent = title;
        chapEl.textContent = chap;
        captionEl.textContent = caption;
        if (linkEl) {
            linkEl.href = url;
        }
        if (linkLabelEl) {
            linkLabelEl.textContent = customLinkLabel;
        }

        const visibleTriggers = getVisibleTriggers();
        const idx = visibleTriggers.indexOf(trigger);
        const total = visibleTriggers.length;
        if (counterEl) {
            counterEl.textContent = (idx >= 0 ? (idx + 1) : 1) + ' / ' + Math.max(1, total);
        }
        if (prevBtn) prevBtn.disabled = total <= 1;
        if (nextBtn) nextBtn.disabled = total <= 1;

        // Open in the browser's Top Layer (traps focus & binds `Escape` automatically)
        if (!dialog.open && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
    }

    // Wrap-around modular arithmetic (`(idx + delta + length) % length`) so pressing `Previous`
    // on image #1 wraps cleanly to image #N, and `Next` on #N wraps back to #1!
    function stepIllustration(delta) {
        const visibleTriggers = getVisibleTriggers();
        if (visibleTriggers.length === 0) return;
        let idx = visibleTriggers.indexOf(currentTrigger);
        if (idx === -1) idx = 0;
        const nextIdx = (idx + delta + visibleTriggers.length) % visibleTriggers.length;
        showIllustrationForTrigger(visibleTriggers[nextIdx]);
    }

    tocZoomBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            showIllustrationForTrigger(btn);
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            stepIllustration(-1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            stepIllustration(1);
        });
    }

    // Close the modal cleanly before `stardust-spa.js` navigates to the linked scene!
    if (linkEl) {
        linkEl.addEventListener('click', function() {
            if (dialog.open) {
                dialog.close();
            }
        });
    }

    // Expose the current page's `stepIllustration` function on `window` and bind ONE global
    // `keydown` listener (`__ovaIllusLightboxKeydownBound`) so `ArrowLeft` / `ArrowRight`
    // never accumulate duplicate listeners across 20+ SPA chapter navigations!
    window.__ovaIllusLightboxStep = function(delta) {
        const activeDialog = document.getElementById('ova-illus-lightbox');
        if (!activeDialog || !activeDialog.open) return false;
        stepIllustration(delta);
        return true;
    };

    if (!window.__ovaIllusLightboxKeydownBound) {
        window.__ovaIllusLightboxKeydownBound = true;
        document.addEventListener('keydown', function(e) {
            if (typeof window.__ovaIllusLightboxStep !== 'function') return;
            const activeDialog = document.getElementById('ova-illus-lightbox');
            if (!activeDialog || !activeDialog.open) return;
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                window.__ovaIllusLightboxStep(-1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                window.__ovaIllusLightboxStep(1);
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            dialog.close();
        });
    }

    // Safari / older WebKit Fallback for `<dialog closedby="any">`:
    // When clicking the `::backdrop` outside a `<dialog>`, the browser dispatches the click event
    // with `event.target === dialog`, whereas `event.clientX` / `event.clientY` fall OUTSIDE
    // `dialog.getBoundingClientRect()`. Checking the rectangle closes the modal on backdrop clicks
    // without closing when clicking inside the dialog box!
    if (!('closedBy' in HTMLDialogElement.prototype)) {
        dialog.addEventListener('click', function(event) {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            const isDialogContent = (
                rect.top <= event.clientY &&
                event.clientY <= rect.top + rect.height &&
                rect.left <= event.clientX &&
                event.clientX <= rect.left + rect.width
            );
            if (isDialogContent) return;
            dialog.close();
        });
    }
})();
</script>
