/**
 * External dependencies
 */
import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
// from calypso
import Button from 'components/button';
import Packages from 'woocommerce/woocommerce-services/views/packages';
import { submit } from 'woocommerce/woocommerce-services/state/packages/actions';
import * as NoticeActions from 'state/notices/actions';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import { getSelectedSiteId } from 'state/ui/selectors';
import { getPackagesForm } from 'woocommerce/woocommerce-services/state/packages/selectors';

const PackagesWrapper = ( props ) => {
	const {
		translate,
		noticeActions,
		buttonDisabled,
		isSaving,
	} = props;

	const onSaveSuccess = () => noticeActions.successNotice( translate( 'Your packages have been saved.' ), { duration: 5000 } );
	const onSaveFailure = () => noticeActions.errorNotice( translate( 'Unable to save your packages. Please try again.' ) );
	const onSaveChanges = () => props.submit( props.siteId, onSaveSuccess, onSaveFailure );

	return (
		<div>
			<GlobalNotices notices={ notices.list } />
			<Packages />
			<Button
				primary
				onClick={ onSaveChanges }
				busy={ isSaving }
				disabled={ buttonDisabled }
			>
				{ translate( 'Save' ) }
			</Button>
		</div>
	);
};

export default connect(
	( state ) => {
		const siteId = getSelectedSiteId( state );
		const form = getPackagesForm( state, siteId );

		return {
			siteId,
			isSaving: form.isSaving,
			buttonDisabled: form.pristine,
		};
	},
	( dispatch ) => ( {
		...bindActionCreators( { submit }, dispatch ),
		noticeActions: bindActionCreators( NoticeActions, dispatch ),
	} ),
)( localize( PackagesWrapper ) );
