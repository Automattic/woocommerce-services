import React from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';

export default React.createClass( {
	displayName: 'PackagesListItem',
	render: function() {
		const {
			type,
			name,
			dimensions
		} = this.props;
		return (
			<div className="wcc-shipping-packages-list-item">
				<div className="package-type">
					<Gridicon icon={ type } />
				</div>
				<div className="package-name">
					<a href="#">{ name }</a>
				</div>
				<div className="package-dimensions">
					<span>{ dimensions }</span>
				</div>
				<div className="package-actions">
					<Button compact borderless>
						<Gridicon icon="cross" size={ 18 } />
					</Button>
				</div>
			</div>
		);
	}
} );