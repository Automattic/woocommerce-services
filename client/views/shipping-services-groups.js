import React, { PropTypes } from 'react';
import ShippingServiceGroup from './shipping-services-group';

const ShippingServiceGroups = ( { services, currencySymbol, onChange } ) => {
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
						currencySymbol={ currencySymbol }
						onChange={ onChange }
					/>
				);
			} ) }
		</div>
	);
};

ShippingServiceGroups.propTypes = {
	services: PropTypes.array.isRequired,
	currencySymbol: PropTypes.string,
	onChange: PropTypes.func.isRequired
};

ShippingServiceGroups.defaultProps = {
	currencySymbol: '$'
};

export default ShippingServiceGroups;
