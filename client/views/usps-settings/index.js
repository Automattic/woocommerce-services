import React, { PropTypes } from 'react';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import CompactCard from 'components/card/compact';
import ShippingServiceGroups from 'components/shipping-service-groups';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/settings/actions';
import * as FormActions from 'state/form/actions';
import SettingsGroup from './render-group';
import { translate as __ } from 'lib/mixins/i18n';
import RadioButtons from 'components/radio-buttons';
import SaveForm from 'components/save-form';

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
	const { settings, form, wooCommerceSettings, settingsActions, formActions, schema, layout, saveFormData } = props;
	const { updateSettingsField, updateSettingsObjectSubField } = settingsActions;
	const { setField } = formActions;
	const setIsSaving = ( value ) => setField( 'isSaving', value );
	const setSuccess = ( value ) => setField( 'success', value );
	const setError = ( value ) => setField( 'error', value );
	const dismissSuccess = () => setField( 'success', false );
	const saveForm = () => saveFormData( setIsSaving, setSuccess, setError, settings );

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
				<SaveForm
					saveForm={ saveForm }
					isSaving={ form.isSaving }
					success={ form.success }
					error={ form.error }
					dismissSuccess={ dismissSuccess }
				/>
			</CompactCard>
		</div>
	);
};

Settings.propTypes = {
	settingsActions: PropTypes.object.isRequired,
	formActions: PropTypes.object.isRequired,
	wooCommerceSettings: PropTypes.object.isRequired,
	settings: PropTypes.object.isRequired,
	form: PropTypes.object.isRequired,
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
