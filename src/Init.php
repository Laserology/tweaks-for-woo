<?php
/**
 * Bootstrap: wires the plugin into WooCommerce's admin.
 */

namespace TweaksForWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Init {

	/**
	 * Register autoloader for src/*.php classes and boot everything.
	 */
	public static function boot(): void {
		self::register_autoloader();

		// Register "Tweaks" tab in WooCommerce → Settings.
		add_filter( 'woocommerce_settings_tabs_array', array( \TweaksForWoo\Admin\SettingsView::class, 'add_settings_tab' ), 99 );
		add_action( 'woocommerce_settings_tabs_tweaks', array( \TweaksForWoo\Admin\SettingsView::class, 'render_tab' ) );
		add_action( 'admin_enqueue_scripts', array( \TweaksForWoo\Admin\SettingsView::class, 'enqueue_assets' ) );
		add_action( 'wp_ajax_tweaks_for_woo_save', array( \TweaksForWoo\Admin\SettingsView::class, 'handle_ajax_save' ) );

		// Conditionally load the location and billing tweaks based on settings.
		self::maybe_load_tweaks();
	}

	/**
	 * Fill in store base address for orders missing billing/shipping info.
	 *
	 * Ensures WooCommerce mobile orders created by administrators have
	 * complete billing and shipping addresses, which is required for tax
	 * compliance and record-keeping.
	 *
	 * Only applies to orders created by users with 'manage_options'
	 * capability (administrators/via mobile app).
	 */
	public static function force_billing_address( $order_id ) {
		// Only proceed if we can positively identify the user as having admin.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get order object from ID.
		$order = wc_get_order( $order_id );

		// Avoid setting orders that have information set already.
		if ( $order && ! $order->has_shipping_address() ) {
			$countries = WC()->countries;

			// Set shipping address.
			$order->set_shipping_country( $countries->get_base_country() );
			$order->set_shipping_state( $countries->get_base_state() );
			$order->set_shipping_city( $countries->get_base_city() );
			$order->set_shipping_address_1( $countries->get_base_address() );
			$order->set_shipping_postcode( $countries->get_base_postcode() );

			// Set billing address.
			$order->set_billing_country( $countries->get_base_country() );
			$order->set_billing_state( $countries->get_base_state() );
			$order->set_billing_city( $countries->get_base_city() );
			$order->set_billing_address_1( $countries->get_base_address() );
			$order->set_billing_postcode( $countries->get_base_postcode() );

			$order->save();
		}
	}

	/**
	 * Load the location and billing tweaks if their respective settings are enabled.
	 */
	private static function maybe_load_tweaks(): void {
		if ( \TweaksForWoo\Admin\SettingsData::is_location_adjust_enabled() ) {
			add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}

		if ( \TweaksForWoo\Admin\SettingsData::is_billing_tweak_enabled() ) {
			add_action( 'woocommerce_new_order', array( __CLASS__, 'force_billing_address' ) );
		}
	}

	/**
	 * Simple class autoloader for src/ classes.
	 */
	private static function register_autoloader(): void {
		spl_autoload_register( function ( string $class ): void {
			$prefix = 'TweaksForWoo\\';

			if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, strlen( $prefix ) );

			// Map namespace to file path: Admin\SettingsData -> src/Admin/SettingsData.php
			$file = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		} );
	}
}
