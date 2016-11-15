import React from 'react';
import { combineReducers } from 'redux';
import ShippingLabelRootView from './views';
import shippingLabel from './state/reducer';
import _ from 'lodash';

// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, labelsData, paperSize, storeOptions } ) => ( {
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
				paperSize,
				form: labelsData ? {} : {
					orderId: formData.order_id,
					origin: {
						values: formData.origin,
						isNormalized: Boolean( formData.origin.phone ), // If the phone field is filled, we assume it's an already normalized address
						normalized: formData.origin.phone ? formData.origin : null,
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
						all: formData.all_packages,
						flatRateGroups: formData.flat_rate_groups,
						selected: formData.selected_packages,
						unpacked: [],
						isPacked: formData.is_packed,
						saved: true,
					},
					rates: {
						values: _.isEmpty( formData.rates.selected ) ? _.mapValues( formData.packages, () => ( '' ) ) : formData.rates.selected,
						available: {},
						retrievalInProgress: false,
					},
					preview: {},
				},
				openedPackageId: labelsData ? '' : Object.keys( formData.selected_packages )[ 0 ] || '',
			},
		};
	},

	View: () => (
		<ShippingLabelRootView
			storeOptions={ storeOptions } />
	),
} );
