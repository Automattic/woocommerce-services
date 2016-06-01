import React, { PropTypes } from 'react';
import ShippingServiceEntry from './entry';
import FoldableCard from 'components/foldable-card';
import CheckBox from 'components/forms/form-checkbox';
import Gridicon from 'components/gridicon';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';
import every from 'lodash/every';

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

const updateAll = ( event, updateValue, services ) => {
	services.forEach( ( service ) => {
		updateValue( service.id, 'enabled', event.target.checked );
	} )
};

const getCheckbox = ( title, updateValue, services ) => {
	const allChecked = every( services, ( service ) => service.enabled );
	return (
		<div>
			<CheckBox
				onClick={ ( event ) => event.stopPropagation() }
				onChange={ ( event ) => updateAll( event, updateValue, services ) }
				checked={ allChecked }
			/>
			{ title }
		</div>
	);
};

const ShippingServiceGroup = ( {
	title,
	services,
	currencySymbol,
	updateValue,
	settingsKey,
	errors,
} ) => {
	const summary = summaryLabel( services );
	const actionButton = (
		<button className="foldable-card__action foldable-card__expand" type="button">
			<span className="screen-reader-text">{ __( 'Expand Services' ) }</span>
			<Gridicon icon="chevron-down" size={ 24 } />
		</button>
	);
	return (
		<FoldableCard
			header={ getCheckbox( title, updateValue, services ) }
			summary={ summary }
			expandedSummary={ summary }
			clickableHeader={ true }
			compact
			actionButton={ actionButton }
			actionButtonExpanded={ actionButton }
			expanded={ Boolean( errors && errors.length ) }
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
						hasError={ errors.find( ( error ) => (
							error.length && ( error[0] === service.id ) )
						) }
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
		adjustment: PropTypes.oneOfType( [
			PropTypes.string,
			PropTypes.number,
		] ),
		adjustment_type: PropTypes.string,
	} ) ).isRequired,
	currencySymbol: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

export default ShippingServiceGroup;
