import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextarea from 'components/forms/form-textarea';
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
}

const TextArea = ( { id, schema, value, placeholder, layout, updateValue, error } ) => {
	const handleChangeEvent = event => updateValue( event.target.value );
	const readOnly = 'undefined' === typeof layout.readonly ? false : layout.readonly;

	return (
		<FormFieldset id={ id + '_container' }>
			<FormLabel htmlFor={ id }>{ schema.title }</FormLabel>
			<FormTextarea
				id={ id }
				name={ id }
				placeholder={ placeholder }
				readOnly={ readOnly }
				value={ value }
				onChange={ handleChangeEvent }
				isError={ error }
			/>
			{ error ? renderFieldError( error ) : renderFieldDescription( schema.description ) }
		</FormFieldset>
	);
};

TextArea.propTypes = {
	id: PropTypes.string.isRequired,
	layout: PropTypes.object.isRequired,
	schema: PropTypes.shape( {
		type: PropTypes.string.valueOf( 'string' ),
		title: PropTypes.string.isRequired,
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
