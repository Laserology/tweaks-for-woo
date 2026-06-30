<?php
/**
 * Admin Settings Data: settings keys, registration, and helpers.
 */

namespace TweaksForWoo\Admin\Tweaks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsData {

	/** Settings keys stored as WordPress options. */
	const LOCATION_TWEAK_KEY = 'tweaks_for_woo_location_adjust';
	const BILLING_OPTION_KEY = 'tweaks_for_woo_force_billing';
	const CA_TAX_SCREEN_KEY  = 'tweaks_for_woo_california_tax_screen';

	/**
	 * Register settings fields so WooCommerce saves them in the database.
	 */
	public static function register_settings(): void {
		register_setting( 'tweaks_for_woo_settings', self::BILLING_OPTION_KEY, [
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => fn( $value ) => (bool) $value,
		] );

		register_setting( 'tweaks_for_woo_settings', self::CA_TAX_SCREEN_KEY, [
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => fn( $value ) => (bool) $value,
		] );

		register_setting( 'tweaks_for_woo_settings', self::LOCATION_TWEAK_KEY, [
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => fn( $value ) => (bool) $value,
		] );
	}

	/**
	 * Check whether location-based price adjustment is currently enabled.
	 */
	public static function is_location_adjust_enabled(): bool {
		return (bool) get_option( self::LOCATION_TWEAK_KEY, true );
	}

	/**
	 * Check whether force-billing is currently enabled.
	 */
	public static function is_billing_tweak_enabled(): bool {
		return (bool) get_option( self::BILLING_OPTION_KEY, true );
	}

	/**
	 * Check whether the California tax report screen is currently enabled.
	 */
	public static function is_ca_tax_screen_enabled(): bool {
		return (bool) get_option( self::CA_TAX_SCREEN_KEY, true );
	}
}
