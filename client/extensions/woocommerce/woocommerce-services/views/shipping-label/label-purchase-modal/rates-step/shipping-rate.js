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
import Card from 'components/card';

function ShippingRate( { rateObject: { service_id }, isSelected, updateValue } ) {
	return <Card>
		<RadioControl
			help="The type of the current user"
			selected={ isSelected ? service_id : null }
			options={ [
				{ label: '', value: service_id },
			] }
			onChange={ () => { updateValue( service_id ) } }
		/>
		</Card>
}

ShippingRate.propTypes = {
	rateObject: PropTypes.object.isRequired,
};

export default localize( ShippingRate );
