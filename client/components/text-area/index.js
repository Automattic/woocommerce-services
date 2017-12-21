/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextarea from 'components/forms/form-textarea';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

const TextArea = ( { id, readonly, title, description, value, placeholder, updateValue, error, className } ) => {
	const handleChangeEvent = event => updateValue( event.target.value );

	return (
		<FormFieldset className={ className }>
			<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
			<FormTextarea
				id={ id }
				name={ id }
				placeholder={ placeholder }
				readOnly={ readonly }
				value={ value }
				onChange={ handleChangeEvent }
			/>
			{ error ? <FieldError text={ error } /> : <FieldDescription text={ description } /> }
		</FormFieldset>
	);
};

TextArea.propTypes = {
	id: PropTypes.string.isRequired,
	readonly: PropTypes.bool,
	title: PropTypes.string,
	description: PropTypes.string,
	value: PropTypes.string.isRequired,
	updateValue: PropTypes.func,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
	className: PropTypes.string,
};

export default TextArea;
