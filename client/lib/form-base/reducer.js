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
	return Object.assign( {}, state, {
		meta: [
			...state.meta,
			{
				pristine: false,
			},
		],
		data: [
			...state.data,
			{
				[ action.key ]: action.value,
			},
		],
	} );
};

reducers[ SET_FORM_META_PROPERTY ] = ( state, action ) => {
	return Object.assign( {}, state, {
		meta: [
			...state.meta,
			{
				[ action.key ]: action.value,
			},
		],
		data: [
			...state.data,
		],
	} );
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
