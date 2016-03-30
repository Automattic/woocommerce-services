import React from 'react';
import ShippingServiceGroup from './shipping-services-group';

export default React.createClass( {
	displayName: 'ShippingServiceGroups',

	propTypes: {
		services: React.PropTypes.array.isRequired,
	},

	render: function() {
		let serviceGroups = [];
		this.props.services.forEach( service => {
			if ( -1 === serviceGroups.indexOf( service.group ) ) {
				serviceGroups.push( service.group );
			}
		} );
		serviceGroups.sort();

		return (
			<div className="wcc-shipping-services-groups">
				{ serviceGroups.map( serviceGroup => {
					return (
						<ShippingServiceGroup
							key={ serviceGroup }
							title={ serviceGroup }
							services={ this.props.services.filter( service => service.group === serviceGroup ) }
						/>
					);
				} ) }
			</div>
		);
	}
} );
