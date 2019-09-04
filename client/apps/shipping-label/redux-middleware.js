/*
 * External dependencies
 */
import jQuery from 'jquery';
/**
 * Internal dependencies
 */
//from calypso
import {
	WOOCOMMERCE_SERVICES_SHIPPING_LABEL_PURCHASE_RESPONSE,
	WOOCOMMERCE_SERVICES_SHIPPING_LABEL_OPEN_PRINTING_FLOW,
} from '../../extensions/woocommerce/woocommerce-services/state/action-types';

const middlewareActions = {
	[  WOOCOMMERCE_SERVICES_SHIPPING_LABEL_PURCHASE_RESPONSE ]: ( { error } ) => {
		if ( error ) {
			return;
		}
		window.wc_shipment_tracking_refresh && window.wc_shipment_tracking_refresh();
	},
	[ WOOCOMMERCE_SERVICES_SHIPPING_LABEL_OPEN_PRINTING_FLOW ]: () => {
		//dismiss the nux pointer if the user opens the printing flow
		const labelMetabox = jQuery( '#woocommerce-order-label' );
		if ( labelMetabox.pointer ) {
			labelMetabox.pointer().pointer( 'close' );
		}
	},
};

export default () => ( next ) => ( action ) => {
	// let the action go to the reducers
	next( action );

	const middlewareAction = middlewareActions[ action.type ];
	if ( ! middlewareAction ) {
		return;
	}

	// perform the action
	middlewareAction( action );
};
