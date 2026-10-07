<?php
/**
 * Tests for the admin screen palette.
 *
 * @package ShippingRulesTester
 */

use PHPUnit\Framework\TestCase;

/**
 * The tester screen should use the AmazingPlugins site colors.
 */
class Test_Admin_Theme extends TestCase {

	/**
	 * Admin CSS uses the site cream, ink, and terracotta tokens.
	 */
	public function test_admin_css_uses_the_site_palette() {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/assets/admin.css' );

		$this->assertIsString( $css );
		$this->assertStringContainsString( '--apsrt-ink: #1A1410;', $css );
		$this->assertStringContainsString( '--apsrt-cream: #FAF5EC;', $css );
		$this->assertStringContainsString( '--apsrt-accent: #A63617;', $css );
		$this->assertStringNotContainsString( '#102a43', $css );
		$this->assertStringNotContainsString( '#174a6e', $css );
		$this->assertStringNotContainsString( '#1769aa', $css );
		$this->assertStringNotContainsString( '#0b8f87', $css );
		$this->assertStringNotContainsString( '#8be3db', $css );
	}
}
