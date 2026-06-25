<?php
/**
 * Data Store: aggregates order totals by billing location.
 *
 * Uses wc_get_orders() with field_query for HPOS-compatible order fetching.
 * Aggregation is performed in PHP since wc_get_orders() returns individual orders,
 * not aggregated results. This is the recommended approach over direct SQL queries
 * per WooCommerce best practices.
 */

namespace TweaksForWC\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DataStore {

	/**
	 * Supported grouping levels.
	 */
	const GROUP_STATE  = 'state';
	const GROUP_COUNTY = 'county';
	const GROUP_CITY   = 'city';
	const GROUP_ALL    = 'all'; // combined rows with state/county/city columns

	/**
	 * Fetch all location-based order totals for a given date range.
	 *
	 * @param string $group_by State, county, city, or all.
	 * @param string $from     Start date (Y-m-d).
	 * @param string $to       End date (Y-m-d).
	 * @return array[]
	 */
	public static function get_totals( string $group_by, string $from, string $to ): array {

		$date_from = strtotime( $from . ' 00:00:00' );
		$date_to   = strtotime( $to . ' 23:59:59' );

		$location_field = match ( $group_by ) {
			self::GROUP_STATE  => 'billing_state',
			self::GROUP_COUNTY => 'billing_county',
			self::GROUP_CITY   => 'billing_city',
			default            => null,
		};

		if ( ! is_null( $location_field ) ) {
			$orders = wc_get_orders(
				array(
					'date_query' => array(
						'relation' => 'AND',
						array(
							'column'  => 'date_created_gmt',
							'value'     => '>' . $date_from,
							'compare'   => '>=',
							'type'      => 'NUMERIC',
						),
						array(
							'field'     => 'date_created_gmt',
							'value'     => $date_to,
							'compare'   => '<=',
							'type'      => 'NUMERIC',
						),
					),
			        'field_query' => array(
						array(
				            'field'     => 'status',
							'value'     => array( 'wc-completed', 'wc-processing' ),
							'compare'   => '=',
				            'type'      => 'CHAR',
						),
				        array(
							'field'     => $location_field,
				            'value'     => array(),
							'compare'   => '!=',
				            'type'      => 'CHAR',
						),
				        'relation' => 'AND',
					),
        		    'limit'  => -1,
          		    'orderby' => 'date',
                    'order'   => 'DESC',
				)
			);

			return static::aggregate_orders( $orders, $location_field );
		}

		// GROUP BY all — return one row with a "Total" label plus the three columns.
		return static::get_combined_totals( $from, $to );
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

		foreach ( $orders as $order ) {
			$location = $order->{ $field_name };
			if ( empty( $location ) ) {
				$location = 'Unknown';
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
	 * Get a combined summary: state / county / city breakdown in parallel columns.
	 *
	 * @return array[]
	 */
	private static function get_combined_totals( string $from, string $to ): array {
		$result = array();

		foreach ( array( self::GROUP_STATE, self::GROUP_COUNTY, self::GROUP_CITY ) as $level ) {
			$field = match ( $level ) {
				self::GROUP_STATE  => 'billing_state',
				self::GROUP_COUNTY => 'billing_county',
				self::GROUP_CITY   => 'billing_city',
				default            => null,
			};

			if ( ! is_null( $field ) ) {
				$orders = wc_get_orders(
					array(
						'field_query' => array(
							array(
								'field'     => 'date_created_gmt',
							    'value'     => '>' . strtotime( $from . ' 00:00:00' ),
								 'compare'   => '>=',
								'type'      => 'NUMERIC',
							),
							 array(
								'field'     => 'date_created_gmt',
						        'value'     => strtotime( $to . ' 23:59:59' ),
								 'compare'   => '<=',
								'type'      => 'NUMERIC',
							 ),
							 array(
								 'field'     => 'status',
							     'value'     => array( 'wc-completed', 'wc-processing' ),
								 'compare'   => '=',
							     'type'      => 'CHAR',
							 ),
							 array(
								'field'     => $field,
								'value'     => array(),
								'compare'   => '!=',
						        'type'      => 'CHAR',
							 ),
							 'relation' => 'AND',
						),
			            'limit'  => -1,
					)
				);

				foreach ( static::aggregate_orders( $orders, $field ) as $entry ) {
					$entry['level'] = match ( $level ) {
						self::GROUP_STATE  => 'state',
						self::GROUP_COUNTY => 'county',
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
	 * Get a grand-total across all locations.
	 */
	public static function get_grand_total( string $from, string $to ): float {
		$date_from = strtotime( $from . ' 00:00:00' );
		$date_to   = strtotime( $to . ' 23:59:59' );

		$orders = wc_get_orders(
			array(
				'field_query' => array(
					array(
						'field'     => 'date_created_gmt',
			            'value'     => '>' . $date_from,
						'compare'   => '>=',
			            'type'      => 'NUMERIC',
					),
					array(
						'field'     => 'date_created_gmt',
			            'value'     => $date_to,
						'compare'   => '<=',
			            'type'      => 'NUMERIC',
					),
					array(
						'field'     => 'status',
			            'value'     => array( 'wc-completed', 'wc-processing' ),
						'compare'   => '=',
			            'type'      => 'CHAR',
					),
					'relation' => 'AND',
				),
			    'limit' => -1,
			)
		);

		$grand_total = 0.0;
		foreach ( $orders as $order ) {
			$grand_total += (float) $order->get_total();
		}

		return $grand_total;
	}

	/**
	 * Return the list of US states (California-first for this plugin's focus).
	 */
	public static function us_states(): array {
		return [
			'CA' => 'California',
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'DC' => 'District of Columbia',
			'DS' => 'Other / Domestic Territories',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
		];
	}

	/**
	 * Return the list of California counties.
	 */
	public static function ca_counties(): array {
		return [
			'Alameda County',
			'Alpine County',
			'Amador County',
			'Butte County',
			'Calaveras County',
			'Colusa County',
			'Contra Costa County',
			'Del Norte County',
			'El Dorado County',
			'Fresno County',
			'Glenn County',
			'Humboldt County',
			'Imperial County',
			'Inyo County',
			'Kern County',
			'Kings County',
			'Lake County',
			'Lassen County',
			'Los Angeles County',
			'Madera County',
			'Marin County',
			'Mariposa County',
			'Mendocino County',
			'Merced County',
			'Modoc County',
			'Mono County',
			'Monterey County',
			'Napa County',
			'Nevada County',
			'Orange County',
			'Placer County',
			'Plumas County',
			'Riverside County',
			'Sacramento County',
			'San Benito County',
			'San Bernardino County',
			'San Diego County',
			'San Francisco County',
			'San Joaquin County',
			'San Luis Obispo County',
			'San Mateo County',
			'Santa Barbara County',
			'Santa Clara County',
			'Santa Cruz County',
			'Siskiyou County',
			'Solano County',
			'Sonoma County',
			'Stanislaus County',
			'Sutter County',
			'Tehama County',
			'Trinity County',
			'Tulare County',
			'Tuolumne County',
			'Ventura County',
			'Yolo County',
			'Yuba County',
		];
	}
}
