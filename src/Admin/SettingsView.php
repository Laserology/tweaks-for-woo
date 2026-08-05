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
	 * Enqueue styles and scripts only for this plugin's settings tab.
	 */
	public static function enqueue_assets(): void {
		if ( ! isset( $_GET['page'], $_GET['tab'] ) || 'wc-settings' !== $_GET['page'] || 'tweaks' !== $_GET['tab'] ) {
			return;
		}

		wp_enqueue_style(
			'tweaks-for-woo-settings',
			plugins_url( 'assets/settings.css', __FILE__ ),
			array( 'dashicons' ),
			filemtime( __DIR__ . '/assets/settings.css' )
		);

		wp_enqueue_script(
			'tweaks-for-woo-settings',
			plugins_url( 'assets/settings.js', __FILE__ ),
			array( 'jquery' ),
			filemtime( __DIR__ . '/assets/settings.js' ),
			true
		);

		wp_localize_script(
			'tweaks-for-woo-settings',
			'lstwcSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tweaks_for_woo_save' ),
			)
		);
	}

	/**
	 * AJAX handler: saves the tweak settings without a page reload.
	 */
	public static function handle_ajax_save(): void {
		check_ajax_referer( 'tweaks_for_woo_save', 'nonce' );

		$keys = array( SettingsData::BILLING_OPTION_KEY, SettingsData::LOCATION_TWEAK_KEY );
		foreach ( $keys as $key ) {
			$enabled = isset( $_POST[ $key ] ) && filter_var( wp_unslash( $_POST[ $key ] ), FILTER_VALIDATE_BOOLEAN );
			update_option( $key, $enabled );
		}

		wp_send_json_success();
	}

	/**
	 * Render the Tweaks tab content inside WooCommerce → Settings.
	 */
	public static function render_tab(): void {
		// Hide WooCommerce's own "Save changes" button; toggles auto-save via AJAX.
		$GLOBALS['hide_save_button'] = true;

		$settings = array(
			array(
				'key'         => SettingsData::BILLING_OPTION_KEY,
				'icon'        => 'admin-home',
				'title'       => __( 'Apply Store Base Address to Blank Orders', 'tweaks-for-woo' ),
				'description' => __( 'When enabled, orders created by administrators with blank billing/shipping addresses will be filled in with the store base address. Disable this to preserve blank addresses.', 'tweaks-for-woo' ),
				'value'       => (bool) get_option( SettingsData::BILLING_OPTION_KEY, true ),
			),
			array(
				'key'         => SettingsData::LOCATION_TWEAK_KEY,
				'icon'        => 'admin-site',
				'title'       => __( 'Prevent price adjustment by location', 'tweaks-for-woo' ),
				'description' => __( 'When enabled, WooCommerce will no longer change display prices if "show prices including tax" is enabled.', 'tweaks-for-woo' ),
				'value'       => (bool) get_option( SettingsData::LOCATION_TWEAK_KEY, true ),
			),
		);
		?>
		<div id="lstwc-settings-status" class="lstwc-status" role="status" aria-live="polite">
			<span class="dashicons"></span>
			<span class="lstwc-status__text"></span>
		</div>
		<!-- .wc-settings-prevent-change-event opts toggles out of WooCommerce's unsaved-changes warning; they auto-save. -->
		<div class="lstwc-settings wc-settings-prevent-change-event">
			<?php foreach ( $settings as $setting ) : ?>
				<div class="lstwc-card">
					<div class="lstwc-card__header">
						<div class="lstwc-card__icon">
							<span class="dashicons dashicons-<?php echo esc_attr( $setting['icon'] ); ?>"></span>
						</div>
						<h3 class="lstwc-card__title"><?php echo esc_html( $setting['title'] ); ?></h3>
					</div>
					<p class="lstwc-card__description"><?php echo esc_html( $setting['description'] ); ?></p>
					<div class="lstwc-card__footer">
						<label class="lstwc-toggle" for="<?php echo esc_attr( $setting['key'] ); ?>">
							<input type="hidden" name="<?php echo esc_attr( $setting['key'] ); ?>" value="0" />
							<input type="checkbox"
								id="<?php echo esc_attr( $setting['key'] ); ?>"
								name="<?php echo esc_attr( $setting['key'] ); ?>"
								value="1"
								<?php checked( $setting['value'], true ); ?>
							/>
							<span class="lstwc-toggle__track" aria-hidden="true"></span>
							<span class="lstwc-toggle__label"><?php esc_html_e( 'Enabled', 'tweaks-for-woo' ); ?></span>
						</label>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
