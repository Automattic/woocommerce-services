<?php

if ( ! class_exists( 'WC_Connect_Extension_Compatibility' ) ) {
	/**
	 * Handles compatibility between WooCommerce Shipping & Tax and other extensions.
	 */
	class WC_Connect_Extension_Compatibility {
		/**
		 * Function called when a new tracking number is added to the order
		 *
		 * @param int    $order_id        Order ID.
		 * @param string $carrier_id      Carrier ID, as returned on the label objects returned by the server.
		 * @param string $tracking_number Tracking number string.
		 */
		public static function on_new_tracking_number( $order_id, $carrier_id, $tracking_number ) {
			// Call WooCommerce Shipment Tracking if it's installed.
			if ( function_exists( 'wc_st_add_tracking_number' ) ) {
				// Note: the only carrier ID we use at the moment is 'usps', which is the same in WC_ST, but this might require a mapping.
				wc_st_add_tracking_number( $order_id, $tracking_number, $carrier_id );
			}
		}

		/**
		 * Checks if WooCommerce Tax should email the tracking details, or if another extension is taking care of that already
		 *
		 * @param int $order_id Order ID.
		 * @return boolean true if WCS should send the tracking info, false otherwise
		 */
		public static function should_email_tracking_details( $order_id ) {
			if ( function_exists( 'wc_shipment_tracking' ) ) {
				$shipment_tracking = wc_shipment_tracking();
				if ( property_exists( $shipment_tracking, 'actions' )
					&& method_exists( $shipment_tracking->actions, 'get_tracking_items' ) ) {
					$shipment_tracking_items = $shipment_tracking->actions->get_tracking_items( $order_id );
					if ( ! empty( $shipment_tracking_items ) ) {
						return false;
					}
				}
			}

			return true;
		}
	}
}//end if
