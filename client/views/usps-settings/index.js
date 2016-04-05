import React from 'react';
import SectionHeader from 'components/section-header';
import FormToggle from 'components/forms/form-toggle';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormSelect from 'components/forms/form-select';
import FormButton from 'components/forms/form-button';
import FormRadio from 'components/forms/form-radio';
import FormCheckbox from 'components/forms/form-checkbox';
import FormButtonsBar from 'components/forms/form-buttons-bar';
import FormInputValidation from 'components/forms/form-input-validation';
import CompactCard from 'components/card/compact';
import Gridicon from 'components/gridicon';
import SelectOptGroups from 'components/forms/select-opt-groups';
import ShippingServiceGroups from '../shipping-services-groups';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/actions/settings';
import ShippingServiceSetup from 'components/shipping-service-setup';

const Settings = React.createClass( {
	displayName: 'Settings',
	onFieldChange: function( { target } = event ) {
		const { updateSettingsField } = this.props.actions;
		const key = target.name;
		const value = ( 'checkbox' === target.type ) ? target.checked : target.value;
		updateSettingsField( key, value );
	},
	render: function() {
		const { settings, wooCommerceSettings, actions } = this.props;
		return (
			<div>
				<SectionHeader label="USPS Shipping">
					<FormToggle id="enabled" name="enabled" checked={ true } readOnly={ true }><FormLabel htmlFor="enabled" style={ { float: 'left' } }>Enable</FormLabel></FormToggle>
				</SectionHeader>
				<CompactCard>
					<FormSectionHeading>Setup</FormSectionHeading>
					<ShippingServiceSetup titlePlaceholder="USPS" titleValue={ settings.title } onChange={ this.onFieldChange }>
						<FormFieldset>
							<FormLabel htmlFor="usps_account">USPS Account</FormLabel>
							<FormTextInput id="account_id" name="account_id" placeholder="WOOUSPS2016" value={ settings.account_id } onChange={ this.onFieldChange } />
							<FormSettingExplanation>
								Use the account provided or <a href="#">sign up for your own</a>
							</FormSettingExplanation>
						</FormFieldset>
					</ShippingServiceSetup>
				</CompactCard>
				<CompactCard>
					<FormSectionHeading>Rates</FormSectionHeading>
					<FormFieldset>
						<FormLegend>Services</FormLegend>
						<ShippingServiceGroups
							services={ settings.services }
							currencySymbol={ wooCommerceSettings.currency_symbol }
							onChange={ actions.updateSettingsArrayField }
							settingsKey="services"
						/>
					</FormFieldset>
					<FormFieldset>
						<FormLegend>Show the customer</FormLegend>
						<FormLabel>
							<FormRadio value="all" checked={ true } readOnly={ true } />
							<span>All available rates that apply and let them choose</span>
						</FormLabel>
						<FormLabel>
							<FormRadio value="cheapest" checked={ false } />
							<span>Only give them the one, cheapest rate</span>
						</FormLabel>
					</FormFieldset>
				</CompactCard>
				<CompactCard>
					<FormSectionHeading>Packages</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="packing_method">Packing method</FormLabel>
						<FormSelect id="packing_method" value="box_packing" readOnly={ true }>
							<option value="box_packing" readOnly={ true }>When cheaper, pack multiple items in a single package</option>
							<option value="per_item" readOnly={ true }>Pack items individually</option>
							<option value="weight_based" readOnly={ true }>Group regular items (less than 12 inches) and get a quote by weight</option>
						</FormSelect>
					</FormFieldset>
					<FormFieldset>
						<FormSettingExplanation>
							<Gridicon icon="info-outline" />
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
						<FormButton>Save changes</FormButton>
					</FormButtonsBar>
				</CompactCard>
				<br />{ /* Add a package modal */ }
				<CompactCard>
					<FormSectionHeading>Add a package</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="package_type">Type of package</FormLabel>
						<SelectOptGroups id="package_type" value="box" optGroups={ [
							{
								options: [
									{ value: 'box', label: 'Box' },
									{ value: 'envelope', label: 'Envelope' }
								]
							},
							{
								label: 'Saved packages',
								options: [
									{ value: 'bike-box', label: 'Bike box' },
									{ value: 'small-padded-envelope', label: 'Small padded envelope' }
								]
							},
							{
								label: 'USPS Flat Rate Boxes and Envelopes',
								options: [
									{ value: 'small-box', label: 'Priority Mail Small Flat Rate Box' },
									{ value: 'medium-box', label: 'Priority Mail Medium Flat Rate Box' },
									{ value: 'large-box', label: 'Priority Mail Large Flat Rate Box' },
									{ value: 'legal-envelope', label: 'Priority Mail Legal Flat Rate Envelope' }
								]
							}
						] } readOnly={ true } />
					</FormFieldset>
					<FormFieldset>
						<FormLabel htmlFor="package_name">Package name</FormLabel>
						<FormTextInput
							id="package_name"
							name="package_name"
							className="is-error"
							placeholder="The customer will see this during checkout" />
						<FormInputValidation isError text="A package name is needed" />
					</FormFieldset>
					<FormFieldset>
						<FormLabel>Inner Dimensions (L x W x H)</FormLabel>
						<FormTextInput placeholder="100 x 25 x 5.5" />
						<a href="#">Define exterior dimensions</a>
					</FormFieldset>
					<FormFieldset>
						<div style={ { width: '200px', float: 'left' } }>
							<FormLabel htmlFor="package_weight">Package weight</FormLabel>
							<FormTextInput style={ { width: '88%' } } id="package_weight" name="package_weight" placeholder="Weight of box" />
						</div>
						<div style={ { width: '200px', float: 'left' } }>
							<FormLabel htmlFor="max_weight">Max weight</FormLabel>
							<FormTextInput style={ { width: '88%' } } id="max_weight" name="max_weight" placeholder="Max weight" /> lbs
						</div>
						<FormSettingExplanation style={ { display: 'inline-block' } }>Define both the weight of the empty box and the max weight it can hold</FormSettingExplanation>
					</FormFieldset>
				</CompactCard>
				<CompactCard>
					<FormLabel>
						<FormCheckbox checked={ true } readOnly={ true } />
						<span>Save package to use in other shipping methods</span>
					</FormLabel>
					<FormButtonsBar>
						<FormButton>Add package</FormButton>
					</FormButtonsBar>
				</CompactCard>
			</div>
		);
	}
} );

function mapStateToProps( state ) {
	return {
		settings: state.settings
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		actions: bindActionCreators( SettingsActions, dispatch )
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Settings );
