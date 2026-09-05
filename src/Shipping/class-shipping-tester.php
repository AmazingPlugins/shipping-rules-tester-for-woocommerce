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

		$package = $this->package_builder->build( $input );
		$zone    = \WC_Shipping_Zones::get_zone_matching_package( $package );
		if ( ! is_a( $zone, 'WC_Shipping_Zone' ) ) {
			return new \WP_Error( 'srt_no_zone', __( 'WooCommerce could not match this destination to a shipping zone.', 'shipping-rules-tester-for-woocommerce' ) );
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
			'zone'     => $zone->get_zone_name(),
			'fallback' => method_exists( $zone, 'get_id' ) && 0 === absint( $zone->get_id() ),
			'package'  => $input,
			'methods'  => $rows,
		);
	}
}
