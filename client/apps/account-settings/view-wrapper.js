/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
// from calypso
import LabelSettings from 'woocommerce/woocommerce-services/views/label-settings';
import Button from 'components/button';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import { ProtectFormGuard } from 'lib/protect-form';
import * as NoticeActions from 'state/notices/actions';
import { submit } from 'woocommerce/woocommerce-services/state/label-settings/actions';
import { getSelectedSiteId } from 'state/ui/selectors';
import { getLabelSettingsFormMeta } from 'woocommerce/woocommerce-services/state/label-settings/selectors';

class LabelSettingsWrapper extends Component {
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
		const { noticeActions, translate, orderHref } = this.props;
		const options = orderHref ? { button: translate( 'Back to order' ), href: orderHref } : { duration: 5000 };
		noticeActions.successNotice( translate( 'Your shipping label settings have been saved.' ), options );
		this.setState( { pristine: true } );
	}

	onSaveFailure = () => {
		const { noticeActions, translate } = this.props;
		noticeActions.errorNotice( translate( 'Unable to save your shipping label settings. Please try again.' ) );
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
				<GlobalNotices id="notices" notices={ notices.list } />
				<LabelSettings onChange={ this.onChange } />
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

function mapStateToProps( state ) {
	const form = getLabelSettingsFormMeta( state );
	return {
		siteId: getSelectedSiteId( state ),
		isSaving: form.isSaving,
		buttonDisabled: form.pristine,
		orderHref: form.order_href,
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
