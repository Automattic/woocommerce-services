import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormButton from 'components/forms/form-button';
import PackagesList from './packages-list';
import AddPackageDialog from './add-package';

export default React.createClass( {
	displayName: 'Packages',
	getInitialState: function() {
		return {
			addingPackage: false,
		};
	},
	render: function() {
		return (
			<div>
				<PackagesList { ...this.props } />
				{ this.renderAddPackage() }
				<FormFieldset className="add-package-button-field">
					<FormButton
						type="button"
						isPrimary={ false }
						compact
						onClick={ () => this.setState( { addingPackage: true } ) }
					>
						Add a package
					</FormButton>
				</FormFieldset>
			</div>
		);
	},
	renderAddPackage: function() {
		if ( this.state.addingPackage ) {
			return (
				<AddPackageDialog { ...this.props } onClose={ () => this.setState( { addingPackage: false } ) } />
			);
		}
	},
} );
