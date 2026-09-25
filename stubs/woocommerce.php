<?php
/**
 * Minimal WooCommerce declarations used by PHPStan.
 *
 * @package ShippingRulesTester
 */

/**
 * Get the WooCommerce singleton.
 *
 * @return object
 */
function WC(): object {
}

/**
 * Format a decimal.
 *
 * @param mixed $number Number.
 * @param int   $dp Decimal places.
 * @return string
 */
function wc_format_decimal( $number, $dp = false ): string {
}

/**
 * Format a price.
 *
 * @param float $price Price.
 * @return string
 */
function wc_price( $price ): string {
}

/**
 * Get one WooCommerce product.
 *
 * @param int $product_id Product ID.
 * @return object|null
 */
function wc_get_product( $product_id ) {
}

/**
 * Get WooCommerce products.
 *
 * @param array $args Query arguments.
 * @return array
 */
function wc_get_products( array $args = array() ): array {
}

function wc_tax_enabled(): bool {}
function wc_prices_include_tax(): bool {}
function wc_get_price_decimals(): int {}
function wc_string_to_bool( $value ): bool {}

class WC_Data_Store {
	/** @return object */
	public static function load( $type ) {}
}

class WC_Tax {
	/** @return array */
	public static function get_base_tax_rates( $class = '' ) {}
	/** @return array */
	public static function find_rates( $args = array() ) {}
	/** @return array */
	public static function find_shipping_rates( $args = array() ) {}
	/** @return array */
	public static function calc_tax( $price, $rates, $inclusive = false ) {}
	/** @return array */
	public static function calc_shipping_tax( $price, $rates ) {}
	/** @return string[] */
	public static function get_tax_class_slugs() {}
}

class WC_Shipping_Rate {
	/** @return float */
	public function get_cost() {}
	/** @return void */
	public function set_taxes( $taxes ) {}
	/** @return void */
	public function set_tax_status( $status ) {}
}

/**
 * Shipping zones API.
 */
class WC_Shipping_Zones {
	/**
	 * Get the matching zone.
	*
	 * @param array $package Package.
	 * @return WC_Shipping_Zone
	 */
	public static function get_zone_matching_package( array $package ): WC_Shipping_Zone {
	}
}

/**
 * Shipping zone.
 */
class WC_Shipping_Zone {
	/**
	 * Get enabled methods.
	*
	 * @param bool $enabled_only Enabled only.
	 * @return array
	 */
	public function get_shipping_methods( $enabled_only = false ): array {
	}

	/**
	 * Get zone name.
	*
	 * @return string
	 */
	public function get_zone_name(): string {
	}
}

/**
 * Synthetic product.
 */
class WC_Product_Simple {
	/**
	 * Set price.
	*
	 * @param float $price Price.
	 */
	public function set_price( $price ): void {
	}

	/**
	 * Set weight.
	*
	 * @param string $weight Weight.
	 */
	public function set_weight( $weight ): void {
	}
}
