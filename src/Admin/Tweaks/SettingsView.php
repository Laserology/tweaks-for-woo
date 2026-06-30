<?php
/**
 * Admin View: renders the Tweaks / Settings screen within the tabbed interface.
 */

namespace TweaksForWoo\Admin\Tweaks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsView {

	/**
	 * Render the settings page HTML inside the active tab.
	 */
	public static function render_page(): void {
		$lo_enabled     = get_option( SettingsData::LOCATION_TWEAK_KEY, true );
		$bi_enabled     = get_option( SettingsData::BILLING_OPTION_KEY, true );
		$ca_enabled     = get_option( SettingsData::CA_TAX_SCREEN_KEY, true );

		?>
		<div class="wrap tfw-settings-page">

			<form method="post" action="options.php">
				<?php settings_fields( 'tweaks_for_woo_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( SettingsData::BILLING_OPTION_KEY ); ?>">
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
									<input type="hidden" name="<?php echo esc_attr( SettingsData::BILLING_OPTION_KEY ); ?>" value="0" />
									<input type="checkbox"
										id="<?php echo esc_attr( SettingsData::BILLING_OPTION_KEY ); ?>"
										name="<?php echo esc_attr( SettingsData::BILLING_OPTION_KEY ); ?>"
										value="1"
										<?php checked( $bi_enabled, true ); ?>
									/>
									<?php esc_html_e( 'Enabled', 'tweaks-for-woo' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( SettingsData::CA_TAX_SCREEN_KEY ); ?>">
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
									<input type="hidden" name="<?php echo esc_attr( SettingsData::CA_TAX_SCREEN_KEY ); ?>" value="0" />
									<input type="checkbox"
										id="<?php echo esc_attr( SettingsData::CA_TAX_SCREEN_KEY ); ?>"
										name="<?php echo esc_attr( SettingsData::CA_TAX_SCREEN_KEY ); ?>"
										value="1"
										<?php checked( $ca_enabled, true ); ?>
									/>
									<?php esc_html_e( 'Enabled', 'tweaks-for-woo' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( SettingsData::LOCATION_TWEAK_KEY ); ?>">
								<?php esc_html_e( 'Prevent price adjustment by location', 'tweaks-for-woo' ); ?>
							</label>
						</th>
						<td>
							<fieldset>
								<legend class="description">
									<?php echo wp_kses_post( __(
										'When enabled, WooCommerce will no longer change display prices if "show prices including tax" is enabled.',
										'tweaks-for-woo'
									) ); ?>
								</legend>
								<label>
									<input type="hidden" name="<?php echo esc_attr( SettingsData::LOCATION_TWEAK_KEY ); ?>" value="0" />
									<input type="checkbox"
										id="<?php echo esc_attr( SettingsData::LOCATION_TWEAK_KEY ); ?>"
										name="<?php echo esc_attr( SettingsData::LOCATION_TWEAK_KEY ); ?>"
										value="1"
										<?php checked( $lo_enabled, true ); ?>
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
}
