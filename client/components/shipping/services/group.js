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

const ShippingServiceGroup = ( props ) => {
	const {
		title,
		services,
		updateValue,
		errors,
	} = props;
	const summary = summaryLabel( services );
	const actionButton = (
		<button className="foldable-card__action foldable-card__expand" type="button">
			<span className="screen-reader-text">{ __( 'Expand Services' ) }</span>
			<Gridicon icon="chevron-down" size={ 24 } />
		</button>
	);
	const allChecked = every( services, ( service ) => service.enabled );

	return (
		<FoldableCard
			header={ title }
			summary={ summary }
			expandedSummary={ summary }
			clickableHeader={ true }
			compact
			actionButton={ actionButton }
			actionButtonExpanded={ actionButton }
			expanded={ Boolean( errors && errors.length ) }
		>
			<div className="wcc-shipping-service-entry">
				<label className="wcc-shipping-service-entry-title">
					<CheckBox
						onClick={ ( event ) => event.stopPropagation() }
						onChange={ ( event ) => updateAll( event, updateValue, services ) }
						checked={ allChecked }
					/>
					<strong>Service</strong>
				</label>
			</div>

			{ services.map( ( service, idx ) => <ShippingServiceEntry { ...props } { ...{ service } } key={ idx }/> ) }
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
	updateValue: PropTypes.func.isRequired,
};

export default ShippingServiceGroup;
