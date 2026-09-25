<?php
/**
 * Tax regressions. Run only in the disposable integration sandbox.
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
$options = array( 'woocommerce_calc_taxes', 'woocommerce_tax_based_on', 'woocommerce_shipping_tax_class', 'woocommerce_prices_include_tax', 'woocommerce_default_country', 'woocommerce_currency' );
$before = array();
foreach ( $options as $key ) {
	$before[ $key ] = get_option( $key );
}
$customer = WC()->customer;
$cart = WC()->cart;
$zone = new WC_Shipping_Zone();
$product = new WC_Product_Simple();
$tax_ids = array();
$reduced_class = WC_Tax::create_tax_class( 'SRT Reduced' );
try {
	update_option( 'woocommerce_calc_taxes', 'yes' );
	update_option( 'woocommerce_tax_based_on', 'shipping' );
	update_option( 'woocommerce_shipping_tax_class', 'inherit' );
	update_option( 'woocommerce_prices_include_tax', 'yes' );
	update_option( 'woocommerce_default_country', 'GB' );
	update_option( 'woocommerce_currency', 'USD' );
	foreach ( array( array( 'GB', '', '', 20 ), array( 'US', 'CA', '', 10 ), array( 'US', 'CA', 'srt-reduced', 5 ), array( 'GB', '', 'srt-reduced', 20 ) ) as $fixture ) {
		$tax_ids[] = WC_Tax::_insert_tax_rate( array(
			'tax_rate_country' => $fixture[0], 'tax_rate_state' => $fixture[1], 'tax_rate_class' => $fixture[2],
			'tax_rate' => $fixture[3], 'tax_rate_name' => 'SRT tax', 'tax_rate_shipping' => 1, 'tax_rate_priority' => 1, 'tax_rate_compound' => 0,
		) );
	}
	$zone->set_zone_name( 'Tax sample zone' );
	$zone->add_location( 'US', 'country' );
	$zone->save();
	$flat_id = $zone->add_shipping_method( 'flat_rate' );
	$pickup_id = $zone->add_shipping_method( 'local_pickup' );
	update_option( "woocommerce_flat_rate_{$flat_id}_settings", array( 'cost' => '10', 'tax_status' => 'taxable' ) );
	update_option( "woocommerce_local_pickup_{$pickup_id}_settings", array( 'cost' => '10', 'tax_status' => 'taxable' ) );
	$product->set_name( 'Tax-inclusive sample' );
	$product->set_regular_price( '120' );
	$product->set_tax_class( 'srt-reduced' );
	$product->save();
	WC()->customer = new WC_Customer();
	WC()->customer->set_shipping_country( 'GB' );
	WC()->customer->set_is_vat_exempt( true );
	WC()->cart = null;
	$sample_customer = WC()->customer;
	$tester = new \AmazingPlugins\SRT\Shipping\Shipping_Tester();
	$input = array( 'country' => 'US', 'state' => 'CA', 'postcode' => '90210', 'value' => '50' );
	$method = static function ( $result, $id ) {
		foreach ( $result['methods'] as $row ) {
			if ( $row['method'] === $id ) { return $row; }
		}
		throw new RuntimeException( 'Missing method ' . $id );
	};
	global $wpdb;
	$snapshot = static function () use ( $wpdb ) {
		return array(
			'options' => $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce_%' ORDER BY option_name", ARRAY_A ),
			'meta' => $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A ),
			'sessions' => $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_sessions ORDER BY session_id", ARRAY_A ),
		);
	};
	$stored = $snapshot();
	$result = $tester->test( $input );
	$assert( '$1.00' === $method( $result, 'flat_rate' )['rates'][0]['tax'], 'Sample shipping tax did not use CA standard rate.' );
	$assert( '$2.00' === $method( $result, 'local_pickup' )['rates'][0]['tax'], 'Pickup did not use shop-base tax.' );
	$result = $tester->test( array_merge( $input, array( 'product_id' => $product->get_id() ) ) );
	$assert( '100.00' === $result['package']['value'], 'Tax-inclusive product was not converted to net value.' );
	$assert( '$0.50' === $method( $result, 'flat_rate' )['rates'][0]['tax'], 'Saved product reduced tax class was not inherited.' );
	$assert( WC()->customer === $sample_customer && null === WC()->cart, 'Global customer/cart context changed.' );
	$assert( $stored === $snapshot(), 'Calculation persisted options, product metadata, or sessions.' );
	$mixed = $tester->test( array_merge( $input, array( 'items' => array(
		array( 'source' => 'product', 'product_id' => $product->get_id() ),
		array( 'value' => '20', 'weight' => '1' ),
	) ) ) );
	$assert( '$1.00' === $method( $mixed, 'flat_rate' )['rates'][0]['tax'], 'Standard sample item did not take precedence over reduced tax.' );
	update_option( "woocommerce_flat_rate_{$flat_id}_settings", array( 'cost' => '[fee percent="10"]', 'tax_status' => 'taxable' ) );
	$result = $tester->test( array_merge( $input, array( 'product_id' => $product->get_id() ) ) );
	$assert( '$10.00' === $method( $result, 'flat_rate' )['rates'][0]['cost'], 'Percentage shipping used the gross product price.' );
	update_option( "woocommerce_flat_rate_{$flat_id}_settings", array( 'cost' => '10', 'tax_status' => 'taxable' ) );
	update_option( 'woocommerce_tax_based_on', 'billing' );
	$result = $tester->test( $input );
	$rate = $method( $result, 'flat_rate' )['rates'][0];
	$assert( false === $rate['tax_known'] && '' === $rate['tax'] && '' === $rate['total'], 'Billing-address tax was falsely reported as known.' );
	update_option( 'woocommerce_tax_based_on', 'base' );
	$result = $tester->test( $input );
	$assert( '$2.00' === $method( $result, 'flat_rate' )['rates'][0]['tax'], 'Shop-base tax setting was ignored.' );
	update_option( 'woocommerce_tax_based_on', 'shipping' );
	$product->set_tax_status( 'none' );
	$product->save();
	$result = $tester->test( array_merge( $input, array( 'product_id' => $product->get_id() ) ) );
	$assert( '$0.00' === $method( $result, 'flat_rate' )['rates'][0]['tax'], 'Non-taxable package inherited a tax rate.' );
	$inclusive = static function () { return true; };
	add_filter( 'woocommerce_shipping_prices_include_tax', $inclusive );
	$result = $tester->test( $input );
	remove_filter( 'woocommerce_shipping_prices_include_tax', $inclusive );
	$assert( 'not-tested' === $method( $result, 'flat_rate' )['status'], 'Tax-inclusive shipping extension was not skipped.' );
} finally {
	WC()->customer = $customer;
	WC()->cart = $cart;
	$zone->delete();
	$product->delete( true );
	foreach ( $tax_ids as $id ) { WC_Tax::_delete_tax_rate( $id ); }
	if ( ! is_wp_error( $reduced_class ) ) { WC_Tax::delete_tax_class_by( 'slug', 'srt-reduced' ); }
	foreach ( $before as $key => $value ) {
		if ( false === $value ) { delete_option( $key ); } else { update_option( $key, $value ); }
	}
}
echo "Shipping Rules Tester tax context passed.\n";
