/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import classNames from 'classnames';
import { find } from 'lodash';

/**
 * Internal dependencies
 */
// from calypso
import Button from 'components/button';
import LabelPurchaseDialog from 'woocommerce/woocommerce-services/views/shipping-label/label-purchase-modal';
import QueryLabels from 'woocommerce/woocommerce-services/components/query-labels';
import {
	openPrintingFlow,
	setEmailDetailsOption,
	setFulfillOrderOption,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import GlobalNotices from 'components/global-notices';
import Notice from 'components/notice';
import notices from 'notices';
import { getSelectedSiteId } from 'state/ui/selectors';
import {
	areLabelsFullyLoaded,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	areLabelsEnabled,
	getSelectedPaymentMethodId,
	getLabelSettingsFormMeta,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';
import ActivityLog from 'woocommerce/app/order/order-activity-log/events';

const ShippingLabelViewWrapper = ( props ) => {
	const {
		translate,
		siteId,
		orderId,
		loaded,
		labelsEnabled,
		selectedPaymentMethod,
		paymentMethods,
	} = props;

	const renderPaymentInfo = () => {
		if ( ! loaded ) {
			return null;
		}

		if ( selectedPaymentMethod ) {
			return (
				<Notice isCompact showDismiss={ false } className="shipping-label__payment inline">
					<p>
						{ translate( 'Labels will be purchased using card ending: {{strong}}%(cardDigits)s.{{/strong}}', {
							components: { strong: <strong /> },
							args: { cardDigits: selectedPaymentMethod.card_digits },
						} ) }
					</p>
					<p><a href="admin.php?page=wc-settings&tab=shipping&section=label-settings">{ translate( 'Manage cards' ) }</a></p>
				</Notice>
			);
		}

		if ( paymentMethods.length ) {
			return (
				<Notice isCompact={ true } showDismiss={ false } className="shipping-label__payment inline">
					<p>{ translate( 'To purchase shipping labels, you will first need to select a credit card.' ) }</p>
					<p>
						<a href="admin.php?page=wc-settings&tab=shipping&section=label-settings">{ translate( 'Select a credit card' ) }</a>
					</p>
				</Notice>
			);
		}

		return (
			<Notice isCompact showDismiss={ false } className="shipping-label__payment inline">
				<p>{ translate( 'To purchase shipping labels, you will first need to add a credit card.' ) }</p>
				<p><a href="admin.php?page=wc-settings&tab=shipping&section=label-settings">{ translate( 'Add a credit card' ) }</a></p>
			</Notice>
		);
	};

	const handleButtonClick = () => {
		// We don't support automatically emailing the customer
		// or marking the order as fulfilled
		// once the order is fulfilled
		props.setEmailDetailsOption( orderId, siteId, false );
		props.setFulfillOrderOption( orderId, siteId, false );

		props.openPrintingFlow( orderId, siteId );
	};

	const renderLabelButton = () => {
		const className = classNames( 'shipping-label__new-label-button', {
			'is-placeholder': ! loaded,
		} );

		return (
			<Button
				className={ className }
				onClick={ handleButtonClick } >
				{ translate( 'Create new label' ) }
			</Button>
		);
	};

	const shouldRenderButton = ! loaded || labelsEnabled && selectedPaymentMethod;

	return (
		<div className="shipping-label__container">
			<div className="shipping-label__new" >
				<GlobalNotices notices={ notices.list } />
				<QueryLabels orderId={ orderId } siteId={ siteId } />
				<LabelPurchaseDialog orderId={ orderId } siteId={ siteId } />
				{ renderPaymentInfo() }
				{ shouldRenderButton && renderLabelButton() }
			</div>
			<div className="shipping-label__dummy-class order-activity-log">
				<ActivityLog orderId={ orderId } siteId={ siteId } />
			</div>
		</div>
	);
};

ShippingLabelViewWrapper.propTypes = {
	orderId: PropTypes.number.isRequired,
};

export default connect(
	( state, { orderId } ) => {
		const siteId = getSelectedSiteId( state );
		const loaded = areLabelsFullyLoaded( state, orderId, siteId );
		const paymentMethodId = getSelectedPaymentMethodId( state, siteId );
		const formMeta = getLabelSettingsFormMeta( state, siteId ) || {};
		const paymentMethods = formMeta.payment_methods;
		const selectedPaymentMethod = loaded ? find( paymentMethods, { payment_method_id: paymentMethodId } ) : null;

		return {
			siteId,
			loaded,
			labelsEnabled: areLabelsEnabled( state, siteId ),
			selectedPaymentMethod,
			paymentMethods,
		};
	},
	( dispatch ) => ( {
		...bindActionCreators( {
			openPrintingFlow,
			setEmailDetailsOption,
			setFulfillOrderOption,
		}, dispatch ),
	} ),
)( localize( ShippingLabelViewWrapper ) );
