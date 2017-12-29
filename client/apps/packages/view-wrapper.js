/**
 * External dependencies
 */
import React, { Component } from 'react';
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
import { ProtectFormGuard } from 'lib/protect-form';

class PackagesWrapper extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			pristine: true,
		};
	}

	onChange = () => {
		this.setState( { pristine: false } );
	}

	onSaveSuccess = () => {
		const { noticeActions, translate } = this.props;
		noticeActions.successNotice( translate( 'Your packages have been saved.' ), { duration: 5000 } );
		this.setState( { pristine: true } );
	}

	onSaveFailure = () => {
		const { noticeActions, translate } = this.props;
		noticeActions.errorNotice( translate( 'Unable to save your packages. Please try again.' ) );
	}

	onSaveChanges = () => {
		this.props.submit( this.props.siteId, this.onSaveSuccess, this.onSaveFailure );
	}

	render() {
		const {
			translate,
			buttonDisabled,
			isSaving,
		} = this.props;

		return (
			<div>
				<GlobalNotices notices={ notices.list } />
				<Packages onChange={ this.onChange } />
				<Button
					primary
					onClick={ this.onSaveChanges }
					busy={ isSaving }
					disabled={ buttonDisabled }
				>
					{ translate( 'Save changes' ) }
				</Button>
				<ProtectFormGuard isChanged={ ! this.state.pristine } />
			</div>
		);
	}
}

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
