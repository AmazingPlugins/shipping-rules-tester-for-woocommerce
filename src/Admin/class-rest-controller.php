<?php
/**
 * REST API controller.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

namespace AmazingPlugins\SRT\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Expose one authenticated, read-only test route.
 */
class REST_Controller {

	/**
	 * Shipping tester.
	 *
	 * @var \AmazingPlugins\SRT\Shipping\Shipping_Tester
	 */
	private $tester;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tester = new \AmazingPlugins\SRT\Shipping\Shipping_Tester();
	}

	/**
	 * Register the route.
	 */
	public function register_routes() {
		register_rest_route(
			'srt/v1',
			'/products',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_products' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
		register_rest_route(
			'srt/v1',
			'/test',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_shipping' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Search the catalog, including variation names and SKUs.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array|\WP_Error
	 */
	public function search_products( $request ) {
		$term = $request->get_param( 'search' );
		$term = null === $term ? '' : $term;
		if ( ! is_string( $term ) || strlen( $term ) > 100 || ( '' !== trim( $term ) && strlen( trim( $term ) ) < 3 && ! preg_match( '/^[0-9]+$/', trim( $term ) ) ) ) {
			return new \WP_Error( 'srt_product_search', __( 'Enter a product ID or at least three characters of a name or SKU, up to 100 characters.', 'ap-shipping-rules-tester-for-woocommerce' ), array( 'status' => 400 ) );
		}
		$term = sanitize_text_field( $term );
		$ids  = array();
		if ( '' === $term ) {
			foreach ( array( 'simple', 'variation' ) as $type ) {
				$ids = array_merge(
					$ids,
					wc_get_products(
						array(
							'type'    => $type,
							'limit'   => 11,
							'orderby' => 'name',
							'order'   => 'ASC',
							'status'  => array( 'publish', 'private', 'draft' ),
							'virtual' => false,
							'return'  => 'ids',
						)
					)
				);
			}
		} else {
			$store = \WC_Data_Store::load( 'product' );
			$ids   = $store->search_products( $term, '', true, true, 51 );
		}
		$products = array();
		foreach ( array_slice( $ids, 0, 50 ) as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || ! $product->needs_shipping() || ! in_array( $product->get_type(), array( 'simple', 'variation' ), true ) || ! in_array( $product->get_status(), array( 'publish', 'private', 'draft' ), true ) ) {
				continue;
			}
			$products[] = array(
				'id'   => $product->get_id(),
				'name' => html_entity_decode( wp_strip_all_tags( $product->get_formatted_name() ), ENT_QUOTES, 'UTF-8' ),
			);
		}
		usort(
			$products,
			static function ( $left, $right ) {
				return strnatcasecmp( $left['name'], $right['name'] );
			}
		);
		return array(
			'products' => array_slice( $products, 0, 10 ),
			'more'     => count( $products ) > 10 || count( $ids ) > 50,
		);
	}

	/**
	 * Check capability and the WordPress REST nonce.
	 *
	 * @return true|\WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'srt_forbidden',
				__( 'You do not have permission to run this test.', 'ap-shipping-rules-tester-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'srt_invalid_nonce',
				__( 'Security check failed.', 'ap-shipping-rules-tester-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Run a shipping test.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array|\WP_Error
	 */
	public function test_shipping( $request ) {
		$result = $this->tester->test( $request->get_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}
}
