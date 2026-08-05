<?php
/**
 * Admin View: adds "Tweaks" tab to WooCommerce → Settings.
 */

namespace TweaksForWoo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsView {

	/**
	 * Add a "Tweaks" tab to WooCommerce settings tabs list.
	 */
	public static function add_settings_tab( array $tabs ): array {
		$tabs['tweaks'] = __( 'Tweaks', 'tweaks-for-woo' );
		return self::move_tab_before( $tabs, 'tweaks', 'advanced' );
	}

	/**
	 * Move a tab so it appears immediately before another tab in the array.
	 */
	private static function move_tab_before( array $tabs, string $tab, string $before ): array {
		if ( ! isset( $tabs[ $tab ], $tabs[ $before ] ) || $tab === $before ) {
			return $tabs;
		}

		$tab_entry = $tabs[ $tab ];
		unset( $tabs[ $tab ] );

		$reordered = array();
		foreach ( $tabs as $key => $label ) {
			if ( $key === $before ) {
				$reordered[ $tab ] = $tab_entry;
			}
			$reordered[ $key ] = $label;
		}

		return $reordered;
	}

	/**
	 * Render the Tweaks tab content inside WooCommerce → Settings.
	 */
	public static function render_tab(): void {
		// Hide WooCommerce's own "Save changes" button; this tab posts to the options API instead.
		$GLOBALS['hide_save_button'] = true;

		// Handle form submission (saves via WordPress options API).
		if ( isset( $_POST['tweaks_save'] ) && isset( $_POST['tweaks_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tweaks_nonce'] ) ), 'tweaks_for_woo_save' )
		) {
			$keys = array( SettingsData::BILLING_OPTION_KEY, SettingsData::CA_TAX_SCREEN_KEY, SettingsData::LOCATION_TWEAK_KEY );
			foreach ( $keys as $key ) {
				update_option( $key, isset( $_POST[ $key ] ) ? true : false );
			}
			// Prevent re-submit by redirecting to same page with success flag.
			wp_safe_redirect( add_query_arg( array( 'tweaks_saved' => '1' ), admin_url( 'admin.php?page=woocommerce&tab=tweaks' ) ) );
			exit;
		}

		$bi_enabled = get_option( SettingsData::BILLING_OPTION_KEY, true );
		$ca_enabled = get_option( SettingsData::CA_TAX_SCREEN_KEY, true );
		$lo_enabled = get_option( SettingsData::LOCATION_TWEAK_KEY, true );

		// Show success notice if saved.
		if ( isset( $_GET['tweaks_saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'tweaks-for-woo' ) . '</p></div>';
		}

		?>
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

		<p class="submit">
			<?php wp_nonce_field( 'tweaks_for_woo_save', 'tweaks_nonce' ); ?>
			<input type="hidden" name="tweaks_save" value="1" />
			<?php submit_button( __( 'Save Changes', 'tweaks-for-woo' ) ); ?>
		</p>
		<?php
	}
}
