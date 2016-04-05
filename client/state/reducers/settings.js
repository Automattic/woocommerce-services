import cloneDeep from 'lodash/cloneDeep';
import {
	UPDATE_SETTINGS_FIELD,
	UPDATE_SETTINGS_ARRAY_FIELD,
} from '../actions/settings';

export default function settings( state = {}, action ) {
	switch ( action.type ) {
		case UPDATE_SETTINGS_FIELD:
			return Object.assign( {}, state, {
				[action.key]: action.value
			} );
		case UPDATE_SETTINGS_ARRAY_FIELD:
			const originalArray = cloneDeep( state[ action.array_key ] );
			const updatedArray = originalArray.map( arrayItemState => {
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
}
