/**
 * Internal dependencies
 */
//from calypso
import {
	WOOCOMMERCE_SERVICES_SHIPPING_LABEL_PURCHASE_RESPONSE,
} from 'woocommerce/woocommerce-services/state/action-types';

const middlewareActions = {
	[  WOOCOMMERCE_SERVICES_SHIPPING_LABEL_PURCHASE_RESPONSE ]: ( { error } ) => {
		if ( error ) {
			return;
		}
		window.wc_shipment_tracking_refresh && window.wc_shipment_tracking_refresh();
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
