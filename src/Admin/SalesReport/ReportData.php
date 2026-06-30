<?php
/**
 * Data Store: aggregates order totals by billing location.
 *
 * Uses HPOS-compatible wc_get_orders() to fetch orders, then aggregates
 * in PHP. Blank billing fields are skipped during aggregation so the
 * store operates correctly under both legacy post_meta and COT storage.
 */

namespace TweaksForWoo\Admin\SalesReport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReportData {

	/**
	 * Supported grouping levels.
	 */
	const GROUP_STATE = 'state';
	const GROUP_CITY  = 'city';
	const GROUP_ALL   = 'all'; // Combined rows with state/city columns.

	/**
	 * Fetch all location-based order totals for a given date range.
	 *
	 * @param string $group_by       State, city, or all.
	 * @param string $from           Start date (Y-m-d).
	 * @param string $to             End date (Y-m-d).
	 * @param bool   $california_only Whether to filter to CA orders only.
	 * @return array[]
	 */
	public static function get_totals( string $group_by, string $from, string $to, bool $california_only = false ): array {

		$location_field = match ( $group_by ) {
			self::GROUP_STATE  => 'billing_state',
			self::GROUP_CITY   => 'billing_city',
			default            => null,
		};

		if ( ! is_null( $location_field ) ) {
			$orders = static::fetch_orders( $from, $to, $california_only );
			return static::aggregate_orders( $orders, $location_field );
		}

		// GROUP BY all — return combined rows with state/city columns.
		return static::get_combined_totals( $from, $to, $california_only );
	}

	/**
	 * Aggregate orders by a location field in PHP.
	 *
	 * @param array<WC_Order> $orders     The fetched orders.
	 * @param string          $field_name The billing field to group by.
	 * @return array[]
	 */
	private static function aggregate_orders( array $orders, string $field_name ): array {
		$aggregated = array();
		$countries = WC()->countries;

		foreach ( $orders as $order ) {
			$location = $order->{ $field_name };

			if ( empty( $location ) ) {
			    $location = match ( $field_name ) {
					'billing_state' => $countries->get_base_state(),
					'billing_city'  => $countries->get_base_city(),
					default         => __( 'Unknown or Unspecified', 'tweaks-for-woo' ),
				};
			}

			if ( ! isset( $aggregated[ $location ] ) ) {
				$aggregated[ $location ] = array(
					'name'   => $location,
					'total'  => 0.0,
					'orders' => 0,
				);
			}

			$aggregated[ $location ]['total']  += (float) $order->get_total();
			$aggregated[ $location ]['orders'] += 1;
		}

		// Sort by total descending.
		uasort( $aggregated, function ( $a, $b ) {
			return $b['total'] <=> $a['total'];
		});

		return array_values( $aggregated );
	}

	/**
	 * Get a combined summary: state / city breakdown in parallel columns.
	 *
	 * @return array[]
	 */
	private static function get_combined_totals( string $from, string $to, bool $california_only = false ): array {
		$result = array();

		foreach ( array( self::GROUP_STATE, self::GROUP_CITY ) as $level ) {
    		$field = match ( $level ) {
    			self::GROUP_STATE  => 'billing_state',
    			self::GROUP_CITY   => 'billing_city',
    			default            => null,
    		};

			if ( ! is_null( $field ) ) {
				$orders = static::fetch_orders( $from, $to, $california_only );

				foreach ( static::aggregate_orders( $orders, $field ) as $entry ) {
					$entry['level'] = match ( $level ) {
						self::GROUP_STATE  => 'state',
						self::GROUP_CITY   => 'city',
						default            => null,
					};
					$result[] = $entry;
				}
			}
		}

		return $result;
	}

	/**
	 * Get a grand-total across all locations for the given date range.
	 *
	 * Sums up `get_total()` for all completed and processing orders within
	 * the specified period, providing a single total revenue figure regardless
	 * of billing location.
	 *
	 * @param string $from            Start date (Y-m-d).
	 * @param string $to              End date (Y-m-d).
	 * @param bool   $california_only Whether to filter to CA orders only.
	 * @return float Grand total as a decimal.
	 */
	public static function get_grand_total( string $from, string $to, bool $california_only = false ): float {
		$grand_total = 0.0;

		foreach ( static::fetch_orders( $from, $to, $california_only ) as $order ) {
			$grand_total += (float) $order->get_total();
		}

		return $grand_total;
	}

	/**
	 * Fetch orders matching the date range.
	 *
	 * Note: location-based blank-value filtering is handled in aggregate_orders()
	 * because billing fields are stored as COT columns under HPOS, not post meta.
	 * Filtering in PHP avoids incompatibility with the meta_query approach.
	 *
	 * @param string $from Start date (Y-m-d).
	 * @param string $to   End date (Y-m-d).
	 * @return array<WC_Order>
	 */
	private static function fetch_orders( string $from, string $to, bool $california_only = false ): array {
		$raw = wc_get_orders( array(
			'status'       => array( 'wc-completed', 'wc-processing' ),
			'date_created' => $from . '...' . $to,
			'relation'     => 'AND',
			'limit'        => -1,
		) );

		// Filter out OrderRefund objects which lack billing fields.
		$filter = array_filter( $raw, fn ( $order ) => $order instanceof \WC_Order );

		if ( $california_only ) {
		    // Filter out non-california orders if selected.
			// Also includes orders with blank addresses (assumed to be store's base address)
			return array_filter( $filter, fn ( $order ) => $order->get_billing_state() == 'CA' ||$order->get_billing_state() == '' );
		}
		else {
		    return $filter;
		}
	}
}
