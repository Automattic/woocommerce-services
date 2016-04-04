/**
 * External dependencies
 */
import { UPDATE_SETTINGS_FIELD } from '../actions/settings';
import { UPDATE_SETTINGS_ARRAY_FIELD } from '../actions/settings';

const settings = ( state = {}, action ) => {
	switch ( action.type ) {
		case UPDATE_SETTINGS_FIELD:
			return Object.assign( {}, state, {
				[action.key]: action.value
			} );
		case UPDATE_SETTINGS_ARRAY_FIELD:
			const updatedArray = state[ action.array_key ].map( arrayItemState => {
				if ( action.id !== arrayItemState.id ) {
					return arrayItemState;
				}
				arrayItemState[ action.key ] = action.value;
				return arrayItemState;
			} );
			return Object.assign( {}, state, {
				[ action.array_key ]: updatedArray
			} );
	}

	return state;
};

export default settings;
