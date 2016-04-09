import {
	UPDATE_FORM_ELEMENT_FIELD,
} from '../actions/form';

const updateFormElementField = ( state, action ) => {
	const newState = Object.assign( {}, state );
	newState[ action.element ][ action.field ] = action.value;
	return newState;
};

export default function settings( state = {}, action ) {
	switch ( action.type ) {
		case UPDATE_FORM_ELEMENT_FIELD:
			return updateFormElementField( state, action );
	}

	return state;
}
