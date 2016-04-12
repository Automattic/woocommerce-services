import React, { PropTypes } from 'react';
import TextField from '../text-field';

const RenderField = ( { item, schema, settings, updateValue } ) => {
	const id = item.key ? item.key : item;
	if ( 'string' === schema.properties[id].type ) {
		return (
			<TextField
				id={ id }
				schema={ schema.properties[id] }
				value={ settings[id] }
				placeholder={ item.placeholder }
				updateValue={ value => updateValue( id, value ) }
			/>
		)
	}

	console.log( 'Don\'t know how to handle: ', item );
};

RenderField.propTypes = {
	item: PropTypes.oneOfType( [
		PropTypes.string.isRequired,
		PropTypes.shape( {
			key: PropTypes.string.isRequired,
		} ).isRequired,
	] ).isRequired,
	schema: PropTypes.shape( {
		properties: PropTypes.object.isRequired,
	} ).isRequired,
	settings: PropTypes.object.isRequired,
	updateValue: PropTypes.func.isRequired,
	updateSubValue: PropTypes.func.isRequired,
	updateSubSubValue: PropTypes.func.isRequired,
};

export default RenderField;