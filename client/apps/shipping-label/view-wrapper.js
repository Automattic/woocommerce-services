/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import classNames from 'classnames';
import { find, get } from 'lodash';

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
	getForm,
	areLabelsFullyLoaded,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	areLabelsEnabled,
	getSelectedPaymentMethodId,
	getLabelSettingsFormMeta,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';
import ActivityLog from 'woocommerce/app/order/order-activity-log/events';
import {
	getActivityLogEvents,
} from 'woocommerce/state/sites/orders/activity-log/selectors';
import { fetchOrder } from 'woocommerce/state/sites/orders/actions';

const ACCEPTED_USPS_ORIGIN_COUNTRY_CODES = [ 'US' ];

class ShippingLabelViewWrapper extends Component {
	static propTypes = {
		orderId: PropTypes.number.isRequired,
	};

	componentDidMount() {
		const { siteId, orderId } = this.props;

		if ( siteId && orderId ) {
			this.props.fetchOrder( siteId, orderId );
		}
	}

	renderPaymentInfo = () => {
		const {
			translate,
			orderId,
			loaded,
			selectedPaymentMethod,
			paymentMethods,
		} = this.props;

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
					<p>
						<a href={ `admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings&from_order=${ orderId }` }>
							{ translate( 'Manage cards' ) }
						</a>
					</p>
				</Notice>
			);
		}

		if ( paymentMethods.length ) {
			return (
				<Notice isCompact={ true } showDismiss={ false } className="shipping-label__payment inline">
					<p>{ translate( 'To purchase shipping labels, you will first need to select a credit card.' ) }</p>
					<p>
						<a href={ `admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings&from_order=${ orderId }` }>
							{ translate( 'Select a credit card' ) }
						</a>
					</p>
				</Notice>
			);
		}

		return (
			<Notice isCompact showDismiss={ false } className="shipping-label__payment inline">
				<p>{ translate( 'To purchase shipping labels, you will first need to add a credit card.' ) }</p>
				<p>
					<a href="admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings">
						{ translate( 'Add a credit card' ) }
					</a>
				</p>
			</Notice>
		);
	};

	renderLabelButton = () => {
		const {
			loaded,
			translate,
		} = this.props;

		const className = classNames( 'shipping-label__new-label-button', {
			'is-placeholder': ! loaded,
		} );

		return (
			<Button
				className={ className }
				onClick={ this.handleButtonClick } >
				{ translate( 'Create new label' ) }
			</Button>
		);
	};

	renderActivityLog = () => {
		const {
			siteId,
			orderId,
			events,
		} = this.props;

		if ( 0 === events.length ) {
			return null;
		}

		return (
			<div className="shipping-label__dummy-class order-activity-log">
				<ActivityLog orderId={ orderId } siteId={ siteId } />
			</div>
		);
	};

	renderOriginCountryWarning = () => {
		return (
			<div>
				{ this.props.translate( 'We only support shipping from the US. Looks like this order is set in ${country}. You can install ShipStation to print a shipping label using your local service.' ) }
			</div>
		);
	};

	handleButtonClick = () => {
		const {
			orderId,
			siteId,
		} = this.props;

		// We don't support automatically emailing the customer
		// or marking the order as fulfilled
		// once the order is fulfilled
		this.props.setEmailDetailsOption( orderId, siteId, false );
		this.props.setFulfillOrderOption( orderId, siteId, false );

		this.props.openPrintingFlow( orderId, siteId );
	};

	render() {
		const {
			siteId,
			orderId,
			loaded,
			labelsEnabled,
			selectedPaymentMethod,
			originCountry,
			isSupportedOriginCountry,
		} = this.props;

		// while loading, labelsEnabled is false, but we still want to show the button placeholder
		// TODO: pass shouldRenderButton into renderLabelButton so that this logic is a bit simpler
		const shouldRenderButton = ! loaded || labelsEnabled
			&& selectedPaymentMethod
			&& isSupportedOriginCountry;

		const shouldRenderPaymentInfo = isSupportedOriginCountry;

		return (
			<div className="shipping-label__container">
				<div className="shipping-label__new" >
					<GlobalNotices notices={ notices.list } />
					<QueryLabels orderId={ orderId } siteId={ siteId } />
					<LabelPurchaseDialog orderId={ orderId } siteId={ siteId } />
					{ originCountry && ! isSupportedOriginCountry && this.renderOriginCountryWarning() }
					{ shouldRenderPaymentInfo && this.renderPaymentInfo() }
					{ shouldRenderButton && this.renderLabelButton() }
				</div>
				{ this.renderActivityLog() }
			</div>
		);
	}
}

export default connect(
	( state, { orderId } ) => {
		const siteId = getSelectedSiteId( state );
		const loaded = areLabelsFullyLoaded( state, orderId, siteId );
		const paymentMethodId = getSelectedPaymentMethodId( state, siteId );
		const formMeta = getLabelSettingsFormMeta( state, siteId ) || {};
		const paymentMethods = formMeta.payment_methods;
		const selectedPaymentMethod = loaded ? find( paymentMethods, { payment_method_id: paymentMethodId } ) : null;
		const events = getActivityLogEvents( state, orderId );

		const formData = getForm( state, orderId, siteId );
		const originCountry = get( formData, 'origin.values.country' );
		const isSupportedOriginCountry = ACCEPTED_USPS_ORIGIN_COUNTRY_CODES.includes( originCountry );

		return {
			originCountry,
			isSupportedOriginCountry,
			siteId,
			loaded,
			labelsEnabled: areLabelsEnabled( state, siteId ),
			selectedPaymentMethod,
			paymentMethods,
			events,
		};
	},
	( dispatch ) => ( {
		...bindActionCreators( {
			openPrintingFlow,
			setEmailDetailsOption,
			setFulfillOrderOption,
			fetchOrder,
		}, dispatch ),
	} ),
)( localize( ShippingLabelViewWrapper ) );
