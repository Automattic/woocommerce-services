/** @format */

/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import EllipsisMenu from 'components/ellipsis-menu';
import PopoverMenuItem from 'components/popover/menu-item';
import RefundDialog from './label-refund-modal';
import ReprintDialog from './label-reprint-modal';
import DetailsDialog from './label-details-modal';
import TrackingLink from './tracking-link';
import {
	openRefundDialog,
	openReprintDialog,
	openDetailsDialog,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';

export class LabelItem extends Component {
	renderRefund = label => {
		const { orderId, siteId, translate } = this.props;

		const today = new Date();
		const thirtyDaysAgo = new Date().setDate( today.getDate() - 30 );
		if (
			label.anonymized ||
			label.usedDate ||
			( label.createdDate && label.createdDate < thirtyDaysAgo )
		) {
			return null;
		}

		const openDialog = () => {
			this.props.openRefundDialog( orderId, siteId, label.labelId );
		};

		return (
			<PopoverMenuItem onClick={ openDialog } icon="refund">
				{ translate( 'Request refund' ) }
			</PopoverMenuItem>
		);
	};

	renderReprint = label => {
		const todayTime = new Date().getTime();
		if (
			label.anonymized ||
			label.usedDate ||
			( label.expiryDate && label.expiryDate < todayTime )
		) {
			return null;
		}

		const { orderId, siteId, translate } = this.props;

		const openDialog = () => {
			this.props.openReprintDialog( orderId, siteId, label.labelId );
		};

		return (
			<PopoverMenuItem onClick={ openDialog } icon="print">
				{ translate( 'Reprint' ) }
			</PopoverMenuItem>
		);
	};

	renderLabelDetails = label => {
		const { orderId, siteId, translate } = this.props;

		const openDialog = () => {
			this.props.openDetailsDialog( orderId, siteId, label.labelId );
		};

		return (
			<PopoverMenuItem onClick={ openDialog } icon="info-outline">
				{ translate( 'View details' ) }
			</PopoverMenuItem>
		);
	};

	renderPickup = label => {
		const { translate } = this.props;
		const pickup_urls = {
			'usps': 'https://tools.usps.com/schedule-pickup-steps.htm',
			'fedex': 'https://www.fedex.com/en-us/shipping/schedule-manage-pickups.html',
		};

		if ( ! ( pickup_urls.hasOwnProperty( label.carrierId ) ) ) {
			return null;
		}

		const onClickOpenPage = () => {
			window.open(pickup_urls[ label.carrierId ], '_blank');
		};

		return (
			<PopoverMenuItem onClick={ onClickOpenPage } icon="external">
				{ translate( 'Schedule a pickup' ) }
			</PopoverMenuItem>
		);
	};

	render() {
		const { siteId, orderId, label, translate, isModal } = this.props;
		const {
			labelIndex,
			serviceName,
			packageName,
			productNames,
			receiptId,
			labelId,
			createdDate,
			refundableAmount,
			currency,
		} = label;

		return (
			<div className="shipping-label__item">
				<p className="shipping-label__item-detail">
					{ translate( '%(service)s label (#%(labelIndex)d)', {
						args: {
							service: label.serviceName,
							labelIndex: label.labelIndex + 1,
						},
					} ) }
					{ label.showDetails && (
						<span>
							{ ( ! isModal && (
								<EllipsisMenu position="bottom left">
									{ this.renderLabelDetails( label ) }
									{ this.renderPickup( label ) }
									{ this.renderRefund( label ) }
									{ this.renderReprint( label ) }
								</EllipsisMenu>
							) ) }
							<DetailsDialog
								siteId={ siteId }
								orderId={ orderId }
								labelIndex={ labelIndex }
								serviceName={ serviceName }
								packageName={ packageName }
								productNames={ productNames }
								receiptId={ receiptId }
								labelId={ labelId }
							/>
							<RefundDialog
								siteId={ siteId }
								orderId={ orderId }
								createdDate={ createdDate }
								refundableAmount={ refundableAmount }
								currency={ currency }
								labelId={ labelId }
							/>
							<ReprintDialog siteId={ siteId } orderId={ orderId } labelId={ labelId } />
						</span>
					) }
				</p>
				{ label.showDetails && (
					<p className="shipping-label__item-tracking">
						{ translate( 'Tracking #: {{trackingLink/}}', {
							components: { trackingLink: <TrackingLink { ...label } /> },
						} ) }
					</p>
				) }
			</div>
		);
	}
}

LabelItem.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	label: PropTypes.object.isRequired,
	isModal: PropTypes.bool.isRequired,
	openRefundDialog: PropTypes.func.isRequired,
	openReprintDialog: PropTypes.func.isRequired,
	openDetailsDialog: PropTypes.func.isRequired,
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators( { openRefundDialog, openReprintDialog, openDetailsDialog }, dispatch );
};

export default connect(
	null,
	mapDispatchToProps
)( localize( LabelItem ) );
