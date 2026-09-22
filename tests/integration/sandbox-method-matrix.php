<?php
/**
 * Exercise local WooCommerce methods in a temporary shipping zone.
 *
 * Run with: wp eval-file tests/integration/sandbox-method-matrix.php --allow-root
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$srt_matrix_snapshot = static function () use ( $wpdb ) {
	return array(
		'options'  => $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce\\_%' ORDER BY option_name", ARRAY_A ),
		'orders'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'" ),
		'products' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'" ),
	);
};

$srt_matrix_assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

$srt_before = $srt_matrix_snapshot();
$srt_zone   = new WC_Shipping_Zone();
$srt_zone->set_zone_name( 'SRT temporary matrix zone' );
$srt_zone_id = $srt_zone->save();
$srt_zone->add_location( 'AQ', 'country' );
$srt_zone->add_location( '00000', 'postcode' );
$srt_zone->save();

try {
	$srt_zone->add_shipping_method( 'flat_rate' );
	$srt_zone->add_shipping_method( 'local_pickup' );
	$srt_zone->add_shipping_method( 'free_shipping' );

	$srt_result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test(
		array(
			'country'  => 'AQ',
			'postcode' => '00000',
			'value'    => '50',
			'weight'   => '2',
			'quantity' => '2',
		)
	);

	$srt_matrix_assert( is_array( $srt_result ), 'The tester did not return an array.' );
	$srt_matrix_assert( 'SRT temporary matrix zone' === $srt_result['zone'], 'The temporary zone did not match.' );
	$srt_matrix_assert( false === $srt_result['fallback'], 'The temporary zone was incorrectly marked as fallback.' );

	$srt_methods = array();
	foreach ( $srt_result['methods'] as $srt_method ) {
		$srt_methods[ $srt_method['method'] ] = $srt_method;
	}

	foreach ( array( 'flat_rate', 'local_pickup', 'free_shipping' ) as $srt_method_id ) {
		$srt_matrix_assert( isset( $srt_methods[ $srt_method_id ] ), "Missing {$srt_method_id} result." );
		$srt_matrix_assert( 'matched' === $srt_methods[ $srt_method_id ]['status'], "{$srt_method_id} did not return a local rate." );
	}
} finally {
	( new WC_Shipping_Zone( $srt_zone_id ) )->delete();
}

$srt_after = $srt_matrix_snapshot();
if ( $srt_before !== $srt_after ) {
	throw new RuntimeException( 'The temporary matrix changed WooCommerce data after cleanup.' );
}

foreach ( WC_Shipping_Zones::get_zones() as $srt_remaining_zone ) {
	if ( 'SRT temporary matrix zone' === $srt_remaining_zone['zone_name'] ) {
		throw new RuntimeException( 'The temporary matrix zone was not removed.' );
	}
}

echo "Shipping Rules Tester local method matrix passed.\n";
