import React, { PropTypes } from 'react';
import ShippingServiceEntry from './entry';
import FoldableCard from 'components/foldable-card';
import Gridicon from 'components/gridicon';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';

const summaryLabel = ( services ) => {
	const numSelected = services.reduce( ( count, service ) => (
		count + ( service.enabled ? 1 : 0 )
	), 0 );
	if ( numSelected === services.length ) {
		return __( 'All services selected' );
	}
	const format = ( 1 === numSelected ) ? __( '%d service selected' ) : __( '%d services selected' );
	return sprintf( format, numSelected );
};

const ShippingServiceGroup = ( {
	title,
	services,
	currencySymbol,
	updateValue,
	settingsKey,
} ) => {
	const summary = summaryLabel( services );
	const actionButton = (
		<button className="foldable-card__action foldable-card__expand" type="button">
			<span className="screen-reader-text">{ __( 'Expand Services' ) }</span>
			<Gridicon icon="chevron-up" size={ 24 } />
		</button>
	);
	return (
		<FoldableCard
			header={ title }
			summary={ summary }
			expandedSummary={ summary }
			clickableHeader={ true }
			actionButton={ actionButton }
			actionButtonExpanded={ actionButton }
		>
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
};

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
