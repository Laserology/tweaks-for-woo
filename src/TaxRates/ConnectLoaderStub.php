<?php
/**
 * Minimal loader stand-in so the tax integration can be constructed without
 * registering the full WooCommerce Tax service stack.
 */

namespace TweaksForWoo\TaxRates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ConnectLoaderStub extends \WC_Connect_Loader {

	public function __construct() {
	}

	public function get_active_services(): array {
		return array();
	}
}
