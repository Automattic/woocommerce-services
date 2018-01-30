/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { translate as __ } from 'i18n-calypso';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import ShippingServiceEntry from './entry';
import FoldableCard from 'components/foldable-card';
import Checkbox from 'components/checkbox';
import InfoTooltip from 'components/info-tooltip';

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

class ShippingServiceGroup extends Component {
	static propTypes = {
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

	stopPropagation = ( event ) => event.stopPropagation();
	onChange = ( event ) => updateAll( event, this.props.updateValue, this.props.services );

	renderHeader = ( numSelected ) => {
		return <div>
			<Checkbox
				checked={ this.props.services.length === numSelected }
				partialChecked={ Boolean( numSelected ) }
				onChange={ this.onChange }
				onClick={ this.stopPropagation } />
			{ this.props.title }
		</div>;
	};

	onUpdate = ( id ) => {
		const updateValue = this.props.updateValue;
		return (
			( key, val ) => updateValue( [ id ].concat( key ), val )
		);
	}

	render() {
		const {
			services,
			errors,
		} = this.props;

		const numSelected = services.reduce( ( count, service ) => (
			count + ( service.enabled ? 1 : 0 )
		), 0 );

		const summary = summaryLabel( services, numSelected );

		return (
			<FoldableCard
				header={ this.renderHeader( numSelected ) }
				summary={ summary }
				expandedSummary={ summary }
				clickableHeader={ true }
				compact
				screenReaderText={ __( 'Expand Services' ) }
				expanded={ ! _.isEmpty( errors ) }
			>
				<div className="shipping-services__entry shipping-services__entry-header-container">
					<span className="shipping-services__entry-header">{ __( 'Service' ) }</span>
					<span className="shipping-services__entry-header shipping-services__entry-price-adjustment">
						{ __( 'Price adjustment' ) }
						<InfoTooltip
							className="shipping-services__entry-price-adjustment-info"
							position="top left"
							maxWidth={ 230 }>
							{ __( 'Increase the rates calculated by the carrier to account for packaging and handling costs. ' +
								'You can also add a negative amount to save your customers money.' ) }
						</InfoTooltip>
					</span>
				</div>

				{ services.map( ( service, idx ) => {
					return <ShippingServiceEntry
							{ ...this.props }
							{ ...{ service } }
							updateValue={ this.onUpdate( service.id ) }
							key={ idx }
						/>;
				} ) }
			</FoldableCard>
		);
	}
}

export default ShippingServiceGroup;
