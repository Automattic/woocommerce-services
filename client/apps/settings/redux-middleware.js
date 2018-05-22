/**
 * Internal dependencies
 */
//from calypso
import { WOOCOMMERCE_SHIPPING_ZONE_METHOD_UPDATED } from 'woocommerce/state/action-types';
import {
	WOOCOMMERCE_SERVICES_SHIPPING_ZONE_METHOD_SETTINGS_REQUEST_SUCCESS,
} from 'woocommerce/woocommerce-services/state/action-types';
import { fetchShippingMethodSchemaSuccess } from 'woocommerce/woocommerce-services/state/shipping-method-schemas/actions';
import { openShippingZoneMethod } from 'woocommerce/state/ui/shipping/zones/methods/actions';

const middlewareActions = {
	[  WOOCOMMERCE_SERVICES_SHIPPING_ZONE_METHOD_SETTINGS_REQUEST_SUCCESS ]: ( { siteId, instanceId, data }, dispatch ) => {
		// When getting the shipping method settings, store the form schema in the state tree too so we don't make an extra request
		dispatch( fetchShippingMethodSchemaSuccess( siteId, data.methodId, data ) );
	},

	[ WOOCOMMERCE_SHIPPING_ZONE_METHOD_UPDATED ]: ( { siteId, originatingAction: { methodId } }, dispatch ) => {
		// Make sure the updated shipping method stays open for further editing after it's updated
		dispatch( openShippingZoneMethod( siteId, methodId ) );
	},
};

export default ( { dispatch } ) => ( next ) => ( action ) => {
	// let the action go to the reducers
	next( action );

	const middlewareAction = middlewareActions[ action.type ];
	if ( middlewareAction ) {
		// perform the action
		middlewareAction( action, dispatch );
	}
};
