<?php
/**
 * Admin Settings Data: settings keys and helper methods.
 *
 * Settings values are stored as WordPress options and managed
 * via the form in SettingsView.
 */

namespace TweaksForWoo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsData {

	/** Settings keys stored as WordPress options. */
	const LOCATION_TWEAK_KEY = 'tweaks_for_woo_location_adjust';
	const BILLING_OPTION_KEY = 'tweaks_for_woo_force_billing';

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
}
