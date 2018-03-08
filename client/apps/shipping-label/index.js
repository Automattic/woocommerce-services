/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ShippingLabelViewWrapper from './view-wrapper';
// from calypso
import notices from 'state/notices/reducer';
import reducer from 'woocommerce/woocommerce-services/state/shipping-label/reducer';
import packagesReducer from 'woocommerce/woocommerce-services/state/packages/reducer';
import labelSettingsReducer from 'woocommerce/woocommerce-services/state/label-settings/reducer';
import reduxMiddleware from './redux-middleware';
import ordersReducer from 'woocommerce/state/sites/orders/reducer';
import { combineReducers } from 'state/utils';
import { addHandlers } from 'state/data-layer/extensions-middleware';
import orders from 'woocommerce/state/data-layer/orders';

export default ( { orderId } ) => {
	addHandlers( 'woocommerce', orders );

	return {
		getReducer() {
			return combineReducers( {
				extensions: combineReducers( {
					woocommerce: combineReducers( {
						woocommerceServices: combineReducers( {
							1: combineReducers( {
								shippingLabel: reducer,
								packages: packagesReducer,
								labelSettings: labelSettingsReducer,
							} ),
						} ),
						sites: combineReducers( {
							1: combineReducers( {
								orders: ordersReducer,
							} ),
						} ),
					} ),
				} ),
				notices,
				ui: () => ( {
					selectedSiteId: 1,
				} ),
			} );
		},

		getInitialState() {
			return {};
		},

		getStateForPersisting() {
			return null; //do not persist any state for labels
		},

		getStateKey() {
			return `wcs-label-${ orderId }`;
		},

		getMiddleware() {
			return reduxMiddleware;
		},

		View: () => (
			<ShippingLabelViewWrapper orderId={ orderId } />
		),
	};
};
