import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormButton from 'components/forms/form-button';
import PackagesList from './packages-list';
import AddPackageDialog from './add-package';
import { translate as __ } from 'lib/mixins/i18n';

const Packages = ( props ) => {
	return (
		<div>
			<PackagesList { ...props } />
			<AddPackageDialog { ...props } />
			<FormFieldset className="add-package-button-field">
				<FormButton
					type="button"
					isPrimary={ false }
					compact
					onClick={ props.addPackage }
				>
					{ __( 'Add a package' ) }
				</FormButton>
			</FormFieldset>
		</div>
	);
};

Packages.propTypes = {
	addPackage: PropTypes.func.isRequired,
	showModal: PropTypes.bool,
	dismissModal: PropTypes.func.isRequired,
};

export default Packages;

