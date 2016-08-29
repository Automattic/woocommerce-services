import { SET_FORM_DATA_VALUE, SET_FORM_META_PROPERTY } from './actions';

export const initialState = {
	meta: {
		error: false,
		fieldsStatus: false,
		isSaving: false,
		pristine: true,
	},
	data: {
	},
};

const reducers = {};

reducers[ SET_FORM_DATA_VALUE ] = ( state, action ) => {
	const data = Object.assign( {}, state.data, { [ action.key ]: action.value } );
	const meta = Object.assign( {}, state.meta, { pristine: false } );
	return {
		meta: meta,
		data: data,
	};
};

reducers[ SET_FORM_META_PROPERTY ] = ( state, action ) => {
	const data = Object.assign( {}, state.data );
	const meta = Object.assign( {}, state.meta, { [ action.key ]: action.value } );
	return {
		meta: meta,
		data: data,
	};
};

const reducer = ( state, action ) => {
	if ( 'undefined' === typeof state ) {
		return initialState;
	}

	if ( 'function' === typeof reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};

export default reducer;
