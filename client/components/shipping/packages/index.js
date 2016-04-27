import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormButton from 'components/forms/form-button';
import PackagesList from './packages-list';
import AddPackageDialog from './add-package';

const Packages = React.createClass( {
	displayName: 'Packages',
	propTypes: {
		addPackage: PropTypes.func.isRequired,
		showModal: PropTypes.bool,
		dismissModal: PropTypes.func.isRequired,
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
						onClick={ this.props.addPackage }
					>
						Add a package
					</FormButton>
				</FormFieldset>
			</div>
		);
	},
	renderAddPackage: function() {
		if ( this.props.showModal ) {
			return (
				<AddPackageDialog
					{ ...this.props }
					onClose={ this.props.dismissModal }
				/>
			);
		}
	},
} );

export default Packages;

