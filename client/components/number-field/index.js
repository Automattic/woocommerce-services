import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import NumberInput from './number-input';
import parseNumber from 'lib/utils/parse-number';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

const NumberField = ( { id, schema, value, placeholder, updateValue, error } ) => {
	return (
		<FormFieldset>
			<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( schema.title ) } />
			<NumberInput
				id={ id }
				name={ id }
				placeholder={ placeholder }
				value={ value }
				onChange={ ( event ) => updateValue( parseNumber( event.target.value ) ) }
				isError={ error }
			/>
			{ error ? <FieldError text={ error } /> : <FieldDescription text={ schema.description } /> }
		</FormFieldset>
	);
};

NumberField.propTypes = {
	id: PropTypes.string.isRequired,
	schema: PropTypes.shape( {
		type: PropTypes.string.valueOf( 'number' ),
		title: PropTypes.string,
		description: PropTypes.string,
		default: PropTypes.string,
	} ).isRequired,
	value: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.number,
	] ).isRequired,
	updateValue: PropTypes.func,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
};

export default NumberField;
