import React, { PropTypes } from 'react';
import TextField from 'components/text-field';
import ShippingServiceGroups from 'components/shipping-service-groups';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import RadioButtons from 'components/radio-buttons';

const RenderField = ( { item, schema, settings, wooCommerceSettings, updateValue, updateSubSubValue } ) => {
	const id = item.key ? item.key : item;
	const property = schema.properties[id];
	if ( 'string' === property.type ) {
		if ( schema.properties[id].oneOf ) {
			return (
				<RadioButtons schema={ property } setting={ settings[id] } updateValue={ val => updateValue( id, val ) } />
			);
		}

		return (
			<TextField
				id={ id }
				schema={ property }
				value={ settings[id] }
				placeholder={ item.placeholder }
				updateValue={ value => updateValue( id, value ) }
			/>
		);
	}

	if ( 'object' === property.type ) {
		if ( 'services' === id ) {
			return (
				<FormFieldset>
					<FormLegend>Services</FormLegend>
					<ShippingServiceGroups
						services={ schema.definitions.services }
						settings={ settings.services }
						currencySymbol={ wooCommerceSettings.currency_symbol }
						updateValue={ ( svc, key, val ) => updateSubSubValue( id, svc, key, val ) }
						settingsKey="services"
					/>
				</FormFieldset>
			);
		}
	}

	return (
		<FormFieldset>
			<FormLabel htmlFor="unknown" >Unknown</FormLabel>
			<FormSettingExplanation>Don't know how to handle: { JSON.stringify( property ) }</FormSettingExplanation>
		</FormFieldset>
	);
};

RenderField.propTypes = {
	item: PropTypes.oneOfType( [
		PropTypes.string.isRequired,
		PropTypes.shape( {
			key: PropTypes.string.isRequired,
		} ).isRequired,
	] ).isRequired,
	schema: PropTypes.shape( {
		properties: PropTypes.object.isRequired,
	} ).isRequired,
	settings: PropTypes.object.isRequired,
	wooCommerceSettings: PropTypes.object.isRequired,
	updateValue: PropTypes.func.isRequired,
	updateSubSubValue: PropTypes.func.isRequired,
};

export default RenderField;
