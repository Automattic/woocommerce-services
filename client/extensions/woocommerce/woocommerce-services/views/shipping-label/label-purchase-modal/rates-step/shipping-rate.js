/** @format */

/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import PropTypes from 'prop-types';
import { translate, localize, moment } from 'i18n-calypso';
import { RadioControl, SelectControl } from '@wordpress/components';
import { mapValues, concat, values } from 'lodash';

/**
 * Internal dependencies
 */
import CarrierLogo from './carrier-logo';
import formatCurrency from '@automattic/format-currency';
import {
	openRateSignatureOptions,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

class ShippingRate extends Component {
	constructor() {
		super();
		this.state = {
			signatureOption: false
		}
	}

	setRateActive = () => {
		const { orderId, rateObject: { rate_id } } = this.props;
		this.props.openRateSignatureOptions( orderId, rate_id );
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

	renderServices( carrier_id, includedServices ) {

		const servicesToRender = [];

		if ( includedServices.tracking ) {
			switch ( carrier_id ) {
				case 'usps':
					// Ideally this would come from connect-server, but we have no info from EasyPost API
					// Refer to: https://www.easypost.com/docs/api/node#trackers, specifically
					// `A Tracker is created automatically whenever you buy a Shipment through EasyPost`
					servicesToRender.push( translate( 'Includes USPS tracking' ) );
					break;
				default:
					servicesToRender.push( translate( 'Includes tracking' ) );
			}
		}
		if ( includedServices.insurance ) {
			servicesToRender.push( translate( 'Insurance (up to %s)', { args: [ formatCurrency( includedServices.insurance, 'USD') ] } ) );
		}
		if ( includedServices.signature_required ) {
			servicesToRender.push( translate( 'Signature required' ) );
		}
		if ( includedServices.free_pickup ) {
			servicesToRender.push( translate( 'Eligible for free pickup' ) );
		}

		return servicesToRender.join(', ');
	}

	render() {
		const { signatureOption } = this.state;
		const {
			rateObject: {
				rate_id,
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
			includedServices = {},
			activeRateId
		} = this.props;

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

		if ( includedServices.signature_required  ) {
			signatureOptions.unshift( {
				label: translate( 'Signature required (Free)' ),
				value: 0
			} );
		}

		let deliveryDateMessage = '';

		if ( delivery_date_guaranteed && delivery_date ) {
			deliveryDateMessage = moment( delivery_date ).format( 'MMMM D' );
		} else if ( delivery_days ) {
			deliveryDateMessage = translate( '%(delivery_days)s business day', '%(delivery_days)s business days', {
				count: delivery_days,
				args: { delivery_days }
			} );
		}

		const isRateActive = activeRateId === rate_id;

		return(
			<div className="rates-step__shipping-rate-container" onClick={ this.setRateActive } >
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
							{ this.renderServices( carrier_id, includedServices ) }
							{ isRateActive && signatureOptions.length > 1 ? (
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
	orderId: PropTypes.number.isRequired,
	siteId: PropTypes.number.isRequired,
	rateObject: PropTypes.object.isRequired,
	//includedServices: PropTypes.object.isRequired,
	openRateSignatureOptions: PropTypes.func.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const shippingLabelState = getShippingLabel( state, orderId, siteId );
	return {
		activeRateId: shippingLabelState.activeRateId
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators( { openRateSignatureOptions }, dispatch );
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( ShippingRate ) );
