/*global woocommerce_admin_meta_boxes */
/**
 * External dependencies
 */
import jQuery from 'jquery';

const fieldValue = ( id ) => {
	const field = document.getElementById( id );

	return field && field.value ? field.value : '';
};

/**
 * Street for the address WooCommerce core posts when recalculating an order.
 *
 * Core's `get_taxable_address()` (meta-boxes-order.js) posts country, state,
 * postcode and city but no street. It reads the shipping fields when taxes are
 * based on shipping, and falls back to the billing fields when taxes are based
 * on anything else or the shipping country is empty. The street must come from
 * the same side, read live from the form, or TaxJar gets one address's ZIP with
 * another address's street.
 *
 * @param {string} taxBasedOn The `woocommerce_tax_based_on` setting.
 * @return {string} Street for the recalculation request.
 */
export const getTaxableStreet = ( taxBasedOn ) => {
	if ( 'shipping' === taxBasedOn && fieldValue( '_shipping_country' ) ) {
		return fieldValue( '_shipping_address_1' );
	}

	return fieldValue( '_billing_address_1' );
};

/**
 * Adds the street to the data core posts for the Recalculate button.
 *
 * @param {Object} event jQuery event.
 * @param {Object} data Request data built by core.
 * @return {Object} The same data, with `street` set.
 */
export const addStreetToRecalculateData = ( event, data ) => {
	const taxBasedOn = 'undefined' !== typeof woocommerce_admin_meta_boxes ? woocommerce_admin_meta_boxes.tax_based_on : '';

	data.street = getTaxableStreet( taxBasedOn );

	return data;
};

const bindRecalculateFilter = () => {
	// Core fires this with jQuery's triggerHandler(), which runs only handlers bound
	// through jQuery (never addEventListener) and does not bubble, so bind with
	// jQuery, on the element itself.
	jQuery( '#woocommerce-order-items' ).on( 'woocommerce_order_meta_box_recalculate_ajax_data', addStreetToRecalculateData );
};

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bindRecalculateFilter );
} else {
	bindRecalculateFilter();
}
