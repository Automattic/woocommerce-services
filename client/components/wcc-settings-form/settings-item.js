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

const SettingsItem = ( {
	form,
	layout,
	schema,
	settings,
	settingsActions,
	storeOptions,
	packagesActions,
	errors,
} ) => {
	const id = layout.key ? layout.key : layout;
	const updateValue = ( value ) => settingsActions.updateSettingsField( id, value );
	const updateSubSubValue = ( key, subKey, val ) => settingsActions.updateSettingsObjectSubField( id, key, subKey, val );
	const removeArrayItem = ( idx ) => settingsActions.removeSettingsArrayFieldItem( id, idx );
	const savePackage = ( packageData ) => packagesActions.savePackage( id, packageData );
	const fieldRequired = ( -1 !== schema.required.indexOf( id ) );
	const fieldValue = settings[id];
	const fieldSchema = schema.properties[id];
	const fieldError = ( errors && errors.length ) ? ( layout.validation_hint || '' ) : false;

	switch ( layout.type ) {
		case 'radios':
			return (
				<RadioButtons
					layout={ layout }
					schema={ fieldSchema }
					value={ fieldValue }
					setValue={ updateValue }
					error={ fieldError }
				/>
			);

		case 'shipping_services':
			return (
				<ShippingServiceGroups
					services={ schema.definitions.services }
					settings={ fieldValue }
					currencySymbol={ storeOptions.currency_symbol }
					updateValue={ updateSubSubValue }
					settingsKey={ id }
					errors={ errors }
				/>
			);

		case 'packages':
			const packagesState = form.packages;
			return (
				<Packages
					{ ...packagesState }
					{ ...packagesActions }
					packages={ fieldValue }
					presets={ schema.definitions.preset_boxes }
					dimensionUnit={ storeOptions.dimension_unit }
					removePackage={ removeArrayItem }
					savePackage={ savePackage }
					weightUnit={ storeOptions.weight_unit }
					errors={ errors }
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
					schema={ fieldSchema }
					value={ fieldValue }
					placeholder={ layout.placeholder }
					updateValue={ updateValue }
					required={ fieldRequired }
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
