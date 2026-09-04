<?php
/**
 * Admin page and assets.
 *
 * @package ShippingRulesTester
 */

namespace AmazingPlugins\SRT\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Register the WooCommerce admin screen.
 */
class Admin {

	/**
	 * REST controller.
	 *
	 * @var REST_Controller
	 */
	private $rest_controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->rest_controller = new REST_Controller();
	}

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'rest_api_init', array( $this->rest_controller, 'register_routes' ) );
	}

	/**
	 * Add the WooCommerce submenu.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Shipping Rules Tester', 'shipping-rules-tester' ),
			__( 'Shipping Rules Tester', 'shipping-rules-tester' ),
			'manage_woocommerce',
			'shipping-rules-tester',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets only on this screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_shipping-rules-tester' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.css', array(), \AmazingPlugins\SRT\Core\Plugin::VERSION );
		wp_enqueue_script( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.js', array( 'wp-api-fetch' ), \AmazingPlugins\SRT\Core\Plugin::VERSION, true );
		wp_localize_script(
			'srt-admin',
			'srtData',
			array(
				'restUrl' => rest_url( 'srt/v1/test' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'error'       => __( 'The shipping test could not be completed.', 'shipping-rules-tester' ),
					'testing'     => __( 'Testing shipping rules…', 'shipping-rules-tester' ),
					'matchedZone' => __( 'Matched shipping zone', 'shipping-rules-tester' ),
					'methods'     => __( 'Shipping methods', 'shipping-rules-tester' ),
					'noMethods'   => __( 'No enabled shipping methods were found in this zone.', 'shipping-rules-tester' ),
					'method'      => __( 'Method', 'shipping-rules-tester' ),
					'result'      => __( 'Result', 'shipping-rules-tester' ),
					'details'     => __( 'Details', 'shipping-rules-tester' ),
					'noRate'      => __( 'No rate', 'shipping-rules-tester' ),
					'notTested'   => __( 'Not tested', 'shipping-rules-tester' ),
				),
			)
		);
	}

	/**
	 * Render the tester form.
	 */
	public function render_page() {
		$countries = WC()->countries->get_countries();
		require SRT_PLUGIN_DIR . 'templates/admin-page.php';
	}
}
