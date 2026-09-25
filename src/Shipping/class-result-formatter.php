<?php
/**
 * Format shipping results.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

namespace AmazingPlugins\SRT\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Convert WooCommerce rate objects into safe response data.
 */
class Result_Formatter {

	/**
	 * Format a WooCommerce shipping rate.
	 *
	 * @param object $rate Rate object.
	 * @param bool   $tax_known Whether sample tax was evaluated.
	 * @return array
	 */
	public function format_rate( $rate, $tax_known = true ) {
		if ( ! is_object( $rate ) || ! method_exists( $rate, 'get_cost' ) || ! method_exists( $rate, 'get_taxes' ) ) {
			return $this->unavailable_rate();
		}

		$raw_cost = $rate->get_cost();
		$taxes    = $rate->get_taxes();
		if ( ! is_scalar( $raw_cost ) || ! is_numeric( $raw_cost ) || ! is_array( $taxes ) ) {
			return $this->unavailable_rate();
		}

		$tax = 0;
		foreach ( $taxes as $tax_amount ) {
			if ( is_scalar( $tax_amount ) && is_numeric( $tax_amount ) ) {
				$tax += (float) $tax_amount;
			}
		}

		$cost  = (float) $raw_cost;
		$total = $cost + $tax;
		return array(
			'id'        => method_exists( $rate, 'get_id' ) ? sanitize_text_field( (string) $rate->get_id() ) : '',
			'cost'      => $this->format_money( $cost ),
			'tax'       => $tax_known ? $this->format_money( $tax ) : '',
			'total'     => $tax_known ? $this->format_money( $total ) : '',
			'tax_known' => $tax_known,
			'zero_cost' => 0.0 === $total,
			'available' => true,
		);
	}

	/**
	 * Return the unavailable rate shape.
	 *
	 * @return array
	 */
	private function unavailable_rate() {
		return array(
			'id'        => '',
			'cost'      => '',
			'tax'       => '',
			'total'     => '',
			'available' => false,
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
