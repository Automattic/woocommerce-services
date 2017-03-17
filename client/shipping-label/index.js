import React from 'react';
import { combineReducers } from 'redux';
import ShippingLabelRootView from './views';
import shippingLabel from './state/reducer';
import _ from 'lodash';

// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, labelsData, paperSize, storeOptions, paymentMethod } ) => ( {
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
		// The phone field is never prefilled, so if it's present it means the address is fully valid
		const hasOriginAddress = Boolean( formData.origin.phone );
		const hasDestinationAddress = Boolean( formData.destination.phone );

		return {
			shippingLabel: {
				labels: labelsData || [],
				paperSize,
				paymentMethod,
				form: {
					orderId: formData.order_id,
					origin: {
						values: formData.origin,
						isNormalized: hasOriginAddress,
						normalized: hasOriginAddress ? formData.origin : null,
						// If no origin address is stored, mark all fields as "ignore validation" so the UI doesn't immediately show errors
						ignoreValidation: hasOriginAddress ? null : _.mapValues( formData.origin, () => true ),
						selectNormalized: true,
						normalizationInProgress: false,
						allowChangeCountry: false,
					},
					destination: {
						values: formData.destination,
						isNormalized: Boolean( formData.destination_normalized ),
						normalized: formData.destination_normalized ? formData.destination : null,
						// If no destination address is stored, mark all fields as "ignore validation" so the UI doesn't immediately show errors
						ignoreValidation: hasDestinationAddress ? null : _.mapValues( formData.destination, () => true ),
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

	getStateKey() {
		return `wcs-label-${formData.order_id}`;
	},

	View: () => (
		<ShippingLabelRootView
			storeOptions={ storeOptions } />
	),
} );
