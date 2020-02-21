/** @format */

/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import PropTypes from 'prop-types';
import { translate, localize, moment } from 'i18n-calypso';
import { CheckboxControl, RadioControl } from '@wordpress/components';
import { mapValues, values } from 'lodash';

/**
 * Internal dependencies
 */
import CarrierLogo from './carrier-logo';
import formatCurrency from '@automattic/format-currency';

class ShippingRate extends Component {
	constructor() {
		super();
		this.state = {
			signatureOption: false
		}
	}

	onSignatureChecked = ( isChecked, i, signatureOption ) => {
		const { rateObject: { service_id }, updateValue } = this.props;
		const selectedSignature = isChecked ? { id: i, value: signatureOption.netCost } : null;
		this.setState( { selectedSignature } );
		updateValue( service_id, signatureOption.value );
	}

	renderServices( carrier_id, signatureOptions, includedServices ) {

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
		if ( signatureOptions.filter( signatureOption => 0 === signatureOption.netCost ).length > 0 ) {
			servicesToRender.push( translate( 'Signature required' ) );
		}
		if ( includedServices.free_pickup ) {
			servicesToRender.push( translate( 'Eligible for free pickup' ) );
		}

		return servicesToRender.join(', ');
	}

	renderSignatureOptions( signatureOptions ) {
		return ( signatureOptions.map( ( signatureOption, i ) => {
			return <CheckboxControl
						key={ i }
						name={ `signature_option_${i}` }
						label={ signatureOption.label }
						checked={ !!this.state.selectedSignature && this.state.selectedSignature.id === i }
						onChange={ isChecked => this.onSignatureChecked( isChecked, i, signatureOption ) } />;
		} ) );
	}

	render() {
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
				tracking,
				insurance,
				free_pickup,
			},
			isSelected,
			updateValue,
			signatureRates,
		} = this.props;
		const { selectedSignature } = this.state;

		const signatureOptions = values(
			mapValues( signatureRates, ( r, key ) => {
				const priceString = ( 0 === r.optionNetCost ) ? translate( 'free' ) :
					translate( '+%s', { args: [ formatCurrency( r.optionNetCost, 'USD') ] } );
				return {
					label: translate( '%(label)s (%(price)s)', {
						args: { label: r.label, price: priceString },
					} ),
					value: key,
					netCost: r.optionNetCost,
				};
			} )
		);

		let deliveryDateMessage = '';

		if ( delivery_date_guaranteed && delivery_date ) {
			deliveryDateMessage = moment( delivery_date ).format( 'MMMM D' );
		} else if ( delivery_days ) {
			deliveryDateMessage = translate( '%(delivery_days)s business day', '%(delivery_days)s business days', {
				count: delivery_days,
				args: { delivery_days }
			} );
		}

		const ratePlusSignatureCost = selectedSignature ? rate + selectedSignature.value : rate;

		return(
			<div className="rates-step__shipping-rate-container" >
				<RadioControl
					className="rates-step__shipping-rate-radio-control"
					selected={ isSelected ? service_id : null }
					options={ [
						{ label: '', value: service_id },
					] }
					onChange={ () => { updateValue( service_id, false ) } }
				/>
				<CarrierLogo carrier_id={ carrier_id }/>
				<div className="rates-step__shipping-rate-information">
					<div className="rates-step__shipping-rate-description">
						<div className="rates-step__shipping-rate-description-title">{ title }</div>
						<div className="rates-step__shipping-rate-description-details">
							{ this.renderServices( carrier_id, signatureOptions, { tracking, insurance, free_pickup } ) }
							{ isSelected && signatureOptions.length > 1 ? (
								this.renderSignatureOptions( signatureOptions )
							) : null }
						</div>
					</div>
					<div className="rates-step__shipping-rate-details">
						<div className="rates-step__shipping-rate-rate">{ formatCurrency( ratePlusSignatureCost, 'USD' ) }</div>
						<div className="rates-step__shipping-rate-delivery-date">{ deliveryDateMessage }</div>
					</div>
				</div>
			</div>
		);
	}
}

ShippingRate.propTypes = {
	rateObject: PropTypes.shape({
		rate_id: PropTypes.string.isRequired,
		title: PropTypes.string.isRequired,
		service_id: PropTypes.string.isRequired,
		carrier_id: PropTypes.string.isRequired,
		rate: PropTypes.number.isRequired,
		delivery_days: PropTypes.string,
		delivery_date_guaranteed: PropTypes.bool,
		delivery_date: PropTypes.instanceOf( Date ),
		tracking: PropTypes.book,
		insurance: PropTypes.number,
		free_pickup: PropTypes.book,
	}).isRequired,
	signatureRates: PropTypes.object.isRequired,
};

export default ShippingRate;
