<?php
/**
 * WordPress integration tests.
 *
 * @package ShippingRulesTester
 */

/**
 * Test the plugin in a real WordPress and WooCommerce environment.
 */
class SRT_Integration_Test extends WP_UnitTestCase {

	/**
	 * The REST route is registered only after the plugin dependencies load.
	 */
	public function test_rest_route_is_registered() {
		do_action( 'rest_api_init', rest_get_server() );
		$request  = new WP_REST_Request( 'POST', '/srt/v1/test' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertNotSame( 404, $response->get_status() );
	}

	/**
	 * Running the tester does not create orders.
	 */
	public function test_shipping_test_does_not_create_orders() {
		$before = wp_count_posts( 'shop_order' );
		$tester = new \AmazingPlugins\SRT\Shipping\Shipping_Tester();
		$result = $tester->test( array( 'country' => 'US' ) );
		$after  = wp_count_posts( 'shop_order' );

		$this->assertIsArray( $result );
		$this->assertSame( $before->publish, $after->publish );
		$this->assertSame( $before->trash, $after->trash );
	}
}
