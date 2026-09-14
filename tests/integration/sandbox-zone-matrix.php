<?php
/**
 * Exercise real WooCommerce zone precedence and fallback matching.
 *
 * Run with: wp eval-file tests/integration/sandbox-zone-matrix.php --allow-root
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$srt_zone_snapshot = static function () use ( $wpdb ) {
	return array(
		'options'  => $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce\\_%' ORDER BY option_name", ARRAY_A ),
		'orders'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'" ),
		'products' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'" ),
	);
};

$srt_zone_assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

$srt_before = $srt_zone_snapshot();
$srt_zones  = array();

try {
	$srt_zone_definitions = array(
		array( 'name' => 'SRT postcode zone', 'locations' => array( array( '10001', 'postcode' ) ) ),
		array( 'name' => 'SRT state zone', 'locations' => array( array( 'US:NY', 'state' ) ) ),
		array( 'name' => 'SRT country zone', 'locations' => array( array( 'US', 'country' ) ) ),
	);

	foreach ( $srt_zone_definitions as $srt_definition ) {
		$srt_zone = new WC_Shipping_Zone();
		$srt_zone->set_zone_name( $srt_definition['name'] );
		$srt_zone_id = $srt_zone->save();
		foreach ( $srt_definition['locations'] as $srt_location ) {
			$srt_zone->add_location( $srt_location[0], $srt_location[1] );
		}
		$srt_zone->save();
		$srt_zones[] = $srt_zone_id;
	}

	$srt_tester = new \AmazingPlugins\SRT\Shipping\Shipping_Tester();
	$srt_cases  = array(
		array( 'country' => 'US', 'state' => 'NY', 'postcode' => '10001', 'zone' => 'SRT postcode zone', 'fallback' => false ),
		array( 'country' => 'US', 'state' => 'NY', 'postcode' => '10002', 'zone' => 'SRT state zone', 'fallback' => false ),
		array( 'country' => 'US', 'state' => 'CA', 'postcode' => '90001', 'zone' => 'SRT country zone', 'fallback' => false ),
		array( 'country' => 'GB', 'state' => '', 'postcode' => 'SW1A 1AA', 'zone' => 'Locations not covered by your other zones', 'fallback' => true ),
	);

	foreach ( $srt_cases as $srt_case ) {
		$srt_result = $srt_tester->test( $srt_case );
		$srt_zone_assert( is_array( $srt_result ), 'The zone test did not return an array.' );
		$srt_zone_assert( $srt_case['zone'] === $srt_result['zone'], "Unexpected zone for {$srt_case['postcode']}." );
		$srt_zone_assert( $srt_case['fallback'] === $srt_result['fallback'], "Unexpected fallback flag for {$srt_case['postcode']}." );
	}
} finally {
	foreach ( $srt_zones as $srt_zone_id ) {
		( new WC_Shipping_Zone( $srt_zone_id ) )->delete();
	}
}

$srt_after = $srt_zone_snapshot();
if ( $srt_before !== $srt_after ) {
	throw new RuntimeException( 'The zone matrix changed WooCommerce data after cleanup.' );
}

echo "Shipping Rules Tester zone matrix passed.\n";
