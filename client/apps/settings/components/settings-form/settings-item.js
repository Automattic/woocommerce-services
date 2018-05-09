/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Text from 'components/text';
import TextArea from 'components/text-area';
import NumberField from 'components/number-field';
import TextField from 'components/text-field';
import Toggle from 'components/toggle';
import RadioButtons from 'components/radio-buttons';
import Dropdown from 'components/dropdown';
import ShippingServiceGroups from '../shipping-services';
import FormLegend from 'components/forms/form-legend';

class SettingsItem extends Component {
	static propTypes = {
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

	updateValue = ( value ) => {
		const {
			layout,
			formValueActions,
		} = this.props;
		const id = layout.key ? layout.key : layout;

		return formValueActions.updateField( id, value );
	}

	updateSubValue = ( key, val ) => {
		const {
			layout,
			formValueActions,
		} = this.props;
		const id = layout.key ? layout.key : layout;

		return formValueActions.updateField( [ id ].concat( key ), val );
	}

	onToggle = () => this.props.saveForm( this.props.schema );

	render() {
		const {
			form,
			layout,
			schema,
			storeOptions,
			errors,
		} = this.props;
		const id = layout.key ? layout.key : layout;
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
						setValue={ this.updateValue }
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
						updateValue={ this.updateValue }
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
						updateValue={ this.updateSubValue }
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
									a: <a href="admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings" />,
								},
							}
						) }
					</div>
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
						updateValue={ this.updateValue }
						value={ fieldValue }
					/>
				);

			case 'boolean':
				return (
					<Toggle
						checked={ Boolean( fieldValue ) }
						id={ id }
						title={ fieldSchema.title }
						description={ fieldSchema.description }
						trueText={ fieldSchema.trueText }
						falseText={ fieldSchema.falseText }
						saveOnToggle={ Boolean( fieldSchema.saveOnToggle ) }
						saveForm={ this.onToggle }
						updateValue={ this.updateValue }
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
						updateValue={ this.updateValue }
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
						updateValue={ this.updateValue }
						error={ fieldError }
					/>
				);
		}
	}
}

export default SettingsItem;
