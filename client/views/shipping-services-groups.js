import React, { PropTypes } from 'react';
import ShippingServiceGroup from './shipping-services-group';

const ShippingServiceGroups = ( { services } ) => {
	let serviceGroups = [];
	services.forEach( service => {
		service.group = service.group || '';
		if ( -1 === serviceGroups.indexOf( service.group ) ) {
			serviceGroups.push( service.group );
		}
	} );
	serviceGroups.sort();

	return (
		<div className="wcc-shipping-services-groups">
			{ serviceGroups.map( serviceGroup => {
				return (
					<ShippingServiceGroup
						key={ serviceGroup }
						title={ serviceGroup }
						services={ services.filter( service => service.group === serviceGroup ) }
					/>
				);
			} ) }
		</div>
	);
};

ShippingServiceGroups.propTypes = {
	services: PropTypes.array.isRequired
};

export default ShippingServiceGroups;
