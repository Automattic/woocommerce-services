import React, { PropTypes } from 'react';
import Notice from 'components/notice';
import FormButton from 'components/forms/form-button';
import FormButtonsBar from 'components/forms/form-buttons-bar';
import { translate as __ } from 'lib/mixins/i18n';

const renderFormNotices = ( success, error, dismissSuccess ) => {
	if ( error ) {
		return (
			<Notice status="is-error" text={ error } showDismiss={ false } />
		);
	}
	if ( success ) {
		return (
			<Notice
				className="wcc-form-success"
				status="is-success"
				text={ __( 'Your changes have been saved.' ) }
				showDismiss={ false }
				onDismissClick={ dismissSuccess }
				duration={ 2250 }
			/>
		);
	}
};

const SaveForm = ( { saveForm, isSaving, success, error, dismissSuccess } ) => {
	return (
		<FormButtonsBar>
			{ renderFormNotices( success, error, dismissSuccess ) }
			<FormButton type="button" onClick={ saveForm }>
				{ isSaving ? __( 'Saving...' ) : __( 'Save changes' ) }
			</FormButton>
		</FormButtonsBar>
	);
};

SaveForm.propTypes = {
	saveForm: PropTypes.func.isRequired,
	isSaving: PropTypes.bool.isRequired,
	success: PropTypes.bool,
	error: PropTypes.string,
	dismissSuccess: PropTypes.func.isRequired,
};

export default SaveForm;
