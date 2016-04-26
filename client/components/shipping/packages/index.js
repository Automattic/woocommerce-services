import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormButton from 'components/forms/form-button';
import PackagesList from './packages-list';
import AddPackageDialog from './add-package';
import * as PackagesActions from 'state/form/packages/actions';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

const Packages = React.createClass( {
	displayName: 'Packages',
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
						onClick={ () => this.props.packagesActions.addPackage() }
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
					onClose={ () => this.props.packagesActions.dismissModal() }
					saveBox={ this.props.addPackage }
				/>
			);
		}
	},
} );

const mapStateToProps = ( state ) => {
	return state.form.packages;
};

const mapDispatchToProps = ( dispatch ) => ( {
	packagesActions: bindActionCreators( PackagesActions, dispatch ),
} );

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Packages );
