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
	 * @param array       $input   Validated values.
	 * @param object|null $product Optional saved product.
	 * @return array
	 */
	public function build( array $input, $product = null ) {
		$quantity        = absint( $input['quantity'] );
		$unit_value      = $quantity > 0 ? (float) $input['value'] / $quantity : 0;
		$unit_weight     = $quantity > 0 ? (float) $input['weight'] / $quantity : 0;
		$package_product = is_object( $product ) ? clone $product : new \WC_Product_Simple();
		if ( ! is_object( $product ) ) {
			$package_product->set_price( $unit_value );
			$package_product->set_weight( (string) $unit_weight );
		}

		$product_id   = is_object( $product ) && method_exists( $product, 'get_id' ) ? absint( $product->get_id() ) : 0;
		$variation_id = is_object( $product ) && method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ? $product_id : 0;
		if ( $variation_id > 0 && method_exists( $product, 'get_parent_id' ) ) {
			$product_id = absint( $product->get_parent_id() );
		}

		return array(
			'contents'             => array(
				'srt-sample-item' => array(
					'key'               => 'srt-sample-item',
					'product_id'        => $product_id,
					'variation_id'      => $variation_id,
					'variation'         => array(),
					'quantity'          => $quantity,
					'data'              => $package_product,
					'line_total'        => (float) $input['value'],
					'line_tax'          => 0,
					'line_subtotal'     => (float) $input['value'],
					'line_subtotal_tax' => 0,
				),
			),
			'contents_cost'        => (float) $input['value'],
			'applied_coupons'      => array(),
			'user'                 => array( 'ID' => 0 ),
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
