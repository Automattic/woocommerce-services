/** @format */

/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { translate, moment } from 'i18n-calypso';
import { CheckboxControl, RadioControl, Tooltip } from '@wordpress/components';
import { mapValues, values } from 'lodash';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import CarrierIcon from 'woocommerce/woocommerce-services/components/carrier-icon';
import formatCurrency from '@automattic/format-currency';

class ShippingRate extends Component {
	constructor() {
		super();
		this.state = {
			signatureOption: false
		}
	}

	onSignatureChecked = ( isChecked, i, signatureOption ) => {
		const { rateObject: { service_id, carrier_id }, updateValue } = this.props;
		const selectedSignature = isChecked ? { id: i, value: signatureOption.value, netCost: signatureOption.netCost } : null;
		this.setState( { selectedSignature } );
		updateValue( service_id, carrier_id, isChecked ? signatureOption.value : 0 );
	}

	renderServices( carrier_id, signatureOptions, includedServices ) {

		const servicesToRender = [];

		if ( includedServices.tracking ) {
			switch ( carrier_id ) {
				case 'usps':
					servicesToRender.push( translate( 'Includes USPS tracking' ) );
					break;
				default:
					servicesToRender.push( translate( 'Includes tracking' ) );
			}
		}
		if ( includedServices.insurance ) {
			const numericInsurance = Number( includedServices.insurance );
			if ( isNaN( numericInsurance ) ) {
				servicesToRender.push( translate( 'Insurance (%s)', { args: [ includedServices.insurance ] } ) );
			} else if ( numericInsurance > 0 ) {
				servicesToRender.push( translate( 'Insurance (up to %s)', { args: [ formatCurrency( numericInsurance, 'USD') ] } ) );
			}
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
			currency,
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
			deliveryDateMessage = moment( delivery_date ).format('LL').split(',')[0]; // moment does not have a date format without year so we'll do it this way.
		} else if ( delivery_days ) {
			deliveryDateMessage = translate( '%(delivery_days)s business day', '%(delivery_days)s business days', {
				count: delivery_days,
				args: { delivery_days }
			} );
		}

		const ratePlusSignatureCost = selectedSignature ? rate + selectedSignature.netCost : rate;

		// Add suffix for non USD.
		const formattedRatePlusSignatureCost = ( typeof currency != 'undefined' && 'USD' !== currency ) ? formatCurrency( ratePlusSignatureCost, 'USD' ) + ' USD' : formatCurrency( ratePlusSignatureCost, 'USD' );

		return(
			<div className="rates-step__shipping-rate-container">
				<RadioControl
					className="rates-step__shipping-rate-radio-control"
					selected={ isSelected ? service_id : null }
					options={ [
						{ label: '', value: service_id },
					] }
					onChange={ () => { updateValue( service_id, carrier_id, selectedSignature ? selectedSignature.value : false ) } }
				/>
				<div className="rates-step__shipping-rate-information">
					<CarrierIcon carrier={ carrier_id } size={ 30 } />
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
						<div className="rates-step__shipping-rate-rate">
							{
								'ups' === carrier_id ?
									<Tooltip
										position="top left"
										text={translate(
											'This rate is provided by your connected UPS account'
										)}
									>
										<div className="rates-step__shipping-rate-rate-tooltip">
											<Gridicon icon="help-outline" size={18}/>
										</div>
									</Tooltip>
									: null
							}
							{ formattedRatePlusSignatureCost }
						</div>
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
		delivery_days: PropTypes.number,
		delivery_date_guaranteed: PropTypes.bool,
		delivery_date: PropTypes.string,
		tracking: PropTypes.bool,
		insurance: PropTypes.oneOfType([
			PropTypes.string,
			PropTypes.number
		]),
		free_pickup: PropTypes.bool,
	}).isRequired,
	signatureRates: PropTypes.object.isRequired,
};

export default ShippingRate;
