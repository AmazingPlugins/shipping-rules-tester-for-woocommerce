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
<div class="wrap">
<div class="apsrt-wrap">
	<div class="apsrt-app">
		<header class="apsrt-hero">
			<div class="apsrt-hero-copy">
				<p class="apsrt-eyebrow"><?php echo esc_html__( 'Shipping diagnostics', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
				<h1><?php echo esc_html__( 'Shipping Rules Tester', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h1>
				<p><?php echo esc_html__( 'Check the shipping zone and supported built-in rates for a sample package. Confirm the result at checkout before changing your shipping setup.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
			</div>
			<div class="apsrt-hero-badges" aria-label="<?php echo esc_attr__( 'Test properties', 'ap-shipping-rules-tester-for-woocommerce' ); ?>">
				<span class="apsrt-badge apsrt-badge-local"><span aria-hidden="true">●</span> <?php echo esc_html__( 'Built-in methods', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
				<span class="apsrt-badge"><?php echo esc_html__( 'Nothing saved', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
			</div>
		</header>

		<div class="apsrt-workspace">
			<main class="apsrt-main">
				<form id="apsrt-form" class="apsrt-form">
					<input type="hidden" name="items" id="apsrt-items-payload" value="">
					<section class="apsrt-form-section">
						<div class="apsrt-section-heading">
							<span class="apsrt-step" aria-hidden="true">01</span>
							<div>
								<h2><?php echo esc_html__( 'Where is it going?', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2>
								<p><?php echo esc_html__( 'Use the destination a customer would enter at checkout.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
							</div>
						</div>
						<div class="apsrt-field-grid apsrt-field-grid-destination">
							<div class="apsrt-destination-step apsrt-country-picker">
								<label class="apsrt-field" for="apsrt-country-search">
									<span><?php echo esc_html__( 'Country', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <em>*</em></span>
									<input id="apsrt-country-search" type="search" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="apsrt-country-options" aria-required="true" placeholder="<?php echo esc_attr__( 'Search by name or code', 'ap-shipping-rules-tester-for-woocommerce' ); ?>" autocomplete="off" required>
								</label>
								<div class="apsrt-country-options" id="apsrt-country-options" role="listbox" aria-label="<?php echo esc_attr__( 'Country options', 'ap-shipping-rules-tester-for-woocommerce' ); ?>" hidden></div>
								<select id="apsrt-country" name="country" autocomplete="country-name" aria-hidden="true" tabindex="-1" hidden>
										<option value=""><?php echo esc_html__( 'Select a country', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
										<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- These are local loop variables in the template.
										foreach ( $countries as $apsrt_code => $apsrt_name ) :
											?>
											<option value="<?php echo esc_attr( $apsrt_code ); ?>"><?php echo esc_html( $apsrt_name ); ?></option>
										<?php endforeach; ?>
									</select>
							</div>
							<div class="apsrt-destination-step" id="apsrt-state-step" hidden>
								<label class="apsrt-field" for="apsrt-state">
									<span><?php echo esc_html__( 'State or province (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
									<select id="apsrt-state" name="state" autocomplete="address-level1" disabled>
										<option value=""><?php echo esc_html__( 'Select a state or province', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
									</select>
								</label>
								<button class="apsrt-location-skip" id="apsrt-skip-state" type="button"><?php echo esc_html__( 'No state or province? Continue', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
							</div>
							<div class="apsrt-destination-step" id="apsrt-postcode-step" hidden>
								<label class="apsrt-field" for="apsrt-postcode">
									<span><?php echo esc_html__( 'Postcode (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
									<input id="apsrt-postcode" type="text" name="postcode" maxlength="20" autocomplete="postal-code" disabled>
								</label>
								<button class="apsrt-location-skip" id="apsrt-skip-postcode" type="button"><?php echo esc_html__( 'No postcode? Continue', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
							</div>
							<label class="apsrt-field apsrt-destination-step" id="apsrt-city-step" for="apsrt-city" hidden>
								<span><?php echo esc_html__( 'City (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
								<input id="apsrt-city" type="text" name="city" maxlength="100" autocomplete="address-level2" disabled>
							</label>
						</div>
					</section>

					<section class="apsrt-form-section apsrt-package-section" id="apsrt-package-section" hidden>
						<div class="apsrt-section-heading">
							<span class="apsrt-step" aria-hidden="true">02</span>
							<div>
								<h2><?php echo esc_html__( 'What is in the package?', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2>
								<p><?php echo esc_html__( 'Start with a quick package, or open the advanced builder for product-level rules.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
							</div>
						</div>

						<div class="apsrt-quick-package">
							<div class="apsrt-field apsrt-field-product">
								<span><?php echo esc_html__( 'Product context (optional)', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
								<select name="product_id" id="apsrt-product" hidden>
									<option value="0"><?php echo esc_html__( 'Synthetic package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
								</select>
								<small><?php echo esc_html__( 'Use a real product when shipping class, dimensions, or product weight matter.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></small>
								<small><?php echo esc_html__( 'Values exclude product tax. Advanced line totals cover all units; new rows use per-item values.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></small>
							</div>
							<label class="apsrt-field">
								<span><?php /* translators: %s: currency code. */ echo esc_html( sprintf( __( 'Package value (%s)', 'ap-shipping-rules-tester-for-woocommerce' ), $currency ) ); ?> <em>*</em></span>
								<input type="number" name="value" min="0" max="100000" step="0.01" value="0" inputmode="decimal" required>
							</label>
							<label class="apsrt-field">
								<span><?php /* translators: %s: weight unit. */ echo esc_html( sprintf( __( 'Total package weight (%s)', 'ap-shipping-rules-tester-for-woocommerce' ), $weight_unit ) ); ?> <em>*</em></span>
								<input type="number" name="weight" min="0" max="100000" step="0.001" value="0" inputmode="decimal" required>
							</label>
							<label class="apsrt-field">
								<span><?php echo esc_html__( 'Item quantity', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <em>*</em></span>
								<input type="number" name="quantity" min="1" max="10000" step="1" value="1" inputmode="numeric" required>
							</label>
						</div>

						<div class="apsrt-preset-row">
							<span class="apsrt-preset-label"><?php echo esc_html__( 'Start with a scenario', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
							<div class="apsrt-preset-buttons" role="group" aria-label="<?php echo esc_attr__( 'Quick scenarios', 'ap-shipping-rules-tester-for-woocommerce' ); ?>">
								<button type="button" class="apsrt-preset" data-preset="standard"><?php echo esc_html__( 'Standard order', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="apsrt-preset" data-preset="free"><?php echo esc_html__( 'Free-shipping check', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="apsrt-preset" data-preset="heavy"><?php echo esc_html__( 'Heavy parcel', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="apsrt-preset" data-preset="pickup"><?php echo esc_html__( 'Local pickup', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
							</div>
						</div>

						<div class="apsrt-advanced-toggle-wrap">
							<button type="button" class="apsrt-advanced-toggle" id="apsrt-advanced-toggle" aria-expanded="false" aria-controls="apsrt-advanced-panel">
								<span class="apsrt-toggle-icon" aria-hidden="true">+</span>
								<span class="apsrt-toggle-label"><?php echo esc_html__( 'Advanced scenario', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
								<small><?php echo esc_html__( 'Multiple items, shipping classes, and dimensions', 'ap-shipping-rules-tester-for-woocommerce' ); ?></small>
							</button>
						</div>

						<div id="apsrt-advanced-panel" class="apsrt-advanced-panel" hidden>
							<p><?php echo esc_html__( 'While this builder is open, its rows replace the quick package above. Collapsing it uses the quick package again and keeps your rows for later.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
							<div class="apsrt-advanced-heading">
								<div>
									<h3><?php echo esc_html__( 'Build a realistic package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h3>
									<p><?php echo esc_html__( 'Add up to ten items. Synthetic items let you test shipping classes and dimensions without creating a product.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
								</div>
								<span class="apsrt-advanced-note"><?php echo esc_html__( 'Still read-only', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
							</div>
							<div id="apsrt-items-list" class="apsrt-items-list">
								<div class="apsrt-item-row" data-item-row>
									<div class="apsrt-item-row-head">
										<strong><span class="apsrt-item-number">1</span> <?php echo esc_html__( 'Item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></strong>
										<button type="button" class="apsrt-remove-item" hidden><?php echo esc_html__( 'Remove item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
									</div>
									<div class="apsrt-item-grid">
										<input type="hidden" class="apsrt-item-source" data-item-field="source" value="custom">
										<div class="apsrt-field apsrt-item-product-field">
											<span><?php echo esc_html__( 'Product or synthetic item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
											<select class="apsrt-item-product" data-item-field="product_id" hidden></select>
										</div>
										<label class="apsrt-field">
											<span><span data-value-label><?php echo esc_html__( 'Value per item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span> (<?php echo esc_html( $currency ); ?>) <em>*</em></span>
											<input type="number" class="apsrt-item-value" data-item-field="value" min="0" max="100000" step="0.01" value="0" inputmode="decimal">
										</label>
										<label class="apsrt-field">
											<span><span data-weight-label><?php echo esc_html__( 'Weight per item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span> (<?php echo esc_html( $weight_unit ); ?>) <em>*</em></span>
											<input type="number" class="apsrt-item-weight" data-item-field="weight" min="0" max="100000" step="0.001" value="0" inputmode="decimal">
										</label>
										<label class="apsrt-field">
											<span><?php echo esc_html__( 'Quantity', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <em>*</em></span>
											<input type="number" class="apsrt-item-quantity" data-item-field="quantity" min="1" max="10000" step="1" value="1" inputmode="numeric">
										</label>
										<label class="apsrt-field">
											<span><?php echo esc_html__( 'Shipping class', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span>
											<select class="apsrt-item-shipping-class" data-item-field="shipping_class_id">
												<option value="0"><?php echo esc_html__( 'No override', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
											</select>
										</label>
										<div class="apsrt-item-dimensions">
																						<span class="apsrt-field-label"><?php echo esc_html__( 'Dimensions', 'ap-shipping-rules-tester-for-woocommerce' ); ?> <small>(<?php echo esc_html( $dimension_unit ); ?> store units)</small></span>
											<div class="apsrt-dimension-fields">
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Length', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="length" min="0" max="10000" step="0.001" value="0" placeholder="L"></label>
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Width', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="width" min="0" max="10000" step="0.001" value="0" placeholder="W"></label>
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Height', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="height" min="0" max="10000" step="0.001" value="0" placeholder="H"></label>
											</div>
										</div>
									</div>
								</div>
							</div>
							<button type="button" class="apsrt-add-item" id="apsrt-add-item"><span aria-hidden="true">+</span> <?php echo esc_html__( 'Add another item', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
						</div>
					</section>

					<div class="apsrt-form-actions" id="apsrt-form-actions" hidden>
						<button type="submit" class="button button-primary apsrt-submit" id="apsrt-submit"><span class="apsrt-submit-label"><?php echo esc_html__( 'Test shipping rules', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><span class="apsrt-submit-arrow" aria-hidden="true">→</span></button>
						<button type="button" class="button apsrt-reset" id="apsrt-reset"><?php echo esc_html__( 'Reset', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
					</div>
				</form>
				<div id="apsrt-status" class="apsrt-status" role="status" aria-live="polite"></div>
				<div class="apsrt-result-actions" id="apsrt-result-actions" aria-label="<?php echo esc_attr__( 'Result actions', 'ap-shipping-rules-tester-for-woocommerce' ); ?>" hidden>
					<button type="button" class="button apsrt-action-edit" id="apsrt-edit-parameters"><span class="dashicons dashicons-edit" aria-hidden="true"></span><span><?php echo esc_html__( 'Edit parameters', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span></button>
					<button type="button" class="button apsrt-reset" id="apsrt-result-reset"><span class="dashicons dashicons-image-rotate" aria-hidden="true"></span><span><?php echo esc_html__( 'Reset', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span></button>
					<button type="button" class="button apsrt-keep" id="apsrt-keep" hidden><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span><?php echo esc_html__( 'Keep result and test another', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span></button>
					<button type="button" class="button apsrt-clear" id="apsrt-clear" hidden><?php echo esc_html__( 'Clear comparison', 'ap-shipping-rules-tester-for-woocommerce' ); ?></button>
				</div>
				<div id="apsrt-results" class="apsrt-results" hidden></div>
			</main>

			<aside class="apsrt-sidebar">
					<section class="apsrt-side-card apsrt-summary-card" id="apsrt-summary-card" aria-labelledby="apsrt-summary-title" hidden>
					<div class="apsrt-side-card-heading"><span class="apsrt-side-icon" aria-hidden="true">↗</span><h2 id="apsrt-summary-title"><?php echo esc_html__( 'Live scenario', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2></div>
					<p class="apsrt-side-description"><?php echo esc_html__( 'Your test updates here as you work.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
					<div id="apsrt-live-summary" class="apsrt-live-summary">
						<div><span><?php echo esc_html__( 'Destination', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><strong id="apsrt-summary-destination"><?php echo esc_html__( 'Choose a country', 'ap-shipping-rules-tester-for-woocommerce' ); ?></strong></div>
						<div><span><?php echo esc_html__( 'Package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><strong id="apsrt-summary-package"><?php echo esc_html__( 'Synthetic package', 'ap-shipping-rules-tester-for-woocommerce' ); ?></strong></div>
						<div><span><?php echo esc_html__( 'Totals', 'ap-shipping-rules-tester-for-woocommerce' ); ?></span><strong id="apsrt-summary-totals">0 <?php echo esc_html( $currency ); ?> · 0 <?php echo esc_html( $weight_unit ); ?></strong></div>
					</div>
				</section>

				<section class="apsrt-side-card">
					<div class="apsrt-side-card-heading"><span class="apsrt-side-icon apsrt-side-icon-shield" aria-hidden="true">✓</span><h2><?php echo esc_html__( 'What this test does', 'ap-shipping-rules-tester-for-woocommerce' ); ?></h2></div>
					<ul class="apsrt-check-list">
						<li><?php echo esc_html__( 'Matches the destination to your configured zone', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Calculates supported built-in methods', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Shows why a method was skipped', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Does not save test inputs or results', 'ap-shipping-rules-tester-for-woocommerce' ); ?></li>
					</ul>
					<p class="apsrt-side-description"><?php echo esc_html__( 'Coupons, billing addresses, customer exemptions, and live-cart rules need a checkout test. Installed extensions can affect WooCommerce calculations and run their own hooks.', 'ap-shipping-rules-tester-for-woocommerce' ); ?></p>
				</section>
			</aside>
		</div>

		<select id="apsrt-shipping-class-source" class="apsrt-hidden-source" aria-hidden="true" tabindex="-1">
			<option value="0"><?php echo esc_html__( 'No override', 'ap-shipping-rules-tester-for-woocommerce' ); ?></option>
			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This is a local loop variable in the template.
			foreach ( $shipping_classes as $apsrt_shipping_class ) :
				?>
				<option value="<?php echo esc_attr( (string) absint( $apsrt_shipping_class->term_id ) ); ?>"><?php echo esc_html( $apsrt_shipping_class->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
</div>
