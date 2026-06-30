<?php
/**
 * Bootstrap: wires the tabbed report plugin into WooCommerce's admin.
 */

namespace TweaksForWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Init {

	/**
	 * Register autoloader for src/*.php classes and boot the TabManager.
	 */
	public static function boot(): void {
		self::register_autoloader();

		// Bootstrap the unified tabbed admin interface.
		$tab_manager = new \TweaksForWoo\Admin\TabManager();
		$tab_manager->register();
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
	 * Simple class autoloader for src/ classes.
	 */
	private static function register_autoloader(): void {
		    spl_autoload_register( function ( string $class ): void {
    			$prefix = 'TweaksForWoo\\';
    
    			if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
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
}
