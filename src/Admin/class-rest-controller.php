<?php
/**
 * REST API controller.
 *
 * @package ShippingRulesTester
 */

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
			'/test',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_shipping' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
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
				__( 'You do not have permission to run this test.', 'shipping-rules-tester' ),
				array( 'status' => 403 )
			);
		}

		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'srt_invalid_nonce',
				__( 'Security check failed.', 'shipping-rules-tester' ),
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
