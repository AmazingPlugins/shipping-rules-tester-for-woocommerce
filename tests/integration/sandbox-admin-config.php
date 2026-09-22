<?php
/**
 * Verify that the admin form uses WooCommerce measurement settings.
 *
 * Run with: wp eval-file tests/integration/sandbox-admin-config.php --allow-root
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

$srt_admin_config_marker = '__srt_missing_setting__';
$srt_weight_before      = get_option( 'woocommerce_weight_unit', $srt_admin_config_marker );
$srt_currency_before    = get_option( 'woocommerce_currency', $srt_admin_config_marker );

try {
	update_option( 'woocommerce_currency', 'USD' );
	foreach ( array( 'kg', 'g', 'lbs', 'oz' ) as $srt_weight_unit ) {
		update_option( 'woocommerce_weight_unit', $srt_weight_unit );
		ob_start();
		( new \AmazingPlugins\SRT\Admin\Admin() )->render_page();
		$srt_markup = ob_get_clean();

		if ( false === strpos( $srt_markup, 'Package value (USD)' ) ) {
			throw new RuntimeException( 'The form did not show the configured currency.' );
		}

		if ( false === strpos( $srt_markup, "Total package weight ({$srt_weight_unit})" ) ) {
			throw new RuntimeException( "The form did not show the {$srt_weight_unit} weight unit." );
		}
	}
} finally {
	if ( $srt_admin_config_marker === $srt_weight_before ) {
		delete_option( 'woocommerce_weight_unit' );
	} else {
		update_option( 'woocommerce_weight_unit', $srt_weight_before );
	}

	if ( $srt_admin_config_marker === $srt_currency_before ) {
		delete_option( 'woocommerce_currency' );
	} else {
		update_option( 'woocommerce_currency', $srt_currency_before );
	}
}

echo "Shipping Rules Tester admin configuration passed.\n";
