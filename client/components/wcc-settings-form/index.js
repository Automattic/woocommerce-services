import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import isString from 'lodash/isString';
import { translate as __ } from 'lib/mixins/i18n';
import validator from 'is-my-json-valid';

const WCCSettingsForm = ( {
	storeOptions,
	schema,
	layout,
	settings,
	form,
	saveFormData,
	formActions,
	noticeActions,
} ) => {
	const setIsSaving = ( value ) => formActions.setField( 'isSaving', value );
	const setSuccess = ( value ) => {
		formActions.setField( 'success', value );
		if ( true === value ) {
			noticeActions.successNotice( __( 'Your changes have been saved.' ), {
				duration: 2250,
			} );
		}
	};
	const setError = ( value ) => {
		formActions.setField( 'error', value );
		if ( isString( value ) ) {
			noticeActions.errorNotice( value, {
				duration: 7000,
			} );
		}
	}
	const saveForm = () => saveFormData( setIsSaving, setSuccess, setError, settings );
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			{ layout.map( ( group, idx ) => (
				<WCCSettingsGroup
					key={ idx }
					{ ...{
						group,
						schema,
						storeOptions,
						saveForm,
						form,
					} }
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

function mapStateToProps( state, props ) {
	const validate = validator( props.schema );
	validate( state.settings );

	return {
		settings: state.settings,
		form: state.form,
		errors: validate.errors,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( WCCSettingsForm );
