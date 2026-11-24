#!/bin/bash
# ============================================================================
# PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
# ============================================================================
# File: scripts/generate_pwa_assets.sh
# App:  RaggieSoft Book Reader (`https://books.raggiesoft.com`)
#
# WHAT IS THIS SCRIPT & WHY DOES IT EXIST?
#   When a reader installs RaggieSoft Book Reader to their phone, tablet, or
#   desktop Home Screen as a Progressive Web App (PWA), different operating
#   systems demand dozens of exact icon and startup-screen image files:
#     - Browser tabs want `favicon.ico` (16x16, 32x32, 48x48) and `icon.svg`.
#     - Android / Chrome / Windows PWAs want `192x192` and `512x512` icons in
#       both standard rounded-squircle ("any") and full-bleed padded ("maskable")
#       formats so OS icon masks never chop off the book artwork.
#     - iOS & iPadOS Safari want an `apple-icon-180.png` Home Screen icon PLUS
#       **92 exact pixel-resolution startup splash screens** (23 iPhone/iPad
#       screen sizes x 2 orientations [Portrait + Landscape] x 2 color modes
#       [Light Mode `#FAF8F5` + Dark Mode `#0B0F19`]) linked in `header.php`!
#
#   Creating 99 image files by hand in Photoshop every time an icon changes
#   would take hours. Running this single Bash + Python (`Pillow`) build script
#   generates all 99 files deterministically in seconds with zero network calls!
#
# WHAT FEEDS INTO IT?
#   Two master high-resolution source PNGs in `raggiesoft-assets/raggiesoft-books/images/logos/`:
#     1. `book-reader-app-icon.png` (the glowing open hardcover book over waves)
#     2. `oceanview-archives.png`   (the Ocean View Archives keyhole-compass crest)
#
# WHAT DOES IT PRODUCE?
#   - Writes all 92 `apple-splash-*.jpg` files + master icons into the CDN folder
#     `raggiesoft-assets/raggiesoft-books/images/pwa-icons/`.
#   - Copies the local `/icons/*` and `/favicon.ico` files into `subdomains/books/`
#     so same-origin PWA manifest and favicon requests resolve immediately.
#
# NOTE ON LINE 1 (`#!/bin/bash`):
#   In Unix/macOS shell scripts, `#!/bin/bash` on Line 1 is called a "shebang"—
#   it tells the operating system to run this file using the Bash shell interpreter.
# ============================================================================

# `set -e` tells Bash: "Stop immediately with a non-zero error code if any command fails."
set -e

# Change working directory (`cd`) to the root of `subdomains/books` (one folder above `scripts/`)
# regardless of which directory the developer ran `./scripts/generate_pwa_assets.sh` from.
cd "$(dirname "$0")/.." || exit

echo "Generating RaggieSoft Book Reader PWA icons and 92 Light/Dark Co-Branded iOS splash screens..."

# Run an inline Python 3 script using a "heredoc" (`<< 'EOF' ... EOF`).
# Everything between `<< 'EOF'` and `EOF` is fed directly into Python 3!
/opt/homebrew/bin/python3 - << 'EOF'
import os, io, base64
from PIL import Image, ImageDraw, ImageFilter, ImageFont

# Resolve paths to `subdomains/books`, the CDN `pwa-icons` output folder, and master `logos` folder
books_root = os.getcwd()
cdn_pwa_dir = os.path.abspath(os.path.join(books_root, "../../../raggiesoft-assets/raggiesoft-books/images/pwa-icons"))
logos_dir = os.path.abspath(os.path.join(books_root, "../../../raggiesoft-assets/raggiesoft-books/images/logos"))

os.makedirs(cdn_pwa_dir, exist_ok=True)

# Load the two master artwork PNGs in 32-bit RGBA (Red, Green, Blue, Alpha transparency) mode
app_icon = Image.open(os.path.join(logos_dir, "book-reader-app-icon.png")).convert("RGBA")
imprint_crest = Image.open(os.path.join(logos_dir, "oceanview-archives.png")).convert("RGBA")

