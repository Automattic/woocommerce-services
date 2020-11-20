/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import upsLogo from './logos/ups.png';
import uspsLogo from './logos/usps.png';
import dhlExpressLogo from './logos/dhlExpress.png';

const carrierLogos = {
	"ups": upsLogo,
	"upsaccount": upsLogo,
	"usps": uspsLogo,
	"dhlexpress": dhlExpressLogo,
	"dhlexpressaccount": dhlExpressLogo,
};

const sizeToPixels = ( size ) => {
	switch( size ) {
		case 'small':
			return 24;
		case 'big':
			return 40;
		case 'medium':
		default:
			return 30;
	}
}

const CarrierIcon = ( { carrier, size } ) => {
	if (  ! carrier || ! carrierLogos[ carrier.toLowerCase() ] ) {
		return <span/>;
	}
	const pixels = `${sizeToPixels( size )}px`;
	return <div style={{ width: pixels }} className="carrier-icon">
			<img src={ carrierLogos[ carrier.toLowerCase() ] } alt={ carrier } className="carrier-icon__logo" />
	</div>;
};

CarrierIcon.propTypes = {
	carrier: PropTypes.string.isRequired,
	size: PropTypes.number,
};

export default CarrierIcon;
