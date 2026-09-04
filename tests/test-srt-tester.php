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
		$this->assertSame( 'NY', $result['package']['state'] );
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
