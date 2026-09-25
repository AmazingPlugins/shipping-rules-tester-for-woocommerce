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
			__( 'Shipping Rules Tester', 'ap-shipping-rules-tester-for-woocommerce' ),
			__( 'Shipping Rules Tester', 'ap-shipping-rules-tester-for-woocommerce' ),
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
				esc_html__( 'Test shipping rules', 'ap-shipping-rules-tester-for-woocommerce' )
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

		wp_enqueue_style( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.css', array(), (string) filemtime( SRT_PLUGIN_DIR . 'assets/admin.css' ) );
		wp_enqueue_script( 'srt-product-picker', SRT_PLUGIN_URL . 'assets/product-picker.js', array( 'wp-api-fetch' ), (string) filemtime( SRT_PLUGIN_DIR . 'assets/product-picker.js' ), true );
		wp_enqueue_script( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.js', array( 'wp-api-fetch', 'srt-product-picker' ), (string) filemtime( SRT_PLUGIN_DIR . 'assets/admin.js' ), true );
		wp_localize_script(
			'srt-admin',
			'srtData',
			array(
				'restUrl'       => '/srt/v1/test',
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'states'        => WC()->countries->get_states(),
				'countryMeta'   => wp_json_file_decode( SRT_PLUGIN_DIR . 'assets/countries.json', array( 'associative' => true ) ),
				'currency'      => strtoupper( sanitize_text_field( (string) get_option( 'woocommerce_currency', 'USD' ) ) ),
				'weightUnit'    => sanitize_text_field( (string) get_option( 'woocommerce_weight_unit', 'kg' ) ),
				'dimensionUnit' => sanitize_text_field( (string) get_option( 'woocommerce_dimension_unit', 'cm' ) ),
				'i18n'          => array(
					'productSearch'  => __( 'Search products', 'ap-shipping-rules-tester-for-woocommerce' ),
					'searchHint'     => __( 'Type at least 3 characters to search by name, SKU, or ID.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'searchLoading'  => __( 'Searching products…', 'ap-shipping-rules-tester-for-woocommerce' ),
					'searchTop'      => __( 'Product suggestions', 'ap-shipping-rules-tester-for-woocommerce' ),
					'synthetic'      => __( 'Synthetic package', 'ap-shipping-rules-tester-for-woocommerce' ),
					'searchMore'     => __( 'Showing the first 10 matches. Refine your search for another product.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'searchEmpty'    => __( 'No shippable simple products or variations found. Refine your search.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'taxNotTested'   => __( 'Tax not tested', 'ap-shipping-rules-tester-for-woocommerce' ),
					'lineValue'      => __( 'Line value (all units)', 'ap-shipping-rules-tester-for-woocommerce' ),
					'lineWeight'     => __( 'Line weight (all units)', 'ap-shipping-rules-tester-for-woocommerce' ),
					'unitValue'      => __( 'Value per item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'unitWeight'     => __( 'Weight per item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'productPending' => __( 'Saved-product totals are calculated when you run the test.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'error'          => __( 'The shipping test could not be completed.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'chooseFromList' => __( 'Choose a country from the list.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'noResults'      => __( 'No countries found.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'testing'        => __( 'Testing shipping rules…', 'ap-shipping-rules-tester-for-woocommerce' ),
					'advanced'       => __( 'Advanced scenario', 'ap-shipping-rules-tester-for-woocommerce' ),
					'hideAdvanced'   => __( 'Hide advanced scenario', 'ap-shipping-rules-tester-for-woocommerce' ),
					'quickPresets'   => __( 'Quick scenarios', 'ap-shipping-rules-tester-for-woocommerce' ),
					'presetStandard' => __( 'Standard order', 'ap-shipping-rules-tester-for-woocommerce' ),
					'presetFree'     => __( 'Free-shipping check', 'ap-shipping-rules-tester-for-woocommerce' ),
					'presetHeavy'    => __( 'Heavy parcel', 'ap-shipping-rules-tester-for-woocommerce' ),
					'presetPickup'   => __( 'Local pickup', 'ap-shipping-rules-tester-for-woocommerce' ),
					'item'           => __( 'Item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'itemType'       => __( 'Item type', 'ap-shipping-rules-tester-for-woocommerce' ),
					'syntheticItem'  => __( 'Synthetic item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'savedProduct'   => __( 'Saved product', 'ap-shipping-rules-tester-for-woocommerce' ),
					'addItem'        => __( 'Add another item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'removeItem'     => __( 'Remove item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'reset'          => __( 'Reset', 'ap-shipping-rules-tester-for-woocommerce' ),
					'packageItems'   => __( 'Package items', 'ap-shipping-rules-tester-for-woocommerce' ),
					'packageTotals'  => __( 'Package totals', 'ap-shipping-rules-tester-for-woocommerce' ),
					'itemsChecked'   => __( 'Items', 'ap-shipping-rules-tester-for-woocommerce' ),
					'methodsChecked' => __( 'Methods checked', 'ap-shipping-rules-tester-for-woocommerce' ),
					'ratesFound'     => __( 'Rates found', 'ap-shipping-rules-tester-for-woocommerce' ),
					'rate'           => __( 'Rate', 'ap-shipping-rules-tester-for-woocommerce' ),
					'cost'           => __( 'Cost', 'ap-shipping-rules-tester-for-woocommerce' ),
					'matched'        => __( 'Matched', 'ap-shipping-rules-tester-for-woocommerce' ),
					'errorStatus'    => __( 'Error', 'ap-shipping-rules-tester-for-woocommerce' ),
					'countryOnly'    => __( 'Country only', 'ap-shipping-rules-tester-for-woocommerce' ),
					'chooseCountry'  => __( 'Choose a country', 'ap-shipping-rules-tester-for-woocommerce' ),
					'testingShort'   => __( 'Testing…', 'ap-shipping-rules-tester-for-woocommerce' ),
					'testButton'     => __( 'Test shipping rules', 'ap-shipping-rules-tester-for-woocommerce' ),
					'comparisonHint' => __( 'Results stay in this browser tab only.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'matchedZone'    => __( 'Matched shipping zone', 'ap-shipping-rules-tester-for-woocommerce' ),
					'methods'        => __( 'Shipping methods', 'ap-shipping-rules-tester-for-woocommerce' ),
					'noMethods'      => __( 'No enabled shipping methods were found in this zone.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'method'         => __( 'Method', 'ap-shipping-rules-tester-for-woocommerce' ),
					'result'         => __( 'Result', 'ap-shipping-rules-tester-for-woocommerce' ),
					'details'        => __( 'Details', 'ap-shipping-rules-tester-for-woocommerce' ),
					'tax'            => __( 'tax', 'ap-shipping-rules-tester-for-woocommerce' ),
					'total'          => __( 'total', 'ap-shipping-rules-tester-for-woocommerce' ),
					'noRate'         => __( 'No rate', 'ap-shipping-rules-tester-for-woocommerce' ),
					'unavailable'    => __( 'Unavailable', 'ap-shipping-rules-tester-for-woocommerce' ),
					'disabled'       => __( 'Disabled', 'ap-shipping-rules-tester-for-woocommerce' ),
					'notTested'      => __( 'Not tested', 'ap-shipping-rules-tester-for-woocommerce' ),
					'zeroCost'       => __( 'This method returned a zero-cost rate. Check its configured cost; free shipping and local pickup commonly return zero.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'comparison'     => __( 'Scenario comparison', 'ap-shipping-rules-tester-for-woocommerce' ),
					// translators: %d: scenario number.
					'scenario'       => __( 'Scenario %d', 'ap-shipping-rules-tester-for-woocommerce' ),
					'package'        => __( 'Package', 'ap-shipping-rules-tester-for-woocommerce' ),
					'keep'           => __( 'Keep result and test another', 'ap-shipping-rules-tester-for-woocommerce' ),
					'clear'          => __( 'Clear comparison', 'ap-shipping-rules-tester-for-woocommerce' ),
					'kept'           => __( 'Result kept. Change the inputs and run another test.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'fallback'       => __( 'fallback zone', 'ap-shipping-rules-tester-for-woocommerce' ),
					'destination'    => __( 'Destination', 'ap-shipping-rules-tester-for-woocommerce' ),
					'value'          => __( 'value', 'ap-shipping-rules-tester-for-woocommerce' ),
					'weight'         => __( 'weight', 'ap-shipping-rules-tester-for-woocommerce' ),
					'quantity'       => __( 'quantity', 'ap-shipping-rules-tester-for-woocommerce' ),
					'zoneRules'      => __( 'Zone matching rules', 'ap-shipping-rules-tester-for-woocommerce' ),
					'fallbackRule'   => __( 'No explicit location rules apply. WooCommerce uses this as the fallback zone.', 'ap-shipping-rules-tester-for-woocommerce' ),
					'product'        => __( 'Product', 'ap-shipping-rules-tester-for-woocommerce' ),
					'productDetails' => __( 'Product details', 'ap-shipping-rules-tester-for-woocommerce' ),
					'shippingClass'  => __( 'shipping class', 'ap-shipping-rules-tester-for-woocommerce' ),
					'dimensions'     => __( 'dimensions', 'ap-shipping-rules-tester-for-woocommerce' ),
					'taxClass'       => __( 'tax class', 'ap-shipping-rules-tester-for-woocommerce' ),
					'productPrice'   => __( 'price per item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'productWeight'  => __( 'weight per item', 'ap-shipping-rules-tester-for-woocommerce' ),
					'none'           => __( 'none', 'ap-shipping-rules-tester-for-woocommerce' ),
					'standard'       => __( 'standard', 'ap-shipping-rules-tester-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Render the tester form.
	 */
	public function render_page() {
		$countries        = WC()->countries->get_countries();
		$shipping_classes = get_terms(
			array(
				'taxonomy'   => 'product_shipping_class',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $shipping_classes ) ) {
			$shipping_classes = array();
		}
		$currency       = strtoupper( sanitize_text_field( (string) get_option( 'woocommerce_currency', 'USD' ) ) );
		$weight_unit    = sanitize_text_field( (string) get_option( 'woocommerce_weight_unit', 'kg' ) );
		$dimension_unit = sanitize_text_field( (string) get_option( 'woocommerce_dimension_unit', 'cm' ) );
		require SRT_PLUGIN_DIR . 'templates/admin-page.php';
	}
}
