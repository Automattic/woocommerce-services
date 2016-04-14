import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormRadio from 'components/forms/form-radio';

const RadioButton = ( { id, value, title, updateValue } ) => (
	<FormLabel>
		<FormRadio value={ id } checked={ id === value } onChange={ () => updateValue( id ) } />
		<span>{ title }</span>
	</FormLabel>
);

const RadioButtons = ( { schema, setting, updateValue } ) => (
	<FormFieldset>
		<FormLegend>{ schema.title }</FormLegend>
		{
			schema.oneOf.map( item => (
				<RadioButton key={ item.oneOf[0] } id={ item.oneOf[0] } title={ item.title } value={ setting } updateValue={ updateValue }/>
			) )
		}
	</FormFieldset>
);

RadioButtons.propTypes = {
	schema: React.PropTypes.object.isRequired,
	setting: React.PropTypes.string.isRequired,
	updateValue: React.PropTypes.func.isRequired,
}

export default RadioButtons;
