/**
 * Retrieve a field's schema, handling referenced schema definitions if need be.
 *
 * @param {Object} fieldSchema - Schema object for field.
 * @param {Object} definitions - Common definitions.
 * @returns {Object} - Schema object for field, potentially pulled from $ref's definition.
 */
const getFieldSchema = ( fieldSchema, definitions ) => {
	if ( fieldSchema.$ref ) {
		const definitionKey = fieldSchema.$ref.match( /^#\/definitions\/(.+)/ );

		if ( definitionKey && definitions[ definitionKey[ 1 ] ] ) {
			fieldSchema = definitions[ definitionKey[ 1 ] ];
		}
	}
	return fieldSchema;
};

/**
 * Coerces a value into a type.
 *
 * @param {*} value - Value to coerce.
 * @param {string} type - Type to coerce value to.
 * @returns {*} - Coerced value.
 */
const coerceValue = ( value, type ) => {
	switch ( type ) {
		case 'number':
			if ( '' === value ) {
				return undefined;
			}

			if ( ! isNaN( value ) ) {
				return parseFloat( value );
			}

			return value;

		case 'boolean':
			const truthy = [ 'true', 'True', 'TRUE', '1', 1, true ];
			const falsy = [ 'false', 'False', 'FALSE', '0', 0, false ];

			if ( -1 !== truthy.indexOf( value ) ) {
				return true;
			}

			if ( -1 !== falsy.indexOf( value ) ) {
				return false;
			}

			return undefined;

		case 'string':
			return value.toString();

		default:
			return value;
	}
};

/**
 * Coerce a form values object using the provided schema.
 *
 * @param {Object} schema - Schema containing type declarations.
 * @param {Object} values - Form values.
 * @param {Object} [definitions=null] - Optional. Schema definitions, parsed from schema if omitted.
 * @returns {Object} - Coerced values based on schema.
 */
const coerceFormValues = ( schema, values, definitions = null ) => {
	let coerced = {};

	// Pull definitions from schema if not passed explicitly.
	if ( ( null === definitions ) && schema.definitions ) {
		definitions = schema.definitions;
	}

	// Coerce each form value
	Object.keys( values ).forEach( ( key ) => {
		// If the value is undefined or we don't have a schema type to reference, leave it be.
		if ( ( undefined === values[ key ] ) || ! schema.properties || ! schema.properties[ key ] ) {
			coerced[ key ] = values[ key ];
			return;
		}

		// Coerce this form value
		const fieldSchema = getFieldSchema( schema.properties[ key ], definitions );
		const fieldType = fieldSchema.type;
		let coercedValue = coerceValue( values[ key ], fieldType );

		// If the value is complex, coerce all nested values
		if ( 'object' === fieldType ) {
			coercedValue = coerceFormValues( fieldSchema, coercedValue, definitions );
		} else if ( 'array' === fieldType ) {
			coercedValue = coercedValue.map( ( arrayItem ) => coerceFormValues( fieldSchema.items, arrayItem, definitions ) );
		}

		coerced[ key ] = coercedValue;
	} );

	return coerced;
};

export default coerceFormValues;
