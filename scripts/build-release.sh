#!/usr/bin/env sh

set -eu

PLUGIN_DIR=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
VERSION=$(sed -n 's/^ \* Version: *//p' "$PLUGIN_DIR/shipping-rules-tester-for-woocommerce.php" | head -n 1)
SLUG=ap-shipping-rules-tester-for-woocommerce
OUTPUT=${1:-"$PLUGIN_DIR/dist/$SLUG-$VERSION.zip"}
BUILD_DIR=$(mktemp -d "${TMPDIR:-/tmp}/srt-release.XXXXXX")
PACKAGE_DIR="$BUILD_DIR/$SLUG"

cleanup() {
	rm -rf "$BUILD_DIR"
}

trap cleanup EXIT INT TERM

mkdir -p "$PACKAGE_DIR" "$(dirname "$OUTPUT")"
rsync -a --delete --exclude-from="$PLUGIN_DIR/.distignore" "$PLUGIN_DIR/" "$PACKAGE_DIR/"

# Always create a fresh archive so removed files cannot survive a rebuild.
( cd "$BUILD_DIR" && zip -qr release.zip "$SLUG" )
unzip -t "$BUILD_DIR/release.zip" >/dev/null
mv "$BUILD_DIR/release.zip" "$OUTPUT"

echo "Built $OUTPUT"
