<?php /*
Plugin Name: Tweaks for Woo
Plugin URI: https://github.com/Laserology/tweaks-for-woo/
Description: Free tweaks plugin for your woo store.
License: GPL v2 or later
Version: 1.0.0
Author: Laserology
Author URI: https://laserology.net/
Requires Plugins: woocommerce
Text Domain: tweaks-for-woo
*/

if ( ! defined('ABSPATH') ) {
    exit;
}

class LSTWC {
    /**
     * Holds the values to be used in the fields
     */
    private $settings;

    /**
     * Init and hook in the integration.
     */
    public function __construct() {
        add_filter('woocommerce_adjust_non_base_location_prices', '__return_false');

        // Boot the report submodule.
        require_once plugin_dir_path( __FILE__ ) . 'src/Init.php';
        \TweaksForWoo\Init::boot();

        add_action('woocommerce_new_order', [$this, 'LSTWC_force_billing_address']);
    }

    /**
     * Fill in billing address from store base if it's missing.
     *
     * This ensures the Woo mobile app always has a billing address on
     * in-person orders, which is required for compliance and records.
     *
     * Only applies to orders created by users with 'manage_options' capability.
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
}

// Initialize the plugin (if it exists)
if ( class_exists( 'LSTWC' ) ) {
    add_action('plugins_loaded', function() {
        new LSTWC();
    });
}
