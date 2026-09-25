<?php
/** Browser fixtures for a disposable sandbox only. */
defined( 'ABSPATH' ) || exit;
$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'Browser test zone' );
$zone->add_location( 'US', 'country' );
$zone->save();
$id = $zone->add_shipping_method( 'flat_rate' );
update_option( "woocommerce_flat_rate_{$id}_settings", array( 'cost' => '10', 'tax_status' => 'none' ) );
$zone->add_shipping_method( 'free_shipping' );
$zone->add_shipping_method( 'local_pickup' );
$product = new WC_Product_Simple();
$product->set_name( 'Browser sample' );
$product->set_regular_price( '15.75' );
$product->set_weight( '2.5' );
$product->save();
update_option( 'woocommerce_currency', 'USD' );
echo "Browser fixtures ready.\n";
