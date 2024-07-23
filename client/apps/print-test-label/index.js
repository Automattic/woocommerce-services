/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import PrintTestLabelView from './view';
import reducer from './state/reducer';

export default ( { paperSize, storeOptions, isShippingLoaded } ) => ( {
	getReducer() {
		return reducer;
	},

	getInitialState() {
		return {
			paperSize,
			country: storeOptions.origin_country,
			isShippingLoaded,
		};
	},

	getStateKey() {
		return 'wcs-print-test-label';
	},

	View: () => {
		return <PrintTestLabelView />;
	},
} );
