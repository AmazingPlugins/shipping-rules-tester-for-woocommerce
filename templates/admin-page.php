<?php
/**
 * Shipping tester admin template.
 *
 * @package ShippingRulesTester
 * @var array $countries WooCommerce countries.
 * @var array $products Available WooCommerce products.
 * @var array $shipping_classes Available product shipping classes.
 * @var string $currency Store currency code.
 * @var string $weight_unit Store weight unit.
 * @var string $dimension_unit Store dimension unit.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap srt-wrap">
	<div class="srt-app">
		<header class="srt-hero">
			<div class="srt-hero-copy">
				<div class="notice notice-info inline" role="note">
					<p><strong><?php echo esc_html__( 'About other admin notices:', 'shipping-rules-tester-for-woocommerce' ); ?></strong> <?php echo esc_html__( 'Scheduled Actions and other WordPress or WooCommerce notices are unrelated to Shipping Rules Tester. This plugin does not schedule tasks or send notifications.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
				</div>
				<p class="srt-eyebrow"><?php echo esc_html__( 'Shipping diagnostics', 'shipping-rules-tester-for-woocommerce' ); ?></p>
				<h1><?php echo esc_html__( 'Shipping Rules Tester', 'shipping-rules-tester-for-woocommerce' ); ?></h1>
				<p><?php echo esc_html__( 'See exactly which zone and rates WooCommerce will choose for a package before a customer reaches checkout.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
			</div>
			<div class="srt-hero-badges" aria-label="<?php echo esc_attr__( 'Test properties', 'shipping-rules-tester-for-woocommerce' ); ?>">
				<span class="srt-badge srt-badge-local"><span aria-hidden="true">●</span> <?php echo esc_html__( 'Local only', 'shipping-rules-tester-for-woocommerce' ); ?></span>
				<span class="srt-badge"><?php echo esc_html__( 'Nothing saved', 'shipping-rules-tester-for-woocommerce' ); ?></span>
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
								<h2><?php echo esc_html__( 'Where is it going?', 'shipping-rules-tester-for-woocommerce' ); ?></h2>
								<p><?php echo esc_html__( 'Use the destination a customer would enter at checkout.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
							</div>
						</div>
						<div class="srt-field-grid srt-field-grid-destination">
							<label class="srt-field srt-field-wide">
								<span><?php echo esc_html__( 'Country', 'shipping-rules-tester-for-woocommerce' ); ?> <em>*</em></span>
								<select name="country" required>
									<option value=""><?php echo esc_html__( 'Select a country', 'shipping-rules-tester-for-woocommerce' ); ?></option>
									<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- These are local loop variables in the template.
									foreach ( $countries as $srt_code => $srt_name ) :
										?>
										<option value="<?php echo esc_attr( $srt_code ); ?>"><?php echo esc_html( $srt_name ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="srt-field">
								<span><?php echo esc_html__( 'State or province', 'shipping-rules-tester-for-woocommerce' ); ?></span>
								<input type="text" name="state" maxlength="100" autocomplete="address-level1">
							</label>
							<label class="srt-field">
								<span><?php echo esc_html__( 'Postcode', 'shipping-rules-tester-for-woocommerce' ); ?></span>
								<input type="text" name="postcode" maxlength="20" autocomplete="postal-code">
							</label>
							<label class="srt-field">
								<span><?php echo esc_html__( 'City', 'shipping-rules-tester-for-woocommerce' ); ?></span>
								<input type="text" name="city" maxlength="100" autocomplete="address-level2">
							</label>
						</div>
					</section>

					<section class="srt-form-section srt-package-section">
						<div class="srt-section-heading">
							<span class="srt-step" aria-hidden="true">02</span>
							<div>
								<h2><?php echo esc_html__( 'What is in the package?', 'shipping-rules-tester-for-woocommerce' ); ?></h2>
								<p><?php echo esc_html__( 'Start with a quick package, or open the advanced builder for product-level rules.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
							</div>
						</div>

						<div class="srt-quick-package">
							<label class="srt-field srt-field-product">
								<span><?php echo esc_html__( 'Product context', 'shipping-rules-tester-for-woocommerce' ); ?></span>
								<select name="product_id" id="srt-product">
									<option value="0"><?php echo esc_html__( 'Synthetic package', 'shipping-rules-tester-for-woocommerce' ); ?></option>
									<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This is a local loop variable in the template.
									foreach ( $products as $srt_product ) :
										?>
										<?php if ( ! is_object( $srt_product ) || ! method_exists( $srt_product, 'get_id' ) || ! method_exists( $srt_product, 'get_name' ) ) : ?>
											<?php continue; ?>
										<?php endif; ?>
										<option value="<?php echo esc_attr( (string) absint( $srt_product->get_id() ) ); ?>">
											<?php echo esc_html( sprintf( '%s (#%d)', $srt_product->get_name(), absint( $srt_product->get_id() ) ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<small><?php echo esc_html__( 'Use a real product when shipping class, dimensions, or product weight matter.', 'shipping-rules-tester-for-woocommerce' ); ?></small>
							</label>
							<div class="srt-field-grid srt-field-grid-package">
								<label class="srt-field">
									<span><?php /* translators: %s: currency code. */ echo esc_html( sprintf( __( 'Package value (%s)', 'shipping-rules-tester-for-woocommerce' ), $currency ) ); ?></span>
									<input type="number" name="value" min="0" max="100000" step="0.01" value="0" inputmode="decimal">
								</label>
								<label class="srt-field">
										<span><?php /* translators: %s: weight unit. */ echo esc_html( sprintf( __( 'Total package weight (%s)', 'shipping-rules-tester-for-woocommerce' ), $weight_unit ) ); ?></span>
									<input type="number" name="weight" min="0" max="100000" step="0.001" value="0" inputmode="decimal">
								</label>
								<label class="srt-field">
									<span><?php echo esc_html__( 'Item quantity', 'shipping-rules-tester-for-woocommerce' ); ?></span>
									<input type="number" name="quantity" min="1" max="10000" step="1" value="1" inputmode="numeric">
								</label>
							</div>
						</div>

						<div class="srt-preset-row">
							<span class="srt-preset-label"><?php echo esc_html__( 'Start with a scenario', 'shipping-rules-tester-for-woocommerce' ); ?></span>
							<div class="srt-preset-buttons" role="group" aria-label="<?php echo esc_attr__( 'Quick scenarios', 'shipping-rules-tester-for-woocommerce' ); ?>">
								<button type="button" class="srt-preset" data-preset="standard"><?php echo esc_html__( 'Standard order', 'shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="srt-preset" data-preset="free"><?php echo esc_html__( 'Free-shipping check', 'shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="srt-preset" data-preset="heavy"><?php echo esc_html__( 'Heavy parcel', 'shipping-rules-tester-for-woocommerce' ); ?></button>
								<button type="button" class="srt-preset" data-preset="pickup"><?php echo esc_html__( 'Local pickup', 'shipping-rules-tester-for-woocommerce' ); ?></button>
							</div>
						</div>

						<div class="srt-advanced-toggle-wrap">
							<button type="button" class="srt-advanced-toggle" id="srt-advanced-toggle" aria-expanded="false" aria-controls="srt-advanced-panel">
								<span class="srt-toggle-icon" aria-hidden="true">+</span>
								<span class="srt-toggle-label"><?php echo esc_html__( 'Advanced scenario', 'shipping-rules-tester-for-woocommerce' ); ?></span>
								<small><?php echo esc_html__( 'Multiple items, shipping classes, and dimensions', 'shipping-rules-tester-for-woocommerce' ); ?></small>
							</button>
						</div>

						<div id="srt-advanced-panel" class="srt-advanced-panel" hidden>
							<div class="srt-advanced-heading">
								<div>
									<h3><?php echo esc_html__( 'Build a realistic package', 'shipping-rules-tester-for-woocommerce' ); ?></h3>
									<p><?php echo esc_html__( 'Add up to ten items. Synthetic items let you test shipping classes and dimensions without creating a product.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
								</div>
								<span class="srt-advanced-note"><?php echo esc_html__( 'Still read-only', 'shipping-rules-tester-for-woocommerce' ); ?></span>
							</div>
							<div id="srt-items-list" class="srt-items-list">
								<div class="srt-item-row" data-item-row>
									<div class="srt-item-row-head">
										<strong><span class="srt-item-number">1</span> <?php echo esc_html__( 'Item', 'shipping-rules-tester-for-woocommerce' ); ?></strong>
										<button type="button" class="srt-remove-item" hidden><?php echo esc_html__( 'Remove item', 'shipping-rules-tester-for-woocommerce' ); ?></button>
									</div>
									<div class="srt-item-grid">
										<label class="srt-field srt-item-source-field">
											<span><?php echo esc_html__( 'Item type', 'shipping-rules-tester-for-woocommerce' ); ?></span>
											<select class="srt-item-source" data-item-field="source">
												<option value="custom"><?php echo esc_html__( 'Synthetic item', 'shipping-rules-tester-for-woocommerce' ); ?></option>
												<option value="product"><?php echo esc_html__( 'Saved product', 'shipping-rules-tester-for-woocommerce' ); ?></option>
											</select>
										</label>
										<label class="srt-field srt-item-product-field" hidden>
											<span><?php echo esc_html__( 'Saved product', 'shipping-rules-tester-for-woocommerce' ); ?></span>
											<select class="srt-item-product" data-item-field="product_id"></select>
										</label>
										<label class="srt-field">
											<span><?php /* translators: %s: currency code. */ echo esc_html( sprintf( __( 'Item value (%s)', 'shipping-rules-tester-for-woocommerce' ), $currency ) ); ?></span>
											<input type="number" class="srt-item-value" data-item-field="value" min="0" max="100000" step="0.01" value="0" inputmode="decimal">
										</label>
										<label class="srt-field">
											<span><?php /* translators: %s: weight unit. */ echo esc_html( sprintf( __( 'Item weight (%s)', 'shipping-rules-tester-for-woocommerce' ), $weight_unit ) ); ?></span>
											<input type="number" class="srt-item-weight" data-item-field="weight" min="0" max="100000" step="0.001" value="0" inputmode="decimal">
										</label>
										<label class="srt-field">
											<span><?php echo esc_html__( 'Quantity', 'shipping-rules-tester-for-woocommerce' ); ?></span>
											<input type="number" class="srt-item-quantity" data-item-field="quantity" min="1" max="10000" step="1" value="1" inputmode="numeric">
										</label>
										<label class="srt-field">
											<span><?php echo esc_html__( 'Shipping class', 'shipping-rules-tester-for-woocommerce' ); ?></span>
											<select class="srt-item-shipping-class" data-item-field="shipping_class_id">
												<option value="0"><?php echo esc_html__( 'No override', 'shipping-rules-tester-for-woocommerce' ); ?></option>
											</select>
										</label>
										<div class="srt-item-dimensions">
																						<span class="srt-field-label"><?php echo esc_html__( 'Dimensions', 'shipping-rules-tester-for-woocommerce' ); ?> <small>(<?php echo esc_html( $dimension_unit ); ?> store units)</small></span>
											<div class="srt-dimension-fields">
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Length', 'shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="length" min="0" max="10000" step="0.001" value="0" placeholder="L"></label>
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Width', 'shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="width" min="0" max="10000" step="0.001" value="0" placeholder="W"></label>
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Height', 'shipping-rules-tester-for-woocommerce' ); ?></span><input type="number" data-item-field="height" min="0" max="10000" step="0.001" value="0" placeholder="H"></label>
											</div>
										</div>
									</div>
								</div>
							</div>
							<button type="button" class="srt-add-item" id="srt-add-item"><span aria-hidden="true">+</span> <?php echo esc_html__( 'Add another item', 'shipping-rules-tester-for-woocommerce' ); ?></button>
						</div>
					</section>

					<div class="srt-form-actions">
						<button type="submit" class="button button-primary srt-submit" id="srt-submit"><span class="srt-submit-label"><?php echo esc_html__( 'Test shipping rules', 'shipping-rules-tester-for-woocommerce' ); ?></span><span class="srt-submit-arrow" aria-hidden="true">→</span></button>
						<button type="button" class="button srt-reset" id="srt-reset"><?php echo esc_html__( 'Reset', 'shipping-rules-tester-for-woocommerce' ); ?></button>
						<button type="button" class="button srt-keep" id="srt-keep" hidden><?php echo esc_html__( 'Keep result and test another', 'shipping-rules-tester-for-woocommerce' ); ?></button>
						<button type="button" class="button srt-clear" id="srt-clear" hidden><?php echo esc_html__( 'Clear comparison', 'shipping-rules-tester-for-woocommerce' ); ?></button>
					</div>
				</form>
				<div id="srt-status" class="srt-status" role="status" aria-live="polite"></div>
				<div id="srt-results" class="srt-results" hidden></div>
			</main>

			<aside class="srt-sidebar">
				<section class="srt-side-card srt-summary-card" aria-labelledby="srt-summary-title">
					<div class="srt-side-card-heading"><span class="srt-side-icon" aria-hidden="true">↗</span><h2 id="srt-summary-title"><?php echo esc_html__( 'Live scenario', 'shipping-rules-tester-for-woocommerce' ); ?></h2></div>
					<p class="srt-side-description"><?php echo esc_html__( 'Your test updates here as you work.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
					<div id="srt-live-summary" class="srt-live-summary">
						<div><span><?php echo esc_html__( 'Destination', 'shipping-rules-tester-for-woocommerce' ); ?></span><strong id="srt-summary-destination"><?php echo esc_html__( 'Choose a country', 'shipping-rules-tester-for-woocommerce' ); ?></strong></div>
						<div><span><?php echo esc_html__( 'Package', 'shipping-rules-tester-for-woocommerce' ); ?></span><strong id="srt-summary-package"><?php echo esc_html__( 'Synthetic package', 'shipping-rules-tester-for-woocommerce' ); ?></strong></div>
						<div><span><?php echo esc_html__( 'Totals', 'shipping-rules-tester-for-woocommerce' ); ?></span><strong id="srt-summary-totals">0 <?php echo esc_html( $currency ); ?> · 0 <?php echo esc_html( $weight_unit ); ?></strong></div>
					</div>
				</section>

				<section class="srt-side-card">
					<div class="srt-side-card-heading"><span class="srt-side-icon srt-side-icon-shield" aria-hidden="true">✓</span><h2><?php echo esc_html__( 'What this test does', 'shipping-rules-tester-for-woocommerce' ); ?></h2></div>
					<ul class="srt-check-list">
						<li><?php echo esc_html__( 'Matches the destination to your configured zone', 'shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Runs safe built-in methods locally', 'shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Shows why a method was skipped', 'shipping-rules-tester-for-woocommerce' ); ?></li>
						<li><?php echo esc_html__( 'Changes nothing on your store', 'shipping-rules-tester-for-woocommerce' ); ?></li>
					</ul>
				</section>
			</aside>
		</div>

		<select id="srt-shipping-class-source" class="srt-hidden-source" aria-hidden="true" tabindex="-1">
			<option value="0"><?php echo esc_html__( 'No override', 'shipping-rules-tester-for-woocommerce' ); ?></option>
			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This is a local loop variable in the template.
			foreach ( $shipping_classes as $srt_shipping_class ) :
				?>
				<option value="<?php echo esc_attr( (string) absint( $srt_shipping_class->term_id ) ); ?>"><?php echo esc_html( $srt_shipping_class->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
