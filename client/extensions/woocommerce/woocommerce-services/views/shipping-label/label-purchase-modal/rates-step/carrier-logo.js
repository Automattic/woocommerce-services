/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';


/**
 * Internal dependencies
 */

function CarrierLogo( { carrier_id } ) {
	switch ( carrier_id ) {
		default:
			return <div/>;
	}
}

CarrierLogo.propTypes = {
	carrier_id: PropTypes.string.isRequired,
};

export default CarrierLogo;
