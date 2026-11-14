<?php
/**
 * API client that opts the tax service into tax lookups without enabling automated taxes.
 */

namespace TweaksForWoo\TaxRates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APIClient extends \WC_Connect_API_Client_Live {

	protected function get_settings_values() {
		$values                  = parent::get_settings_values();
		$values['taxes_enabled'] = true;

		return $values;
	}
}
