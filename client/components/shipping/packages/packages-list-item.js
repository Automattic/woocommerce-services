import React from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';

const PackagesListItem = ( {
	is_letter,
	name,
	dimensions,
	dimensionUnit,
	onRemove,
} ) => (
	<div className="wcc-shipping-packages-list-item">
		<div className="package-type">
			<Gridicon icon={ is_letter ? 'mail' : 'flip-horizontal' } size={ 18 } />
		</div>
		<div className="package-name">
			<a href="#">{ name }</a>
		</div>
		<div className="package-dimensions">
			<span>{ dimensions } { dimensionUnit }</span>
		</div>
		<div className="package-actions">
			<Button compact borderless className="remove-package" onClick={ onRemove }>
				<Gridicon icon="cross-small" size={ 18 } />
			</Button>
		</div>
	</div>
);

PackagesListItem.propTypes = {
	id: React.PropTypes.string,
	type: React.PropTypes.string,
	name: React.PropTypes.string,
	dimensions: React.PropTypes.string,
	dimensionUnit: React.PropTypes.string,
	onRemove: React.PropTypes.func,
};

export default PackagesListItem;
