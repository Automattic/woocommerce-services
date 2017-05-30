import React, { PropTypes } from 'react';
import ShippingServiceEntry from './entry';
import FoldableCard from 'components/foldable-card';
import Checkbox from 'components/checkbox';
import Gridicon from 'gridicons';
import InfoTooltip from 'components/info-tooltip';
import { translate as __ } from 'i18n-calypso';
import _ from 'lodash';

const summaryLabel = ( services, numSelected ) => {
	if ( numSelected === services.length ) {
		return __( 'All services selected' );
	}
	return __(
		'%(numSelected)d service selected',
		'%(numSelected)d services selected',
		{
			count: numSelected,
			args: { numSelected },
		}
	);
};

const updateAll = ( event, updateValue, services ) => {
	services.forEach( ( service ) => {
		updateValue( [ service.id, 'enabled' ], event.target.checked );
	} );
};

const ShippingServiceGroup = ( props ) => {
	const {
		title,
		services,
		updateValue,
		errors,
	} = props;
	const actionButton = (
		<button className="foldable-card__action foldable-card__expand" type="button">
			<span className="screen-reader-text">{ __( 'Expand Services' ) }</span>
			<Gridicon icon="chevron-down" size={ 24 } />
		</button>
	);

	const numSelected = services.reduce( ( count, service ) => (
		count + ( service.enabled ? 1 : 0 )
	), 0 );
	const renderHeader = () => {
		return <div className="wcc-shipping-services-group-header">
			<Checkbox
				checked={ services.length === numSelected }
				partialChecked={ Boolean( numSelected ) }
				onChange={ ( event ) => ( updateAll( event, updateValue, services ) ) }
				onClick={ ( event ) => event.stopPropagation() } />
			{ title }
		</div>;
	};

	const summary = summaryLabel( services, numSelected );

	return (
		<FoldableCard
			header={ renderHeader() }
			summary={ summary }
			expandedSummary={ summary }
			clickableHeader={ true }
			compact
			actionButton={ actionButton }
			actionButtonExpanded={ actionButton }
			expanded={ ! _.isEmpty( errors ) }
		>
			<div className="wcc-shipping-service-entry multi-select-header">
				<span className="wcc-shipping-service-header service-name">{ __( 'Service' ) }</span>
				<span className="wcc-shipping-service-header price-adjustment">
					{ __( 'Price adjustment' ) }
					<InfoTooltip
						className="price-adjustment-info"
						position="top left"
						maxWidth={ 230 }>
						{ __( 'Increase the rates calculated by the carrier to account for packaging and handling costs. You can also add a negative amount to save your customers money.' ) }
					</InfoTooltip>
				</span>
			</div>

			{ services.map( ( service, idx ) => (
				<ShippingServiceEntry
					{ ...props }
					{ ...{ service } }
					updateValue={ ( key, val ) => updateValue( [ service.id ].concat( key ), val ) }
					key={ idx }
				/>
				) ) }
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
