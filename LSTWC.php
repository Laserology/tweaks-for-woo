<?php /*
Plugin Name: Tweaks for Woo
Plugin URI: https://github.com/Laserology/tweaks-for-wc/
Description: Free tweaks plugin for your woo store.
License: GPL v2 or later
Version: 1.0.0
Author: Laserology
Author URI: https://laserology.net/
Requires Plugins: woocommerce
Text Domain: tweaks-for-wc
*/

add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
