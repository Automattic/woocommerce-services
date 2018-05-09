<?php

if ( ! class_exists( 'WC_Abstract_Privacy' ) ) {
	return;
}

class WC_Connect_Privacy extends WC_Abstract_Privacy {
	/**
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $settings_store;

	public function __construct( WC_Connect_Service_Settings_Store $settings_store ) {
		parent::__construct( 'WooCommerce Services' );

		$this->settings_store = $settings_store;

		$this->add_exporter(
			'woocommerce-services-labels',
			__( 'WooCommerce Services Label Data', 'woocommerce-services' ),
			array( $this, 'label_data_exporter' )
		);
	}

	/**
	 * Gets the privacy message to display in the admin panel
	 */
	public function get_privacy_message() {
		return wpautop(
			sprintf(
				wp_kses(
					__( 'By using this extension, you may be storing personal data or sharing data with external services. <a href="%s" target="_blank">Learn more about how this works, including what you may want to include in your privacy policy.</a>', 'woocommerce-services' ),
					array( 'a' => array( 'href' => array(), 'target' => array() ) )
				),
				'https://jetpack.com/support/for-your-privacy-policy/#woocommerce-services'
			)
		);
	}

	/**
	 * Gets the customer's orders by page
	 * @param string  $email_address
	 * @param int     $page
	 * @return array WP_Post
	 */
	private function get_orders_page( $email_address, $page ) {
		$user = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$order_query    = array(
			'limit'          => 10,
			'page'           => $page,
		);
		if ( $user instanceof WP_User ) {
			$order_query['customer_id'] = (int) $user->ID;
		} else {
			$order_query['billing_email'] = $email_address;
		}
		return wc_get_orders( $order_query );
	}

	/**
	 * Handle exporting data for orders
	 * @param string  $email_address
	 * @param int     $page
	 * @return array
	 */
	public function label_data_exporter( $email_address, $page = 1 ) {
		$data_to_export = array();

		$orders = $this->get_orders_page( $email_address, $page );
		$done = empty( $orders );

		foreach ( $orders as $order ) {
			$order_id = $order->get_id();
			$labels = $this->settings_store->get_label_order_meta_data( $order_id );

			foreach ( $labels as $label ) {
				if ( empty( $label['tracking'] ) ) {
					continue;
				}
				$data_to_export[] = array(
					'group_id'    => 'woocommerce_orders',
					'group_label' => __( 'Orders', 'woocommerce-services' ),
					'item_id'     => 'order-' . $order_id,
					'data'        => array(
						array(
							'name'  => __( 'Shipping label service', 'woocommerce-services' ),
							'value' => $label['service_name'],
						),
						array(
							'name'  => __( 'Shipping label tracking number', 'woocommerce-services' ),
							'value' => $label['tracking'],
						),
					),
				);
			}
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}
}
