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

		$value      = $this->parse_decimal( isset( $raw['value'] ) ? $raw['value'] : 0, 2 );
		$weight     = $this->parse_decimal( isset( $raw['weight'] ) ? $raw['weight'] : 0, 3 );
		$quantity   = $this->parse_quantity( isset( $raw['quantity'] ) ? $raw['quantity'] : 1 );
		$product_id = $this->parse_product_id( isset( $raw['product_id'] ) ? $raw['product_id'] : 0 );
		if ( is_wp_error( $value ) || is_wp_error( $weight ) || is_wp_error( $quantity ) || is_wp_error( $product_id ) ) {
			return new \WP_Error( 'srt_invalid_package', __( 'Enter package values within the supported ranges.', 'shipping-rules-tester-for-woocommerce' ) );
		}
		$items = $this->parse_items(
			isset( $raw['items'] ) ? $raw['items'] : null,
			array(
				'product_id' => $product_id,
				'value'      => $value,
				'weight'     => $weight,
				'quantity'   => $quantity,
			)
		);
		if ( is_wp_error( $items ) ) {
			return $items;
		}

		return array(
			'country'    => $country,
			'state'      => $this->sanitize_text_input( isset( $raw['state'] ) ? $raw['state'] : '', true, 100 ),
			'postcode'   => $this->sanitize_text_input( isset( $raw['postcode'] ) ? $raw['postcode'] : '', false, 20 ),
			'city'       => $this->sanitize_text_input( isset( $raw['city'] ) ? $raw['city'] : '', false, 100 ),
			'value'      => wc_format_decimal( $value, 2 ),
			'weight'     => wc_format_decimal( $weight, 3 ),
			'quantity'   => $quantity,
			'product_id' => $product_id,
			'items'      => $items,
		);
	}

	/**
	 * Normalize the optional item builder payload.
	 *
	 * The old single-package fields remain the fallback so saved browser forms
	 * and direct API clients continue to work.
	 *
	 * @param mixed $raw_items Submitted item rows or JSON.
	 * @param array $legacy Legacy single-item values.
	 * @return array|\WP_Error
	 */
	private function parse_items( $raw_items, array $legacy ) {
		if ( null === $raw_items || '' === $raw_items ) {
			$item = $this->normalize_item(
				array(
					'source'     => $legacy['product_id'] > 0 ? 'product' : 'custom',
					'product_id' => $legacy['product_id'],
					'value'      => $legacy['value'],
					'weight'     => $legacy['weight'],
					'quantity'   => $legacy['quantity'],
				)
			);
			if ( is_wp_error( $item ) ) {
				return $item;
			}
			$item['legacy_totals'] = true;

			return array(
				$item,
			);
		}

		if ( is_string( $raw_items ) ) {
			$raw_items = json_decode( $raw_items, true );
		}
		if ( ! is_array( $raw_items ) || empty( $raw_items ) || count( $raw_items ) > 10 ) {
			return new \WP_Error( 'srt_invalid_items', __( 'Add between one and ten package items.', 'shipping-rules-tester-for-woocommerce' ) );
		}

		$items = array();
		foreach ( $raw_items as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				return new \WP_Error( 'srt_invalid_items', __( 'Each package item must be valid.', 'shipping-rules-tester-for-woocommerce' ) );
			}

			$item = $this->normalize_item( $raw_item );
			if ( is_wp_error( $item ) ) {
				return $item;
			}
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Normalize one saved-product or synthetic package item.
	 *
	 * @param array $raw_item Raw item values.
	 * @return array|\WP_Error
	 */
	private function normalize_item( array $raw_item ) {
		$source = isset( $raw_item['source'] ) && is_scalar( $raw_item['source'] ) ? sanitize_key( (string) $raw_item['source'] ) : 'custom';
		if ( ! in_array( $source, array( 'custom', 'product' ), true ) ) {
			return new \WP_Error( 'srt_invalid_items', __( 'Choose a valid package item type.', 'shipping-rules-tester-for-woocommerce' ) );
		}

		$product_id     = $this->parse_product_id( isset( $raw_item['product_id'] ) ? $raw_item['product_id'] : 0 );
		$value          = $this->parse_decimal( isset( $raw_item['value'] ) ? $raw_item['value'] : 0, 2 );
		$weight         = $this->parse_decimal( isset( $raw_item['weight'] ) ? $raw_item['weight'] : 0, 3 );
		$quantity       = $this->parse_quantity( isset( $raw_item['quantity'] ) ? $raw_item['quantity'] : 1 );
		$shipping_class = $this->parse_product_id( isset( $raw_item['shipping_class_id'] ) ? $raw_item['shipping_class_id'] : 0 );
		$length         = $this->parse_decimal( isset( $raw_item['length'] ) ? $raw_item['length'] : 0, 3 );
		$width          = $this->parse_decimal( isset( $raw_item['width'] ) ? $raw_item['width'] : 0, 3 );
		$height         = $this->parse_decimal( isset( $raw_item['height'] ) ? $raw_item['height'] : 0, 3 );
		if ( is_wp_error( $product_id ) || is_wp_error( $value ) || is_wp_error( $weight ) || is_wp_error( $quantity ) || is_wp_error( $shipping_class ) || is_wp_error( $length ) || is_wp_error( $width ) || is_wp_error( $height ) ) {
			return new \WP_Error( 'srt_invalid_items', __( 'Check the values in each package item.', 'shipping-rules-tester-for-woocommerce' ) );
		}
		if ( 'product' === $source && $product_id < 1 ) {
			return new \WP_Error( 'srt_invalid_items', __( 'Choose a saved product or use a synthetic item.', 'shipping-rules-tester-for-woocommerce' ) );
		}

		return array(
			'source'            => $source,
			'legacy_totals'     => isset( $raw_item['totals'] ) && true === $raw_item['totals'],
			'product_id'        => 'product' === $source ? $product_id : 0,
			'value'             => wc_format_decimal( $value, 2 ),
			'weight'            => wc_format_decimal( $weight, 3 ),
			'quantity'          => $quantity,
			'shipping_class_id' => 'custom' === $source ? $shipping_class : 0,
			'length'            => wc_format_decimal( $length, 3 ),
			'width'             => wc_format_decimal( $width, 3 ),
			'height'            => wc_format_decimal( $height, 3 ),
		);
	}

	/**
	 * Parse an optional saved product ID.
	 *
	 * @param mixed $value Raw product ID.
	 * @return int|\WP_Error
	 */
	private function parse_product_id( $value ) {
		if ( ! is_scalar( $value ) || ! preg_match( '/^\d+$/D', trim( (string) $value ) ) ) {
			return new \WP_Error( 'srt_invalid_product' );
		}

		return absint( $value );
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
