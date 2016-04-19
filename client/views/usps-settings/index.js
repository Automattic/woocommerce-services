import React, { PropTypes } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as SettingsActions from 'state/settings/actions';
import * as FormActions from 'state/form/actions';
import SettingsGroup from './render-group';
import UspsHeader from './usps-header';

const Settings = ( { settings, form, wooCommerceSettings, settingsActions, formActions, saveForm, schema, layout } ) => {
	const { updateSettingsField, updateSettingsObjectSubField } = settingsActions;
	const setIsSaving = ( value ) => formActions.setField( 'isSaving', value );
	const setError = ( value ) => formActions.setField( 'error', value );
	return (
		<div>
			<UspsHeader />
			{
				layout.map( ( group, idx ) => (
					<SettingsGroup
						key={ idx }
						group={ group }
						form={ form }
						schema={ schema }
						settings={ settings }
						wooCommerceSettings={ wooCommerceSettings }
						updateValue={ updateSettingsField }
						updateSubSubValue={ updateSettingsObjectSubField }
						saveForm={ () => saveForm( setIsSaving, setError, settings ) }
					/>
				) )
			}
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
	saveForm: PropTypes.func.isRequired,
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
