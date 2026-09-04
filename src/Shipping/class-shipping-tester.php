<?php
/**
 * Shipping calculation and validation.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

namespace AmazingPlugins\SRT\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Build a sample package and inspect safe WooCommerce methods.
 */
class Shipping_Tester {

	/**
	 * Methods that can calculate without an external provider.
	 *
	 * @var string[]
	 */
	const LOCAL_METHODS = array( 'free_shipping', 'flat_rate', 'local_pickup' );

	/**
	 * Validate request data and calculate configured rates.
	 *
	 * @param array $raw Request values.
	 * @return array|\WP_Error
	 */
	public function test( $raw ) {
		$input = $this->normalize_input( $raw );
		if ( is_wp_error( $input ) ) {
			return $input;
		}

		$package = $this->build_package( $input );
		$zone    = \WC_Shipping_Zones::get_zone_matching_package( $package );
		if ( ! is_a( $zone, 'WC_Shipping_Zone' ) ) {
			return new \WP_Error( 'srt_no_zone', __( 'WooCommerce could not match this destination to a shipping zone.', 'shipping-rules-tester' ) );
		}

		$methods = $zone->get_shipping_methods( true );
		$rows    = array();
		foreach ( $methods as $method ) {
			if ( ! is_object( $method ) || ! $method->is_enabled() ) {
				continue;
			}

			$row = array(
				'id'       => (string) $method->get_method_title(),
				'instance' => absint( $method->get_instance_id() ),
				'method'   => (string) $method->id,
				'cost'     => '',
				'rates'    => array(),
				'status'   => 'not-tested',
				'note'     => '',
			);

			$requires_cart = 'free_shipping' === $method->id && method_exists( $method, 'get_option' ) && '' !== $method->get_option( 'requires', '' );
			if ( ! $requires_cart && in_array( $method->id, self::LOCAL_METHODS, true ) ) {
				try {
					$rates = $method->get_rates_for_package( $package );
				} catch ( \Throwable $exception ) {
					$rates         = array();
					$row['status'] = 'error';
					$row['note']   = __( 'The method reported an error while testing this package.', 'shipping-rules-tester' );
				}
				if ( 'error' !== $row['status'] ) {
					if ( is_array( $rates ) && ! empty( $rates ) ) {
						$costs = array();
						foreach ( $rates as $rate ) {
							$formatted_rate = $this->format_rate( $rate );
							$row['rates'][] = $formatted_rate;
							if ( $formatted_rate['available'] ) {
								$costs[] = $formatted_rate['total'];
							}
						}
						if ( ! empty( $costs ) ) {
							$row['status'] = 'matched';
							$row['cost']   = implode( ', ', $costs );
						} else {
							$row['status'] = 'unavailable';
							$row['note']   = __( 'The method returned a rate that could not be read.', 'shipping-rules-tester' );
						}
					} else {
						$row['status'] = 'no-rate';
						$row['note']   = __( 'The method did not return a rate for this package.', 'shipping-rules-tester' );
					}
				}
			} elseif ( $requires_cart ) {
				$row['note'] = __( 'Skipped because this free-shipping rule requires live cart or coupon context that a sample package cannot provide.', 'shipping-rules-tester' );
			} else {
				$row['note'] = __( 'Skipped because this method may need an external rate provider or product-specific data.', 'shipping-rules-tester' );
			}
			$rows[] = $row;
		}

		return array(
			'zone'     => $zone->get_zone_name(),
			'fallback' => method_exists( $zone, 'get_id' ) && 0 === absint( $zone->get_id() ),
			'package'  => $input,
			'methods'  => $rows,
		);
	}

