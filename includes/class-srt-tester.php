<?php
/**
 * Shipping calculation and validation for the plugin.
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build a sample package and inspect WooCommerce shipping methods.
 */
class SRT_Tester {

	/**
	 * Built-in methods that can calculate without an external rate service.
	 *
	 * @var string[]
	 */
	const LOCAL_METHODS = array( 'free_shipping', 'flat_rate', 'local_pickup' );

	/**
	 * Validate request data and calculate configured rates.
	 *
	 * @param array $raw Request values.
	 *
	 * @return array|WP_Error
	 */
	public function test( $raw ) {
		$input = $this->normalize_input( $raw );
		if ( is_wp_error( $input ) ) {
			return $input;
		}

		$package = $this->build_package( $input );
		$zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
		if ( ! is_a( $zone, 'WC_Shipping_Zone' ) ) {
			return new WP_Error( 'srt_no_zone', __( 'WooCommerce could not match this destination to a shipping zone.', 'shipping-rules-tester' ) );
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
				'status'   => 'not-tested',
				'note'     => '',
			);

			if ( in_array( $method->id, self::LOCAL_METHODS, true ) || ! empty( $input['allow_external'] ) ) {
				try {
					$rates = $method->get_rates_for_package( $package );
				} catch ( Throwable $exception ) {
					$rates         = array();
					$row['status'] = 'error';
					$row['note']   = __( 'The method reported an error while testing this package.', 'shipping-rules-tester' );
				}
				if ( 'error' !== $row['status'] ) {
					if ( is_array( $rates ) && ! empty( $rates ) ) {
						$row['status'] = 'matched';
						$costs         = array();
						foreach ( $rates as $rate ) {
							$costs[] = $this->format_rate( $rate );
						}
						$row['cost'] = implode( ', ', $costs );
					} else {
						$row['status'] = 'no-rate';
						$row['note']   = __( 'The method did not return a rate for this package.', 'shipping-rules-tester' );
					}
				}
			} else {
				$row['note'] = __( 'Skipped by default because this method may contact an external rate service.', 'shipping-rules-tester' );
			}
			$rows[] = $row;
		}

		return array(
			'zone'    => $zone->get_zone_name(),
			'package' => $input,
			'methods' => $rows,
		);
	}

	/**
	 * Normalize and validate submitted values.
	 *
	 * @param mixed $raw Request values.
	 * @return array|WP_Error
	 */
	private function normalize_input( $raw ) {
		$raw       = is_array( $raw ) ? wp_unslash( $raw ) : array();
		$country   = isset( $raw['country'] ) ? strtoupper( sanitize_text_field( $raw['country'] ) ) : '';
		$countries = WC()->countries->get_countries();
		if ( ! isset( $countries[ $country ] ) ) {
			return new WP_Error( 'srt_invalid_country', __( 'Choose a valid destination country.', 'shipping-rules-tester' ) );
		}

		$value    = isset( $raw['value'] ) ? (float) $raw['value'] : 0;
		$weight   = isset( $raw['weight'] ) ? (float) $raw['weight'] : 0;
		$quantity = isset( $raw['quantity'] ) ? absint( $raw['quantity'] ) : 1;
		if ( $value < 0 || $value > 100000 || $weight < 0 || $weight > 100000 || $quantity < 1 || $quantity > 10000 ) {
			return new WP_Error( 'srt_invalid_package', __( 'Enter package values within the supported ranges.', 'shipping-rules-tester' ) );
		}

		return array(
			'country'        => $country,
			'state'          => isset( $raw['state'] ) ? strtoupper( sanitize_text_field( $raw['state'] ) ) : '',
			'postcode'       => isset( $raw['postcode'] ) ? sanitize_text_field( $raw['postcode'] ) : '',
			'city'           => isset( $raw['city'] ) ? sanitize_text_field( $raw['city'] ) : '',
			'value'          => wc_format_decimal( $value, 2 ),
			'weight'         => wc_format_decimal( $weight, 3 ),
			'quantity'       => $quantity,
			'allow_external' => ! empty( $raw['allow_external'] ),
		);
	}

	/**
	 * Build the package shape expected by WooCommerce shipping methods.
	 *
	 * @param array $input Validated values.
	 * @return array
	 */
	private function build_package( $input ) {
		return array(
			'contents'             => array(),
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
			'cart_contents_count'  => absint( $input['quantity'] ),
			'free_shipping'        => false,
		);
	}

	/**
	 * Format a WooCommerce shipping rate for the response.
	 *
	 * @param WC_Shipping_Rate $rate Rate object.
	 * @return string
	 */
	private function format_rate( $rate ) {
		$cost = (float) $rate->get_cost() + (float) array_sum( $rate->get_taxes() );
		return html_entity_decode( wp_strip_all_tags( wc_price( $cost ) ), ENT_QUOTES, 'UTF-8' );
	}
}
