/**
 * External dependencies
 */
import { UPDATE_TEXT_FIELD } from '../actions/settings';
import { TOGGLE_SERVICE } from '../actions/settings';

const settings = ( state = {}, action ) => {
	console.log( state );
	switch ( action.type ) {
		case UPDATE_TEXT_FIELD:
			return Object.assign( {}, state, {
				[action.key]: action.value
			} );
		case TOGGLE_SERVICE:
			console.log( 'in settings TOGGLE_SERVICE' );
			console.log( 'action.id=', action.id );
			const updatedServices = state.services.map( s => {
				if ( action.id !== s.id ) {
					return s;
				}
				s.enabled = ! s.enabled;
				return s;
			} );
			return Object.assign( {}, state, {
				services: updatedServices
			} );
	}

	return state;
};

export default settings;
