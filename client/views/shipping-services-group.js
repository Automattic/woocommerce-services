import React, { PropTypes } from 'react';
import ShippingServiceEntry from './shipping-services-entry';

const ShippingServiceGroup = ( { title, services } ) => (
	<div className="wcc-shipping-services-group">
		<h4>
			{ title }
		</h4>
		{ services.map( service => {
			return (
				<ShippingServiceEntry
					key={ service.title }
					enabled={ service.enabled }
					title={ service.title }
					adjustment={ service.adjustment }
					adjustmentType={ service.adjustment_type }
				/>
			);
		} ) }
	</div>
);

ShippingServiceGroup.propTypes = {
	title: PropTypes.string.isRequired,
	service: PropTypes.shape( {
		enabled: PropTypes.bool.isRequired,
		title: PropTypes.string.isRequired,
		adjustment: PropTypes.number.isRequired,
		adjustmentType: PropTypes.string.isRequired
	} ).isRequired
};

export default ShippingServiceGroup;
