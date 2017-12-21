/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
// from calypso
import LabelSettings from 'woocommerce/woocommerce-services/views/label-settings';
import Button from 'components/button';
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
		buttonDisabled,
		isSaving,
	} = props;

	const onSaveSuccess = () => {
		noticeActions.successNotice( translate( 'Your shipping label settings have been saved.' ), { duration: 5000 } );
	};
	const onSaveFailure = () => noticeActions.errorNotice( translate( 'Unable to save your shipping label settings. Please try again.' ) );
	const onSaveChanges = () => props.submit( props.siteId, onSaveSuccess, onSaveFailure );

	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			<LabelSettings onChange={ noop } />
			<Button
				primary
				onClick={ onSaveChanges }
				busy={ isSaving }
				disabled={ buttonDisabled }
			>
				{ translate( 'Save changes' ) }
			</Button>
		</div>
	);
};

function mapStateToProps( state ) {
	const form = getLabelSettingsFormMeta( state );
	return {
		siteId: getSelectedSiteId( state ),
		isSaving: form.isSaving,
		buttonDisabled: form.pristine,
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
