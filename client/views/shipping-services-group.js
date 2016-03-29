import React from 'react';
import ShippingServiceEntry from './shipping-services-entry';

export default React.createClass( {
	displayName: 'ShippingServiceGroup',

	propTypes: {
		title: React.PropTypes.string.isRequired,
		services: React.PropTypes.array.isRequired,
	},

	render: function() {
		return (
			<div className='wcc-shipping-services-group'>
				<h4>
					{ this.props.title }
				</h4>
				{ this.props.services.map( service => {
					return (
						<ShippingServiceEntry
							key={ service.title }
							enabled={ service.enabled }
							title={ service.title }
							adjustment={ service.adjustment }
							adjustmentType={ service.adjustment_type }
						/>
					);
				} ) }
			</div>
		);
	}
} );
