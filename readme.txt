=== Shipping Rules Tester for WooCommerce ===
Contributors: amazingplugins
Tags: woocommerce, shipping, shipping zones, shipping rates, debugging
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Test WooCommerce shipping zones and methods with a sample destination and package.

== Description ==

Shipping Rules Tester gives store owners and developers a focused way to check which WooCommerce shipping zone and methods match a destination.

Enter a country, state, postcode, city, package value, weight, and item quantity. The plugin runs a read-only test and shows the matched zone, calculated local rates, and methods that did not return a rate.

External rate methods are skipped by default. You can explicitly allow them for a test after reviewing the warning. Test inputs and results are not saved.

== Features ==

* Match a sample destination to a WooCommerce shipping zone.
* Test built-in flat rate, free shipping, and local pickup methods.
* Use package value, weight, and quantity in the test.
* Optionally run configured external rate methods after an explicit confirmation.
* Show no-rate and skipped-method explanations.
* Use WooCommerce's shipping APIs without changing shipping settings, products, orders, or customers.
* Keep the tool local to the WooCommerce admin screen.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the `shipping-rules-tester-for-woocommerce` folder to `/wp-content/plugins/`, or install the ZIP from the Plugins screen.
3. Activate the plugin.
4. Go to **WooCommerce > Shipping Rules Tester**.
5. Enter a sample destination and package, then choose **Test shipping rules**.

== Frequently Asked Questions ==

= Does this change my shipping settings? =

No. The plugin reads the current configuration and calculates a sample result. It does not save changes.

= Are external shipping providers contacted? =

Not by default. External methods are skipped unless you select the explicit option to allow them. Those methods may send the sample data to their own provider.

= Does this test real cart contents? =

No. The test uses a synthetic package with the value, weight, and quantity you enter. Methods that require specific product data may need a real checkout test.

= Does this work with HPOS? =

Yes. The tester does not read or change order data and does not depend on the order storage mode.

== Screenshots ==

1. Destination and package test form.
2. Matched zone and shipping method results.

== Changelog ==

= 1.0.0 =
* Initial development release.
