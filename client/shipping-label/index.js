import React from 'react';
import { combineReducers } from 'redux';
import ShippingLabelRootView from './views';
import shippingLabel from './state/reducer';

// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, storeOptions } ) => ( {
	getReducer() {
		return combineReducers( {
			shippingLabel,
			notices,
		} );
	},

	getHotReducer() {
		return combineReducers( {
			shippingLabel: require( './state/reducer' ),
			notices,
		} );
	},

	getInitialState() {
		return { shippingLabel: { formData } };
	},

	View: () => (
		<ShippingLabelRootView
			storeOptions={ storeOptions } />
	),
} );
