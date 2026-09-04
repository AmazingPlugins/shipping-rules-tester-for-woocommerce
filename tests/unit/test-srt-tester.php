<?php
/**
 * Tests for the shipping tester.
 *
 * @package ShippingRulesTester
 */

use PHPUnit\Framework\TestCase;

/**
 * Test SRT_Tester validation and calculation behavior.
 */
class Test_SRT_Tester extends TestCase {

	/**
	 * Reset shared test fixtures.
	 */
	protected function tearDown(): void {
		WC_Shipping_Zone::$methods = array();
		parent::tearDown();
	}

	/**
	 * Valid input returns a zone and calculated rate.
	 */
	public function test_valid_input_returns_calculated_rate() {
		$method = new SRT_Test_Method( 'flat_rate', 'Flat rate', array( new WC_Shipping_Rate( 12.5 ) ) );
		WC_Shipping_Zone::$methods = array( $method );
		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test(
			array(
				'country' => 'US',
				'state'   => 'ny',
				'value'   => '50.00',
				'weight'  => '2.500',
				'quantity' => '2',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'United States', $result['zone'] );
		$this->assertSame( 'matched', $result['methods'][0]['status'] );
		$this->assertSame( '$12.50', $result['methods'][0]['cost'] );
		$this->assertSame( 'flat_rate:1', $result['methods'][0]['rates'][0]['id'] );
		$this->assertSame( '$0.00', $result['methods'][0]['rates'][0]['tax'] );
		$this->assertSame( 'NY', $result['package']['state'] );
		$this->assertSame( 2, $method->last_package['contents']['srt-sample-item']['quantity'] );
		$this->assertTrue( $method->last_package['contents']['srt-sample-item']['data']->needs_shipping() );
	}

	/**
	 * Rate tax is reported separately and included in the total.
	 */
	public function test_rate_tax_is_included_in_total() {
		$method = new SRT_Test_Method( 'flat_rate', 'Flat rate', array( new WC_Shipping_Rate( 12.5, array( 1.25 ), 'flat_rate:7' ) ) );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertSame( '$12.50', $result['methods'][0]['rates'][0]['cost'] );
		$this->assertSame( '$1.25', $result['methods'][0]['rates'][0]['tax'] );
		$this->assertSame( '$13.75', $result['methods'][0]['rates'][0]['total'] );
		$this->assertSame( '$13.75', $result['methods'][0]['cost'] );
	}

	/**
	 * Non-built-in methods are reported without being called.
	 */
	public function test_external_method_is_skipped() {
		$method = new SRT_Test_Method( 'provider_rate', 'Provider rate', array( new WC_Shipping_Rate( 20 ) ) );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertSame( 'not-tested', $result['methods'][0]['status'] );
		$this->assertSame( 0, $method->calls );
	}

	/**
	 * A method exception becomes a safe row-level error.
	 */
	public function test_method_exception_is_contained() {
		WC_Shipping_Zone::$methods = array( new SRT_Test_Throwing_Method( 'flat_rate', 'Broken rate', array() ) );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertSame( 'error', $result['methods'][0]['status'] );
		$this->assertSame( 'The method reported an error while testing this package.', $result['methods'][0]['note'] );
	}

	/**
	 * Invalid country is rejected before the shipping API runs.
	 */
	public function test_invalid_country_returns_error() {
		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'XX' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Choose a valid destination country.', $result->get_error_message() );
	}

	/**
	 * Malformed numeric values are rejected instead of silently becoming zero.
	 */
	public function test_malformed_numeric_value_returns_error() {
		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test(
			array(
				'country' => 'US',
				'value'   => 'not-a-number',
				'quantity' => '1',
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Array input is rejected without a PHP warning or type coercion.
	 */
	public function test_array_numeric_value_returns_error() {
		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test(
			array(
				'country' => 'US',
				'value'   => array( '50' ),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Cart-dependent free shipping is not falsely calculated.
	 */
	public function test_cart_dependent_free_shipping_is_skipped() {
		$method = new SRT_Test_Free_Shipping_Method( 'free_shipping', 'Free shipping', array( new WC_Shipping_Rate( 0 ) ) );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertSame( 'not-tested', $result['methods'][0]['status'] );
		$this->assertSame( 0, $method->calls );
	}

	/**
	 * Quantity and decimal bounds are enforced.
	 */
	public function test_out_of_range_package_returns_error() {
		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test(
			array(
				'country' => 'US',
				'value'   => '100000.01',
				'weight'  => '0',
				'quantity' => '1',
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
