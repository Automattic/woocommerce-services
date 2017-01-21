import React, { PropTypes } from 'react';
import ShippingServiceEntry from './entry';
import FoldableCard from 'components/foldable-card';
import CheckBox from 'components/forms/form-checkbox';
import Gridicon from 'components/gridicon';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';
import _ from 'lodash';

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
		updateValue( [ service.id, 'enabled' ], event.target.checked );
	} );
};

const ShippingServiceGroup = ( props ) => {
	const {
		title,
		services,
		updateValue,
		errors,
		predefinedPackages,
	} = props;
	const actionButton = (
		<button className="foldable-card__action foldable-card__expand" type="button">
			<span className="screen-reader-text">{ __( 'Expand Services' ) }</span>
			<Gridicon icon="chevron-down" size={ 24 } />
		</button>
	);
	//filter out the services that have a disabled corresponding predefined package
	const manageableServices = services.filter( ( service ) => ! service.predefined_package || predefinedPackages.includes( service.predefined_package ) );
	const allChecked = _.every( manageableServices, ( service ) => service.enabled );
	const renderHeader = () => {
		return <div className="wcc-shipping-services-group-header">
			<CheckBox
				onClick={ ( event ) => event.stopPropagation() }
				onChange={ ( event ) => updateAll( event, updateValue, manageableServices ) }
				checked={ allChecked }
			/>
			{ title }
		</div>;
	};
	const summary = summaryLabel( manageableServices );

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
				<label className="wcc-shipping-service-header-container">
					<span className="wcc-shipping-service-header service-name">{ __( 'Service' ) }</span>
					<span className="wcc-shipping-service-header price-adjustment">{ __( 'Price adjustment' ) }</span>
				</label>
			</div>

			{ services.map( ( service, idx ) => (
				<ShippingServiceEntry
					{ ...props }
					{ ...{ service } }
					isManageable={ manageableServices.includes( service ) }
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
	predefinedPackages: PropTypes.array.isRequired,
};

export default ShippingServiceGroup;
