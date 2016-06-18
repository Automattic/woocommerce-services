import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import NumberInput from './number-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormInputValidation from 'components/forms/form-input-validation';
import parseNumber from 'lib/utils/parse-number';
import { sanitize } from 'dompurify';

const renderFieldDescription = ( description ) => {
	return (
		description ? <FormSettingExplanation dangerouslySetInnerHTML={ { __html: sanitize( description, { ADD_ATTR: [ 'target' ] } ) } } /> : null
	);
};

const renderFieldError = ( validationHint ) => {
	return (
		<FormInputValidation isError text={ validationHint } />
	);
};

const NumberField = ( { id, schema, value, placeholder, updateValue, error } ) => {
	return (
		<FormFieldset>
			<FormLabel htmlFor={ id }>{ schema.title }</FormLabel>
			<NumberInput
				id={ id }
				name={ id }
				placeholder={ placeholder }
				value={ value }
				onChange={ ( event ) => updateValue( parseNumber( event.target.value ) ) }
				isError={ error }
			/>
			{ error ? renderFieldError( error ) : renderFieldDescription( schema.description ) }
		</FormFieldset>
	);
};

NumberField.propTypes = {
	id: PropTypes.string.isRequired,
	schema: PropTypes.shape( {
		type: PropTypes.string.valueOf( 'number' ),
		title: PropTypes.string.isRequired,
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
