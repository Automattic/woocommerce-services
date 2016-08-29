import React from 'react';
import { combineReducers } from 'redux';
import ShippingLabelRootView from './views';
import shippingLabel from './state/reducer';

// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, labelData, storeOptions } ) => ( {
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
			shippingLabel: {
				labels: labelData || {},
				form: {
					origin: {
						values: formData.origin,
						isValidated: Boolean( formData.origin.address ), // If the address field is filled, we assume it's an already validated address
						normalized: formData.origin.address ? formData.origin : null,
						pickNormalized: true,
						validationInProgress: false,
						allowChangeCountry: false,
					},
					destination: {
						values: formData.destination,
						isValidated: false,
						normalized: null,
						pickNormalized: true,
						validationInProgress: false,
						allowChangeCountry: false,
					},
					packages: {
						values: formData.packages,
						isPacked: formData.is_packed,
					},
					rates: {
						values: formData.rates,
						available: formData.rates.map( ( service ) => ( { [ service ]: { name: service, rate: 9.99 } } ) ),
					},
					preview: {
						values: {},
					},
				},
			},
		};
	},

	View: () => (
		<ShippingLabelRootView
			storeOptions={ storeOptions } />
	),
} );
