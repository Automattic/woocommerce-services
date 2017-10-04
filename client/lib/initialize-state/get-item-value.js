/**
 * External dependencies
 */
import _ from 'lodash';

function handleObject( schema, value ) {
	const schemaProps = _.get( schema, 'properties', {} );
	const fieldDefault = _.mapValues( schemaProps, ( propSchema, propId ) => {
		const propValue = _.get( value, propId, null );
		return getItemValue( propSchema, propValue );
	} );

	return fieldDefault;
}

function getItemValue( schema, value ) {
	switch ( schema.type ) {
		case 'boolean':
			return _.isNil( value ) ? ( schema.default || false ) : value;
		case 'number':
			return _.isNil( value ) ? ( schema.default || 0 ) : value;
		case 'string':
		case 'textarea':
			return value || schema.default || '';
		case 'array':
			return value || schema.default || [];
		case 'object':
			return handleObject( schema, value );
		default:
			return null;
	}
}

export default getItemValue;
