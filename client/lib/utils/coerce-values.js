import coerce from 'type-coerce';

const coerceTypeMap = {
	number: 'float',
};

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
				const coerceType = coerceTypeMap.hasOwnProperty( fieldType ) ? coerceTypeMap[ fieldType ] : fieldType;
				if ( coerce.hasOwnProperty( coerceType ) ) {
					coercedValue = coerce[ coerceType ]( values[ key ] );
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
