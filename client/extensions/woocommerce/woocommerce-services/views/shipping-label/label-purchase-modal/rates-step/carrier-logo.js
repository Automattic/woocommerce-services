/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import usps from './logo/usps.png';


/**
 * Internal dependencies
 */

function CarrierLogo( { carrier_id } ) {
	switch ( carrier_id ) {
		case "usps":
			return <img className="rates-step__carier-logo-image-usps" src={ usps } alt="" />;
	
		default:
			return <div/>
	}
}

CarrierLogo.propTypes = {
	carrier_id: PropTypes.string.isRequired,
};

export default CarrierLogo;