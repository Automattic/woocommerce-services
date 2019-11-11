/** @format */

/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { translate, localize, moment } from 'i18n-calypso';
import { RadioControl, SelectControl } from '@wordpress/components';
import { mapValues, concat, values } from 'lodash';

/**
 * Internal dependencies
 */
import CarrierLogo from './carrier-logo';
import formatCurrency from '@automattic/format-currency';

class ShippingRate extends Component {
	constructor() {
		super();
		this.state = {
			signatureOption: false,
		}
	}

	setSignatureOption = ( val ) => {
		const {
			rateObject: {
				service_id,
			},
			updateValue,
		} = this.props;

		this.setState( { signatureOption: val } );
		updateValue( service_id, val );
	}

	render() {
		const { signatureOption } = this.state;
		const {
			rateObject: {
				title,
				service_id,
				carrier_id,
				rate,
				delivery_days,
				delivery_date_guaranteed,
				delivery_date,
			},
			isSelected,
			updateValue,
			signatureRates,
		} = this.props;
		let details = 'Includes tracking';

		const defaultOption = {
			label: translate( 'No signature required' ),
			value: false,
		};
		const signatureOptions = concat( defaultOption, values(
			mapValues( signatureRates, ( r, key ) => {
				const priceString = ( 0 === r.optionNetCost ) ? translate( 'free' ) :
					translate( '+%s', { args: [ formatCurrency( r.optionNetCost, 'USD') ] } );
				return {
					label: translate( '%(label)s (%(price)s)', {
						args: { label: r.label, price: priceString },
					} ),
					value: key,
				};
			}
		) ) );

		let deliveryDateMessage = '';
		let requiredSignatureCostText;

		if ( delivery_date_guaranteed && delivery_date ) {
			deliveryDateMessage = moment( delivery_date ).format( 'MMMM D' );
		} else if ( delivery_days ) {
			deliveryDateMessage = translate( '%(delivery_days)s business day', '%(delivery_days)s business days', {
				count: delivery_days,
				args: { delivery_days }
			} );
		}

		switch ( carrier_id ) {
			case 'usps':
				// Ideally this would come from connect-server, but we have no info from EasyPost API
				// Refer to: https://www.easypost.com/docs/api/node#trackers, specifically
				// `A Tracker is created automatically whenever you buy a Shipment through EasyPost`
				details = translate( 'Includes USPS tracking' );
				break;
		}
		return(
			<div className="rates-step__shipping-rate-container">
				<RadioControl
					className="rates-step__shipping-rate-radio-control"
					selected={ isSelected ? service_id : null }
					options={ [
						{ label: '', value: service_id },
					] }
					onChange={ () => { updateValue( service_id, signatureOption ) } }
				/>
				<CarrierLogo carrier_id={ carrier_id }/>
				<div className="rates-step__shipping-rate-information">
					<div className="rates-step__shipping-rate-description">
						<div className="rates-step__shipping-rate-description-title">{ title }</div>
						<div className="rates-step__shipping-rate-description-details">
							{ details }
							{ signatureOptions.length > 1 ? (
								<SelectControl
									className="rates-step__shipping-rate-description-signature-select"
									options={ signatureOptions }
									onChange={ this.setSignatureOption }
								/>
							) : null }
						</div>
					</div>
					<div className="rates-step__shipping-rate-details">
						<div className="rates-step__shipping-rate-rate">{ formatCurrency( rate, 'USD' ) }</div>
						<div className="rates-step__shipping-rate-delivery-date">{ deliveryDateMessage }</div>
					</div>
				</div>
			</div>
		);
	}
}

ShippingRate.propTypes = {
	rateObject: PropTypes.object.isRequired,
};

export default localize( ShippingRate );
