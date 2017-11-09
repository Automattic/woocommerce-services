/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ShippingLabelViewWrapper from './view-wrapper';
import initializeLabelsState from 'lib/initialize-labels-state';
// from calypso
import notices from 'state/notices/reducer';
import reducer from 'woocommerce/woocommerce-services/state/shipping-label/reducer';
import packagesReducer from 'woocommerce/woocommerce-services/state/packages/reducer';
import labelSettingsReducer from 'woocommerce/woocommerce-services/state/label-settings/reducer';
import { combineReducers } from 'state/utils';

export default ( { orderId, formData, labelsData, paperSize, storeOptions, paymentMethod, numPaymentMethods } ) => ( {
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
				} ),
			} ),
			notices,
			ui: () => ( {
				selectedSiteId: 1,
			} ),
		} );
	},

	getHotReducer() {
		return combineReducers( {
			shippingLabel: require( './state/reducer' ).default,
			notices,
		} );
	},

	getInitialState() {
		return {
			shippingLabel: initializeLabelsState( formData, labelsData, paperSize, storeOptions, paymentMethod, numPaymentMethods ),
		};
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
