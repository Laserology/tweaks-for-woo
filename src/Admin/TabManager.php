<?php
/**
 * Admin Tab Manager: unified multi-tab page under WooCommerce → Tweaks.
 *
 * Replaces the separate "Tweaks Settings" and "Sales Location Report" pages
 * with a single tabbed interface containing:
 *   - Sales Report  (aggregated order totals by state/city)
 *   - Tweaks        (plugin settings/toggles)
 *   - Future        (placeholder for upcoming features)
 */

namespace TweaksForWoo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This page only handles GET requests for filtering display data; no state is modified.

class TabManager {

	/** Unified menu slug for the tabbed page. */
	const MENU_SLUG = 'tweaks-for-woo';

	/** Active tab, read from $_GET['tab']; defaults to 'sales-report'. */
	private string $active_tab;

	public function __construct() {
		$this->active_tab = isset( $_GET['tab'] )
			? sanitize_text_field( wp_unslash( $_GET['tab'] ) )
			: 'sales-report';
	}

	/**
	 * Register the unified menu and hook in all subsystems.
	 */
	public function register(): void {
		// Settings registration (must run on admin_init).
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add the tabbed submenu page under WooCommerce → Tweaks.
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		// Conditionally load the location and billing tweaks based on settings.
		$this->maybe_load_tweaks();

		// Enqueue report styles only when the sales-report tab is active.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register WordPress settings for the Tweaks tab.
	 */
	public function register_settings(): void {
		\TweaksForWoo\Admin\Tweaks\SettingsData::register_settings();
	}

	/**
	 * Add the submenu page under WooCommerce → Tweaks.
	 */
	public function add_submenu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Tweaks for Woo', 'tweaks-for-woo' ),
			__( 'Tweaks', 'tweaks-for-woo' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the full tabbed interface.
	 */
	public function render_page(): void {
		$tabs = array(
			'future'     => __( 'Future', 'tweaks-for-woo' ),
			'tweaks'     => __( 'Tweaks', 'tweaks-for-woo' ),
		);

		if ( \TweaksForWoo\Admin\Tweaks\SettingsData::is_ca_tax_screen_enabled() ) {
			$tabs = array_merge(
				array( 'sales-report' => __( 'Sales Report', 'tweaks-for-woo' ) ),
				$tabs
			);
		}

		// If the active tab has been hidden, fall back to the first visible tab.
		if ( ! isset( $tabs[ $this->active_tab ] ) ) {
			$this->active_tab = array_key_first( $tabs );
		}

		?>
		<div class="wrap tfw-admin-page">
			<h1 class="wp-heading-inline">
				<?php echo esc_html__( 'Tweaks for Woo', 'tweaks-for-woo' ); ?>
			</h1>
			<hr class="wp-header-end" />

			<!-- Main Tab Navigation -->
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ): ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"
					   class="nav-tab <?php echo $this->active_tab === $slug ? ' nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<hr class="wp-header-end" />

			<!-- Tab Content -->
			<?php switch ( $this->active_tab ):
				case 'sales-report':
					\TweaksForWoo\Admin\SalesReport\ReportView::render_page();
					break;

				case 'tweaks':
					\TweaksForWoo\Admin\Tweaks\SettingsView::render_page();
					break;

				case 'future':
					\TweaksForWoo\Admin\Future\BlankView::render_page();
					break;

				default:
					\TweaksForWoo\Admin\SalesReport\ReportView::render_page();
					break;
			endswitch; ?>

		</div>
		<?php
	}

	/**
	 * Load the location and billing tweaks if their respective settings are enabled.
	 */
	private function maybe_load_tweaks(): void {
		if ( \TweaksForWoo\Admin\Tweaks\SettingsData::is_location_adjust_enabled() ) {
			add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}

		if ( \TweaksForWoo\Admin\Tweaks\SettingsData::is_billing_tweak_enabled() ) {
			add_action( 'woocommerce_new_order', array( \TweaksForWoo\Init::class, 'force_billing_address' ) );
		}
	}

	/**
	 * Enqueue CSS styles on the WooCommerce admin pages.
	 *
	 * Loads `assets/css/reports.css` only when the sales-report tab is active.
	 */
	public function enqueue_assets(): void {
		global $hook_suffix;
		if ( basename( $hook_suffix ) !== 'tweaks-for-woo' ) {
			return;
		}

		if ( $this->active_tab !== 'sales-report' ) {
			return;
		}

		wp_enqueue_style(
			'tweaks-for-woo-reports',
			plugins_url( '../assets/css/reports.css', dirname( __FILE__ ) ),
			array(),
			'1.0.0'
		);
	}
}
