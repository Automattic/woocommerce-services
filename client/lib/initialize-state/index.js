const getItemValue = ( schema, value ) => {
	if ( value ) {
		return value;
	}

	if ( schema.default ) {
		return schema.default;
	}

	switch ( schema.type ) {
		case 'boolean':
			return false;
		case 'number':
			return 0;
		case 'string':
			return '';
		case 'array':
			return [];
		case 'object':
			return {};
		default:
			return null;
	}
};

export default ( schema, values ) => {
	const formValues = {};

	Object.keys( schema.properties ).forEach( ( key ) => {
		formValues[key] = getItemValue( schema.properties[key], values[key] );
	} );

	return {
		settings: formValues,
		form: {
			isSaving: false,
		},
	};
};
