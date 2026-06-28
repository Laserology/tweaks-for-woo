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
		// Only run if user has selected for it to be enabled.
        if ( \TweaksForWC\Report\ConfigView::is_ca_tax_screen_enabled() ) {
            \TweaksForWC\Report\AdminView::register_menu();
        }

		// Register settings page under WooCommerce → Tweaks.
		\TweaksForWC\Report\ConfigView::register_menu();

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
	 * Enqueue report styles only on the report page.
	 */
	public static function enqueue_assets(): void {
		global $hook_suffix;
		if ( 'sales-location-report' !== basename( $hook_suffix ) ) {
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