# Color palette constants (RGB tuples `(R, G, B)` where each channel is `0..255`)
ICON_EDGE_BG = (11, 20, 42)     # Deep midnight sapphire matching the master icon edge (for maskable safe-zone padding)
DARK_BG = (11, 15, 25)          # #0B0F19 (Book Reader Dark Mode splash background)
LIGHT_BG = (250, 248, 245)      # #FAF8F5 (Archival Paper Light Mode splash background)

DARK_TEXT_PRIMARY = (248, 250, 252)   # #F8FAFC (crisp white-slate title on Dark Mode)
DARK_TEXT_ACCENT = (227, 178, 124)    # #E3B27C (Ocean View Bronze subtitle on Dark Mode)
DARK_TEXT_MUTED = (148, 163, 184)     # #94A3B8 (muted attribution line on Dark Mode)

LIGHT_TEXT_PRIMARY = (15, 23, 42)     # #0F172A (deep navy ink title on Light Mode)
LIGHT_TEXT_ACCENT = (15, 76, 92)      # #0F4C5C (Ocean View Coastal Teal subtitle on Light Mode)
LIGHT_TEXT_MUTED = (71, 85, 105)      # #475569 (muted attribution line on Light Mode)

# Try macOS system fonts in priority order; fall back safely to Pillow's built-in bitmap font if missing
def load_font(candidates, size):
    for path, idx in candidates:
        if os.path.exists(path):
            try:
                return ImageFont.truetype(path, size=size, index=idx)
            except Exception:
                pass
    return ImageFont.load_default()

SERIF_BOLD_CANDIDATES = [
    ("/System/Library/Fonts/Supplemental/Georgia Bold.ttf", 0),
    ("/System/Library/Fonts/NewYork.ttf", 0),
    ("/System/Library/Fonts/Helvetica.ttc", 1),
]
SANS_BOLD_CANDIDATES = [
    ("/System/Library/Fonts/Helvetica.ttc", 1),
    ("/System/Library/Fonts/SFNS.ttf", 0),
]
SANS_REG_CANDIDATES = [
    ("/System/Library/Fonts/Helvetica.ttc", 0),
    ("/System/Library/Fonts/SFNS.ttf", 0),
]

