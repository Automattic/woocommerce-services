/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ShippingLabelRootView from './view';
import shippingLabel from './state/reducer';
import initializeLabelsState from 'lib/initialize-labels-state';
// from calypso
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';

export default ( { orderId, formData, labelsData, paperSize, storeOptions, paymentMethod, numPaymentMethods } ) => ( {
	getReducer() {
		return combineReducers( {
			shippingLabel,
			notices,
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
		<ShippingLabelRootView />
	),
} );
