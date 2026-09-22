<?php
/**
 * Smoke test for the disposable WordPress sandbox.
 *
 * Run with: wp eval-file tests/integration/sandbox-smoke.php --allow-root
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$srt_fail = static function ( $message ) {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
};

$srt_options_before = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce\\_%' ORDER BY option_name", ARRAY_A );
$srt_orders_before  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'" );
$srt_products_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'" );
$srt_admin_users     = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ids',
	)
);
if ( empty( $srt_admin_users ) ) {
	$srt_fail( 'The sandbox has no administrator user for REST checks.' );
}
$srt_admin_user_id = (int) $srt_admin_users[0];
$srt_http_requests  = array();

$srt_http_callback = static function ( $pre, $args, $url ) use ( &$srt_http_requests ) {
	$srt_http_requests[] = $url;
	return $pre;
};
add_filter( 'pre_http_request', $srt_http_callback, 10, 3 );

$srt_tester = new \AmazingPlugins\SRT\Shipping\Shipping_Tester();
$srt_result = $srt_tester->test(
	array(
		'country'  => 'US',
		'postcode' => '10001',
		'value'    => '50',
		'weight'   => '2',
		'quantity' => '2',
	)
);

remove_filter( 'pre_http_request', $srt_http_callback, 10 );

if ( ! is_array( $srt_result ) || empty( $srt_result['zone'] ) || ! isset( $srt_result['methods'] ) ) {
	$srt_fail( 'The tester did not return a valid result.' );
}

if ( ! empty( $srt_http_requests ) ) {
	$srt_fail( 'The tester made an HTTP request.' );
}

$srt_options_after  = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce\\_%' ORDER BY option_name", ARRAY_A );
$srt_orders_after   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'" );
$srt_products_after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'" );

if ( $srt_options_before !== $srt_options_after || $srt_orders_before !== $srt_orders_after || $srt_products_before !== $srt_products_after ) {
	$srt_fail( 'The tester changed WooCommerce options, orders, or products.' );
}

$srt_request  = new WP_REST_Request( 'POST', '/srt/v1/test' );
$srt_response = rest_get_server()->dispatch( $srt_request );
if ( 403 !== $srt_response->get_status() ) {
	$srt_fail( 'The REST route did not reject an unauthenticated request.' );
}

wp_set_current_user( $srt_admin_user_id );
$srt_limited_users = get_users(
	array(
		'role'   => 'subscriber',
		'number' => 1,
		'fields' => 'ids',
	)
);
$srt_limited_user_created = false;
$srt_limited_user_id      = ! empty( $srt_limited_users ) ? (int) $srt_limited_users[0] : wp_insert_user(
	array(
		'user_login' => 'srt-limited-user',
		'user_pass'  => 'srt-limited-password',
		'role'       => 'subscriber',
	)
);
if ( is_wp_error( $srt_limited_user_id ) ) {
	$srt_fail( 'The limited-capability test user could not be created.' );
}
$srt_limited_user_created = empty( $srt_limited_users );
wp_set_current_user( $srt_limited_user_id );
$_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce( 'wp_rest' );
$srt_capability_request  = new WP_REST_Request( 'POST', '/srt/v1/test' );
$srt_capability_response = rest_get_server()->dispatch( $srt_capability_request );
if ( 403 !== $srt_capability_response->get_status() || 'srt_forbidden' !== $srt_capability_response->get_data()['code'] ) {
	$srt_fail( 'The REST route did not reject a user without manage_woocommerce.' );
}
if ( $srt_limited_user_created ) {
	wp_delete_user( $srt_limited_user_id );
}

wp_set_current_user( $srt_admin_user_id );
$_SERVER['HTTP_X_WP_NONCE'] = 'invalid';
$srt_nonce_request  = new WP_REST_Request( 'POST', '/srt/v1/test' );
$srt_nonce_response = rest_get_server()->dispatch( $srt_nonce_request );
if ( 403 !== $srt_nonce_response->get_status() || 'srt_invalid_nonce' !== $srt_nonce_response->get_data()['code'] ) {
	$srt_fail( 'The REST route did not reject an invalid nonce.' );
}
wp_set_current_user( 0 );
unset( $_SERVER['HTTP_X_WP_NONCE'] );

echo "Shipping Rules Tester integration smoke passed.\n";
