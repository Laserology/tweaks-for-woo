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
    }
}

// Initialize the plugin (if it exists)
if ( class_exists( 'LSTWC' ) ) {
    add_action('plugins_loaded', function() {
        new LSTWC();
    });
}
