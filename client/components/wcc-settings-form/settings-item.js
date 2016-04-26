import React, { PropTypes } from 'react';
import TextField from 'components/text-field';
import RadioButtons from 'components/radio-buttons';
import ShippingServiceGroups from 'components/shipping/services';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/settings/actions';
import * as FormActions from 'state/form/actions';
import * as PackagesActions from 'state/form/packages/actions';
import { bindActionCreators } from 'redux';
import Packages from 'components/shipping/packages';

const SettingsItem = ( { form, layout, schema, settings, settingsActions, storeOptions, packagesActions } ) => {
	const id = layout.key ? layout.key : layout;
	const updateValue = ( value ) => settingsActions.updateSettingsField( id, value );
	const updateSubSubValue = ( key, subKey, val ) => settingsActions.updateSettingsObjectSubField( id, key, subKey, val );
	const removeArrayItem = ( idx ) => settingsActions.removeSettingsArrayFieldItem( id, idx );
	const savePackage = () => packagesActions.savePackage( id );

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
					packages={ settings[id] }
					presets={ schema.definitions.preset_boxes }
					dimensionUnit={ storeOptions.dimension_unit }
					removePackage={ removeArrayItem }
					addPackage={ packagesActions.addPackage }
					editPackage={ packagesActions.editPackage }
					dismissModal={ packagesActions.dismissModal }
					updatePackagesField={ packagesActions.updatePackagesField }
					savePackage={ savePackage }
					weightUnit={ storeOptions.weight_unit }
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
		formActions: bindActionCreators( FormActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SettingsItem );
