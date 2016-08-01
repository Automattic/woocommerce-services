let coerceFormValues;

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
 * @param {Object} schema - Schema containing type declarations.
 * @param {*} value - Value to coerce.
 * @param {Object} definitions - Schema definitions.
 * @returns {*} - Coerced value.
 */
const coerceValue = ( schema, value, definitions ) => {
	// If the value is undefined or we don't have a schema type to reference, leave it be.
	if ( ( undefined === value ) || ! schema ) {
		return value;
	}
	schema = getFieldSchema( schema, definitions );

	switch ( schema.type ) {
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
			return value ? value.toString() : '';

		case 'object':
			return coerceFormValues( schema, value, definitions );

		case 'array':
			return value.map( ( arrayItem ) => coerceValue( schema.items, arrayItem, definitions ) );

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
coerceFormValues = ( schema, values, definitions = null ) => {
	let coerced = {};

	// Pull definitions from schema if not passed explicitly.
	if ( ( null === definitions ) && schema.definitions ) {
		definitions = schema.definitions;
	}

	// Coerce each form value
	Object.keys( values ).forEach( ( key ) => {
		const fieldSchema = ( schema.properties || {} )[ key ];
		coerced[ key ] = coerceValue( fieldSchema, values[ key ], definitions );
	} );

	return coerced;
};

export default coerceFormValues;
