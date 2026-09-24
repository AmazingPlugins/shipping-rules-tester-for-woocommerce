<?php
/**
 * Explicit sample tax calculations without a customer or cart session.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

namespace AmazingPlugins\SRT\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Use package data rather than the administrator's customer context.
 */
class Tax_Context {

	/**
	 * Calculate a saved product's net line value.
	 *
	 * @param object $product Saved product.
	 * @param int    $quantity Quantity.
	 * @param array  $input Sample destination.
	 * @return float|\WP_Error
	 */
	public function product_value( $product, $quantity, array $input ) {
		$value = (float) $product->get_price() * $quantity;
		if ( ! wc_tax_enabled() || ! wc_prices_include_tax() || ! $product->is_taxable() ) {
			return $value;
		}

		if ( apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
			$rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
		} else {
			if ( 'billing' === get_option( 'woocommerce_tax_based_on', 'shipping' ) ) {
				return new \WP_Error( 'srt_billing_price', __( 'This tax-inclusive product needs a billing address to determine its net value. Use a synthetic net package value instead.', 'shipping-rules-tester-for-woocommerce' ) );
			}
			$location              = $this->location( $input, false );
			$location['tax_class'] = $product->get_tax_class();
			$rates                 = \WC_Tax::find_rates( $location );
		}

		return $value - array_sum( \WC_Tax::calc_tax( $value, $rates, true ) );
	}

	/**
	 * Calculate local costs and replace taxes with explicit sample taxes.
	 *
	 * @param object $method Shipping method.
	 * @param array  $package Sample package.
	 * @return array Rates and tax availability.
	 */
	public function calculate( $method, array $package ) {
		$tax_status = isset( $method->tax_status ) ? $method->tax_status : null;
		if ( null !== $tax_status ) {
			$method->tax_status = 'none';
		}
		try {
			$rates = $method->get_rates_for_package( $package );
		} finally {
			if ( null !== $tax_status ) {
				$method->tax_status = $tax_status;
			}
		}

		$known = true;
		if ( wc_tax_enabled() && 'taxable' === $tax_status ) {
			$pickup = 'local_pickup' === $method->id && apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true );
			$known  = $pickup || 'billing' !== get_option( 'woocommerce_tax_based_on', 'shipping' );
			$class  = $this->shipping_class( $package );
			$taxes  = array();
			if ( $known && null !== $class ) {
				$location              = $this->location( $package['destination'], $pickup );
				$location['tax_class'] = $class;
				$taxes                 = \WC_Tax::find_shipping_rates( $location );
			}
			foreach ( (array) $rates as $rate ) {
				if ( $rate instanceof \WC_Shipping_Rate ) {
					$rate->set_taxes( $known ? \WC_Tax::calc_shipping_tax( (float) $rate->get_cost(), $taxes ) : array() );
					if ( method_exists( $rate, 'set_tax_status' ) ) {
						$rate->set_tax_status( $tax_status );
					}
				}
			}
		}

		return array(
			'rates'     => $rates,
			'tax_known' => $known,
		);
	}

	/**
	 * Resolve the shipping tax class from the sample's taxable items.
	 *
	 * @param array $package Sample package.
	 * @return string|null
	 */
	private function shipping_class( array $package ) {
		$configured = get_option( 'woocommerce_shipping_tax_class', 'inherit' );
		if ( 'inherit' !== $configured ) {
			return (string) $configured;
		}
		$classes = array();
		foreach ( $package['contents'] as $item ) {
			$product = $item['data'];
			if ( 'none' !== $product->get_tax_status() ) {
				$classes[] = $product->get_tax_class();
			}
		}
		foreach ( array_merge( array( '' ), \WC_Tax::get_tax_class_slugs() ) as $class ) {
			if ( in_array( $class, $classes, true ) ) {
				return $class;
			}
		}
		return null;
	}

	/**
	 * Choose the sample shipping address or the configured shop base.
	 *
	 * @param array $input Sample destination.
	 * @param bool  $pickup Whether pickup uses base tax.
	 * @return array
	 */
	private function location( array $input, $pickup ) {
		if ( $pickup || 'base' === get_option( 'woocommerce_tax_based_on', 'shipping' ) ) {
			return array(
				'country'  => WC()->countries->get_base_country(),
				'state'    => WC()->countries->get_base_state(),
				'postcode' => WC()->countries->get_base_postcode(),
				'city'     => WC()->countries->get_base_city(),
			);
		}
		return array_intersect_key( $input, array_flip( array( 'country', 'state', 'postcode', 'city' ) ) );
	}
}
