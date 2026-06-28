<?php
/**
 * Bootstrap: wires the report plugin into WooCommerce's admin.
 */

namespace TweaksForWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Init {

	/**
	 * Register autoloader for src/*.php classes.
	 */
	public static function boot(): void {
		self::register_autoloader();

		if ( \TweaksForWoo\Report\ConfigView::is_location_adjust_enabled() ) {
		    add_filter('woocommerce_adjust_non_base_location_prices', '__return_false');
		}

		if ( \TweaksForWoo\Report\ConfigView::is_billing_tweak_enabled() ) {
		    add_action('woocommerce_new_order', [__CLASS__, 'LSTWC_force_billing_address']);
		}

		// Hook into WooCommerce menu so our tab appears alongside the default reports.
		// Only run if user has selected for it to be enabled.
        if ( \TweaksForWoo\Report\ConfigView::is_ca_tax_screen_enabled() ) {
            \TweaksForWoo\Report\AdminView::register_menu();
        }

		// Register settings page under WooCommerce → Tweaks.
		\TweaksForWoo\Report\ConfigView::register_menu();

		// Enqueue report styles on the WooCommerce admin pages.
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
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
    public static function LSTWC_force_billing_address( $order_id ) {
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
	 * Simple class autoloader for src/ classes.
	 */
	private static function register_autoloader(): void {
		spl_autoload_register( function ( string $class ): void {
			$prefix = 'TweaksForWoo\\';

			if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, strlen( $prefix ) );

			// Map namespace to file path: Report\AdminView -> src/Report/AdminView.php
			$file = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		} );
	}

	/**
	 * Enqueue CSS styles on the WooCommerce admin pages for this plugin.
	 *
	 * Loads `assets/css/reports.css` only when the Sales Location Report
	 * page is being displayed, to avoid unnecessary asset loading elsewhere.
	 */
	public static function enqueue_assets(): void {
		global $hook_suffix;
		if ( 'sales-location-report' !== basename( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'tweaks-for-woo-reports',
			plugins_url( '../assets/css/reports.css', dirname( __FILE__ ) ),
			[],
			'1.0.0'
		);
	}
}
