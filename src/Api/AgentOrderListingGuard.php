<?php
/**
 * REST guard that keeps the Internal Agent account out of WooCommerce orders.
 */

namespace TweaksForWoo\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AgentOrderListingGuard {

	/** Account whose access to the orders endpoints is restricted. */
	const RESTRICTED_USER_LOGIN = 'Internal Agent';

	/** Route prefix covering the WooCommerce orders endpoints. */
	const ORDERS_ROUTE_PREFIX = '/wc/v3/orders';

	/**
	 * Register the REST guard.
	 */
	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'prevent_order_access' ), 10, 3 );
	}

	/**
	 * Deny the restricted account access to the orders endpoints.
	 *
	 * @param mixed            $result  Response to replace the requested version with.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public static function prevent_order_access( $result, $server, $request ) {
		$user = wp_get_current_user();

		if ( self::RESTRICTED_USER_LOGIN !== $user->user_login ) {
			return $result;
		}

		if ( ! $request instanceof \WP_REST_Request ) {
			return $result;
		}

		if ( 0 === strpos( $request->get_route(), self::ORDERS_ROUTE_PREFIX ) ) {
			return new \WP_Error(
				'woocommerce_rest_cannot_view',
				'Sorry, you cannot list resources.',
				array( 'status' => 403 )
			);
		}

		return $result;
	}
}
