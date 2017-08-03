/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Indicators from 'components/indicators';
import Text from 'components/text';
import TextArea from 'components/text-area';
import NumberField from 'components/number-field';
import TextField from 'components/text-field';
import Toggle from 'components/toggle';
import RadioButtons from 'components/radio-buttons';
import Dropdown from 'components/dropdown';
import ShippingServiceGroups from '../shipping-services';
import FormLegend from 'components/forms/form-legend';

const SettingsItem = ( {
	form,
	layout,
	schema,
	formValueActions,
	storeOptions,
	errors,
	saveForm,
} ) => {
	const id = layout.key ? layout.key : layout;
	const updateValue = ( value ) => formValueActions.updateField( id, value );
	const updateSubValue = ( key, val ) => formValueActions.updateField( [ id ].concat( key ), val );
	const fieldValue = form.values[ id ];
	const fieldSchema = schema.properties[ id ];
	const fieldType = layout.type || fieldSchema.type || '';
	const fieldError = errors[ '' ] ? ( errors[ '' ].value || layout.validation_hint || '' ) : false;

	switch ( fieldType ) {
		case 'radios':
			return (
				<RadioButtons
					valuesMap={ layout.titleMap }
					title={ fieldSchema.title }
					description={ fieldSchema.description }
					value={ fieldValue }
					setValue={ updateValue }
					error={ fieldError }
				/>
			);

		case 'dropdown':
			return (
				<Dropdown
					id={ id }
					valuesMap={ layout.titleMap }
					title={ fieldSchema.title }
					description={ fieldSchema.description }
					value={ fieldValue }
					updateValue={ updateValue }
					error={ fieldError }
				/>
			);

		case 'shipping_services':
			return (
				<ShippingServiceGroups
					services={ schema.definitions.services }
					title={ fieldSchema.title }
					description={ fieldSchema.description }
					settings={ fieldValue }
					currencySymbol={ storeOptions.currency_symbol }
					updateValue={ updateSubValue }
					settingsKey={ id }
					errors={ errors }
					generalError={ fieldError }
				/>
			);

		case 'packages':
			return (
				<div>
					<FormLegend>{ __( 'Saved Packages' ) }</FormLegend>
					{ __(
						'Add and edit saved packages using the {{a}}Packaging Manager{{/a}}.',
						{
							components: {
								a: <a href="admin.php?page=wc-settings&tab=shipping&section=package-settings" />,
							},
						}
					) }
				</div>
			);

		case 'indicators':
			return (
				<Indicators
					title={ fieldSchema.title }
					subtitle={ fieldSchema.subtitle }
					indicators={ Object.values( fieldValue ) }
				/>
			);

		case 'text':
			return (
				<Text
					id={ id }
					title={ layout.title }
					className={ layout.class }
					value={ fieldValue || layout.description }
				/>
			);

		case 'textarea':
			return (
				<TextArea
					error={ fieldError }
					id={ id }
					readonly={ Boolean( layout.readonly ) }
					placeholder={ layout.placeholder }
					title={ fieldSchema.title }
					description={ fieldSchema.description }
					updateValue={ updateValue }
					value={ fieldValue }
				/>
			);

		case 'boolean':
			const onToggle = () => saveForm( schema );
			return (
				<Toggle
					checked={ Boolean( fieldValue ) }
					id={ id }
					title={ fieldSchema.title }
					description={ fieldSchema.description }
					trueText={ fieldSchema.trueText }
					falseText={ fieldSchema.falseText }
					saveOnToggle={ Boolean( fieldSchema.saveOnToggle ) }
					saveForm={ onToggle }
					updateValue={ updateValue }
				/>
			);

		case 'number':
			return (
				<NumberField
					id={ id }
					title={ fieldSchema.title }
					description={ fieldSchema.description }
					value={ fieldValue }
					placeholder={ layout.placeholder }
					updateValue={ updateValue }
					error={ fieldError }
				/>
			);

		default:
			return (
				<TextField
					id={ id }
					title={ fieldSchema.title }
					description={ fieldSchema.description }
					value={ fieldValue }
					placeholder={ layout.placeholder }
					updateValue={ updateValue }
					error={ fieldError }
				/>
			);
	}
};

SettingsItem.propTypes = {
	layout: PropTypes.oneOfType( [
		PropTypes.string.isRequired,
		PropTypes.object.isRequired,
	] ).isRequired,
	schema: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	form: PropTypes.object.isRequired,
	formValueActions: PropTypes.object.isRequired,
	errors: PropTypes.object,
	saveForm: PropTypes.func,
};

export default SettingsItem;
