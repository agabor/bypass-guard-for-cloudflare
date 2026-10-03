#!/bin/bash
set -e

PLUGIN_SLUG="bypass-guard-for-cloudflare"
BUILD_DIR="build"
PACKAGE_DIR="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_NAME="$PLUGIN_SLUG.zip"

rm -rf "$BUILD_DIR"
rm -f "$ZIP_NAME"

mkdir -p "$PACKAGE_DIR"

rsync -av \
  --exclude='.*' \
  --exclude='build' \
  --exclude='*.zip' \
  --exclude='pack.sh' \
  ./ "$PACKAGE_DIR/"

cd "$BUILD_DIR"
zip -r "../$ZIP_NAME" "$PLUGIN_SLUG"
cd ..

rm -rf "$BUILD_DIR"

echo "Package created: $ZIP_NAME"
