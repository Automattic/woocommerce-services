import React, { PropTypes } from 'react';
import SectionHeader from 'components/section-header';
import FormToggle from 'components/forms/form-toggle';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormSelect from 'components/forms/form-select';
import FormButton from 'components/forms/form-button';
import FormRadio from 'components/forms/form-radio';
import FormButtonsBar from 'components/forms/form-buttons-bar';
import CompactCard from 'components/card/compact';
import Gridicon from 'components/gridicon';
import ShippingServiceGroups from 'components/shipping-service-groups';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/actions/settings';
import * as FormActions from 'state/actions/form';
import SettingsGroup from './render-group';
import AddPackageDialog from 'components/shipping/packages/add-package';

const handleSaveForm = ( event, props ) => {
	event.preventDefault();
	props.formActions.setField( 'isSaving', true );

	props.saveFormData( props.settings ).then( () => {
		props.formActions.setField( 'isSaving', false );
	} );
};

const Settings = ( props ) => {
	const { settings, form, wooCommerceSettings, settingsActions, schema, layout } = props;
	const { updateSettingsField, updateSettingsObjectSubField } = settingsActions;
	return (
		<div>
			<AddPackageDialog />
			<SectionHeader label="USPS Shipping">
				<FormToggle id="enabled" name="enabled" checked={ true } readOnly={ true }>
					<FormLabel htmlFor="enabled" style={ { float: 'left' } }>Enable</FormLabel>
				</FormToggle>
			</SectionHeader>
			<SettingsGroup
				group={ layout[0] }
				schema={ schema }
				settings={ settings }
				updateValue={ updateSettingsField }
				updateSubValue={ () => {} }
				updateSubSubValue={ updateSettingsObjectSubField }
			/>
			<CompactCard>
				<FormSectionHeading>Rates</FormSectionHeading>
				<FormFieldset>
					<FormLegend>Services</FormLegend>
					<ShippingServiceGroups
						services={ schema.definitions.services }
						settings={ settings.services }
						currencySymbol={ wooCommerceSettings.currency_symbol }
						updateValue={ ( id, key, val ) => updateSettingsObjectSubField( 'services', id, key, val ) }
						settingsKey="services"
					/>
				</FormFieldset>
				<FormFieldset>
					<FormLegend>Show the customer</FormLegend>
					<FormLabel>
						<FormRadio value="all" checked={ true } readOnly={ true }/>
						<span>All available rates that apply and let them choose</span>
					</FormLabel>
					<FormLabel>
						<FormRadio value="cheapest" checked={ false }/>
						<span>Only give them the one, cheapest rate</span>
					</FormLabel>
				</FormFieldset>
			</CompactCard>
			<CompactCard>
				<FormSectionHeading>Packages</FormSectionHeading>
				<FormFieldset>
					<FormLabel htmlFor="packing_method">Packing method</FormLabel>
					<FormSelect id="packing_method" value="box_packing" readOnly={ true }>
						<option value="box_packing" readOnly={ true }>When cheaper, pack multiple items in a single
							package
						</option>
						<option value="per_item" readOnly={ true }>Pack items individually</option>
						<option value="weight_based" readOnly={ true }>Group regular items (less than 12 inches) and get
							a quote by weight
						</option>
					</FormSelect>
				</FormFieldset>
				<FormFieldset>
					<FormSettingExplanation>
						<Gridicon icon="info-outline"/>
						Your packages will appear here once you add them
					</FormSettingExplanation>
				</FormFieldset>
				<FormFieldset>
					<FormButton
						type="button"
						isPrimary={ false }
						style={ { float: 'left', marginLeft: 'initial' } }>
						Add a package
					</FormButton>
				</FormFieldset>
			</CompactCard>
			<CompactCard>
				<FormButtonsBar>
					<FormButton onClick={ ( event ) => handleSaveForm( event, props ) }>
						{ form.isSaving ? 'Saving...' : 'Save changes' }
					</FormButton>
				</FormButtonsBar>
			</CompactCard>
		</div>
	);
};

Settings.propTypes = {
	settingsActions: PropTypes.object.isRequired,
	formActions: PropTypes.object.isRequired,
	wooCommerceSettings: PropTypes.object.isRequired,
	settings: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

function mapStateToProps( state ) {
	return {
		settings: state.settings,
		form: state.form,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		settingsActions: bindActionCreators( SettingsActions, dispatch ),
		formActions: bindActionCreators( FormActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Settings );
