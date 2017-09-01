/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import form from './state/reducer';
import Loading from './components/loading';
import Connect from './components/loading';
// from calypso
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';

export default ( { methodId, instanceId } ) => ( {
	getReducer() {
		return combineReducers( {
			form,
			notices,
		} );
	},

	getHotReducer() {
		return combineReducers( {
			form: require( './state/reducer' ).default,
			notices,
		} );
	},

	getInitialState() {
		return {};
	},

	getStateForPersisting( state ) {
		delete state.notices;
		return state;
	},

	getStateKey() {
		return `wcs-settings-${ methodId }-${ instanceId }`;
	},

	View: () => {
		if ( 2 === 1 + 1 ) {
			return <Loading />;
		}

		return <Connect />;
	},
} );
