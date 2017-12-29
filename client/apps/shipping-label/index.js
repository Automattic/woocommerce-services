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
import plugins from 'state/plugins/reducer';
import reducer from 'woocommerce/woocommerce-services/state/shipping-label/reducer';
import packagesReducer from 'woocommerce/woocommerce-services/state/packages/reducer';
import labelSettingsReducer from 'woocommerce/woocommerce-services/state/label-settings/reducer';
import { combineReducers } from 'state/utils';

export default ( { orderId } ) => ( {
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
							orders: combineReducers( {
								notes: combineReducers( {
									isLoading: () => ( { [ orderId ]: false } ),
									isLoaded: () => ( { [ orderId ]: true } ),
								} ),
							} ),
						} ),
					} ),
				} ),
			} ),
			notices,
			plugins,
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

	View: () => (
		<ShippingLabelViewWrapper orderId={ orderId } />
	),
} );
