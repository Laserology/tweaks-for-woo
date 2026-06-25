<?php
/**
 * Bootstrap: wires the report plugin into WooCommerce's admin.
 */

namespace TweaksForWC;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Init {

	/**
	 * Register autoloader for src/*.php classes.
	 */
	public static function boot(): void {
		self::register_autoloader();

		// Hook into WooCommerce menu so our tab appears alongside the default reports.
		\TweaksForWC\Report\AdminView::register_menu();

		// Enqueue report styles on the WooCommerce admin pages.
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Simple class autoloader for src/ classes.
	 */
	private static function register_autoloader(): void {
		spl_autoload_register( function ( string $class ): void {
			$prefix = 'TweaksForWC\\';

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
	 * Enqueue admin styles only on our report page.
	 */
	public static function enqueue_assets(): void {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
	}

	private static function enqueue_styles(): void {
		global $hook_suffix;
		if ( $hook_suffix !== 'woocommerce_page_sales-location-report' ) {
			return;
		}

		wp_enqueue_style(
			'tweaks-for-wc-reports',
			plugins_url( '../assets/css/reports.css', dirname( __FILE__ ) ),
			[],
			'1.0.0'
		);
	}
}
