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

		$resolved_items = array();
		$item_summaries = array();
		$total_value    = 0.0;
		$total_weight   = 0.0;
		$total_quantity = 0;
		foreach ( $input['items'] as $item ) {
			$product = null;
			if ( 'product' === $item['source'] ) {
				$product = wc_get_product( absint( $item['product_id'] ) );
				if ( ! is_object( $product ) ) {
					return new \WP_Error( 'srt_invalid_product', __( 'One of the selected products could not be found.', 'ap-shipping-rules-tester-for-woocommerce' ) );
				}

				if ( method_exists( $product, 'needs_shipping' ) && ! $product->needs_shipping() ) {
					return new \WP_Error( 'srt_non_shippable_product', __( 'Choose products that require shipping.', 'ap-shipping-rules-tester-for-woocommerce' ) );
				}
				if ( ! $product->is_type( 'simple' ) && ! $product->is_type( 'variation' ) ) {
					return new \WP_Error( 'srt_product_type', __( 'Choose a simple product or a specific variation.', 'ap-shipping-rules-tester-for-woocommerce' ) );
				}

				$net_value = ( new Tax_Context() )->product_value( $product, absint( $item['quantity'] ), $input );
				if ( is_wp_error( $net_value ) ) {
					return $net_value;
				}
				$item['value']  = wc_format_decimal( $net_value, wc_get_price_decimals() );
				$item['weight'] = wc_format_decimal( $this->get_product_number( $product, 'get_weight' ) * absint( $item['quantity'] ), 3 );
			} elseif ( empty( $item['legacy_totals'] ) ) {
				$item['value']  = wc_format_decimal( (float) $item['value'] * absint( $item['quantity'] ), 2 );
				$item['weight'] = wc_format_decimal( (float) $item['weight'] * absint( $item['quantity'] ), 3 );
			}

			$total_value     += (float) $item['value'];
			$total_weight    += (float) $item['weight'];
			$total_quantity  += absint( $item['quantity'] );
			$resolved_items[] = array(
				'input'   => $item,
				'product' => $product,
				'value'   => $item['value'],
				'weight'  => $item['weight'],
			);
			$item_summaries[] = $this->get_item_summary( $item, $product );
		}

		$input['value']      = wc_format_decimal( $total_value, 2 );
		$input['weight']     = wc_format_decimal( $total_weight, 3 );
		$input['quantity']   = $total_quantity;
		$input['product_id'] = count( $resolved_items ) === 1 && is_object( $resolved_items[0]['product'] ) ? absint( $resolved_items[0]['product']->get_id() ) : 0;
		foreach ( $input['items'] as &$item ) {
			$item['totals'] = ! empty( $item['legacy_totals'] );
			unset( $item['legacy_totals'] );
		}
		unset( $item );
		$single_product = count( $resolved_items ) === 1 ? $resolved_items[0]['product'] : null;

		$package = $this->package_builder->build( $input, $single_product, $resolved_items );
		$zone    = \WC_Shipping_Zones::get_zone_matching_package( $package );
		if ( ! is_a( $zone, 'WC_Shipping_Zone' ) ) {
			return new \WP_Error( 'srt_no_zone', __( 'WooCommerce could not match this destination to a shipping zone.', 'ap-shipping-rules-tester-for-woocommerce' ) );
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
				$row['note']   = __( 'Skipped because this shipping method is disabled.', 'ap-shipping-rules-tester-for-woocommerce' );
				$rows[]        = $row;
				continue;
			}

			$requires_cart = 'free_shipping' === $method->id && method_exists( $method, 'get_option' ) && '' !== $method->get_option( 'requires', '' );
			if ( ! $requires_cart && in_array( $method->id, self::LOCAL_METHODS, true ) ) {
				if ( wc_tax_enabled() && wc_string_to_bool( apply_filters( 'woocommerce_shipping_prices_include_tax', false ) ) ) {
					$row['note'] = __( 'Tax-inclusive shipping extensions need a real checkout test.', 'ap-shipping-rules-tester-for-woocommerce' );
					$rows[]      = $row;
					continue;
				}
				$tax_known = true;
				try {
					$calculated = ( new Tax_Context() )->calculate( $method, $package );
					$rates      = $calculated['rates'];
					$tax_known  = $calculated['tax_known'];
					if ( ! $tax_known ) {
						$row['note'] = __( 'Shipping cost excludes tax. Billing-address tax cannot be evaluated from a shipping destination.', 'ap-shipping-rules-tester-for-woocommerce' );
					}
				} catch ( \Throwable $exception ) {
					$rates         = array();
					$row['status'] = 'error';
					$row['note']   = __( 'The method reported an error while testing this package.', 'ap-shipping-rules-tester-for-woocommerce' );
				}
				if ( 'error' !== $row['status'] ) {
					if ( is_array( $rates ) && ! empty( $rates ) ) {
						$costs = array();
						foreach ( $rates as $rate ) {
							$formatted_rate = $this->formatter->format_rate( $rate, $tax_known );
							$row['rates'][] = $formatted_rate;
							if ( $formatted_rate['available'] ) {
								$costs[] = $tax_known ? $formatted_rate['total'] : $formatted_rate['cost'];
							}
						}
						if ( ! empty( $costs ) ) {
							$row['status'] = 'matched';
							$row['cost']   = implode( ', ', $costs );
						} else {
							$row['status'] = 'unavailable';
							$row['note']   = __( 'The method returned a rate that could not be read.', 'ap-shipping-rules-tester-for-woocommerce' );
						}
					} else {
						$row['status'] = 'no-rate';
						$row['note']   = __( 'The method did not return a rate for this package.', 'ap-shipping-rules-tester-for-woocommerce' );
					}
				}
			} elseif ( $requires_cart ) {
				$row['note'] = __( 'Skipped because this free-shipping rule requires live cart or coupon context that a sample package cannot provide.', 'ap-shipping-rules-tester-for-woocommerce' );
			} else {
				$row['note'] = __( 'Skipped because this method may need an external rate provider or product-specific data.', 'ap-shipping-rules-tester-for-woocommerce' );
			}
			$rows[] = $row;
		}

		return array(
			'zone'           => $zone->get_zone_name(),
			'fallback'       => method_exists( $zone, 'get_id' ) && 0 === absint( $zone->get_id() ),
			'zone_locations' => $this->get_zone_locations( $zone ),
			'package'        => $input,
			'product'        => 1 === count( $item_summaries ) && 'product' === $item_summaries[0]['source'] ? $item_summaries[0] : null,
			'items'          => $item_summaries,
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
	 * Return a safe summary for a package item.
	 *
	 * @param array       $item    Normalized item.
	 * @param object|null $product Saved product, if selected.
	 * @return array
	 */
	private function get_item_summary( array $item, $product ) {
		if ( is_object( $product ) ) {
			$summary                = $this->get_product_summary( $product );
			$summary['source']      = 'product';
			$summary['quantity']    = absint( $item['quantity'] );
			$summary['line_value']  = (float) $item['value'];
			$summary['line_weight'] = (float) $item['weight'];
			return $summary;
		}

		$quantity = absint( $item['quantity'] );
		return array(
			'source'            => 'custom',
			'id'                => 0,
			'name'              => __( 'Synthetic item', 'ap-shipping-rules-tester-for-woocommerce' ),
			'type'              => 'custom',
			'price'             => $quantity > 0 ? (float) $item['value'] / $quantity : 0,
			'weight'            => $quantity > 0 ? (float) $item['weight'] / $quantity : 0,
			'dimensions'        => array(
				'length' => (float) $item['length'],
				'width'  => (float) $item['width'],
				'height' => (float) $item['height'],
			),
			'shipping_class'    => '',
			'shipping_class_id' => absint( $item['shipping_class_id'] ),
			'tax_class'         => '',
			'quantity'          => $quantity,
			'line_value'        => (float) $item['value'],
			'line_weight'       => (float) $item['weight'],
		);
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
