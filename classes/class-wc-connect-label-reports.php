<?php

if ( ! class_exists( 'WC_Connect_Label_Reports' ) ) {
	include_once WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php';

	class WC_Connect_Label_Reports extends WC_Admin_Report {
		const LABELS_TRANSIENT_KEY = 'wcs_label_reports';

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $settings_store;

		public function __construct( WC_Connect_Service_Settings_Store $settings_store ) {
			$this->settings_store = $settings_store;
		}

		public function get_export_button() {
			$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
			?>
			<a
				href="#"
				download="report-shipping-labels-<?php echo esc_attr( $current_range ); ?>-<?php echo esc_html(date_i18n( 'Y-m-d', current_time( 'timestamp' ) )); ?>.csv"
				class="export_csv"
				data-export="table"
			>
				<?php esc_html_e( 'Export CSV', 'woocommerce-services' ); ?>
			</a>
			<?php
		}

		private function compare_label_dates_desc( $label_a, $label_b ) {
			return $label_b['created'] - $label_a['created'];
		}

		private function get_all_labels() {
			global $wpdb;
			$query      = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'wc_connect_labels'";
			$db_results = $wpdb->get_results( $query );
			$results    = array();

			foreach ( $db_results as $meta ) {
				$labels = maybe_unserialize( $meta->meta_value );

				if ( ! is_array( $labels ) ) {
					$labels = $this->settings_store->try_deserialize_labels_json( $meta->meta_value );
				}

				if ( empty( $labels ) ) {
					continue;
				}

				foreach ( $labels as $label ) {
					$results[] = array_merge( $label, array( 'order_id' => $meta->post_id ) );
				}
			}

			usort( $results, array( $this, 'compare_label_dates_desc' ) );
			return $results;
		}

		private function query_labels() {
			$all_labels = get_transient( self::LABELS_TRANSIENT_KEY );
			if ( false === $all_labels ) {
				$all_labels = $this->get_all_labels();
				// set transient with ttl of 30 minutes
				set_transient( self::LABELS_TRANSIENT_KEY, $all_labels, 1800 );
			}

			// translate timestamps to JS timestapms
			$start_date = $this->start_date * 1000;
			$end_date   = $this->end_date * 1000;

			$results = array();
			foreach ( $all_labels as $label ) {
				$created = $label['created'];
				if ( $created > $end_date ) {
					continue;
				}

				// labels are sorted in descending order, so if we reached the end, break the loop
				if ( $created < $start_date ) {
					break;
				}

				if ( isset( $label['error'] ) || // ignore the error labels
					! isset( $label['rate'] ) ) { // labels where purchase hasn't completed for any reason
					continue;
				}

				// ignore labels with complete refunds
				if ( isset( $label['refund'] ) ) {
					$refund = (array) $label['refund'];
					if ( isset( $refund['status'] ) && 'completed' === $refund['status'] ) {
						continue;
					}
				}

				$results[] = $label;
			}

			return $results;
		}

		public function output_report() {
			$ranges = array(
				'year'       => __( 'Year', 'woocommerce-services' ),
				'last_month' => __( 'Last month', 'woocommerce-services' ),
				'month'      => __( 'This month', 'woocommerce-services' ),
				'7day'       => __( 'Last 7 days', 'woocommerce-services' ),
			);

			$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';

			if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
				$current_range = '7day';
			}

			$this->check_current_range_nonce( $current_range );
			$this->calculate_current_range( $current_range );

			$hide_sidebar = true;

			include WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php';
		}

		private function get_edit_order_link( $post_id ) {
			$order = wc_get_order( $post_id );
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return null;
			}
			return '<a href="' . $order->get_edit_order_url() . '">' . $order->get_order_number() . '</a>';
		}

		private function get_label_refund_status( $label ) {
			if ( ! isset( $label['refund'] ) ) {
				return '';
			}

			$refund = (array) $label['refund'];

			if ( isset( $refund['status'] ) &&
				( 'rejected' === $refund['status'] || 'complete' === $refund['status'] ) ) {
				return '';
			}

			return __( 'Requested', 'woocommerce-services' );
		}

		/**
		 * Get the main chart.
		 */
		public function get_main_chart() {
			$labels = $this->query_labels();

			?>
			<table class="widefat">
				<thead>
					<tr>
						<th>
							<?php esc_html_e( 'Time', 'woocommerce-services' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Order', 'woocommerce-services' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Price', 'woocommerce-services' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Service', 'woocommerce-services' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Refund', 'woocommerce-services' ); ?>
						</th>
					</tr>
				</thead>
				<?php if ( ! empty( $labels ) ) : ?>
					<tbody>
						<?php foreach ( $labels as $label ) : ?>
							<tr>
								<th scope="row">
									<?php echo esc_html(get_date_from_gmt( date( 'Y-m-d H:i:s', $label['created'] / 1000 ) )); ?>
								</th>
								<td>
									<?php echo esc_html($this->get_edit_order_link( $label['order_id'] )); ?>
								</td>
								<td>
									<?php echo esc_html(wc_price( $label['rate'] )); ?>
								</td>
								<td>
									<?php echo esc_html($label['service_name']); ?>
								</td>
								<td>
									<?php echo esc_html($this->get_label_refund_status( $label )); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<?php
							$total = array_sum( wp_list_pluck( $labels, 'rate' ) );
						?>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Total', 'woocommerce-services' ); ?>
							</th>
							<th>
								<?php echo count( $labels ); ?>
							</th>
							<th>
								<?php echo esc_html(wc_price( $total )); ?>
							</th>
							<th></th>
							<th></th>
						</tr>
				<?php else : ?>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'No labels found for this period', 'woocommerce-services' ); ?></td>
						</tr>
					</tbody>
				<?php endif; ?>
			</table>
			<?php
		}
	}
}
