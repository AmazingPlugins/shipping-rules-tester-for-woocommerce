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
		$this->assertFalse( $result['methods'][0]['rates'][0]['zero_cost'] );
		$this->assertSame( 2, $method->last_package['contents']['srt-sample-item']['quantity'] );
		$this->assertTrue( $method->last_package['contents']['srt-sample-item']['data']->needs_shipping() );
		$this->assertSame( 0, $method->last_package['user']['ID'] );
	}

	/**
	 * Text fields are unslashed, sanitized, trimmed, and normalized.
	 */
	public function test_text_fields_are_normalized() {
		$method = new SRT_Test_Method( 'flat_rate', 'Flat rate', array( new WC_Shipping_Rate( 12.5 ) ) );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test(
			array(
				'country'  => 'us',
				'state'    => 'n\\y',
				'postcode' => '<strong>10001</strong>',
				'city'     => ' New York ',
			)
		);

		$this->assertSame( 'US', $result['package']['country'] );
		$this->assertSame( 'NY', $result['package']['state'] );
		$this->assertSame( '10001', $result['package']['postcode'] );
		$this->assertSame( 'New York', $result['package']['city'] );
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
		$this->assertFalse( $result['methods'][0]['rates'][0]['zero_cost'] );
		$this->assertSame( '$13.75', $result['methods'][0]['cost'] );
	}

	/**
	 * Zero-cost rates are identified for the admin explanation.
	 */
	public function test_zero_cost_rate_is_marked() {
		$method = new SRT_Test_Method( 'local_pickup', 'Local pickup', array( new WC_Shipping_Rate( 0 ) ) );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertTrue( $result['methods'][0]['rates'][0]['zero_cost'] );
	}

	/**
	 * Malformed rate objects are reported as unavailable.
	 */
	public function test_malformed_rate_is_unavailable() {
		$method = new SRT_Test_Method( 'flat_rate', 'Flat rate', array( new WC_Shipping_Rate( array( 'bad' ) ) ) );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertSame( 'unavailable', $result['methods'][0]['status'] );
		$this->assertFalse( $result['methods'][0]['rates'][0]['available'] );
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
	 * Disabled methods remain visible and are not calculated.
	 */
	public function test_disabled_method_is_reported() {
		$method = new SRT_Test_Disabled_Method( 'flat_rate', 'Disabled rate', array( new WC_Shipping_Rate( 20 ) ) );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertSame( 'disabled', $result['methods'][0]['status'] );
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
	 * An empty method response is reported as no rate.
	 */
	public function test_empty_method_response_is_no_rate() {
		$method = new SRT_Test_Method( 'flat_rate', 'Flat rate', array() );
		WC_Shipping_Zone::$methods = array( $method );

		$result = ( new \AmazingPlugins\SRT\Shipping\Shipping_Tester() )->test( array( 'country' => 'US' ) );

		$this->assertSame( 'no-rate', $result['methods'][0]['status'] );
		$this->assertSame( 'The method did not return a rate for this package.', $result['methods'][0]['note'] );
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
		$this->assertSame( 'srt_invalid_package', $result->get_error_code() );
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

	/**
	 * Signed and scientific numeric forms are rejected.
	 */
	public function test_unsupported_numeric_forms_return_error() {
		$tester = new \AmazingPlugins\SRT\Shipping\Shipping_Tester();

		foreach ( array( '-1', '+1', '1e2', '.5' ) as $value ) {
			$result = $tester->test(
				array(
					'country' => 'US',
					'value'   => $value,
				)
			);

			$this->assertInstanceOf( WP_Error::class, $result, $value );
		}
	}
}
