import React, { PropTypes } from 'react';
import Indicators from 'components/indicators';
import TextField from 'components/text-field';
import RadioButtons from 'components/radio-buttons';
import ShippingServiceGroups from 'components/shipping/services';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/settings/actions';
import * as PackagesActions from 'state/form/packages/actions';
import { bindActionCreators } from 'redux';
import Packages from 'components/shipping/packages';

const SettingsItem = ( { form, layout, schema, settings, settingsActions, storeOptions, packagesActions } ) => {
	const id = layout.key ? layout.key : layout;
	const updateValue = ( value ) => settingsActions.updateSettingsField( id, value );
	const updateSubSubValue = ( key, subKey, val ) => settingsActions.updateSettingsObjectSubField( id, key, subKey, val );
	const removeArrayItem = ( idx ) => settingsActions.removeSettingsArrayFieldItem( id, idx );
	const savePackage = ( packageData ) => packagesActions.savePackage( id, packageData );

	switch ( layout.type ) {
		case 'radios':
			return (
				<RadioButtons
					layout={ layout }
					schema={ schema.properties[id] }
					value={ settings[id] }
					setValue={ updateValue }
				/>
			);

		case 'shipping_services':
			return (
				<ShippingServiceGroups
					services={ schema.definitions.services }
					settings={ settings[id] }
					currencySymbol={ storeOptions.currency_symbol }
					updateValue={ updateSubSubValue }
					settingsKey={ id }
				/>
			);

		case 'packages':
			const packagesState = form.packages;
			return (
				<Packages
					{ ...packagesState }
					{ ...packagesActions }
					packages={ settings[id] }
					presets={ schema.definitions.preset_boxes }
					dimensionUnit={ storeOptions.dimension_unit }
					removePackage={ removeArrayItem }
					savePackage={ savePackage }
					weightUnit={ storeOptions.weight_unit }
				/>
			);

		case 'indicators':
			return (
				<Indicators
					layout={ layout }
					schema={ schema.properties[id] }
					indicators={ Object.values( settings[id] ) }
				/>
			);

		default:
			return (
				<TextField
					id={ id }
					schema={ schema.properties[id] }
					value={ settings[id] }
					placeholder={ layout.placeholder }
					updateValue={ updateValue }
					validationHint={ layout.validation_hint }
					required={ schema.required && -1 !== schema.required.indexOf( id ) }
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
	settings: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
};

function mapStateToProps( state ) {
	return {
		settings: state.settings,
		form: state.form,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		packagesActions: bindActionCreators( PackagesActions, dispatch ),
		settingsActions: bindActionCreators( SettingsActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SettingsItem );
