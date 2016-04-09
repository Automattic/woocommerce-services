import React, { PropTypes } from 'react';
import groupBy from 'lodash/groupBy';
import ShippingServiceGroup from './shipping-services-group';

const ShippingServiceGroups = ( {
	services,
	settings,
	currencySymbol,
	onChange,
	settingsKey,
} ) => {
	// Some shippers have so many services that it is helpful to organize them
	// into groups.  This code iterates over the services and extracts the group(s)
	// it finds.  When rendering, we can then iterate over the group(s).
	const servicesWithSettings = services.map( svc => Object.assign( {}, svc, settings[svc.id] ) );
	const serviceGroups = groupBy( servicesWithSettings, svc => svc.group );

	return (
		<div className="wcc-shipping-services-groups">
			{ Object.keys( serviceGroups ).sort().map( serviceGroup => {
				return (
					<ShippingServiceGroup
						key={ serviceGroup }
						title={ serviceGroup }
						services={ serviceGroups[serviceGroup] }
						currencySymbol={ currencySymbol }
						onChange={ onChange }
						settingsKey={ settingsKey }
					/>
				);
			} ) }
		</div>
	);
};

ShippingServiceGroups.propTypes = {
	services: PropTypes.array.isRequired,
	settings: PropTypes.object.isRequired,
	currencySymbol: PropTypes.string,
	onChange: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

ShippingServiceGroups.defaultProps = {
	currencySymbol: '$',
	settings: {},
};

export default ShippingServiceGroups;
