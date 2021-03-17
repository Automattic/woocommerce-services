<?php
/**
 * WooCommerce Shipping note: DHL live rates available.
 *
 * Only for legacy customers that had the feature available.
 */

class WC_Connect_Note_DHL_Live_Rates_Available {

	use Automattic\WooCommerce\Admin\Notes\NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-services-dhl-live-rates-available';

	/**
	 * Maybe add note to inform WooCommerce Shipping users with legacy live rates about new DHL live rates.
	 *
	 * @param WC_Connect_Service_Schemas_Store $schemas   Store schemas.
	 */
	public static function init( WC_Connect_Service_Schemas_Store $schemas ) {
		// If store has DHL Express live rates.
		$has_wc_services_dhl_express = in_array( 'wc_services_dhlexpress', $schemas->get_all_shipping_method_ids(), true );

		if ( $has_wc_services_dhl_express ) {
			self::possibly_add_note();
		}
	}

	/**
	 * Get the note.
	 *
	 * @return Automattic\WooCommerce\Admin\Notes\Note
	 */
	public static function get_note() {
		$note = new Automattic\WooCommerce\Admin\Notes\Note();

		$note->set_title( __( 'DHL Express live rates are now available', 'woocommerce-services' ) );
		$note->set_content( __( 'Add DHL Express as a shipping method to selected shipping zones to display live rates at checkout.', 'woocommerce-services' ) );
		$note->set_type( Automattic\WooCommerce\Admin\Notes\Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-services' );
		$note->add_action(
			'go-to-shipping-zones',
			__( 'Go to shipping zones', 'woocommerce-services' ),
			admin_url( 'admin.php?page=wc-settings&tab=shipping' )
		);

		return $note;
	}
}
