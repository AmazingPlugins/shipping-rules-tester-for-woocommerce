<?php
/**
 * Shipping tester admin template.
 *
 * @package ShippingRulesTester
 * @var array $countries WooCommerce countries.
 * @var array $shipping_classes Available product shipping classes.
 * @var string $currency Store currency code.
 * @var string $weight_unit Store weight unit.
 * @var string $dimension_unit Store dimension unit.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="notice notice-info">
	<p><strong><?php echo esc_html__( 'About other admin notices:', 'ap-shipping-rules-tester-for-woocommerce' ); ?></strong> <?php echo esc_html__( 'Scheduled Actions and other WordPress or WooCommerce notices are unrelated to Shipping Rules Tester. This plugin does not schedule tasks or send notifications.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
</div>
<div class="wrap srt-wrap">
	<div class="srt-app">
		<header class="srt-hero">
			<div class="srt-hero-copy">
				<p class="srt-eyebrow"><?php echo esc_html__( 'Shipping diagnostics', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
				<h1><?php echo esc_html__( 'Shipping Rules Tester', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h1>
				<p><?php echo esc_html__( 'Check the shipping zone and supported built-in rates for a sample package. Confirm the result at checkout before changing your shipping setup.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
			</div>
			<div class="srt-hero-badges" aria-label="<?php echo esc_attr__( 'Test properties', 'ap-shipping-rules-tester-for-woocommerce' ); ?>">
				<span class="srt-badge srt-badge-local"><span aria-hidden="true">●</span> <?php echo esc_html__( 'Built-in methods', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
				<span class="srt-badge"><?php echo esc_html__( 'Nothing saved', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
			</div>
		</header>

		<div class="srt-workspace">
			<main class="srt-main">
				<form id="srt-form" class="srt-form">
					<input type="hidden" name="items" id="srt-items-payload" value="">
					<section class="srt-form-section">
						<div class="srt-section-heading">
							<span class="srt-step" aria-hidden="true">01</span>
							<div>
								<h2><?php echo esc_html__( 'Where is it going?', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2>
								<p><?php echo esc_html__( 'Use the destination a customer would enter at checkout.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
							</div>
						</div>
						<div class="srt-field-grid srt-field-grid-destination">
							<div class="srt-destination-step srt-country-picker">
								<label class="srt-field" for="srt-country-search">
									<span><?php echo esc_html__( 'Country', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <em>*</em></span>
									<input id="srt-country-search" type="search" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="srt-country-options" aria-required="true" placeholder="<?php echo esc_attr__( 'Search by name or code', 'ap-shipping-rules-tester-for-woocommerce' ); ?>" autocomplete="off" required>
								</label>
								<div class="srt-country-options" id="srt-country-options" role="listbox" aria-label="<?php echo esc_attr__( 'Country options', 'ap-shipping-rules-tester-for-woocommerce' ); ?>" hidden></div>
								<select id="srt-country" name="country" autocomplete="country-name" aria-hidden="true" tabindex="-1" hidden>
										<option value=""><?php echo esc_html__( 'Select a country', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
										<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- These are local loop variables in the template.
										foreach ( $countries as $srt_code => $srt_name ) :
											?>
											<option value="<?php echo esc_attr( $srt_code ); ?>"><?php echo esc_html( $srt_name ); ?></option>
										<?php endforeach; ?>
									</select>
							</div>
							<div class="srt-destination-step" id="srt-state-step" hidden>
								<label class="srt-field" for="srt-state">
									<span><?php echo esc_html__( 'State or province (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
									<select id="srt-state" name="state" autocomplete="address-level1" disabled>
										<option value=""><?php echo esc_html__( 'Select a state or province', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
									</select>
								</label>
								<button class="srt-location-skip" id="srt-skip-state" type="button"><?php echo esc_html__( 'No state or province? Continue', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
							</div>
							<div class="srt-destination-step" id="srt-postcode-step" hidden>
								<label class="srt-field" for="srt-postcode">
									<span><?php echo esc_html__( 'Postcode (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
									<input id="srt-postcode" type="text" name="postcode" maxlength="20" autocomplete="postal-code" disabled>
								</label>
								<button class="srt-location-skip" id="srt-skip-postcode" type="button"><?php echo esc_html__( 'No postcode? Continue', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
							</div>
							<label class="srt-field srt-destination-step" id="srt-city-step" for="srt-city" hidden>
								<span><?php echo esc_html__( 'City (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
								<input id="srt-city" type="text" name="city" maxlength="100" autocomplete="address-level2" disabled>
							</label>
						</div>
					</section>

					<section class="srt-form-section srt-package-section" id="srt-package-section" hidden>
						<div class="srt-section-heading">
							<span class="srt-step" aria-hidden="true">02</span>
							<div>
								<h2><?php echo esc_html__( 'What is in the package?', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2>
								<p><?php echo esc_html__( 'Start with a quick package, or open the advanced builder for product-level rules.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
							</div>
						</div>

						<div class="srt-quick-package">
							<div class="srt-field srt-field-product">
								<span><?php echo esc_html__( 'Product context (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
								<select name="product_id" id="srt-product" hidden>
									<option value="0"><?php echo esc_html__( 'Synthetic package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
								</select>
								<small><?php echo esc_html__( 'Use a real product when shipping class, dimensions, or product weight matter.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></small>
								<small><?php echo esc_html__( 'Values exclude product tax. Advanced line totals cover all units; new rows use per-item values.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></small>
							</div>
							<label class="srt-field">
								<span><?php /* translators: %s: currency code. */ echo esc_html( sprintf( __( 'Package value (%s)', 'ap-shipping-rules-tester-for-woocommerce' ), $currency ) ); ?> <em>*</em></span>
								<input type="number" name="value" min="0" max="100000" step="0.01" value="0" inputmode="decimal" required>
							</label>
							<label class="srt-field">
								<span><?php /* translators: %s: weight unit. */ echo esc_html( sprintf( __( 'Total package weight (%s)', 'ap-shipping-rules-tester-for-woocommerce' ), $weight_unit ) ); ?> <em>*</em></span>
								<input type="number" name="weight" min="0" max="100000" step="0.001" value="0" inputmode="decimal" required>
							</label>
							<label class="srt-field">
								<span><?php echo esc_html__( 'Item quantity', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <em>*</em></span>
								<input type="number" name="quantity" min="1" max="10000" step="1" value="1" inputmode="numeric" required>
							</label>
						</div>

						<div class="srt-preset-row">
							<span class="srt-preset-label"><?php echo esc_html__( 'Start with a scenario', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
							<div class="srt-preset-buttons" role="group" aria-label="<?php echo esc_attr__( 'Quick scenarios', 'ap-shipping-rules-tester-for-woocommerce' ); ?>">
								<button type="button" class="srt-preset" data-preset="standard"><?php echo esc_html__( 'Standard order', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="srt-preset" data-preset="free"><?php echo esc_html__( 'Free-shipping check', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="srt-preset" data-preset="heavy"><?php echo esc_html__( 'Heavy parcel', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="srt-preset" data-preset="pickup"><?php echo esc_html__( 'Local pickup', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
							</div>
						</div>

						<div class="srt-advanced-toggle-wrap">
							<button type="button" class="srt-advanced-toggle" id="srt-advanced-toggle" aria-expanded="false" aria-controls="srt-advanced-panel">
								<span class="srt-toggle-icon" aria-hidden="true">+</span>
								<span class="srt-toggle-label"><?php echo esc_html__( 'Advanced scenario', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
								<small><?php echo esc_html__( 'Multiple items, shipping classes, and dimensions', 'ap-shipping-rules-tester-for-woocommerce' ); ?></small>
							</button>
						</div>

						<div id="srt-advanced-panel" class="srt-advanced-panel" hidden>
							<p><?php echo esc_html__( 'While this builder is open, its rows replace the quick package above. Collapsing it uses the quick package again and keeps your rows for later.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
							<div class="srt-advanced-heading">
								<div>
									<h3><?php echo esc_html__( 'Build a realistic package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h3>
									<p><?php echo esc_html__( 'Add up to ten items. Synthetic items let you test shipping classes and dimensions without creating a product.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
								</div>
								<span class="srt-advanced-note"><?php echo esc_html__( 'Still read-only', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
							</div>
							<div id="srt-items-list" class="srt-items-list">
								<div class="srt-item-row" data-item-row>
									<div class="srt-item-row-head">
										<strong><span class="srt-item-number">1</span> <?php echo esc_html__( 'Item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></strong>
										<button type="button" class="srt-remove-item" hidden><?php echo esc_html__( 'Remove item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
									</div>
									<div class="srt-item-grid">
										<input type="hidden" class="srt-item-source" data-item-field="source" value="custom">
										<div class="srt-field srt-item-product-field">
											<span><?php echo esc_html__( 'Product or synthetic item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
											<select class="srt-item-product" data-item-field="product_id" hidden></select>
										</div>
										<label class="srt-field">
											<span><span data-value-label><?php echo esc_html__( 'Value per item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span> (<?php echo esc_html( $currency ); ?>) <em>*</em></span>
											<input type="number" class="srt-item-value" data-item-field="value" min="0" max="100000" step="0.01" value="0" inputmode="decimal">
										</label>
										<label class="srt-field">
											<span><span data-weight-label><?php echo esc_html__( 'Weight per item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span> (<?php echo esc_html( $weight_unit ); ?>) <em>*</em></span>
											<input type="number" class="srt-item-weight" data-item-field="weight" min="0" max="100000" step="0.001" value="0" inputmode="decimal">
										</label>
										<label class="srt-field">
											<span><?php echo esc_html__( 'Quantity', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <em>*</em></span>
											<input type="number" class="srt-item-quantity" data-item-field="quantity" min="1" max="10000" step="1" value="1" inputmode="numeric">
										</label>
										<label class="srt-field">
											<span><?php echo esc_html__( 'Shipping class', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
											<select class="srt-item-shipping-class" data-item-field="shipping_class_id">
												<option value="0"><?php echo esc_html__( 'No override', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
											</select>
										</label>
										<div class="srt-item-dimensions">
																						<span class="srt-field-label"><?php echo esc_html__( 'Dimensions', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <small>(<?php echo esc_html( $dimension_unit ); ?> store units)</small></span>
											<div class="srt-dimension-fields">
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Length', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="length" min="0" max="10000" step="0.001" value="0" placeholder="L"></label>
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Width', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="width" min="0" max="10000" step="0.001" value="0" placeholder="W"></label>
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Height', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="height" min="0" max="10000" step="0.001" value="0" placeholder="H"></label>
											</div>
										</div>
									</div>
								</div>
							</div>
							<button type="button" class="srt-add-item" id="srt-add-item"><span aria-hidden="true">+</span> <?php echo esc_html__( 'Add another item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
						</div>
					</section>

					<div class="srt-form-actions" id="srt-form-actions" hidden>
						<button type="submit" class="button button-primary srt-submit" id="srt-submit"><span class="srt-submit-label"><?php echo esc_html__( 'Test shipping rules', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><span class="srt-submit-arrow" aria-hidden="true">→</span></button>
						<button type="button" class="button srt-reset" id="srt-reset"><?php echo esc_html__( 'Reset', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
					</div>
				</form>
				<div id="srt-status" class="srt-status" role="status" aria-live="polite"></div>
				<div class="srt-result-actions" id="srt-result-actions" aria-label="<?php echo esc_attr__( 'Result actions', 'ap-shipping-rules-tester-for-woocommerce' ); ?>" hidden>
					<button type="button" class="button srt-action-edit" id="srt-edit-parameters"><span class="dashicons dashicons-edit" aria-hidden="true"></span><span><?php echo esc_html__( 'Edit parameters', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span></button>
					<button type="button" class="button srt-reset" id="srt-result-reset"><span class="dashicons dashicons-image-rotate" aria-hidden="true"></span><span><?php echo esc_html__( 'Reset', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span></button>
					<button type="button" class="button srt-keep" id="srt-keep" hidden><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span><?php echo esc_html__( 'Keep result and test another', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span></button>
					<button type="button" class="button srt-clear" id="srt-clear" hidden><?php echo esc_html__( 'Clear comparison', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
				</div>
				<div id="srt-results" class="srt-results" hidden></div>
			</main>

			<aside class="srt-sidebar">
					<section class="srt-side-card srt-summary-card" id="srt-summary-card" aria-labelledby="srt-summary-title" hidden>
					<div class="srt-side-card-heading"><span class="srt-side-icon" aria-hidden="true">↗</span><h2 id="srt-summary-title"><?php echo esc_html__( 'Live scenario', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2></div>
					<p class="srt-side-description"><?php echo esc_html__( 'Your test updates here as you work.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
					<div id="srt-live-summary" class="srt-live-summary">
						<div><span><?php echo esc_html__( 'Destination', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><strong id="srt-summary-destination"><?php echo esc_html__( 'Choose a country', 'ap-shipping-rules-tester-for-woocommerce' ); ?></strong></div>
						<div><span><?php echo esc_html__( 'Package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><strong id="srt-summary-package"><?php echo esc_html__( 'Synthetic package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></strong></div>
						<div><span><?php echo esc_html__( 'Totals', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><strong id="srt-summary-totals">0 <?php echo esc_html( $currency ); ?> · 0 <?php echo esc_html( $weight_unit ); ?></strong></div>
					</div>
				</section>

				<section class="srt-side-card">
					<div class="srt-side-card-heading"><span class="srt-side-icon srt-side-icon-shield" aria-hidden="true">✓</span><h2><?php echo esc_html__( 'What this test does', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2></div>
					<ul class="srt-check-list">
						<li><?php echo esc_html__( 'Matches the destination to your configured zone', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Calculates supported built-in methods', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Shows why a method was skipped', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Does not save test inputs or results', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
					</ul>
					<p class="srt-side-description"><?php echo esc_html__( 'Coupons, billing addresses, customer exemptions, and live-cart rules need a checkout test. Installed extensions can affect WooCommerce calculations and run their own hooks.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
				</section>
			</aside>
		</div>

		<select id="srt-shipping-class-source" class="srt-hidden-source" aria-hidden="true" tabindex="-1">
			<option value="0"><?php echo esc_html__( 'No override', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This is a local loop variable in the template.
			foreach ( $shipping_classes as $srt_shipping_class ) :
				?>
				<option value="<?php echo esc_attr( (string) absint( $srt_shipping_class->term_id ) ); ?>"><?php echo esc_html( $srt_shipping_class->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
