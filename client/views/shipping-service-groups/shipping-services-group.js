import React, { PropTypes } from 'react';
import ShippingServiceEntry from './shipping-services-entry';

const ShippingServiceGroup = ( {
	title,
	services,
	currencySymbol,
	updateValue,
	settingsKey,
} ) => (
	<div className="wcc-shipping-services-group">
		<h4>
			{ title }
		</h4>
		{ services.map( service => {
			return (
				<ShippingServiceEntry
					key={ service.id }
					enabled={ service.enabled }
					title={ service.name }
					adjustment={ service.adjustment }
					adjustment_type={ service.adjustment_type }
					currencySymbol={ currencySymbol }
					updateValue={ ( key, val ) => updateValue( service.id, key, val) }
					settingsKey={ settingsKey }
				/>
			);
		} ) }
	</div>
);

ShippingServiceGroup.propTypes = {
	title: PropTypes.string.isRequired,
	services: PropTypes.arrayOf( PropTypes.shape( {
		id: PropTypes.string.isRequired,
		name: PropTypes.string.isRequired,
		enabled: PropTypes.bool,
		adjustment: PropTypes.number,
		adjustment_type: PropTypes.string,
	} ) ).isRequired,
	currencySymbol: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

export default ShippingServiceGroup;
