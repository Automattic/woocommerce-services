/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import reducer from './state/reducer';
import View from './view';

export default ( { methodId, instanceId } ) => ( {
	getReducer() {
		return reducer;
	},

	getHotReducer() {
		return reducer;
	},

	getInitialState() {
		return { status: 'new' };
	},

	getStateForPersisting( state ) {
		return state;
	},

	getStateKey() {
		return `wcs-settings-${ methodId }-${ instanceId }`;
	},

	View: () => {
		return <View />;
	},
} );
