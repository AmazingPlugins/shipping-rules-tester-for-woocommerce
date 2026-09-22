<?php
/**
 * WordPress integration bootstrap.
 *
 * @package ShippingRulesTester
 */

$tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $tests_dir || ! is_dir( $tests_dir ) ) {
	fwrite( STDERR, "WP_TESTS_DIR is required for integration tests.\n" );
	exit( 2 );
}

require_once $tests_dir . '/includes/functions.php';

/**
 * Load WooCommerce and the plugin into the WordPress test suite.
 */
function srt_load_plugins() {
	$woocommerce_file = getenv( 'WOOCOMMERCE_PLUGIN_FILE' );
	if ( $woocommerce_file && file_exists( $woocommerce_file ) ) {
		require_once $woocommerce_file;
	}

	require_once dirname( __DIR__, 2 ) . '/shipping-rules-tester-for-woocommerce.php';
}

tests_add_filter( 'muplugins_loaded', 'srt_load_plugins' );
require_once $tests_dir . '/includes/bootstrap.php';
