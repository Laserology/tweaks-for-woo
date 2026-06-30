<?php
/**
 * Admin View: placeholder screen for future features.
 */

namespace TweaksForWoo\Admin\Future;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BlankView {

	/**
	 * Render the blank future-features page.
	 */
	public static function render_page(): void {
		?>
		<div class="wrap tfw-future-page">
			<h1 class="wp-heading-inline">
				<?php echo esc_html__( 'Coming Soon', 'tweaks-for-woo' ); ?>
			</h1>
			<hr class="wp-header-end" />
			<p class="description">
				<?php esc_html_e( 'This section is reserved for upcoming Tweaks for Woo features.', 'tweaks-for-woo' ); ?>
			</p>
		</div>
		<?php
	}
}
