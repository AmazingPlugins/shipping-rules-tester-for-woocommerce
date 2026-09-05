<?php
/**
 * Shipping tester admin template.
 *
 * @package ShippingRulesTester
 * @var array $countries WooCommerce countries.
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
			<p class="description"><?php echo esc_html__( 'This uses an unsaved synthetic product. Product-specific shipping classes, dimensions, coupons, and live-cart rules are not included.', 'shipping-rules-tester-for-woocommerce' ); ?></p>
			<div class="srt-grid">
				<label><?php echo esc_html__( 'Package value', 'shipping-rules-tester-for-woocommerce' ); ?>
					<input type="number" name="value" min="0" max="100000" step="0.01" value="0">
				</label>
				<label><?php echo esc_html__( 'Weight', 'shipping-rules-tester-for-woocommerce' ); ?>
					<input type="number" name="weight" min="0" max="100000" step="0.001" value="0">
				</label>
				<label><?php echo esc_html__( 'Item quantity', 'shipping-rules-tester-for-woocommerce' ); ?>
					<input type="number" name="quantity" min="1" max="10000" step="1" value="1">
				</label>
			</div>
		</fieldset>
		<p><button type="submit" class="button button-primary" id="srt-submit"><?php echo esc_html__( 'Test shipping rules', 'shipping-rules-tester-for-woocommerce' ); ?></button></p>
	</form>
	<div id="srt-status" class="srt-status" role="status" aria-live="polite"></div>
	<div id="srt-results" class="srt-results" hidden></div>
</div>
