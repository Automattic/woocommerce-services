import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

const TextField = ( { id, schema, value, placeholder, updateValue, error } ) => {
	const handleChangeEvent = event => updateValue( event.target.value );

	return (
		<FormFieldset>
			<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( schema.title ) } />
			<FormTextInput
				id={ id }
				name={ id }
				placeholder={ placeholder }
				value={ value }
				onChange={ handleChangeEvent }
				isError={ error }
			/>
			{ error ? <FieldError text={ error } /> : <FieldDescription text={ schema.description } /> }
		</FormFieldset>
	);
};

TextField.propTypes = {
	id: PropTypes.string.isRequired,
	schema: PropTypes.shape( {
		type: PropTypes.string.valueOf( 'string' ),
		title: PropTypes.string,
		description: PropTypes.string,
		default: PropTypes.string,
	} ).isRequired,
	value: PropTypes.string.isRequired,
	updateValue: PropTypes.func,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
};

export default TextField;
