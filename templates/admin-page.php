<?php
/**
 * Shipping tester admin template.
 *
 * @package ShippingRulesTester
 * @var array $countries WooCommerce countries.
 * @var array $products Available WooCommerce products.
 * @var string $currency Store currency code.
 * @var string $weight_unit Store weight unit.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap srt-wrap">
	<h1><?php echo esc_html__( 'Shipping Rules Tester', 'shipping-rules-tester-for-woocommerce' ); ?></h1>
	<p><?php echo esc_html__( 'Test which WooCommerce shipping zone and methods match a sample destination and package.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
	<div class="notice notice-warning inline"><p><?php echo esc_html__( 'This is a read-only local test. External rate methods are skipped. Results are not saved.', 'shipping-rules-tester-for-woocommerce' ); ?></p></div>
	<form id="srt-form" class="srt-form">
		<fieldset>
			<legend><?php echo esc_html__( 'Destination', 'shipping-rules-tester-for-woocommerce' ); ?></legend>
			<div class="srt-grid">
				<label><?php echo esc_html__( 'Country', 'shipping-rules-tester-for-woocommerce' ); ?>
					<select name="country" required>
						<option value=""><?php echo esc_html__( 'Select a country', 'shipping-rules-tester-for-woocommerce' ); ?></option>
						<?php
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- These are local loop variables in the template.
						foreach ( $countries as $code => $name ) :
							?>
							<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php echo esc_html__( 'State or province', 'shipping-rules-tester-for-woocommerce' ); ?>
					<input type="text" name="state" maxlength="100">
				</label>
				<label><?php echo esc_html__( 'Postcode', 'shipping-rules-tester-for-woocommerce' ); ?>
					<input type="text" name="postcode" maxlength="20">
				</label>
				<label><?php echo esc_html__( 'City', 'shipping-rules-tester-for-woocommerce' ); ?>
					<input type="text" name="city" maxlength="100">
				</label>
			</div>
		</fieldset>
		<fieldset>
			<legend><?php echo esc_html__( 'Sample package', 'shipping-rules-tester-for-woocommerce' ); ?></legend>
			<p class="description"><?php echo esc_html__( 'Use a saved product to include its shipping class, dimensions, weight, price, and tax class. Leave it as a synthetic package to enter totals manually.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
			<label><?php echo esc_html__( 'Product context', 'shipping-rules-tester-for-woocommerce' ); ?>
				<select name="product_id" id="srt-product">
					<option value="0"><?php echo esc_html__( 'Synthetic package', 'shipping-rules-tester-for-woocommerce' ); ?></option>
					<?php
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local template loop variable.
					foreach ( $products as $shipping_rules_tester_product ) :
						?>
						<?php if ( ! is_object( $shipping_rules_tester_product ) || ! method_exists( $shipping_rules_tester_product, 'get_id' ) || ! method_exists( $shipping_rules_tester_product, 'get_name' ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<option value="<?php echo esc_attr( (string) absint( $shipping_rules_tester_product->get_id() ) ); ?>">
							<?php echo esc_html( sprintf( '%s (#%d)', $shipping_rules_tester_product->get_name(), absint( $shipping_rules_tester_product->get_id() ) ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<p class="description"><?php echo esc_html__( 'Selecting a product uses its saved price and weight multiplied by the quantity. The manual value and weight fields are ignored for that test.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
			<?php /* translators: 1: currency code, 2: weight unit. */ ?>
			<p class="description"><?php echo esc_html( sprintf( __( 'Package value is the total value of all items in %1$s. Weight is the total package weight in %2$s, not the weight of one item.', 'shipping-rules-tester-for-woocommerce' ), $currency, $weight_unit ) ); ?></p>
			<div class="srt-grid">
				<?php /* translators: %s: currency code. */ ?>
				<label><?php echo esc_html( sprintf( __( 'Package value (%s)', 'shipping-rules-tester-for-woocommerce' ), $currency ) ); ?>
					<input type="number" name="value" min="0" max="100000" step="0.01" value="0">
				</label>
				<?php /* translators: %s: weight unit. */ ?>
				<label><?php echo esc_html( sprintf( __( 'Total package weight (%s)', 'shipping-rules-tester-for-woocommerce' ), $weight_unit ) ); ?>
					<input type="number" name="weight" min="0" max="100000" step="0.001" value="0">
				</label>
				<label><?php echo esc_html__( 'Item quantity', 'shipping-rules-tester-for-woocommerce' ); ?>
					<input type="number" name="quantity" min="1" max="10000" step="1" value="1">
				</label>
			</div>
		</fieldset>
		<p class="srt-actions">
			<button type="submit" class="button button-primary" id="srt-submit"><?php echo esc_html__( 'Test shipping rules', 'shipping-rules-tester-for-woocommerce' ); ?></button>
			<button type="button" class="button" id="srt-keep" hidden><?php echo esc_html__( 'Keep result and test another', 'shipping-rules-tester-for-woocommerce' ); ?></button>
			<button type="button" class="button" id="srt-clear" hidden><?php echo esc_html__( 'Clear comparison', 'shipping-rules-tester-for-woocommerce' ); ?></button>
		</p>
	</form>
	<div id="srt-status" class="srt-status" role="status" aria-live="polite"></div>
	<div id="srt-results" class="srt-results" hidden></div>
</div>
