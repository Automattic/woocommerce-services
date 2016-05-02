import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormButton from 'components/forms/form-button';
import PackagesList from './packages-list';
import AddPackageDialog from './add-package';

export default React.createClass( {
	displayName: 'Packages',
	propTypes: {
		presets: PropTypes.object.isRequired,
		packages: PropTypes.array.isRequired,
		removePackage: PropTypes.func.isRequired,
		addPackage: PropTypes.func.isRequired,
		formState: PropTypes.object.isRequired,
		updateFormState: PropTypes.func.isRequired,
	},
	render: function() {
		const { packages, removePackage, updateFormState } = this.props;
		return (
			<div>
				<PackagesList packages={ packages } removePackage={ removePackage } />
				{ this.renderAddPackage() }
				<FormFieldset className="add-package-button-field">
					<FormButton
						type="button"
						isPrimary={ false }
						compact
						onClick={ () => updateFormState( 'addingPackage', true ) }
					>
						Add a package
					</FormButton>
				</FormFieldset>
			</div>
		);
	},
	renderAddPackage: function() {
		const { presets, weightUnit, formState, updateFormState, addPackage } = this.props;
		if ( formState.addingPackage ) {
			return (
				<AddPackageDialog
					presets={ presets }
					weightUnit={ weightUnit }
					onClose={ () => updateFormState( 'addingPackage', false ) }
					saveBox={ addPackage }
					formState={ formState.addPackageState || {} }
					updateFormState={ ( value ) => updateFormState( 'addPackageState', value ) }
				/>
			);
		}
	},
} );
