<?php
/**
 * Admin View: renders the Sales Location Report screen within the tabbed interface.
 */

namespace TweaksForWoo\Admin\SalesReport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This page only handles GET requests for filtering display data; no state is modified.

class ReportView {

	/**
	 * Render the Sales Location Report page content inside the active tab.
	 */
	public static function render_page(): void {
		// Get filters
		$group_by = isset( $_GET['location_level'] ) ? sanitize_text_field( wp_unslash( $_GET['location_level'] ) ) : 'city';
		$range    = isset( $_GET['range'] )
			? sanitize_text_field( wp_unslash( $_GET['range'] ) )
			: 'this_year';
		$date_from = isset( $_GET['date_start'] ) ? sanitize_text_field( wp_unslash( $_GET['date_start'] ) ) : date_i18n( 'Y-m-d', strtotime( '-30 days' ) );
		$date_to   = isset( $_GET['date_end'] )   ? sanitize_text_field( wp_unslash( $_GET['date_end'] ) )   : date_i18n( 'Y-m-d' );
		$ca_only   = isset( $_GET['california_only'] ) && $_GET['california_only'] !== 'no';

		// Handle quick-range buttons
		if ( ! empty( $range ) ) {
			switch ( $range ) {
				case '7d':
					$date_from = date_i18n( 'Y-m-d', strtotime( '-7 days' ) );
					$date_to   = date_i18n( 'Y-m-d' );
					break;
				case '30d':
					$date_from = date_i18n( 'Y-m-d', strtotime( '-30 days' ) );
					$date_to   = date_i18n( 'Y-m-d' );
					break;
				case '90d':
					$date_from = date_i18n( 'Y-m-d', strtotime( '-90 days' ) );
					$date_to   = date_i18n( 'Y-m-d' );
					break;
				case 'this_month':
					$date_from = date_i18n( 'Y-m-01' );
					$date_to   = date_i18n( 'Y-m-t' );
					break;
				case 'last_month':
					$date_from = date_i18n( 'Y-m-01', strtotime( '-1 month' ) );
					$date_to   = date_i18n( 'Y-m-t', strtotime( '-1 month' ) );
					break;
				case 'this_year':
					$date_from = date_i18n( 'Y-01-01' );
					$date_to   = date_i18n( 'Y-m-d' );
					break;
				case 'last_year':
					$year      = date_i18n( 'Y', strtotime( '-1 year' ) );
					$date_from = date_i18n( "{$year}-01-01" );
					$date_to   = date_i18n( "{$year}-12-31" );
					break;
			}
		}

		// Fetch data
		$totals  = ReportData::get_totals( $group_by, $date_from, $date_to, $ca_only );
		$grand   = ReportData::get_grand_total( $date_from, $date_to, $ca_only );

		// Tab labels for the report sub-tabs
		$report_tabs = [
			'all'    => __( 'All Levels', 'tweaks-for-woo' ),
			'state'  => __( 'By State', 'tweaks-for-woo' ),
			'city'   => __( 'By City', 'tweaks-for-woo' ),
		];

		?>
		<div class="wrap tweaks-sales-location-report">

			<p class="description">
				<?php echo esc_html__( 'Aggregated order totals grouped by state and city billing address. Designed for California tax reporting.', 'tweaks-for-woo' ); ?>
			</p>

			<hr class="wp-header-end" />

			<!-- Filters -->
			<form method="get" action="">
				<input type="hidden" name="page" value="tweaks-for-woo" />
				<input type="hidden" name="tab" value="sales-report" />
				<input type="hidden" name="location_level" value="<?php echo esc_attr( $group_by ); ?>" />

				<div class="tflc-filters">
					<label>
						<?php esc_html_e( 'From:', 'tweaks-for-woo' ); ?>
						<input type="date" name="date_start" value="<?php echo esc_attr( $date_from ); ?>" required />
					</label>

					<label>
						<?php esc_html_e( 'To:', 'tweaks-for-woo' ); ?>
						<input type="date" name="date_end" value="<?php echo esc_attr( $date_to ); ?>" required />
					</label>

					<label class="tflc-ca-toggle">
						<input type="checkbox" name="california_only" value="yes" <?php checked( $ca_only, true ); ?>
						       onchange="this.form.submit()" />
						<?php esc_html_e( 'California orders only', 'tweaks-for-woo' ); ?>
					</label>

					<div class="tflc-quick-range">
						<span class="dashicons dashicons-calendar" style="margin-right:4px"></span>
						<?php esc_html_e( 'Quick Range:', 'tweaks-for-woo' ); ?>
						<select name="range" onchange="this.form.submit()">
							<option value="" <?php selected( empty( $range ), true ); ?>><?php esc_html_e( 'Custom', 'tweaks-for-woo' ); ?></option>
							<option value="7d" <?php selected( $range === '7d', true ); ?>><?php esc_html_e( 'Last 7 Days', 'tweaks-for-woo' ); ?></option>
							<option value="30d" <?php selected( $range === '30d', true ); ?>><?php esc_html_e( 'Last 30 Days', 'tweaks-for-woo' ); ?></option>
							<option value="90d" <?php selected( $range === '90d', true ); ?>><?php esc_html_e( 'Last 90 Days', 'tweaks-for-woo' ); ?></option>
							<option value="this_month" <?php selected( $range === 'this_month', true ); ?>><?php esc_html_e( 'This Month', 'tweaks-for-woo' ); ?></option>
							<option value="last_month" <?php selected( $range === 'last_month', true ); ?>><?php esc_html_e( 'Last Month', 'tweaks-for-woo' ); ?></option>
							<option value="this_year" <?php selected( $range === 'this_year', true ); ?>><?php esc_html_e( 'This Year', 'tweaks-for-woo' ); ?></option>
							<option value="last_year" <?php selected( $range === 'last_year', true ); ?>><?php esc_html_e( 'Last Year', 'tweaks-for-woo' ); ?></option>
						</select>
					</div>

					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Get Report', 'tweaks-for-woo' ); ?>
					</button>
				</div>
			</form>

			<!-- Summary Card -->
			<div class="tflc-summary">
				<div class="tflc-summary-card">
					<span class="tflc-summary-label"><?php esc_html_e( 'Total Revenue', 'tweaks-for-woo' ); ?></span>
					<span class="tflc-summary-value"><?php echo wp_kses( wc_price( $grand ), array( 'span' => array( 'class' => true ) ) ); ?></span>
				</div>
			</div>

			<!-- Report Sub-Tabs -->
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $report_tabs as $key => $label ): ?>
					<a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'sales-report', 'location_level' => $key ] ) ); ?>"
					   class="nav-tab <?php echo $group_by === $key ? ' nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<!-- Tab Content -->
			<div class="tflc-tab-content">

				<?php if ( empty( $totals ) ): ?>
					<p class="description">
						<?php esc_html_e( 'No orders found for the selected period.', 'tweaks-for-woo' ); ?>
					</p>
				<?php else: ?>

					<?php if ( $group_by === 'all' ): ?>
						<!-- Combined table: State | City -->
						<h3><?php esc_html_e( 'Combined Breakdown', 'tweaks-for-woo' ); ?></h3>
						<div class="tflc-table-wrapper">
							<table class="wp-list-table widefat striped">
								<thead>
									<tr>
										<th style="width:50%"><?php esc_html_e( 'State', 'tweaks-for-woo' ); ?></th>
										<th style="width:50%"><?php esc_html_e( 'City', 'tweaks-for-woo' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$states_list  = array_filter( $totals, fn( $r ) => $r['level'] === 'state' );
									$cities_list  = array_filter( $totals, fn( $r ) => $r['level'] === 'city' );

									$max_rows = max( count( $states_list ), count( $cities_list ), 1 );

									for ( $i = 0; $i < $max_rows; $i++ ):
										$row_states  = $states_list[ $i ] ?? null;
										$row_cities   = $cities_list[ $i ] ?? null;
									?>
									<tr>
										<td>
											<?php if ( $row_states ): ?>
												<strong><?php echo esc_html( $row_states['name'] ); ?></strong><br />
												<small class="tflc-amount"><?php echo wp_kses( wc_price( $row_states['total'] ), array( 'span' => array( 'class' => true ), 'bdi' => array( 'class' => true ) ) ); ?></small>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( $row_cities ): ?>
												<strong><?php echo esc_html( $row_cities['name'] ); ?></strong><br />
												<small class="tflc-amount"><?php echo wp_kses( wc_price( $row_cities['total'] ), array( 'span' => array( 'class' => true ), 'bdi' => array( 'class' => true ) ) ); ?></small>
											<?php endif; ?>
										</td>
									</tr>
									<?php endfor; ?>

									<!-- Grand total footer row -->
									<tfoot>
										<tr class="tflc-grand-total-row">
											<td colspan="2"><strong><?php echo esc_html( __( 'Grand Total', 'tweaks-for-woo' ) ); ?>:</strong> <?php echo wp_kses( wc_price( $grand ), array( 'span' => array( 'class' => true ), 'bdi' => array( 'class' => true ) ) ); ?></td>
										</tr>
									</tfoot>
								</tbody>
							</table>
						</div>

					<?php else: ?>
						<!-- Single level table -->
						<h3><?php echo esc_html( ucwords( str_replace( '_', ' ', $group_by ) ) ); ?></h3>
						<div class="tflc-table-wrapper">
							<table class="wp-list-table widefat striped">
								<thead>
									<tr>
										<th><?php echo esc_html( $group_by === 'state' ? __( 'State', 'tweaks-for-woo' ) : __( 'City', 'tweaks-for-woo' ) ); ?></th>
										<th><?php esc_html_e( 'Revenue', 'tweaks-for-woo' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $totals as $row ): ?>
										<tr>
											<td><strong><?php echo esc_html( $row['name'] ); ?></strong></td>
											<td class="tflc-amount"><?php echo wp_kses( wc_price( $row['total'] ), array( 'span' => array( 'class' => true ), 'bdi' => array( 'class' => true ) ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								<tfoot>
									<tr class="tflc-grand-total-row">
										<td><strong><?php echo esc_html( __( 'Total', 'tweaks-for-woo' ) ); ?></strong></td>
										<td class="tflc-amount"><strong><?php echo wp_kses( wc_price( $grand ), array( 'span' => array( 'class' => true ), 'bdi' => array( 'class' => true ) ) ); ?></strong></td>
									</tr>
								</tfoot>
							</table>
						</div>
					<?php endif; ?>

				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
