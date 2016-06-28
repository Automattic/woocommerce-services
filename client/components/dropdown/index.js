import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormSelect from 'components/forms/form-select';
import FormLegend from 'components/forms/form-legend';
import FormInputValidation from 'components/forms/form-input-validation';

const renderFieldError = ( validationHint ) => {
	return (
		<FormInputValidation isError text={ validationHint } />
	);
};

const Dropdown = ( { id, layout, schema, value, setValue, error } ) => {
	return (
		<FormFieldset>
			<FormLegend>{ schema.title }</FormLegend>
			<FormSelect
				id={ id }
				name={ id }
				value={ value }
				onChange={ ( event ) => setValue( event.target.value ) }
				isError={ error } >
				{ Object.keys( layout.titleMap ).map( key => {
					return (
						<option
							key={ key }
							value={ key }>
							{ layout.titleMap[ key ] }
						</option>
					);
				} ) }
				{ error ? renderFieldError( error ) : null }
			</FormSelect>
		</FormFieldset>
	);
};

Dropdown.propTypes = {
	layout: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	value: PropTypes.string.isRequired,
	setValue: PropTypes.func.isRequired,
};

export default Dropdown;
