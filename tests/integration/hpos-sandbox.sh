#!/usr/bin/env sh

set -eu

SOURCE_DIR=$(CDPATH= cd -- "$(dirname "$0")/../.." && pwd)
PLUGIN_DIR=$(CDPATH= cd -- "${SRT_PLUGIN_DIR:-$SOURCE_DIR}" && pwd)
TEST_DIR=$(CDPATH= cd -- "${SRT_TEST_DIR:-$SOURCE_DIR}" && pwd)
SUFFIX="srt-hpos-$$"
NETWORK="$SUFFIX-network"
DB_CONTAINER="$SUFFIX-db"
WP_CONTAINER="$SUFFIX-wordpress"
WP_IMAGE=${SRT_WP_IMAGE:-wordpress:latest}
HPOS_MODE=${SRT_HPOS_MODE:-yes}
WC_VERSION=${SRT_WC_VERSION:-}
RUN_BROWSER=${SRT_RUN_BROWSER:-no}
ADMIN_PASSWORD=$(openssl rand -hex 18)

case "$HPOS_MODE" in
	yes|no) ;;
	*)
		echo 'SRT_HPOS_MODE must be yes or no.' >&2
		exit 2
		;;
esac

cleanup() {
	docker rm -f "$WP_CONTAINER" "$DB_CONTAINER" >/dev/null 2>&1 || true
	docker network rm "$NETWORK" >/dev/null 2>&1 || true
}

trap cleanup EXIT INT TERM

docker network create "$NETWORK" >/dev/null
docker run -d \
	--name "$DB_CONTAINER" \
	--network "$NETWORK" \
	--network-alias db \
	-e MYSQL_ROOT_PASSWORD=rootpass \
	-e MYSQL_DATABASE=wordpress \
	-e MYSQL_USER=wpuser \
	-e MYSQL_PASSWORD=wppass \
	mysql:8.0 >/dev/null

until docker exec "$DB_CONTAINER" mysqladmin ping -h 127.0.0.1 -uroot -prootpass --silent >/dev/null 2>&1; do
	sleep 2
done

set --
if [ "$RUN_BROWSER" = yes ]; then
	set -- -p 127.0.0.1::80
fi
docker run -d "$@" \
	--name "$WP_CONTAINER" \
	--network "$NETWORK" \
	--network-alias wordpress \
	-e WORDPRESS_DB_HOST=db:3306 \
	-e WORDPRESS_DB_USER=wpuser \
	-e WORDPRESS_DB_PASSWORD=wppass \
	-e WORDPRESS_DB_NAME=wordpress \
	-v "$PLUGIN_DIR:/var/www/html/wp-content/plugins/shipping-rules-tester-for-woocommerce:ro" \
	-v "$TEST_DIR/tests:/tmp/srt-tests:ro" \
	"$WP_IMAGE" >/dev/null

until docker exec "$WP_CONTAINER" sh -c 'curl -fsS http://localhost >/dev/null' >/dev/null 2>&1; do
	sleep 2
done

docker exec "$WP_CONTAINER" sh -c 'curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp && chmod +x /usr/local/bin/wp'

docker exec "$WP_CONTAINER" wp core install \
	--url=http://wordpress \
	--title='Shipping Rules Tester HPOS sandbox' \
	--admin_user=srt-admin \
	--admin_password="$ADMIN_PASSWORD" \
	--admin_email=srt@example.test \
	--skip-email \
	--allow-root >/dev/null
if [ -n "$WC_VERSION" ]; then
	docker exec "$WP_CONTAINER" wp plugin install woocommerce --version="$WC_VERSION" --activate --allow-root >/dev/null
else
	docker exec "$WP_CONTAINER" wp plugin install woocommerce --activate --allow-root >/dev/null
fi
docker exec "$WP_CONTAINER" wp plugin activate shipping-rules-tester-for-woocommerce --allow-root >/dev/null
docker exec "$WP_CONTAINER" wp option update woocommerce_custom_orders_table_enabled "$HPOS_MODE" --allow-root >/dev/null

docker exec "$WP_CONTAINER" wp eval-file \
	/tmp/srt-tests/integration/sandbox-smoke.php \
	--allow-root
docker exec "$WP_CONTAINER" wp eval-file \
	/tmp/srt-tests/integration/sandbox-method-matrix.php \
	--allow-root
docker exec "$WP_CONTAINER" wp eval-file \
	/tmp/srt-tests/integration/sandbox-zone-matrix.php \
	--allow-root
docker exec "$WP_CONTAINER" wp eval-file \
	/tmp/srt-tests/integration/sandbox-product-context.php \
	--allow-root
docker exec "$WP_CONTAINER" wp eval-file \
	/tmp/srt-tests/integration/sandbox-admin-config.php \
	--allow-root
docker exec "$WP_CONTAINER" wp eval-file \
	/tmp/srt-tests/integration/sandbox-tax-context.php \
	--allow-root
docker exec "$WP_CONTAINER" wp eval-file \
	/tmp/srt-tests/integration/sandbox-catalog-search.php \
	--allow-root

if [ "$HPOS_MODE" != "$(docker exec "$WP_CONTAINER" wp option get woocommerce_custom_orders_table_enabled --allow-root)" ]; then
	echo "FAIL: WooCommerce order storage mode was not set to $HPOS_MODE." >&2
	exit 1
fi

echo "WooCommerce order storage sandbox passed ($HPOS_MODE)."

if [ "$RUN_BROWSER" = yes ]; then
	PORT=$(docker port "$WP_CONTAINER" 80/tcp | head -n 1 | sed 's/.*://')
	SRT_TEST_URL="http://127.0.0.1:$PORT"
	export SRT_TEST_URL
	export SRT_ADMIN_USER=srt-admin
	export SRT_ADMIN_PASSWORD="$ADMIN_PASSWORD"
	docker exec "$WP_CONTAINER" wp option update home "$SRT_TEST_URL" --allow-root >/dev/null
	docker exec "$WP_CONTAINER" wp option update siteurl "$SRT_TEST_URL" --allow-root >/dev/null
	docker exec "$WP_CONTAINER" wp eval-file /tmp/srt-tests/integration/sandbox-browser-fixtures.php --allow-root
	(cd "$SOURCE_DIR" && npm run test:e2e -- --workers=1)
fi
