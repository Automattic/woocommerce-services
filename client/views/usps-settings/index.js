import React, { PropTypes } from 'react';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import FormButton from 'components/forms/form-button';
import FormButtonsBar from 'components/forms/form-buttons-bar';
import CompactCard from 'components/card/compact';
import ShippingServiceGroups from 'components/shipping-service-groups';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/settings/actions';
import * as FormActions from 'state/form/actions';
import SettingsGroup from './render-group';
import Notice from 'components/notice';
import { translate as __ } from 'lib/mixins/i18n';
import RadioButtons from 'components/radio-buttons';

const handleSaveForm = ( event, props ) => {
	event.preventDefault();
	props.formActions.setField( 'isSaving', true );

	props.saveFormData( props.settings ).then( ( result ) => {
		props.formActions.setField( 'isSaving', false );
		if ( result.success ) {
			props.formActions.setField( 'error', null );
			props.formActions.setField( 'success', true );
		} else if ( 'validation_failure' === result.data.error ) {
			props.formActions.setField( 'error', result.data.message );
		}
	} );
};

const findLayoutInfo = ( layout, target ) => {
	for ( let groupIdx = 0; groupIdx < layout.length; groupIdx++ ) {
		for ( let fieldIdx = 0; fieldIdx < layout[groupIdx].items.length; fieldIdx++ ) {
			if ( target === layout[groupIdx].items[fieldIdx].key ) {
				return layout[groupIdx].items[fieldIdx];
			}
		}
	}

	return null;
};

const Settings = ( props ) => {
	const { settings, form, wooCommerceSettings, settingsActions, schema, layout } = props;
	const { updateSettingsField, updateSettingsObjectSubField } = settingsActions;
	const renderFormErrors = () => {
		if ( form.error ) {
			return (
				<Notice status="is-error" text={ form.error } showDismiss={ false } />
			);
		}
		if ( form.success ) {
			return (
				<Notice
					className="wcc-form-success"
					status="is-success"
					text={ __( 'Your changes have been saved.' ) }
					showDismiss={ false }
					onDismissClick={ () => props.formActions.setField( 'success', null ) }
					duration={ 2250 }
				/>
			);
		}
	};
	return (
		<div>
			<SettingsGroup
				group={ layout[0] }
				schema={ schema }
				settings={ settings }
				updateValue={ updateSettingsField }
				updateSubValue={ () => {} }
				updateSubSubValue={ updateSettingsObjectSubField }
			/>
			<CompactCard>
				<FormSectionHeading>{ __( 'Rates' ) }</FormSectionHeading>
				<FormFieldset>
					<FormLegend>{ __( 'Services' ) }</FormLegend>
					<ShippingServiceGroups
						services={ schema.definitions.services }
						settings={ settings.services }
						currencySymbol={ wooCommerceSettings.currency_symbol }
						updateValue={ ( id, key, val ) => updateSettingsObjectSubField( 'services', id, key, val ) }
						settingsKey="services"
					/>
				</FormFieldset>
				<RadioButtons
					layout={ findLayoutInfo( layout, 'rate_filter' ) }
					schema={ schema.properties.rate_filter }
					value={ settings.rate_filter }
					setValue={ ( value ) => updateSettingsField( 'rate_filter', value ) }
				/>
			</CompactCard>
			<CompactCard className="save-button-bar">
				<FormButtonsBar>
					{ renderFormErrors() }
					<FormButton onClick={ ( event ) => handleSaveForm( event, props ) }>
						{ form.isSaving ? __( 'Saving...' ) : __( 'Save changes' ) }
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
