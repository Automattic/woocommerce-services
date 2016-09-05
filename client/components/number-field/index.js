import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import NumberInput from './number-input';
import parseNumber from 'lib/utils/parse-number';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

const NumberField = ( { id, title, description, value, placeholder, updateValue, error, className } ) => {
	return (
		<FormFieldset className={ className }>
			<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
			<NumberInput
				id={ id }
				name={ id }
				placeholder={ placeholder }
				value={ value }
				onChange={ ( event ) => updateValue( parseNumber( event.target.value ) ) }
				isError={ error }
			/>
			{ error ? <FieldError text={ error } /> : <FieldDescription text={ description } /> }
		</FormFieldset>
	);
};

NumberField.propTypes = {
	id: PropTypes.string.isRequired,
	title: PropTypes.string,
	description: PropTypes.string,
	value: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.number,
	] ).isRequired,
	updateValue: PropTypes.func,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
	className: PropTypes.string,
};

export default NumberField;
