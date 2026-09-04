<?php
/**
 * Plugin Name: Shipping Rules Tester for WooCommerce
 * Plugin URI:  https://amazingplugins.com/plugins/shipping-rules-tester/
 * Description: Test WooCommerce shipping zones and methods with a sample destination and package.
 * Version:     1.0.0
 * Author:      AmazingPlugins
 * Author URI:  https://amazingplugins.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shipping-rules-tester
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 11.1
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
final class Shipping_Rules_Tester {

	/**
	 * Plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin file.
	 */
	const FILE = __FILE__;

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
	}

	/**
	 * Initialize after plugins have loaded.
	 */
	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		if ( ! defined( 'SRT_PLUGIN_DIR' ) ) {
			define( 'SRT_PLUGIN_DIR', plugin_dir_path( self::FILE ) );
		}
		if ( ! defined( 'SRT_PLUGIN_URL' ) ) {
			define( 'SRT_PLUGIN_URL', plugin_dir_url( self::FILE ) );
		}
		if ( ! defined( 'SRT_VERSION' ) ) {
			define( 'SRT_VERSION', self::VERSION );
		}

		require_once SRT_PLUGIN_DIR . 'includes/class-srt-tester.php';
		require_once SRT_PLUGIN_DIR . 'includes/class-srt-admin.php';

		add_action( 'init', array( $this, 'load_textdomain' ) );

		$admin = new SRT_Admin();
		$admin->init();
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'shipping-rules-tester',
			false,
			dirname( plugin_basename( self::FILE ) ) . '/languages'
		);
	}
}

Shipping_Rules_Tester::instance();
