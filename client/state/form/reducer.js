import {
	UPDATE_FORM_ELEMENT_FIELD,
	SET_FIELD,
} from './actions';

const reducers = {};

reducers[UPDATE_FORM_ELEMENT_FIELD] = ( state, action ) => {
	const newObj = {};
	newObj[action.element] = {};
	newObj[action.element][action.field] = action.value;
	return Object.assign( {}, state, newObj );
};

reducers[SET_FIELD] = ( state, action ) => {
	const newObj = {};
	newObj[action.field] = action.value;
	return Object.assign( {}, state, newObj );
};

export default function settings( state = {}, action ) {
	const reducer = reducers[action.type];

	if ( reducer ) {
		return reducer( state, action );
	}

	return state;
}
