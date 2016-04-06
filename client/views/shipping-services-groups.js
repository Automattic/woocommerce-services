import React, { PropTypes } from 'react';
import ShippingServiceGroup from './shipping-services-group';

import mapValues from 'lodash/mapValues';
import sortedUniq from 'lodash/sortedUniq';
import values from 'lodash/values';

const ShippingServiceGroups = ( {
	services,
	currencySymbol,
	onChange,
	settingsKey,
} ) => {
	// Some shippers have so many services that it is helpful to organize them
	// into groups.  This code iterates over the services and extracts the group(s)
	// it finds.  When rendering, we can then iterate over the group(s).
	const serviceGroups = sortedUniq( values( mapValues( services, 'group' ) ).sort() );

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
};

ShippingServiceGroups.defaultProps = {
	currencySymbol: '$',
};

export default ShippingServiceGroups;
