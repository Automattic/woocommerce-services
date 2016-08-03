import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormRadio from 'components/forms/form-radio';
import sanitizeHTML from 'lib/utils/sanitize-html';

const RadioButton = ( { value, currentValue, setValue, description } ) => {
	return (
		<FormLabel>
			<FormRadio value={ value } checked={ value === currentValue } onChange={ () => setValue( value ) } />
			<span dangerouslySetInnerHTML={ sanitizeHTML( description ) } />
		</FormLabel>
	);
};

const RadioButtons = ( { layout, schema, value, setValue } ) => {
	return (
		<FormFieldset>
			<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( schema.title ) } />
			{ Object.keys( layout.titleMap ).map( key => {
				return (
					<RadioButton
						key={ key }
						value={ key }
						currentValue={ value }
						setValue={ setValue }
						description={ layout.titleMap[ key ] }
					/>
				);
			} ) }
		</FormFieldset>
	);
};

RadioButtons.propTypes = {
	id: PropTypes.string.isRequired,
	layout: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	value: PropTypes.string.isRequired,
	setValue: PropTypes.func.isRequired,
};

export default RadioButtons;
