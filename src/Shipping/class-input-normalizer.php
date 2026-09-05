<?php
/**
 * Normalize shipping test input.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

namespace AmazingPlugins\SRT\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Validate and normalize request values.
 */
class Input_Normalizer {

	/**
	 * Normalize request values.
	 *
	 * @param mixed $raw Request values.
	 * @return array|\WP_Error
	 */
	public function normalize( $raw ) {
		$raw       = is_array( $raw ) ? wp_unslash( $raw ) : array();
		$country   = isset( $raw['country'] ) && is_scalar( $raw['country'] ) ? strtoupper( sanitize_text_field( (string) $raw['country'] ) ) : '';
		$countries = \WC()->countries->get_countries();
		if ( ! isset( $countries[ $country ] ) ) {
			return new \WP_Error( 'srt_invalid_country', __( 'Choose a valid destination country.', 'shipping-rules-tester-for-woocommerce' ) );
		}

		$value    = $this->parse_decimal( isset( $raw['value'] ) ? $raw['value'] : 0, 2 );
		$weight   = $this->parse_decimal( isset( $raw['weight'] ) ? $raw['weight'] : 0, 3 );
		$quantity = $this->parse_quantity( isset( $raw['quantity'] ) ? $raw['quantity'] : 1 );
		if ( is_wp_error( $value ) || is_wp_error( $weight ) || is_wp_error( $quantity ) ) {
			return new \WP_Error( 'srt_invalid_package', __( 'Enter package values within the supported ranges.', 'shipping-rules-tester-for-woocommerce' ) );
		}

		return array(
			'country'  => $country,
			'state'    => $this->sanitize_text_input( isset( $raw['state'] ) ? $raw['state'] : '', true, 100 ),
			'postcode' => $this->sanitize_text_input( isset( $raw['postcode'] ) ? $raw['postcode'] : '', false, 20 ),
			'city'     => $this->sanitize_text_input( isset( $raw['city'] ) ? $raw['city'] : '', false, 100 ),
			'value'    => wc_format_decimal( $value, 2 ),
			'weight'   => wc_format_decimal( $weight, 3 ),
			'quantity' => $quantity,
		);
	}

	/**
	 * Parse a non-negative decimal input.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $precision Maximum decimal places.
	 * @return float|\WP_Error
	 */
	private function parse_decimal( $value, $precision ) {
		if ( ! is_scalar( $value ) ) {
			return new \WP_Error( 'srt_invalid_decimal' );
		}

		$value = trim( (string) $value );
		$regex = '/^\d+(?:\.\d{1,' . absint( $precision ) . '})?$/D';
		if ( ! preg_match( $regex, $value ) || (float) $value > 100000 ) {
			return new \WP_Error( 'srt_invalid_decimal' );
		}

		return (float) $value;
	}

	/**
	 * Parse the item quantity.
	 *
	 * @param mixed $value Raw value.
	 * @return int|\WP_Error
	 */
	private function parse_quantity( $value ) {
		if ( ! is_scalar( $value ) || ! preg_match( '/^\d+$/D', trim( (string) $value ) ) ) {
			return new \WP_Error( 'srt_invalid_quantity' );
		}

		$value = absint( $value );
		return $value >= 1 && $value <= 10000 ? $value : new \WP_Error( 'srt_invalid_quantity' );
	}

	/**
	 * Sanitize a text input and apply its server-side length limit.
	 *
	 * @param mixed $value Raw value.
	 * @param bool  $uppercase Whether to uppercase the value.
	 * @param int   $max_length Maximum length.
	 * @return string
	 */
	private function sanitize_text_input( $value, $uppercase, $max_length ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( (string) $value );
		$value = substr( $value, 0, absint( $max_length ) );
		return $uppercase ? strtoupper( $value ) : $value;
	}
}
