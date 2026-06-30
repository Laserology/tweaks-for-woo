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
     * Init and hook in the integration.
     * This boots the report submodule.
     */
    public function __construct() {
        require_once plugin_dir_path( __FILE__ ) . 'src/Init.php';
        \TweaksForWoo\Init::boot();
    }
}

// Initialize the plugin (if it exists)
if ( class_exists( 'LSTWC' ) ) {
    add_action('plugins_loaded', function() {
        new LSTWC();
    });
}
