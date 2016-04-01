import React from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';

const PackagesListItem = ( {
	type,
	name,
	dimensions
} ) => (
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

PackagesListItem.propTypes = {
	type: React.PropTypes.string,
	name: React.PropTypes.string,
	dimensions: React.PropTypes.string,
};

export default PackagesListItem;
