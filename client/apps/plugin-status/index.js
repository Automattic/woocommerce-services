/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import status from './state/reducer';
import View from './view';
// from calypso
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';

export default ( { formData } ) => ( {
	getReducer() {
		return combineReducers( {
			status,
			notices,
		} );
	},

	getInitialState() {
		return { status: formData };
	},

	getStateForPersisting() {
		return null;
	},

	getStateKey() {
		return 'wcs-admin-status';
	},

	View: () => (
		<View />
	),
} );
