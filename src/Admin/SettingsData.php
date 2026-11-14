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
	const ALWAYS_FILL_RATES_KEY = 'tweaks_for_woo_force_fill_rates';
	const AGENT_ORDERS_KEY = 'tweaks_for_woo_block_agent_orders';

	/**
	 * Check whether tax rate filling is enabled.
	 */
	public static function is_force_rate_fill_enabled(): bool {
		return (bool) get_option( self::ALWAYS_FILL_RATES_KEY, true );
	}

	/**
	 * Check whether the Internal Agent order REST guard is enabled.
	 */
	public static function is_agent_orders_blocked(): bool {
		return (bool) get_option( self::AGENT_ORDERS_KEY, true );
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
}
