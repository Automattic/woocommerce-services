import React from 'react';
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

	View: () => {
		return <PrintTestLabelView />;
	},
} );
