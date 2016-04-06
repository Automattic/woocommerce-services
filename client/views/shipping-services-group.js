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
					key={ service.title }
					enabled={ service.enabled }
					title={ service.title }
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
		enabled: PropTypes.bool.isRequired,
		title: PropTypes.string.isRequired,
		adjustment: PropTypes.number.isRequired,
		adjustment_type: PropTypes.string.isRequired,
	} ) ).isRequired,
	currencySymbol: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

export default ShippingServiceGroup;
