import {
	UPDATE_FORM_ELEMENT_FIELD,
} from '../actions/form';

const updateFormElementField = ( state, action ) => {
	const newObj = {};
	newObj[action.element] = {};
	newObj[action.element][action.field] = action.value;
	return Object.assign( {}, state, newObj );
};

export default function settings( state = {}, action ) {
	switch ( action.type ) {
		case UPDATE_FORM_ELEMENT_FIELD:
			return updateFormElementField( state, action );
	}

	return state;
}
