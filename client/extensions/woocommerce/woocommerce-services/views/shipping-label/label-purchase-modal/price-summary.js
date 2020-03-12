/** @format */

/**
 * External dependencies
 */
import React, { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';
import formatCurrency from '@automattic/format-currency';
import { Tooltip } from '@wordpress/components';
/**
 * Internal dependencies
 */
import { getTotalPriceBreakdown } from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

class PriceSummary extends Component {
	renderDiscount = (discount) => {
		const { translate } = this.props;
		const tooltipText = translate( "WooCommerce Services gives you access to USPS Commercial Pricing, which is discounted over Retail rates.");

		return (
			<div className="label-purchase-modal__price-item-help">
				<Tooltip
					className="label-purchase-modal__price-item-tooltip is-dialog-visible"
				    position="top center"
					text={ tooltipText }>
					<div className="label-purchase-modal__discount">
						{ translate( 'You save %s with WooCommerce Services', { args: [ formatCurrency( discount, 'USD' ) ]} ) }
						<Gridicon
							icon="help-outline"
							size={ 18 }
						/>
					</div>
				</Tooltip>
			</div>
		);
	};

	renderRow = ( itemName, itemCost, key, isTotal ) => {
		const className = classNames( 'label-purchase-modal__price-item', {
			'label-purchase-modal__price-item-total': isTotal,
		} );
		return (
			<div key={ key } className={ className }>
				<div className="label-purchase-modal__price-item-name">{ itemName }</div>
				<div className="label-purchase-modal__price-item-amount">
					{ formatCurrency( itemCost, 'USD' ) }
				</div>
			</div>
		);
	};

	render() {
		const { priceBreakdown, translate } = this.props;
		if ( ! priceBreakdown ) {
			return null;
		}

		const { prices, discount, total } = priceBreakdown;

		return (
			<div className="label-purchase-modal__shipping-summary-section">
				<hr />
				{ prices.map( ( service, index ) => {
					const title = translate( 'Package %(index)s', {
						args: {
							index: index + 1,
						}
					} );
					return (
						<Fragment key={ index }>
							{ this.renderRow( title, service.rateWithDiscount, index ) }
							{ service.addons.map( ( addon, addonIndex ) =>
								<div key={ 'addons-' + index } className="label-purchase-modal__price-item-addons">
									{ this.renderRow( addon.title, addon.rate, 'addon-' + addonIndex ) }
								</div>
							) }
						</Fragment>
					);
				} ) }
				{ this.renderRow( translate( 'Total' ), total, 'total', true ) }
				{ 0 < discount && this.renderDiscount( discount ) }
			</div>
		);
	}
}

PriceSummary.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
};

export default connect( ( state, { orderId, siteId } ) => {
	const priceBreakdown = getTotalPriceBreakdown( state, orderId, siteId );
	return {
		priceBreakdown,
	};
} )( localize( PriceSummary ) );
