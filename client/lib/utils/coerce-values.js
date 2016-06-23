import coerce from 'type-coerce';

const coerceFormValues = ( schema, values, definitions = null ) => {
	let coerced = {};
	if ( ( null === definitions ) && schema.hasOwnProperty( 'definitions' ) ) {
		definitions = schema.definitions;
	}
	Object.keys( values ).forEach( ( key ) => {
		if ( schema.properties.hasOwnProperty( key ) ) {
			const fieldSchema = schema.properties[ key ];
			let coercedValue = values[ key ];

			if ( fieldSchema.hasOwnProperty( 'type' ) ) {
				const fieldType = fieldSchema.type;
				if ( 'number' === fieldType ) {
					if ( isNaN( values[key] ) ) {
						coercedValue = values[key];
					} else {
						coercedValue = parseFloat( values[key] );
					}
				} else if ( coerce.hasOwnProperty( fieldType ) ) {
					coercedValue = coerce[ fieldType ]( values[ key ] );
				}

				if ( 'object' === fieldType ) {
					coercedValue = coerceFormValues( fieldSchema, coercedValue, definitions );
				} else if ( 'array' === fieldType ) {
					coercedValue = coercedValue.map( ( arrayItem ) => coerceFormValues( fieldSchema.items, arrayItem, definitions ) );
				}
			} else if ( fieldSchema.hasOwnProperty( '$ref' ) ) {
				const definitionKey = fieldSchema['$ref'].match( /^#\/definitions\/(.+)/ );

				if ( definitionKey && definitions.hasOwnProperty( definitionKey[1] ) ) {
					coercedValue = coerceFormValues( definitions[ definitionKey[1] ], coercedValue, definitions );
				}
			}

			coerced[ key ] = coercedValue;
		}
	} );

	return coerced;
};

export default coerceFormValues;
