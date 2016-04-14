import React, { PropTypes } from 'react';
import SectionHeader from 'components/section-header';
import FormToggle from 'components/forms/form-toggle';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormButton from 'components/forms/form-button';
import FormRadio from 'components/forms/form-radio';
import FormButtonsBar from 'components/forms/form-buttons-bar';
import CompactCard from 'components/card/compact';
import ShippingServiceGroups from 'components/shipping-service-groups';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/settings/actions';
import * as FormActions from 'state/form/actions';
import SettingsGroup from './render-group';
import Packages from 'components/shipping/packages';
import Notice from 'components/notice';

const handleSaveForm = ( event, props ) => {
	event.preventDefault();
	props.formActions.setField( 'isSaving', true );

	props.saveFormData( props.settings ).then( ( result ) => {
		props.formActions.setField( 'isSaving', false );
		if ( result.success ) {
			props.formActions.setField( 'error', null );
		} else if ( 'validation_failure' === result.data.error ) {
			props.formActions.setField( 'error', result.data.message );
		}
	} );
};

const getPackages = () => ( [
	{
		id: 'padded-envelope',
		type: 'mail',
		name: 'Large padded envelope',
		dimensions: '14 x 7 x .25 in',
	},
	{
		id: 'bike-box',
		type: 'flip-horizontal',
		name: 'Bike box',
		dimensions: '34 x 12 x 9.75 in',
	},
] );

const getPackageTypes = () => ( {
	user: {
		label: 'Saved packages',
		packages: [
			{
				id: 'padded-envelope',
				type: 'envelope',
				name: 'Large padded envelope',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'bike-box',
				type: 'box',
				name: 'Bike box',
				dimensions: '34 x 12 x 9.75 in',
			},
		],
	},
	service: {
		label: 'USPS Flat Rate Boxes and Envelopes',
		packages: [
			{
				id: 'small-box',
				type: 'box',
				name: 'Priority Mail Small Flat Rate Box',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'medium-box',
				type: 'box',
				name: 'Priority Mail Medium Flat Rate Box',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'large-box',
				type: 'box',
				name: 'Priority Mail Large Flat Rate Box',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'legal-envelope',
				type: 'box',
				name: 'Priority Mail Legal Flat Rate Envelope',
				dimensions: '14 x 7 x .25 in',
			},
		],
	},
} );

const Settings = ( props ) => {
	const { settings, form, wooCommerceSettings, settingsActions, schema, layout } = props;
	const { updateSettingsField, updateSettingsObjectSubField } = settingsActions;
	const renderFormErrors = () => {
		if ( form.error ) {
			return (
				<Notice status="is-error" text={ form.error } showDismiss={ false } />
			);
		}
	}
	return (
		<div>
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
						<FormRadio value="all" checked={ 'all' === settings.rate_filter } onChange={ () => updateSettingsField( 'rate_filter', 'all' ) } />
						<span>All available rates that apply and let them choose</span>
					</FormLabel>
					<FormLabel>
						<FormRadio value="cheapest" checked={ 'cheapest' === settings.rate_filter } onChange={ () => updateSettingsField( 'rate_filter', 'cheapest' ) } />
						<span>Only give them the one, cheapest rate</span>
					</FormLabel>
				</FormFieldset>
			</CompactCard>
			<CompactCard>
				<FormSectionHeading>Packages</FormSectionHeading>
				<Packages packages={ getPackages() } packageTypes={ getPackageTypes() } />
			</CompactCard>
			<CompactCard>
				<FormButtonsBar>
					{ renderFormErrors() }
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
