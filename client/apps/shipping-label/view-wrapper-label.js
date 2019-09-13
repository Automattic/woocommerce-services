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

/**
 * Internal dependencies
 */
// from calypso
import Button from 'components/button';
import LabelPurchaseDialog from '../../extensions/woocommerce/woocommerce-services/views/shipping-label/label-purchase-modal';
import QueryLabels from '../../extensions/woocommerce/woocommerce-services/components/query-labels';
import {
	openPrintingFlow,
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
import { fetchOrder } from '../../extensions/woocommerce/state/sites/orders/actions';

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

	renderLabelButton = () => {
		const {
			loaded,
			translate,
		} = this.props;

		const className = classNames( 'shipping-label__new-label-button', {
			'is-placeholder': ! loaded,
			'is-primary': loaded,
		} );

		return (
			<Button
				className={ className }
				onClick={ this.handleButtonClick } >
				{ translate( 'Create shipping label' ) }
			</Button>
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
			items,
			translate,
		} = this.props;

		const shouldRenderButton = ! loaded || labelsEnabled;

		return (
			<div className="shipping-label__container">
				<div>
					<Gridicon size="36" icon="shipping" />
					<em>{ items + ' ' + translate( 'items need to be fulfilled' ) }</em>
				</div>
				<div>
					<QueryLabels orderId={ orderId } siteId={ siteId } />
					<LabelPurchaseDialog orderId={ orderId } siteId={ siteId } />
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

		return {
			siteId,
			loaded,
			labelsEnabled: areLabelsEnabled( state, siteId ),
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
