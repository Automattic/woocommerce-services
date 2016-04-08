import {
	SET_FORM_STATE,
} from '../actions/form';

const setFormState = ( state, action ) => {
	console.log( 'Im here' );
	return Object.assign( {}, state, { currentState: action.value } );
};

export default function settings( state = {}, action ) {
	switch ( action.type ) {
		case SET_FORM_STATE:
			return setFormState( state, action );
	}

	return state;
}
