#!/usr/bin/env sh

set -eu

PLUGIN_DIR=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
VERSION=$(sed -n 's/^ \* Version: *//p' "$PLUGIN_DIR/shipping-rules-tester-for-woocommerce.php" | head -n 1)
OUTPUT=${1:-"$PLUGIN_DIR/dist/shipping-rules-tester-for-woocommerce-$VERSION.zip"}
BUILD_DIR=$(mktemp -d "${TMPDIR:-/tmp}/srt-release.XXXXXX")
PACKAGE_DIR="$BUILD_DIR/shipping-rules-tester-for-woocommerce"

cleanup() {
	rm -rf "$BUILD_DIR"
}

trap cleanup EXIT INT TERM

mkdir -p "$PACKAGE_DIR" "$(dirname "$OUTPUT")"
rsync -a --delete --exclude-from="$PLUGIN_DIR/.distignore" "$PLUGIN_DIR/" "$PACKAGE_DIR/"

( cd "$BUILD_DIR" && zip -qr "$OUTPUT" shipping-rules-tester-for-woocommerce )
unzip -t "$OUTPUT" >/dev/null

echo "Built $OUTPUT"
