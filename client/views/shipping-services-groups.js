import React, { PropTypes } from 'react';
import groupBy from 'lodash/groupBy';
import ShippingServiceGroup from './shipping-services-group';

const ShippingServiceGroups = ( {
	services,
	currencySymbol,
	onChange,
	settingsKey,
	serviceSettings,
} ) => {
	//get default values of settings are not there
	const defaultValues = {
		enabled: false,
		adjustment: 0,
		adjustment_type: 'flat',
	};
	const serviceValues = services.map( svc => Object.assign( {}, defaultValues, svc, serviceSettings[svc.id] ) );

	// Some shippers have so many services that it is helpful to organize them
	// into groups.  This code iterates over the services and extracts the group(s)
	// it finds.  When rendering, we can then iterate over the group(s).
	const serviceGroups = groupBy( serviceValues, svc => svc.group );

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
	currencySymbol: PropTypes.string,
	onChange: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
	serviceSettings: PropTypes.object.isRequired,
};

ShippingServiceGroups.defaultProps = {
	currencySymbol: '$',
};

export default ShippingServiceGroups;
