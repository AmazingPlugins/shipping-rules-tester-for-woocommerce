<?php
/**
 * Admin page and AJAX endpoint.
 *
 * @package ShippingRulesTester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render and handle the shipping tester screen.
 */
class SRT_Admin {

	/**
	 * Tester instance.
	 *
	 * @var SRT_Tester
	 */
	private $tester;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tester = new SRT_Tester();
	}

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_srt_test_shipping', array( $this, 'ajax_test_shipping' ) );
	}

	/**
	 * Add the WooCommerce submenu.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Shipping Rules Tester', 'shipping-rules-tester' ),
			__( 'Shipping Rules Tester', 'shipping-rules-tester' ),
			'manage_woocommerce',
			'shipping-rules-tester',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets only on this screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_shipping-rules-tester' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.css', array(), SRT_VERSION );
		wp_enqueue_script( 'srt-admin', SRT_PLUGIN_URL . 'assets/admin.js', array( 'jquery' ), SRT_VERSION, true );
		wp_localize_script(
			'srt-admin',
			'srtData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'srt_nonce' ),
				'i18n'    => array(
					'error'       => __( 'The shipping test could not be completed.', 'shipping-rules-tester' ),
					'testing'     => __( 'Testing shipping rules…', 'shipping-rules-tester' ),
					'matchedZone' => __( 'Matched shipping zone', 'shipping-rules-tester' ),
					'methods'     => __( 'Shipping methods', 'shipping-rules-tester' ),
					'noMethods'   => __( 'No enabled shipping methods were found in this zone.', 'shipping-rules-tester' ),
					'method'      => __( 'Method', 'shipping-rules-tester' ),
					'result'      => __( 'Result', 'shipping-rules-tester' ),
					'details'     => __( 'Details', 'shipping-rules-tester' ),
					'matched'     => __( 'Matched', 'shipping-rules-tester' ),
					'noRate'      => __( 'No rate', 'shipping-rules-tester' ),
					'notTested'   => __( 'Not tested', 'shipping-rules-tester' ),
				),
			)
		);
	}

	/**
	 * Render the tester form.
	 */
	public function render_page() {
		$countries = WC()->countries->get_countries();
		?>
		<div class="wrap srt-wrap">
			<h1><?php echo esc_html__( 'Shipping Rules Tester', 'shipping-rules-tester' ); ?></h1>
			<p><?php echo esc_html__( 'Test which WooCommerce shipping zone and methods match a sample destination and package.', 'shipping-rules-tester' ); ?></p>
			<div class="notice notice-warning inline"><p><?php echo esc_html__( 'This is a read-only test. External rate methods are skipped unless you explicitly allow them below. Results are not saved.', 'shipping-rules-tester' ); ?></p></div>
			<form id="srt-form" class="srt-form">
				<div class="srt-grid">
					<label><?php echo esc_html__( 'Country', 'shipping-rules-tester' ); ?>
						<select name="country" required>
							<option value=""><?php echo esc_html__( 'Select a country', 'shipping-rules-tester' ); ?></option>
							<?php foreach ( $countries as $code => $name ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label><?php echo esc_html__( 'State or province', 'shipping-rules-tester' ); ?>
						<input type="text" name="state" maxlength="100">
					</label>
					<label><?php echo esc_html__( 'Postcode', 'shipping-rules-tester' ); ?>
						<input type="text" name="postcode" maxlength="20">
					</label>
					<label><?php echo esc_html__( 'City', 'shipping-rules-tester' ); ?>
						<input type="text" name="city" maxlength="100">
					</label>
					<label><?php echo esc_html__( 'Package value', 'shipping-rules-tester' ); ?>
						<input type="number" name="value" min="0" max="100000" step="0.01" value="0">
					</label>
					<label><?php echo esc_html__( 'Weight', 'shipping-rules-tester' ); ?>
						<input type="number" name="weight" min="0" max="100000" step="0.001" value="0">
					</label>
					<label><?php echo esc_html__( 'Item quantity', 'shipping-rules-tester' ); ?>
						<input type="number" name="quantity" min="1" max="10000" step="1" value="1">
					</label>
				</div>
				<label class="srt-external"><input type="checkbox" name="allow_external" value="1"> <?php echo esc_html__( 'Allow configured external rate methods to run during this test', 'shipping-rules-tester' ); ?></label>
				<p class="description"><?php echo esc_html__( 'External methods may send the sample destination and package to their own rate provider. Leave this unchecked for a local-only test.', 'shipping-rules-tester' ); ?></p>
				<p><button type="submit" class="button button-primary" id="srt-submit"><?php echo esc_html__( 'Test shipping rules', 'shipping-rules-tester' ); ?></button></p>
			</form>
			<div id="srt-status" class="srt-status" role="status" aria-live="polite"></div>
			<div id="srt-results" class="srt-results" hidden></div>
		</div>
		<?php
	}

	/**
	 * Handle a shipping test request.
	 */
	public function ajax_test_shipping() {
		if ( ! check_ajax_referer( 'srt_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'shipping-rules-tester' ) ) );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to run this test.', 'shipping-rules-tester' ) ) );
		}

		$result = $this->tester->test( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}
}