def make_rounded_squircle(img_rgba, size, radius_ratio=0.225, border_rgba=None):
    """
    Creates a smooth rounded-rectangle ('squircle') icon with optional border.
    Draws at 2x resolution (`ss = size * 2`) and downscales with Lanczos resampling
    ('2x supersampling') so corner curves look silky-smooth with zero jagged pixel stairs!
    """
    ss = size * 2
    resized = img_rgba.resize((ss, ss), Image.Resampling.LANCZOS)
    mask = Image.new("L", (ss, ss), 0)
    draw = ImageDraw.Draw(mask)
    r = int(round(ss * radius_ratio))
    draw.rounded_rectangle((0, 0, ss - 1, ss - 1), radius=r, fill=255)

    out = Image.new("RGBA", (ss, ss), (0, 0, 0, 0))
    out.paste(resized, (0, 0), mask)

    if border_rgba is not None:
        bdraw = ImageDraw.Draw(out)
        bw = max(2, int(round(ss * 0.012)))
        bdraw.rounded_rectangle((bw // 2, bw // 2, ss - 1 - bw // 2, ss - 1 - bw // 2), radius=r, outline=border_rgba, width=bw)

    return out.resize((size, size), Image.Resampling.LANCZOS)

def make_maskable_icon(img_rgba, size, safe_padding_ratio=0.10):
    """
    Creates a full-bleed W3C 'maskable' icon with 10% safe-zone padding around all edges
    so Android launchers (circle, teardrop, squircle) never clip the open book emblem.
    """
    canvas = Image.new("RGB", (size, size), ICON_EDGE_BG)
    inner = int(round(size * (1.0 - 2.0 * safe_padding_ratio)))
    resized = img_rgba.resize((inner, inner), Image.Resampling.LANCZOS)
    offset = (size - inner) // 2
    canvas.paste(resized, (offset, offset), resized)
    return canvas

# 1. Generate SVG Favicon (embeds a base64-encoded 128x128 PNG squircle inside `<svg>`)
fav_png = make_rounded_squircle(app_icon, 128, radius_ratio=0.22, border_rgba=(227, 178, 124, 140))
buf = io.BytesIO()
fav_png.save(buf, format="PNG", optimize=True)
b64_fav = base64.b64encode(buf.getvalue()).decode("ascii")

svg_favicon = f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128">
  <title>RaggieSoft Book Reader — Ocean View Archives</title>
  <image width="128" height="128" href="data:image/png;base64,{b64_fav}" />
</svg>
"""
with open(os.path.join(cdn_pwa_dir, "icon.svg"), "w") as f:
    f.write(svg_favicon)

# 2. Generate Multi-Resolution `favicon.ico` bundling 16x16, 32x32, and 48x48 frames in one `.ico` file
ico_base = make_rounded_squircle(app_icon, 256, radius_ratio=0.22, border_rgba=(227, 178, 124, 160))
ico_base.save(os.path.join(books_root, "favicon.ico"), format="ICO", sizes=[(16, 16), (32, 32), (48, 48)])
ico_base.save(os.path.join(cdn_pwa_dir, "favicon.ico"), format="ICO", sizes=[(16, 16), (32, 32), (48, 48)])

# 3. Generate iOS Home Screen Icon (`180x180` opaque RGB; iOS Safari applies Apple's hardware squircle mask automatically)
apple_180 = app_icon.resize((180, 180), Image.Resampling.LANCZOS).convert("RGB")
apple_180.save(os.path.join(cdn_pwa_dir, "apple-icon-180.png"), format="PNG", optimize=True)

# 4. Generate Standard ("any") & Maskable ("maskable") Web App Manifest Icons (`192x192` & `512x512`)
icon_192_any = make_rounded_squircle(app_icon, 192, radius_ratio=0.22, border_rgba=(227, 178, 124, 120))
icon_512_any = make_rounded_squircle(app_icon, 512, radius_ratio=0.22, border_rgba=(227, 178, 124, 120))
icon_192_any.save(os.path.join(cdn_pwa_dir, "icon-192.png"), format="PNG", optimize=True)
icon_512_any.save(os.path.join(cdn_pwa_dir, "icon-512.png"), format="PNG", optimize=True)

icon_192_mask = make_maskable_icon(app_icon, 192, safe_padding_ratio=0.10)
icon_512_mask = make_maskable_icon(app_icon, 512, safe_padding_ratio=0.10)
icon_192_mask.save(os.path.join(cdn_pwa_dir, "manifest-icon-192.maskable.png"), format="PNG", optimize=True)
icon_512_mask.save(os.path.join(cdn_pwa_dir, "manifest-icon-512.maskable.png"), format="PNG", optimize=True)

# 5. Generate All 46 iOS / iPadOS Startup Splash Screen Resolutions (46 Light + 46 Dark = 92 JPEG files)
#    Every `(width, height)` pair matches a `<link rel="apple-touch-startup-image" ...>` tag in `header.php`.
splash_resolutions = [
    (2048, 2732), (2732, 2048),
    (2064, 2752), (2752, 2064),
    (1668, 2420), (2420, 1668),
    (1668, 2388), (2388, 1668),
    (1668, 2224), (2224, 1668),
    (1536, 2048), (2048, 1536),
    (1640, 2360), (2360, 1640),
    (1620, 2160), (2160, 1620),
    (1488, 2266), (2266, 1488),
    (1320, 2868), (2868, 1320),
    (1206, 2622), (2622, 1206),
    (1260, 2736), (2736, 1260),
    (1290, 2796), (2796, 1290),
    (1179, 2556), (2556, 1179),
    (1170, 2532), (2532, 1170),
    (1284, 2778), (2778, 1284),
    (1080, 2340), (2340, 1080),
    (1242, 2688), (2688, 1242),
    (1125, 2436), (2436, 1125),
    (828, 1792),  (1792, 828),
    (1242, 2208), (2208, 1242),
    (750, 1334),  (1334, 750),
    (640, 1136),  (1136, 640),
]

def make_cobranded_splash(w, h, is_dark):
    """
    Composites a single Co-Branded ('App + Imprint') launch screen at `w x h` pixels:
      - Center hero: RaggieSoft Book Reader rounded squircle icon with Gaussian drop shadow,
        app title, and 'STARDUST ENGINE LITERARY ARCHIVE' subtitle.
      - Bottom lockup: Ocean View Archives keyhole-compass crest + imprint attribution.
    """
    bg_rgb = DARK_BG if is_dark else LIGHT_BG
    text_primary = DARK_TEXT_PRIMARY if is_dark else LIGHT_TEXT_PRIMARY
    text_accent = DARK_TEXT_ACCENT if is_dark else LIGHT_TEXT_ACCENT
    text_muted = DARK_TEXT_MUTED if is_dark else LIGHT_TEXT_MUTED
    border_rgba = (227, 178, 124, 150) if is_dark else (15, 76, 92, 95)

    canvas = Image.new("RGBA", (w, h), bg_rgb + (255,))
    short_side = min(w, h)
    is_landscape = w > h

    # Center Hero App Icon size (proportional to the shorter screen edge so tablets & phones scale evenly)
    icon_dim = int(round(short_side * (0.24 if is_landscape else 0.28)))
    squircle = make_rounded_squircle(app_icon, icon_dim, radius_ratio=0.225, border_rgba=border_rgba)

    # Soft Gaussian-blurred elevation drop shadow behind the squircle
    shadow_pad = int(round(icon_dim * 0.18))
    shadow = Image.new("RGBA", (icon_dim + shadow_pad * 2, icon_dim + shadow_pad * 2), (0, 0, 0, 0))
    sdraw = ImageDraw.Draw(shadow)
    sr = int(round(icon_dim * 0.225))
    shadow_alpha = 135 if is_dark else 65
    sdraw.rounded_rectangle(
        (shadow_pad, shadow_pad, shadow_pad + icon_dim - 1, shadow_pad + icon_dim - 1),
        radius=sr,
        fill=(0, 0, 0, shadow_alpha),
    )
    shadow = shadow.filter(ImageFilter.GaussianBlur(radius=max(6, int(round(icon_dim * 0.045)))))

    center_x = w // 2
    icon_y = int(round(h * (0.22 if is_landscape else 0.27))) - (icon_dim // 2)
    shadow_offset_y = max(4, int(round(icon_dim * 0.03)))

    canvas.alpha_composite(shadow, (center_x - icon_dim // 2 - shadow_pad, icon_y - shadow_pad + shadow_offset_y))
    canvas.alpha_composite(squircle, (center_x - icon_dim // 2, icon_y))

    draw = ImageDraw.Draw(canvas)

    # Typography sizes scaled dynamically to `short_side`
    title_sz = max(24, int(round(short_side * (0.044 if is_landscape else 0.050))))
    sub_sz = max(13, int(round(short_side * (0.019 if is_landscape else 0.021))))
    imp_title_sz = max(14, int(round(short_side * (0.020 if is_landscape else 0.022))))
    imp_sub_sz = max(11, int(round(short_side * (0.015 if is_landscape else 0.0165))))

    font_title = load_font(SERIF_BOLD_CANDIDATES, title_sz)
    font_sub = load_font(SANS_BOLD_CANDIDATES, sub_sz)
    font_imp_title = load_font(SANS_BOLD_CANDIDATES, imp_title_sz)
    font_imp_sub = load_font(SANS_REG_CANDIDATES, imp_sub_sz)

    # Horizontally centered App Title + Subtitle below the squircle
    title_text = "RaggieSoft Book Reader"
    sub_text = "STARDUST ENGINE LITERARY ARCHIVE"

    title_y = icon_y + icon_dim + int(round(short_side * 0.042))
    tb = draw.textbbox((0, 0), title_text, font=font_title)
    tw = tb[2] - tb[0]
    draw.text((center_x - tw // 2, title_y), title_text, font=font_title, fill=text_primary)

    sub_y = title_y + (tb[3] - tb[1]) + int(round(short_side * 0.018))
    sb = draw.textbbox((0, 0), sub_text, font=font_sub)
    sw = sb[2] - sb[0]
    draw.text((center_x - sw // 2, sub_y), sub_text, font=font_sub, fill=text_accent)

    # Bottom Co-Branded Imprint Lockup (Ocean View Archives Crest + Label)
    crest_dim = max(36, int(round(short_side * (0.068 if is_landscape else 0.078))))
    crest_resized = imprint_crest.resize((crest_dim, crest_dim), Image.Resampling.LANCZOS)

    imp_line1 = "OCEAN VIEW ARCHIVES"
    imp_line2 = "Official Literary Imprint of RaggieSoft Media"
    ib1 = draw.textbbox((0, 0), imp_line1, font=font_imp_title)
    ib2 = draw.textbbox((0, 0), imp_line2, font=font_imp_sub)
    iw1, ih1 = ib1[2] - ib1[0], ib1[3] - ib1[1]
    iw2, ih2 = ib2[2] - ib2[0], ib2[3] - ib2[1]

    bottom_margin = int(round(h * (0.085 if is_landscape else 0.075)))
    imp_sub_y = h - bottom_margin - ih2
    imp_title_y = imp_sub_y - int(round(short_side * 0.008)) - ih1
    crest_y = imp_title_y - int(round(short_side * 0.018)) - crest_dim

    canvas.alpha_composite(crest_resized, (center_x - crest_dim // 2, crest_y))
    draw.text((center_x - iw1 // 2, imp_title_y), imp_line1, font=font_imp_title, fill=text_accent)
    draw.text((center_x - iw2 // 2, imp_sub_y), imp_line2, font=font_imp_sub, fill=text_muted)

    return canvas.convert("RGB")

# Render and save both Light Mode and Dark Mode JPEGs for every device resolution
for (w, h) in splash_resolutions:
    light_img = make_cobranded_splash(w, h, is_dark=False)
    light_img.save(os.path.join(cdn_pwa_dir, f"apple-splash-{w}-{h}.jpg"), format="JPEG", quality=92, optimize=True)

    dark_img = make_cobranded_splash(w, h, is_dark=True)
    dark_img.save(os.path.join(cdn_pwa_dir, f"apple-splash-dark-{w}-{h}.jpg"), format="JPEG", quality=92, optimize=True)

print(f"Generated {len(splash_resolutions)} Light and {len(splash_resolutions)} Dark Book Reader splash screens in {cdn_pwa_dir}")
EOF

# Mirror the core Home Screen / Manifest icons into `subdomains/books/icons/`
# because Web App Manifests (`manifest.json`) work most reliably with same-origin icon paths.
mkdir -p icons
cp "../../../raggiesoft-assets/raggiesoft-books/images/pwa-icons/icon-192.png" \
   "../../../raggiesoft-assets/raggiesoft-books/images/pwa-icons/icon-512.png" \
   "../../../raggiesoft-assets/raggiesoft-books/images/pwa-icons/manifest-icon-192.maskable.png" \
   "../../../raggiesoft-assets/raggiesoft-books/images/pwa-icons/manifest-icon-512.maskable.png" \
   "../../../raggiesoft-assets/raggiesoft-books/images/pwa-icons/apple-icon-180.png" \
   "../../../raggiesoft-assets/raggiesoft-books/images/pwa-icons/icon.svg" \
   icons/

echo "Book Reader PWA assets generated successfully!"
