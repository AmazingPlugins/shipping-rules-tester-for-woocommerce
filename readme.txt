=== AP Shipping Rules Tester for WooCommerce ===
Contributors: amazingplugins
Tags: woocommerce, shipping, shipping zones, shipping rates
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Test WooCommerce shipping zones and methods with a sample destination and package.

== Description ==

Shipping Rules Tester gives store owners and developers a focused way to check which WooCommerce shipping zone and methods match a package.

Enter a destination and run a quick package check, or open the advanced builder for multiple items, shipping classes, dimensions, and saved products. The plugin runs a read-only test and shows the matched zone, calculated local rates, and methods that did not return a rate.

External rate methods are not called. Methods that need live cart data or an external provider are listed as skipped. Test inputs and results are not saved.

== Features ==

* Match a sample destination to a WooCommerce shipping zone.
* Test built-in flat rate, free shipping, and local pickup methods.
* Use package value, weight, and quantity in the test.
* Test a saved product's shipping class, dimensions, weight, price, and tax class.
* Build a multi-item package with synthetic items and per-item quantities.
* Use quick scenarios for standard orders, free-shipping checks, heavy parcels, and local pickup.
* Clearly identify methods that need live cart data or an external provider.
* Show disabled, no-rate, and skipped-method explanations.
* Show the location rules for the matched shipping zone.
* Compare multiple test scenarios in the browser without saving them.
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

= Can I compare destinations or packages? =

Yes. Run one test, choose **Keep result and test another**, change the inputs, and run the next test. The comparison stays in the current browser tab and is cleared when you leave the page or choose **Clear comparison**.

= Which units does the tester use? =

Package value uses the store currency. Weight uses the weight unit configured in WooCommerce. Both values describe the complete sample package, not one product unit.

= Are external shipping providers contacted? =

No. The plugin never calls external shipping providers. Methods that need one are listed as skipped.

= Does this test real cart contents? =

No. The test uses synthetic items or saved products and builds an unsaved package for the shipping method. It doesn't include coupons, customer roles, subscriptions, or live-cart state. Methods that require that context may need a real checkout test.

= What happens when I select a product? =

The tester reads the saved product's current price, weight, dimensions, shipping class, and tax class. It doesn't save changes. The selected product's price and weight replace the manual package fields for that item.

= Does this work with HPOS? =

Yes. The tester does not read or change order data and does not depend on the order storage mode.

== Screenshots ==

1. Scenario builder and advanced package options.
2. Matched zone, package summary, and shipping method results.

== Changelog ==

= 1.2.0 =
* Added a responsive scenario builder with quick presets and clearer results.
* Added advanced multi-item package tests with synthetic dimensions and shipping classes.
* Added richer loading, status, rate, and comparison views.

= 1.1.0 =
* Added read-only saved product context for local shipping tests.
* Added product-aware integration and browser coverage.

= 1.0.0 =
* Initial development release.
