/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import PrintTestLabelView from './views';
import reducer from './state/reducer';

export default ( { paperSize, storeOptions } ) => ( {
	getReducer() {
		return reducer;
	},

	getHotReducer() {
		return require( './state/reducer' );
	},

	getInitialState() {
		return {
			paperSize,
			country: storeOptions.origin_country,
		};
	},

	getStateKey() {
		return 'wcs-print-test-label';
	},

	View: () => {
		return <PrintTestLabelView />;
	},
} );
