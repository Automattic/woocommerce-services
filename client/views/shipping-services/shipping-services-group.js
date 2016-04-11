import React, { PropTypes } from 'react';
import ShippingServiceEntry from './shipping-services-entry';

const ShippingServiceGroup = ( {
	title,
	services,
	currencySymbol,
	onChange,
	settingsKey,
} ) => (
	<div className="wcc-shipping-services-group">
		<h4>
			{ title }
		</h4>
		{ services.map( service => {
			return (
				<ShippingServiceEntry
					id={ service.id }
					key={ service.id }
					enabled={ service.enabled }
					title={ service.name }
					adjustment={ service.adjustment }
					adjustment_type={ service.adjustment_type }
					currencySymbol={ currencySymbol }
					onChange={ onChange }
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
	onChange: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

export default ShippingServiceGroup;
