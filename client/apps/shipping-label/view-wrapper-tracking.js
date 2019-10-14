/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
// from calypso
import QueryLabels from '../../extensions/woocommerce/woocommerce-services/components/query-labels';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import { getSelectedSiteId } from 'state/ui/selectors';
import ActivityLog from '../../extensions/woocommerce/app/order/order-activity-log/events';
import {
	getActivityLogEvents,
} from '../../extensions/woocommerce/state/sites/orders/activity-log/selectors';
import {
	isOrderLoaded,
	isOrderLoading
 } from '../../extensions/woocommerce/state/sites/orders/selectors';
import { fetchOrder } from '../../extensions/woocommerce/state/sites/orders/actions';

class ShipmentTrackingViewWrapper extends Component {
	static propTypes = {
		orderId: PropTypes.number.isRequired,
	};

	componentDidMount() {
		const { siteId, orderId, orderLoading, orderLoaded } = this.props;

		if ( siteId && orderId && ! orderLoading && ! orderLoaded) {
			this.props.fetchOrder( siteId, orderId );
		}
	}

	renderActivityLog = () => {
		const {
			siteId,
			orderId,
			events,
			translate,
		} = this.props;

		if ( 0 === events.length ) {
			return translate( 'No tracking information available at this time' );
		}

		return (
			// eslint-disable-next-line wpcalypso/jsx-classname-namespace
			<div className="shipment-tracking__dummy-class order-activity-log">
				<ActivityLog orderId={ orderId } siteId={ siteId } />
			</div>
		);
	};

	render() {
		const {
			siteId,
			orderId,
		} = this.props;

		return (
			// eslint-disable-next-line wpcalypso/jsx-classname-namespace
			<div className="shipment-tracking__container">
				<GlobalNotices notices={ notices.list } />
				<QueryLabels orderId={ orderId } siteId={ siteId } origin={ "tracking" } />
				{ this.renderActivityLog() }
			</div>
		);
	}
}

export default connect(
	( state, { orderId } ) => {
		const siteId = getSelectedSiteId( state );
		const events = getActivityLogEvents( state, orderId );
		const orderLoading = isOrderLoading( state, orderId, siteId );
		const orderLoaded = isOrderLoaded( state, orderId, siteId );

		return {
			siteId,
			events,
			orderLoading,
			orderLoaded,
		};
	},
	( dispatch ) => ( {
		...bindActionCreators( {
			fetchOrder,
		}, dispatch ),
	} ),
)( localize( ShipmentTrackingViewWrapper ) );
