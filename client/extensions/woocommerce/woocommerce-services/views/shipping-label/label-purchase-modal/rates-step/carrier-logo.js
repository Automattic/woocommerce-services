/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import fedex from './logos/fedex.svg';
import usps from './logos/usps.png';
import ups from './logos/ups.png';

/**
 * Internal dependencies
 */
import { translate } from 'i18n-calypso';

function CarrierLogo( { carrier_id } ) {
	switch ( carrier_id ) {
		case 'fedex':
			return <div><img alt={ translate( 'Fedex' ) } src={ fedex } /></div>;
		case 'usps':
			return <div><img alt={ translate( 'USPS' ) } src={ usps } /></div>;
		case 'ups':
			return <div><img alt={ translate( 'UPS' ) } src={ ups } /></div>;
		default:
			return <div />;
	}
}

CarrierLogo.propTypes = {
	carrier_id: PropTypes.string.isRequired,
};

export default CarrierLogo;
