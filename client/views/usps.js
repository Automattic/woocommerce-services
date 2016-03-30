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
import protectForm from 'lib/mixins/protect-form';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as SettingsActions from '../actions/settings';

const Settings = React.createClass( {
	displayName: 'Settings',
	mixins: [ protectForm.mixin ],
	render: function() {
		const { settings } = this.props;
		return (
			<form onChange={ this.markChanged }>
				<SectionHeader label="USPS Shipping">
					<FormToggle id="enabled" name="enabled" checked={ true } readOnly={ true }><FormLabel htmlFor="enabled" style={ { float: 'left' } }>Enable</FormLabel></FormToggle>
				</SectionHeader>
				<CompactCard>
					<FormSectionHeading>Setup</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="method_title">Shipping method title</FormLabel>
						<FormTextInput id="method_title" name="method_title" placeholder="USPS" value={ settings.method_title } onChange={ this.props.actions.onFieldChange } />
						<FormSettingExplanation>The customer will see this during checkout</FormSettingExplanation>
					</FormFieldset>
					<FormFieldset>
						<FormLabel htmlFor="usps_account">USPS Account</FormLabel>
						<FormTextInput id="usps_account" name="usps_account" placeholder="WOOUSPS2016" />
						<FormSettingExplanation>
							Use the account provided or <a href="#">sign up for your own</a>
						</FormSettingExplanation>
					</FormFieldset>
				</CompactCard>
				<CompactCard>
					<FormSectionHeading>Rates</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="rate_type">USPS Rates</FormLabel>
						<FormSelect id="rate_type" name="rate_type" value={ settings.rate_type } onChange={ this.props.actions.onFieldChange }>
							<option value="all">Use all available USPS rates</option>
							<option value="some">Let me pick which rates to offer</option>
						</FormSelect>
						<FormButton type="button" isPrimary={ false }>Advanced Options</FormButton>
						<FormSettingExplanation>These rates are automatically sent from the U.S. Post Office</FormSettingExplanation>
					</FormFieldset>
					<FormFieldset>
						<FormLabel>
							<FormCheckbox name="services[0]" checked={ !! settings['services[0]'] } onChange={ this.props.actions.onFieldChange } />
							<span>Priority Mail Express™</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>First-Class Mail®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Standard Post™</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Media Mail</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Library Mail</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Priority Mail®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Priority Mail Express Flat Rate®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Priority Mail Flat Rate®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Priority Mail Express International™</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Priority Mail International®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>Global Express Guaranteed®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>First Class Mail® International Letters</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={ true } readOnly={ true } />
							<span>International Postcards</span>
						</FormLabel>
					</FormFieldset>
					<FormFieldset>
						<FormLegend>Rate Pricing</FormLegend>
						<FormLabel>
							<FormRadio name="rate_type" value="retail" checked={ 'retail' === settings.rate_type } onChange={ this.props.actions.onFieldChange } />
							<span>Retail</span>
						</FormLabel>
						<FormLabel>
							<FormRadio name="rate_type" value="commercial" checked={ 'commercial' === settings.rate_type } onChange={ this.props.actions.onFieldChange } />
							<span>Commercial</span>
						</FormLabel>
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
			</form>
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
