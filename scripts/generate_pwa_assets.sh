#!/bin/bash
# Architectural Block Comment:
# File: generate_pwa_assets.sh
# Purpose:
#     This shell script automates the generation of Progressive Web App (PWA) icons and iOS splash screens 
#     using the `pwa-asset-generator` npm package. It processes a single source vector graphic (SVG) 
#     into multiple rasterized resolutions required by different operating systems and devices.
# 
# Design Decisions & Future Maintenance:
#     - Dependency: Requires `npx` (Node Package eXecute) and network access to pull/run `pwa-asset-generator`.
#     - Auto-injection: Uses the `-i` flag to automatically inject the generated `<link>` and `<meta>` tags 
#       into the PHP header component, and the `-m` flag to update the web manifest. This reduces manual copy-pasting.
#     - Execution Context: The `cd` command ensures the script runs relative to the `raggiesoft-book-library` 
#       root directory, regardless of where the script was invoked from in the terminal.

# Navigate to the raggiesoft-book-library root to ensure relative paths resolve correctly.
cd "$(dirname "$0")/.." || exit

echo "Generating PWA icons and iOS splash screens..."

# Background color for the splash screens
# Replace this with the exact hex code of your dark theme background to ensure seamless visual transitions.
BG_COLOR="#0B0F19" 

# Source icon (we default to the SVG, but you can change this to a high-res PNG/JPG if you prefer the lighthouse)
SOURCE_ICON="icons/icon.svg"

# Run pwa-asset-generator
# Arguments breakdown:
# $SOURCE_ICON : The high-resolution source file.
# icons        : The output directory for the generated images.
# -b           : Background color applied behind transparent source images.
# -i           : HTML/PHP file to inject the <link> tags into.
# -m           : Manifest file to update with the new icon sizes.
npx pwa-asset-generator "$SOURCE_ICON" icons \
  -b "$BG_COLOR" \
  -i includes/components/headers/header.php \
  -m manifest.json

echo "PWA assets generated successfully!"
