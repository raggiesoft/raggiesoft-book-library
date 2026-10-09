#!/bin/bash

# Navigate to the raggiesoft-book-library root
cd "$(dirname "$0")/.." || exit

echo "Generating PWA icons and iOS splash screens..."

# Background color for the splash screens
# Replace this with the exact hex code of your dark theme background
BG_COLOR="#0B0F19" 

# Source icon (we default to the SVG, but you can change this to a high-res PNG/JPG if you prefer the lighthouse)
SOURCE_ICON="icons/icon.svg"

# Run pwa-asset-generator
# -b: Background color
# -i: HTML file to inject the <link> tags into
# -m: Manifest file to update
npx pwa-asset-generator "$SOURCE_ICON" icons \
  -b "$BG_COLOR" \
  -i includes/components/headers/header.php \
  -m manifest.json

echo "PWA assets generated successfully!"

