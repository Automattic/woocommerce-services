/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CarrierLogo from './carrier-logo';
import formatCurrency from '@automattic/format-currency';

function ShippingRate( { rateObject: { title, service_id, carrier_id, rate }, isSelected, updateValue } ) {
	return <div className="rates-step__shipping-rate-container">
		<RadioControl
			className="rates-step__shipping-rate-radio-control"
			selected={ isSelected ? service_id : null }
			options={ [
				{ label: '', value: service_id },
			] }
			onChange={ () => { updateValue( service_id ) } }
		/>
		<CarrierLogo carrier_id={ carrier_id }/>
		<div className="rates-step__shipping-rate-information">
			<div className="rates-step__shipping-rate-description">
				<div className="rates-step__shipping-rate-description-title">{ title }</div>
				<div className="rates-step__shipping-rate-description-details"></div>
			</div>
			<div className="rates-step__shipping-rate-rate">{ formatCurrency( rate, 'USD' ) }</div>
		</div>
	</div>
}

ShippingRate.propTypes = {
	rateObject: PropTypes.object.isRequired,
};

export default localize( ShippingRate );
