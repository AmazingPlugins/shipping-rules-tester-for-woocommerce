<?php
/**
 * Admin page and assets.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

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
		add_filter( 'plugin_action_links_' . plugin_basename( SRT_PLUGIN_FILE ), array( $this, 'add_plugin_action_link' ) );
	}

	/**
	 * Add the WooCommerce submenu.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Shipping Rules Tester', 'shipping-rules-tester-for-woocommerce' ),
			__( 'Shipping Rules Tester', 'shipping-rules-tester-for-woocommerce' ),
			'manage_woocommerce',
			'shipping-rules-tester-for-woocommerce',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a direct link to the tester from the Plugins screen.
	 *
	 * @param string[] $links Existing plugin action links.
	 * @return string[]
	 */
	public function add_plugin_action_link( $links ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $links;
		}

		$url = admin_url( 'admin.php?page=shipping-rules-tester-for-woocommerce' );
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html__( 'Test shipping rules', 'shipping-rules-tester-for-woocommerce' )
			)
		);

		return $links;
	}

	/**
	 * Enqueue assets only on this screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_shipping-rules-tester-for-woocommerce' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.css', array(), \AmazingPlugins\SRT\Core\Plugin::VERSION );
		wp_enqueue_script( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.js', array( 'wp-api-fetch' ), \AmazingPlugins\SRT\Core\Plugin::VERSION, true );
		wp_localize_script(
			'srt-admin',
			'srtData',
			array(
				'restUrl' => '/srt/v1/test',
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'error'       => __( 'The shipping test could not be completed.', 'shipping-rules-tester-for-woocommerce' ),
					'testing'     => __( 'Testing shipping rules…', 'shipping-rules-tester-for-woocommerce' ),
					'matchedZone' => __( 'Matched shipping zone', 'shipping-rules-tester-for-woocommerce' ),
					'methods'     => __( 'Shipping methods', 'shipping-rules-tester-for-woocommerce' ),
					'noMethods'   => __( 'No enabled shipping methods were found in this zone.', 'shipping-rules-tester-for-woocommerce' ),
					'method'      => __( 'Method', 'shipping-rules-tester-for-woocommerce' ),
					'result'      => __( 'Result', 'shipping-rules-tester-for-woocommerce' ),
					'details'     => __( 'Details', 'shipping-rules-tester-for-woocommerce' ),
					'tax'         => __( 'tax', 'shipping-rules-tester-for-woocommerce' ),
					'total'       => __( 'total', 'shipping-rules-tester-for-woocommerce' ),
					'noRate'      => __( 'No rate', 'shipping-rules-tester-for-woocommerce' ),
					'unavailable' => __( 'Unavailable', 'shipping-rules-tester-for-woocommerce' ),
					'disabled'    => __( 'Disabled', 'shipping-rules-tester-for-woocommerce' ),
					'notTested'   => __( 'Not tested', 'shipping-rules-tester-for-woocommerce' ),
					'zeroCost'    => __( 'This method returned a zero-cost rate. Check its configured cost; free shipping and local pickup commonly return zero.', 'shipping-rules-tester-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Render the tester form.
	 */
	public function render_page() {
		$countries   = WC()->countries->get_countries();
		$currency    = strtoupper( sanitize_text_field( (string) get_option( 'woocommerce_currency', 'USD' ) ) );
		$weight_unit = sanitize_text_field( (string) get_option( 'woocommerce_weight_unit', 'kg' ) );
		require SRT_PLUGIN_DIR . 'templates/admin-page.php';
	}
}
