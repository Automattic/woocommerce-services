import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';

const PackagesListItem = ( {
	index,
	data,
	dimensionUnit,
	onRemove,
	editPackage,
} ) => (
	<div className="wcc-shipping-packages-list-item">
		<div className="package-type">
			<Gridicon icon={ data.is_letter ? 'mail' : 'flip-horizontal' } size={ 18 } />
		</div>
		<div className="package-name">
			<a onClick={ () => {
				editPackage( Object.assign( {}, data, { index } ) );
			} }>
				{ data.name }
			</a>
		</div>
		<div className="package-dimensions">
			<span>{ data.dimensions } { dimensionUnit }</span>
		</div>
		<div className="package-actions">
			<Button compact borderless className="remove-package" onClick={ onRemove }>
				<Gridicon icon="cross-small" size={ 18 } />
			</Button>
		</div>
	</div>
);

PackagesListItem.propTypes = {
	index: PropTypes.number.isRequired,
	data: PropTypes.shape( {
		name: PropTypes.string.isRequired,
		is_letter: PropTypes.bool.isRequired,
		dimensions: PropTypes.string.isRequired,
	} ).isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	onRemove: PropTypes.func.isRequired,
	editPackage: PropTypes.func.isRequired,
};

export default PackagesListItem;
