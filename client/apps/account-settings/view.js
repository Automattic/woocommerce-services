/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import ActionButtonsCard from 'components/action-buttons-card';
import CompactCard from 'components/card/compact';
import ErrorMessage from 'components/error-message';
import GlobalNotices from 'components/global-notices';
import LabelSettings from './components/label-settings';
import notices from 'notices';
import * as settingsActions from './state/actions';
import * as NoticeActions from 'state/notices/actions';

const AccountSettingsRootView = ( { formData, formMeta, actions, noticeActions, storeOptions } ) => {
	const onSaveSuccess = () => {
		actions.setFormMetaProperty( 'pristine', true );
		noticeActions.successNotice( __( 'Your shipping label settings have been saved.' ), { duration: 5000 } );
	};
	const onSaveFailure = () => noticeActions.errorNotice( __( 'Unable to save your shipping label settings. Please try again.' ) );
	const onSaveChanges = () => actions.submit( onSaveSuccess, onSaveFailure );

	const buttons = [
		{
			label: __( 'Save changes' ),
			onClick: onSaveChanges,
			isPrimary: true,
			isDisabled: formMeta.pristine || formMeta.isSaving,
		},
	];

	const renderContent = () => {
		if ( ! formData && ! formMeta.isFetching ) {
			return (
				<ErrorMessage>
					{ __( 'Unable to get your settings. Please refresh the page to try again.' ) }
				</ErrorMessage>
			);
		}
		return (
			<LabelSettings
				isLoading={ formMeta.isFetching }
				paymentMethods={ formMeta.payment_methods || [] }
				setFormDataValue={ actions.setFormDataValue }
				selectedPaymentMethod={ ( formData || {} ).selected_payment_method_id }
				paperSize={ ( formData || {} ).paper_size }
				storeOptions={ storeOptions }
			/>
		);
	};

	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			<CompactCard>
				{ renderContent() }
			</CompactCard>
			<ActionButtonsCard buttons={ buttons } />
		</div>
	);
};

AccountSettingsRootView.propTypes = {
	submit: PropTypes.func,
};

function mapStateToProps( state ) {
	return {
		storeOptions: state.form.storeOptions,
		formData: state.form.data,
		formMeta: state.form.meta,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		actions: bindActionCreators( settingsActions, dispatch ),
		noticeActions: bindActionCreators( NoticeActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( AccountSettingsRootView );
