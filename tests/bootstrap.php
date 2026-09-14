<?php
/**
 * Test bootstrap for Shipping Rules Tester.
 *
 * @package ShippingRulesTester
 */

define( 'ABSPATH', true );

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}

		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( strip_tags( $value ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wc_format_decimal' ) ) {
	function wc_format_decimal( $value, $decimals = false ) {
		return false === $decimals ? (string) $value : number_format( (float) $value, $decimals, '.', '' );
	}
}

if ( ! function_exists( 'wc_price' ) ) {
	function wc_price( $value ) {
		return '$' . number_format( (float) $value, 2, '.', '' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $value ) {
		return strip_tags( $value );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 7;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Constructor.
		 *
		 * @param string $code Error code.
		 * @param string $message Error message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * Get the error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * Get the error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}
	}
}

if ( ! class_exists( 'WC_Countries' ) ) {
	class WC_Countries {
		/**
		 * Get countries.
		 *
		 * @return array
		 */
		public function get_countries() {
			return array( 'US' => 'United States', 'GB' => 'United Kingdom' );
		}
	}
}

if ( ! function_exists( 'WC' ) ) {
	function WC() {
		static $woocommerce;
		if ( ! $woocommerce ) {
			$woocommerce           = new stdClass();
			$woocommerce->countries = new WC_Countries();
		}
		return $woocommerce;
	}
}

if ( ! class_exists( 'WC_Shipping_Rate' ) ) {
	class WC_Shipping_Rate {
		/**
		 * Rate cost.
		 *
		 * @var float
		 */
		private $cost;

		/**
		 * Rate identifier.
		*
		 * @var string
		 */
		private $id;

		/**
		 * Tax amounts.
		*
		 * @var array
		 */
		private $taxes;

		/**
		 * Constructor.
		*
		 * @param float $cost Rate cost.
		 * @param array $taxes Tax amounts.
		 * @param string $id Rate identifier.
		 */
		public function __construct( $cost, $taxes = array(), $id = 'flat_rate:1' ) {
			$this->cost  = $cost;
			$this->taxes = $taxes;
			$this->id    = $id;
		}

		/**
		 * Get cost.
		 *
		 * @return float
		 */
		public function get_cost() {
			return $this->cost;
		}

		/**
		 * Get taxes.
		 *
		 * @return array
		 */
		public function get_taxes() {
			return $this->taxes;
		}

		/**
		 * Get rate identifier.
		*
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}
	}
}

if ( ! class_exists( 'WC_Product_Simple' ) ) {
	class WC_Product_Simple {
		/**
		 * Set sample price.
		*
		 * @param float $price Product price.
		 */
		public function set_price( $price ) {}

		/**
		 * Set sample weight.
		*
		 * @param string $weight Product weight.
		 */
		public function set_weight( $weight ) {}

		/**
		 * Whether the sample needs shipping.
		*
		 * @return bool
		 */
		public function needs_shipping() {
			return true;
		}

		/**
		 * Get the sample shipping class.
		*
		 * @return string
		 */
		public function get_shipping_class() {
			return '';
		}
	}
}

if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
	class WC_Shipping_Zone {
		/**
		 * Methods returned by the test zone.
		 *
		 * @var array
		 */
		public static $methods = array();

		/**
		 * Get zone name.
		*
		 * @return string
		 */
		public function get_zone_name() {
			return 'United States';
		}

		/**
		 * Get configured methods.
		*
		 * @param bool $enabled_only Whether to return enabled methods only.
		 * @return array
		 */
		public function get_shipping_methods( $enabled_only = false ) {
		if ( ! empty( self::$methods ) ) {
			return self::$methods;
		}

			return array( new SRT_Test_Method( 'flat_rate', 'Flat rate', array( new WC_Shipping_Rate( 12.5 ) ) ) );
		}
	}
}

if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
	class WC_Shipping_Zones {
		/**
		 * Get matching zone.
		*
		 * @param array $package Package data.
		 * @return WC_Shipping_Zone
		 */
		public static function get_zone_matching_package( $package ) {
			return new WC_Shipping_Zone();
		}
	}
}

if ( ! class_exists( 'SRT_Test_Method' ) ) {
	class SRT_Test_Method {
		/**
		 * Method ID.
		*
		 * @var string
		 */
		public $id;

		/**
		 * Title.
		*
		 * @var string
		 */
		private $title;

		/**
		 * Rates.
		*
		 * @var array
		 */
		private $rates;

		/**
		 * Number of calculations.
		*
		 * @var int
		 */
		public $calls = 0;

		/**
		 * Last package received by the method.
		*
		 * @var array
		 */
		public $last_package = array();

		/**
		 * Constructor.
		*
		 * @param string $id Method ID.
		 * @param string $title Method title.
		 * @param array  $rates Rates.
		 */
		public function __construct( $id, $title, $rates ) {
			$this->id    = $id;
			$this->title = $title;
			$this->rates = $rates;
		}

		/**
		 * Whether method is enabled.
		*
		 * @return bool
		 */
		public function is_enabled() {
			return true;
		}

		/**
		 * Get title.
		*
		 * @return string
		 */
		public function get_method_title() {
			return $this->title;
		}

		/**
		 * Get instance ID.
		*
		 * @return int
		 */
		public function get_instance_id() {
			return 1;
		}

		/**
		 * Calculate rates.
		*
		 * @param array $package Package data.
		 * @return array
		 */
		public function get_rates_for_package( $package ) {
			$this->calls++;
			$this->last_package = $package;
			return $this->rates;
		}
	}
}

if ( ! class_exists( 'SRT_Test_Free_Shipping_Method' ) ) {
	class SRT_Test_Free_Shipping_Method extends SRT_Test_Method {
		/**
		 * Return a cart-dependent requirement.
		*
		 * @param string $key Setting name.
		* @param string $default Default value.
		 * @return string
		 */
		public function get_option( $key, $default = '' ) {
			return 'requires' === $key ? 'min_amount' : $default;
		}
	}
}

if ( ! class_exists( 'SRT_Test_Disabled_Method' ) ) {
	class SRT_Test_Disabled_Method extends SRT_Test_Method {
		/**
		 * Disabled methods must remain visible in the result.
		*
		 * @return bool
		 */
		public function is_enabled() {
			return false;
		}
	}
}

if ( ! class_exists( 'SRT_Test_Throwing_Method' ) ) {
	class SRT_Test_Throwing_Method extends SRT_Test_Method {
		/**
		 * Throw while calculating a rate.
		*
		 * @param array $package Package data.
		 * @return array
		 * @throws RuntimeException Test exception.
		 */
		public function get_rates_for_package( $package ) {
			throw new RuntimeException( 'test failure' );
		}
	}
}

require_once dirname( __DIR__ ) . '/src/Shipping/class-input-normalizer.php';
require_once dirname( __DIR__ ) . '/src/Shipping/class-package-builder.php';
require_once dirname( __DIR__ ) . '/src/Shipping/class-result-formatter.php';
require_once dirname( __DIR__ ) . '/src/Shipping/class-shipping-tester.php';
