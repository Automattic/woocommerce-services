import React, { PropTypes } from 'react';
import ShippingServiceGroup from './shipping-services-group';

const ShippingServiceGroups = ( { services, currencySymbol, onChange, onChangeScope } ) => {
	let serviceGroups = [];

	// Some shippers have so many services that it is helpful to organize them
	// into groups.  This code iterates over the services and extracts the group(s)
	// it finds.  When rendering, we can then iterate over the group(s).
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
						changeScope="services"
					/>
				);
			} ) }
		</div>
	);
};

ShippingServiceGroups.propTypes = {
	services: PropTypes.array.isRequired,
	currencySymbol: PropTypes.string,
	onChange: PropTypes.func.isRequired,
	onChangeScope: PropTypes.string.isRequired
};

ShippingServiceGroups.defaultProps = {
	currencySymbol: '$'
};

export default ShippingServiceGroups;
