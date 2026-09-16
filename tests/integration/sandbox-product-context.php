<?php
/**
 * Verify saved product context on a real WooCommerce site.
 *
 * Run with: wp eval-file tests/integration/sandbox-product-context.php --allow-root
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$srt_product_snapshot = static function () use ( $wpdb ) {
	return array(
		'options' => $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce\\_%' ORDER BY option_name", ARRAY_A ),
		'orders'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'" ),
		'products' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'" ),
	);
};

$srt_product_before = $srt_product_snapshot();
$srt_product_id     = 0;
$srt_task_marker     = '__srt_missing_task_option__';
$srt_task_before     = get_option( 'woocommerce_task_list_tracked_completed_tasks', $srt_task_marker );

try {
	$srt_product = new WC_Product_Simple();
	$srt_product->set_name( 'SRT temporary product' );
	$srt_product->set_regular_price( '15.75' );
	$srt_product->set_price( '15.75' );
	$srt_product->set_weight( '2.5' );
	$srt_product->set_length( '10' );
	$srt_product->set_width( '20' );
	$srt_product->set_height( '30' );
	$srt_product_id = $srt_product->save();
	$srt_test_before = $srt_product_snapshot();

	$srt_result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test(
		array(
			'country'   => 'US',
			'product_id' => (string) $srt_product_id,
			'quantity'  => '3',
		)
	);

	if ( ! is_array( $srt_result ) || ! is_array( $srt_result['product'] ) ) {
		throw new RuntimeException( 'The product-aware test did not return product details.' );
	}

	if ( '47.25' !== $srt_result['package']['value'] || '7.500' !== $srt_result['package']['weight'] ) {
		throw new RuntimeException( 'The product-aware test did not calculate package totals.' );
	}

	if ( $srt_product_id !== $srt_result['product']['id'] ) {
		throw new RuntimeException( 'The product-aware test returned the wrong product.' );
	}

	if ( 15.75 !== $srt_result['product']['price'] || 2.5 !== $srt_result['product']['weight'] ) {
		throw new RuntimeException( 'The product-aware test did not return saved product measurements.' );
	}

	if ( $srt_test_before !== $srt_product_snapshot() ) {
		throw new RuntimeException( 'The product-aware test changed WooCommerce data.' );
	}
} finally {
	if ( $srt_product_id > 0 ) {
		wp_delete_post( $srt_product_id, true );
	}

	if ( $srt_task_marker === $srt_task_before ) {
		delete_option( 'woocommerce_task_list_tracked_completed_tasks' );
	} else {
		update_option( 'woocommerce_task_list_tracked_completed_tasks', $srt_task_before );
	}
}

$srt_product_after = $srt_product_snapshot();
if ( $srt_product_before !== $srt_product_after ) {
	throw new RuntimeException( 'The product-aware test changed WooCommerce data after cleanup.' );
}

echo "Shipping Rules Tester product context passed.\n";