	/**
	 * Normalize and validate submitted values.
	 *
	 * @param mixed $raw Request values.
	 * @return array|\WP_Error
	 */
	private function normalize_input( $raw ) {
		$raw       = is_array( $raw ) ? wp_unslash( $raw ) : array();
		$country   = isset( $raw['country'] ) && is_scalar( $raw['country'] ) ? strtoupper( sanitize_text_field( (string) $raw['country'] ) ) : '';
		$countries = \WC()->countries->get_countries();
		if ( ! isset( $countries[ $country ] ) ) {
			return new \WP_Error( 'srt_invalid_country', __( 'Choose a valid destination country.', 'shipping-rules-tester' ) );
		}

		$value    = $this->parse_decimal( isset( $raw['value'] ) ? $raw['value'] : 0, 2 );
		$weight   = $this->parse_decimal( isset( $raw['weight'] ) ? $raw['weight'] : 0, 3 );
		$quantity = $this->parse_quantity( isset( $raw['quantity'] ) ? $raw['quantity'] : 1 );
		if ( is_wp_error( $value ) || is_wp_error( $weight ) || is_wp_error( $quantity ) ) {
			return new \WP_Error( 'srt_invalid_package', __( 'Enter package values within the supported ranges.', 'shipping-rules-tester' ) );
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

	/**
	 * Build the package shape expected by WooCommerce shipping methods.
	 *
	 * @param array $input Validated values.
	 * @return array
	 */
	private function build_package( $input ) {
		$quantity       = absint( $input['quantity'] );
		$unit_value     = $quantity > 0 ? (float) $input['value'] / $quantity : 0;
		$unit_weight    = $quantity > 0 ? (float) $input['weight'] / $quantity : 0;
		$sample_product = new \WC_Product_Simple();
		$sample_product->set_price( $unit_value );
		$sample_product->set_weight( (string) $unit_weight );

		return array(
			'contents'             => array(
				'srt-sample-item' => array(
					'key'               => 'srt-sample-item',
					'product_id'        => 0,
					'variation_id'      => 0,
					'variation'         => array(),
					'quantity'          => $quantity,
					'data'              => $sample_product,
					'line_total'        => (float) $input['value'],
					'line_tax'          => 0,
					'line_subtotal'     => (float) $input['value'],
					'line_subtotal_tax' => 0,
				),
			),
			'contents_cost'        => (float) $input['value'],
			'applied_coupons'      => array(),
			'user'                 => array( 'ID' => get_current_user_id() ),
			'destination'          => array(
				'country'   => $input['country'],
				'state'     => $input['state'],
				'postcode'  => $input['postcode'],
				'city'      => $input['city'],
				'address'   => '',
				'address_2' => '',
			),
			'cart_subtotal'        => (float) $input['value'],
			'cart_subtotal_tax'    => 0,
			'cart_contents_total'  => (float) $input['value'],
			'cart_contents_weight' => (float) $input['weight'],
			'cart_contents_count'  => $quantity,
			'free_shipping'        => false,
		);
	}

	/**
	 * Format a WooCommerce shipping rate for the response.
	 *
	 * @param object $rate Rate object.
	 * @return array
	 */
	private function format_rate( $rate ) {
		if ( ! is_object( $rate ) || ! method_exists( $rate, 'get_cost' ) || ! method_exists( $rate, 'get_taxes' ) ) {
			return array(
				'id'        => '',
				'cost'      => '',
				'tax'       => '',
				'total'     => '',
				'available' => false,
			);
		}

		$raw_cost = $rate->get_cost();
		$taxes    = $rate->get_taxes();
		if ( ! is_scalar( $raw_cost ) || ! is_array( $taxes ) ) {
			return array(
				'id'        => '',
				'cost'      => '',
				'tax'       => '',
				'total'     => '',
				'available' => false,
			);
		}

		$tax = 0;
		foreach ( $taxes as $tax_amount ) {
			if ( is_scalar( $tax_amount ) && is_numeric( $tax_amount ) ) {
				$tax += (float) $tax_amount;
			}
		}

		$cost = (float) $raw_cost;
		return array(
			'id'        => method_exists( $rate, 'get_id' ) ? sanitize_text_field( (string) $rate->get_id() ) : '',
			'cost'      => $this->format_money( $cost ),
			'tax'       => $this->format_money( $tax ),
			'total'     => $this->format_money( $cost + $tax ),
			'available' => true,
		);
	}

	/**
	 * Format an amount using the store currency.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	private function format_money( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}
}
