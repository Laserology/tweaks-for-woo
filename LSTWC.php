<?php /*
Plugin Name: Tweaks for Woo
Plugin URI: https://github.com/Laserology/tweaks-for-wc/
Description: Free tweaks plugin for your woo store.
License: GPL v2 or later
Version: 1.1.0
Author: Laserology
Author URI: https://laserology.net/
Requires Plugins: woocommerce
Text Domain: tweaks-for-wc
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
        \TweaksForWC\Init::boot();

        add_action('woocommerce_new_order', [$this, 'force_billing_address']);
    }

    /**
     * Fill in billing address from store base if it's missing.
     *
     * This ensures the Woo mobile app always has a billing address on
     * in-person orders, which is required for compliance and records.
     */
    public static function force_billing_address( $order_id ) {
        // Get order object frim ID.
        $order = wc_get_order( $order_id );

        // Set order addresses if shipping address is not set.
        // This should only run if an order is created via the admin menu.
        if ( ! $order->has_shipping_address() ) {
            // Set shipping address.
            $order->set_shipping_country( WC()->countries->get_base_country() );
            $order->set_shipping_state( WC()->countries->get_base_state() );
            $order->set_shipping_city( WC()->countries->get_base_city() );
            $order->set_shipping_address_1( WC()->countries->get_base_address() );
            $order->set_shipping_postcode( WC()->countries->get_base_postcode() );

            // Set billing address.
            $order->set_billing_country( WC()->countries->get_base_country() );
            $order->set_billing_state( WC()->countries->get_base_state() );
            $order->set_billing_city( WC()->countries->get_base_city() );
            $order->set_billing_address_1( WC()->countries->get_base_address() );
            $order->set_billing_postcode( WC()->countries->get_base_postcode() );
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
