/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import classNames from 'classnames';
import Gridicon from 'gridicons';
import { sumBy, differenceBy, filter } from 'lodash';

/**
 * Internal dependencies
 */
// from calypso
import Button from 'components/button';
import LabelPurchaseModal from '../../extensions/woocommerce/woocommerce-services/views/shipping-label/label-purchase-modal';
import TrackingModal from '../../extensions/woocommerce/woocommerce-services/views/shipping-label/tracking-modal';
import QueryLabels from '../../extensions/woocommerce/woocommerce-services/components/query-labels';
import {
	openPrintingFlow,
	openTrackingFlow,
	setEmailDetailsOption,
	setFulfillOrderOption,
} from '../../extensions/woocommerce/woocommerce-services/state/shipping-label/actions';
import { getSelectedSiteId } from 'state/ui/selectors';
import {
	areLabelsFullyLoaded,
} from '../../extensions/woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	areLabelsEnabled,
} from '../../extensions/woocommerce/woocommerce-services/state/label-settings/selectors';
import {
	getActivityLogEvents,
} from '../../extensions/woocommerce/state/sites/orders/activity-log/selectors';
import { fetchOrder } from '../../extensions/woocommerce/state/sites/orders/actions';
import {
	isOrderLoaded,
	isOrderLoading
 } from '../../extensions/woocommerce/state/sites/orders/selectors';

class ShippingLabelViewWrapper extends Component {
	static propTypes = {
		orderId: PropTypes.number.isRequired,
	};

	componentDidMount() {
		const { siteId, orderId, orderLoading, orderLoaded } = this.props;

		if ( siteId && orderId && ! orderLoading && ! orderLoaded) {
			this.props.fetchOrder( siteId, orderId );
		}
	}

	renderLabelButton = () => {
		const {
			loaded,
			translate,
			events,
			items,
		} = this.props;

		const className = classNames( 'shipping-label__new-label-button', {
			'is-placeholder': ! loaded,
		} );

		if ( ! loaded ) {
			return (
				<Button
					className={ 'shipping-label__button-loading' }
					primary
					busy={ true }
					disabled={ true }
				>

				</Button>
			);
		}

		// eslint-disable-next-line no-undef
		if ( wcConnectData.wcs_server_connection ) {

			const labels = filter( events, { type: 'LABEL_PURCHASED' } );
			const refunds = filter( events, { type: 'LABEL_REFUND_REQUESTED' } );
			const activeLabels = differenceBy( labels, refunds, "labelIndex" );
			const productsPackaged = sumBy( activeLabels, (l) => l.productNames.length );

			// If there are no purchased labels, just show Create labels button
			if ( ! activeLabels.length ) {
				return (
					<Button
						className={ className }
						primary
						busy= { ! loaded }
						disabled= { ! loaded }
						onClick={ this.handleCreateLabelButtonClick }
					>
						{ translate( 'Create shipping label' ) }
					</Button>
				);
			}

			// If not all items are packaged (but some are, per condition above), show both buttons
			if ( productsPackaged !== items ) {
				return (
					<div className="shipping-label__multiple-buttons-container">
						<Button
							className={ className }
							primary
							busy= { ! loaded }
							disabled= { ! loaded }
							onClick={ this.handleCreateLabelButtonClick }
						>
							{ translate( 'Create shipping label' ) }
						</Button>
						<Button
							onClick={ this.handleTrackPackagesButtonClick }
						>
							{ translate( 'Track Packages' ) }
						</Button>
					</div>
				);
			}

			// All items are packaged, just show track button
			return (
				<span> 
					<span className="shipping-label__redo-shipping-button">
						<Button
							borderless
							onClick={ this.handleCreateLabelButtonClick }
						>
							{ translate( 'Redo Shipping' ) }
						</Button>
					</span>
					<Button
						onClick={ this.handleTrackPackagesButtonClick }
					>
						{ translate( 'Track Packages' ) }
					</Button>
				</span>
			);
		}

		return (
			<Button>
				{ translate( 'Connection error: unable to create label at this time' ) }
			</Button>
		);
	};

	handleCreateLabelButtonClick = () => {
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

	handleTrackPackagesButtonClick = () => {
		const {
			orderId,
			siteId,
		} = this.props;

		this.props.openTrackingFlow( orderId, siteId );
	};

	render() {
		const {
			siteId,
			orderId,
			loaded,
			labelsEnabled,
			items,
			translate,
		} = this.props;

		const shouldRenderButton = ! loaded || labelsEnabled;

		return (
			<div className="shipping-label__container">
				<div>
					<Gridicon size={36} icon="shipping" />
					<em>{ items + ' ' + translate( 'item is ready for shipment', 'items are ready for shipment', { count: items } ) }</em>
				</div>
				<div>
					<QueryLabels orderId={ orderId } siteId={ siteId } origin={ "labels" } />
					<LabelPurchaseModal orderId={ orderId } siteId={ siteId } />
					<TrackingModal orderId={ orderId } siteId={ siteId } />
					{ shouldRenderButton && this.renderLabelButton() }
				</div>
			</div>
		);
	}
}

export default connect(
	( state, { orderId } ) => {
		const siteId = getSelectedSiteId( state );
		const loaded = areLabelsFullyLoaded( state, orderId, siteId );
		const events = getActivityLogEvents( state, orderId );
		const orderLoading = isOrderLoading( state, orderId, siteId );
		const orderLoaded = isOrderLoaded( state, orderId, siteId );

		return {
			siteId,
			loaded,
			events,
			orderLoading,
			orderLoaded,
			labelsEnabled: areLabelsEnabled( state, siteId ),
		};
	},
	( dispatch ) => ( {
		...bindActionCreators( {
			openPrintingFlow,
			openTrackingFlow,
			setEmailDetailsOption,
			setFulfillOrderOption,
			fetchOrder,
		}, dispatch ),
	} ),
)( localize( ShippingLabelViewWrapper ) );
