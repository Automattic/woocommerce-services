import {
	UPDATE_SETTINGS_FIELD,
	ADD_SETTINGS_OBJECT_FIELD,
	REMOVE_SETTINGS_OBJECT_FIELD,
	UPDATE_SETTINGS_OBJECT_SUB_FIELD,
	REMOVE_SETTINGS_OBJECT_SUB_FIELD,
	ADD_SETTINGS_ARRAY_FIELD_ITEM,
	REMOVE_SETTINGS_ARRAY_FIELD_ITEM,
	UPDATE_SETTINGS_ARRAY_FIELD_ITEM,
} from './actions';
import cloneDeep from 'lodash/cloneDeep';

const reducers = {};

reducers[UPDATE_SETTINGS_FIELD] = ( state, action ) => {
	return Object.assign( {}, state, {
		[action.key]: action.value,
	} );
};

reducers[ADD_SETTINGS_OBJECT_FIELD] = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const updatedObject = Object.assign( {}, originalObject, {
		[action.key]: action.object,
	} );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

reducers[REMOVE_SETTINGS_OBJECT_FIELD] = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const updatedObject = Object.assign( {}, originalObject );
	delete updatedObject[action.key];

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

reducers[UPDATE_SETTINGS_OBJECT_SUB_FIELD] = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const originalSubObject = originalObject[action.key] || {};
	const updatedSubObject = Object.assign( {}, originalSubObject, {
		[action.subfield_key]: action.value,
	} );
	const updatedObject = Object.assign( {}, originalObject, {
		[action.key]: updatedSubObject,
	} );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

reducers[REMOVE_SETTINGS_OBJECT_SUB_FIELD] = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const originalSubObject = originalObject[action.key] || {};
	const updatedSubObject = Object.assign( {}, originalSubObject );
	delete updatedSubObject[action.subfield_key];
	const updatedObject = Object.assign( {}, originalObject, {
		[action.key]: updatedSubObject,
	} );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

reducers[ADD_SETTINGS_ARRAY_FIELD_ITEM] = ( state, action ) => {
	const originalArray = state[action.settings_key] || [];
	const updatedArray = cloneDeep( originalArray );
	updatedArray.push( action.item );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedArray,
	} );
}

reducers[REMOVE_SETTINGS_ARRAY_FIELD_ITEM] = ( state, action ) => {
	const originalArray = state[action.settings_key] || [];
	const updatedArray = originalArray.filter( ( item, idx ) => ( idx !== action.index ) );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedArray,
	} );
}

reducers[UPDATE_SETTINGS_ARRAY_FIELD_ITEM] = ( state, action ) => {
	const originalArray = state[action.settings_key] || [];
	const updatedArray = cloneDeep( originalArray );
	updatedArray[action.index] = action.item;

	return Object.assign( {}, state, {
		[action.settings_key]: updatedArray,
	} );
}

const settings = ( state = {}, action ) => {
	if ( reducers.hasOwnProperty( action.type ) ) {
		return reducers[action.type]( state, action );
	}
	return state;
};

export default settings;
