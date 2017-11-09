/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
// from calypso
import LabelSettings from 'woocommerce/woocommerce-services/views/label-settings';
import ActionButtonsCard from 'components/action-buttons-card';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import * as NoticeActions from 'state/notices/actions';
import { submit } from 'woocommerce/woocommerce-services/state/label-settings/actions';
import { getSelectedSiteId } from 'state/ui/selectors';
import { getLabelSettingsFormMeta } from 'woocommerce/woocommerce-services/state/label-settings/selectors';

const LabelSettingsWrapper = ( props ) => {
	const {
		translate,
		noticeActions,
		formMeta,
	} = props;

	const onSaveSuccess = () => {
		noticeActions.successNotice( translate( 'Your shipping label settings have been saved.' ), { duration: 5000 } );
	};
	const onSaveFailure = () => noticeActions.errorNotice( translate( 'Unable to save your shipping label settings. Please try again.' ) );
	const onSaveChanges = () => props.submit( props.siteId, onSaveSuccess, onSaveFailure );

	const buttons = [
		{
			label: translate( 'Save changes' ),
			onClick: onSaveChanges,
			isPrimary: true,
			isDisabled: formMeta.pristine || formMeta.isSaving,
		},
	];

	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			<LabelSettings />
			<ActionButtonsCard buttons={ buttons } />
		</div>
	);
};

function mapStateToProps( state ) {
	return {
		siteId: getSelectedSiteId( state ),
		formMeta: getLabelSettingsFormMeta( state ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		...bindActionCreators( { submit }, dispatch ),
		noticeActions: bindActionCreators( NoticeActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( LabelSettingsWrapper ) );
