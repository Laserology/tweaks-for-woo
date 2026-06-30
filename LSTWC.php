<?php
/**
 * Plugin Name: Tweaks for Woo
 * Plugin URI: https://github.com/Laserology/tweaks-for-woo/
 * Description: Free tweaks plugin for your woo store.
 * License: GPL v2 or later
 * Version: 1.0.0
 * Author: Laserology
 * Author URI: https://laserology.net/
 * Requires Plugins: woocommerce
 * Text Domain: tweaks-for-woo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Require PHP 8.0+ for modern syntax support (match expressions, typed arrow functions).
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action( 'admin_notices', function() {
	    sprintf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__('Tweaks for Woo requires PHP 8.0 or later. Your server is running PHP %d', 'plugin-domain'), PHP_VERSION );
	} );
	return;
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
	add_action( 'plugins_loaded', function() {
		new LSTWC();
	} );
}
