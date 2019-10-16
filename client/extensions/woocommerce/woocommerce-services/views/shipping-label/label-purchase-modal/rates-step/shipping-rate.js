/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { translate, localize } from 'i18n-calypso';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CarrierLogo from './carrier-logo';
import formatCurrency from '@automattic/format-currency';

function ShippingRate( { rateObject: { title, service_id, carrier_id, rate, delivery_days }, isSelected, updateValue } ) {
	let details = 'Includes tracking';

	switch ( carrier_id ) {
		case 'usps':
			// Ideally this would come from connect-server, but we have no info from EasyPost API
			// Refer to: https://www.easypost.com/docs/api/node#trackers, specifically
			// `A Tracker is created automatically whenever you buy a Shipment through EasyPost`
			details = translate( 'Includes USPS tracking' );
			break;
	}

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
				<div className="rates-step__shipping-rate-description-details">{ details }</div>
			</div>
			<div className="rates-step__shipping-rate-details">
				<div className="rates-step__shipping-rate-rate">{ formatCurrency( rate, 'USD' ) }</div>
				<div className="rates-step__shipping-rate-delivery-date">
					{ ! delivery_days ? '' : translate( '%(delivery_days)s business day', '%(delivery_days)s business days', { count: delivery_days, args: { delivery_days } } ) }
				</div>
			</div>
		</div>
	</div>
}

ShippingRate.propTypes = {
	rateObject: PropTypes.object.isRequired,
};

export default localize( ShippingRate );
