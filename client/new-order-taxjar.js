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
 * Adds the street to the data core posts for Recalculate and for deleting an item.
 *
 * @param {Object} event jQuery event.
 * @param {Object} data Request data built by core.
 * @return {Object|string} The data an earlier handler returned, or core's data, with `street` set.
 */
export const addStreetToRecalculateData = ( event, data ) => {
	// Core always passes an object, but anyone can trigger the event.
	data = data || {};

	const taxBasedOn = 'undefined' !== typeof woocommerce_admin_meta_boxes ? woocommerce_admin_meta_boxes.tax_based_on : '';
	const street = getTaxableStreet( taxBasedOn );

	// triggerHandler() passes core's original data to every handler and keeps only the
	// last value returned, so build on what an earlier handler returned, or its changes
	// are lost. $.ajax() takes either an object or a query string as request data; any
	// other return (false, a number) is not request data.
	const previous = event && event.result;

	if ( previous && 'string' === typeof previous ) {
		return previous + '&' + jQuery.param( { street } );
	}

	const target = previous && 'object' === typeof previous ? previous : data;

	target.street = street;

	return target;
};

const bindRecalculateFilter = () => {
	// Core fires these with jQuery's triggerHandler(), which runs only handlers bound
	// through jQuery (never addEventListener) and does not bubble, so bind with
	// jQuery, on the element itself. Deleting an item also saves unsaved item edits,
	// which recalculates tax from the posted address just like Recalculate does.
	jQuery( '#woocommerce-order-items' ).on(
		'woocommerce_order_meta_box_recalculate_ajax_data woocommerce_order_meta_box_delete_item_ajax_data',
		addStreetToRecalculateData
	);
};

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bindRecalculateFilter );
} else {
	bindRecalculateFilter();
}
