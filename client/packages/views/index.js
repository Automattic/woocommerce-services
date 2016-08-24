import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import ActionButtons from 'components/action-buttons';
import FormFieldset from 'components/forms/form-fieldset';
import FormButton from 'components/forms/form-button';
import PackagesList from './packages-list';
import AddPackageDialog from './add-package';
import { translate as __ } from 'lib/mixins/i18n';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as PackagesActions from '../state/actions';
import * as NoticeActions from 'state/notices/actions';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';

const Packages = ( props ) => {
	const onSaveSuccess = () => props.noticeActions.successNotice( __( 'Your packages have been saved.' ), { duration: 2250 } );
	const onSaveFailure = () => props.noticeActions.errorNotice( __( 'Unable to save your packages. Please try again.' ), { duration: 7000 } );
	const onSaveChanges = () => props.saveForm( onSaveSuccess, onSaveFailure );

	const buttons = [
		{
			label: __( 'Save changes' ),
			onClick: onSaveChanges,
			isPrimary: true,
			isDisabled: props.form.isSaving,
		},
	];

	return (
		<div className="wcc-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			<CompactCard className="settings-group-card">
				<FormSectionHeading className="settings-group-header">{ __( 'Packaging' ) }</FormSectionHeading>
				<div className="settings-group-content">
					<PackagesList { ...props } />
					<AddPackageDialog { ...props } />
					<FormFieldset className="add-package-button-field">
						<FormButton
							type="button"
							isPrimary={ false }
							compact
							onClick={ props.addPackage } >
							{ __( 'Add a package' ) }
						</FormButton>
					</FormFieldset>
				</div>
			</CompactCard>
			<CompactCard className="save-button-bar">
				<ActionButtons buttons={ buttons } />
			</CompactCard>
		</div>
	);
};

Packages.propTypes = {
	addPackage: PropTypes.func.isRequired,
	removePackage: PropTypes.func.isRequired,
	editPackage: PropTypes.func.isRequired,
	dismissModal: PropTypes.func.isRequired,
	setSelectedPreset: PropTypes.func.isRequired,
	savePackage: PropTypes.func.isRequired,
	updatePackagesField: PropTypes.func.isRequired,
	toggleOuterDimensions: PropTypes.func.isRequired,
	setModalErrors: PropTypes.func.isRequired,
	showModal: PropTypes.bool,
	form: PropTypes.object,
	noticeActions: PropTypes.object,
};

export default connect(
	( state ) => state,
	( dispatch ) => (
		{
			...bindActionCreators( PackagesActions, dispatch ),
			noticeActions: bindActionCreators( NoticeActions, dispatch ),
		} )
)( Packages );
