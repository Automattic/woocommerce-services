import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Button from 'components/button';
import { translate as __ } from 'lib/mixins/i18n';
import PurchaseLabelDialog from './purchase';
import RefundDialog from './refund';
import ReprintDialog from './reprint';
import TrackingLink from './tracking-link';
import Spinner from 'components/spinner';
import formatDate from 'lib/utils/format-date';
import * as ShippingLabelActions from 'shipping-label/state/actions';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import getFormErrors from 'shipping-label/state/selectors/errors';
import canPurchase from 'shipping-label/state/selectors/can-purchase';

let labelsStatusUpdateTriggered = false;

const ShippingLabelRootView = ( props ) => {
	const renderPurchaseLabelFlow = () => {
		return (
			<div>
				<PurchaseLabelDialog
					{ ...props.shippingLabel }
					{ ...props } />
				<Button onClick={ props.labelActions.openPrintingFlow } >
					{ __( 'Create label' ) }
				</Button>
			</div>
		);
	};

	const renderLabelActions = ( label, index ) => {
		if ( ! label.statusUpdated ) {
			return <Spinner key={ index } size={ 24 } />;
		}

		if ( label.refunded_time ) {
			return (
				<div key={ index }>
					<dl>
						<dt>{ __( 'Refund status' ) }</dt>
						<dd>{ __( 'Refund submitted' ) }</dd>

						<dt>{ __( 'Refund request date' ) }</dt>
						<dd>{ formatDate( label.refunded_time ) }</dd>
					</dl>
				</div>
			);
		}

		return (
			<div key={ index }>
				<dl>
					<dt>{ __( 'Tracking' ) }</dt>
					<dd><TrackingLink { ...label }/></dd>

					<dt>{ __( 'Ship via' ) }</dt>
					<dd>{ label.service_name }</dd>

					<dt>{ __( 'Purchase date' ) }</dt>
					<dd>{ formatDate( label.created ) }</dd>
				</dl>
				<div className="wcc-meta-box-action-buttons">
					<ReprintDialog
						reprintDialog={ props.shippingLabel.reprintDialog }
						{ ...props.shippingLabel }
						{ ...props }
						{ ...label } />
					<Button onClick={ () => props.labelActions.openReprintDialog( label.label_id ) } >
						{ __( 'Reprint label' ) }
					</Button>

					<RefundDialog
						refundDialog={ props.shippingLabel.refundDialog }
						{ ...props.shippingLabel }
						{ ...props }
						{ ...label } />
					<Button onClick={ () => props.labelActions.openRefundDialog( label.label_id ) } >
						{ __( 'Refund label' ) }
					</Button>
				</div>
			</div>
		);
	};

	const renderLabelsActions = () => {
		if ( ! labelsStatusUpdateTriggered ) {
			labelsStatusUpdateTriggered = true;
			props.labelActions.fetchLabelsStatus();
		}
		return (
			<div>
				{ props.shippingLabel.labels.map( renderLabelActions ) }
			</div>
		);
	};

	return (
		<div className="wcc-metabox-shipping-label-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			{ props.shippingLabel.labels.length ? renderLabelsActions() : renderPurchaseLabelFlow() }
		</div>
	);
};

ShippingLabelRootView.propTypes = {
	storeOptions: PropTypes.object.isRequired,
};

function mapStateToProps( state, { storeOptions } ) {
	return {
		shippingLabel: state.shippingLabel,
		errors: getFormErrors( state, storeOptions ),
		canPurchase: canPurchase( state, storeOptions ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		labelActions: bindActionCreators( ShippingLabelActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( ShippingLabelRootView );
