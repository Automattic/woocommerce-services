import React, { PropTypes } from 'react';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormButton from 'components/forms/form-button';
import FormCheckbox from 'components/forms/form-checkbox';
import FormInputValidation from 'components/forms/form-input-validation';
import Dialog from 'components/dialog';
import AddPackagePresets from './add-package-presets';

class AddPackageDialog extends React.Component {

	constructor() {
		super();
		this.state = {
			hideOuterDimensions: true,
			form: {
				name: '',
				outer_dimensions: '',
				inner_dimensions: '',
				package_weight: '',
				max_weight: '',
			}
		};
	}

	getDialogButtons() {
		return [
			<FormLabel className="share-package-option">
				<FormCheckbox checked={ true } readOnly={ true } />
				<span>Save package to use in other shipping methods</span>
			</FormLabel>,
			<FormButton>Add package</FormButton>,
		];
	}

	renderOuterDimensionsToggle() {
		if ( this.state.hideOuterDimensions ) {
			return (
				<a
					href="#"
					className="form-setting-explanation"
					onClick={ ( evt ) => {
						evt.preventDefault();
						this.setState( { hideOuterDimensions: false } );
					} }>
					Define exterior dimensions
				</a>
			);
		}
	}

	renderOuterDimensions() {
		return this.state.hideOuterDimensions ? null : (
			<FormFieldset>
				<FormLabel>Outer Dimensions (L x W x H)</FormLabel>
				<FormTextInput
					name="outer_dimensions"
					placeholder="100.25 x 25.25 x 5.75"
					value={ this.state.form.outer_dimensions }
					onChange={ () => {} }
					readOnly={ false }
				/>
			</FormFieldset>
		);
	}

	usePresetValues( idx ) {
		const preset = this.props.presets.boxes[idx];
		const newForm = {
			name: preset.name,
			inner_dimensions: preset.inner_length + ' x ' + preset.inner_width + ' x ' + preset.inner_height,
			outer_dimensions: preset.outer_length + ' x ' + preset.outer_width + ' x ' + preset.outer_height,
			package_weight: preset.box_weight,
			max_weight: preset.max_weight,
		};
		this.setState( { form: newForm } );
	}

	updateFormTextField( field, value ) {
		console.log(field, value);
		const newForm = Object.assign( {}, this.state.form );
		newForm[field] = value;
		this.setState( { form: newForm } );
	}

	render() {
		console.log(this.props.presets);
		console.log(this.state)
		return (
			<Dialog
				isVisible={ true }
				additionalClassNames="wcc-modal wcc-shipping-add-edit-package-dialog"
				onClose={ this.props.onClose }
				buttons={ this.getDialogButtons() }>
				<FormSectionHeading>Add a package</FormSectionHeading>
				<AddPackagePresets
					presets={ this.props.presets }
					onSelectDefault={ ( value ) => { console.log( 'select default: ' + value ); } }
					onSelectPreset={ idx => this.usePresetValues( idx ) }
				/>
				<FormFieldset>
					<FormLabel htmlFor="package_name">Package name</FormLabel>
					<FormTextInput
						id="package_name"
						name="package_name"
						placeholder="The customer will see this during checkout"
						onChange={ event => this.updateFormTextField( 'package_name', event.target.value ) }
						value={ this.state.form.name }
						readOnly={ false }
					/>
				</FormFieldset>
				<FormFieldset>
					<FormLabel>Inner Dimensions (L x W x H)</FormLabel>
					<FormTextInput
						name="inner_dimensions"
						placeholder="100 x 25 x 5.5"
						value={ this.state.form.inner_dimensions }
						onChange={ () => {} }
						readOnly={ false }
					/>
					{ this.renderOuterDimensionsToggle() }
				</FormFieldset>
				{ this.renderOuterDimensions() }
				<FormFieldset className="wcc-shipping-add-package-weight-group">
					<div className="wcc-shipping-add-package-weight">
						<FormLabel htmlFor="package_weight">Package weight</FormLabel>
						<FormTextInput
							id="package_weight"
							name="package_weight"
							placeholder="Weight of box"
							value={ this.state.form.package_weight }
							onChange={ () => {} }
							readOnly={ false }
						/>
					</div>
					<div className="wcc-shipping-add-package-weight">
						<FormLabel htmlFor="max_weight">Max weight</FormLabel>
						<FormTextInput
							id="max_weight"
							name="max_weight"
							placeholder="Max weight"
							value={ this.state.form.max_weight }
							onChange={ () => {} }
							readOnly={ false }
						/>
						{ this.props.weightUnit }
					</div>
					<FormSettingExplanation> Define both the weight of the empty box and the max weight it can hold</FormSettingExplanation>
				</FormFieldset>
			</Dialog>
		);
	}
}

AddPackageDialog.propTypes = {
	onClose: PropTypes.func.isRequired,
	presets: PropTypes.object.isRequired,
	weightUnit: PropTypes.string.isRequired,
};

export default AddPackageDialog;
