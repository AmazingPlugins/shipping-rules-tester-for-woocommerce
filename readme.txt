=== AP Shipping Rules Tester for WooCommerce ===
Contributors: amazingplugins
Tags: woocommerce, shipping, shipping zones, shipping rates
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Test WooCommerce shipping zones and methods with a sample destination and package.

== Description ==

Shipping Rules Tester gives store owners and developers a focused way to check which WooCommerce shipping zone and methods match a package.

Enter a destination and run a quick package check, or open the advanced builder for multiple items, shipping classes, dimensions, and saved products. The plugin runs a read-only test and shows the matched zone, calculated local rates, and methods that did not return a rate.

Methods that need live cart data or an external provider are listed as skipped. Test inputs and results are not saved. Installed extensions can affect WooCommerce calculations through their hooks, so confirm results at checkout.

== Features ==

* Match a sample destination to a WooCommerce shipping zone.
* Test built-in flat rate, free shipping, and local pickup methods.
* Use package value, weight, and quantity in the test.
* Pick a product from a popover with up to ten suggestions. Search runs automatically after three characters.
* Search countries by name, two-letter code, or three-letter code.
* Test its shipping class, dimensions, weight, net price, and tax class.
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
2. Upload the `ap-shipping-rules-tester-for-woocommerce` folder to `/wp-content/plugins/`, or install the ZIP from the Plugins screen.
3. Activate the plugin.
4. Go to **WooCommerce > Shipping Rules Tester**.
5. Enter a sample destination and package, then choose **Test shipping rules**.

== Frequently Asked Questions ==

= Does this change my shipping settings? =

No. The plugin reads the current configuration and calculates a sample result. It does not save changes.

= Can I compare destinations or packages? =

Yes. Run one test, choose **Keep result and test another**, change the inputs, and run the next test. The comparison stays in the current browser tab and is cleared when you leave the page or choose **Clear comparison**.

= Which units does the tester use? =

Package value uses the store currency and excludes product tax. Weight uses the configured WooCommerce unit. Quick fields describe the complete package. The first advanced row preserves those totals; additional rows use per-item values. Each row is labelled accordingly.

= Are external shipping providers contacted? =

The plugin skips external-provider shipping methods and makes no direct external requests. WooCommerce still runs hooks from installed extensions. Those extensions can have their own network activity.

= How are taxes calculated? =

Shipping taxes use the sample destination and package tax classes, or the shop base when configured. Local pickup uses base tax unless that WooCommerce behavior is disabled. Saved tax-inclusive product prices are converted to net package values.

Billing-address tax is marked as untested because this form only collects a shipping address. Tax-inclusive shipping extensions are skipped. Customer exemptions and checkout-specific extension behavior need a real checkout test.

= Does this test real cart contents? =

No. The test uses synthetic items or saved products and builds an unsaved package for the shipping method. It doesn't include coupons, customer roles, subscriptions, or live-cart state. Methods that require that context may need a real checkout test.

= What happens when I select a product? =

The tester reads the saved product's current price, weight, dimensions, shipping class, and tax class. It doesn't save changes. Its net price and weight replace manual fields for that item. Totals appear after you run the test.

Hover over, focus, or tap the product field to see up to ten suggestions. Type at least three characters to search by name, SKU, or ID. Real products appear first, with Synthetic package last. Synthetic stays selected until you choose a product. Each advanced row has its own picker.

= Which country names and codes are shown? =

The picker uses full English ISO 3166-1 names, such as United States of America (US/USA). United Arab Emirates displays AE/ARE/UAE, combining its ISO codes with the familiar abbreviation. All three work in search. Search also accepts the full name or the localized WooCommerce country name. The submitted destination still uses WooCommerce's two-letter code. Countries without an official three-letter code keep their WooCommerce name and code.

The bundled country data contains names and code pairs from the league/iso3166 dataset. No country lookup service is contacted.

= Does this work with HPOS? =

Yes. The tester does not read or change order data and does not depend on the order storage mode.

== Screenshots ==

1. Scenario builder and advanced package options.
2. Matched zone, package summary, and shipping method results.

== Changelog ==

= 1.2.4 =
* Matched translations and the release folder to the assigned WordPress.org slug.
* Corrected the plugin page URL and removed directory screenshots from the installable ZIP.

= 1.2.3 =
* Restyled result actions with icons, keyboard focus states, and mobile-friendly buttons.
* Added a smooth editor reveal that respects reduced-motion preferences.
* Moved kept-result feedback above the editor.
* Fixed alphabetical country ordering and the Kosovo flag.
* Added AE/ARE/UAE labels and search aliases for United Arab Emirates.

= 1.2.2 =
* Replaced catalog search buttons with product-picker popovers in quick and advanced packages.
* Added ten initial suggestions and automatic search after three characters.
* Kept synthetic packages available as the last picker option.
* Added full ISO country names and searchable two-letter/three-letter code pairs without duplicate codes.

= 1.2.1 =
* Fixed shipping tax calculations to use the sample destination and package tax classes.
* Converted saved tax-inclusive product prices to net values and marked unsupported tax contexts.
* Preserved advanced edits and exact package totals when switching modes.
* Fixed validation of inactive fields and clarified saved-product totals.
* Added catalog search for simple products and variations.
* Added calculation and browser regression coverage.

= 1.2.0 =
* Added a responsive scenario builder with quick presets and clearer results.
* Added advanced multi-item package tests with synthetic dimensions and shipping classes.
* Added richer loading, status, rate, and comparison views.

= 1.1.0 =
* Added read-only saved product context for local shipping tests.
* Added product-aware integration and browser coverage.

= 1.0.0 =
* Initial development release.
