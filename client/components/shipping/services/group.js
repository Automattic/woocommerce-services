import React, { PropTypes } from 'react';
import ShippingServiceEntry from './entry';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';

const ShippingServiceGroup = ( {
	title,
	services,
	currencySymbol,
	updateValue,
	settingsKey,
} ) => (
	<FoldableCard
		header={ title }
		screenReaderText={ __( 'More' ) }
		summary="3 services selected">
		{ services.map( service => {
			return (
				<ShippingServiceEntry
					key={ service.id }
					enabled={ service.enabled }
					title={ service.name }
					adjustment={ service.adjustment }
					adjustment_type={ service.adjustment_type }
					currencySymbol={ currencySymbol }
					updateValue={ ( key, val ) => updateValue( service.id, key, val ) }
					settingsKey={ settingsKey }
				/>
			);
		} ) }
	</FoldableCard>
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
