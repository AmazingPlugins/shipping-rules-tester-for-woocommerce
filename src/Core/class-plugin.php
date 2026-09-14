<?php
/**
 * Plugin coordinator.
 *
 * @package ShippingRulesTester
 */

declare( strict_types=1 );

namespace AmazingPlugins\SRT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Start the plugin after WooCommerce is loaded.
 */
final class Plugin {

	/**
	 * Plugin version.
	 */
	const VERSION = '1.0.1';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
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
	 * Register the startup hook.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
	}

	/**
	 * Load plugin services after dependencies are available.
	 */
	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		require_once SRT_PLUGIN_DIR . 'src/Shipping/class-input-normalizer.php';
		require_once SRT_PLUGIN_DIR . 'src/Shipping/class-package-builder.php';
		require_once SRT_PLUGIN_DIR . 'src/Shipping/class-result-formatter.php';
		require_once SRT_PLUGIN_DIR . 'src/Shipping/class-shipping-tester.php';
		require_once SRT_PLUGIN_DIR . 'src/Admin/class-rest-controller.php';
		require_once SRT_PLUGIN_DIR . 'src/Admin/class-admin.php';

		$admin = new \AmazingPlugins\SRT\Admin\Admin();
		$admin->init();
	}
}
