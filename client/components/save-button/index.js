import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormButton from 'components/forms/form-button';
import FormButtonsBar from 'components/forms/form-buttons-bar';
import Notice from 'components/notice';

const handleSaveForm = ( event, saveForm ) => {
	event.preventDefault();
	saveForm();
};

const renderFormErrors = ( form ) => {
	if ( form.error ) {
		return (
			<Notice status="is-error" text={ JSON.stringify( form.error ) } showDismiss={ false } />
		);
	}
};

const SaveForm = ( { form, saveForm } ) => {
	return (
		<CompactCard>
			<FormButtonsBar>
				{ renderFormErrors( form ) }
				<FormButton onClick={ ( event ) => handleSaveForm( event, saveForm ) }>
					{ form.isSaving ? 'Saving...' : 'Save changes' }
				</FormButton>
			</FormButtonsBar>
		</CompactCard>
	);
}

SaveForm.propTypes = {
	form: PropTypes.object.isRequired,
	saveForm: PropTypes.func.isRequired,
};

export default SaveForm;
