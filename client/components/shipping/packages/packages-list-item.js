import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';
import classNames from 'classnames';
import trim from 'lodash/trim';

const renderIcon = ( isLetter, isError, onClick ) => {
	let icon;
	if ( isError ) {
		icon = 'notice';
	} else {
		icon = isLetter ? 'mail' : 'flip-horizontal';
	}
	return (
		<a href="#" onClick={ onClick }>
			<Gridicon icon={ icon } className="package-type-icon" size={ isError ? 29 : 18 } />
		</a>
	);
};

const PackagesListItem = ( {
	index,
	data,
	dimensionUnit,
	onRemove,
	editPackage,
	hasError,
} ) => {
	const openModal = ( event ) => {
		event.preventDefault();
		editPackage( Object.assign( {}, data, { index } ) );
	};

	return (
		<div className={ classNames( 'wcc-shipping-packages-list-item', { 'wcc-error': hasError } ) }>
			<div className="package-type">
				{ renderIcon( data.is_letter, hasError, openModal ) }
			</div>
			<div className="package-name">
				<a href="#" onClick={ openModal }>
					{
						data.name && '' !== trim( data.name )
						? data.name
						: <span style={ { color: 'gray' } }>Untitled</span>
					}
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
};

PackagesListItem.propTypes = {
	index: PropTypes.number.isRequired,
	data: PropTypes.shape( {
		name: PropTypes.string,
		is_letter: PropTypes.bool,
		inner_dimensions: PropTypes.string,
	} ).isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	onRemove: PropTypes.func.isRequired,
	editPackage: PropTypes.func.isRequired,
};

export default PackagesListItem;
