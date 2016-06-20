import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import isString from 'lodash/isString';
import isArray from 'lodash/isArray';
import { translate as __ } from 'lib/mixins/i18n';
import * as SettingsActions from 'state/settings/actions';
import * as PackagesActions from 'state/form/packages/actions';
import { getFormErrors } from 'state/selectors';

const WCCSettingsForm = ( props ) => {
	const {
		layout,
		saveFormData,
		formActions,
		noticeActions,
	} = props;

	const setIsSaving = ( value ) => formActions.setFormProperty( 'isSaving', value );
	const setSuccess = ( value ) => {
		formActions.setFormProperty( 'success', value );
		if ( true === value ) {
			noticeActions.successNotice( __( 'Your changes have been saved.' ), {
				duration: 2250,
			} );
		}
	};
	const setError = ( value ) => {
		formActions.setFormProperty( 'errors', value );

		if ( isString( value ) ) {
			noticeActions.errorNotice( value, {
				duration: 7000,
			} );
		}

		if ( isArray( value ) ) {
			noticeActions.errorNotice( __( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ), {
				duration: 7000,
			} );
		}
	};

	const filterStoreOnSave = ( store ) => {
		return store.getState().settings;
	};

	const saveForm = () => saveFormData( setIsSaving, setSuccess, setError, filterStoreOnSave );
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			{ layout.map( ( group, idx ) => (
				<WCCSettingsGroup
					{ ...props }
					{ ...{ group, saveForm } }
					key={ idx }
				/>
			) ) }
		</div>
	);
};

WCCSettingsForm.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

function mapStateToProps( state ) {
	return {
		settings: state.settings,
		form: state.form,
		errors: getFormErrors( state ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
		packagesActions: bindActionCreators( PackagesActions, dispatch ),
		settingsActions: bindActionCreators( SettingsActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( WCCSettingsForm );
