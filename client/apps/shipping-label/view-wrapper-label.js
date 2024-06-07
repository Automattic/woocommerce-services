/**
 * External dependencies
 */
import React, { Component, Suspense } from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import classNames from 'classnames';
import Gridicon from 'gridicons';
import { sumBy, differenceBy, filter, maxBy } from 'lodash';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import {getNonce} from 'api/request'; // client/api/request.js

/**
 * Internal dependencies
 */
// from calypso
const LabelPurchaseModal = React.lazy(() => import('../../extensions/woocommerce/woocommerce-services/views/shipping-label/label-purchase-modal'));
const TrackingModal = React.lazy(() => import('../../extensions/woocommerce/woocommerce-services/views/shipping-label/tracking-modal'));

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
import { withLocalizedMoment } from 'components/localized-moment';

export class ShippingLabelViewWrapper extends Component {
	static propTypes = {
		orderId: PropTypes.number.isRequired,
	};

	componentDidMount() {
		const { siteId, orderId, orderLoading, orderLoaded } = this.props;

		if ( siteId && orderId && ! orderLoading && ! orderLoaded) {
			this.props.fetchOrder( siteId, orderId );
		}
	}

	renderLabelButton = ( activeLabels, productsPackaged ) => {
		const {
			loaded,
			translate,
			items,
			orderId,
			siteId,
		} = this.props;

		const className = classNames( 'button', 'shipping-label__new-label-button', {
			'is-placeholder': ! loaded,
		} );

		if ( ! loaded ) {
			return (
				<Button
					className={ 'shipping-label__button-loading' }
					isPrimary
					isBusy={ true }
					disabled={ true }
				>

				</Button>
			);
		}

		// eslint-disable-next-line no-undef
		if ( wcConnectData.wcs_server_connection ) {

			// If there are no purchased labels, just show Create labels button
			if ( ! activeLabels.length ) {
				return (
					<div>
						<Button
							className={ className }
							isPrimary
							isBusy= { ! loaded }
							disabled= { ! loaded }
							onClick={ this.handleCreateLabelButtonClick }
						>
							{ translate( 'Create shipping label' ) }
						</Button>
						<Button
							className={ className }
							isPrimary
							isBusy= { ! loaded }
							disabled= { ! loaded }
							onClick={ this.handleActivateLabelButtonClick }
						>
							{ translate( 'Activate plugin' ) }
						</Button>
						<Suspense fallback={<div />}>
							<LabelPurchaseModal orderId={ orderId } siteId={ siteId } />
						</Suspense>
					</div>
				);
			}

			// If not all items are packaged (but some are, per condition above), show both buttons
			if ( productsPackaged < items ) {
				return (
					<div className="shipping-label__multiple-buttons-container">
						<Button
							className={ className }
							isPrimary
							isBusy= { ! loaded }
							disabled= { ! loaded }
							onClick={ this.handleCreateLabelButtonClick }
						>
							{ translate( 'Create shipping label' ) }
						</Button>
						<Button
							isSecondary
							className={ className }
							onClick={ this.handleTrackPackagesButtonClick }
						>
							{ translate( 'Track Package', 'Track Packages', { count: activeLabels.length } ) }
						</Button>
						<Suspense fallback={<div />}>
							<LabelPurchaseModal orderId={ orderId } siteId={ siteId } />
							<TrackingModal orderId={ orderId } siteId={ siteId } />
						</Suspense>
					</div>
				);
			}

			// All items are packaged, show track button and create shipping label button to allow redo fulfillment
			return (
				<div>
					<span className="shipping-label__redo-shipping-button">
						<Button
							className = { classNames( 'button', 'is-borderless' ) }
							onClick={ this.handleCreateLabelButtonClick }
						>
							{ translate( 'Create new label' ) }
						</Button>
					</span>
					<Button
						isSecondary
						className = { classNames( 'button') }
						onClick={ this.handleTrackPackagesButtonClick }
					>
						{ translate( 'Track Package', 'Track Packages', { count: activeLabels.length } ) }
					</Button>
					<Suspense fallback={<div />}>
						<LabelPurchaseModal orderId={ orderId } siteId={ siteId } />
						<TrackingModal orderId={ orderId } siteId={ siteId } />
					</Suspense>
				</div>
			);
		}

		return (
			<Button>
				{ translate( 'Connection error: unable to create label at this time' ) }
			</Button>
		);
	};

	handleActivateLabelButtonClick = () => {
		const plugins = 'hello-dolly'; //this needs to be a CSV string.
		const installPluginAPICall = () =>
			apiFetch( {
				path: '/wp-json/wc-admin/plugins/install',
				method: 'POST',
				headers: {
					'X-WP-Nonce': getNonce()
				},
				data: {
					plugins
				}
			} );

		const activatePluginAPICall = () =>
			apiFetch( {
				path: '/wp-json/wc-admin/plugins/activate',
				method: 'POST',
				headers: {
					'X-WP-Nonce': getNonce()
				},
				data: {
					plugins
				}
			} );


		const installAndActivatePlugins = async() => {
			try {
				//TODO: status update
				await installPluginAPICall();
				await activatePluginAPICall();
			} catch (e) {
				//TODO: error handling.
				// console.log('Failed to install or activate.', e);
			}
		};

		installAndActivatePlugins();
	};

	handleCreateLabelButtonClick = () => {
		const {
			orderId,
			siteId,
		} = this.props;
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
			loaded,
			labelsEnabled,
			items,
			translate,
			events,
			moment,
		} = this.props;

		const shouldRenderButton = ! loaded || labelsEnabled;

		const labels = filter( events, { type: 'LABEL_PURCHASED' } );
		const refunds = filter( events, { type: 'LABEL_REFUND_REQUESTED' } );
		const activeLabels = differenceBy( labels, refunds, "labelIndex" );
		const productsPackaged = sumBy( activeLabels, (l) => l.productNames.length );

		// Calculate if order fulfilled and find latest date.
		let orderFulfilled = false;
		let createdDate;
		if ( activeLabels.length > 0 && items <= productsPackaged ) {
			orderFulfilled = true;
			const latestLabel = maxBy( activeLabels, 'createdDate' );
			const createdMoment = moment( latestLabel.createdDate );
			createdDate = createdMoment.format( 'll' );
		}

		return (
			<div className="shipping-label__container">
				<div className="shipping-label__banner-fulfilled-message">
					<Gridicon size={36} icon="shipping" />
					{ loaded && ( orderFulfilled ?
							(
								<em>
									{ translate(
										'%(itemCount)d item was fulfilled on {{span}}%(createdDate)s{{/span}}',
										'%(itemCount)d items were fulfilled on {{span}}%(createdDate)s{{/span}}',
										{
											count: items,
											args: {
												createdDate,
												itemCount: items,
											},
											components: {
												span: <span className="shipping-label__banner-fulfilled-date" />,
											},
										}
									) }
								</em>
						) : (
								<em>
									{ translate(
										'%(itemCount)d item is ready to be fulfilled',
										'%(itemCount)d items are ready to be fulfilled',
										{
											count: items,
											args: {
												itemCount: items,
											},
										}
									) }
								</em> )
					) }
				</div>
				<div>
					{ shouldRenderButton && this.renderLabelButton( activeLabels, productsPackaged ) }
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
)( (localize( withLocalizedMoment( ShippingLabelViewWrapper ) ) ) );
