import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormSelect from 'components/forms/form-select';
import FormLegend from 'components/forms/form-legend';
import FieldError from 'components/field-error';
import sanitizeHTML from 'lib/utils/sanitize-html';

const Dropdown = ( { id, layout, schema, value, updateValue, error } ) => {
	return (
		<FormFieldset>
			<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( schema.title ) } />
			<FormSelect
				id={ id }
				name={ id }
				value={ value }
				onChange={ ( event ) => updateValue( event.target.value ) }
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
				{ error ? <FieldError text={ error } /> : null }
			</FormSelect>
		</FormFieldset>
	);
};

Dropdown.propTypes = {
	id: PropTypes.string.isRequired,
	layout: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	value: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
};

export default Dropdown;
