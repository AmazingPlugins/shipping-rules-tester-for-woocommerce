<?php
/**
 * Run shipping tests.
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
	 * Input normalizer.
	 *
	 * @var Input_Normalizer
	 */
	private $normalizer;

	/**
	 * Package builder.
	 *
	 * @var Package_Builder
	 */
	private $package_builder;

	/**
	 * Result formatter.
	 *
	 * @var Result_Formatter
	 */
	private $formatter;

	/**
	 * Constructor.
	 *
	 * @param Input_Normalizer|null $normalizer Input normalizer.
	 * @param Package_Builder|null  $package_builder Package builder.
	 * @param Result_Formatter|null $formatter Result formatter.
	 */
	public function __construct( $normalizer = null, $package_builder = null, $formatter = null ) {
		$this->normalizer      = $normalizer instanceof Input_Normalizer ? $normalizer : new Input_Normalizer();
		$this->package_builder = $package_builder instanceof Package_Builder ? $package_builder : new Package_Builder();
		$this->formatter       = $formatter instanceof Result_Formatter ? $formatter : new Result_Formatter();
	}

	/**
	 * Validate request data and calculate configured rates.
	 *
	 * @param array $raw Request values.
	 * @return array|\WP_Error
	 */
	public function test( $raw ) {
		$input = $this->normalizer->normalize( $raw );
		if ( is_wp_error( $input ) ) {
			return $input;
		}

		$product = null;
		if ( ! empty( $input['product_id'] ) ) {
			$product = wc_get_product( absint( $input['product_id'] ) );
			if ( ! is_object( $product ) ) {
				return new \WP_Error( 'srt_invalid_product', __( 'The selected product could not be found.', 'shipping-rules-tester-for-woocommerce' ) );
			}

			if ( method_exists( $product, 'needs_shipping' ) && ! $product->needs_shipping() ) {
				return new \WP_Error( 'srt_non_shippable_product', __( 'Choose a product that requires shipping.', 'shipping-rules-tester-for-woocommerce' ) );
			}

			$input['value']  = wc_format_decimal( $this->get_product_number( $product, 'get_price' ) * absint( $input['quantity'] ), 2 );
			$input['weight'] = wc_format_decimal( $this->get_product_number( $product, 'get_weight' ) * absint( $input['quantity'] ), 3 );
		}

		$package = $this->package_builder->build( $input, $product );
		$zone    = \WC_Shipping_Zones::get_zone_matching_package( $package );
		if ( ! is_a( $zone, 'WC_Shipping_Zone' ) ) {
			return new \WP_Error( 'srt_no_zone', __( 'WooCommerce could not match this destination to a shipping zone.', 'shipping-rules-tester-for-woocommerce' ) );
		}

		$methods = $zone->get_shipping_methods( false );
		$rows    = array();
		foreach ( $methods as $method ) {
			if ( ! is_object( $method ) ) {
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
			if ( ! $method->is_enabled() ) {
				$row['status'] = 'disabled';
				$row['note']   = __( 'Skipped because this shipping method is disabled.', 'shipping-rules-tester-for-woocommerce' );
				$rows[]        = $row;
				continue;
			}

			$requires_cart = 'free_shipping' === $method->id && method_exists( $method, 'get_option' ) && '' !== $method->get_option( 'requires', '' );
			if ( ! $requires_cart && in_array( $method->id, self::LOCAL_METHODS, true ) ) {
				try {
					$rates = $method->get_rates_for_package( $package );
				} catch ( \Throwable $exception ) {
					$rates         = array();
					$row['status'] = 'error';
					$row['note']   = __( 'The method reported an error while testing this package.', 'shipping-rules-tester-for-woocommerce' );
				}
				if ( 'error' !== $row['status'] ) {
					if ( is_array( $rates ) && ! empty( $rates ) ) {
						$costs = array();
						foreach ( $rates as $rate ) {
							$formatted_rate = $this->formatter->format_rate( $rate );
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
							$row['note']   = __( 'The method returned a rate that could not be read.', 'shipping-rules-tester-for-woocommerce' );
						}
					} else {
						$row['status'] = 'no-rate';
						$row['note']   = __( 'The method did not return a rate for this package.', 'shipping-rules-tester-for-woocommerce' );
					}
				}
			} elseif ( $requires_cart ) {
				$row['note'] = __( 'Skipped because this free-shipping rule requires live cart or coupon context that a sample package cannot provide.', 'shipping-rules-tester-for-woocommerce' );
			} else {
				$row['note'] = __( 'Skipped because this method may need an external rate provider or product-specific data.', 'shipping-rules-tester-for-woocommerce' );
			}
			$rows[] = $row;
		}

		return array(
			'zone'           => $zone->get_zone_name(),
			'fallback'       => method_exists( $zone, 'get_id' ) && 0 === absint( $zone->get_id() ),
			'zone_locations' => $this->get_zone_locations( $zone ),
			'package'        => $input,
			'product'        => $this->get_product_summary( $product ),
			'methods'        => $rows,
		);
	}

	/**
	 * Read a numeric product property without trusting malformed data.
	 *
	 * @param object $product Product object.
	 * @param string $method Getter method.
	 * @return float
	 */
	private function get_product_number( $product, $method ) {
		if ( ! method_exists( $product, $method ) ) {
			return 0;
		}

		$value = $product->$method();
		return is_scalar( $value ) && is_numeric( $value ) ? (float) $value : 0;
	}

	/**
	 * Return safe product details for the result panel.
	 *
	 * @param object|null $product Product object.
	 * @return array|null
	 */
	private function get_product_summary( $product ) {
		if ( ! is_object( $product ) ) {
			return null;
		}

		$dimensions = array();
		foreach ( array( 'length', 'width', 'height' ) as $dimension ) {
			$dimensions[ $dimension ] = $this->get_product_number( $product, 'get_' . $dimension );
		}

		return array(
			'id'             => method_exists( $product, 'get_id' ) ? absint( $product->get_id() ) : 0,
			'name'           => method_exists( $product, 'get_name' ) ? sanitize_text_field( (string) $product->get_name() ) : '',
			'type'           => method_exists( $product, 'get_type' ) ? sanitize_key( (string) $product->get_type() ) : '',
			'price'          => $this->get_product_number( $product, 'get_price' ),
			'weight'         => $this->get_product_number( $product, 'get_weight' ),
			'dimensions'     => $dimensions,
			'shipping_class' => method_exists( $product, 'get_shipping_class' ) ? sanitize_text_field( (string) $product->get_shipping_class() ) : '',
			'tax_class'      => method_exists( $product, 'get_tax_class' ) ? sanitize_text_field( (string) $product->get_tax_class() ) : '',
		);
	}

	/**
	 * Return safe location rules for the matched zone.
	 *
	 * @param object $zone Matched shipping zone.
	 * @return array
	 */
	private function get_zone_locations( $zone ) {
		if ( ! method_exists( $zone, 'get_zone_locations' ) ) {
			return array();
		}

		$locations = array();
		foreach ( (array) $zone->get_zone_locations() as $location ) {
			if ( ! is_object( $location ) ) {
				continue;
			}

			$type = isset( $location->type ) ? sanitize_key( (string) $location->type ) : '';
			$code = isset( $location->code ) ? sanitize_text_field( (string) $location->code ) : '';
			if ( '' === $type || '' === $code ) {
				continue;
			}

			$locations[] = array(
				'type' => $type,
				'code' => $code,
			);
		}

		return $locations;
	}
}
