import React from 'react';
import Gridicon from 'components/gridicon';
import Button from 'components/button';
import * as PackagesActions from 'state/form/packages/actions';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

const PackagesListItem = ( {
	index,
	data,
	dimensionUnit,
	onRemove,
	packagesActions,
} ) => (
	<div className="wcc-shipping-packages-list-item">
		<div className="package-type">
			<Gridicon icon={ data.is_letter ? 'mail' : 'flip-horizontal' } size={ 18 } />
		</div>
		<div className="package-name">
			<a onClick={ () => {
				packagesActions.editPackage( Object.assign( {}, data, { index } ) );
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
	id: React.PropTypes.string,
	type: React.PropTypes.string,
	name: React.PropTypes.string,
	dimensions: React.PropTypes.string,
	dimensionUnit: React.PropTypes.string,
	onRemove: React.PropTypes.func,
};

const mapDispatchToProps = ( dispatch ) => ( {
	packagesActions: bindActionCreators( PackagesActions, dispatch ),
} );

export default connect(
	null,
	mapDispatchToProps
)( PackagesListItem );