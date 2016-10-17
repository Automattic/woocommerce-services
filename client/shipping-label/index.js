import React from 'react';
import { combineReducers } from 'redux';
import ShippingLabelRootView from './views';
import shippingLabel from './state/reducer';
import isEmpty from 'lodash/isEmpty';
import mapValues from 'lodash/mapValues';

// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, labelsData, storeOptions } ) => ( {
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
				labels: labelsData || [],
				form: labelsData ? {} : {
					orderId: formData.order_id,
					origin: {
						values: formData.origin,
						isNormalized: Boolean( formData.origin.address ), // If the address field is filled, we assume it's an already normalized address
						normalized: formData.origin.address ? formData.origin : null,
						selectNormalized: true,
						normalizationInProgress: false,
						allowChangeCountry: false,
					},
					destination: {
						values: formData.destination,
						isNormalized: false,
						normalized: null,
						selectNormalized: true,
						normalizationInProgress: false,
						allowChangeCountry: false,
					},
					packages: {
						values: formData.packages,
						isPacked: formData.is_packed,
					},
					rates: {
						values: isEmpty( formData.rates.selected ) ? mapValues( formData.packages, () => ( '' ) ) : formData.rates.selected,
						available: {},
						retrievalInProgress: false,
					},
					preview: {
						paperSize: '', // TODO: remember this setting in the merchant host
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
