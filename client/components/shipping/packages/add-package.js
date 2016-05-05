import React, { PropTypes } from 'react';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormButton from 'components/forms/form-button';
import FormCheckbox from 'components/forms/form-checkbox';
import Dialog from 'components/dialog';
import AddPackagePresets from './add-package-presets';
import { translate as __ } from 'lib/mixins/i18n';

class AddPackageDialog extends React.Component {

	constructor() {
		super();
		this.updateFormTextField = this.updateFormTextField.bind( this );
	}

	getDialogButtons() {
		const {
			mode,
			savePackage,
			packageData,
		} = this.props;
		return [
			<FormLabel className="share-package-option">
				<FormCheckbox checked={ true } readOnly={ true } />
				<span>Save package to use in other shipping methods</span>
			</FormLabel>,
			<FormButton onClick={ () => savePackage( packageData ) }>{ ( 'add' === mode ) ? __( 'Add package' ) : __( 'Apply changes' ) }</FormButton>,
		];
	}

	renderOuterDimensionsToggle() {
		const {
			showOuterDimensions,
			packageData,
			toggleOuterDimensions,
		} = this.props;

		if ( ! showOuterDimensions && ! packageData.outer_dimensions ) {
			return (
				<a
					href="#"
					className="form-setting-explanation"
					onClick={ ( evt ) => {
						evt.preventDefault();
						toggleOuterDimensions();
					} }>
					Define exterior dimensions
				</a>
			);
		}
	}

	renderOuterDimensions( value ) {
		const {
			showOuterDimensions,
			packageData,
		} = this.props;

		return ( showOuterDimensions || packageData.outer_dimensions ) ? (
			<FormFieldset>
				<FormLabel>Outer Dimensions (L x W x H)</FormLabel>
				<FormTextInput
					name="outer_dimensions"
					placeholder="100.25 x 25.25 x 5.75"
					value={ value }
					onChange={ this.updateFormTextField }
				/>
			</FormFieldset>
		) : null;
	}

	usePresetValues( idx ) {
		const preset = this.props.presets.boxes[idx];
		this.props.updatePackagesField( {
			index: null,
			...preset,
		} );
	}

	updateFormTextField( event ) {
		const {
			name,
			value,
		} = event.target;
		this.props.updatePackagesField( { [name]: value } );
	}

	useDefaultField( value ) {
		this.props.updatePackagesField( {
			index: null,
			is_letter: 'envelope' === value ? true : false,
		} );
	}

	render() {
		const {
			dismissModal,
			mode,
			presets,
			weightUnit,
			packageData,
		} = this.props;
		const {
			name,
			inner_dimensions,
			outer_dimensions,
			box_weight,
			max_weight,
		} = packageData;
		return (
			<Dialog
				isVisible={ true }
				additionalClassNames="wcc-modal wcc-shipping-add-edit-package-dialog"
				onClose={ dismissModal }
				buttons={ this.getDialogButtons() }>
				<FormSectionHeading>{ ( 'edit' === mode ) ? __( 'Edit package' ) : __( 'Add a package' ) }</FormSectionHeading>
				{ ( 'add' === mode ) ? (
					<AddPackagePresets
						presets={ presets }
						onSelectDefault={ ( value ) => this.useDefaultField( value ) }
						onSelectPreset={ ( idx ) => this.usePresetValues( idx ) }
					/>
				) : null }
				<FormFieldset>
					<FormLabel htmlFor="name">Package name</FormLabel>
					<FormTextInput
						id="name"
						name="name"
						placeholder="The customer will see this during checkout"
						value={ name }
						onChange={ this.updateFormTextField }
					/>
				</FormFieldset>
				<FormFieldset>
					<FormLabel>Inner Dimensions (L x W x H)</FormLabel>
					<FormTextInput
						name="inner_dimensions"
						placeholder="100 x 25 x 5.5"
						value={ inner_dimensions }
						onChange={ this.updateFormTextField }
					/>
					{ this.renderOuterDimensionsToggle() }
				</FormFieldset>
				{ this.renderOuterDimensions( outer_dimensions ) }
				<FormFieldset className="wcc-shipping-add-package-weight-group">
					<div className="wcc-shipping-add-package-weight">
						<FormLabel htmlFor="box_weight">Package weight</FormLabel>
						<FormTextInput
							id="box_weight"
							name="box_weight"
							placeholder="Weight of box"
							value={ box_weight }
							onChange={ this.updateFormTextField }
						/>
					</div>
					<div className="wcc-shipping-add-package-weight">
						<FormLabel htmlFor="max_weight">Max weight</FormLabel>
						<FormTextInput
							id="max_weight"
							name="max_weight"
							placeholder="Max weight"
							value={ max_weight }
							onChange={ this.updateFormTextField }
						/>
						<span className="wcc-shipping-add-package-weight-unit">{ weightUnit }</span>
					</div>
					<FormSettingExplanation> Define both the weight of the empty box and the max weight it can hold</FormSettingExplanation>
				</FormFieldset>
			</Dialog>
		);
	}
}

AddPackageDialog.propTypes = {
	dismissModal: PropTypes.func.isRequired,
	presets: PropTypes.object.isRequired,
	weightUnit: PropTypes.string.isRequired,
	mode: PropTypes.string.isRequired,
	updatePackagesField: PropTypes.func.isRequired,
	showOuterDimensions: PropTypes.bool,
};

AddPackageDialog.defaultProps = {
	packageData: {},
};

export default AddPackageDialog;
