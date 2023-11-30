/*global woocommerce_admin_meta_boxes */
/**
 * External dependencies
 */
import jQuery from 'jquery';

jQuery( document ).ready( function() {
	/*
	* JavaScript for WooCommerce new order page
	*/
	const TaxJarOrder = function( $ ){ // eslint-disable-line no-unused-vars
		$( document ).ajaxSend( function( event, request, settings ) {
			// Making sure it's a recalculate event by checking `woocommerce_calc_line_taxes` before parsing to JSON.
			if ( settings.data && settings.data.indexOf( 'woocommerce_calc_line_taxes' ) > 0 ) {
				const data = JSON.parse( '{"' + decodeURIComponent( settings.data.replace( /&/g, '","' ).replace( /=/g, '":"' ) ) + '"}' );
 				if ( 'woocommerce_calc_line_taxes' === data.action ) {
					let street = '';
 					if ( 'shipping' === woocommerce_admin_meta_boxes.tax_based_on ) {
						street = $( '#_shipping_address_1' ).val();
					}
 					if ( 'billing' === woocommerce_admin_meta_boxes.tax_based_on ) {
						street = $( '#_billing_address_1' ).val();
					}
 					data.street = street;
					settings.data = $.param( data );
				}
			}
		} );
	}( jQuery );
});
