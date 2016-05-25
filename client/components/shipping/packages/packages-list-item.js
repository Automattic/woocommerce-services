import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';
import classNames from 'classnames';

const renderIcon = ( isLetter, isError ) => {
	let icon;
	if ( isError ) {
		icon = 'notice';
	} else {
		icon = isLetter ? 'mail' : 'flip-horizontal';
	}
	return (
		<Gridicon icon={ icon } size={ isError ? 29 : 18 } />
	);
}

const PackagesListItem = ( {
	index,
	data,
	dimensionUnit,
	onRemove,
	editPackage,
	hasError,
} ) => {
	return (
		<div className={ classNames( 'wcc-shipping-packages-list-item', { 'wcc-error': hasError } ) }>
			<div className="package-type">
				{ renderIcon( data.is_letter, hasError ) }
			</div>
			<div className="package-name">
				<a href="#" onClick={ ( event ) => {
					event.preventDefault();
					editPackage( Object.assign( {}, data, { index } ) );
				} }>
					{ data.name }
				</a>
			</div>
			<div className="package-dimensions">
				<span>{ data.inner_dimensions } { dimensionUnit }</span>
			</div>
			<div className="package-actions">
				<Button compact borderless className="remove-package" onClick={ onRemove }>
					<Gridicon icon="cross-small" size={ 18 } />
				</Button>
			</div>
		</div>
	);
}

PackagesListItem.propTypes = {
	index: PropTypes.number.isRequired,
	data: PropTypes.shape( {
		name: PropTypes.string.isRequired,
		is_letter: PropTypes.bool,
		inner_dimensions: PropTypes.string.isRequired,
	} ).isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	onRemove: PropTypes.func.isRequired,
	editPackage: PropTypes.func.isRequired,
};

export default PackagesListItem;
