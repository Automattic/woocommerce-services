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
import { getLabelSettingsFormMeta, getLabelSettingsFormData } from 'woocommerce/woocommerce-services/state/label-settings/selectors';

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
		const { noticeActions, translate, orderId, orderHref, paymentMethodSelected } = this.props;
		const options =
			orderHref && paymentMethodSelected
				? { button: translate( 'Back to Order #%(orderId)s', { args: { orderId } } ), href: orderHref }
				: { duration: 5000 };

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
	const formMeta = getLabelSettingsFormMeta( state );
	const formData = getLabelSettingsFormData( state );
	return {
		siteId: getSelectedSiteId( state ),
		isSaving: formMeta.isSaving,
		buttonDisabled: formMeta.pristine,
		paymentMethodSelected: Boolean( formData && formData.selected_payment_method_id ),
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
