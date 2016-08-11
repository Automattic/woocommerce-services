import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormSelect from 'components/forms/form-select';
import FormLegend from 'components/forms/form-legend';
import FieldError from 'components/field-error';
import sanitizeHTML from 'lib/utils/sanitize-html';
import FieldDescription from 'components/field-description';

const Dropdown = ( { id, valuesMap, title, description, value, updateValue, error } ) => {
	return (
		<FormFieldset>
			<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
			<FormSelect
				id={ id }
				name={ id }
				value={ value }
				onChange={ ( event ) => updateValue( event.target.value ) }
				isError={ error } >
				{ Object.keys( valuesMap ).map( key => {
					return (
						<option
							key={ key }
							value={ key }>
							{ valuesMap[ key ] }
						</option>
					);
				} ) }
			</FormSelect>
			{ error ? <FieldError text={ error } /> : <FieldDescription text={ description } /> }
		</FormFieldset>
	);
};

Dropdown.propTypes = {
	id: PropTypes.string.isRequired,
	valuesMap: PropTypes.object.isRequired,
	title: PropTypes.string,
	description: PropTypes.string,
	value: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
};

export default Dropdown;
