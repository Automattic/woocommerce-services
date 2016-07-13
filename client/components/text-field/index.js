import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormInputValidation from 'components/forms/form-input-validation';
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

const TextField = ( { id, schema, value, placeholder, updateValue, error } ) => {
	const handleChangeEvent = event => updateValue( event.target.value );

	return (
		<FormFieldset id={ id + '_container' }>
			<FormLabel htmlFor={ id }>{ schema.title }</FormLabel>
			<FormTextInput
				id={ id }
				name={ id }
				placeholder={ placeholder }
				value={ value }
				onChange={ handleChangeEvent }
				isError={ error }
			/>
			{ error ? renderFieldError( error ) : renderFieldDescription( schema.description ) }
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
