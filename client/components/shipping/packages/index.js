import React, { PropTypes } from 'react';
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
	propTypes: {
		presets: PropTypes.object.isRequired,
		packages: PropTypes.array.isRequired,
		removePackage: PropTypes.func.isRequired,
		addPackage: PropTypes.func.isRequired,
	},
	render: function() {
		return (
			<div>
				<PackagesList packages={ this.props.packages } removePackage={ this.props.removePackage } />
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
				<AddPackageDialog
					presets={ this.props.presets }
					weightUnit={ this.props.weightUnit }
					onClose={ () => this.setState( { addingPackage: false } ) }
					saveBox={ this.props.addPackage }
				/>
			);
		}
	},
} );
