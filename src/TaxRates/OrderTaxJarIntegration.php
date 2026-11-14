<?php
/**
 * TaxJar integration wrapper exposing order line items for tax lookups.
 */

namespace TweaksForWoo\TaxRates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrderTaxJarIntegration extends \WC_Connect_TaxJar_Integration {

	/**
	 * Build the line items for an order using the tax service's own backend mapper.
	 */
	public function get_order_line_items( \WC_Order $order ): array {
		if ( ! method_exists( $this, 'get_backend_line_items' ) ) {
			return array();
		}

		return $this->get_backend_line_items( $order );
	}
}
