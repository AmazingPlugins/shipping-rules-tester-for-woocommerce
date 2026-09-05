<?php
/**
 * Build a synthetic WooCommerce package.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

namespace AmazingPlugins\SRT\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Build package data without saving a product.
 */
class Package_Builder {

	/**
	 * Build the package shape expected by WooCommerce shipping methods.
	 *
	 * @param array $input Validated values.
	 * @return array
	 */
	public function build( array $input ) {
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
}
