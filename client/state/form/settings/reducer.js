import {
	UPDATE_SETTINGS_FIELD,
	REMOVE_SETTINGS_FIELD,
	ADD_SETTINGS_ARRAY_FIELD_ITEM,
} from './actions';
import {
	SAVE_PACKAGE,
} from 'state/form/packages/actions';
import omit from 'lodash/omit';
import objectPath from 'object-path-immutable';

const reducers = {};

reducers[ UPDATE_SETTINGS_FIELD ] = ( state, action ) => {
	return objectPath.set( state, action.path, action.value );
};

reducers[ REMOVE_SETTINGS_FIELD ] = ( state, action ) => {
	return objectPath.del( state, action.path, action.value );
};

reducers[ ADD_SETTINGS_ARRAY_FIELD_ITEM ] = ( state, action ) => {
	return objectPath.push( state, action.settings_key, action.item );
}

reducers[ SAVE_PACKAGE ] = ( state, action ) => {
	const {
		settings_key,
		packageData,
	} = action;

	if ( packageData.box_weight ) {
		packageData.box_weight = Number.parseFloat( packageData.box_weight );
	}

	if ( packageData.max_weight ) {
		packageData.max_weight = Number.parseFloat( packageData.max_weight );
	}

	if ( packageData.hasOwnProperty( 'index' ) ) {
		const { index } = packageData;
		const item = omit( packageData, 'index' );

		return reducers[ UPDATE_SETTINGS_FIELD ]( state, {
			path: [ settings_key, index ],
			value: item,
		} );
	}

	return reducers[ ADD_SETTINGS_ARRAY_FIELD_ITEM ]( state, {
		settings_key,
		item: packageData,
	} );
};

const settings = ( state = {}, action ) => {
	if ( reducers.hasOwnProperty( action.type ) ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};

export default settings;
