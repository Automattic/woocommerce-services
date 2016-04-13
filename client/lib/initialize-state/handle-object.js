// initializes the state of an object property in a schema
const getDefaultFields = ( properties ) => {
	return Object.keys( properties ).reduce( ( value, key ) => {
		value[key] = properties[key].default;
		return value;
	}, {} );
};

const getDefault = ( definition, itemSchema ) => {
	const defaultValues = Object.assign( {}, getDefaultFields( itemSchema.properties ) );
	return definition.reduce( ( prev, item ) => {
		prev[item.id] = Object.assign( {}, defaultValues, { id: item.id } );
		return prev;
	}, {} );
};

const handleObject = ( schema, value, definitions ) => {
	const defaultValues = getDefault( definitions[schema.definition], schema.items );

	if ( value ) {
		Object.keys( value ).forEach( key => {
			if ( ! defaultValues[key] ) { //ignore values that don't exist in the schema
				return;
			}

			Object.assign( defaultValues[key], value[key] );
		} );
	}

	return defaultValues;
};

export default handleObject;
