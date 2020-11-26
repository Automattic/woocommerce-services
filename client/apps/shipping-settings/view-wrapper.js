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
import Button from 'components/button';
import GlobalNotices from 'components/global-notices';
import LabelSettings from '../../extensions/woocommerce/woocommerce-services/views/label-settings';
import notices from 'notices';
import Packages from '../../extensions/woocommerce/woocommerce-services/views/packages';
import CarrierAccounts from '../../extensions/woocommerce/woocommerce-services/views/carrier-accounts';
import CarrierAccountSettings from '../../extensions/woocommerce/woocommerce-services/views/carrier-accounts/settings';
import DynamicCarrierAccountSettings from '../../extensions/woocommerce/woocommerce-services/views/carrier-accounts/dynamic-settings';
import { ProtectFormGuard } from 'lib/protect-form';
import { successNotice, errorNotice } from 'state/notices/actions';
import { createWcsShippingSaveActionList } from '../../extensions/woocommerce/woocommerce-services/state/actions';
import { getSelectedSiteId } from 'state/ui/selectors';
import {
	getLabelSettingsFormMeta,
	getSelectedPaymentMethodId,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';
import { getPackagesForm } from '../../extensions/woocommerce/woocommerce-services/state/packages/selectors';

class LabelSettingsWrapper extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			pristine: true,
		};
	}

	onChange = () => {
		this.setState( { pristine: false } );
	};

	onSaveSuccess = () => {
		const { translate, orderId, orderHref, paymentMethodSelected } = this.props;
		const options =
			orderHref && paymentMethodSelected
				? { button: translate( 'Return to Order #%(orderId)s', { args: { orderId } } ), href: orderHref }
				: { duration: 5000 };

		this.setState( { pristine: true } );
		return this.props.successNotice( translate( 'Your shipping settings have been saved.' ), options );
	};

	onSaveFailure = () => {
		const { translate } = this.props;
		return this.props.errorNotice( translate( 'Unable to save your shipping settings. Please try again.' ) );
	};

	onSaveChanges = () => {
		this.props.createWcsShippingSaveActionList( this.onSaveSuccess, this.onSaveFailure );
	};

	render() {
		const { carrier, carriers, isSaving, siteId, translate } = this.props;

		if ( ! carrier ) {
			return (
				<div>
					<GlobalNotices id="notices" notices={ notices.list } />
					<LabelSettings onChange={ this.onChange } />
					<Packages onChange={ this.onChange } />
					<CarrierAccounts siteId={ siteId } carriers={ carriers } onChange={ this.onChange } />
					<Button primary onClick={ this.onSaveChanges } busy={ isSaving } disabled={ isSaving }>
						{ translate( 'Save changes' ) }
					</Button>
					<ProtectFormGuard isChanged={ ! this.state.pristine } />
				</div>
			);
		}

		if ( carrier.toLowerCase() === 'ups' ) {
			return (
				<div>
					<GlobalNotices id="notices" notices={ notices.list } />
					<CarrierAccountSettings carrier={ carrier } />
					<ProtectFormGuard isChanged={ ! this.state.pristine } />
				</div>
			);
		}

		// Dynamically create registration form
		return (
			<div>
				<GlobalNotices id="notices" notices={ notices.list } />
				<DynamicCarrierAccountSettings carrier={ carrier } />
				<ProtectFormGuard isChanged={ ! this.state.pristine } />
			</div>
		);
	}
}

export default connect(
	( state ) => {
		const labelsFormMeta = getLabelSettingsFormMeta( state );
		const packagesForm = getPackagesForm( state );

		return {
			siteId: getSelectedSiteId( state ),
			isSaving: labelsFormMeta.isSaving || packagesForm.isSaving,
			paymentMethodSelected: Boolean( getSelectedPaymentMethodId( state ) ),
		};
	},
	( dispatch ) =>
		bindActionCreators(
			{
				createWcsShippingSaveActionList,
				errorNotice,
				successNotice,
			},
			dispatch
		)
)( localize( LabelSettingsWrapper ) );
