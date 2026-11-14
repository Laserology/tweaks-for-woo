<?php
/**
 * Detection for the WooCommerce Tax service the tax rate filler depends on.
 */

namespace TweaksForWoo\TaxRates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TaxService {

	/**
	 * Classes provided by WooCommerce Tax (WooCommerce Shipping & Tax).
	 *
	 * @var string[]
	 */
	const REQUIRED_CLASSES = array(
		'\WC_Connect_TaxJar_Integration',
		'\WC_Connect_API_Client_Live',
		'\WC_Connect_Service_Schemas_Validator',
		'\WC_Connect_Logger',
		'\WC_Connect_Tracks',
		'\WC_Connect_Loader',
	);

	/**
	 * Whether every class needed to talk to the tax service is available.
	 */
	public static function is_available(): bool {
		foreach ( self::REQUIRED_CLASSES as $class ) {
			if ( ! class_exists( $class ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Warn administrators when the tax service is missing.
	 */
	public static function dependency_notice(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || self::is_available() ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Tweaks for Woo needs the WooCommerce Tax (WooCommerce Shipping & Tax) plugin active to fill tax rates. No rates will be filled until it is.', 'tweaks-for-woo' )
		);
	}
}
