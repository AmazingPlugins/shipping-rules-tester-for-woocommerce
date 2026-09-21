<?php
/**
 * Plugin Name: AP Shipping Rules Tester for WooCommerce
 * Plugin URI:  https://amazingplugins.com/plugins/shipping-rules-tester/
 * Description: Test WooCommerce shipping zones and methods with a sample destination and package.
 * Version:     1.2.0
 * Author:      AmazingPlugins
 * Author URI:  https://amazingplugins.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.6
 * Text Domain: shipping-rules-tester-for-woocommerce
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 11.1
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

define( 'SRT_PLUGIN_FILE', __FILE__ );
define( 'SRT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SRT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SRT_PLUGIN_DIR . 'src/Core/class-plugin.php';

\AmazingPlugins\SRT\Core\Plugin::instance();
