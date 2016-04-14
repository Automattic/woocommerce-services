import React from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';

const PackagesListItem = ( {
	id,
	type,
	name,
	dimensions,
	onRemove
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
			<Button compact borderless onClick={ onRemove }>
				<Gridicon icon="cross" size={ 18 } />
			</Button>
		</div>
	</div>
);

PackagesListItem.propTypes = {
	id: React.PropTypes.string,
	type: React.PropTypes.string,
	name: React.PropTypes.string,
	dimensions: React.PropTypes.string,
	onRemove: React.PropTypes.func,
};

export default PackagesListItem;
