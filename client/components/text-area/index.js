import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextarea from 'components/forms/form-textarea';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

const TextArea = ( { id, schema, value, placeholder, layout, updateValue, error } ) => {
	const handleChangeEvent = event => updateValue( event.target.value );
	const readOnly = 'undefined' === typeof layout.readonly ? false : layout.readonly;

	return (
		<FormFieldset>
			<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( schema.title ) } />
			<FormTextarea
				id={ id }
				name={ id }
				placeholder={ placeholder }
				readOnly={ readOnly }
				value={ value }
				onChange={ handleChangeEvent }
				isError={ error }
			/>
			{ error ? <FieldError text={ error } /> : <FieldDescription text={ schema.description } /> }
		</FormFieldset>
	);
};

TextArea.propTypes = {
	id: PropTypes.string.isRequired,
	layout: PropTypes.object.isRequired,
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

export default TextArea;
