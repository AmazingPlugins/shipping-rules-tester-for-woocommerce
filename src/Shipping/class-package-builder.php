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
	 * @param array       $input          Validated values.
	 * @param object|null $product        Optional saved product.
	 * @param array       $resolved_items Resolved item rows.
	 * @return array
	 */
	public function build( array $input, $product = null, array $resolved_items = array() ) {
		if ( empty( $resolved_items ) ) {
			$resolved_items[] = array(
				'input'   => array(
					'value'    => $input['value'],
					'weight'   => $input['weight'],
					'quantity' => $input['quantity'],
				),
				'product' => $product,
			);
		}

		$contents       = array();
		$contents_cost  = 0.0;
		$total_weight   = 0.0;
		$total_quantity = 0;
		foreach ( $resolved_items as $index => $resolved_item ) {
			$item_input      = isset( $resolved_item['input'] ) && is_array( $resolved_item['input'] ) ? $resolved_item['input'] : array();
			$item_product    = isset( $resolved_item['product'] ) && is_object( $resolved_item['product'] ) ? $resolved_item['product'] : null;
			$quantity        = absint( isset( $item_input['quantity'] ) ? $item_input['quantity'] : 1 );
			$value           = (float) ( isset( $resolved_item['value'] ) ? $resolved_item['value'] : ( isset( $item_input['value'] ) ? $item_input['value'] : 0 ) );
			$weight          = (float) ( isset( $resolved_item['weight'] ) ? $resolved_item['weight'] : ( isset( $item_input['weight'] ) ? $item_input['weight'] : 0 ) );
			$unit_value      = $quantity > 0 ? $value / $quantity : 0;
			$unit_weight     = $quantity > 0 ? $weight / $quantity : 0;
			$package_product = is_object( $item_product ) ? clone $item_product : new \WC_Product_Simple();

			if ( ! is_object( $item_product ) ) {
				$package_product->set_price( $unit_value );
				$package_product->set_weight( (string) $unit_weight );
				$this->set_synthetic_properties( $package_product, $item_input );
			}

			$product_id   = is_object( $item_product ) && method_exists( $item_product, 'get_id' ) ? absint( $item_product->get_id() ) : 0;
			$variation_id = is_object( $item_product ) && method_exists( $item_product, 'is_type' ) && $item_product->is_type( 'variation' ) ? $product_id : 0;
			if ( $variation_id > 0 && method_exists( $item_product, 'get_parent_id' ) ) {
				$product_id = absint( $item_product->get_parent_id() );
			}

			$key              = 0 === $index ? 'srt-sample-item' : 'srt-sample-item-' . ( $index + 1 );
			$contents[ $key ] = array(
				'key'               => $key,
				'product_id'        => $product_id,
				'variation_id'      => $variation_id,
				'variation'         => array(),
				'quantity'          => $quantity,
				'data'              => $package_product,
				'line_total'        => $value,
				'line_tax'          => 0,
				'line_subtotal'     => $value,
				'line_subtotal_tax' => 0,
			);
			$contents_cost   += $value;
			$total_weight    += $weight;
			$total_quantity  += $quantity;
		}

		return array(
			'contents'             => $contents,
			'contents_cost'        => $contents_cost,
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
			'cart_subtotal'        => $contents_cost,
			'cart_subtotal_tax'    => 0,
			'cart_contents_total'  => $contents_cost,
			'cart_contents_weight' => $total_weight,
			'cart_contents_count'  => $total_quantity,
			'free_shipping'        => false,
		);
	}

	/**
	 * Apply non-persistent values to a synthetic product.
	 *
	 * @param object $product Unsaved product object.
	 * @param array  $input   Item values.
	 */
	private function set_synthetic_properties( $product, array $input ) {
		if ( ! empty( $input['shipping_class_id'] ) && method_exists( $product, 'set_shipping_class_id' ) ) {
			$product->set_shipping_class_id( absint( $input['shipping_class_id'] ) );
		}

		foreach ( array( 'length', 'width', 'height' ) as $dimension ) {
			$setter = 'set_' . $dimension;
			if ( method_exists( $product, $setter ) ) {
				$product->$setter( isset( $input[ $dimension ] ) ? (string) $input[ $dimension ] : '0' );
			}
		}
	}
}
