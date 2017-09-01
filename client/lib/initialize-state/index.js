/**
 * Internal dependencies
 */
import handleObject from './handle-object';

const getItemValue = ( schema, value, definitions ) => {
	switch ( schema.type ) {
		case 'boolean':
			return value || schema.default || false;
		case 'number':
			return value || schema.default || 0;
		case 'string':
		case 'textarea':
			return value || schema.default || '';
		case 'array':
			return value || schema.default || [];
		case 'object':
			return handleObject( schema, value, definitions );
		default:
			return null;
	}
};

export default ( schema, values, layout, storeOptions, noticeDismissed ) => {
	if ( ! schema || ! values ) {
		return {
			isFetching: false,
			loaded: false,
		};
	}

	const formValues = {};
	const pristine = {};
	Object.keys( schema.properties ).forEach( ( key ) => {
		formValues[ key ] = getItemValue( schema.properties[ key ], values[ key ], schema.definitions );
		pristine[ key ] = true;
	} );

	return {
		isSaving: false,
		isFetching: false,
		loaded: true,
		pristine,
		currentStep: -1,
		values: formValues,
		shippingLabel: {},
		layout,
		schema,
		storeOptions,
		noticeDismissed,
	};
};
