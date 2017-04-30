import { SET_FORM_DATA_VALUE, SET_FORM_META_PROPERTY } from './actions';

export const initialState = {
	meta: {
		error: false,
		fieldsStatus: false,
		isSaving: false,
		isFetching: false,
		pristine: true,
	},
	data: null,
};

const reducers = {};

reducers[ SET_FORM_DATA_VALUE ] = ( state, { key, value } ) => {
	return { ...state,
		meta: { ...state.meta,
			pristine: false,
		},
		data: { ...state.data,
			[ key ]: value,
		},
	};
};

reducers[ SET_FORM_META_PROPERTY ] = ( state, { key, value } ) => {
	return { ...state,
		meta: { ...state.meta,
			[ key ]: value,
		},
	};
};

const reducer = ( state = initialState, action ) => {
	if ( reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};

export default reducer;
