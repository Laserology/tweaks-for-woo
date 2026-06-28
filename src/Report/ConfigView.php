<?php
/**
 * Admin View: renders the Settings page under WooCommerce → Settings.
 */

namespace TweaksForWoo\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ConfigView {

	const BILLING_OPTION_KEY                  = 'tweaks_for_wc_force_billing';
	const CA_TAX_SCREEN_KEY   = 'tweaks_for_wc_california_tax_screen';

	/**
	 * Register the settings submenu and handler.
	 */
	public static function register_menu(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_submenu_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	/**
	 * Add submenu page under WooCommerce → Settings.
	 */
	public static function add_submenu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Tweaks for Woo Settings', 'tweaks-for-woo' ),
			__( 'Tweaks', 'tweaks-for-woo' ),
			'manage_woocommerce',
			'tweaks-for-woo-settings',
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * Register the plugin options.
	 */
	public static function register_settings(): void {
		register_setting( 'tweaks_for_wc_settings', self::BILLING_OPTION_KEY, [
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => fn( $value ) => (bool) $value,
		] );

		register_setting( 'tweaks_for_wc_settings', self::CA_TAX_SCREEN_KEY, [
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => fn( $value ) => (bool) $value,
		] );
	}

	/**
	 * Render the settings page HTML.
	 */
	public static function render_page(): void {
		$enabled       = get_option( self::BILLING_OPTION_KEY, true );
		$ca_enabled    = get_option( self::CA_TAX_SCREEN_KEY, true );

		?>
		<div class="wrap tweaks-for-woo-settings">

			<h1 class="wp-heading-inline">
				<?php echo esc_html__( 'Tweaks for Woo Settings', 'tweaks-for-woo' ); ?>
			</h1>

			<hr class="wp-header-end" />

			<form method="post" action="options.php">
				<?php settings_fields( 'tweaks_for_wc_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::BILLING_OPTION_KEY ); ?>">
								<?php esc_html_e( 'Apply Store Base Address to Blank Orders', 'tweaks-for-woo' ); ?>
							</label>
						</th>
						<td>
							<fieldset>
								<legend class="description">
									<?php echo wp_kses_post( __(
										'When enabled, orders created by administrators with blank billing/shipping addresses will be filled in with the store base address. Disable this to preserve blank addresses.',
										'tweaks-for-woo'
									) ); ?>
								</legend>
								<label>
									<input type="hidden" name="<?php echo esc_attr( self::BILLING_OPTION_KEY ); ?>" value="0" />
									<input type="checkbox"
										id="<?php echo esc_attr( self::BILLING_OPTION_KEY ); ?>"
										name="<?php echo esc_attr( self::BILLING_OPTION_KEY ); ?>"
										value="1"
										<?php checked( $enabled, true ); ?>
									/>
									<?php esc_html_e( 'Enabled', 'tweaks-for-woo' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::CA_TAX_SCREEN_KEY ); ?>">
								<?php esc_html_e( 'Enable California Tax Screen', 'tweaks-for-woo' ); ?>
							</label>
						</th>
						<td>
							<fieldset>
								<legend class="description">
									<?php echo wp_kses_post( __(
										'When enabled, the California tax screen will be loaded in the admin view. Disable this to hide the California tax screen from administrators.',
										'tweaks-for-woo'
									) ); ?>
								</legend>
								<label>
									<input type="hidden" name="<?php echo esc_attr( self::CA_TAX_SCREEN_KEY ); ?>" value="0" />
									<input type="checkbox"
										id="<?php echo esc_attr( self::CA_TAX_SCREEN_KEY ); ?>"
										name="<?php echo esc_attr( self::CA_TAX_SCREEN_KEY ); ?>"
										value="1"
										<?php checked( $ca_enabled, true ); ?>
									/>
									<?php esc_html_e( 'Enabled', 'tweaks-for-woo' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

		</div>
		<?php
	}

	/**
	 * Check whether force-billing is currently enabled.
	 */
	public static function is_fba_enabled(): bool {
		return (bool) get_option( self::BILLING_OPTION_KEY, true );
	}

	/**
	 * Check whether the California tax screen is currently enabled.
	 */
	public static function is_ca_tax_screen_enabled(): bool {
		return (bool) get_option( self::CA_TAX_SCREEN_KEY, true );
	}
}
