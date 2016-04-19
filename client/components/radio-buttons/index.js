import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormRadio from 'components/forms/form-radio';

const RadioButton = ( { id, value, title, updateValue } ) => {
	return (
		<FormLabel>
			<FormRadio value={ id } checked={ id === value } onChange={ () => updateValue( id ) }/>
			<span>{ title }</span>
		</FormLabel>
	);
};

const RadioButtons = ( { layout, schema, setting, updateValue } ) => {
	return (
		<FormFieldset>
			<FormLegend>{ schema.title }</FormLegend>
			{
				Object.keys( layout.titleMap ).map( key => (
					<RadioButton
						key={ key }
						id={ key }
						title={ layout.titleMap[key] }
						value={ setting }
						updateValue={ updateValue }
					/>
				) )
			}
		</FormFieldset>
	);
};

RadioButtons.propTypes = {
	layout: React.PropTypes.object.isRequired,
	schema: React.PropTypes.object.isRequired,
	setting: React.PropTypes.string.isRequired,
	updateValue: React.PropTypes.func.isRequired,
};

export default RadioButtons;
